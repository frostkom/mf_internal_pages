<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_internal_pages/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(is_plugin_active('mf_cache/index.php'))
{
	$obj_cache = new mf_cache();
	$obj_cache->fetch_request();
	$obj_cache->get_or_set_file_content(array('suffix' => 'json'));
}

$json_output = array(
	'success' => false,
);

$obj_internal_pages = new mf_internal_pages();

$type = check_var('type');

$arr_type = explode("/", $type);

$type_switch = $arr_type[0]."/".$arr_type[1];

switch($arr_type[1])
{
	case 'internal':
		if(is_user_logged_in())
		{
			$post_name = $arr_type[(count($arr_type) - 1)];

			$page_content = $obj_internal_pages->get_page_content($post_name);

			if(isset($page_content['external_link']) && $page_content['external_link'] != '')
			{
				$json_output['success'] = true;
				$json_output['redirect'] = $page_content['external_link'];
			}

			else if(isset($page_content['post_content']) && $page_content['post_content'] != '')
			{
				$json_output['success'] = true;
				$json_output['admin_response'] = array(
					'template' => str_replace("/", "_", $type_switch),
					'container' => str_replace("/", "_", $type),
					'output' => apply_filters('the_content', $page_content['post_content']),
					//'timestamp' => date("Y-m-d H:i:s"),
				);
			}

			else
			{
				$json_output['success'] = false;
				$json_output['message'] = sprintf(__("I could not find any information for the page %s that you were requesting"), $post_name);
			}
		}

		else
		{
			$json_output['redirect'] = wp_login_url();
		}
	break;
}

echo json_encode($json_output);