<?php

add_action($taxonomy.'_edit_form_fields', function($term, $taxonomy=''){
	$taxonomy_fields = wpjam_get_term_options($taxonomy);
	
	wpjam_fields($taxonomy_fields, array(
		'data_type'		=> 'term_meta',
		'id'			=> $term->term_id,
		'fields_type'	=> 'tr',
		'item_class'	=> 'form-field'
	));
}, 10, 2);


add_filter('term_updated_messages', function($messages){
	global $taxonomy;

	$labels		= get_taxonomy_labels(get_taxonomy($taxonomy));
	$label_name	= $labels->name;

	$messages['_item']	= array_map(function($message) use ($label_name){
		if($message == $label_name) return $message;

		return str_replace(
			['项目', 'Item'], 
			[$label_name, ucfirst($label_name)], 
			$message
		);
	}, $messages['_item']);

	return $messages;
});