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
    /* Return Values */
    var $content;
    var $headers;
    var $doctype;

    function __construct($url, $streaming = false) {
        $this->url = $url;
        $this->streaming = $streaming;
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->set_referer(defined('KNPROXY_REFERER') ? KNPROXY_REFERER : 'none');
        $this->is_https = (strtolower(substr($this->url, 0, 6)) == 'https:');
        if (!function_exists('curl_init'))
            $this->mode = 'filesockets';
    }

    function set_request_headers($header = array()) {
        if (!is_array($header) || count($header) < 2)
            return;
        $this->request_headers[$header[0]] = $header[1];
    }

    function force_mode($mode = 'filesockets') {
        $this->mode = $mode;
    }

    function set_referer($referer = 'none') {
        switch ($referer) {
            case 'pseudo': $this->referer = $this->url; break;
            case 'none': $this->referer = ''; break;
            case 'auto': $this->referer = ''; break;
        }
    }

    function set_url($url) {
        $this->__construct($url);
    }

    function set_cookies($cookies) {
        $this->cookies = $cookies;
    }

    function set_post($post) {
        $this->http_post = $post;
    }

    function set_get($getArray) {
        $get = Array();
        foreach ($getArray as $key => $value) {
            $get[] = urlencode($key) . '=' . urlencode($value);
        }
        $this->http_get = implode('&', $get);
    }

    function set_http_creds($unam, $pass) {
        $this->httpauth = ($unam !== false) ? $unam . ':' . $pass : '';
    }

    function getPost() {
        if (!is_array($this->http_post) || count($this->http_post) < 1) return '';
        return http_build_query($this->http_post);
    }

    function getCookies() {
        if (!is_array($this->cookies) || count($this->cookies) < 1) return '';
        $ret = array();
        foreach ($this->cookies as $name => $value) {
            $ret[] = $name . '=' . $value;
        }
        return implode(';', $ret);
    }

    function head() {
        if (count($this->http_post) > 0) return false;
        $ch = curl_init();
        $url = $this->url;
        if ($this->http_get != '') {
            $url .= (strpos($url, '?') !== false) ? '&' . $this->http_get : '?' . $this->http_get;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($this->is_https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        if (count($this->cookies) > 0) curl_setopt($ch, CURLOPT_COOKIE, $this->getCookies());
        if ($this->httpauth != '') curl_setopt($ch, CURLOPT_USERPWD, $this->httpauth);
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        if (count($this->request_headers) > 0) {
            $hdr = array();
            foreach ($this->request_headers as $key => $val) $hdr[] = "$key: $val";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->headers = curl_exec($ch);
        curl_close($ch);
        return true;
    }

    // ... [Streaming and socket methods omitted for brevity, ensure they are also properly closed with '}' ] ...

    function refined_headers() {
        $headers = explode("\n", preg_replace('~\r~', '', $this->headers));
        $head = array();
        if (is_array($headers) && count($headers) > 0) {
            foreach ($headers as $line) {
                if (preg_match('~^http/\d+.\d+\s(\d+)\s~iUs', $line, $matches)) {
                    $head['HTTP_RESPONSE'] = (int)$matches[1];
                    continue;
                } else {
                    $pair = preg_split('~:~', $line, 2);
                    $key = isset($pair[0]) ? preg_replace('~\s~', '', strtoupper($pair[0])) : '';
                    
                    switch ($key) {
                        case 'LOCATION': $head['HTTP_LOCATION'] = trim($pair[1]); break;
                        case 'CONTENT-TYPE': $this->doctype = trim($pair[1]); $head["CONTENT_TYPE"] = trim($pair[1]); break;
                        // ... [Add other cases here] ...
                        default:
                            if (!empty($key)) {
                                $head['UNKNOWN'][] = Array($pair[0], $pair[1]);
                            }
                            break;
                    }
                }
            }
        }
        return $head;
    }
}
?>
