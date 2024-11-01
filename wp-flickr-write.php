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

function wpflickr_get_user_tab() {
	GLOBAL $post_id;

	if(! $post_id)	// only show on post edit/create page
		return array();

	return array('wp-flickr' => array('WP-Flickr', 'upload_files', 'wpflickr_get_user_tab_content', null, null));
}

function wpflickr_get_user_tab_content() {
	global $wpflickr_config;
?>	<script src="../wp-content/plugins/wp-flickr/js/prototype.js"></script>
	<script language="JavaScript">
		function insertIntoEditor(h) {
			var win = window.opener ? window.opener : window.dialogArguments;
                
			if (! win)
				win = top;
		
			tinyMCE = win.tinyMCE;
		
			if(typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content')) {
				tinyMCE.selectedInstance.getWin().focus();
				tinyMCE.execCommand('mceInsertContent', false, h);
			} else
				win.edInsertContent(win.edCanvas, h);
                        
			if(! this.ID)
				this.cancelView();
		
			return false;
		}
		function updatePhotos() {
			window.location.href = window.location.href + "&set=" + $F('wpflickr_sets');
		}
	</script>
	
	<div style="padding: 10px; padding-left: 15px;">
    	<!-- Added by Jon -->
        <div style="float:left;">
        <p class="label" style="font-weight: bold;border-bottom: 1px solid #CCCCCC;">Photo Size:</p>
		<p class="field">
			<select size=1 id="wpflickr_size">
				<option value="_s" <?php if ($wpflickr_config['photo_size'] == "s") echo "selected"; ?>>Square (75 x 75 pixels)</option>
				<option value="_t" <?php if ($wpflickr_config['photo_size'] == "t") echo "selected"; ?>>Thumbnail (100 x 75 pixels)</option>
				<option value="_m" <?php if ($wpflickr_config['photo_size'] == "m") echo "selected"; ?>>Small (240 x 180 pixels)</option>
				<option value="" <?php if ($wpflickr_config['photo_size'] == "_") echo "selected"; ?>>Medium (500 x 375 pixels)</option>
				<option value="_b" <?php if ($wpflickr_config['photo_size'] == "b") echo "selected"; ?>>Large (1024 x 768 pixels)</option>
			</select>
		</p>
        </div>
        
    	<div style="float:left;margin-left:20px;">
		<p class="label" style="font-weight: bold;border-bottom: 1px solid #CCCCCC;">Photo Set (to Select From):</p>

		<p class="field">
			<?php
				$params = array(
					'method'	=> 'flickr.photosets.getList',
					'format'	=> 'php_serial'
				);

				$r = wpflickr_api_call($params, false, true);

				if($r) { ?>
						<select id="wpflickr_sets" onchange="updatePhotos();">
							<option value="" <?php if(empty($_REQUEST['set'])) echo "selected"; ?>>My Public PhotoStream</option>
							<option value="f" <?php if($_REQUEST['set'] == 'f') echo "selected"; ?>>My Favorites</option>
					<?php
					foreach($r['photosets']['photoset'] as $number=>$photoset) {
						$selected = $_REQUEST['set'] == $photoset['id'] ? "selected" : "";
						echo '<option value="' . $photoset['id'] . '" ' . $selected . '>' . $photoset['title']['_content'] . ' (' . $photoset['photos'] . ' photo' . (($photoset['photos'] != 1) ? "s" : "") . ')</option>';
					}
					echo '</select>';

				} else { 
					echo "<em>No sets were found on Flickr. Did you <a href='options-general.php?page=flickr-tag.php' target='_top'>setup the plugin</a> yet?</em>";
				}
			?>
		</p>
        </div>
		<div style="clear:both;"></div>
        <?php 
		global $wpflickr_config;
		$title = "My Public PhotoStream";
		
		if(! function_exists("wpflickr_api_call")) {
			include('wp-flickr.php');
		}
		
		if(empty($_REQUEST['set'])) {
			$params = array(
				'method'	=> 'flickr.people.getPublicPhotos',
				'format'	=> 'php_serial',
				'per_page'	=> '50',
				'user_id'	=> $wpflickr_config['nsid']
			);
	
			$rPhotos = wpflickr_api_call($params, false, true);
			
		}
		elseif ($_REQUEST['set'] == "f") {
			$params = array(
				'method'	=> 'flickr.favorites.getList',
				'per_page'	=> '50',
				'format'	=> 'php_serial'
			);
	
			$rPhotos = wpflickr_api_call($params, false, true);
		}
		else {
			$params = array(
				'method'	=> 'flickr.photosets.getPhotos',
				'photoset_id' => $_REQUEST['set'],
				'per_page'	=> '50',
				'format'	=> 'php_serial'
			);
	
			$rPhotoset = wpflickr_api_call($params, false, true);
		}
		
		function photoURL($farm, $server, $id, $secret) {
			return $img_url = "http://farm" . $farm . ".static.flickr.com/" . $server . "/" . $id . "_" . $secret;
		}
		
	?>
	<p style="font-weight: bold;border-bottom: 1px solid #CCCCCC;">Photos from flickr (Click thumbnail image, to insert into post / page)</p>
	<p style="padding-left: 30px;">
	<?php
		 if($rPhotos) {
			foreach($rPhotos['photos']['photo'] as $number=>$photo) {
				// If changing the html below, copy to photoset, as it is identical
	?>
			<a href="javascript:void(0)" onclick="insertIntoEditor('<img src=\'<?php echo photoURL($photo['farm'], $photo['server'], $photo['id'], $photo['secret']); ?>' + $('wpflickr_size').value + '.jpg\' <?php if (!empty($wpflickr_config['img_class'])) echo 'class=\\\'' . $wpflickr_config['img_class'] . '\\\''; if ($wpflickr_config['alt_title'] == "1") echo 'alt=\\\'' . $photo['title'] . '\\\''; ?>/>'); return false;"><img src="<?php echo photoURL($photo['farm'], $photo['server'], $photo['id'], $photo['secret']); ?>_s.jpg"/></a>
	<?php
			}
		}
		if($rPhotoset) {
			foreach($rPhotoset['photoset']['photo'] as $number=>$photo) {
	?>
			<a href="javascript:void(0)" onclick="insertIntoEditor('<img src=\'<?php echo photoURL($photo['farm'], $photo['server'], $photo['id'], $photo['secret']); ?>' + $('wpflickr_size').value + '.jpg\' <?php if (!empty($wpflickr_config['img_class'])) echo 'class=\\\'' . $wpflickr_config['img_class'] . '\\\''; if ($wpflickr_config['alt_title'] == "1") echo 'alt=\\\'' . $photo['title'] . '\\\''; ?>/>'); return false;"><img src="<?php echo photoURL($photo['farm'], $photo['server'], $photo['id'], $photo['secret']); ?>_s.jpg"/></a>
	<?php
			}
		}
		
	?>
	</p>
	</div>
<?php } ?>
