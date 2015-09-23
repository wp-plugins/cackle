<?php
class CackleMonitor{
    public static function check_monitor()
    {
        /* Check cackle_monitor for synchronizing process
        * Return post_id which needed to sync or -1 if not
        */

        $cackle_monitor = get_option('cackle_monitor');
        if (!isset($cackle_monitor->counter) || $cackle_monitor->counter > 10000) $cackle_monitor->counter = 0;

        if ($cackle_monitor->counter % 2) {
            $mode = 'long';
        } else {
            $mode = 'short';
        }
        switch ($mode) {
            case 'long':
                $object = get_option('cackle_monitor');
                break;
            case 'short':
                $object = get_option('cackle_monitor_short');
                break;

        }


        if ($object->mode == 'by_channel') {
//if sync is called by pages, we need pause for 30 sec from the last sync
            if ($object->time + 15 > time()) {
                return -1;
            }
            if ($object->status == 'inprocess' && $object->time + 120 > time()) {
//do nothing because in progress
                return -1;
            }
            if ($object->status == 'next_page') {
// do sync with the same post
                $ret_object = new stdClass();
                $ret_object->post_id = $object->post_id;
                $ret_object->mode = $mode;
                return $ret_object;
            }
            if ($object->status == 'finish' || $object->time + 120 < time()) {
//get next post
                global $wpdb;
                if ($mode == 'long') {
                    $min_max_post_id = $wpdb->get_results("
SELECT MAX(ID) as max, MIN(ID) as min
FROM $wpdb->posts
WHERE post_type != 'revision'
AND post_status = 'publish'
");
                } else {
                    $min_max_post_id = $wpdb->get_results("SELECT MAX(ID) as max, MIN(ID) as min
FROM ( select * from $wpdb->posts
WHERE post_type != 'revision'
AND post_status = 'publish'
order by post_date desc limit 50) cackle
");
                }

                $min_max_post_id = $min_max_post_id[0];
                $min_post_id = $min_max_post_id->min;
                $max_post_id = $min_max_post_id->max;

                if ($object->post_id > $min_post_id) {
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
                    $ret_object->post_id = $next_post_id;
                    $ret_object->mode = $mode;
                    return $ret_object;

                }
                if ($object->post_id <= $min_post_id) {
//set max because it is initial sync
                    $ret_object = new stdClass();
                    $ret_object->post_id = $max_post_id;
                    $ret_object->mode = $mode;
                    return $ret_object;
                }


            }
        } elseif ($object->mode == 'all_comments') {
            if ($object->status == 'inprocess' && $object->time + 120 > time()) {
//don't start if all comments sync in progress
                return -1;
            } else {
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

                update_option('cackle_monitor', $object);
                update_option('cackle_monitor_short', $object_s);

            }
        }
    }
}


?>