<?php
/*
Plugin Name: MF Internal Pages
Plugin URI: https://github.com/frostkom/mf_internal_pages
Description: 
Version: 2.2.16
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_int_page
Domain Path: /lang

Depends: Meta Box, MF Base
GitHub Plugin URI: frostkom/mf_internal_pages
*/

if(is_admin())
{
	include_once("include/classes.php");

	$obj_internal_pages = new mf_internal_pages();

	register_activation_hook(__FILE__, 'activate_int_page');
	register_uninstall_hook(__FILE__, 'uninstall_int_page');

	add_action('init', array($obj_internal_pages, 'init'));
	add_action('admin_menu', array($obj_internal_pages, 'admin_menu'));
	add_action('rwmb_meta_boxes', array($obj_internal_pages, 'rwmb_meta_boxes'));

	add_filter('manage_int_page_posts_columns', array($obj_internal_pages, 'column_header'), 5);
	add_action('manage_int_page_posts_custom_column', array($obj_internal_pages, 'column_cell'), 5, 2);

	load_plugin_textdomain('lang_int_page', false, dirname(plugin_basename(__FILE__)).'/lang/');

	function activate_int_page()
	{
		require_plugin("meta-box/meta-box.php", "Meta Box");
	}

	function uninstall_int_page()
	{
		mf_uninstall_plugin(array(
			'post_types' => array('int_page'),
		));
	}
}