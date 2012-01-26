<?php
/*
Plugin Name: Cackle comments
Plugin URI: http://cackle.ru
Description: This plugin allows your website's audience communicate through social networks like Facebook, Vkontakte, Twitter, e.t.c.
Version: 1.10
Author: Denis Golovachev
Author URI: http://borov.net
*/


class cackle  {

		function cackle () {

			add_filter( 'comments_template', array($this, 'comments_template'));
			add_action('wp_head', array($this, 'head_script'));
			add_action('admin_menu', array($this, 'admin_menu'));
		}

		function comments_template () {
			return dirname( __FILE__ ) . '/comment-template.php';
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
				 		update_option('cackle_apiId', (int)$_POST['api_id']);
				 		update_option('cackle_comments_hidewpcomnts', (isset($_POST['hidewpcomments'])) ? 1 : 0);


				 		$this->showmessage('Setting are saved');
				 	}
				 }

			?>
			<div class="wrap">
			<h2>Cackle Social Comments</h2>
				<form method="post">
                                    <a href="http://cackle.ru" target="_blank"><img alt="cackle logo" src="http://cackle.ru/static/images/logo.png"></a>
                                    <p>Cackle -  comments platform that helps your website's audience communicate through social networks.</p>
                                    <p>Please, <a href="http://cackle.ru/site/new" target="_blank">register</a> to obtain your own API key.</p>

                                    <h3>Settings</h3>
				<?php	wp_nonce_field( plugin_basename( __FILE__ ), 'cackle_comments_wpnonce', false, true ); ?>
				<?php $apiId = get_option('cackle_apiId','')?>

				<p><?php echo __('Cackle API ID','cackle_comments'); ?>: <input type="text" value="<?php echo $apiId;?>" name="api_id"/></p>

				<p><?php echo __('Hide wordpress default comments','cackle_comments'); ?>: <input type="checkbox" value="1" name="hidewpcomments" <?php if(get_option('cackle_comments_hidewpcomnts')==1):?>  checked="checked" <?php endif;?>/></p>

				<p><input type="submit" value="Update Settings" name="update" class="button-primary" /></p>
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