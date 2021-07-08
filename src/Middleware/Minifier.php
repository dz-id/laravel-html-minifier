<?php

namespace DzId\LaravelHtmlMinifier\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use DOMDocument;

abstract class Minifier
{
    protected static $dom;
    protected static $minifyCssHasBeenUsed = false;
    protected static $minifyJavascriptHasBeenUsed = false;

    protected static $isEnable;
    protected static $ignore;

    protected const REGEX_VALID_HTML = "/<html[^>]*>.*<head[^>]*>.*<\/head[^>]*>.*<body[^>]*>.*<\/body[^>]*>.*<\/html[^>]*>/is";

    abstract protected function apply();

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /* Apakah boleh menjalankan minify? */
        if (!$this->shouldProcessMinify($request, $response))
        {
            if (!(static::$dom instanceof DOMDocument))
            {
                return $response;
            }
        }

        $html = $response->getContent();

        $this->loadDom($html);

        return $response->setContent($this->apply());
    }

    protected function shouldProcessMinify($request, $response) : bool
    {
        /* Apakah fungsi laravel-html-minifier.enable dimatikan? */
        if (!$this->isEnable())
        {
            return false;
        }

        /* Apakah responnya adalah json? */
        if ($response instanceof JsonResponse)
        { 
            return false;
        }

        /* Apakah responnya adalah binnary? */
        if ($response instanceof BinaryFileResponse)
        {
            return false;
        }

        /* Apakah responsenya adalah stream? */
        if ($response instanceof StreamedResponse)
        {
            return false;
        }
        
        /* Apakah responnya dari view() fungsi laravel? */
        if ($response->original instanceof View)
        {
            $data = $response->original->getData();
            /* Apakah ada data ignore_minify di view() dan isinya true? */
            if (isset($data["ignore_minify"]) && $data["ignore_minify"] === true)
            {
                return false;
            }
        }

        foreach ($this->ignore() as $route)
        {
            /* Apakah route yang sedang diakses cocok dengan yang ada di laravel-html-minifier.ignore? */
            if ($request->is($route))
            {
                return false;
            }
        }

        $response = $response->getContent();

        /* Apakah responnya kosong atau NULL? */
        if (empty($response) or !is_string($response) or $this->isEmpty($response))
        {
            return false;
        }

        /*
        * $result = true;
        * 
        * foreach (["html", "body"] as $name)
        * {
        *    $result = $result && !is_null($this->matchHtmlTag($response, $name));
        * }
        *
        * return $result;
        */

        /* Apakah responnya adalah bentuk syntax html yang valid? */
        return $this->validHtml($response);
    }

    protected function isEmpty(string $value) : bool
    {
        return (bool) preg_match("/^\s*$/", $value);
    }

    protected function validHtml(string $value) : bool
    {
        return (bool) preg_match(self::REGEX_VALID_HTML, $value);
    }

    protected function isEnable() : bool
    {
        /* Apakah laravel-html-minifier.enable belum dipanggil? */
        if (is_null(static::$isEnable))
        {
            static::$isEnable = (bool) config("laravel-html-minifier.enable", true);
        }

        return static::$isEnable;
    }

    protected function ignore() : array
    {
        /* Apakah laravel-html-minifier.enable belum dipanggil? */
        if (is_null(static::$ignore))
        {
            static::$ignore = (array) config("laravel-html-minifier.ignore", []);
        }

        return static::$ignore;
    }

    protected function matchHtmlTag(string $value, string $tags)
    {
        if (!preg_match_all("/<" . $tags . "[^>]*>(.*?)<\/" . $tags . "[^>]*>/is", $value, $matches))
        {
            return null;
        }

        return $matches;
    }

    protected function loadDom(string $html, bool $force = false)
    {
        /* Apakah dom sudah diload? */
        if ((static::$dom instanceof DOMDocument))
        {
            /* Apakah dipaksa untuk meload dom lagi? */
            if ($force)
            {
                
            }
            else
            {
                return;
            }
        }

        static::$dom = new DOMDocument;
        @static::$dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE);
    }

    protected function getByTag(string $tags) : array
    {
        $result = [];
        $element = static::$dom->getElementsByTagName($tags);

        foreach ($element as $el)
        {
            $value = $el->nodeValue;

            /* Apakah isinya kosong? */
            if ($this->isEmpty($value))
            {
                continue;
            }

            /* Apakah memiliki attribute ignore--minify? */
            if ($el->hasAttribute("ignore--minify"))
            {
                continue;
            }

            $result[] = $el;
        }

        return $result;
    }

    protected function getByTagOnlyIgnored(string $tags) : array
    {
        $result = [];
        $element = static::$dom->getElementsByTagName($tags);

        foreach ($element as $el)
        {
            $value = $el->nodeValue;

            /* Apakah isinya kosong? */
            if ($this->isEmpty($value))
            {
                continue;
            }

            /* Apakah tidak memiliki attribute ignore--minify? */
            if (!$el->hasAttribute("ignore--minify"))
            {
                continue;
            }

            $result[] = $el;
        }

        return $result;
    }
}
