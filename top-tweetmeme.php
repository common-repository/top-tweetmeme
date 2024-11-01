<?php
/*
 Plugin Name: Top Tweetmeme
 Plugin URI: http://www.franklinstrube.com/projects/top-tweetmeme
 Description: This plugin displays a list of the top posts on your blog that have been retweeted using Tweetmeme
 Version: 1.0
 Author: Franklin P. Strube
 Author URI: http://www.franklinstrube.com
 License: GPL2

 Copyright 2010  Franklin P. Strube  (email : franklin.strube@gmail.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

$top_tweetmeme_db_version = '1.0';

// (Un)installation hooks
register_activation_hook(__FILE__, 'top_tweetmeme_install');
register_deactivation_hook(__FILE__, 'top_tweetmeme_uninstall');

// Scheduled task
add_action('top_tweetmeme_update_event', 'top_tweetmeme_update');

// Add our function to the widgets_init hook.
add_action('widgets_init', 'top_tweetmeme_load_widgets');

// Function that registers our widget. 
function top_tweetmeme_load_widgets() {
	register_widget('Top_Tweetmeme_Widget');
}

// Test the updater
//add_action('wp', 'top_tweetmeme_update');


/**
 * Installs the top-tweetmeme database and schedules hourly updates
 */
function top_tweetmeme_install() {
	global $wpdb;
	global $top_tweetmeme_db_version;
	
	$table_name = $wpdb->prefix . 'top_tweetmeme';
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		
		$sql = "CREATE TABLE `$table_name` (
	    	`post_ID` bigint(20) UNSIGNED NOT NULL,
	    	`retweets` bigint(11) UNSIGNED DEFAULT '0' NOT NULL,
	  		UNIQUE KEY `post_ID` (`post_ID`)
		);";
		
		require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		add_option("top_tweetmeme_db_version", $top_tweetmeme_db_version);
	}
	wp_schedule_event(time(), 'hourly', 'top_tweetmeme_update_event');
}

/**
 * Remove the scheduled update
 */
function top_tweetmeme_uninstall() {
	wp_clear_scheduled_hook('top_tweetmeme_update_event');
}

/**
 * Loop through all posts and store the tweetmeme count in the database
 */
function top_tweetmeme_update() {
	/* @var $wpdb wpdb */
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'top_tweetmeme';
	
	// Get all articles
	$posts = get_posts(array('numberposts' => ''));
	foreach ( $posts as $post ) {
		$permalink = get_permalink($post->ID);
		$retweets = get_tweetmeme_count($permalink);
		
		// Update tweetmeme count in the top_tweetmeme database
		$wpdb->update($table_name, array('retweets' => $retweets), array('post_ID' => $post->ID)) || $wpdb->insert($table_name, array('post_ID' => $post->ID, 'retweets' => $retweets));
	}
}

/**
 * Untility function for use in themes. Returns an array of posts ordered by
 * the tweetmeme count.
 *
 * @param array $max an array of arguments
 */
function top_tweetmeme_posts($args = array()) {
	/* @var $wpdb wpdb */
	global $wpdb;
	$defaults = array('numberposts' => 5);
	$args = wp_parse_args((array) $args, $defaults);
	
	// Get all the post ids for the query
	$post_ids = array();
	$posts = get_posts(array_merge($args, array('numberposts' => '')));
	foreach ( $posts as $post ) {
		$post_ids[] = $post->ID;
	}
	$post_ids = implode(',', $post_ids);
	
	// Build the query to include the retweets
	$sql = "SELECT p.ID,t.retweets FROM `{$wpdb->prefix}top_tweetmeme` t 
			LEFT JOIN `{$wpdb->prefix}posts` p ON p.ID = t.post_ID
			WHERE p.ID IN ($post_ids)
			ORDER BY t.retweets DESC
			LIMIT {$args['numberposts']}";
	$results = $wpdb->get_results($sql, OBJECT);
	
	// Assemple an array of posts
	$top_posts = array();
	foreach ( $results as $result ) {
		$post = get_post($result->ID);
		$post->retweets = $result->retweets;
		$top_posts[] = $post;
	}
	
	return $top_posts;
}

/**
 * Query and return the number of retweets for the provided URL
 *
 * @param String	$url	The URL to be investigated
 */
function get_tweetmeme_count($url) {
	// get the number of retweets via the tweetmeme API
	$api_url = "http://api.tweetmeme.com/url_info.json?url=$url";
	
	// create a new cURL resource
	$ch = curl_init();
	
	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, $api_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// grab URL and pass it to the browser
	$response = curl_exec($ch);
	
	// close cURL resource, and free up system resources
	curl_close($ch);
	
	$tweetmeme = json_decode($response, true);
	
	$count = is_array($tweetmeme) && $tweetmeme['status'] != 'failure' ? $tweetmeme['story']['url_count'] : 0;
	
	return $count;
}

/**
 * 
 * Top Tweetmeme widget for easy addition to a theme's sidebars
 * @author Franklin P. Strube
 *
 */
class Top_Tweetmeme_Widget extends WP_Widget {
	/**
	 * 
	 * Initialize the widget
	 */
	function Top_Tweetmeme_Widget() {
		/* Widget settings. */
		$widget_ops = array('classname' => 'top-tweetmeme', 'description' => '');
		
		/* Widget control settings. */
		$control_ops = array('id_base' => 'top-tweetmeme');
		
		/* Create the widget. */
		$this->WP_Widget('top-tweetmeme', 'Top Tweetmeme Widget', $widget_ops, $control_ops);
	}
	
	/**
	 * 
	 * Render the widget on the page
	 * @see WP_Widget::widget()
	 */
	function widget($args, $instance) {
		extract($args);
		
		/* User-selected settings. */
		$title = apply_filters('widget_title', $instance['title']);
		$args = array();
		$args['category'] = is_array($instance['category']) ? implode(',', $instance['category']) : $instance['category'];
		if (is_numeric($instance['numberposts'])) {
			$args['numberposts'] = $instance['numberposts'];
		}
		
		/* Before widget (defined by themes). */
		echo $before_widget;
		
		/* Title of widget (before and after defined by themes). */
		if ($title) echo $before_title . $title . $after_title;
		
		/* Show the Top Tweetmeme posts */
		$top_posts = top_tweetmeme_posts($args);
		echo '<ul class="tweetmeme-top-posts">';
		foreach ( $top_posts as $post ) {
			echo '<li><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a> <span class="retweets">(' . $post->retweets . ' ' . ($post->retweets == 1 ? 'retweet' : 'retweets') . ')</span></li>';
		}
		echo '</ul>';
		
		/* After widget (defined by themes). */
		echo $after_widget;
	}
	
	/**
	 * Update the widget parameters
	 * @see WP_Widget::update()
	 */
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category'] = in_array(0,$new_instance['category']) ? 0 : implode(',',$new_instance['category']);
		$instance['numberposts'] = strip_tags($new_instance['numberposts']);
		
		return $instance;
	}
	
	/**
	 * Render the widget form in the admin
	 * @see WP_Widget::form()
	 */
	function form($instance) {
		$defaults = array('title' => 'Top Posts', 'categories' => '');
		$instance = wp_parse_args((array) $instance, $defaults);
		
		// Prepare the category select list
		$categories = get_categories();
		$selected_cats = explode(',',$instance['category']);
		$all_categories = empty($selected_cats) || in_array(0,$selected_cats);
		$options = '<option value="0" '.($all_categories ? 'selected="selected"' : '').'>(All Categories)</option>';
		foreach ( $categories as $category ) {
			$selected = $all_categories || in_array($category->cat_ID, $selected_cats);
			$options .= '<option value="' . $category->cat_ID . '" ' . ($selected ? 'selected="selected"' : '') . '>' . $category->name . '</option>';
		}
		
		// The form HTML
		echo <<<FORM
<p>
	<label for="{$this->get_field_id('title')}">Title:</label>
	<input class="widefat" id="{$this->get_field_id('title')}" 
			name="{$this->get_field_name('title')}" type="text"
			value="{$instance['title']}" />
</p>
<p>
	<label for="{$this->get_field_id('numberposts')}">Number of posts to show:</label>
	<input id="{$this->get_field_id('numberposts')}" size="4" 
			name="{$this->get_field_name('numberposts')}" type="text"
			value="{$instance['numberposts']}" />
</p>
<p>
	<label for="{$this->get_field_id('category')}">Category:</label>
	<select class="widefat" id="{$this->get_field_id('category')}"
			name="{$this->get_field_name('category][')}" size="8"
			multiple="multiple" style="height:8em">
		{$options}
	</select>
FORM;
	
	}
}
?>
