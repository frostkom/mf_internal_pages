<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_internal_pages/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array(
	'success' => false,
);

if(!isset($obj_internal_pages))
{
	$obj_internal_pages = new mf_internal_pages();
}

$type = check_var('type');

$arr_type = explode("/", $type);

$type_switch = $arr_type[0]."/".$arr_type[1];

switch($arr_type[1])
{
	case 'internal':
		if(is_user_logged_in())
		{
			$post_name = $arr_type[(count($arr_type) - 1)];

			$post_information = $obj_internal_pages->get_post_information(array('post_name' => $post_name));

			if(isset($post_information['external_link']) && $post_information['external_link'] != '')
			{
				$json_output['success'] = true;
				$json_output['redirect'] = $post_information['external_link'];
			}

			else if(isset($post_information['post_content']) && $post_information['post_content'] != '')
			{
				$json_output['success'] = true;
				$json_output['admin_response'] = array(
					'template' => str_replace("/", "_", $type_switch),
					'container' => str_replace("/", "_", $type),
					'output' => apply_filters('the_content', $post_information['post_content']),
					//'timestamp' => date("Y-m-d H:i:s"),
				);
			}

			else
			{
				$json_output['success'] = false;
				$json_output['message'] = sprintf(__("I could not find any information for the page %s that you were requesting"), $post_information['post_title']); //$post_name
			}
		}

		else
		{
			$json_output['redirect'] = wp_login_url();
		}
	break;
}

echo json_encode($json_output);