<?php
/*
	This file is required only if you are installing Register Plus Redux
	under WordPress MU /plugins/ directory.
	Unluckily WordPress MU is designed to disable ALL /plugins/* when
	activating a new user/blog, preventing Cimy User Extra Fields to correctly
	save extra fields data in that phase.
*/
// need to know if registering with VHOST set to 'yes', as seems filters are not added in time, grr!
if (constant( "VHOST" ) == 'yes')
	$rpr_mu_register_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
else
	$rpr_mu_register_action = "";

if (isset($_REQUEST["blog_id"])) {
	$mu_blog_id = intval($_REQUEST["blog_id"]);

	if (rpr_mu_blog_exists($mu_blog_id) /*&& ($_REQUEST["stage"] != "validate-blog-signup")*/) {
		switch_to_blog($mu_blog_id);
	}
}

if (!defined("WP_CONTENT_DIR"))
	define("WP_CONTENT_DIR", ABSPATH."/wp_content");

// Leave this after all!
if ((defined('WP_INSTALLING')) || ($rpr_mu_register_action == "register"))
	require_once(WP_CONTENT_DIR."/plugins/register-plus-redux/register-plus-redux-mu.php");

function rpr_mu_blog_exists($blog_id, $c_site_id=-1) {
	global $wpdb, $site_id;

	$blog_id = intval($blog_id);
	$c_site_id = intval($c_site_id);

	if ($c_site_id == -1)
		$c_site_id = $site_id;

	$sql = "SELECT blog_id FROM $wpdb->blogs WHERE blog_id=".$blog_id." AND site_id=".$c_site_id;
	$id = $wpdb->get_var($sql);

	// if exists any result then the blog exists too!
	if (isset($id))
		return true;

	return false;
}
