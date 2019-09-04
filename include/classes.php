<?php

class mf_internal_pages
{
	function __construct()
	{
		$this->post_type = 'int_page';
		$this->meta_prefix = 'myip_';
	}

	function init()
	{
		$labels = array(
			'name' => _x(__("Internal Pages", 'lang_int_page'), 'post type general name'),
			'menu_name' => __("Internal Pages", 'lang_int_page')
		);

		$args = array(
			'labels' => $labels,
			'public' => is_user_logged_in(),
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			//'menu_position' => 100,
			'supports' => array('title', 'editor', 'page-attributes'),
			'capability_type' => 'page',
			'hierarchical' => true,
		);

		register_post_type($this->post_type, $args);
	}

	function get_admin_menu_items()
	{
		global $wpdb;

		$out = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent = '0' ORDER BY menu_order ASC", $this->post_type, 'publish'));

		if($wpdb->num_rows > 0)
		{
			$profile_role = get_current_user_role(get_current_user_id());

			foreach($result as $r)
			{
				$post_id_parent = $post_id = $r->ID;
				$post_name = $r->post_name;
				$post_title = $r->post_title;

				$arr_post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

				if(count($arr_post_roles) == 0 || in_array($profile_role, $arr_post_roles))
				{
					$out[$post_id] = array(
						'post_name' => $post_name,
						'post_title' => $post_title,
						'children' => array(),
					);

					$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent = %d ORDER BY menu_order ASC", $this->post_type, 'publish', $post_id));

					if($wpdb->num_rows > 0)
					{
						foreach($result2 as $r)
						{
							$post_id = $r->ID;
							$post_name = $r->post_name;
							$post_title = $r->post_title;

							$arr_post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

							if(count($arr_post_roles) == 0 || in_array($profile_role, $arr_post_roles))
							{
								$out[$post_id_parent]['children'][$post_id] = array(
									'post_name' => $post_name,
									'post_title' => $post_title,
								);
							}
						}
					}
				}
			}
		}

		return $out;
	}

	function admin_menu()
	{
		$menu_root = "mf_internal_pages/";
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$menu_title = __("Internal Pages", 'lang_int_page');
		add_submenu_page("index.php", $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type);

		$arr_menu_items = $this->get_admin_menu_items();

		foreach($arr_menu_items as $menu_key => $menu_item)
		{
			$menu_start = $this->post_type."_".$menu_item['post_name'];

			$post_icon = get_post_meta($menu_key, $this->meta_prefix.'icon', true);
			$post_position = get_post_meta($menu_key, $this->meta_prefix.'position', true);

			if(count($menu_item['children']) > 0)
			{
				if(mf_get_post_content($menu_key) == '') // To hide the first empty submenu page in the menu system
				{
					$child_first_key = key($menu_item['children']);

					$menu_start = $this->post_type."_".$menu_item['children'][$child_first_key]['post_name'];
				}

				add_menu_page($menu_item['post_title'], $menu_item['post_title'], 'read', $menu_start, array($this, 'admin_menu_pages'), $post_icon, ($post_position != '' ? $post_position : 100));

				foreach($menu_item['children'] as $child_key => $child_item)
				{
					$menu_page = $this->post_type."_".$child_item['post_name'];

					add_submenu_page($menu_start, $child_item['post_title'], $child_item['post_title'], 'read', $menu_page, array($this, 'admin_menu_pages'));
				}
			}

			else
			{
				add_menu_page($menu_item['post_title'], $menu_item['post_title'], 'read', $menu_start, array($this, 'admin_menu_pages'), $post_icon, ($post_position != '' ? $post_position : 100));
			}
		}
	}

	function get_page_content($post_name)
	{
		global $wpdb;

		$out = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $this->post_type, $post_name));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_content = $r->post_content;

			$post_external_link = get_post_meta($post_id, $this->meta_prefix.'external_link', true);

			$out = array(
				'post_title' => $post_title,
				'post_content' => $post_content,
				'external_link' => $post_external_link,
			);
		}

		return $out;
	}

	function admin_menu_pages()
	{
		$page = check_var('page', 'char');
		$post_name = str_replace($this->post_type."_", "", $page);

		$page_content = $this->get_page_content($post_name);

		if(isset($page_content['external_link']) && $page_content['external_link'] != '')
		{
			mf_redirect($page_content['external_link']);

			$page_content['post_content'] = sprintf(__("Redirecting to %s", 'lang_int_page'), "<a href='".$page_content['external_link']."'>".$page_content['external_link']."</a>")."&hellip;";
		}

		if(isset($page_content['post_title']) && $page_content['post_title'] != '')
		{
			echo "<div class='wrap'>
				<h1>".$page_content['post_title']."</h1>"
				.apply_filters('the_content', $page_content['post_content'])
			."</div>";
		}
	}

	function get_icons_for_select()
	{
		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose Here", 'lang_int_page')." --";

		$obj_font_icons = new mf_font_icons();
		$arr_icons = $obj_font_icons->get_array(array('allow_optgroup' => false));

		foreach($arr_icons as $key => $value)
		{
			$arr_data[$key] = $value;
		}

		return $arr_data;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		$arr_fields = array();

		$arr_roles = get_all_roles(); //array('denied' => array('administrator'))

		if(count($arr_roles) > 0)
		{
			$arr_fields[] = array(
				'name' => __("Roles", 'lang_int_page'),
				'id' => $this->meta_prefix.'roles',
				'type' => 'select3',
				'options' => $arr_roles,
				'multiple' => true,
				'attributes' => array(
					'size' => get_select_size(array('count' => count($arr_roles))),
				),
			);
		}

		//$obj_base = new mf_base();

		//if($obj_base->has_page_template(array('template' => "/plugins/mf_front_end_admin/include/templates/template_admin.php")) > 0)
		if(apply_filters('get_front_end_admin_id', 0) > 0)
		{
			$arr_fields[] = array(
				'name' => __("Icon", 'lang_int_page')." (".__("Front-end", 'lang_int_page').")",
				'id' => $this->meta_prefix.'front_end_icon',
				'type' => 'select',
				'options' => $this->get_icons_for_select(),
			);
		}

		$arr_fields[] = array(
			'name' => __("Icon", 'lang_int_page')." (".__("Admin", 'lang_int_page').")",
			'id' => $this->meta_prefix.'icon',
			'type' => 'select',
			'options' => array(
				'' => "-- ".__("Choose Here", 'lang_int_page')." --",
				'dashicons-admin-site' => __("Admin", 'lang_int_page'),
				'dashicons-cart' => __("Cart", 'lang_int_page'),
				'dashicons-clipboard' => __("Clipboard", 'lang_int_page'),
				'dashicons-dashboard' => __("Dashboard", 'lang_int_page'),
				'dashicons-download' => __("Download", 'lang_int_page'),
				'dashicons-email-alt' => __("E-mail", 'lang_int_page'),
				'dashicons-facebook-alt' => "Facebook",
				'dashicons-forms' => __("Forms", 'lang_int_page'),
				'dashicons-format-gallery' => __("Gallery", 'lang_int_page'),
				'dashicons-groups' => __("Groups", 'lang_int_page'),
				'dashicons-images-alt2' => __("Images", 'lang_int_page'),
				'dashicons-admin-network' => __("Key", 'lang_int_page'),
				'dashicons-list-view' => __("List", 'lang_int_page'),
				'dashicons-lock' => __("Lock", 'lang_int_page'),
				'dashicons-admin-media' => __("Media", 'lang_int_page'),
				'dashicons-megaphone' => __("Megaphone", 'lang_int_page'),
				'dashicons-networking' => __("Network", 'lang_int_page'),
				'dashicons-controls-play' => __("Play", 'lang_int_page'),
				'dashicons-plus-alt' => __("Plus", 'lang_int_page'),
				'dashicons-phone' => __("Phone", 'lang_int_page'),
				'dashicons-shield' => __("Security", 'lang_int_page'),
				'dashicons-admin-generic' => __("Settings", 'lang_int_page')." (".__("Default", 'lang_int_page').")",
				'dashicons-tickets' => __("Tickets", 'lang_int_page'),
				'dashicons-unlock' => __("Unlock", 'lang_int_page'),
				'dashicons-video-alt3' => __("Video", 'lang_int_page'),
				'dashicons-warning' => __("Warning", 'lang_int_page'),
				/* https://developer.wordpress.org/resource/dashicons/ */
			),
			/*'attributes' => array(
				'condition_type' => 'show_this_if',
				'condition_selector' => 'parent_id',
				'condition_value' => '',
			),*/
		);

		$arr_fields[] = array(
			'name' => __("Position", 'lang_int_page'),
			'id' => $this->meta_prefix.'position',
			'type' => 'number',
			'attributes' => array(
				'min' => 0,
				'max' => 140,
			),
			'desc' => "<ul>"
				."<li>2 = ".__("Dashboard", 'lang_int_page')."</li>"
				//."<li>4 = ".__("Separator", 'lang_int_page')."</li>"
				."<li>5 = ".__("Posts", 'lang_int_page')."</li>"
				."<li>10 = ".__("Media", 'lang_int_page')."</li>"
				//."<li>15 = ".__("Links", 'lang_int_page')."</li>"
				."<li>20 = ".__("Pages", 'lang_int_page')."</li>"
				."<li>25 = ".__("Comments", 'lang_int_page')."</li>"
				//."<li>59 = ".__("Separator", 'lang_int_page')."</li>"
				."<li>60 = ".__("Appearance", 'lang_int_page')."</li>"
				."<li>65 = ".__("Plugins", 'lang_int_page')."</li>"
				."<li>70 = ".__("Users", 'lang_int_page')."</li>"
				."<li>75 = ".__("Tools", 'lang_int_page')."</li>"
				."<li>80 = ".__("Settings", 'lang_int_page')."</li>"
				//."<li>99 = ".__("Separator", 'lang_int_page')."</li>"
			."</ul>",
			/*'attributes' => array(
				'condition_type' => 'show_this_if',
				'condition_selector' => 'parent_id',
				'condition_value' => '',
			),*/
		);

		$arr_fields[] = array(
			'name' => __("External Link", 'lang_int_page'),
			'id' => $this->meta_prefix.'external_link',
			'type' => 'url',
		);

		$meta_boxes[] = array(
			'id' => '',
			'title' => __("Settings", 'lang_int_page'),
			'post_types' => array($this->post_type),
			'context' => 'side',
			'priority' => 'low',
			'fields' => $arr_fields,
		);

		return $meta_boxes;
	}

	function column_header($cols)
	{
		unset($cols['date']);

		$cols['icons'] = __("Icons", 'lang_int_page');
		$cols['roles'] = __("Roles", 'lang_int_page');
		$cols['position'] = __("Position", 'lang_int_page');

		return $cols;
	}

	function column_cell($col, $id)
	{
		switch($col)
		{
			case 'icons':
				$post_meta_front_end_icon = get_post_meta($id, $this->meta_prefix.'front_end_icon', true);
				$post_meta_icon = get_post_meta($id, $this->meta_prefix.'icon', true);

				echo "<div class='flex_flow'>";

					if($post_meta_front_end_icon != '')
					{
						echo "<div><i class='".$post_meta_front_end_icon." fa-lg'></i></div>";
					}

					if($post_meta_icon != '')
					{
						echo "<div><div class='dashicons-before ".$post_meta_icon."'><br></div></div>";
					}

				echo "</div>";
			break;

			case 'roles':
				$post_meta_roles = get_post_meta($id, $this->meta_prefix.'roles', false);

				$arr_data = get_roles_for_select(array('add_choose_here' => false, 'use_capability' => false));

				$arr_roles = array();

				foreach($arr_data as $key => $value)
				{
					if(in_array($key, $post_meta_roles))
					{
						$arr_roles[] = $value;
					}
				}

				$count_temp = count($arr_roles);

				if($count_temp > 1)
				{
					echo "<span title='".implode(", ", $arr_roles)."'>".$count_temp."</span>";
				}

				else
				{
					echo implode(", ", $arr_roles);
				}
			break;

			case 'position':
				$post_meta = get_post_meta($id, $this->meta_prefix.'position', true);

				echo $post_meta != '' ? $post_meta : "<span class='grey'>100</span>";
			break;
		}
	}

	function wp_head()
	{
		global $post;

		if(isset($post->post_type) && $post->post_type == $this->post_type)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_style('style_internal_admin', $plugin_include_url."style.css", $plugin_version);
		}
	}

	function init_base_admin($arr_views)
	{
		global $wpdb;

		$templates = "";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		if(!is_admin())
		{
			mf_enqueue_style('style_internal_admin', $plugin_include_url."style_admin.css", $plugin_version);

			$templates .= "<script type='text/template' id='template_admin_internal'>
				<%= output %>
			</script>";
		}

		$user_id = get_current_user_id();
		$profile_role = get_current_user_role($user_id);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent = '0' ORDER BY menu_order ASC", $this->post_type, 'publish'));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_name = $r->post_name;
			$post_title = $r->post_title;
			$post_content = $r->post_content;

			$arr_post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

			if(count($arr_post_roles) == 0 || in_array($profile_role, $arr_post_roles))
			{
				$post_icon = get_post_meta($post_id, $this->meta_prefix.'front_end_icon', true);

				$arr_views['internal_'.$post_name] = array(
					'name' => $post_title,
					'icon' => $post_icon,
					'items' => array(),
					'templates_id' => 'internal',
					'templates' => $templates,
					'api_url' => $plugin_include_url,
				);

				$arr_items = array(
					array(
						'id' => $post_name,
						'name' => $post_title,
						'clickable' => ($post_content != ''),
					),
				);

				$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent = %d ORDER BY menu_order ASC", $this->post_type, 'publish', $post_id));

				if($wpdb->num_rows > 0)
				{
					foreach($result2 as $r)
					{
						$post_id_child = $r->ID;
						$post_name_child = $r->post_name;
						$post_title_child = $r->post_title;

						$arr_post_roles = get_post_meta($post_id_child, $this->meta_prefix.'roles');

						if(count($arr_post_roles) == 0 || in_array($profile_role, $arr_post_roles))
						{
							$arr_items[] = array(
								'id' => $post_name_child,
								'name' => $post_title_child,
							);
						}
					}
				}

				$arr_views['internal_'.$post_name]['items'] = $arr_items;
			}
		}

		return $arr_views;
	}
}