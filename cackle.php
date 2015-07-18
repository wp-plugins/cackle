<?php
/*
Plugin Name: Cackle comments
Plugin URI: http://cackle.me
Description: This plugin allows your website's audience communicate through social networks like Facebook, Vkontakte, Twitter, e.t.c.
Version: 4.14
Author: Cackle
Author URI: http://cackle.me
*/
define('CACKLE_PLUGIN_URL', WP_CONTENT_URL . '/plugins/' . cackle_plugin_basename(__FILE__));
define('CACKLE_VERSION', '4.14');
define('CACKLE_SCHEDULE_COMMON', 120);
define('CACKLE_SCHEDULE_CHANNEL', 120);


require_once(dirname(__FILE__) . '/cackle_api.php');
require_once(dirname(__FILE__) . '/sync.php');
function cackle_manage() {
    include_once (dirname(__FILE__) . '/manage.php');
}

$cackle_api = new CackleAPI();
function cackle_i($text, $params = null) {
    if (!is_array($params)) {
        $params = func_get_args();
        $params = array_slice($params, 1);
    }
    return vsprintf(__($text, 'cackle'), $params);
}

function channel_timer($cron_time, $id){
    $cackle_api = new CackleAPI();
    global $wpdb;

        $get_last_time = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $id));
        //print_r($get_last_time);die();
        //$get_last_time = $cackle_api->cackle_get_param("last_time_" . $schedule . "_" . $_SERVER['HTTP_HOST'],0);
        $now = time();
        if (count($get_last_time)==0) {
            $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
            $sql = $wpdb->prepare($sql,$id,$now,$now);
            $wpdb->query($sql);
            return $now;
        } else {
            $get_last_time = $get_last_time['0']->time;
            if ($get_last_time + $cron_time > $now) {
                return false;
            }
            if ($get_last_time + $cron_time < $now) {
                $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
                $sql = $wpdb->prepare($sql,$id,$now,$now);
                $wpdb->query($sql);
                return $cron_time;
            }
        }

}
function time_is_over($cron_time, $schedule) {
    $cackle_api = new CackleAPI();
    $q = "last_time_" . $schedule . "_" . $_SERVER['HTTP_HOST'];
    $get_last_time = $cackle_api->cackle_get_param($q,0);
    $now = time();
    if ($get_last_time == "") {
        $q = "last_time_" . $schedule . "_" . $_SERVER['HTTP_HOST'];
        $set_time = $cackle_api->cackle_set_param($q, $now);
        return time();
    } else {
        if ($get_last_time + $cron_time > $now) {
            return false;
        }
        if ($get_last_time + $cron_time < $now) {
            $q = "last_time_" . $schedule . "_" . $_SERVER['HTTP_HOST'];
            $set_time = $cackle_api->cackle_set_param($q, $now);
            return $cron_time;
        }
    }

}

class cackle {

    function cackle() {

        //cackle_enabled()
        if (cackle_enabled()) {
         //   wp_schedule_single_event(time() + 10, 'my_new_event'); //uncomment for debug without 5min delay
        }
        add_filter('comments_template', array($this, 'comments_template'),1000);
        add_filter('comments_number', array($this, 'comments_text'));
        add_action('my_new_event', array($this, 'do_this_in_an_hour')); //uncomment for debug without 5min delay
        add_action('admin_menu', array($this, 'cackle_add_pages'), 10);
        add_filter('plugin_action_links', array($this, 'cackle_plugin_action_links'), 10, 2);
        add_action('admin_head', array($this, 'cackle_admin_head'));
    }






    function cackle_plugin_action_links($links, $file) {
        $plugin_file = basename(__FILE__);
        if (basename($file) == $plugin_file) {
            $settings_link = '<a href="edit-comments.php?page=cackle#adv">' . cackle_i('Settings') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    function cackle_add_pages() {
        add_submenu_page('edit-comments.php', 'Cackle', 'Cackle', 'moderate_comments', 'cackle', 'cackle_manage');
    }

    function cackle_comments_number($count) {
        global $post;
        return $count;
    }

    function identifier_for_post($post) {
        return $post->ID;
    }

    function comments_text($comment_text) {
        global $post;
        return '<span class="cackle-postid" id="c' . htmlspecialchars($this->identifier_for_post($post)) . '">' . $comment_text . '</span>';
    }

    function comments_template() {
        global $wpdb;
        $this->do_this_in_an_hour(); //uncomment for debug without 5min delay
        if (cackle_enabled()) {
            return dirname(__FILE__) . '/comment-template.php';
        }
    }

    function cackle_admin_head() {
        if (isset ($_GET ['page']) && $_GET ['page'] == 'cackle') {
            ?>

        <link rel='stylesheet'
              href='<?php echo CACKLE_PLUGIN_URL; ?>/manage.css'
              type='text/css'/>
        <style type="text/css">
            .cackle-importing, .cackle-imported, .cackle-import-fail, .cackle-exporting, .cackle-exported, .cackle-export-fail {
                background: url(<?php echo admin_url('images/loading.gif'); ?>) left center no-repeat;
                line-height: 16px;
                padding-left: 20px;
            }

            p.status {
                padding-top: 0;
                padding-bottom: 0;
                margin: 0;
            }

            .cackle-imported, .cackle-exported {
                background: url(<?php
                    echo admin_url('images/yes.png');
                    ?>) left center no-repeat;
            }

            .cackle-import-fail, .cackle-export-fail {
                background: url(<?php
                    echo admin_url('images/no.png');
                    ?>) left center no-repeat;
            }
        </style>
        <script type="text/javascript">
            jQuery(function ($) {
                $('#cackle-tabs li').click(function () {
                    $('#cackle-tabs li.selected').removeClass('selected');
                    $(this).addClass('selected');
                    $('.cackle-main, .cackle-advanced').hide();
                    $('.' + $(this).attr('rel')).show();
                });
                if (location.href.indexOf('#adv') != -1) {
                    $('#cackle-tab-advanced').click();
                }
                <?php if (isset($_POST['site_api_key'])) { ?>
                    $('#cackle-tab-advanced').click()
                    <?php }?>
                cackle_fire_export();
                cackle_fire_import();
            });
            cackle_fire_export = function () {
                var $ = jQuery;
                $('#cackle_export a.button, #cackle_export_retry').unbind().click(function () {
                    $('#cackle_export').html('<p class="status"></p>');
                    $('#cackle_export .status').removeClass('cackle-export-fail').addClass('cackle-exporting').html('Processing...');
                    cackle_export_comments();
                    return false;
                });
            }
            cackle_export_comments = function () {
                var $ = jQuery;
                var status = $('#cackle_export .status');
                var export_info = (status.attr('rel') || '0|' + (new Date().getTime() / 1000)).split('|');
                setTimeout( function() {
                    $.get(
                            '<?php echo admin_url('index.php'); ?>',
                            {
                                cf_action:'export_comments',
                                post_id:export_info[0],
                                timestamp:export_info[1]
                            },
                            function (response) {
                                switch (response.result) {
                                    case 'success':
                                        status.html(response.msg).attr('rel', response.post_id + '|' + response.timestamp);
                                        switch (response.status) {
                                            case 'partial':
                                                cackle_export_comments();
                                                break;
                                            case 'complete':
                                                status.removeClass('cackle-exporting').addClass('cackle-exported');
                                                break;
                                        }
                                        break;
                                    case 'fail':
                                        status.parent().html(response.msg);
                                        cackle_fire_export();
                                        break;
                                }
                            },
                            'json'
                    );
                },5000)
            };
            cackle_fire_import = function () {
                var $ = jQuery;
                $('#cackle_import a.button, #cackle_import_retry').unbind().click(function () {
                    $('#cackle_import').html('<p class="status"></p>');
                    $('#cackle_import .status').removeClass('cackle-import-fail').addClass('cackle-importing').html('Processing...');
                    cackle_import_comments();
                    return false;
                });
            };
            cackle_import_comments = function (wipe) {
                var $ = jQuery;
                var status = $('#cackle_import .status');
                var import_info = (status.attr('rel') || '0|' + (new Date().getTime() / 1000)).split('|');
                setTimeout( function() {
                    $.get(
                        '<?php echo admin_url('index.php'); ?>',
                        {
                            cf_action:'import_comments',
                            post_id:import_info[0],
                            timestamp:import_info[1]
                        },
                        function (response) {
                            switch (response.result) {
                                case 'success':
                                    status.html(response.msg).attr('rel', response.post_id + '|' + response.timestamp);
                                    switch (response.status) {
                                        case 'partial':
                                            cackle_import_comments();
                                            break;
                                        case 'complete':
                                            status.removeClass('cackle-importing').addClass('cackle-imported');
                                            break;
                                    }
                                    break;
                                case 'fail':
                                    status.parent().html(response.msg);
                                    cackle_fire_import();
                                    break;
                            }
                        },
                        'json'
                    );
                },5000)
            }
        </script>
        <?php
        }
    }
    function check_monitor(){
        /* Check cackle_monitor for synchronizing process
         * Return post_id which needed to sync or -1 if not
         */

        $cackle_monitor = get_option('cackle_monitor');
        if(!isset($cackle_monitor->counter) || $cackle_monitor->counter > 10000) $cackle_monitor->counter = 0;

        if($cackle_monitor->counter%2){
            $mode='long';
        }
        else{
            $mode='short';
        }
        switch ($mode) {
            case 'long':
                $object = get_option('cackle_monitor');
                break;
            case 'short':
                $object = get_option('cackle_monitor_short');
                break;

        }


        if($object->mode=='by_channel'){
            //if sync is called by pages, we need pause for 30 sec from the last sync
            if($object->time + 15 > time()){
                return -1;
            }
            if($object->status=='inprocess' && $object->time + 120 > time()){
                //do nothing because in progress
                return -1;
            }
            if($object->status=='next_page'){
                // do sync with the same post
                $ret_object = new stdClass();
                $ret_object->post_id=$object->post_id;
                $ret_object->mode=$mode;
                return $ret_object;
            }
            if ($object->status=='finish' || $object->time + 120 < time()){
                //get next post
                global $wpdb;
                if($mode=='long'){
                    $min_max_post_id = $wpdb->get_results("
                            SELECT MAX(ID) as max, MIN(ID) as min
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            ");
                }
                else{
                    $min_max_post_id = $wpdb->get_results("SELECT MAX(ID) as max, MIN(ID) as min
                            FROM ( select * from wp_posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            order by post_date desc limit 50) cackle
                            ");
                }

                $min_max_post_id = $min_max_post_id[0];
                $min_post_id = $min_max_post_id->min;
                $max_post_id = $min_max_post_id->max;

                if($object->post_id > $min_post_id){
                    $current_post_id = $object->post_id;
                    $next = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND ID < %d
                            ORDER BY ID DESC
                            LIMIT 1
                            ", $current_post_id));
                    $next_post = $next[0];
                    $next_post_id = $next_post->ID;
                    $ret_object = new stdClass();
                    $ret_object->post_id=$next_post_id;
                    $ret_object->mode=$mode;
                    return $ret_object;

                }
                if($object->post_id <= $min_post_id){
                    //set max because it is initial sync
                    $ret_object = new stdClass();
                    $ret_object->post_id=$max_post_id;
                    $ret_object->mode=$mode;
                    return $ret_object;
                }


            }
        }
        elseif($object->mode == 'all_comments'){
            if($object->status=='inprocess' && $object->time + 120 > time()){
                //don't start if all comments sync in progress
                return -1;
            }
            else{
                //we can't handle all_comments sync from here because it handles ajax requests, so
                //we should start sync again from the max
                global $wpdb;
                $min_max_post_id = $wpdb->get_results("
                            SELECT MAX(ID) as max, MIN(ID) as min
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            ");
                $min_max_post_id = $min_max_post_id[0];
                $max_post_id = $min_max_post_id->max;

                $object->post_id = $max_post_id;
                $object->mode = 'by_channel';

                $object_s = get_option('cackle_monitor_short');
                $object_s->post_id = $max_post_id;
                $object_s->mode = 'by_channel';

                update_option('cackle_monitor',$object);
                update_option('cackle_monitor_short',$object_s);

            }
        }
    }
    function do_this_in_an_hour() {

        try {
        if(get_option('cackle_comments_hidewpcomnts') != 1){
            if (version_compare(get_bloginfo('version'), '2.9', '>=')) {
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
                $sync = new Sync();

                $monitor = $this->check_monitor();


                if(is_object($monitor)){
                    if (isset($monitor->post_id) && $monitor->post_id == null) $monitor->post_id = 1;
                    channel_timer(time(),$monitor->post_id);
                    $sync->init($monitor->post_id,$monitor->mode);
                }



            }
        }
        }
        catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            function myException($exception) {
                echo "<b>Exception:</b> " . $exception->getMessage();
            }

            set_exception_handler('myException');

            throw new Exception('Uncaught Exception occurred');
        }
    }

    function showmessage($message, $type = 'message') { //or error
        echo '<div class="' . ($type == 'message' ? 'updated' : 'error') . '">' . addslashes($message) . '</div>';
    }
}

function cackle_output_footer_comment_js() {
    if (is_single() || is_page()){
    }
    else{
    ?>
<script type="text/javascript">
    // <![CDATA[
    var nodes = document.getElementsByTagName('span');
    for (var i = 0, url; i < nodes.length; i++) {
        if (nodes[i].className.indexOf('cackle-postid') != -1) {
            var c_id = nodes[i].getAttribute('id').split('c');
            nodes[i].parentNode.setAttribute('cackle-channel', c_id[1] );
            url = nodes[i].parentNode.href.split('#', 1);
            if (url.length == 1) url = url[0];
            else url = url[1]
            nodes[i].parentNode.href = url + '#mc-container';
        }
    }


    cackle_widget = window.cackle_widget || [];
    cackle_widget.push({widget: 'CommentCount', id: '<?php echo get_option('cackle_apiId') ?>'});
    (function() {
        var mc = document.createElement('script');
        mc.type = 'text/javascript';
        mc.async = true;
        mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
    })();
    //]]>
</script>

<?php
    }
}

add_action('wp_footer', 'cackle_output_footer_comment_js');
function cackle_init() {
    $a = new cackle;
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

function cackle_export_utf($string) {

    //$encoding = mb_detect_encoding($string, array('UTF-8', 'Windows-1251'));

    //$string = iconv('cp1251', 'utf-8', $string);
    //$string = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $string) . ']]>';
    return $string;
}

function cackle_request_handler() {
    global $cackle_response;
    global $cackle_api;
    global $post;
    global $wpdb;
    if (!empty($_GET['cf_action'])) {
        switch ($_GET['cf_action']) {
            case 'export_comments':
                if (current_user_can('manage_options')) {
                    $timestamp = intval($_GET['timestamp']);
                    $post_id = intval($_GET['post_id']);
                    global $wpdb, $cackle_api;
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
                                        'msg'=> cackle_export_utf($comment->comment_content),
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
                                $response = (isset($response['responseApi']['status']) && $response['responseApi']['status'] == "ok" ) ? "success" : "fail";
                            }

                        if (!($response == "success")) {
                            $result = 'fail';
                            $msg = '<p class="status cackle-export-fail">' . cackle_i('Sorry, something  happened with the export. Please <a href="#" id="cackle_export_retry">try again</a></p><p>If your API key has changed, you may need to reinstall Cackle (deactivate the plugin and then reactivate it). If you are still having issues, refer to the <a href="%s" onclick="window.open(this.href); return false">WordPress help page</a>.', 'http://cackle.me/help/') . '</p>';
                            $response = $cackle_api->get_last_error();
                        } else {
                            if ($eof) {
                                $msg = cackle_i('Your comments have been sent to Cackle and queued for import!<br/>After exporting the comments you receive email notification', 'http://cackle.me/help/');
                            }
                            $result = 'success';
                        }
                    }
                    //AJAX response
                    $debug = ob_get_clean();
                    $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                    header('Content-type: text/javascript');
                    echo cf_json_encode($response);
                    die();
                }
                break;
            case 'import_comments':
                if (current_user_can('manage_options')) {
                    global $wpdb, $cackle_api;
                    $timestamp = intval($_GET['timestamp']);
                    $post_id = intval($_GET['post_id']);
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
                    }
                    $result = 'fail';
                    ob_start();
                    $response = null;
                    if ($post) {
                        $sync = new Sync();
                        $response = $sync->init($post_id,'all_comments');
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

                                $msg = cackle_i('Your comments have been synchronized with Cackle and queued for import!<br/>After exporting the comments you receive email notification', 'http://cackle.me/help/');
                            }
                            $result = 'success';
                        }
                    }
                    //AJAX response
                    $debug = ob_get_clean();
                    $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                    header('Content-type: text/javascript');
                    echo cf_json_encode($response);
                    die();
                }
                break;

        }
    }
}

function cf_json_encode($data) {
    return cfjson_encode($data);
}

function cfjson_encode_string($str) {
    if (is_bool($str)) {
        return $str ? 'true' : 'false';
    }
    return str_replace(
        array(
            '"'
        , '/'
        , "\n"
        , "\r"
        )
        , array(
            '\"'
        , '\/'
        , '\n'
        , '\r'
        )
        , $str
    );
}

function cfjson_encode($arr) {
    $json_str = '';
    if (is_array($arr)) {
        $pure_array = true;
        $array_length = count($arr);
        for ($i = 0; $i < $array_length; $i++) {
            if (!isset($arr[$i])) {
                $pure_array = false;
                break;
            }
        }
        if ($pure_array) {
            $json_str = '[';
            $temp = array();
            for ($i = 0; $i < $array_length; $i++) {
                $temp[] = sprintf("%s", cfjson_encode($arr[$i]));
            }
            $json_str .= implode(',', $temp);
            $json_str .= "]";
        } else {
            $json_str = '{';
            $temp = array();
            foreach ($arr as $key => $value) {
                $temp[] = sprintf("\"%s\":%s", $key, cfjson_encode($value));
            }
            $json_str .= implode(',', $temp);
            $json_str .= '}';
        }
    } else if (is_object($arr)) {
        $json_str = '{';
        $temp = array();
        foreach ($arr as $k => $v) {
            $temp[] = '"' . $k . '":' . cfjson_encode($v);
        }
        $json_str .= implode(',', $temp);
        $json_str .= '}';
    } else if (is_string($arr)) {
        $json_str = '"' . cfjson_encode_string($arr) . '"';
    } else if (is_numeric($arr)) {
        $json_str = $arr;
    } else if (is_bool($arr)) {
        $json_str = $arr ? 'true' : 'false';
    } else {
        $json_str = '"' . cfjson_encode_string($arr) . '"';
    }
    return $json_str;
}

function cackle_plugin_basename($file) {
    $file = dirname($file);
    $file = str_replace('\\', '/', $file);
    $file = preg_replace('|/+|', '/', $file);
    $file = preg_replace('|^.*/' . PLUGINDIR . '/|', '', $file);
    if (strstr($file, '/') === false) {
        return $file;
    }
    $pieces = explode('/', $file);
    return !empty($pieces[count($pieces) - 1]) ? $pieces[count($pieces) - 1] : $pieces[count($pieces) - 2];
}

function cackle_warning() {
    if (!cackle_enabled() || cackle_validate_field('api_id', false, false) || cackle_validate_field('site_api_key', true, false) || cackle_validate_field('account_api_key', true, false)) {
        echo '<div id="activate_plugin" class="updated fade"> You must <a href="edit-comments.php?page=cackle">configure the plugin</a> to enable Cackle Comments.</div>';
    }
    if ($_POST) {
        if (cackle_activated()) {
            if (key_validate($_POST['api_id'], $_POST['site_api_key'], $_POST['account_api_key'])) {
                echo "<script type='text/javascript'>document.getElementById('activate_plugin').style.display = 'none';</script>";
                echo '<div class="updated fade">Succesfully activated</div>';
            }
        }
    }
}

function cackle_enabled() {
    if (get_option('cackle_apiId') && get_option('cackle_siteApiKey') && get_option('cackle_accountApiKey')) {
        return true;
    }
}

function cackle_validate_field($field, $length, $message) {
    if ($_POST) {
        if ($length) {
            if ((empty($_POST[$field])) || strlen($_POST[$field]) != 64) {
                if ($message) {
                    echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid value </span>';
                }
                return true;
            }
        } else {
            if (empty($_POST[$field])) {
                if ($message) {
                    echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid value</span>';
                }
                return true;
            }
        }
    }
}

function key_validate($api, $site, $account) {
    $k_validate = new CackleApi();
    $k_req = $k_validate->key_validate($api, $site, $account);
    $k_req = json_decode( $k_req, true);
    $k_req = $k_req["siteInfo"];
    if ($k_req['correctKey'] == "true") {
        update_option("cackle_lang",$k_req["lang"]);
        update_option("cackle_nolabel",(($k_req["whitelabel"])? 0 : 1));
        return true;
    } else {
        return false;
    }
}

function cackle_field_activated($field) {
    if ($field == 'api_id') {
        if (empty($_POST['api_id'])) {
            //print_r("is null");
            return false;
        } else {
            return true;
        }
    } elseif ($field == 'api_key') {
        if ((isset($_POST['site_api_key']) && strlen($_POST['site_api_key']) == 64) && (isset($_POST['account_api_key']) && strlen($_POST['account_api_key']) == 64)) {
            if (key_validate($_POST['api_id'], $_POST['site_api_key'], $_POST['account_api_key'])) {
                return true;
            }
        }
    } else {
        return false;
    }
}

function cackle_activated() {
    if (!empty($_POST['api_id']) && isset($_POST['site_api_key']) && strlen($_POST['site_api_key']) == 64 && isset($_POST['account_api_key']) && strlen($_POST['account_api_key']) == 64) {
        return true;
    }
}

function cackle_activate() {
    cackle_install();
}


function cackle_install() {
    //print_r('test');die();
    global $wpdb;
    $table_name = $wpdb->prefix . "cackle_channel";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE " . $table_name . " (
	            id varchar(150) NOT NULL DEFAULT '',
	            time bigint(11) NOT NULL,
	            modified varchar(25) DEFAULT NULL,
	            last_comment varchar(250) DEFAULT NULL,
                PRIMARY KEY (id),
	            UNIQUE KEY id (id)
	        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);

    }

    //Delete Cackle comments for resync
    $wpdb->query("DELETE FROM `" . $wpdb->prefix . "commentmeta` WHERE meta_key IN ('cackle_post_id', 'cackle_parent_post_id')");
    $wpdb->query("DELETE FROM `" . $wpdb->prefix . "comments` WHERE comment_agent LIKE 'Cackle:%%'");
    $wpdb->query("DELETE FROM `" . $wpdb->prefix . "cackle_channel`");
    delete_option("cackle_monitor");
    delete_option("cackle_monitor_short");
    delete_option("cackle_modified_trigger");
    update_option("cackle_plugin_version", CACKLE_VERSION);

}
function cackle_plugin_is_current_version(){
    $version = get_option( 'cackle_plugin_version','4.07');
    return version_compare($version, CACKLE_VERSION, '=') ? true : false;
}
if ( !cackle_plugin_is_current_version() ) cackle_install();

//checking activation errors
//add_action('activated_plugin', 'cackle_plugin_activation_error');
//function cackle_plugin_activation_error() {
//    file_put_contents( plugin_dir_path(__FILE__) . '/error_activation.html', ob_get_contents());
//}

add_action('init', 'cackle_request_handler');
add_action('init', 'cackle_init');
register_activation_hook( __FILE__, 'cackle_activate' );