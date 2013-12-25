<?php
class CackleAPI
{
    function to_i($number_to_format)
    {
        return number_format($number_to_format, 0, '', '');
    }

    function __construct()
    {
        $this->siteId = $siteId = get_option('cackle_apiId');
        $this->accountApiKey = $accountApiKey = get_option('cackle_accountApiKey');
        $this->siteApiKey = $siteApiKey = get_option('cackle_siteApiKey');
        $this->cackle_last_modified = $this->cackle_get_param('cackle_last_modified', 0);
        $this->get_url = $get_url = "http://cackle.me/api/2.0/comment/list.json?id=$siteId&accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->get_url2 = "http://cackle.me/api/2.0/site/info.json?id=$siteId&accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->update_url = $update_url = "http://cackle.me/api/wp115/setup?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->last_error = null;
    }

    function cackle_set_param($param, $value)
    {
          $beg = "/";
          $value = $beg . $value;
          $eof = "/";
          $value .= $eof;
        return update_option($param, $value);
    }

    function cackle_get_param($param, $default)
    {
        $res = get_option($param, $default);
        $res = str_replace("/","",$res);
        return $res;

    }

    function get_comments($cackle_last_modified, $cackle_page = 0)
    {

        $host = $this->get_url . "&modified=" . $cackle_last_modified . "&page=" . $cackle_page . "&size=100";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
        //curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/x-www-form-urlencoded; charset=utf-8',


            )
        );
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;


        /*
        $postdata = http_build_query(
            array(
                'id' =>$this->siteId,
                'accountApiKey' => $this->accountApiKey,
                'siteApiKey' => $this->siteApiKey,
                'modified' => $cackle_last_modified,
                'page' => $cackle_page,
                'size' => 2
            )
        );
        $opts = array('http' =>
        array(
            'method'  => 'GET',
            'header'  =>
                'Accept-Encoding: gzip, deflate, sdch' . "\r\n" .
                'Content-type: application/x-www-form-urlencoded; charset=utf-8' . "\r\n"

       //     'content' => ''
        )
        );

        $context  = stream_context_create($opts);

        $response = file_get_contents($this->get_url2 . "&modified=" . $cackle_last_modified . "&page=" . $cackle_page . "&size=2", false, $context);
        return $response;
        /*

    /*

        $http = new WP_Http();
        $import_response = $http->request(
                $c=$this->get_url . "&modified=" . $cackle_last_modified . "&page=" . $cackle_page . "&size=2",
            array(
                'headers' => array("Accept-Encoding" => "gzip,deflate,sdch")
            )
       );
        if ($import_response instanceof WP_Error){
            return NULL;
        }
        else {
            return $import_response["body"];
        }  **/
    }

    function update_comments($update_request)
    {
        $http = new WP_Http();

        $blog_url = get_bloginfo('wpurl');
        $update_response = $http->request(
            $this->update_url,
            array(
                'method' => 'POST',
                'headers' => array("Content-type" => "application/x-www-form-urlencoded"),
                //'body' => "chan0=http://localhost:88/wordpress/?p=1&post0=1&count=1"
                'body' => $update_request
            )
        );

    }



    function key_validate($api, $site, $account)
    {
        $key_url = "http://cackle.me/api/2.0/site/info.json?id=$api&accountApiKey=$account&siteApiKey=$site";
        $http = new WP_Http();

        $blog_url = get_bloginfo('wpurl');
        $key_response = $http->request(
            $key_url,
            array(
                'headers' => array("referer" => $blog_url)
            )
        );
        return $key_response["body"];
    }

    function import_wordpress_comments(&$wxr, $timestamp, $eof = true)
    {
        $http = new WP_Http();
        $blog_url = get_bloginfo('wpurl');
        $debug_site_id = $this->siteId;
        $debug_account_key = $this->accountApiKey;
        $debug_site_api_key = $this->siteApiKey;
        $response = $http->request(
            'http://import.cackle.me/api/import-wordpress-comments',
            array(
                'method' => 'POST',
                'timeout' => 10,
                'headers' => array("referer" => $blog_url, "Content-type" => "application/x-www-form-urlencoded"),
                'body' => array(
                    'siteId' => $this->siteId,
                    'accountApiKey' => $this->accountApiKey,
                    'siteApiKey' => $this->siteApiKey,

                    'wxr' => $wxr,

                    'eof' => (int)$eof
                )
            )
        );
        if ($response instanceof WP_Error) {
            return -1;
        }
        if ($response['body'] == 'fail') {
            $this->api->last_error = $response['body'];
            return -1;
        }
        $data = $response['body'];
        if (!$data || $data == 'fail') {
            return -1;
        }

        return $data;
    }

    function get_last_error()
    {
        if (empty($this->last_error)) return;
        if (!is_string($this->last_error)) {
            return var_export($this->last_error);
        }
        return $this->last_error;
    }

}

?>