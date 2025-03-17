<?php

class mf_internal_pages
{
	var $post_type = 'int_page';
	var $meta_prefix = 'myip_';

	function __construct(){}

	function init()
	{
		load_plugin_textdomain('lang_int_page', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		// Post types
		#######################
		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => _x(__("Internal Pages", 'lang_int_page'), 'post type general name'),
				'menu_name' => __("Internal Pages", 'lang_int_page')
			),
			'public' => true, // is_user_logged_in() removed because it didn't work with payment forms on the page. I.e. accept from payment provider wasn't saved because non-admins was not displayed as logged in att this moment
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			//'menu_position' => 100,
			'supports' => array('title', 'editor', 'page-attributes'),
			'capability_type' => 'page',
			'hierarchical' => true,
		));
		#######################
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

					$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent = '%d' ORDER BY menu_order ASC", $this->post_type, 'publish', $post_id));

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

	function filter_sites_table_pages($arr_pages)
	{
		$arr_pages[$this->post_type] = array(
			'icon' => "fas fa-copy",
			'title' => __("Internal Pages", 'lang_internal_pages'),
		);

		return $arr_pages;
	}

	function get_post_information($data)
	{
		global $wpdb;

		if(!isset($data['post_name'])){		$data['post_name'] = '';}

		$out = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s", $this->post_type, $data['post_name']));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_content = $r->post_content;

			$post_external_link = get_post_meta($post_id, $this->meta_prefix.'external_link', true);

			$out = array(
				//'post_id' => $post_id,
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

		$post_information = $this->get_post_information(array('post_name' => $post_name));

		if(isset($post_information['external_link']) && $post_information['external_link'] != '')
		{
			mf_redirect($post_information['external_link']);

			$post_information['post_content'] = sprintf(__("Redirecting to %s", 'lang_int_page'), "<a href='".$post_information['external_link']."'>".$post_information['external_link']."</a>")."&hellip;";
		}

		if(isset($post_information['post_title']) && $post_information['post_title'] != '')
		{
			echo "<div class='wrap'>
				<h1>".$post_information['post_title']."</h1>"
				.apply_filters('the_content', $post_information['post_content'])
			."</div>";
		}
	}

	function get_rwmb_post_id($data)
	{
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}

		$post_id = check_var('post', 'int');

		if($post_id > 0)
		{
			update_user_meta($data['user_id'], $data['meta_key'], $post_id);
		}

		else
		{
			$post_id = get_user_meta($data['user_id'], $data['meta_key'], true);
		}

		return $post_id;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$post_id = $this->get_rwmb_post_id(array(
			'meta_key' => 'meta_internal_pages_last_id',
		));

		$post_parent = ($post_id > 0 ? mf_get_post_content($post_id, 'post_parent') : 0);

		$arr_fields = array();

		$arr_roles = get_all_roles();

		if(count($arr_roles) > 0)
		{
			$arr_fields[] = array(
				'name' => __("Roles", 'lang_int_page'),
				'id' => $this->meta_prefix.'roles',
				'type' => 'select',
				'options' => $arr_roles,
				'multiple' => true,
				'attributes' => array(
					'size' => get_select_size(array('count' => count($arr_roles))),
				),
			);
		}

		if($post_parent == 0)
		{
			if(apply_filters('get_front_end_admin_id', 0) > 0)
			{
				$arr_fields[] = array(
					'name' => __("Icon", 'lang_int_page')." (".__("Front-end", 'lang_int_page').")",
					'id' => $this->meta_prefix.'front_end_icon',
					'type' => 'select',
					'options' => $obj_base->get_icons_for_select(),
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
				'name' => __("Position", 'lang_int_page')." <i class='fa fa-info-circle blue' title='2 = ".__("Dashboard", 'lang_int_page')."\n5 = ".__("Posts", 'lang_int_page')."\n10 = ".__("Media", 'lang_int_page')."\n20 = ".__("Pages", 'lang_int_page')."\n25 = ".__("Comments", 'lang_int_page')."\n60 = ".__("Appearance", 'lang_int_page')."\n65 = ".__("Plugins", 'lang_int_page')."\n70 = ".__("Users", 'lang_int_page')."\n75 = ".__("Tools", 'lang_int_page')."\n80 = ".__("Settings", 'lang_int_page')."'></i>",
				'id' => $this->meta_prefix.'position',
				'type' => 'number',
				'attributes' => array(
					'min' => 0,
					'max' => 140,
				),
				/*'desc' => "<ul>"
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
				."</ul>",*/
				/*'attributes' => array(
					'condition_type' => 'show_this_if',
					'condition_selector' => 'parent_id',
					'condition_value' => '',
				),*/
			);
		}

		$arr_fields[] = array(
			'name' => __("External Link", 'lang_int_page'),
			'id' => $this->meta_prefix.'external_link',
			'type' => 'url',
		);

		if(count($arr_fields) > 0)
		{
			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'settings',
				'title' => __("Settings", 'lang_int_page'),
				'post_types' => array($this->post_type),
				'context' => 'side',
				'priority' => 'low',
				'fields' => $arr_fields,
			);
		}

		return $meta_boxes;
	}

	function column_header($cols)
	{
		unset($cols['date']);

		$cols['information'] = __("Information", 'lang_int_page');
		$cols['roles'] = __("Roles", 'lang_int_page');
		$cols['position'] = __("Position", 'lang_int_page');

		return $cols;
	}

	function column_cell($col, $post_id)
	{
		switch($col)
		{
			case 'information':
				$post_parent = mf_get_post_content($post_id, 'post_parent');

				$post_meta_external_link = get_post_meta($post_id, $this->meta_prefix.'external_link', true);

				echo "<div class='flex_flow tight'>";

					if($post_parent == 0)
					{
						$post_meta_front_end_icon = get_post_meta($post_id, $this->meta_prefix.'front_end_icon', true);
						$post_meta_icon = get_post_meta($post_id, $this->meta_prefix.'icon', true);

						if($post_meta_front_end_icon != '')
						{
							echo "<div><i class='".$post_meta_front_end_icon." fa-lg'></i></div>";
						}

						if($post_meta_icon != '')
						{
							echo "<div><div class='dashicons-before ".$post_meta_icon."'><br></div></div>";
						}
					}

					if($post_meta_external_link != '')
					{
						echo "<a href='".$post_meta_external_link."'><i class='fa fa-link'></i></a>";
					}

				echo "</div>";
			break;

			case 'roles':
				$post_meta_roles = get_post_meta($post_id, $this->meta_prefix.$col, false);

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
				$post_parent = mf_get_post_content($post_id, 'post_parent');

				if($post_parent == 0)
				{
					$post_meta = get_post_meta($post_id, $this->meta_prefix.$col, true);

					echo $post_meta != '' ? $post_meta : "<span class='grey'>100</span>";
				}
			break;
		}
	}

	function filter_last_updated_post_types($array, $type)
	{
		$array[] = $this->post_type;

		return $array;
	}

	function wp_sitemaps_post_types($post_types)
	{
		unset($post_types[$this->post_type]);

		return $post_types;
	}

	function init_base_admin($arr_views, $data = array())
	{
		global $wpdb;

		if(!isset($data['init'])){		$data['init'] = false;}
		if(!isset($data['include'])){	$data['include'] = 'publish';}

		$templates = "";
		$plugin_include_url = plugin_dir_url(__FILE__);

		if($data['init'] == true)
		{
			mf_enqueue_style('style_internal_pages_admin', $plugin_include_url."style_admin.css");

			$templates .= "<script type='text/template' id='template_admin_internal'>
				<%= output %>
			</script>";
		}

		$user_id = get_current_user_id();
		$profile_role = get_current_user_role($user_id);

		$query_where = "";

		switch($data['include'])
		{
			case 'all':
				$query_where .= " AND (post_status = 'draft' OR post_status = 'publish')";
			break;

			default:
				$query_where .= " AND post_status = '".esc_sql($data['include'])."'";
			break;
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_status, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = '0'".$query_where." ORDER BY menu_order ASC", $this->post_type));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_name = $r->post_name;
			$post_status = $r->post_status;
			$post_title = $r->post_title;
			$post_content = $r->post_content;

			$arr_post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

			if(count($arr_post_roles) == 0 || in_array($profile_role, $arr_post_roles))
			{
				$post_icon = get_post_meta($post_id, $this->meta_prefix.'front_end_icon', true);

				if($post_status != 'publish')
				{
					if(!isset($obj_fea))
					{
						$obj_fea = new mf_fea();
					}

					$arr_post_status = $obj_fea->get_post_status_for_select();

					$post_title .= " (".$arr_post_status[$post_status].")";
				}

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

				$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = '%d'".$query_where." ORDER BY menu_order ASC", $this->post_type, $post_id));

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