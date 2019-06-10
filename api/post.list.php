<?php
global $wp, $wp_query;

/* 规则：
** 1. 分成主的查询和子查询（$args['sub']=1）
** 2. 主查询支持 $_GET 参数 和 $_GET 参数 mapping
** 3. 子查询（sub）只支持 $args 参数
** 4. 主查询返回 next_cursor 和 total_pages，current_page，子查询（sub）没有
** 5. $_GET 参数只适用于 post.list 
** 6. taxonomy.list 只能用 $_GET 参数 mapping 来传递参数
*/

$output		= $args['output'] ?? '';
$is_sub		= $args['sub'] ?? false;
$is_search	= $_GET['s'] ?? false;
$use_cursor	= $is_search ? false : true;

$post_type	= $args['post_type'] ?? null;
if(is_null($post_type) && !$is_sub){
	if($_post_type = wpjam_get_parameter('post_type')){
		$post_type = $_post_type;
	}
}

if($post_type && $post_type != 'any' && !is_array($post_type)){
	$post_type_object	= get_post_type_object($post_type);
	if(!$post_type_object){
		wpjam_send_json(array(
			'errcode'	=> 'post_type_not_defined',
			'errmsg'	=> 'post_type 未定义'
		));
	}
}

$wp->set_query_var('post_type', $post_type);

// post_status 支持的 $args 参数为 post_status ，get 参数为 status
$post_status	= 'publish';

if(!empty($args['post_status'])){
	$post_status	= $args['post_status'];
}

if(!$is_sub){
	if($status = wpjam_get_parameter('status')){
		$post_status = $status;
	}
}

$wp->set_query_var('post_status', $post_status);

// 缓存处理
// $wp->set_query_var('cache_results', false);
// $wp->set_query_var('update_post_meta_cache', true);
// $wp->set_query_var('update_post_term_cache', true);
$wp->set_query_var('lazy_load_term_meta', false);	// 在 the_posts filter 的时候，已经处理了

// 搜索 post_meta 的 filter
if(!$is_sub && $is_search && !empty($args['search'])){
	$search_metas	= explode(',', $args['search']);

	add_filter('posts_search', function($search, $query) use ($search_metas){
		return WPJAM_PostType::parse_search($query->query_vars, $search_metas);
	},2,2);
}

$ignore_sticky_posts	= true;

if(!$is_sub){
	if($post_type && $post_type != 'any' && !is_array($post_type)){
		$response['current_post_type']	= [
			'post_type'	=> $post_type,
			'label'		=> $post_type_object->label,
		];
	}


	$date_query = [];
	
	if($cursor	= wpjam_get_parameter('cursor',	array('default'=>0,	'type'=>'int'))){
		$date_query[]	= array('before' => get_date_from_gmt(date('Y-m-d H:i:s',$cursor)));
	}

	if($since	= wpjam_get_parameter('since',	array('default'=>0,	'type'=>'int'))){
		$date_query[]	= array('after' => get_date_from_gmt(date('Y-m-d H:i:s',$since)));
	}

	if($date_query){
		$wp->set_query_var('date_query', $date_query);
	}

	if($paged	= wpjam_get_parameter('paged',	array('type'=>'int'))){
		$wp->set_query_var('paged', $paged);
	}

	// $_GET 参数 mapping
	if(isset($args['mapping'])){
		$mapping	= wp_parse_args($args['mapping']);
		if($mapping && is_array($mapping)){
			foreach ($mapping as $key => $mapping_key) {
				if($value = wpjam_get_parameter($mapping_key)){
					$wp->set_query_var($key, $value);
				}
			}
		}
	}

	$ignore_sticky_posts = false;
}

if(!empty($args['ignore_sticky_posts'])){
	$ignore_sticky_posts	= $args['ignore_sticky_posts'];
}

$wp->set_query_var('ignore_sticky_posts', $ignore_sticky_posts);

// 同时支持 $_GET 参数 和 $args 参数
foreach (['posts_per_page', 'order','orderby','meta_key','meta_value','meta_query','meta_compare','post__in','post__not_in'] as $key) {
	$value = $args[$key] ?? null;

	if(!$is_sub){
		if($get = wpjam_get_parameter($key)){
			$value = $get;
		}
	}

	if($value){
		if($key == 'meta_compare'){
			$value	= str_replace(['lt','gt','e'], ['<','>','='], $value);
			$wp->set_query_var($key, $value);
		}elseif($key == 'orderby'){
			$meta_key_list = array(
				'price'			=> 'price',
				'sales'			=> 'sales',
				'total_sales'	=> 'total_sales',
				'views'			=> 'views',
				'fav_count'		=> '_fav_count',
				'like_count'	=> '_like_count',
				'reply_count'	=> '_reply_count',
				'comment_count'	=> '_comment_count',
			);

			if(isset($meta_key_list[$value])){
				$wp->set_query_var('orderby', 'meta_value_num');
				$wp->set_query_var('meta_key', $meta_key_list[$value]);
			}elseif($value == 'location'){
				$wp->set_query_var('orderby', 'meta_value_num');
				$wp->set_query_var('meta_key', 'views');
			}elseif(!$is_search){
				if(strpos($value, '&')){
					$value	= wp_parse_args($value);
				}
				$wp->set_query_var('orderby', $value);
			}

			$use_cursor	= ($value == 'date')?true:false;
		}elseif($key == 'post__in'){
			$wp->set_query_var('post__in', wp_parse_id_list($value));
			if(empty($wp->query_vars['orderby'])){
				$wp->set_query_var('orderby', 'post__in');
			}
		}else{
			$wp->set_query_var($key, $value);
		}
	}
}

// taxonomy 参数处理，同时支持 $_GET 和 $args 参数
$tax_query		= array();

$list_term_ids	= '';
$list_taxonomy	= '';

if($post_type){
	$taxonomies = get_object_taxonomies($post_type);
}else{
	$taxonomies = get_taxonomies(['public' => true]);
}

if($taxonomies){
	$taxonomy_key_list	= array(
		'category'	=> array('cat', 'category_id', 'cat_id'),
		'post_tag'	=> array('tag_id')
	);

	foreach ($taxonomies as $taxonomy) {
		if(!$is_sub){
			if($taxonomy == 'category'){
				$slug = wpjam_get_parameter('category_name');
			}elseif($taxonomy == 'post_tag'){
				$slug = wpjam_get_parameter('tag');
			}else{
				$slug = wpjam_get_parameter($taxonomy);
			}

			if($slug){
				$term = get_term_by('slug', $slug, $taxonomy);

				$current_taxonomy	= wpjam_get_term($term, $taxonomy);
				if(is_wp_error($current_taxonomy)){
					wpjam_send_json($current_taxonomy);
				}

				if(empty($response['current_taxonomy'])){
					$response['current_taxonomy']	= $taxonomy;
				}

				if(empty($response['page_title'])){
					$response['page_title']		= $current_taxonomy['page_title'];
				}

				if(empty($response['share_title'])){
					$response['share_title']	= $current_taxonomy['share_title'];
				}

				$response['current_'.$taxonomy]	= $current_taxonomy;
			}
		}

		$taxonomy_keys	= $taxonomy_key_list[$taxonomy]??array($taxonomy.'_id');

		foreach ($taxonomy_keys as $key) {

			$value = $args[$key]??'';
			if(!$is_sub && ($get = wpjam_get_parameter($key))){
				$value = $get;
			}

			if($value){
				$tax_query[$taxonomy]	= ['taxonomy'=>$taxonomy, 'terms'=>array($value), 'field'=>'id'];
				$current_taxonomy		= wpjam_get_term($value, $taxonomy);
				if(is_wp_error($current_taxonomy)){
					wpjam_send_json($current_taxonomy);
				}

				if(!$is_sub){
					if(empty($response['current_taxonomy'])){
						$response['current_taxonomy']	= $taxonomy;
					}

					if(empty($response['page_title'])){
						$response['page_title']		= $current_taxonomy['page_title'];
					}

					if(empty($response['share_title'])){
						$response['share_title']	= $current_taxonomy['share_title'];
					}

					$response['current_'.$taxonomy]	= $current_taxonomy;
				}
			}

			$value	= $args[$key.'s']??'';
			if(!$is_sub && ($get = wpjam_get_parameter($key.'s'))){
				$value = $get;
			}

			if($value){
				if($value == 'parent'){
					$term_parent	= wpjam_get_parameter('parent')?:($args['parent']??'');
					if($term_parent){
						$parent_taxonomy	= wpjam_get_term($term_parent, $taxonomy);
						if(is_wp_error($parent_taxonomy)){
							wpjam_send_json($parent_taxonomy);
						}

						$list_term_ids	= get_terms(['taxonomy'=>$taxonomy, 'fields'=>'ids', 'parent'=>$term_parent, 'hide_empty'=>0]);

						if(!$is_sub){
							$response['parent_'.$taxonomy]	= $parent_taxonomy;
							$response['current']			= 'parent_'.$taxonomy;
						}
					}
				}else{
					$list_term_ids	= wp_parse_id_list($value); 
				}

				$list_taxonomy	= $taxonomy;
			}
		}
	}
}

// 如果是分类汇总页面
if($list_taxonomy && $list_term_ids){
	$output	= $output ?: $list_taxonomy.'s';

	if($list_term_ids){

		$tax_query[$taxonomy] = array('taxonomy'=>$list_taxonomy, 'terms'=>$list_term_ids, 'field'=>'id');

		if($tax_query){	
			$the_tax_query = array_values($tax_query);
			$the_tax_query['relation']	= 'OR';
			$wp->set_query_var('tax_query', $the_tax_query);
		}

		$posts_per_page = $wp->query_vars['posts_per_page']??0;

		$wp->set_query_var('posts_per_page', count($list_term_ids)*$posts_per_page);

		// $wp->query_posts();
		// $wp_query->have_posts();

		foreach ($list_term_ids as $list_term_id) {
			$term_json	= wpjam_get_term($list_term_id, $list_taxonomy);
			if(is_wp_error($term_json)){
				wpjam_send_json($term_json);
			}

			$wp->set_query_var('posts_per_page', $posts_per_page);

			$tax_query[$taxonomy] = array('taxonomy'=>$list_taxonomy, 'terms'=>array($list_term_id), 'field'=>'id');

			if($tax_query){	
				$the_tax_query = array_values($tax_query);
				$the_tax_query['relation']	= 'AND';
				$wp->set_query_var('tax_query', $the_tax_query);
			}

			$wp->query_posts();

			$posts_json = array();
			if($wp_query->have_posts()){
				$posts_json	= apply_filters('wpjam_posts_json', $wp_query->posts, $args);
				$posts_json	= array_map(function($post) use ($args){ return wpjam_get_post($post->ID, $args); }, $posts_json);
			}

			$sub_output	= $args['sub_output']??$args['post_type'].'s';
			$term_json[$sub_output]	= $posts_json;
			$response[$output][]	= $term_json;
		}
	}
}else{
	$output	= $output ?: ($post_type ? $post_type.'s' : 'posts');
	if($tax_query){
		$tax_query	= array_values($tax_query);
		$tax_query['relation']	= 'AND';
		$wp->set_query_var('tax_query', $tax_query);
	}

	// wpjam_print_r($wp);

	$wp->query_posts();

	// wpjam_print_r($wp_query);

	$posts_json = [];

	if($wp_query->have_posts()){
		$posts_json	= apply_filters('wpjam_posts_json', $wp_query->posts, $args);
		$posts_json	= array_map(function($post_json) use ($args){ return wpjam_get_post($post_json->ID, $args); }, $posts_json);
	}

	if(!$is_sub){
		$response['total']			= (int)$wp_query->found_posts;
		$response['total_pages']	= (int)$wp_query->max_num_pages;
		$response['current_page']	= (int)wpjam_get_parameter('paged',	array('default'=>1,	'type'=>'int'));

		if($use_cursor){
			$response['next_cursor']	= ($posts_json && $wp_query->max_num_pages>1) ? end($posts_json)['timestamp'] : 0;
		}
	}

	$response[$output]	= $posts_json;
}

if($post_type && $post_type != 'any' && !is_array($post_type)){
	if(empty($response['page_title'])){
		$response['page_title']	= $post_type_object->label;
	}

	if(empty($response['share_title'])){
		$response['share_title']	= $post_type_object->label;
	}
}