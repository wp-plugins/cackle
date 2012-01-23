<?php 
	$api_id = get_option('cackle_apiId','');
?>

<?php 

if(get_option('cackle_comments_hidewpcomnts')!=1)
{
	$theme = get_theme_root().'/'.get_template();
		if(file_exists($theme.'/comments.php'))
			include($theme.'/comments.php');
		else if (file_exists( ABSPATH . WPINC . '/theme-compat/comments.php'))
			require( ABSPATH . WPINC . '/theme-compat/comments.php');
}		
?>

	<?php if ( post_password_required() || ! comments_open()) : ?>
	<?php
			return;
		endif;
	?>
<div id="mc-container"></div>
<script type="text/javascript">
    var mcSite = '<?php echo $api_id?>';
</script>
	<?php if($api_id==''):?>API ID not specified<?php endif;?>	
</div>
