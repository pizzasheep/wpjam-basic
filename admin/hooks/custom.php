<?php
if(wpjam_basic_get_setting('disable_auto_update')){  
	remove_action('admin_init', '_maybe_update_core');
	remove_action('admin_init', '_maybe_update_plugins');
	remove_action('admin_init', '_maybe_update_themes');
}

add_action('admin_head', function(){
	remove_action('admin_bar_menu', 'wp_admin_bar_wp_menu', 10);
	
	add_action('admin_bar_menu', function ($wp_admin_bar){
		if(wpjam_basic_get_setting('admin_logo')){
			$title 	= '<img src="'.wpjam_get_thumbnail(wpjam_basic_get_setting('admin_logo'),40,40).'" style="height:20px; padding:6px 0">';
		}else{
			$title	= '<span class="ab-icon"></span>';
		}
		$wp_admin_bar->add_menu( array(
			'id'    => 'wp-logo',
			'title' => $title,
			'href'  => self_admin_url(),
			'meta'  => array(
				'title' => __('About'),
			),
		) );
	});

	echo wpjam_basic_get_setting('admin_head');

	if(wpjam_basic_get_setting('favicon')){ 
		echo '<link rel="shortcut icon" href="'.wpjam_basic_get_setting('favicon').'">';
	}
});

// 修改 WordPress Admin text
add_filter('admin_footer_text', function($text){
	if(wpjam_basic_get_setting('admin_footer')){
		return wpjam_basic_get_setting('admin_footer');
	}
	return $text;
});

if(wpjam_basic_get_setting('disable_privacy')){
	add_action('admin_menu', function (){

		global $menu, $submenu;

		unset($submenu['options-general.php'][45]);

		// Bookmark hooks.
		remove_action( 'admin_page_access_denied', 'wp_link_manager_disabled_message' );

		// Privacy tools
		remove_action( 'admin_menu', '_wp_privacy_hook_requests_page' );
		// Privacy hooks
		remove_filter( 'wp_privacy_personal_data_erasure_page', 'wp_privacy_process_personal_data_erasure_page', 10, 5 );
		remove_filter( 'wp_privacy_personal_data_export_page', 'wp_privacy_process_personal_data_export_page', 10, 7 );
		remove_filter( 'wp_privacy_personal_data_export_file', 'wp_privacy_generate_personal_data_export_file', 10 );
		remove_filter( 'wp_privacy_personal_data_erased', '_wp_privacy_send_erasure_fulfillment_notification', 10 );

		// Privacy policy text changes check.
		remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'text_change_check' ), 100 );

		// Show a "postbox" with the text suggestions for a privacy policy.
		remove_action( 'edit_form_after_title', array( 'WP_Privacy_Policy_Content', 'notice' ) );

		// Add the suggested policy text from WordPress.
		remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'add_suggested_content' ), 1 );

		// Update the cached policy info when the policy page is updated.
		remove_action( 'post_updated', array( 'WP_Privacy_Policy_Content', '_policy_page_updated' ) );
	},9);
}


// 屏蔽后台功能提示
// if(wpjam_basic_get_setting('disable_update')){
// 	add_filter ('pre_site_transient_update_core', '__return_null');

// 	remove_action ('load-update-core.php', 'wp_update_plugins');
// 	add_filter ('pre_site_transient_update_plugins', '__return_null');

// 	remove_action ('load-update-core.php', 'wp_update_themes');
// 	add_filter ('pre_site_transient_update_themes', '__return_null');
// }

// 移除 Google Fonts
// if(wpjam_basic_get_setting('disable_google_fonts')){
// 	//add_filter( 'gettext_with_context', 'wpjam_disable_google_fonts', 888, 4);
// 	function wpjam_disable_google_fonts($translations, $text, $context, $domain ) {
// 		$google_fonts_contexts = array('Open Sans font: on or off','Lato font: on or off','Source Sans Pro font: on or off','Bitter font: on or off');
// 		if( $text == 'on' && in_array($context, $google_fonts_contexts ) ){
// 			$translations = 'off';
// 		}

// 		return $translations;
// 	}
// }

if(wpjam_basic_get_setting('timestamp_file_name')){
	// 防止重名造成大量的 SQL 请求
	add_filter('wp_handle_sideload_prefilter', function($file){
		$file['name']	= time().'-'.$file['name'];
		return $file;
	});

	add_filter('wp_handle_upload_prefilter', function($file){
		$file['name']	= time().'-'.$file['name'];
		return $file;
	});
}

add_action('wp_loaded', function (){
	if(CDN_NAME == '')
		return;

	// 不用生成 -150x150.png 这类的图片
	add_filter('intermediate_image_sizes_advanced', function($sizes){
		if(isset($sizes['full'])){
			return ['full'=>$sizes['full']];
		}else{
			return [];
		}
	});

	add_filter('image_size_names_choose', function($sizes){
		$_sizes	= $sizes;

		$sizes	= [];
		$sizes['full']	= $_sizes['full'];
		unset($_sizes['full']);

		foreach(['large', 'medium', 'thumbnail'] as $key){
			if(get_option($key.'_size_w') || get_option($key.'_size_h')){
				$sizes[$key]	= $_sizes[$key];
			}else{
				unset($_sizes[$key]);
			}
		}

		if($_sizes){
			foreach ($_sizes as $key => $value) {
				$sizes[$key]	= $value;
			}
		}

		return $sizes;
	});

	add_filter('upload_dir', function($uploads){
		$uploads['url']		= wpjam_cdn_replace_local_hosts($uploads['url']);
		$uploads['baseurl']	= wpjam_cdn_replace_local_hosts($uploads['baseurl']);
		return $uploads;
	});

	// add_filter('wp_calculate_image_srcset_meta', '__return_empty_array');
	// add_filter('image_downsize', '__return_true');

	// wp_get_attachment_image_src(271);
}, 11);

add_action('admin_page_access_denied', function(){
	if((is_multisite() && is_user_member_of_blog(get_current_user_id(), get_current_blog_id())) || !is_multisite()){
		wp_die(__( 'Sorry, you are not allowed to access this page.' ).'<a href="'.admin_url().'">返回首页</a>', 403);
	}
});



