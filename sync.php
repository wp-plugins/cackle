<?php
class Sync {
    function Sync() {
    }

    function has_next ($size_comments, $size_pagination = 100) {
        return $size_comments == $size_pagination;
    }
    function push_next_comments($mode, $size_comments,$channel,$cackle_last_modified){
        $apix = new CackleAPI();
        $i = 1;
        while($this->has_next($size_comments)){

            if ($mode=="all_comments"){
                $response = $apix->get_comments(0,$i) ;
            }
            else{
                $response = $apix->get_comments($cackle_last_modified,$channel,$i) ;
            }
            $size_comments = $this->process_comments($response, $channel); // get comment from array and insert it to wp db
            $i++;
        }
    }
    function init($channel,$mode = "") {

        $apix = new CackleAPI();
        $cackle_last_modified = $apix->get_last_modified_by_channel($channel,0);

        if ($mode == "all_comments") {
            $response = $apix->get_comments(0,$channel);
        }
        else {
            $response = $apix->get_comments($cackle_last_modified,$channel);
        }
        //get comments from Cackle Api for sync
        if ($response==NULL){
            return false;
        }
        $size_comments = $this->process_comments($response, $channel); // get comment from array and insert it to wp db, and return size

        if ($this->has_next($size_comments)) {
            $this->push_next_comments($mode,$size_comments, $channel,$cackle_last_modified);
        }

        return "success";
    }

    /**
     * Decodes json to array
     * @return $obj
     */
    function cackle_json_decodes($response) {
        $obj = json_decode($response, true);
        return $obj;
    }

    /**
     * Get one comment from array $response and insert it to wp_comments throught insert_comm function
     * @return $obj
     */


    function process_comments($response,$channel) {
        global $wpdb;
        $apix = new CackleAPI();
        $obj = $this->cackle_json_decodes($response,true);
        $obj = $obj['comments'];
        $comments_size = count($obj);
        if ($comments_size != 0){
            foreach ($obj as $comment) {
                if ($comment['id'] > $apix->get_last_comment_by_channel($channel,0)){
                    $comment_id = $comment['id'];
                    $count = $wpdb->get_results($wpdb->prepare("SELECT count(comment_ID) as count from $wpdb->comments  WHERE comment_agent = %s", "Cackle:{$comment_id}"));
                    if(isset($count[0]->count)&&$count[0]->count==0){
                        $this->insert_comm($comment, $this->comment_status_decoder($comment));
                    }

                } else {
                    // if ($comment['modified'] > $apix->cackle_get_param('cackle_last_modified', 0)) {
                    $this->update_comment_status($comment['id'], $this->comment_status_decoder($comment), $comment['modified'], $comment['message'], $channel );
                    // }
                }
            }
        }

        return $comments_size;
    }

    function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function comment_status_decoder($comment) {

        if (strtolower($comment['status']) == "approved") {
            $status = 1;
        } elseif (strtolower($comment['status'] == "pending") || strtolower($comment['status']) == "rejected") {
            $status = 0;
        } elseif (strtolower($comment['status']) == "spam") {
            $status = "spam";
        } elseif (strtolower($comment['status']) == "deleted") {
            $status = "trash";
        }
        return $status;
    }

    function update_comment_status($comment_id, $status, $modified, $comment_content,$channel) {
        $apix=new CackleAPI();
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->comments SET comment_approved = '$status' WHERE comment_agent = %s", "Cackle:{$comment_id}"));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->comments SET comment_content = '$comment_content' WHERE comment_agent = %s", "Cackle:{$comment_id}"));
        if ($modified > $apix->get_last_modified_by_channel($channel,0)) {
            $apix->set_last_modified_by_channel($channel, $modified);

        }

    }

    function insert_comm($comment, $status) {
        $apix = new CackleAPI();
        global $wpdb;
        if ($this->startsWith($comment['chan']['channel'], 'http')) {
            $postid = url_to_postid($comment['chan']['channel']);
        } else {
            $postid = $comment['chan']['channel'];
        }
        if (isset($comment['author']) && $comment['author'] != null) {
            $comment_author = isset($comment['author']['name']) ? $comment['author']['name'] : "";
            $comment_author_email = isset($comment['author']['email']) ? $comment['author']['email'] : "";
            $comment_author_url = isset($comment['author']['www']) ? $comment['author']['www'] : "" ;
            if($comment['author']['provider']=='sso'){
                if(isset($comment['author']['openId'])){
                    $openId = $comment['author']['openId'];
                    $user_id = (int)substr($openId, strpos($openId, "_") + 1);
                }

            }
            else{
                $user_id=0;
            }
        } else {
            $comment_author = isset($comment['anonym']['name']) ? $comment['anonym']['name'] : "";
            if(!isset($comment['anonym']['email'])){
                $comment_author_email = NULL;
            }
            else{
                $comment_author_email = $comment['anonym']['email'];
            }
            $comment_author_url = $comment['anonym']['www'];
        }
        $commentdata = array(
            'comment_post_ID' => $postid,
            'comment_author' => $comment_author,
            'comment_karma' => $comment['rating'],
            'comment_author_email' => $comment_author_email,
            'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $comment['created'] / 1000 + (get_option('gmt_offset') * 3600)),
            'comment_author_url' => $comment_author_url,
            'comment_author_IP' => $comment['ip'],
            'comment_content' => apply_filters('pre_comment_content', $comment['message']),
            'comment_approved' => $status,
            'comment_agent' => 'Cackle:' . $comment['id'],
            'comment_type' => '',
            'user_id' => $user_id
        );
        $commentdata['comment_ID'] = wp_insert_comment($commentdata);
        $comment_id = $commentdata['comment_ID'];
        update_comment_meta($comment_id, 'cackle_parent_post_id', $comment['parentId']);
        update_comment_meta($comment_id, 'cackle_post_id', $comment['id']);
        if ($comment['parentId']) {
            $parent_id = $wpdb->get_var($wpdb->prepare("SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = 'cackle_post_id' AND meta_value = %s LIMIT 1", $comment['parent']));
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
        $apix->set_last_comment_by_channel($postid, $comment['id']);
        if ($comment['modified'] > $apix->get_last_modified_by_channel($postid,0)) {
            $apix->set_last_modified_by_channel($postid, $comment['modified']);

        }
    }
}

?>