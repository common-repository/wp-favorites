<?php
/*
 * Plugin Name: WP Favorites
 * Plugin URI: http://josephcarrington.com/software/
 * Description: WP Favorites allows your logged in users to favorite a tag, and allows them to see only posts that have that tag. As a bonus, they can favorite multiple tags, and see only posts that have all those tags. Example: You run a site about tea, iced and hot, green black and white. You tag every post green, black, or white, as well as iced or hot. Your user only likes iced green tea, so they favorite the 'iced' tag, and the 'green' tag. Then, they can see only posts pertaining to iced green tea. To use: just drop <code>if(function_exists('wp_favorites')) wp_favorites(); </code> into your template wherever you want the navigation to show up.
 * Version: 0.6
 * Author: Joseph Carrington
 * Author URI: http://josephcarrington.com/about/
 * License: GPL2
 */

// Create the global object
global $favorites;

// Include the favorites admin menu if we need it
if(is_admin()) include('wp_favorites_admin.php');
include('wp_favorites_class.php');


// Add our query variables to wordpress's accepted variables
add_filter('query_vars', 'wp_favorites_parameters');
function wp_favorites_parameters($qvars)
{
	// The thing wp_favorites will do
	$qvars[] = 'wp_favorites_action';
	// The taxonomy that our term is in
	$qvars[] = 'wp_favorites_tax';
	// The unique id of the term
	$qvars[] = 'wp_favorites_term_id';
	return $qvars;
}
// A custom router for changing the query_object for wp_favorites tasks as well as multiterm navigation, and finally removing the wp_favorites parameters from the query 
add_action('get_header','wp_custom_query');

/*
 * Takes request data from wp_favorites forms and links, and turns it into wordpress style multi-tag navigation, as well as controlling common wp_favorites tasks
 *
 * @return NULL
 * @uses wp_favorites
 */
function wp_custom_query()
{
	// Get the request URI to eventually remove the arguments from and redirect to
	$redirect = $_SERVER['REQUEST_URI'];

	// We route based on action
	switch(get_query_var('wp_favorites_action'))
	{
	case 'view_all':
		$favorites = new wp_favorites();
		if($favorites->has_favorites('post_tag'))
		{
			$favorites_array = $favorites->get_favorites('post_tag');
			$term_slugs = array();
			foreach($favorites_array as $favorite) $term_slugs[] = $favorite['slug'];

			if(get_option('wp_favorites_inclusive_search') == 'TRUE')
			{
				wp_redirect(add_query_arg('tag', implode(',', $term_slugs), get_bloginfo('url')));
				die();
			}
			else
			{
				wp_redirect(add_query_arg('tag', implode('+', $term_slugs), get_bloginfo('url')));
				die();
			}
		}
	break;
	case 'navigate':
		if(!is_null($_POST['wp_favorites_term_id']) && is_array($_POST['wp_favorites_term_id']))
		{
			$tax = get_query_var('wp_favorites_tax');
			$term_ids = $_POST['wp_favorites_term_id'];
			$term_slugs = array();
			foreach($term_ids as $term_id)
			{
				$term = get_term_by('id', intval($term_id), $tax);
				$term_slugs[] = $term->slug;
			}

			if(get_option('wp_favorites_inclusive_search') == 'TRUE')
			{
				wp_redirect(add_query_arg('tag', implode(',', $term_slugs), get_bloginfo('url')));
				die();
			}
			else
			{
				wp_redirect(add_query_arg('tag', implode('+', $term_slugs), get_bloginfo('url')));
				die();
			}
		}

		if(!isset($_GET['wp_favorites_term_id']))
		{
			wp_redirect(get_bloginfo('url'));
			die();
		}
	break;
	case 'add':
		// Get globals
		$favorites = new wp_favorites();
		$taxonomy = get_query_var('wp_favorites_tax');
		$term_id = get_query_var('wp_favorites_term_id');
		if($taxonomy != '' && $term_id != '')
		{
			$favorites->add_term(intval($term_id), $taxonomy);
			$favorites->store();
		}
		$redirect = remove_query_arg(array('wp_favorites_tax', 'wp_favorites_term_id', 'wp_favorites_action'), $redirect);
		wp_redirect($redirect);
		die();
	break;
	case 'remove':
		$favorites = new wp_favorites();
		$taxonomy = get_query_var('wp_favorites_tax');
		$term_id = get_query_var('wp_favorites_term_id');
		if($taxonomy != '' && $term_id != '')
		{
			$favorites->remove_term($term_id, $taxonomy);
			$favorites->store();
		}
		$redirect = remove_query_arg(array('wp_favorites_tax', 'wp_favorites_term_id', 'wp_favorites_action'), $redirect);
		wp_redirect($redirect);
		die();
	break;

	}
}

// Inserts wp_favorites' javascript into header
add_action('wp_head', 'wp_favorites_js');
function wp_favorites_js()
{
?>
<script type="text/javascript">
function wp_favorites_confirm(tag_name)
{
	var agree = confirm('Are you sure you want to remove ' + tag_name + ' from your favorites?');
	if(agree)
	{
		return true;
	}
	else
	{	
		return false;
	}
}
</script>
<?php
}

/**
 * Takes care of routing wp_favorites internal requests to the wP_favorites object, as well as generating multiterm navigation lists and other user viewable controls. Also the template tag.
 *
 * @uses wp_favorites
 * @uses wp_favorites_user_controls()
 */
function wp_favorites()
{
	global $wp_query;
	// We don't need to do any of this if user is not logged in.
	if(!is_user_logged_in())
	{
		$html = get_option('wp_favorites_non_logged_in_html');
		echo $html;
	}
	else
	{
		// Get globals, make wp_favorites object
		global $userdata;
		global $favorites;

		$favorites = new wp_favorites();
		
		// Run user controls, which allows user to add or remove favorites, as well as generating the multiterm navigation form, dependant on what page they are on
		wp_favorites_user_controls();
	}
}

/*
 * user controls, which allows user to add or remove favorites, as well as generating the multiterm navigation form, dependant on what page they are on
 *
 * @uses wp_favorites
*/
function wp_favorites_user_controls()
{
	global $favorites;
	global $wp_query;

	// I have streamlind the wp_favorites logic to only work with post_tags at present
	if(is_tag())
	{
		// TODO: use is_favorite() here instead


		if(count($wp_query->query_vars['tag_slug__in']) >= 1)
		{
			$tags = $wp_query->query_vars['tag_slug__in'];
		}
		if(count($wp_query->query_vars['tag_slug__and']) >= 1) 
		{
			$tags = $wp_query->query_vars['tag_slug__and'];
		}

		// In future versions, we could get the taxonomy here
		$current_taxonomy = 'post_tag';
		foreach($tags as $tag)
		{
			$current_term = get_term_by('slug', $tag, $current_taxonomy, 'ARRAY_A');
			$args = array(
				'taxonomy' => $current_taxonomy,
				'term_id' => $current_term['term_id']
			);
			// If we are on a tag archive, and that tag is not alrady one of our favorites, provide a link to favorite it
			if(is_null($favorites->search($args)))
			{
				$link =  "<a title='Add " . $current_term['name'] . " to my favorite tags' id='wp_favorites_add_link_" . $current_term['term_id'] . "' class='wp_favorites_add_link' href='";
				$link .= add_query_arg(array('wp_favorites_action' => 'add', 'wp_favorites_tax' => $current_taxonomy, 'wp_favorites_term_id' => $current_term['term_id']));
				$link .= "'>";
				$link .= "Add " . $current_term['name'] . " to my favorite tags.";
				$link .= "</a> <br />";
				echo $link;
			}
		}
	}

	// If we have any favorites, display the multi-term navigation form, as well as the option to visit any of the favorited tag's archives, as well as the option to remove the tag from our favorite
	if($favorites->has_favorites('post_tag'))
	{

		$list = "<div class='wp_favorites'>";
		$list .= "<ul>";
		$list .= "<form action='#' method='post'>";
		$favorites_array = $favorites->get_favorites('post_tag');

		foreach($favorites_array as $favorite)
		{
			$list .= "<li>";
			$list .= "<input type='checkbox' class='checkbox' name='wp_favorites_term_id[]' value='" . $favorite['term_id'] . "'";
			if(in_array($favorite['slug'], $wp_query->query_vars['tag_slug__in']) || in_array($favorite['slug'], $wp_query->query_vars['tag_slug__and'])) $list .= " checked='checked'";
			$list .= "></input>";
			$list .= "<a title='View posts in " . $favorite['name'] . "' class='wp_favorites_link' href='";
			$list .= add_query_arg('tag', $favorite['slug'], get_bloginfo('url'));
			$list .= "'>" . $favorite['name'] . " </a>";
			$list .= " ";
			$list .= "<a title= 'remove " . $favorite['name'] . " from favorites' class='wp_favorites_remove_link' href='";
			$list .= add_query_arg(array('wp_favorites_action' => 'remove', 'wp_favorites_tax' => 'post_tag', 'wp_favorites_term_id' => $favorite['term_id']));
			$list .= "'";
			$list .= " onclick=\"return wp_favorites_confirm('" . $favorite['name'] . "')\">";
			$list .= "[x]";
			$list .= "</a>";
			$list .= "</li>";
		}
		$list .= "<input type='hidden' name='wp_favorites_tax' value='post_tag' />";
		$list .= "<input type='hidden' name='wp_favorites_action' value='navigate' />";
		$list .= "<li><input type='submit' class='submit' value='search' /></li>";
		$list .= "</form>";
		$list .= "<li>";
		$list .= "<form>";
		$list .= "<input type='hidden' name='wp_favorites_action' value='view_all' />";
		$list .= "<input type='submit' class='view_all_submit' value='view all' />";
		$list .= "</form>";
		$list .= "</li>";
		$list .= "</ul>";
		$list .= "</div>";
		echo $list;
	}
}

register_activation_hook(__FILE__, 'wp_favorites_activate');
function wp_favorites_activate()
{
	$wp_favorites_version = '0.6';
	$default_non_logged_in_html = "Please <a href='" . get_bloginfo('url') . "/wp-login.php'>login</a> or <a href='" . get_bloginfo('url') . "/wp-login.php?action=register'>register</a> to view your favorites";
	$default_logged_in_no_faves_html = "You don't have any favorites.";

	update_option('wp_favorites_version', $wp_favorites_version);
	update_option('wp_favorites_non_logged_in_html', $default_non_logged_in_html);
	update_option('wp_favorites_logged_in_no_faves_html', $default_logged_in_no_faves_html);
}

// Load the widget
add_action('widgets_init', 'wp_favorites_load_widget');
function wp_favorites_load_widget()
{
	register_widget('wp_favorites_widget');
}

// Create the widget
class wp_favorites_widget extends WP_Widget
{
	function wp_favorites_widget()
	{
		parent::WP_Widget(false, $name = 'wp_favorites Widget');
	}

	function widget($args, $instance)
	{
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget; 
		if($title) echo $before_title . $title . $after_title; 
		if(function_exists('wp_favorites')) wp_favorites(); 
		echo $after_widget; 
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	function form($instance)
	{
		$title = esc_attr($instance['title']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<?php
	}
}

// New conditional tags to deal with the fact that WordPress Only displays the first tag as 'Browsing ____'  when browsing multiple tags
//
/*
 * returns true is either tag_slug__in or tag_slug__and contain more than one item, designating a multiterm request
 */
function is_multiterm()
{
	if(count($wp_query->query_vars['tag_slug__in']) > 1 || count($wp_query->query_vars['tag_slug__and']) > 1) return TRUE;
}
/*
 * returns true if all the tags currently being browsed are also favorited
 *
 * $uses is_multiterm
 */
function is_favorite()
{
	global $favorites;
	// get all the current tags
	if(count($wp_query->query_vars['tag_slug__in']) >= 1)
	{
		$tags = $wp_query->query_vars['tag_slug__in'];
	}
	if(count($wp_query->query_vars['tag_slug__and']) >= 1) 
	{
		$tags = $wp_query->query_vars['tag_slug__and'];
	}

	// In future versions, we could get the taxonomy here
	$current_taxonomy = 'post_tag';
	foreach($tags as $tag)
	{
		$current_term = get_term_by('slug', $tag, $current_taxonomy, 'ARRAY_A');
		$args = array(
			'taxonomy' => $current_taxonomy,
			'term_id' => $current_term['term_id']
		);

		if(is_null($favorites->search($args))) return;
	}
	return TRUE;
}
