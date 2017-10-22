<?php

namespace Portal\Core;

final class Utils {
    /**
     * Utils constructor.
     * "Static" class
     */
    private function __construct(){}

    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public static function nameToUrl($name) {
        return strtolower('/' . preg_replace('/\B([A-Z])/', '-$1', $name));
    }

    public static function nameToKey($name) {
        setlocale(LC_ALL, "en_US.utf8");
        $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $key = preg_replace('/[\W\s]/i', '-', $key);
        $key = preg_replace('/-{2,}/i', '-', $key);

        return strtolower(trim($key, '-'));
    }

    public static function sanitizeText($text)
    {
        if (strpos($text, '<') !== false) {
            // This will strip extra whitespace for us.
            $text = self::removeTags($text, true);
        } else {
            $text = trim(preg_replace('/[\r\n\t ]+/', ' ', $text));
        }

        $found = false;
        while (preg_match('/%[a-f0-9]{2}/i', $text, $match)) {
            $text = str_replace($match[0], '', $text);
            $found = true;
        }

        if ($found) {
            // Strip out the whitespace that may now exist after removing the octets.
            $text = trim(preg_replace('/ +/', ' ', $text));
        }

        return $text;
    }

    public static function removeTags($string, $removeWhitespace = false)
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);

        if ($removeWhitespace) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim($string);
    }

    public static function extractDomain($url) {
        preg_match('/^(?:https?:\/\/|www\.)[^\/]+/i', $url, $match);
        return isset($match[0]) ? $match[0] : null;
    }

    public static function extractSiteName($url) {
        return preg_replace('|^https?://(?:www\.)?|i', '', $url);
    }

    public static function removeProtocol($url) {
        return preg_replace('|^https?://(?:www\.)?|i', '//', $url);
    }

    public static function stringToFloat($string) {
        $string = preg_replace('/[^0-9\.,]/i', '', $string);
        $string = str_ireplace(',','.', $string);
        return floatval($string);
    }

    public static function joinDomainWithUrl($url, $domain) {
        $withDomain = self::extractDomain($url);
        if($withDomain === null) {
            if(strpos($url, '/') === 0) {
                return $domain . $url;
            }
            return $domain . '/' . $url;
        }
        return $url;
    }
}