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
        $string = htmlspecialchars($string, ENT_QUOTES);

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

    public static function fileUploadMaxSize($human = false) {
        $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = self::_parseSize(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = self::_parseSize(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $human ? self::_humanFileSize($max_size) : $max_size;
    }

    private static function _parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }

    private static function _humanFileSize($size,$unit="") {
        if( (!$unit && $size >= 1<<30) || $unit == "GB")
            return number_format($size/(1<<30),2)."GB";
        if( (!$unit && $size >= 1<<20) || $unit == "MB")
            return number_format($size/(1<<20),2)."MB";
        if( (!$unit && $size >= 1<<10) || $unit == "KB")
            return number_format($size/(1<<10),2)."KB";
        return number_format($size)." bytes";
    }
}