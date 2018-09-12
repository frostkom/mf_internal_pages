<?php

class mf_internal_pages
{
	function __construct()
	{
		$this->meta_prefix = "myip_";
	}

	function init()
	{
		$labels = array(
			'name' => _x(__("Internal Pages", 'lang_int_page'), 'post type general name'),
			'menu_name' => __("Internal Pages", 'lang_int_page')
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			//'menu_position' => 100,
			'supports' => array('title', 'editor', 'page-attributes'),
			'capability_type' => 'page',
			'hierarchical' => true,
		);

		register_post_type('int_page', $args);
	}

	function admin_menu()
	{
		global $wpdb;

		$menu_root = "mf_internal_pages/";
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$menu_title = __("Internal Pages", 'lang_int_page');
		add_submenu_page("index.php", $menu_title, $menu_title, $menu_capability, "edit.php?post_type=int_page");

		$user_id = get_current_user_id();

		$profile_role = get_current_user_role($user_id);

		$result = $wpdb->get_results("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = 'int_page' AND post_parent = '0' AND post_status = 'publish' ORDER BY menu_order ASC");

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_name = $r->post_name;
			$post_title = $r->post_title;

			/*$post_show_in_menu = get_post_meta($post_id, $this->meta_prefix.'show_in_menu', true);

			if($post_show_in_menu == 'yes')
			{*/
				$post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

				if(IS_ADMIN || count($post_roles) == 0 || in_array($profile_role, $post_roles))
				{
					$post_icon = get_post_meta($post_id, $this->meta_prefix.'icon', true);
					$post_position = get_post_meta($post_id, $this->meta_prefix.'position', true);

					$menu_start = "int_page_".$post_name;

					add_menu_page($post_title, $post_title, 'read', $menu_start, array($this, 'admin_menu_pages'), $post_icon, ($post_position != '' ? $post_position : 100));

					$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title FROM ".$wpdb->posts." WHERE post_type = 'int_page' AND post_parent = %d AND post_status = 'publish' ORDER BY menu_order ASC", $post_id));

					foreach($result2 as $r)
					{
						$post_id = $r->ID;
						$post_name = $r->post_name;
						$post_title = $r->post_title;

						/*$post_show_in_menu = get_post_meta($post_id, $this->meta_prefix.'show_in_menu', true);

						if($post_show_in_menu == 'yes')
						{*/
							$post_roles = get_post_meta($post_id, $this->meta_prefix.'roles');

							if(IS_ADMIN || count($post_roles) == 0 || in_array($profile_role, $post_roles))
							{
								$menu_page = "int_page_".$post_name;

								add_submenu_page($menu_start, $post_title, $post_title, 'read', $menu_page, array($this, 'admin_menu_pages'));
							}
						//}
					}
				}
			//}
		}
	}

	function admin_menu_pages()
	{
		global $wpdb;

		$page = check_var('page', 'char');
		$page = str_replace("int_page_", "", $page);

		$result = $wpdb->get_results("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = 'int_page' AND post_name = '".$page."'");

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;
			$post_content = $r->post_content;

			$post_external_link = get_post_meta($post_id, $this->meta_prefix.'external_link', true);

			if($post_external_link != '')
			{
				mf_redirect($post_external_link);

				$post_content = sprintf(__("Redirecting to %s", 'lang_int_page'), "<a href='".$post_external_link."'>".$post_external_link."</a>")."&hellip;";
			}

			echo "<div class='wrap'>
				<h1>".$post_title."</h1>"
				.apply_filters('the_content', $post_content)
			."</div>";
		}
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		$arr_groups = array();

		if(isset($wpdb))
		{
			$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'groups' AND post_status = 'publish' ORDER BY menu_order ASC");

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;

				$arr_groups[$post_id] = $post_title;
			}

			$arr_roles = get_all_roles(); //array('denied' => array('administrator'))
		}

		$meta_boxes[] = array(
			'id' => '',
			'title' => __("Settings", 'lang_int_page'),
			'post_types' => array('int_page'),
			'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				/*array(
					'name'  => __("Show in menu", 'lang_int_page'),
					'id' => $this->meta_prefix.'show_in_menu',
					'type' => 'select',
					'options' => get_yes_no_for_select(),
				),*/
				array(
					'name'  => __("Roles", 'lang_int_page'),
					'id' => $this->meta_prefix.'roles',
					'type' => 'select',
					'options' => $arr_roles,
					'multiple' => true,
				),
				array(
					'name'  => __("Icon", 'lang_int_page'),
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
						'dashicons-facebook' => __("Facebook", 'lang_int_page'),
						'dashicons-forms' => __("Forms", 'lang_int_page'),
						'dashicons-format-gallery' => __("Gallery", 'lang_int_page'),
						'dashicons-groups' => __("Groups", 'lang_int_page'),
						'dashicons-images-alt2' => __("Images", 'lang_int_page'),
						'dashicons-admin-network' => __("Key", 'lang_int_page'),
						'dashicons-list-view' => __("List", 'lang_int_page'),
						'dashicons-lock' => __("Lock", 'lang_int_page'),
						'dashicons-media-default' => __("Media", 'lang_int_page'),
						'dashicons-megaphone' => __("Megaphone", 'lang_int_page'),
						'dashicons-networking' => __("Network", 'lang_int_page'),
						'dashicons-controls-play' => __("Play", 'lang_int_page'),
						'dashicons-plus-alt' => __("Plus", 'lang_int_page'),
						'dashicons-phone' => __("Phone", 'lang_int_page'),
						'dashicons-shield' => __("Security", 'lang_int_page'),
						'dashicons-admin-generic' => __("Settings", 'lang_int_page')." (".__("Default", 'lang_int_page').")",
						'dashicons-tickets' => __("Tickets", 'lang_int_page'),
						'dashicons-unlock' => __("Unlock", 'lang_int_page'),
						'dashicons-warning' => __("Warning", 'lang_int_page'),
						/* https://developer.wordpress.org/resource/dashicons/ */
					),
					/*'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => 'parent_id',
						'condition_value' => '',
					),*/
				),
				array(
					'name'  => __("Position", 'lang_int_page'),
					'id' => $this->meta_prefix.'position',
					'type' => 'number',
					'attributes' => array(
						'min' => 0,
						'max' => 120,
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
				),
				array(
					'name' => __("External Link", 'lang_int_page'),
					'id' => $this->meta_prefix.'external_link',
					'type' => 'url',
				),
			)
		);

		return $meta_boxes;
	}

	function column_header($cols)
	{
		unset($cols['date']);

		$cols['roles'] = __("Roles", 'lang_int_page');
		$cols['position'] = __("Position", 'lang_int_page');
		//$cols['show_in_menu'] = __("Show in menu", 'lang_int_page');

		return $cols;
	}

	function column_cell($col, $id)
	{
		switch($col)
		{
			case 'roles':
				$post_meta = get_post_meta($id, $this->meta_prefix.$col, false);

				$arr_data = get_roles_for_select(array('add_choose_here' => false, 'use_capability' => false));

				$i = 0;

				foreach($arr_data as $key => $value)
				{
					if(in_array($key, $post_meta))
					{
						echo ($i > 0 ? ", " : "").$value;

						$i++;
					}
				}
			break;

			case 'position':
				$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

				echo $post_meta != '' ? $post_meta : 100;
			break;

			/*case 'show_in_menu':
				$post_meta = get_post_meta($id, $this->meta_prefix.'show_in_menu', true);

				echo "<i class='".($post_meta == "yes" ? "fa fa-check green" : "fa fa-close red")." fa-lg'></i>";
			break;*/
		}
	}
}