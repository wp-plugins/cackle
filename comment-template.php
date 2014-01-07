<?php
function is_comments_close() {
    global $wpdb;
    global $post;
    $post_id = $post->ID;
    $status = $wpdb->get_results($wpdb->prepare("
            SELECT comment_status
            FROM $wpdb->posts
            WHERE ID = %d
            ", $post_id));
    $status = $status[0];
    $comment_status = $status->comment_status;
    if ($comment_status == "closed") {
        $status = true;
    } else {
        $status = false;
    }
    return $status;
}

function get_avatar_path($id) {
    $avatar_path = get_avatar($id);
    $avatar_path = str_replace("&#038;", "&", $avatar_path);
    preg_match("/src=(\'|\")(.*)(\'|\")/Uis", $avatar_path, $matches);
    $avatar_src = substr(trim($matches[0]), 5, strlen($matches[0]) - 6);
    if (strpos($avatar_src, 'http') === false) {
        $avatar_src = get_option('siteurl') . $avatar_src;
    }
    //var_dump($avatar_src);
    return $avatar_src;
}

function cackle_auth() {
    global $current_user;
    get_currentuserinfo();
    $timestamp = time();
    $siteApiKey = get_option('cackle_siteApiKey');
    if (is_user_logged_in()) {
        $user = array(
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar' => get_avatar_path($current_user->ID)
        );
        $user_data = base64_encode(json_encode($user));
    } else {
        $user = '{}';
        $user_data = base64_encode($user);
    }
    $sign = md5($user_data . $siteApiKey . $timestamp);
    return "$user_data $sign $timestamp";
}

$api_id = get_option('cackle_apiId', '');
if (!is_comments_close()) {

    require_once(dirname(__FILE__) . '/cackle_api.php');
    require_once(dirname(__FILE__) . '/sync.php');
    ?>

<?php  function cackle_comment($comment, $args, $depth) {
        $GLOBALS['comment'] = $comment;
        ?><li <?php comment_class(); ?> id="cackle-comment-<?php echo comment_ID(); ?>">
                    <div id="cackle-comment-header-<?php echo comment_ID(); ?>" class="cackle-comment-header">
                        <cite id="cackle-cite-<?php echo comment_ID(); ?>">
                            <?php if (comment_author_url()) : ?>
                            <a id="cackle-author-user-<?php echo comment_ID(); ?>"
                               href="<?php echo comment_author_url(); ?>" target="_blank"
                               rel="nofollow"><?php echo comment_author(); ?></a>
                            <?php else : ?>
                            <span id="cackle-author-user-<?php echo comment_ID(); ?>"><?php echo comment_author(); ?></span>
                            <?php endif; ?>
                        </cite>
                    </div>
        <div id="cackle-comment-body-<?php echo comment_ID(); ?>" class="cackle-comment-body">
            <div id="cackle-comment-message-<?php echo comment_ID(); ?>"
                 class="cackle-comment-message"><?php echo wp_filter_kses(comment_text()); ?></div>
        </div><?php } ?>

    <div id="mc-container">
        <div id="mc-content">

            <?php
            if (get_option('cackle_comments_hidewpcomnts') != 1) {
                if (get_comment_pages_count() > 1 && get_option('page_comments')): // Are there comments to navigate through?
                    ?>
                    <div class="navigation">
                        <div class="nav-previous"><?php previous_comments_link(cackle_i('<span class="meta-nav">&larr;</span> Older Comments')); ?></div>
                        <div class="nav-next"><?php next_comments_link(cackle_i('Newer Comments <span class="meta-nav">&rarr;</span>')); ?></div>
                    </div> <!-- .navigation -->
                    <?php endif; // check for comment navigation ?>

                <ul id="cackle-comments">
                    <?php
                    /* Loop through and list the comments. Tell wp_list_comments()
                     * to use dsq_comment() to format the comments.
                     */
                    wp_list_comments(array('callback' => 'cackle_comment'));
                    ?>
                </ul>
                <?php } ?>
        </div>
    </div>


    <script type="text/javascript">
        cackle_widget = window.cackle_widget || [];
        cackle_widget.push({widget: 'Comment', id: '<?php echo $api_id?>', channel: '<?php echo $post->ID?>',
    <?php if (get_option('cackle_sso') == 1) : ?> ssoAuth: '<?php print_r(cackle_auth()) ?>' <?php endif;?>   });
        document.getElementById('mc-container').innerHTML = '';
        (function() {
            var mc = document.createElement('script');
            mc.type = 'text/javascript';
            mc.async = true;
            mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
        })();
    </script>
    <?php if (get_option('cackle_nolabel',1) == 1) { ?>
    <a id="mc-link" href="http://cackle.me"><?php
        if (get_option('cackle_lang') == "ru" || get_option('cackle_lang') == "uk" || get_option('cackle_lang') == "be"){
            echo "Социальные комментарии ";
        }
        else {
            echo "Social comments ";
        }
        ?><b style="color:#4FA3DA">Cackl</b><b style="color:#F65077">e</b></a>
    <?php }} ?>
    <?php if ($api_id == ''): ?>API ID not specified<?php endif; ?>
