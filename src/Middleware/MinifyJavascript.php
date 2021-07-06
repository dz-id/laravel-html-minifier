<?php

namespace DzId\LaravelHtmlMinifier\Middleware;

class MinifyJavascript extends Minifier
{
    protected function apply()
    {
        static::$minifyJavascriptHasBeenUsed = true;

        $obfuscate = (bool) config("laravel-html-minifier.obfuscate_javascript", false);

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

    protected function replace($value)
    {
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
