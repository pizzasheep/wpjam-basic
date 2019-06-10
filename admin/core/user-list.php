<?php
function wpjam_user_row_actions($actions, $user){
	$capability	= (is_multisite())?'manage_site':'manage_options';
	if(current_user_can($capability) && !is_network_admin()){
		$actions['login_as']	= '<a title="以此身份登陆" href="'.wp_nonce_url("users.php?action=login_as&amp;users=$user->ID", 'bulk-users').'">以此身份登陆</a>';
	}

	$actions['user_id'] = 'ID: '.$user->ID;
	
	return $actions;
}
add_filter('user_row_actions',		'wpjam_user_row_actions', 999, 2);
add_filter('ms_user_row_actions',	'wpjam_user_row_actions',10,2);

add_filter('handle_bulk_actions-users', function($sendback, $action, $user_ids){
	if($action == 'login_as'){
		wp_set_auth_cookie($user_ids, true);
		wp_set_current_user($user_ids);
	}
	return admin_url();
},10,3);

//添加用户注册时间和其他字段
function wpjam_manage_users_columns($columns){
	if(!is_network_admin()){
		$columns['registered']	= '注册时间';
	}

	if(wpjam_basic_get_setting('simplify_user')){
		unset($columns['name']);  //隐藏姓名
		unset($columns['email']); 
		unset($columns['posts']);
	}

	if(is_network_admin()){
		wpjam_array_push($columns, ['detail'=>'用户信息'], 'registered');	
	}else{
		wpjam_array_push($columns, ['detail'=>'用户信息'], 'role');	
	}
		
	return $columns;
};

add_filter('manage_users_columns', 'wpjam_manage_users_columns');
add_filter('wpmu_users_columns', 'wpjam_manage_users_columns');


//显示用户注册时间和其他字段
add_filter('manage_users_custom_column', function ($value, $column, $user_id){
	if($column == 'registered'){
		$user = get_userdata($user_id);
		return get_date_from_gmt($user->user_registered);
	}elseif($column == 'detail'){
		$user 	= get_userdata($user_id);
		if(is_network_admin()){
			$avatar	= get_avatar($user_id, 32);	
		}else{
			$avatar	= '';
		}
		
		return $avatar.apply_filters('wpjam_user_detail_column', '昵称：'.$user->display_name, $user_id);
	}else{
		return $value;
	}
}, 11, 3);

if(wpjam_basic_get_setting('order_by_registered')){
	//设置注册时间为可排序列.
	add_filter( "manage_users_sortable_columns", function($sortable_columns){
		$sortable_columns['registered'] = 'registered';
		return $sortable_columns;
	});

	//默认按注册时间排序.
	add_action('pre_user_query', function($query){
		if(!isset($_REQUEST['orderby'])){
			if( empty($_REQUEST['order']) || !in_array($_REQUEST['order'], ['asc','desc']) ){
				$_REQUEST['order'] = 'desc';
			}
			$query->query_orderby = "ORDER BY user_registered ".$_REQUEST['order'];
		}
	});
}

// 后台可以根据显示的名字来搜索用户 
add_filter('user_search_columns',function($search_columns){
	return ['ID', 'user_login', 'user_email', 'user_url', 'user_nicename', 'display_name'];
});

add_action('admin_head',function(){
	?>
	<style type="text/css">
		.fixed th.column-role{width: 84px;}
		.fixed th.column-registered{width: 140px;}
		.fixed th.column-blogs{width: 224px;}
		<?php if(is_network_admin()){ ?>.column-username img{display: none;}
		.column-detail img{float: left; margin-right: 10px; margin-top: 1px;}<?php } ?>
	</style>
	<?php
});

