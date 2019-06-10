<?php
global $wpjam_list_table;

if(empty($wpjam_list_table)){
	$wpjam_list_table	= new WPJAM_Post_List_Table([
		'post_type'	=> $post_type
	]);
}

