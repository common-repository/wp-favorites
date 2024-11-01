<?php
// Class for interacting with wp_favorites objects
class wp_favorites
{
	private $favorites = array();
	function __construct()
	{
		global $userdata;

		// The wp_favorites usermeta stores the term id and the taxonomy of favorited terms
		if(version_compare(get_bloginfo('version'), '3.0', '<')) $favorites = maybe_unserialize(get_usermeta($userdata->ID, 'wp_favorites'));
		else $favorites = maybe_unserialize(get_user_meta($userdata->ID, 'wp_favorites', true));
 
		// If there are any favorites to use:
		if(is_array($favorites) && count($favorites) > 0)
		{
			foreach($favorites as $favorite)
			{
				// If either of the fiends are not set, skip this favorite
				if(!isset($favorite['taxonomy']) || !isset($favorite['term_id'])) continue;

				$wp_term = get_term($favorite['term_id'], $favorite['taxonomy'], 'ARRAY_A');
				if($wp_term) $this->favorites[] = $wp_term;
			}
		}
	}
	/**
	 * get the favorites of a particular taxonomy as an associative array
	 *
	 * @parameter string $taxonomy specific taxonomy to retrieve favorites of
	 * @return array $favorites_out array of favorites in the supplied taxonomy
	 */
	function get_favorites($taxonomy)
	{
		$favorites_out = array();
		foreach($this->favorites as $favorite)
		{
			if($favorite['taxonomy'] == $taxonomy) $favorites_out[] = $favorite;
		}
		return $favorites_out;
	}
	/**
	 * Store the favorites' term id and taxonomy to the db as associatine array
	 * 
	 * @return NULL	
	 */
	function store()
	{
		global $userdata;
		$favorites_to_store = array();
		foreach($this->favorites as $favorite) 
		{
			$favorites_to_store[] = array('term_id' => $favorite['term_id'], 'taxonomy' => $favorite['taxonomy']);
		}
		if(version_compare(get_bloginfo('version'), '3.0', '<')) update_usermeta($userdata->ID, 'wp_favorites', $favorites_to_store);
		else update_user_meta($userdata->ID, 'wp_favorites', $favorites_to_store); 



		// Refresh the local favorites, so that they accurately represent the stored favorites
		$this->favorites = array();
		$this->__construct();
	}
	/**
	 * Search favorites for key / value array 
	 *
	 * @return $array_id of first favorite with matching criteria, otherwise FALSE
	 * @parameter array $array_values array of key / value pairs to search for
	 */
	function search($array_values)
	{
		foreach($this->favorites as $array_id => $term_data)
		{
			// perform an associative array intersection between the supplied parameter array and the current favorite's data
			$result_array = array_intersect_assoc($term_data, $array_values);
			// if the resulting array has all the keys that were present in the supplied parameter array, a match has been found
			if(count(array_diff($array_values, $result_array)) == 0)
			{
				// return the array_id of the matching favorite
				return $array_id;
			}
		}
	}
	/**
	 * Searches favorites for term_id / taxonomy combo, if not in array, adds the wp_term 
	 *
	 * @return NULL
	 * @parameter string $term_id term_id to add
	 * @parameter string $taxonomy taxonomy of term to add
	 * @uses $this->search()
	 */
	function add_term($term_id, $taxonomy)
	{
		if(!$this->search(array('term_id' => $term_id, 'taxonomy' => $taxonomy)))
		{
			$this->favorites[] = get_term($term_id, $taxonomy, 'ARRAY_A');
		}
	}
	/**
	 * Remove specified id from favorites
	 *
	 * @return NULL
	 * @parameter string $term_id term_id to remove
	 * @parameter string $taxonomy taxonomy of term to remove
	 * @uses $this->search()
	 */
	function remove_term($term_id, $taxonomy)
	{
		$key_to_remove = $this->search(array('term_id' => $term_id, 'taxonomy' => $taxonomy));
		if(!is_null($key_to_remove)) unset($this->favorites[$key_to_remove]);
	}
	/**
	 * Check if there are any favorites of a specific taxonomy to display
	 *
	 * @parameter string $specific_tax specific taxonomy to chack against 
	 * @return bool TRUE if there are favorites to display, otherwise NULL
	 */
	function has_favorites($taxonomy)
	{
		if(!is_null($this->search(array('taxonomy' => $taxonomy)))) return TRUE;
	}
}
