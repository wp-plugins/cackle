
<?php
    $api_id = get_option('cackle_apiId','');
    require_once(dirname(__FILE__) . '/cackle_api.php');
    require_once(dirname(__FILE__) . '/sync.php');
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
        var mcChannel = '<?php echo $post->ID?>';
        </script>
    <?php
    if(get_option('cackle_comments_hidewpcomnts')==1){
    ?>
    <?php
    } 
    else{
        if (version_compare(get_bloginfo('version'), '3.0', '<')){
    ?> 
        <script type="text/javascript">
        var element = document.getElementById("comments");
        if (element != null) {
            element.parentNode.removeChild(element);
        }
        var respond=document.getElementById('respond');
        if (respond != null) {
            respond.parentNode.removeChild(respond);
        }
        var list=document.getElementsByClassName('commentlist');
        if (list[0] != null) {
           list[0].style.display = 'none';
        }
        </script>

    <?php
        }
        else {
        ?>
        <script type="text/javascript">
        element = document.getElementById("comments");
        element.parentNode.removeChild(element);
        </script>
        <?php 
        }
    }?>

        <script type="text/javascript">
        var wrapper = document.createElement('div');
        wrapper.id = "comments" 
        var myDiv = document.getElementById('mc-container'); 
        wrapper.appendChild(myDiv.cloneNode(true)); 
        myDiv.parentNode.replaceChild(wrapper, myDiv);	
        </script>



<?php if($api_id==''):?>API ID not specified<?php endif;?>

