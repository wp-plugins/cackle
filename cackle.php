<?php
/*
Plugin Name: Cackle comments
Plugin URI: http://cackle.ru
Description: This plugin allows your website's audience communicate through social networks like Facebook, Vkontakte, Twitter, e.t.c.
Version: 1.19
Author: Denis Golovachev
Author URI: http://borov.net
*/

require_once(dirname(__FILE__) . '/cackle_api.php');
require_once(dirname(__FILE__) . '/sync.php');
require_once(dirname(__FILE__) . '/update.php');
class cackle  {
	

		function cackle () {
			if ($this->cackle_enabled()){
				wp_schedule_single_event(time()+300, 'my_new_event'); //uncomment for debug without 5min delay
			}
			add_filter( 'comments_template', array($this, 'comments_template'));
			add_action('wp_head', array($this, 'head_script'));
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('my_new_event',array($this, 'do_this_in_an_hour')); //uncomment for debug without 5min delay
			add_action('admin_notices', array($this, 'cackle_warning'));
			
		}

		function comments_template () {
			//$this->do_this_in_an_hour(); //uncomment for debug without 5min delay
			if ($this->cackle_enabled()){
				return dirname( __FILE__ ) . '/comment-template.php';
			}
		}
		function key_validate($api,$site,$account){
			$k_validate= new CackleApi();
			$k_req = $k_validate->key_validate($api,$site,$account);
			
			if ($k_req == "success"){
			
				return true;
			}
			else{
			
				return false;
			}
			
		}
		function cackle_activated(){
			
				if($_POST['api_id'] && $_POST['site_api_key'] && strlen($_POST['site_api_key'])==64 && $_POST['account_api_key'] && strlen($_POST['account_api_key'])==64 ){
						
					
					return true;
				}
					
			
		}
		/**Check that all post params all right and if $field check that one param all right
		 * 
		 * @param unknown_type $field
		 */
		function cackle_field_activated($field){
			
				
				if ($field=='api_id'){
					if($_POST['api_id']){
						return true;
					}
					
				}
				elseif ($field=='api_key'){
					if (($_POST['site_api_key'] && strlen($_POST['site_api_key'])==64) && ($_POST['account_api_key'] && strlen($_POST['account_api_key'])==64)) {
						if ( $this->key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])) {
						return true;
						}
					}
					
				}
				
				else{
					
					
					return false;
				}
			
		}
		function cackle_enabled(){
			if (get_option('cackle_apiId') && get_option('cackle_siteApiKey') && get_option('cackle_accountApiKey')){
				return true;
			}
		}
		function cackle_warning() {
			if (!$this->cackle_enabled() || $this->cackle_validate_field('api_id',false,false) || $this->cackle_validate_field('site_api_key',true,false) || $this->cackle_validate_field('account_api_key',true,false)) {
				echo '<div id="activate_plugin" class="updated fade"> You must <a href="options-general.php?page=cackle_comments">configure the plugin</a> to enable Cackle Comments.</div>';
			}

	    /**Starting key validation after all fields valid
		 * @return false if field is not valid
		*/			
		if ($_POST){	
					if($this->cackle_activated()){
						if ($this->key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
							echo "<script type='text/javascript'>document.getElementById('activate_plugin').style.display = 'none';</script>";
				echo '<div class="updated fade">Succesfully activated</div>';
						}
					}
				}
			
		}
		
		/**Validate fields in Cackle Plugin Page
		 * @return false if field is not valid
		 */
		function cackle_validate_field($field,$length,$message){
			if ($_POST){
				if ($length) {
					if ((!$_POST[$field]) || strlen($_POST[$field])!=64 ){
						if ($message){	
							echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid value </span>';
						}
						return true;
					}
				
				}
				else{
					if (!$_POST[$field]){
						if ($message){
							echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid value</span>';
						}
					return true;
					}
					
				}	
				
			}
		}
		
		
		
		function do_this_in_an_hour(){
				global $post;
				$_post_id = $post->ID;
				$sync = new Sync();
				$response = $sync->init();
		}

		function head_script () {
			global $cackleapiloaded; // can be used by another plugin for escaping twice loading

			if(!isset($cackleapiloaded)):?>
			<script type="text/javascript" src="http://cackle.ru/mc.widget-min.js"  charset="UTF-8" ></script>
			<?php $cackleapiloaded =1; endif;
		}

		function admin_menu ()
		{
			 add_options_page('Cackle comments widget', 'Cackle Comments', 'manage_options', 'cackle_comments', array($this,'cackle_options'));
		}

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
				 		switch ($f){
				 			case 0:
				 				if ($this->cackle_field_activated('api_id')){
					 			 update_option('cackle_apiId', (int)$_POST['api_id']);
					 		  //   $this->showmessage('api id saved');
				 				}
				 			case 1:
					 			if ($this->cackle_field_activated('api_key')){
					 			update_option('cackle_siteApiKey', (string)$_POST['site_api_key']);
					 			//$this->showmessage('site_api are saved');
					 		
								update_option('cackle_accountApiKey', (string)$_POST['account_api_key']);
								update_option('cackle_comments_hidewpcomnts', (isset($_POST['hidewpcomments'])) ? 1 : 0);
								//$this->showmessage('account_api are saved');
						 		}
				 		    
				 		}
				 	}
				 }

			?>
			<div class="wrap">
			<h2>Cackle Social Comments</h2>
				<?php $oldapiId = get_option('cackle_apiId')?>
				<form method="post">
                                    <a href="http://cackle.ru" target="_blank"><img alt="cackle logo" src="http://cackle.ru/static/images/logo.png"></a>
                                    <p>Cackle -  comments platform that helps your website's audience communicate through social networks.</p>
                                    <p>Please, <?php if ($oldapiId){
                                    	echo '<a href="http://cackle.ru/site/' . $oldapiId . '/wordpress" target="_blank">click here</a>';
                                    	}
                                    	else{
                                    	echo '<a href="http://cackle.ru/site/new" target="_blank">register</a>';
                                    	}
                                    	echo ' to obtain your Site ID, Account API Key, Site API Key. </p>'
                                    ?>
                                    <h3>Settings</h3>
				<?php	wp_nonce_field( plugin_basename( __FILE__ ), 'cackle_comments_wpnonce', false, true ); ?>
				<?php $apiId = get_option('cackle_apiId','')?>
				<?php $siteApiId = get_option('cackle_siteApiKey','')?>
				<?php $accountApiId = get_option('cackle_accountApiKey','')?>

				<p><?php echo __('Cackle Site ID','cackle_comments'); ?>: <input type="text" value="<?php echo $apiId;?>" name="api_id"/>
				<?php
				 $this->cackle_validate_field('api_id',false,true);
				 ?> 
			    </p>
				
				<p><?php echo __('Cackle Account API Key','cackle_comments'); ?>: <input style="width:480px" type="text" value="<?php echo $accountApiId;?>" name="account_api_key"/>
				<?php 
				$this->cackle_validate_field('account_api_key',TRUE,TRUE);
				 ?> 
			    </p>
			    
			    <p><?php echo __('Cackle Site API Key','cackle_comments'); ?>: <input style="width:445px" type="text" value="<?php echo $siteApiId;?>" name="site_api_key"/>
				<?php 
				$this->cackle_validate_field('site_api_key',TRUE,TRUE);
				 ?> 
			    </p>
				<?php 
					//if (!$this->key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
					//	echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid keys</span>';
					//}
				if ($_POST){	
					if($this->cackle_activated()){
						if (!$this->key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
							echo '<span style="color:red;padding-left:5px;font-weight:bold;">invalid keys</span>';
						}
					}
				}
				 ?> 
				<p><?php echo __('Disallow search engines to index comments','cackle_comments'); ?>: <input type="checkbox" value="1" name="hidewpcomments" <?php if(get_option('cackle_comments_hidewpcomnts')==1):?>  checked="checked" <?php endif;?>/></p>
				
			
				<?php
				
					if ($this->cackle_activated() ){
						if ($this->key_validate($_POST['api_id'],$_POST['site_api_key'],$_POST['account_api_key'])){
						echo('<br/><span style="color:green">Starting comments update...</span>');
						if (get_option(cackle_apiId)){
							echo('<br/><span style="color:green">Comments were successfully updated.</span>');
							$c_update= new CackleUpdate();
							$c_update->init();
						}
						}
					
					}
				
					?> 	
			
				<p><input type="submit" value="Activate" name="update" class="button-primary" /></p>	
				</form>
			</div>
			<?php

		}

		function showmessage($message, $type = 'message') { //or error
			echo '<div class="'.($type=='message' ? 'updated' : 'error').'">'.addslashes($message).'</div>';
		}


}

function cackle_init() {
	$a = new cackle;
}

add_action('init', 'cackle_init');