<?php 
class CackleUpdate{
	function CackleUpdate(){
	}
	function init(){
		$update_request=$this->request_prepare($this->get_channels());
		$api_u= new CackleAPI;
		$api_u->update_comments($update_request);
		update_option('cackle_update_115', true);
	}
	function get_channels(){
		global $wpdb;
		$comment_id_fields = comment_id_fields(); 
		$channels=	$wpdb->get_results($wpdb->prepare("SELECT comment_post_ID FROM $wpdb->comments where comment_post_ID > 0 group by comment_post_ID"));
	
	return $channels;
	
	}
	function request_prepare($channels){
		$update_request = "";
		foreach ($channels as $comment_num => $value ) {
			$post_id =$value ->comment_post_ID;
			$update_request .= "chan" .	$comment_num . "=" . get_permalink($post_id) . "&post" . $comment_num . "=" . $post_id . "&";
			//$update_request .= "chan" .	$comment_num . "=" . $post_id . "&post" . $comment_num . "=" . get_permalink($post_id) . "&";
		}
		$update_request .= "count=" .count($channels);
		return $update_request;
	}

}
?>	