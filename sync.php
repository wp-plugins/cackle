<?php
class Sync{
    function Sync(){
    }
    function init($a=""){
        $apix = new CackleAPI();
        $response1 = $apix->get_comments();
         //get comments from Cackle Api for sync
        $response_size=$this->get_one_comm($response1); // get comment from array and insert it to wp db
        
        if($response_size==100 && $a="all_comments"){
            while($response_size==100){
                $apix = new CackleAPI();
                $response1 = $apix->get_comments();
                //get comments from Cackle Api for sync
                $response_size=$this->get_one_comm($response1); // get comment from array and insert it to wp db
            }
        }
        return "success";
    }
    /**
     * Decodes json to array
     * @return $obj
     */
    function cackle_json_decodes($response){
        $response_without_jquery = str_replace('jQuery(', '', $response);
        $response = str_replace(');', '', $response_without_jquery);
        $obj = json_decode($response,true);
    
        return $obj;
    }
    
    /**
     * Get one comment from array $response and insert it to wp_comments throught insert_comm function
     * @return $obj
     */

    function get_one_comm($response){
        $obj = $this->cackle_json_decodes($response);
        $obj = $obj['comments'];
        $comments_size=count($obj);
        foreach ($obj as $obj_e) {
            $this->insert_comm($obj_e);
        }
        
        return $comments_size;
    }

    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function insert_comm($comment){
        global $wpdb;
        $status;
        if ($comment['status'] == "APPROVED"){
            $status = 1;
        }
        elseif ($comment['status'] == "PENDING" || $comment['status'] == "REJECTED" ){
            $status = 0;
        }
        elseif ($comment['status'] == "SPAM" ){
            $status = "spam";
        }
        else {
            $status = "trash";
        }
    
    if ( $this->startsWith($comment['channel'], 'http' )){
        $postid = url_to_postid( $comment['channel']);
    }
    else {
        $postid = $comment['channel'];
    }
    if ($comment['author']!=null){
        $comment_author = $comment['author']['name'];
        $comment_author_email =  $comment['author']['email'];
        $comment_author_url = $comment['author']['www'];
    }
    else{
        $comment_author = $comment['anonym']['name'];
        $comment_author_email =  $comment['anonym']['email'];
        $comment_author_url = $comment['anonym']['www'];
    }
    $comment_for_id = $wpdb->get_row($wpdb->prepare( "SELECT comment_ID, comment_parent FROM $wpdb->comments WHERE comment_agent = 'Cackle:{$comment['id']}' LIMIT 1"), ARRAY_A);
    $commentdata = array(
            'comment_post_ID' => $postid,
            'comment_author' =>  $comment_author,
            'comment_karma' => $comment['rating'],
            'comment_author_email' => $comment_author_email,
            //'comment_date' => date('Y-m-d\TH:i:s', strtotime($comment->created_at) + (get_option('gmt_offset') * 3600)),
            //'comment_date_gmt' => $comment->created_at,
            'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $comment['created']/1000 + (get_option('gmt_offset') * 3600)),
            'comment_author_url' => $comment_author_url,
            'comment_author_IP' => $comment['ip'],
            'comment_content' => apply_filters('pre_comment_content', $comment['message']),
            'comment_approved' => $status,
            'comment_agent' => 'Cackle:' . $comment['id'],
            'comment_type' => '',
    );
    $commentdata['comment_ID'] = wp_insert_comment($commentdata);
    $comment_id = $commentdata['comment_ID'];
    
    update_comment_meta($comment_id, 'cackle_parent_post_id', $comment['parent']);
    update_comment_meta($comment_id, 'cackle_post_id', $comment['id']);

    if ($comment['parent']) {
        $parent_id = $wpdb->get_var($wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = 'cackle_post_id' AND meta_value = %s LIMIT 1", $comment['parent']));
        if ($parent_id) {
                $wpdb->query(
                                "
                                UPDATE $wpdb->comments
                                SET comment_parent = $parent_id
                                WHERE comment_ID = $comment_id 
                                    
                                "
                );
        }
    }

    
    update_option( 'cackle_last_comment', $comment['id'] ); //saving last comment id to database

    
    
}


}
?>