<?php
global $taxonomy_fields;

$taxonomy_fields 	= wpjam_get_term_options($taxonomy);

// 显示 标签，分类，tax ID
add_filter($taxonomy.'_row_actions',function ($actions, $term){
	$actions['term_id'] = 'ID：'.$term->term_id;

	$supports	= wpjam_get_taxonomy_supports($term->taxonomy);

	if(!in_array('slug', $supports)){
		unset($actions['inline hide-if-no-js']);
	}

	return $actions;
},10,2);

// 添加 Term Meta 添加表单
add_action($taxonomy.'_add_form_fields', function($taxonomy){

	global $taxonomy_fields;

	if($taxonomy_fields){
		wpjam_fields($taxonomy_fields, array(
			'data_type'		=> 'term_meta',
			'fields_type'	=> 'div',
			'item_class'	=> 'form-field',
			'is_add'		=> true
		));
	}
});

function wpjam_save_term_fields($term_id, $tt_id, $taxonomy){
	if(wp_doing_ajax()){
		if($_POST['action'] == 'inline-save-tax'){
			return;
		}
	}

	global $taxonomy_fields;
	if(empty($taxonomy_fields)) {
		return;
	}

	if($value = wpjam_validate_fields_value($taxonomy_fields)){
		foreach ($value as $key => $field_value) {
			// if($field_value){
				update_term_meta($term_id, $key, $field_value);
			// }else{
			// 	if(isset($fields[$key]['value'])){	// 如果设置了默认值，也是会存储的
			// 		$field_value	= ($fields[$key]['type'] == 'number')?0:'';
			// 		update_term_meta($term_id, $key, $field_value);
			// 	}elseif(get_term_meta($term_id, $key, true)) {
			// 		delete_term_meta($term_id, $key);
			// 	}
			// }
		}
	}
}
add_action('created_term', 'wpjam_save_term_fields',10,3);
add_action('edited_term', 'wpjam_save_term_fields',10,3);

function wpjam_get_taxonomy_columns($taxonomy_fields, $sortable=false, $return='columns'){
	if(empty($taxonomy_fields)){
		return [];
	}

	$taxonomy_fields	= array_filter($taxonomy_fields, function($field){ return !empty($field['show_admin_column']); });

	if(empty($taxonomy_fields)){
		return [];
	}	

	if($sortable){
		$taxonomy_fields	= array_filter($taxonomy_fields, function($field){ return !empty($field['sortable_column']); });

		if(empty($taxonomy_fields)){
			return [];
		}

		if($return == 'columns'){
			return array_combine(array_keys($taxonomy_fields), array_keys($taxonomy_fields));
		}else{
			return $taxonomy_fields;
		}
			
	}else{
		if($return == 'columns'){
			return array_combine(array_keys($taxonomy_fields), array_column($taxonomy_fields, 'title'));
		}else{
			return $taxonomy_fields;
		}	
	}
}

// Term 列表显示字段的名
add_action('manage_edit-'.$taxonomy.'_columns',	function ($columns){
	global $taxonomy, $taxonomy_fields;

	$supports	= wpjam_get_taxonomy_supports($taxonomy);

	if(!in_array('slug', $supports)){
		unset($columns['slug']);
	}

	if(!in_array('description', $supports)){
		unset($columns['description']);
	}

	$taxonomy_columns	= wpjam_get_taxonomy_columns($taxonomy_fields);

	return array_merge($columns, $taxonomy_columns);
});

// Term 列表显示字段的值
add_filter('manage_'.$taxonomy.'_custom_column', function ($value, $column_name, $term_id){
	global $taxonomy, $taxonomy_fields;

	$column_fields	= wpjam_get_taxonomy_columns($taxonomy_fields, false, 'fields');

	if(isset($column_fields[$column_name])){
		return wpjam_column_callback($column_name, array(
			'id'		=> $term_id,
			'field'		=> $column_fields[$column_name],
			'data_type'	=> 'term_meta'
		));
	}

	return $value;
}, 10, 3);

if(wp_doing_ajax()) {
	return;
}

add_action('manage_edit-'.$taxonomy.'_sortable_columns',function ($columns){
	global $taxonomy, $taxonomy_fields;

	$sortable_columns	= wpjam_get_taxonomy_columns($taxonomy_fields, true);

	return array_merge($columns, $sortable_columns);
});

// 使后台的排序生效
add_action('parse_term_query', function($term_query){
	global $taxonomy, $taxonomy_fields;

	$sortable_fields	= wpjam_get_taxonomy_columns($taxonomy_fields, true, 'fields');

	$orderby	= $term_query->query_vars['orderby'];
	if($orderby && isset($sortable_fields[$orderby])){
		$term_query->query_vars['orderby']	= ($sortable_fields[$orderby]['sortable_column'] == 'meta_value_num')?'meta_value_num':'meta_value';
		$term_query->query_vars['meta_key']	= $orderby;
	}
});