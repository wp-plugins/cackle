<?php
function cackle_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if (isset($_POST['cackle_comments_wpnonce'])) {
        if (wp_verify_nonce($_POST['cackle_comments_wpnonce'], plugin_basename(__FILE__))) {
            /**
             * Check each input to update in db
             */
            if (cackle_activated()) {
                if (cackle_field_activated('api_id')) {
                    update_option('cackle_apiId', (int)$_POST['api_id']);
                    //   showmessage('api id saved');
                }
                if (cackle_field_activated('api_key')) {
                    update_option('cackle_siteApiKey', (string)$_POST['site_api_key']);
                    //showmessage('site_api are saved');
                    update_option('cackle_accountApiKey', (string)$_POST['account_api_key']);
                    update_option('cackle_comments_hidewpcomnts', (isset($_POST['hidewpcomments'])) ? 1 : 0);
                    update_option('cackle_sso', (isset($_POST['enable_sso'])) ? 1 : 0);
                    //showmessage('account_api are saved');
                }
                if(ini_get('open_basedir')!='' || ini_get('safe_mode') == true){
                    update_option('cackle_comments_hidewpcomnts', 1);
                }
            }
        }
    }
    function _isCurl(){
        return function_exists('curl_version');
    }





?>
<div class="wrap">
    <?php !_isCurl()? print_r("Attention, curl extention is not installed") : $c=1; ?>
    <?php $oldapiId = get_option('cackle_apiId')?>
    <form method="post">

        <p>Please, <?php if ($oldapiId) {
            echo '<a href="http://admin.cackle.me/admin/comment/' . $oldapiId . '/install" target="_blank">click here</a>';
        } else {
            echo '<a href="http://admin.cackle.me/account/register?demo=[{%22type%22:%22comment%22,%22period%22:12}]&lang=ru" target="_blank">register</a>';
        }
            echo ' to obtain your Site ID, Account API Key, Site API Key. </p>'
            ?>
        <?php (ini_get('open_basedir')!='' || ini_get('safe_mode') == true) ? print_r("<div class='error'> Synchronization (SEO) comments is disabled because <b>open_basedir</b> have value or <b>safe_mode</b> is On. Find these values in php.ini and set (;open_basedir and safe_mode = off) or ask hosting provider.</div>") : print_r("") ?>
        <h3>Settings</h3>
        <?php    wp_nonce_field(plugin_basename(__FILE__), 'cackle_comments_wpnonce', false, true); ?>
        <?php $apiId = get_option('cackle_apiId', '')?>
        <?php $siteApiId = get_option('cackle_siteApiKey', '')?>
        <?php $accountApiId = get_option('cackle_accountApiKey', '')?>

        <p><?php echo __('Cackle Site ID', 'cackle_comments'); ?>: <input type="text" value="<?php echo $apiId;?>"
                                                                          name="api_id"/>
            <?php
            cackle_validate_field('api_id', false, true);
            ?>
        </p>

        <p><?php echo __('Cackle Account API Key', 'cackle_comments'); ?>: <input style="width:480px" type="text"
                                                                                  value="<?php echo $accountApiId;?>"
                                                                                  name="account_api_key"/>
            <?php
            cackle_validate_field('account_api_key', TRUE, TRUE);
            ?>
        </p>

        <p><?php echo __('Cackle Site API Key', 'cackle_comments'); ?>: <input style="width:445px" type="text"
                                                                               value="<?php echo $siteApiId;?>"
                                                                               name="site_api_key"/>
            <?php
            cackle_validate_field('site_api_key', TRUE, TRUE);
            ?>
        </p>
        <?php
        if (isset($_POST)) {
            if (cackle_activated()) {
                if (!key_validate($_POST['api_id'], $_POST['site_api_key'], $_POST['account_api_key'])) {
                    echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid keys</span>';
                }
            }
        }
        ?>
        <p><?php echo __('Disallow search engines to index comments(synchronization will be disabled)', 'cackle_comments'); ?>: <input type="checkbox"
                                                                                                     value="1"
                                                                                                     name="hidewpcomments" <?php if (get_option('cackle_comments_hidewpcomnts') == 1): ?>
                                                                                                     checked="checked" <?php endif;?>/>
        </p>

        <p><?php echo __('Enable <b>Single Sign On</b>', 'cackle_comments'); ?>: <input type="checkbox" value="1"
                                                                                        name="enable_sso" <?php if (get_option('cackle_sso') == 1): ?>
                                                                                        checked="checked" <?php endif;?>/>
            Allows the registered (on your site) users work with widget and post comments. <b>Note</b>, this option only
            availible with <a href="http://cackle.me/pricing" title="See details about SSO">SSO option</a>.
        </p>


        <?php

        if (cackle_activated()) {
            if (key_validate($_POST['api_id'], $_POST['site_api_key'], $_POST['account_api_key'])) {
                echo('<br/><span style="color:green">Starting verifying keys...</span>');
                if (get_option('cackle_apiId')) {
                    echo('<br/><span style="color:green">Your plugin was successfully activated.</span>');
                }
            }
        }

        ?>

        <p><input type="submit" value="Activate" name="update" class="button-primary button" tabindex="4"/></p>
    </form>
</div>
<?php
}

$show_advanced = (isset($_GET['t']) && $_GET['t'] == 'adv');
?>
<div class="wrap" id="cackle-wrap">
    <ul id="cackle-tabs">
        <li<?php if (!$show_advanced) echo ' class="selected"'; ?> id="cackle-tab-main"
                                                                   rel="cackle-main"><?php echo (true ? 'Manage' : 'Install'); ?></li>
        <li<?php if ($show_advanced) echo ' class="selected"'; ?> id="cackle-tab-advanced"
                                                                  rel="cackle-advanced"><?php echo cackle_i('Advanced Options'); ?></li>
    </ul>

    <div id="cackle-main" class="cackle-content">

        <div class="cackle-main"
            <?php if ($show_advanced || isset($_POST['site_api_key'])) echo ' style="display:none;"'; ?>>
            <a style="float: left; margin-bottom: 12px; margin-top:10px;" href="http://cackle.ru" target="_blank"><img
                    alt="cackle logo" src="http://cackle.ru/static/img/logo.png"></a>

            <p style="float: left; font-size: 13px; font-weight: bold; line-height: 30px; padding-left: 13px;">comments
                platform that helps your website's audience communicate through social networks.</p>

            <div id="mc-comment-admin"></div>
            <script type="text/javascript">
                cackle_widget = window.cackle_widget || [];
                cackle_widget.push({widget: 'CommentAdmin'});
                (function() {
                    var mc = document.createElement('script');
                    mc.type = 'text/javascript';
                    mc.async = true;
                    mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
                })();
            </script>

        </div>
        <!-- Advanced options -->

        <div id="cackle-advanced" class="cackle-content cackle-advanced"
            <?php if (!$show_advanced) echo ' style="display:none;"'; ?>>
            <a style="float: left; margin-bottom: 12px; margin-top:10px;" href="http://cackle.ru" target="_blank"><img
                    alt="cackle logo" src="http://cackle.ru/static/img/logo.png"></a>

            <p style="float: left; font-size: 13px; font-weight: bold; line-height: 30px; padding-left: 13px;">comments
                platform that helps your website's audience communicate through social networks.</p>

            <h2 style="padding-left: 0px;clear:both;"><?php echo cackle_i('Advanced Options'); ?></h2>
            <?php cackle_options();?>


            <h3>Import / Export</h3>

            <table class="form-table">

                <tr id="export">
                    <th scope="row" valign="top"><?php echo cackle_i('Export comments to Cackle'); ?></th>
                    <td>
                        <div id="cackle_export">
                            <div class="status">
                                <p><?php echo cackle_i('This will export your existing WordPress comments to Cackle'); ?></p>
                                <?php
                                $manual_export = get_option('cackle_manual_export');
                                if((isset($manual_export->finish)&&($manual_export->finish !=true))) { ?>
                                    <p class="cackle-notcomplete">  <?php echo cackle_i("Your last export was interrupted on post with id = $manual_export->last_post_id. Please, press Continue button to continue."); ?></p>
                                <?php
                                }
                                ?>
                                <br/>
                                <div class="current-status"></div>
                                <p>
                                    <a href="#" id="export_start" class="button"><?php echo cackle_i('Start'); ?></a>
                                    <a href="#" id="export_continue" class="button"><?php echo cackle_i('Continue'); ?></a>
                                    <a href="#" id="export_stop" class="button"><?php echo cackle_i('Stop'); ?></a>
                                </p>
                            </div>
                        </div>
                    </td>

                </tr>

                <tr>
                    <th scope="row" valign="top"><?php echo cackle_i('Sync Cackle with WordPress'); ?></th>
                    <td>
                        <div id="cackle_import">
                            <div class="status">
                                <p><?php echo cackle_i('This will download your Cackle comments and store them locally in WordPress'); ?></p>
                                <?php
                                $manual_sync = get_option('cackle_manual_sync');
                                if((isset($manual_sync->finish)&&($manual_sync->finish !=true))) { ?>
                                  <p class="cackle-notcomplete">  <?php echo cackle_i("Your last download was interrupted on post with id = $manual_sync->last_post_id. Please, press Continue button to continue."); ?></p>
                                <?php
                                }
                                ?>
                                <br/>
                                <div class="current-status"></div>
                                <p>
                                    <a href="#" id="sync_start" class="button"><?php echo cackle_i('Start'); ?></a>
                                    <a href="#" id="sync_continue" class="button"><?php echo cackle_i('Continue'); ?></a>
                                    <a href="#" id="sync_stop" class="button"><?php echo cackle_i('Stop'); ?></a>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

        </div>

