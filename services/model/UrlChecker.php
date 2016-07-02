<?php

namespace Application\Model;


class UrlChecker{

    /**
     * getRedirectUrl()
     * Gets the address that the provided URL redirects to,
     * or FALSE if there's no redirect.
     *
     * @param string $url
     * @return string
     */
    public function getRedirectUrl($url){

        $redirect_url = null;

        $url_parts = @parse_url($url);
        if (!$url_parts) return false;
        if (!isset($url_parts['host'])) return false; //can't process relative URLs
        if (!isset($url_parts['path'])) $url_parts['path'] = '/';

        $sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
        if (!$sock) return false;

        $request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $url_parts['host'] . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        fwrite($sock, $request);
        $response = '';
        while(!feof($sock)) $response .= fread($sock, 8192);
        fclose($sock);

        if (preg_match('/^(L|l)ocation: (.+?)$/m', $response, $matches)){
            if ( substr($matches[2], 0, 1) == "/" )
                return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[2]);
            else
                return trim($matches[2]);

        } else {
            return false;
        }
    }

    /**
     * getAllRedirects()
     * Follows and collects all redirects, in order, for the given URL.
     *
     * @param string $url
     * @return array
     */
    function getAllRedirects($url){
        $redirects = array();
        while ($newurl = $this->getRedirectUrl($url)){
            if (in_array($newurl, $redirects)){
                break;
            }
            $redirects[] = $newurl;
            $url = $newurl;
        }
        return $redirects;
    }

    /**
     * getFinalUrl()
     * Gets the address that the URL ultimately leads to.
     * Returns $url itself if it isn't a redirect.
     *
     * @param string $url
     * @return string
     */
    public function getFinalUrl($url){
        if(preg_match("/^(https?:\/\/)/",$url)){
            $url = preg_replace('/https?:\/\//', '', $url, 1);
        }
        if(!preg_match("/^(www.)/",$url)){ // www Is added to the url
            $url = 'www.'.$url;
        }
        if(preg_match("/\/$/",$url)){ // Remove Last Occurence of '/'
            $url = $this->strReplaceLast('/','',$url);
        }
        $url = 'http://'.$url;

        $full_url = $this->urlExistAll200($url);
        if(!$full_url){
            $redirects = $this->getAllRedirects($url);
            if (count($redirects)>0){
                $full_url = array_pop($redirects);
            } else{
                $full_url = $url;
            }
        }

        if(preg_match("/^(http:\/\/)/",$full_url)){
            $url = preg_replace('/http:\/\//', '', $full_url, 1);
            $protocol = "http";
        } else if(preg_match("/^(https:\/\/)/",$full_url)){
            $url = preg_replace('/https:\/\//', '', $full_url, 1);
            $protocol = "https";
        } else{
            $url = $full_url;
            $protocol = "http";
        }
        if(preg_match("/\/$/",$url)){ // Remove Last Occurence of '/'
            $url = $this->strReplaceLast('/','',$url);
        }
        // echo $full_url;

        if( $this->urlExist($full_url) ) {
            $message = "Url Found !!!";
            $code=1;
        } else {
            $message = "This domain doesn't exist!!! Please select another domain!!!";
            $code=0;
        }
        $return['protocol'] = $protocol;
        $return['url'] = $url;
        $return['message'] = $message;
        $return['code'] = $code;

        return $return;
    }

    /**
     * @param $search
     * @param $replace
     * @param $str
     * @return mixed
     */
    public function strReplaceLast( $search , $replace , $str ) {
        if( ( $pos = strrpos( $str , $search ) ) !== false ) {
            $search_length  = strlen( $search );
            $str    = substr_replace( $str , $replace , $pos , $search_length );
        }
        return $str;
    }

    /**
     * @param $url
     * @return bool
     */
    public function urlExist($url){
        $array = @get_headers($url);
        $string = $array[0];
        // echo "string".$string;
        if(strpos($string,"200") || strpos($string,"301") || strpos($string,"302") || strpos($string,"403") || strpos($string,"500")) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $url
     * @return bool
     */

    public function urlExist200($url){
        // $array = get_headers($url);
        // $string = $array[0];


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        // curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $html = curl_exec($ch);
        // Then, after your curl_exec call:
        // $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // $header = substr($response, 0, $header_size);
        // $body = substr($response, $header_size);
        // var_dump($html);

        $header = substr($html, 0, 100);
        // echo "<b>".$url."</b> --> ".$html."<br>";
        if(strpos($header,"200 OK")) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $url
     * @return bool
     */
    public function urlExistAll200($url){
        $url_http_www = $url;
        $url_http = preg_replace('/www./', '', $url, 1);;
        $url_https_www = preg_replace('/http/', 'https', $url_http_www, 1);
        $url_https = preg_replace('/http/', 'https', $url_http, 1);

        if($this->urlExist200($url_http_www)){
            $url_200 = $url_http_www;
        } else if($this->urlExist200($url_http)){
            $url_200 = $url_http;
        } else if($this->urlExist200($url_https_www)){
            $url_200 = $url_https_www;
        } else if($this->urlExist200($url_https)){
            $url_200 = $url_https;
        } else{
            $url_200 = false;
        }
        return $url_200;

    }

    /**
     * @param $url
     * @return bool
     */
    public static function checkUrlExist($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        // curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $html = curl_exec($ch);
        $header = substr($html, 0, 100);
        if(strpos($header,"200") || strpos($header,"301") || strpos($header,"302")) {
            return true;
        }else{
            return false;
        }
    }
}
