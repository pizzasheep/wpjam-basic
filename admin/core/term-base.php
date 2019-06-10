<?php
add_action('admin_head',function(){
	global $taxonomy;

	$supports	= wpjam_get_taxonomy_supports($taxonomy);

	?>
	
	<style type="text/css">

	.form-field.term-parent-wrap p{display: none;}
	.form-field div.wpjam-img.default{width: 75px; height: 50px;}
	.form-field span.description{font-size: 11px; color:#666; margin: 4px 0; display: block;}

	<?php foreach (['slug', 'description', 'parent'] as $key) { if(!in_array($key, $supports)){ ?>
	.form-field.term-<?php echo $key ?>-wrap{display: none;}
	<?php } } ?>

	<?php if(in_array('order', $supports)){ ?>
	th.manage-column.column-order{width: 74px;text-align: center;}
	td.column-order{width: 74px;text-align: center;}
	<?php } ?>

	</style>
	<?php
});

add_filter('taxonomy_parent_dropdown_args', function($args){
	global $taxonomy;
	$taxonomy_levels	= wpjam_get_taxonomy_levels($taxonomy);

	if($taxonomy_levels > 1){
		$args['depth']	= $taxonomy_levels - 1;
	}

	return $args;
});

add_filter('wpjam_term_options', function($term_options, $taxonomy){
	$term_thumbnail_type		= wpjam_cdn_get_setting('term_thumbnail_type') ?: '';
	$term_thumbnail_taxonomies	= wpjam_cdn_get_setting('term_thumbnail_taxonomies') ?: [];

	if($term_thumbnail_type && $term_thumbnail_taxonomies && in_array($taxonomy, $term_thumbnail_taxonomies)){
		$term_options['thumbnail'] = [
			'title'				=> '缩略图', 
			'taxonomies'		=> $term_thumbnail_taxonomies, 
			'show_admin_column'	=> true,	
			'column_callback'	=> function($term_id){
				return wpjam_get_term_thumbnail($term_id, [50,50]);
			}
		];

		if($term_thumbnail_type == 'img'){
			$width	= wpjam_cdn_get_setting('term_thumbnail_width') ?: 200;
			$height	= wpjam_cdn_get_setting('term_thumbnail_height') ?: 200;

			$term_options['thumbnail']['type']		= 'img';
			$term_options['thumbnail']['item_type']	= 'url';

			if($width || $height){
				$term_options['thumbnail']['size']			= $width.'x'.$height;
				$term_options['thumbnail']['description']	= '尺寸：'.$width.'x'.$height;
			}
		}else{
			$term_options['thumbnail']['type']	= 'image';
		}
	}

	$taxonomy_supports	= wpjam_get_taxonomy_supports($taxonomy);

	if(in_array('order', $taxonomy_supports)){
		$term_options['order']	= [
			'title'				=> '排序',
			'type'				=> 'number',
			'value'				=> 1,
			'show_admin_column'	=> true,
			'sortable_column'	=> 'meta_value_num',
			'description'		=> '数字越大则排序越靠前。'
		];
	}

	return $term_options;
},4,2);