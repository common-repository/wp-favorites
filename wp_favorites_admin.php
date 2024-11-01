<?php
/**
 * Administrative interface and functions for wp_favorites
 */

// Add the admin menu
add_action('admin_menu', 'wp_favorites_admin_menu');
// Initialize the settings
add_action('admin_init', 'wp_favorites_init');

function wp_favorites_admin_menu()
{
	add_options_page('WP Favorites Options', 'WP Favorites', 'edit_plugins', 'wp_favorites_options', 'wp_favorites_options');
}

function wp_favorites_options()
{
	global $wp_taxonomies;
	$favoritable_taxonomies = get_option('wp_favorites_taxonomies');
	if(!is_array($favoritable_taxonomies)) $favoritable_taxonomies = array();
?>
<div class="wrap">
<h2>WP Favorites <?php _e('Options'); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields('wp_favorites_settings'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Text or HTML to display if the user is not logged in. If blank, nothing will be displayed.'); ?></th>
				<td>
					<textarea cols='50' rows='20' name="wp_favorites_non_logged_in_html"><?php echo get_option('wp_favorites_non_logged_in_html'); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Should the multi term navigation return only posts which have all the selected terms, or all posts with any of the selected terms?'); ?></th>
				<td>
					<select name="wp_favorites_inclusive_search">
					<option value="TRUE"<?php if(get_option('wp_favorites_inclusive_search') == "TRUE") echo " selected='selected'"; ?>>All posts with any of the selected terms</option>
						<option value="FALSE"<?php if(get_option('wp_favorites_inclusive_search') == "FALSE") echo " selected='selected'"; ?>>Only posts with ALL the selected terms</option>
					</select>
				</td>
			</tr>
<?php /*
			<tr valign="top">
				<th scope="row"><?php _e('Select which taxonomies to allow favoriting for.'); ?></th>
				<td>
					<ul>
						<?php
						foreach($wp_taxonomies as $tax)
						{
							?>
						<li>
							<input type="checkbox" name="wp_favorites_taxonomies[]" value="<?php echo $tax->name; ?>"<?php if(in_array($tax->name, $favoritable_taxonomies)) echo " checked"; ?> />
							<?php echo $tax->name; ?>
						</li>
							<?php
						}
						?>
					</ul>
				</td>
			</tr>
 */ ?>
			<tr valign="top">
				<td colspan="2">
					<input type="submit" value="<?php _e('Save Settings'); ?>" />
				</td>
			</tr>
		</table>
	</form>
</div>
<?php
}

function wp_favorites_init()
{
	//register settings
	register_setting('wp_favorites_settings', 'wp_favorites_inclusive_search');
	register_setting('wp_favorites_settings', 'wp_favorites_non_logged_in_html');
	register_setting('wp_favorites_settings', 'wp_favorites_taxonomies');
}

