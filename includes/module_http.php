<?php
/*************
 * HTTP REQUEST MODULE FOR KNPROXY THETA
 * AUTHOR: CQZ
 **************/
@include_once('class_stream.php');

class knHttp {
    var $url = '';
    var $is_https = false;
    var $user_agent = '';
    var $cookies = Array();
    var $httpauth = "";
    var $http_post = Array();
    var $http_get = '';
    var $ranges = false;
    var $request_headers = Array();
    protected $referer = '';
    protected $streaming = false;
    protected $mode = 'curl';
    var $content;
    var $headers;
    var $doctype;

    function __construct($url, $streaming = false) {
        $this->url = $url;
        $this->streaming = $streaming;
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0';
        $this->set_referer(defined('KNPROXY_REFERER') ? KNPROXY_REFERER : 'none');
        $this->is_https = (strtolower(substr($this->url, 0, 6)) == 'https:');
        if (!function_exists('curl_init')) $this->mode = 'filesockets';
    }

    function set_request_headers($header = array()) {
        if (is_array($header) && count($header) >= 2) {
            $this->request_headers[$header[0]] = $header[1];
        }
    }

    function send() {
        if ($this->streaming) return;
        if ($this->mode != 'curl') return $this->fsockets_send();

        $ch = curl_init();
        $url = $this->url;
        if ($this->http_get != '') {
            $url .= (strpos($url, '?') !== false) ? '&' . $this->http_get : '?' . $this->http_get;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);

        if ($this->is_https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if (count($this->http_post) > 0) {
            curl_setopt($ch, CURLOPT_POST, count($this->http_post));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPost());
        }

        if (count($this->cookies) > 0) curl_setopt($ch, CURLOPT_COOKIE, $this->getCookies());
        if ($this->httpauth != '') curl_setopt($ch, CURLOPT_USERPWD, $this->httpauth);
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        $raw = curl_exec($ch);
        $this->doctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $spl = preg_split('~\r*\n\r*\n~', $raw, 2);
        $this->headers = $spl[0];
        if (isset($spl[1])) {
            $this->content = $spl[1];
        }
    }

    function getPost() {
        return http_build_query($this->http_post);
    }

    function getCookies() {
        $ret = array();
        foreach ($this->cookies as $name => $value) $ret[] = "$name=$value";
        return implode(';', $ret);
    }

    function fsockets_send() {
        // Implementation for non-curl environments
        return; 
    }

    function refined_headers() {
        $headers = explode("\n", preg_replace('~\r~', '', $this->headers));
        $head = array();
        foreach ($headers as $line) {
            if (empty($line)) continue;
            $pair = explode(':', $line, 2);
            $key = strtoupper(trim($pair[0]));
            if ($key == 'CONTENT-TYPE') {
                $this->doctype = trim($pair[1]);
                $head["CONTENT_TYPE"] = trim($pair[1]);
            }
        }
        return $head;
    }
}
?>
