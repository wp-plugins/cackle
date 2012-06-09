<?php
class CackleAPI{
	function CackleAPI(){
		$accountApiKey = get_option('cackle_accountApiKey');
		$siteApiKey = get_option('cackle_siteApiKey');
		$cackle_last_comment = get_option('cackle_last_comment',0);
		$this->get_url = $get_url = "http://cackle.ru/api/comment/list?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey&id=$cackle_last_comment";
		$this->update_url = $update_url = "http://cackle.ru/api/wp115/setup?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
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
					'headers' => array("referer" =>  $blog_url),
					//'body' => "chan0=http://localhost:88/wordpress/?p=1&post0=1&count=1"
					'body' => $update_request
			)
	);
	return $update_response["response"];
}

function key_validate($api,$site,$account){
	$key_url ="http://cackle.ru/api/keys/check?siteId=$api&accountApiKey=$account&siteApiKey=$site";
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

}
?>