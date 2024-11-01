<?php
/*
Plugin Name: WP-Flickr
Plugin URI: http://clockobj.co.uk/extras/wp-flickr/
Description: The plugin adds a new tab to the upload panel when writing posts / pages to insert img tags from flickr
Author: Jon Baker @ Clockwork Objects
Author URI: http://clockobj.co.uk
Version: 1.0

Copyright 2007 Jon Baker (email: jon@miletbaker.com)

Based on Flickr Tag Plugin for WordPress by Jeffrey Maki

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require(dirname(__FILE__) . "/wp-flickr-write.php");
require(dirname(__FILE__) . "/wp-flickr-admin.php");

define("WPFLICKR_API_KEY", "966065e54a0a7a1c37253572e60392fa");
define("WPFLICKR_API_KEY_SS", "88e474d6424efffb");

add_action('admin_menu', 'wpflickr_get_admin_page'); //wp-flickr-admin.php
add_action('wp_upload_tabs', 'wpflickr_get_user_tab'); //wp-flickr-write.php

wpflickr_load_config();

function wpflickr_get_option($key, $default = null) {
	$v = get_option($key);

	if($v == null)
		return $default;
	else
		return $v;
}

function wpflickr_load_config() {
	GLOBAL $wpflickr_config;

	$wpflickr_config['token'] = wpflickr_get_option("wpflickr_token");
	$wpflickr_config['cache_ttl'] = wpflickr_get_option("wpflickr_cache_ttl", 604800);
	$wpflickr_config['cache_dir'] = dirname(__FILE__) . "/cache";
	$wpflickr_config['nsid'] = wpflickr_get_option("wpflickr_nsid", "Error");

	$wpflickr_config['photo_size'] = wpflickr_get_option("wpflickr_photo_size", "m");
	$wpflickr_config['img_class'] = wpflickr_get_option("wpflickr_img_class", "");
	$wpflickr_config['alt_title'] = wpflickr_get_option("wpflickr_alt_title", "1");

}

function wpflickr_bad_config() {
	return '<div class="flickr_error"><p>There was an error while querying Flickr. Check your configuration, or try this request again later.</p></div>';
}

// API written by Jeffrey Maki (As part of the Flickr Tag Wordpress plugin)

// compatability stuff
if(! function_exists("file_put_contents")) {
	function file_put_contents($file, $contents, $flag) {
		$r = fopen($file, "w+");
		fwrite($r, $contents);
		fclose($r);
	}
}

function wpflickr_api_call($params, $cache = true, $sign = true) {
	GLOBAL $wpflickr_config;

	$params['api_key'] = WPFLICKR_API_KEY;
	if($wpflickr_config['token']) $params['auth_token'] = $wpflickr_config['token'];

	ksort($params);

	$cache_key = md5(join($params, " "));

	$signature_raw = "";
	$encoded_params = array();
	foreach($params as $k=>$v) {
		$encoded_params[] = urlencode($k) . '=' . urlencode($v);

		if($sign)
			$signature_raw .= $k . $v;
	}

	if($sign) 
		array_push($encoded_params, 'api_sig=' . md5(WPFLICKR_API_KEY_SS . $signature_raw));

	if($cache && file_exists($wpflickr_config['cache_dir'] . "/" . $cache_key . ".cache") && (time() - filemtime($wpflickr_config['cache_dir'] . "/" . $cache_key . ".cache")) < $wpflickr_config['cache_ttl'])
		$o = unserialize(file_get_contents($wpflickr_config['cache_dir'] . "/" . $cache_key . ".cache"));
	else {
		@$c = curl_init();

		if($c) {
			curl_setopt($c, CURLOPT_URL, "http://api.flickr.com/services/rest/");
			curl_setopt($c, CURLOPT_POST, 1);
			curl_setopt($c, CURLOPT_POSTFIELDS, implode('&', $encoded_params));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);

			$r = curl_exec($c);
		} else	// no curl available... 
			$r = file_get_contents("http://api.flickr.com/services/rest/?" . implode('&', $encoded_params));

		if(! $r)
			die("API call failed. Is libcurl and or URL fopen() wrappers available?");

	 	$o = unserialize($r);

		if($o['stat'] != "ok")
			return null;

		// save serialized response to cache
		if($cache) {
			if(! is_dir($wpflickr_config['cache_dir']))
				mkdir($wpflickr_config['cache_dir']);

			file_put_contents($wpflickr_config['cache_dir'] . "/" . $cache_key . ".cache", $r, LOCK_EX);
		}
	}

	return $o;
}
?>
