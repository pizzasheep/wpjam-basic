<?php
if(wpjam_basic_get_setting('simplify_user')){
	//移除不必要的用户联系信息
	// add_filter('user_contactmethods', function ( $contactmethods ) {
	// 	unset($contactmethods['aim']);
	// 	unset($contactmethods['yim']);
	// 	unset($contactmethods['jabber']);
		
	// 	//也可以自己增加
	// 	//$contactmethods['user_mobile'] = '手机号码';
	// 	//$contactmethods['user_contact'] = '收货联系人';
	// 	//$contactmethods['user_address'] = '收货地址';

	// 	return $contactmethods;
	// }); 

	add_action('show_user_profile','wpjam_edit_user_profile');
	add_action('edit_user_profile','wpjam_edit_user_profile');
	function wpjam_edit_user_profile($user){
		?>
		<script>
		jQuery(document).ready(function($) {
			$('#first_name').parent().parent().hide();
			$('#last_name').parent().parent().hide();
			$('#display_name').parent().parent().hide();
			$('.user-email-wrap').parent().parent().prev('h2').hide();
			$('.user-email-wrap').parent().parent().hide();
			$('.user-description-wrap').parent().parent().prev('h2').hide();
			$('.user-description-wrap').parent().parent().hide();
			$('.show-admin-bar').hide();
		});
		</script>
	<?php
	}

	add_action('personal_options_update','wpjam_edit_user_profile_update');
	add_action('edit_user_profile_update','wpjam_edit_user_profile_update');
	function wpjam_edit_user_profile_update($user_id){
		if (!current_user_can('edit_user', $user_id))
			return false;

		$user = get_userdata($user_id);

		$_POST['nickname']		= ($_POST['nickname'])?:$user->user_login;
		$_POST['display_name']	= $_POST['nickname'];

		$_POST['first_name']	= '';
		$_POST['last_name']		= '';
	}
}

// add_action('user_register', function($user_id){
// 	$user = get_userdata($user_id);

// 	wp_update_user(array(
// 		'ID'			=> $user_id,
// 		'display_name'	=> $user->user_login
// 	)) ;

// });


/* 在后台修改用户昵称的时候检查是否重复 */
// add_action('user_profile_update_errors', function ($errors, $update, $user){
// 	$check = wpjam_check_nickname($user->nickname,$user->ID);
	
// 	if(is_wp_error($check)){
// 		$errors->add( 'nickname_'.$check->get_error_code, '<strong>错误</strong>：'.$check->get_error_message(), array( 'form-field' => 'nickname' ) );
// 	}
	
// },10,3 );


// 检测用户名是合法标准
function wpjam_check_nickname($nickname, $user_id=0 ){
	if(!$nickname)
		return new WP_Error('empty', $nickname.' 为空');

	if(mb_strwidth($nickname)>20)
		return new WP_Error('too_long', $nickname.' 超过20个字符。');

	if(wpjam_blacklist_check($nickname))
		return new WP_Error('illegal', $nickname. '含有非法字符。');
	

	if($nickname != wpjam_get_validated_nickname($nickname))
		return new WP_Error('invalid', $nickname.' 非法，只能含有中文汉字、英文字母、数字、下划线、中划线和点。');

	if(wpjam_is_duplicate_nickname($nickname,$user_id)){
		return new WP_Error('duplicate', $nickname.' 已被人使用！');
	}

	return true;
}

// 检测用户名是否重复
function wpjam_is_duplicate_nickname($nickname, $user_id=0){
	$users	= get_users(array('blog_id'=>0,'meta_key'=>'nickname', 'meta_value'=>$nickname));
	if(count($users) > 1){
		return true;
	}elseif($users && $user_id != $users[0]->ID){
		return true;
	}

	$users	= get_users(array('blog_id'=>0,'login'=>$nickname));
	if(count($users) > 1){
		return true;
	}elseif($users && $user_id != $users[0]->ID){
		return true;
	}


	return false;
}

// 只能含有中文汉字、英文字母、数字、下划线、中划线和点。
function wpjam_get_validated_nickname($nickname){

	// $nickname	= remove_accents( $nickname );
	// $nickname	= preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $nickname);	// Kill octets
	// $nickname	= preg_replace('/&.+?;/', '', $nickname); // Kill entities
	
	//限制不能使用特殊的中文
	$nickname	= preg_replace('/[^A-Za-z0-9_.\-\x{4e00}-\x{9fa5}]/u', '', $nickname);
	
	$nickname	= trim($nickname);
	// Consolidate contiguous whitespace
	$nickname	= preg_replace('|\s+|', ' ', $nickname);
	
	return $nickname;
}