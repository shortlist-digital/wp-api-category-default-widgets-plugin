<?php
/**
* @wordpress-plugin
* Plugin Name: WP API Category Default Widgets Plugin
* Plugin URI: http://github.com/shortlist-digital/wp-api-category-default-widgets-plugin
* Description: Create default widgets for Categories that have no widgets set
* Version: 1.0.0
* Author: Shortlist Studio
* Author URI: http://shortlist.studio
* License: MIT
*/

require_once __DIR__ . '/../../../../vendor/autoload.php';

class WpApiCategoryDefaultWidgetsPlugin
{
	public function __construct()
	{
		add_filter("acf/rest_api/category/get_fields", array($this, 'modify_widget_widgets'), 9, 2);
	}

	function modify_widget_widgets($data, $request) {

		if(isset($data['acf']['widgets']) && !empty($data['acf']['widgets'])) {
			return $data; // Widgets already specificied.
		}

		require_once get_template_directory() . "/libs/services/ListService.php";
		$related_list = AgreableListService::get_default_related_list();

		if ($request && isset($request['name'])) {
			$category_name = $request['name'] ? $request['name'] : 'Unknown category';
		}

		$data['acf']['widgets'] = [
			[
				'acf_fc_layout' => 'grid',
				'limit' => '20',
				'lists'=> [
					$related_list
				]
			]
		];

		return $data;
	}

	function generate_post_stub($post) {

		if (!($post instanceof TimberPost)) {
			$post = new TimberPost($post);
		}

		/**
		 * Trying to be consistent with default ACF API
		 * e.g. Assignment of empty strings is ""
		 */

		if ($post->post_type === 'tile') {
			$images = [$post->get_field('image')];
			$link = $post->get_field('url');
			$categories = false;
			$short_headline = $post->title;
			$author = false;
		} else {
			$images = get_field('hero_images', $post->ID);
			$link = get_permalink($post);
			$categories = $post->categories;
			$short_headline = $post->short_headline;
			$author = [
				'id' => $post->author->id,
				'name' => $post->author->name,
				'slug' => $post->author->user_nicename,
			];
		}

		$grid_post = [
			'title' => ['rendered' => $post->title],
			'date' => $post->date,
			'link' => $link,
			'acf' => [
				'category' => $categories ? [$categories[0]] : false,
				'short_headline' => $short_headline,
				'hero_images' => $images,
				'sell' => $post->sell ? $post->sell : ''
			],
			'_embedded' => [
				'author' => [$author]
			]
		];

		return $grid_post;
	}
}
new WpApiCategoryDefaultWidgetsPlugin();
