<?php

namespace DzId\LaravelHtmlMinifier\Middleware;

class MinifyJavascript extends Minifier
{
    protected static $allowInsertSemicolon;

    protected function apply()
    {
        static::$minifyJavascriptHasBeenUsed = true;

        $obfuscate = (bool) config("laravel-html-minifier.obfuscate_javascript", false);

        static::$allowInsertSemicolon = (bool) config("laravel-html-minifier.js_automatic_insert_semicolon", true);

        foreach ($this->getByTag("script") as $el)
        {
            $value = $this->replace($el->nodeValue);

            /* Apakah fungsi obfuscate diaktifkan? */
            if ($obfuscate)
            {
                $value = $this->obfuscate($value);
            }
            $el->nodeValue = "";
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }

    protected function insertSemicolon($value)
    {
        /**
         * menghapus semua komentar
         */
        $value = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $value);
        /**
         * Menghapus baris baru string yang didalam tanda kutip (`)
         * supaya tidak ada kesalahan saat penambahan semicolon
         * 
         * dari :
         *  `
         *   example...
         *   example...
         *  `
         * 
         * ke :
         *   `example... example...`
         *
         */
        $value = preg_replace_callback('/(`[\S\s]*?[^\\\`]`)/', function($m) {
            return preg_replace('/\n+/', '', $m[1]);
        }, $value);

        $result = [];
        $code = explode("\n", trim($value));

        $patternRegex = [
            // temukan string yang diakhiri dengan {, [, (, ,, ;, =>, :, ?
            '#(?:({|\[|\(|,|;|=>|\:|\?))$#',
            // temukan blank spasi
            '#^\s*$#',
            // temukan string pertama dan terakhir do, else
            '#^(do|else)$#'
        ];

        $loop = 0;

        foreach ($code as $line)
        {
            $loop++;
            $insert = false;
            $shouldInsert = true;

            foreach ($patternRegex as $pattern)
            {
                // jika pattern tidak cocok artinya boleh ditambahkan semicolon
                $match = preg_match($pattern, trim($line));
                $shouldInsert = $shouldInsert && (bool) !$match;
            }

            if ($shouldInsert)
            {
                $i = $loop;
        
                while (true)
                {
                    if ($i >= count($code))
                    {
                        $insert = true;
                        break;
                    }
        
                    $c = trim($code[$i]);
                    $i++;
                    
                    if (!$c)
                    {
                        continue;
                    }

                    $insert = true;
                    $regex = ['#^(\?|\:|,|\.|{|}|\)|\])#'];

                    foreach ($regex as $r)
                    {
                        $insert = $insert && (bool) !preg_match($r, $c);
                    }

                    break;
                }
            }

            if ($insert)
            {
                $result[] = sprintf("%s;", $line);
            }
            else
            {
                $result[] = $line;
            }
            
        }

        return join("\n", $result);
    } 

    protected function replace($value)
    {
        if (static::$allowInsertSemicolon)
        {
            $value = $this->insertSemicolon($value);
        }

        return trim(preg_replace([
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove the last semicolon
            '#;+\}#',
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // --ibid. From `foo['bar']` to `foo.bar`
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
        ],[
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3'
        ], $value));
    }

    protected function obfuscate($value)
    {
        $ords = [];
 
        for ($i = 0; $i < strlen($value); $i++)
        {
            $ords[] = ord($value[$i]);
        }

        $template = sprintf("
        eval(((_, __, ___, ____, _____, ______, _______) => {
            ______[___](x => _______[__](String[____](x)));
            return _______[_](_____)
        })('join', 'push', 'forEach', 'fromCharCode', '', %s, []))

        ", json_encode($ords));

        return $this->replace($template);
    }
}
