<?php
/*
Copyright 2007 Jon Baker (email: jon@miletbaker.com)

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



function wpflickr_get_admin_page() {
	add_options_page("WP-Flickr", "WP-Flickr", "administrator", basename(__FILE__), "wpflickr_get_admin_page_content");
}

function wpflickr_get_admin_page_content() {
	GLOBAL $wpflickr_config;

	// logout
	if($_REQUEST['wpflickr_logout']) {
		update_option("wpflickr_token", "");
		$wpflickr_config['token'] = null;
	}
	else if($_REQUEST['frob'] != "") { 
		$params = array(
			'method'	=> 'flickr.auth.getToken',
			'frob'		=> $_REQUEST['frob'],
			'format'	=> 'php_serial'
		);

		$r = wpflickr_api_call($params, false);

		if($r) {
			update_option("wpflickr_token", $r['auth']['token']['_content']);
			$wpflickr_config['token'] = $r['auth']['token']['_content'];
		}
	}
	
	// save settings
	else if($_REQUEST['wpflickr_save_options']) {
		$has_error = false;
		
		// Validations for options save.

		if(! $has_error) {
			$c = 0;
			foreach($_REQUEST as $k=>$v) {
				// filter out form fields that are not ours
				if(substr($k, 0, 9) != "wpflickr_") 
					continue;

				update_option($k, trim($v));
				$c++;
			}
			
			if (!isset($_REQUEST['wpflickr_alt_title']))
				update_option('wpflickr_alt_title', '0');
			
			if($c > 0) {
				wpflickr_load_config();

				echo '<div class="updated fade"><p><strong>Settings successfully saved.</strong></p></div>';
			}
		}
	}	

	// check authentication token for validity
	$current_user = null;

	if($wpflickr_config['token']) {
		$params = array(
			'method'	=> 'flickr.auth.checkToken',
			//'auth_token'	=> $flickr_auth_token,
			'format'	=> 'php_serial'
		);

		$r = wpflickr_api_call($params, false, true);

		if($r) {
			$current_user = $r;
			if ($wpflickr_config['nsid'] != $current_user['auth']['user']['nsid'])
				update_option("wpflickr_nsid", $current_user['auth']['user']['nsid']);
		}
	} 

	if(! $current_user) {
		echo '<div class="error fade"><p><strong>You need to authorised this plugin with Flickr before it can be used. Please complete the steps below to authorise this plugin.<br/><br/> Please Note: You will need an account with Flickr to use this plugin.</strong></p></div>';
	}
?>
	<style type="text/css">
		.header {
			font-weight: bold;
			border-bottom: 1px solid #CCCCCC;
		}

		.subheader {
			font-style: italic;
			padding-left: 20px;
		}

		.label {
			float: left;	
			text-align: right;
		
			width: 200px;

			padding: 0px;
			margin: 0px;
			margin-right: 10px;
			padding-top: 5px;
		}

		.more {
			font-style: italic;
			font-size: 75%;

			width: 400px;
			padding-left: 210px;
			margin-bottom: 30px;
		}

		.current {
			padding: 3px;
			background-color: #FFFFDD;
			border: 1px solid #EAD43E;
		}

		.disabled {
			padding: 3px;
			background-color: #EFEFEF;
			border: 1px solid #C0C0C0;
			color: #B0B0B0;
		}

		.disabled A {
			color: #B0B0B0;
			text-decoration: none;
			cursor: default;
		}
	</style>

	<div class="wrap">
		<h2>WP-Flickr Plugin</h2>

		<form action="options-general.php?page=<?php echo basename(__FILE__); ?>" method="post">
		<p class="header">Flickr Authentication</p>

		<?php
			if($current_user) {
				echo 'Currently logged in to Flickr as <a href="http://www.flickr.com/people/' . $current_user['auth']['user']['nsid'] . '" target="_new">' . $current_user['auth']['user']['username'] . '</a> (<a href="options-general.php?page=' . basename(__FILE__) . '&wpflickr_logout=true">logout</a>)';

			} else {			
				$wpflickr_config['token'] = null;
				
					$params = array(
						'method'	=> 'flickr.auth.getFrob',
						'format'	=> 'php_serial'
					);

					$r = wpflickr_api_call($params, false);

					$frob = $r['frob']['_content'];

					if($frob) {
						$flickr_url = "http://www.flickr.com/services/auth/";
						$flickr_url .= "?api_key=" . WPFLICKR_API_KEY;
						$flickr_url .= "&perms=read";
						$flickr_url .= "&frob=" . $frob;
						$flickr_url .= "&api_sig=" . md5(WPFLICKR_API_KEY_SS . "api_key" . WPFLICKR_API_KEY . "frob" . $frob . "permsread");

					?>
						Authorizing this plugin with Flickr is a simple, two step process:

						<p id="step1" class="current">
						<strong>Step 1:</strong> <a href="<?php echo $flickr_url; ?>" onClick="this.parentNode.className='disabled'; document.getElementById('step2').className='current';" target="_new">Authorize this application to access Flickr</a>. <em>This will open a new window. When you are finished, come back to this page.</em>
						</p>

						<p id="step2" class="disabled">
						<strong>Step 2:</strong> After authorizing this application with Flickr in the popup window, <a href="options-general.php?page=<?php echo basename(__FILE__); ?>&frob=<?php echo $frob; ?>">click here to complete the authorization process</a>.
						</p>
					<?php
					}
			}
		?>

		<p class="header">Default Options</p>

		<p class="subheader">Default Photo Size</p>

		<p class="label">Photo Size:</p>
		<p class="field">
			<select size=1 name="wpflickr_photo_size">
				<option value="s" <?php if($wpflickr_config['photo_size'] == "s") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="t" <?php if($wpflickr_config['photo_size'] == "t") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="m" <?php if($wpflickr_config['photo_size'] == "m") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="_" <?php if($wpflickr_config['photo_size'] == "_") echo "selected"; ?>>Medium (500 x 375 pixels)</option>
				<option value="b" <?php if($wpflickr_config['photo_size'] == "b") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>

		<p class="more">
		This the default size that will be selected on the page you write posts / pages. You can change this per photo on that page.
		</p>

		
	    <p class="header">Advanced Insert Options</p>

		<p class="subheader">&lt;img&gt; Tag Attributes</p>

		<p class="label">img Tag:</p>
		<p class="field">
			&lt;img src="" class="<input type="text" size=40 name="wpflickr_img_class" value="<?php echo $wpflickr_config['img_class']; ?>">" &gt;
		</p>

		<p class="more">
		If you specify any CSS classes above, a class attribute will be added. Do not enter either ' or ", just the class names separated by a space. <br/><strong>Note:</strong> the src attribute will automatically be set to the Flickr URL.
		</p>

		<p class="label">alt Tag:</p>
		<p class="field">
			<input type="checkbox" value="1" name="wpflickr_alt_title" <?php if($wpflickr_config['alt_title'] == "1") echo "checked"; ?>> Insert an alt tag, using the photo's title from Flickr as the value.
		</p>

		<p class="header"></p>

		<p>
			<input type="submit" name="wpflickr_save_options" value="Save Changes">
		</p>

		</form>
	</div>
<?php
}

function wpflickr_flush_cache() {
        GLOBAL $wpflickr_config;

        $d = @opendir($wpflickr_config['cache_dir']);

	if(! $d)
		return 0;

        $f = 0;
        while(($file = readdir($d)) !== false) {
                $p = $wpflickr_config['cache_dir'] . "/" . $file;

                if(! is_file($p))
                        continue;

                if(is_file($p) && substr($p, strrpos($p, ".") + 1) == "cache")
                        if(@unlink($p))
                                $f++;
        }

        closedir($d);

        return $f;
}
?>
