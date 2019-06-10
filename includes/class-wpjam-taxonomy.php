<?php
class WPJAM_Taxonomy{
	public static $field_post_ids_list;

	public static function get_term($term, $taxonomy, $children_terms=[], $max_depth=-1, $depth=0){
		if($max_depth == -1){
			return self::parse_for_json($term, $taxonomy);
		}else{
			$term	= self::parse_for_json($term, $taxonomy);
			if(is_wp_error($term)){
				return $term;
			}

			$term['children'] = [];

			if($children_terms){
				if(($max_depth == 0 || $max_depth > $depth+1 ) && isset($children_terms[$term['id']])){
					foreach($children_terms[$term['id']] as $child){
						$term['children'][]	= wpjam_get_term($child, $taxonomy, $children_terms, $max_depth, $depth + 1);
					}
					unset($children_terms[$term['id']]);
				} 
			}

			return $term;
		}
	}

	public static function get_terms($args, $max_depth=-1){
		$taxonomy	= $args['taxonomy'];

		$args['taxonomy']	= [$taxonomy];

		$parent		= 0;
		if(isset($args['parent']) && ($max_depth != -1 && $max_depth != 1)){
			$parent		= $args['parent'];
			unset($args['parent']);
		}

		if($terms = get_terms($args)){
			if($max_depth == -1){
				array_walk($terms, function(&$term) use ($taxonomy){
					$term = self::get_term($term, $taxonomy); 

					if(is_wp_error($term)){
						wpjam_send_json($term);
					}
				});
			}else{
				$top_level_terms	= [];
				$children_terms		= [];

				foreach($terms as $term){
					if(empty($term->parent)){
						if($parent){
							if($term->term_id == $parent){
								$top_level_terms[] = $term;
							}
						}else{
							$top_level_terms[] = $term;
						}
					}else{
						$children_terms[$term->parent][] = $term;
					}
				}

				if($terms = $top_level_terms){
					array_walk($terms, function(&$term) use ($taxonomy, $children_terms, $max_depth){
						$term = self::get_term($term, $taxonomy, $children_terms, $max_depth, 0); 

						if(is_wp_error($term)){
							wpjam_send_json($term);
						}
					});
				}
			}
		}

		return $terms;
	}

	public static function get_parents($term){
		$parents	= [];
		if($term->parent > 0){
			$parent	= get_term($term->parent);
			if($parent){
				$parents[]	= $parent;

				if($parent->parent > 0){
					$parents = array_merge($parents, self::get_parents($parent));
				}
			}
		}

		return $parents;
	}

	public static function get_supports($taxonomy){
		$taxonomy_obj	= get_taxonomy($taxonomy);
		$supports		= $taxonomy_obj->supports ?? ['slug', 'description', 'parent'];

		$taxonomy_levels	= self::get_levels($taxonomy);
		if($taxonomy_levels == 1){
			$supports	= array_diff($supports, ['parent']);
		}

		$supports	= apply_filters('wpjam_taxonomy_supports', $supports, $taxonomy);

		return array_unique($supports);
	}

	public static function get_levels($taxonomy){
		$taxonomy_obj	= get_taxonomy($taxonomy);

		if(isset($taxonomy_obj->levels)){
			return $taxonomy_obj->levels;
		}else{
			return apply_filters('wpjam_taxonomy_levels', 0,  $taxonomy);
		}
	}

	public static function get($term_id){
		$term	= get_term($term_id);

		if(is_wp_error($term) || empty($term)){
			return [];
		}else{
			return self::parse_for_json($term, $term->taxonomy);
		}
	}

	public static function insert($data){
		$taxonomy		= $data['taxonomy']		?? '';

		if(empty($taxonomy)){
			return new WP_Error('empty_taxonomy', '分类模式不能为空');
		}

		$name			= $data['name']			?? '';
		$parent			= $data['parent']		?? 0;
		$slug			= $data['slug']			?? '';
		$description	= $data['description']	?? '';

		

		if(term_exists($name, $taxonomy)){
			return new WP_Error('term_exists', '分组已存在。');
		}

		$term	= wp_insert_term($name, $taxonomy, compact('parent','slug','description'));

		if(is_wp_error($term)){
			return $term;
		}

		$term_id	= $term['term_id'];

		$meta_input	= $data['meta_input']	?? [];

		if($meta_input){
			foreach($meta_input as $meta_key => $meta_value) {
				update_term_meta($term_id, $meta_key, $meta_value);
			}
		}

		return $term_id;
	}

	public static function update($term_id, $data){
		$taxonomy		= $data['taxonomy']	?? '';

		if(empty($taxonomy)){
			return new WP_Error('empty_taxonomy', '分类模式不能为空');
		}

		$term	= wpjam_get_term($term_id, $taxonomy);

		if(is_wp_error($term)){
			return $term;
		}

		if(isset($data['name'])){
			$exist	= term_exists($data['name'], $taxonomy);

			if($exist){
				$exist_term_id	= $exist['term_id'];

				if($exist_term_id != $term_id){
					return new WP_Error('term_name_duplicate', '分组名已被使用。');
				}
			}
		}

		$term_args = [];

		$term_keys = ['name', 'parent', 'slug', 'description'];

		foreach($term_keys as $key) {
			$value = $data[$key] ?? null;
			if (is_null($value)) {
				continue;
			}

			$term_args[$key] = $value;
		}

		if(!empty($term_args)){
			$term =	wp_update_term($term_id, $taxonomy, $term_args);
			if(is_wp_error($term)){
				return $term;	
			}
		}

		$meta_input		= $data['meta_input']	?? [];

		if($meta_input){
			foreach($meta_input as $meta_key => $meta_value) {
				update_term_meta($term['term_id'], $meta_key, $meta_value);
			}
		}

		return true;
	}

	public static function delete($term_id){
		$term	= get_term($term_id);

		if(is_wp_error($term) || empty($term)){
			return $term;
		}

		return wp_delete_term($term_id, $term->taxonomy);
	}

	
	
	public static function parse_for_json($term, $taxonomy){
		$term	= get_term($term);

		if(is_wp_error($term) || empty($term)){
			return new WP_Error('illegal_'.$taxonomy.'_id', '非法 '.$taxonomy.'_id');
		}

		$term_json	= [];
		$term_id	= $term->term_id;

		$term_json['id']			= $term_id;
		$term_json['name']			= $term->name;
		$term_json['page_title']	= $term->name;
		$term_json['share_title']	= $term->name;

		$taxonomy_obj	= get_taxonomy($taxonomy);

		if($taxonomy_obj->public || $taxonomy_obj->publicly_queryable || $taxonomy_obj->query_var){
			$term_json['slug']		= $term->slug;
		}
		$term_json['count']			= (int)$term->count;
		$term_json['description']	= $term->description;
		$term_json['parent']		= $term->parent;
		
		if($term_fields = wpjam_get_term_options($taxonomy)){
			foreach ($term_fields as $term_key => $term_field) {
				$term_value				= get_term_meta($term_id, $term_key, true);
				$term_json[$term_key]	= wpjam_parse_field_value($term_value, $term_field);
			}
		}
		
		return apply_filters('wpjam_term_json', $term_json, $term_id, $taxonomy);
	}
}

