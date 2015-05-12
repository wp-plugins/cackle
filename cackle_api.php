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
        $this->get_url = $get_url = "http://cackle.me/api/3.0/comment/list.json?id=$siteId&accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
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

    function get_comments($cackle_last_modified, $post_id, $cackle_page = 0) {
        $host = $this->get_url . "&modified=" . $cackle_last_modified . "&page=" . $cackle_page . "&size=100&chan=" . $post_id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
        //curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/x-www-form-urlencoded; charset=utf-8',
            )
        );
        $time_start = microtime(true);
        $result = curl_exec($ch);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start)*1000;
        //execution time of the script
        //echo '<b>Total Execution Time:</b> '.$execution_time.' sec';
        //var_dump($host);
        curl_close($ch);
        return $result;
    }

    function get_last_comment_by_channel($channel,$default){
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare("
                            SELECT last_comment
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %s
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $channel));
        if(sizeof($result)>0){
            $result = $result[0]->last_comment;
            if(is_null($result)){
                return $default;
            }
            else{
                return $result;
            }
        }
    }
    function set_last_comment_by_channel($channel,$last_comment){
        global $wpdb;
        $sql = "UPDATE {$wpdb->prefix}cackle_channel SET last_comment = %s  WHERE id = %s";
        $sql = $wpdb->prepare($sql,$last_comment,$channel);
        $wpdb->query($sql);

    }

    function get_last_modified_by_channel($channel,$default){
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare("
                            SELECT modified
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %s
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $channel));
        if(sizeof($result)>0){
            $result = $result[0]->modified;
            if(is_null($result)){
                return $default;
            }
            else{
                return $result;
            }
        }
        $res= $result;
    }
    function set_last_modified_by_channel($channel,$last_modified){
        global $wpdb;
        $sql = "UPDATE {$wpdb->prefix}cackle_channel SET modified = %s  WHERE id = %s";
        $sql = $wpdb->prepare($sql,$last_modified,$channel);
        $wpdb->query($sql);

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

    /**
     * @param $wxr
     * @param $timestamp
     * @param bool $eof
     * @return array|int
     */
    function import_wordpress_comments($comments, $post_id, $eof = true) {
        $data = array(
            'chan' => $post_id->ID,
            'url' => urlencode(get_permalink($post_id->ID,true)),
            'title' => $post_id->post_title,
            'comments' => $comments);
            $postfields = json_encode($data);
            $curl_fields = array(
                'id' => $this->siteId,
                'accountApiKey' => $this->accountApiKey,
                'siteApiKey' => $this->siteApiKey,
                'comments' => $postfields
            );
            $curl=curl_init('http://cackle.me/api/3.0/comment/post.json');
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_POST,true);
            curl_setopt($curl,CURLINFO_HEADER_OUT,true);
            curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($curl_fields));
            $response = curl_exec($curl);
            curl_close($curl);
        if ($response instanceof WP_Error) {
            return -1;
        }
        /*if ($response['body'] == 'fail') {
            $this->api->last_error = $response['body'];
            return -1;
        }
        $data = $response['body'];
        if (!$data || $data == 'fail') {
            return -1;
        }*/

        return $response;
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