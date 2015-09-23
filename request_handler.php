<?php
function cackle_i($text, $params = null) {
    if (!is_array($params)) {
        $params = func_get_args();
        $params = array_slice($params, 1);
    }
    return vsprintf(__($text, 'cackle'), $params);
}
function get_comment_status($status){
    if ($status == "1") {
        $status = "approved";
    } elseif ($status == "0") {
        $status = "pending";
    } elseif ($status == "spam") {
        $status = "spam";
    } elseif ($status == "trash") {
        $status = "deleted";
    }
    return $status;
}
switch ($_GET['cackleApi']) {
    case 'export':
        if (current_user_can('manage_options')) {
            global $wpdb, $cackle_api;
            $timestamp = intval($_GET['timestamp']);
            $action = $_GET['action'];
            $manual_export = get_option('cackle_manual_export','');
            if($manual_export==''){
                $manual_export = new stdClass();
                $manual_export->status='export';
            }
            $post_id = intval($_GET['post_id']);

            switch ($action) {
                case 'export_start':
                    if($manual_export->status == 'stop'){
                        $result = 'fail';
                        ob_start();
                        $msg = '<div class="status cackle-export-fail error">' . cackle_i('export was stopped on processing post with id') . $post_id. '</div>';
                        $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                        header('Content-type: text/javascript');
                        echo json_encode($response);
                        $manual_export->status = 'export'; //revert trigger for initial state
                        update_option('cackle_manual_export',$manual_export);
                        die();

                    }
                    break;
                case 'export_continue':
                    $post_id = $manual_export->last_post_id;
                    $manual_export->status = 'export';
                    update_option('cackle_manual_export',$manual_export);
                    break;
                case 'export_stop':
                    $manual_export->status = 'stop';
                    update_option('cackle_manual_export',$manual_export);
                    header('Content-type: text/javascript');
                    $result = 'fail';
                    ob_start();
                    $msg = '<p class="status cackle-export-fail">' . cackle_i('export was stopped on processing id') . $post_id. '</p>';
                    $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                    header('Content-type: text/javascript');
                    echo json_encode($response);
                    break;

            }
            $post = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            AND ID > %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $post_id));
            $post = $post[0];
            $post_id = $post->ID;
            $max_post_id = $wpdb->get_var($wpdb->prepare("
                            SELECT MAX(ID)
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            ", $post_id));
            $eof = (int)($post_id == $max_post_id);
            if ($eof) {
                $status = 'complete';
                $msg = 'Your comments have been sent to Cackle and queued for import!<br/>';
            } else {
                $status = 'partial';
                //require_once(dirname(__FILE__) . '/manage.php');
                $msg = cackle_i('Processed comments on post #%s&hellip;', $post_id);
                $manual_export->finish=false;
                update_option('cackle_manual_export',$manual_export);
            }
            $result = 'fail';
            ob_start();
            $response = null;
            if ($post) {
                $comms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_agent NOT LIKE 'Cackle:%%' order by comment_date asc", $post->ID));
                if(sizeof($comms)==0){
                    $response = "success";
                }
                else{
                    $comments=array();
                    foreach ($comms as $comment) {
                        $created=new DateTime($comment->comment_date);
                        $comments[]=Array(
                            'id' => $comment->comment_ID,
                            'ip' => $comment->comment_author_IP,
                            'status' => get_comment_status($comment->comment_approved),
                            'msg'=> $comment->comment_content,
                            'created' => $created->getTimestamp()*1000,
                            'user' => ($comment->user_id > 0) ? array(
                                'id' => $comment->user_id,
                                'name' => $comment->comment_author,
                                'email' => $comment->comment_author_email
                            ) : null,
                            'parent' => $comment->comment_parent,
                            'name' => ($comment->user_id == 0) ? $comment->comment_author : null,
                            'email' => ($comment->user_id == 0) ? $comment->comment_author_email : null
                        );

                    }
                    $response = $cackle_api->import_wordpress_comments($comments,$post,$eof);
                    $response = json_decode($response,true);
                    $fail_response = $response;
                    $response = (isset($response['responseApi']['status']) && $response['responseApi']['status'] == "ok" ) ? "success" : "fail";
                }

                if (!($response == "success")) {
                    $result = 'fail';
                    $msg = '<p class="status cackle-export-fail">' . cackle_i('Sorry, something  happened with the export. Please <a href="#" id="cackle_export_retry">try again</a></p><p>If your API key has changed, you may need to reinstall Cackle (deactivate the plugin and then reactivate it). If you are still having issues, refer to the <a href="%s" onclick="window.open(this.href); return false">WordPress help page</a>.', 'http://cackle.me/help/') . '</p>';

                } else {
                    if ($eof) {
                        $msg = cackle_i('Your comments have been sent to Cackle and queued for import!<br/>After exporting the comments you receive email notification', 'http://cackle.me/help/');
                        $manual_export->finish=true;
                        update_option('cackle_manual_export',$manual_export);
                    }
                    $result = 'success';
                }
            }
            //AJAX response
            $debug = ob_get_clean();
            $export = 'export';
            $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug','export','fail_response');
            header('Content-type: text/javascript');
            echo json_encode($response);
            //Update last post id exported only if it was exported
            if($result=='success'){
                $manual_export->last_post_id=$post_id;
                update_option('cackle_manual_export',$manual_export);
            }

            die();
        }
        break;
    case 'import':
        if (current_user_can('manage_options')) {
            global $wpdb, $cackle_api;
            //$timestamp = intval($_GET['timestamp']);
            $action = $_GET['action'];
            $manual_sync = get_option('cackle_manual_sync','');
            if($manual_sync==''){
                $manual_sync = new stdClass();
                $manual_sync->status='sync';
            }
            $post_id = intval($_GET['post_id']);
            switch ($action) {
                case 'sync_start':
                    if($manual_sync->status == 'stop'){
                        $result = 'fail';
                        ob_start();
                        $msg = '<div class="status cackle-export-fail error">' . cackle_i('Sync was stopped on processing post with id') . $post_id. '</div>';
                        $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                        header('Content-type: text/javascript');
                        echo json_encode($response);
                        $manual_sync->status = 'sync'; //revert trigger for initial state
                        update_option('cackle_manual_sync',$manual_sync);
                        die();

                    }



                    break;
                case 'sync_continue':
                    $post_id = $manual_sync->last_post_id;
                    $manual_sync->status = 'sync';
                    update_option('cackle_manual_sync',$manual_sync);
                    break;
                case 'sync_stop':
                    $manual_sync->status = 'stop';
                    update_option('cackle_manual_sync',$manual_sync);
                    header('Content-type: text/javascript');
                    $result = 'fail';
                    ob_start();
                    $msg = '<p class="status cackle-export-fail">' . cackle_i('Sync was stopped on processing id') . $post_id. '</p>';
                    $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                    header('Content-type: text/javascript');
                    echo json_encode($response);
                    break;

            }

            if($post_id==0){
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "commentmeta` WHERE meta_key IN ('cackle_post_id', 'cackle_parent_post_id')");
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "comments` WHERE comment_agent LIKE 'Cackle:%%'");
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "cackle_channel`");
                delete_option("cackle_monitor");
                delete_option("cackle_monitor_short");
                delete_option("cackle_modified_trigger");

                //initialize monitor object if not exist
                if( !get_option('cackle_monitor') ) {
                    $object = new stdClass();
                    $object->post_id = 0;
                    $object->time = 0;
                    $object->mode = "by_channel";
                    $object->status = "finish";
                    $object->counter = 0;
                    update_option('cackle_monitor',$object);
                }

                if( !get_option('cackle_monitor_short') ) {
                    $object = new stdClass();
                    $object->post_id = 0;
                    $object->time = 0;
                    $object->mode = "by_channel";
                    $object->status = "finish";
                    update_option('cackle_monitor_short',$object);
                }

                //initialize modified triger object if not exist
                if(!get_option('cackle_modified_trigger')){
                    $modified_triger = new stdClass();
                    update_option('cackle_modified_trigger',$modified_triger);
                }

            }
            $post = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            AND ID > %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $post_id));
            $post = $post[0];
            $post_id = $post->ID;
            channel_timer(CACKLE_SCHEDULE_CHANNEL,$post_id);
            $max_post_id = $wpdb->get_var($wpdb->prepare("
                            SELECT MAX(ID)
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            ", $post_id));
            $eof = (int)($post_id == $max_post_id);
            if ($eof) {
                $status = 'complete';
                $msg = 'Your comments have been resynchronized!<br/>';
            } else {
                $status = 'partial';
                //require_once(dirname(__FILE__) . '/manage.php');
                $msg = cackle_i('Processed comments on post #%s&hellip;', $post_id);
                $manual_sync->finish=false;
                update_option('cackle_manual_sync',$manual_sync);
            }
            $result = 'fail';
            ob_start();
            $response = null;
            if ($post) {
                $sync = new Sync();
                $response = $sync->init($post_id,'all_comments');
                $fail_response = $response;
                if (!($response == "success")) {
                    $result = 'fail';
                    $msg = '<p class="status cackle-export-fail">' . cackle_i('Sorry, something  happened with the export. Please <a href="#" id="cackle_export_retry">try again</a></p><p>If your API key has changed, you may need to reinstall Cackle (deactivate the plugin and then reactivate it). If you are still having issues, refer to the <a href="%s" onclick="window.open(this.href); return false">WordPress help page</a>.', 'http://cackle.me/help/') . '</p>';
                    $response = $cackle_api->get_last_error();
                } else {
                    if ($eof) {
                        //we need to switch monitor to by_channel
                        $object = new stdClass();
                        $object->mode = "by_channel";
                        $object->post_id = 0;
                        $object->status = 'finish';
                        $object->time = time();
                        update_option('cackle_monitor',$object);
                        update_option('cackle_monitor_short',$object);
                        $manual_sync->finish=true;
                        update_option('cackle_manual_sync',$manual_sync);

                        $msg = cackle_i('Your comments have been synchronized with Cackle!');
                    }
                    $result = 'success';
                }
            }
            //AJAX response
            $debug = ob_get_clean();
            $import='import';
            $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug','import','fail_response');
            header('Content-type: text/javascript');
            echo json_encode($response);
            if($result=='success') {
                $manual_sync->last_post_id = $post_id;
                update_option('cackle_manual_sync', $manual_sync);
            }

            die();
        }
        break;

}
switch ($_GET['cackleApi']) {
    case 'checkKeys':
        if (current_user_can('manage_options')) {
            require_once(dirname(__FILE__) . '/cackle_activation.php');
            $activation_fields = stripslashes($_GET['value']);
            $activation_fields = json_decode($activation_fields);
            $resp = CackleActivation::check($activation_fields);
            echo json_encode($resp);
            die();
        }

}

?>