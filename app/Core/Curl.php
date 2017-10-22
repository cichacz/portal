<?php

namespace Portal\Core;

class Curl
{
    private $headers;
    private $user_agent;
    private $compression;
    private $cookie_file;
    private $proxy;

    private static $cookie_path = '../cache/';

    public function __construct($cookies = true, $cookie = 'cookie.txt', $compression = 'gzip', $proxy = '')
    {
        $this->headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $this->headers[] = 'Accept-Language: en-us,en;q=0.5';
        $this->headers[] = 'Connection: Keep-Alive';

        $this->user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';
        $this->compression = $compression;
        $this->proxy = $proxy;
        $this->cookies = $cookies;

        if ($this->cookies == true) {
            $this->cookie($cookie);
        }
    }

    public function get(&$url, $postData = null, $changeUrl = true)
    {
        $process = curl_init($url);

        curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);

        if ($this->cookies == true) {
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }

        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);

        if ($this->proxy) {
            curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        }

        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($process, CURLOPT_MAXREDIRS, 10);

        if (!empty($postData)) {
            curl_setopt($process, CURLOPT_POST, true);
            curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        $result = curl_exec($process);

        $code = curl_getinfo($process, CURLINFO_HTTP_CODE);

        if($changeUrl) {
            $url = curl_getinfo($process, CURLINFO_EFFECTIVE_URL);
        }

        curl_close($process);

        return $code == 200 ? $result : $code;
    }

    protected function cookie($cookie_file)
    {
        $cookie_file = realpath(self::$cookie_path) . DIRECTORY_SEPARATOR . $cookie_file;
        if (file_exists($cookie_file)) {
            $this->cookie_file = $cookie_file;
        } else {
            $res = fopen($cookie_file, 'w') or $this->error('Nie udało się otworzyć pliku. Sprawdź uprawnienia.');
            $this->cookie_file = $cookie_file;
            fclose($res);
        }
    }

    protected function error($msg) {
        throw new \Exception($msg);
    }

    public static function clearCookies($cookie_file = 'cookie.txt')
    {
        $cookie_file = self::$cookie_path . $cookie_file;
        unlink($cookie_file);
    }
}