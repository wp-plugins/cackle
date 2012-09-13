<?php



function cackle_options() {
    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    if(isset($_POST['cackle_comments_wpnonce'])) {
        if ( wp_verify_nonce($_POST['cackle_comments_wpnonce'], plugin_basename( __FILE__ )))
        {
            /**
             * Check each input to update in db
             */
                
            if (cackle_activated()){
                if (cackle_field_activated('api_id')){
                    update_option('cackle_apiId', (int)$_POST['api_id']);
                    //   showmessage('api id saved');
                }

                if (cackle_field_activated('api_key')){
                    update_option('cackle_siteApiKey', (string)$_POST['site_api_key']);
                    //showmessage('site_api are saved');

                    update_option('cackle_accountApiKey', (string)$_POST['account_api_key']);
                    update_option('cackle_comments_hidewpcomnts', (isset($_POST['hidewpcomments'])) ? 1 : 0);
                    //showmessage('account_api are saved');
                }
            }
                
        }
    }

    ?>
            <div class="wrap">
            
                <?php $oldapiId = get_option('cackle_apiId')?>
                <form method="post">
                                    
                                    <p>Please, <?php if ($oldapiId){
                                        echo '<a href="http://cackle.ru/site/' . $oldapiId . '/wordpress" target="_blank">click here</a>';
                                        }
                                        else{
                                        echo '<a href="http://cackle.ru/site/new" target="_blank">register</a>';
                                        }
                                        echo ' to obtain your Site ID, Account API Key, Site API Key. </p>'
                                    ?>
                                    <h3>Settings</h3>
                <?php    wp_nonce_field( plugin_basename( __FILE__ ), 'cackle_comments_wpnonce', false, true ); ?>
                <?php $apiId = get_option('cackle_apiId','')?>
                <?php $siteApiId = get_option('cackle_siteApiKey','')?>
                <?php $accountApiId = get_option('cackle_accountApiKey','')?>

                <p><?php echo __('Cackle Site ID','cackle_comments'); ?>: <input type="text" value="<?php echo $apiId;?>" name="api_id"/>
                <?php
                 cackle_validate_field('api_id',false,true);
                 ?> 
                </p>
                
                <p><?php echo __('Cackle Account API Key','cackle_comments'); ?>: <input style="width:480px" type="text" value="<?php echo $accountApiId;?>" name="account_api_key"/>
                <?php 
                cackle_validate_field('account_api_key',TRUE,TRUE);
                 ?> 
                </p>
                
                <p><?php echo __('Cackle Site API Key','cackle_comments'); ?>: <input style="width:445px" type="text" value="<?php echo $siteApiId;?>" name="site_api_key"/>
                <?php 
                cackle_validate_field('site_api_key',TRUE,TRUE);
                 ?> 
                </p>
                <?php 
                    //if (!key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
                    //    echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid keys</span>';
                    //}
                if (isset($_POST)){    
                    if(cackle_activated()){
                        if (!key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
                            echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid keys</span>';
                        }
                    }
                }
                 ?> 
                <p><?php echo __('Disallow search engines to index comments','cackle_comments'); ?>: <input type="checkbox" value="1" name="hidewpcomments" <?php if(get_option('cackle_comments_hidewpcomnts')==1):?>  checked="checked" <?php endif;?>/></p>
                
            
                <?php
                
                    if (cackle_activated() ){
                        if (key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
                        echo('<br/><span style="color:green">Starting verifying keys...</span>');
                        if (get_option('cackle_apiId')){
                            echo('<br/><span style="color:green">Your plugin was successfully activated.</span>');
                            
                        }
                        }
                    
                    }
                
                    ?>     
            
                <p><input type="submit" value="Activate" name="update" class="button-primary button" tabindex="4" /></p>    
                </form>
            </div>
            <?php

        }

        
        
        
        /**Check that all post params all right and if $field check that one param all right
         *
        * @param unknown_type $field
        */
        
       /* function cackle_enabled(){
            if (get_option('cackle_apiId') && get_option('cackle_siteApiKey') && get_option('cackle_accountApiKey')){
                return true;
            }
        }
        function cackle_warning() {
            if (!cackle_enabled() || cackle_validate_field('api_id',false,false) || cackle_validate_field('site_api_key',true,false) || cackle_validate_field('account_api_key',true,false)) {
                echo '<div id="activate_plugin" class="updated fade"> You must <a href="options-general.php?page=cackle_comments">configure the plugin</a> to enable Cackle Comments.</div>';
            }
        
            /*Starting key validation after all fields valid
             * @return false if field is not valid
            */
        /*
            if ($_POST){
                if(cackle_activated()){
                    if (key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
                        echo "<script type='text/javascript'>document.getElementById('activate_plugin').style.display = 'none';</script>";
                        echo '<div class="updated fade">Succesfully activated</div>';
                    }
                }
            }
                
        }
        */
        /**Validate fields in Cackle Plugin Page
         * @return false if field is not valid
        */
        
        



$show_advanced = (isset($_GET['t']) && $_GET['t'] == 'adv');
?>
<div class="wrap" id="cackle-wrap">
    <ul id="cackle-tabs">
        <li<?php if (!$show_advanced) echo ' class="selected"'; ?> id="cackle-tab-main" rel="cackle-main"><?php echo (true ? 'Manage' : 'Install'); ?></li>
        <li<?php if ($show_advanced) echo ' class="selected"'; ?> id="cackle-tab-advanced" rel="cackle-advanced"><?php echo cackle_i('Advanced Options'); ?></li>
    </ul>

    <div id="cackle-main" class="cackle-content">

<div class="cackle-main"
    <?php if ($show_advanced || isset($_POST['site_api_key'])) echo ' style="display:none;"'; ?>>
    <a style="float: left; margin-bottom: 12px; margin-top:10px;" href="http://cackle.ru" target="_blank"><img alt="cackle logo" src="http://cackle.ru/static/img/logo.png"></a>
<p style="float: left; font-size: 13px; font-weight: bold; line-height: 30px; padding-left: 13px;">comments platform that helps your website's audience communicate through social networks.</p>
    
    <iframe
        src="http://ru.cackle.me/site/comments"
        style="width: 100%; height: 80%; min-height: 600px;"></iframe>
</div>
<!-- Advanced options -->

<div id="cackle-advanced" class="cackle-content cackle-advanced"
    <?php if (!$show_advanced) echo ' style="display:none;"'; ?>>
    <a style="float: left; margin-bottom: 12px; margin-top:10px;" href="http://cackle.ru" target="_blank"><img alt="cackle logo" src="http://cackle.ru/static/img/logo.png"></a>
<p style="float: left; font-size: 13px; font-weight: bold; line-height: 30px; padding-left: 13px;">comments platform that helps your website's audience communicate through social networks.</p>
    <h2 style="padding-left: 0px;clear:both;"><?php echo cackle_i('Advanced Options'); ?></h2>
    <?php cackle_options();?>
        

    <h3>Import / Export</h3>

    <table class="form-table">
           
            <tr id="export">
            <th scope="row" valign="top"><?php echo cackle_i('Export comments to Cackle'); ?></th>
            <td>
                <div id="cackle_export">
                    <p class="status">
                        <a href="#" class="button"><?php echo cackle_i('Export Comments'); ?></a>  <?php echo cackle_i('This will export your existing WordPress comments to Cackle'); ?></p>
                </div>
            </td>
        </tr>
            
            <tr>
            <th scope="row" valign="top"><?php echo cackle_i('Sync Cackle with WordPress'); ?></th>
            <td>
                <div id="cackle_import">
                    <div class="status">
                        <p><?php echo cackle_i('This will download your Cackle comments and store them locally in WordPress'); ?></p>
                        <br />
                        <p>
                            <a href="#" class="button"><?php echo cackle_i('ReSyncComments'); ?></a>
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    
</div>
