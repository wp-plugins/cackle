<?php
class CackleAPI{
    function CackleAPI(){
        $this->siteId=get_option('cackle_apiId');
        $this->accountApiKey=$accountApiKey = get_option('cackle_accountApiKey');
        $this->siteApiKey=$siteApiKey = get_option('cackle_siteApiKey');
        $this->cackle_last_comment=$cackle_last_comment = get_option('cackle_last_comment',0);
        $this->get_url = $get_url = "http://cackle.ru/api/comment/list?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey&id=$cackle_last_comment";
        $this->update_url = $update_url = "http://cackle.ru/api/wp115/setup?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->last_error = null;
    }
function get_comments(){
    $http = new WP_Http();
    //define('URL', get_bloginfo('wpurl'));
    $blog_url = get_bloginfo('wpurl');
    $import_response = $http->request(
            $this->get_url,
            array(
            'headers' => array("referer" =>  $blog_url)
            )
    );
    return $import_response["body"];
}

function update_comments($update_request){
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

function key_validate($api,$site,$account){
    $key_url ="http://cackle.me/api/keys/check?siteId=$api&accountApiKey=$account&siteApiKey=$site";
    $http = new WP_Http();
    
    $blog_url = get_bloginfo('wpurl');
    $key_response = $http->request(
            $key_url,
            array(
            'headers' => array("referer" =>  $blog_url)
            )
    );
    return $key_response["body"];
}

function import_wordpress_comments(&$wxr, $timestamp, $eof=true) {
    $http = new WP_Http();
    $blog_url = get_bloginfo('wpurl');
    $debug_site_id=$this->siteId;
    $debug_account_key=$this->accountApiKey;
    $debug_site_api_key= $this->siteApiKey;
    $response = $http->request(
            'http://import.cackle.me/api/import-wordpress-comments',
            array(
                    'method' => 'POST',
					'timeout' => 10,
                    'headers' => array("referer" =>  $blog_url, "Content-type" => "application/x-www-form-urlencoded"),
                    'body' => array(
                            'siteId' =>$this->siteId,
                            'accountApiKey' => $this->accountApiKey,
                            'siteApiKey' => $this->siteApiKey,
                            
                            'wxr' => $wxr,
                            
                            'eof' => (int)$eof
                    )
            )
    );
    
    if ($response['body']=='fail') {
		$this->api->last_error = $response['body'];
        return -1;
    }
    $data = $response['body'];
    if (!$data || $data== 'fail') {
        return -1;
    }

    return $data;
}

function get_last_error() {
    if (empty($this->last_error)) return;
    if (!is_string($this->last_error)) {
        return var_export($this->last_error);
    }
    return $this->last_error;
}

}
?>