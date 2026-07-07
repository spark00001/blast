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

    // ==========================================
    // ADD THIS MISSING METHOD TO FIX THE CRASH:
    // ==========================================
    public function set_referer($referer = 'none') {
        switch($referer){
            case 'pseudo':
                $this->referer = $this->url;
                break;
            case 'none':
                $this->referer = '';
                break;
            case 'auto':
                $this->referer = '';
                break;
            default:
                return;
        }
    }
        function set_cookies($cookies){
        $this->cookies = $cookies;
    }
    function set_post($post){
        $this->http_post=$post;
    }
    function set_get($getArray){
        $get=Array();
        foreach($getArray as $key=>$value){
            $get[]=urlencode($key) . '=' . urlencode($value);
        }
        $this->http_get = implode('&',$get); 
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
    function refined_headers(){
        $headers = explode("\n", preg_replace('~\r~', '', $this->headers));
        $head = array();
        foreach($headers as $line){
            if (empty($line)) continue;
            
            // Correctly parse modern HTTP/2 and older HTTP/1.x status lines
            if(preg_match('~^http/(\d+\.?\d*)\s+(\d+)~iUs', $line, $matches)){
                $head['HTTP_RESPONSE'] = (int)$matches[2];
                continue;
            }
            
            $pair = explode(':', $line, 2);
            if (count($pair) < 2) continue;
            
            $key = strtoupper(trim($pair[0]));
            $val = trim($pair[1]);
            
            switch($key){
                case 'LOCATION':
                    $head['HTTP_LOCATION'] = $val;
                    break;
                case 'SET-COOKIE':
                    $cookie = explode(';', $val);
                    if(is_array($cookie) && count($cookie)>1)
                        $cookie[1] = preg_replace('~expires\s*=\s*~iUs', '', $cookie[1]);
                    else
                        $cookie[1] = '';
                    $cookie_ = explode('=', trim($cookie[0]), 2);
                    $head['HTTP_COOKIES'][] = array($cookie_[0], isset($cookie_[1]) ? $cookie_[1] : '', $cookie[1]);
                    break;
                case 'CONTENT-DISPOSITION':
                    $head['CONTENT_DISPOSITION'] = $val;
                    break;
                case 'CONTENT-TYPE':
                    $this->doctype = $val;
                    $head["CONTENT_TYPE"] = $val;
                    break;
                case 'CACHE-CONTROL':
                    $head['CACHE_CONTROL'] = $val;
                    break;
                case 'EXPIRES':
                    $head['EXPIRES'] = $val;
                    break;
                case 'ETAG':
                    $head['ETAG'] = $val;
                    break;
                case 'LAST-MODIFIED':
                    $head['LAST_MODIFIED'] = $val;
                    break;
            }
        }
        return $head;
    }
?>