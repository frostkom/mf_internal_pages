<?php
/*
Plugin Name: MF Internal Pages
Plugin URI: https://github.com/frostkom/mf_internal_pages
Description:
Version: 2.6.5
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_int_page
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_internal_pages = new mf_internal_pages();

	add_action('init', array($obj_internal_pages, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_int_page');

		add_action('admin_menu', array($obj_internal_pages, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_internal_pages, 'filter_sites_table_pages'));

		add_action('rwmb_meta_boxes', array($obj_internal_pages, 'rwmb_meta_boxes'));

		add_filter('manage_int_page_posts_columns', array($obj_internal_pages, 'column_header'), 5);
		add_action('manage_int_page_posts_custom_column', array($obj_internal_pages, 'column_cell'), 5, 2);

		add_filter('filter_last_updated_post_types', array($obj_internal_pages, 'filter_last_updated_post_types'), 10, 2);
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_internal_pages, 'wp_sitemaps_post_types'));
	}

	function uninstall_int_page()
	{
		include_once("include/classes.php");

		$obj_internal_pages = new mf_internal_pages();

		mf_uninstall_plugin(array(
			'post_types' => array($obj_internal_pages->post_type),
			'user_meta' => array('meta_internal_pages_last_id'),
		));
	}
}