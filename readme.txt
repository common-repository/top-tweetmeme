=== Plugin Name ===
Contributors: fstrube
Donate link: http://www.franklinstrube.com/
Tags: twitter, tweetmeme
Requires at least: 2.9.2
Tested up to: 2.9.2
Stable tag: 1.0

This plugin displays a list of the top posts on your blog that have been 
retweeted using Tweetmeme.

== Description ==

The Top Tweetmeme plugin for WordPress ranks articles based on the number of
retweets they receive through the Tweetmeme service. It comes with both a 
widget for use with sidebar-enabled themes, and a utility function to use as you wish.

Tweetmeme counts are updated by a scheduled task on an hourly basis.

== Installation ==

1. Upload `top-tweetmeme.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to the Widgets page in the admin and drag the 'Top Tweetmeme Widget' to the desired sidebar
1. Place `<?php echo top_tweetmeme_posts() ?>` in your templates

== Frequently Asked Questions ==

= Why arent't there any questions here? =

Because nobody has asked yet!

== Screenshots ==

1. The Top Tweetmeme sidebar widget

== Changelog ==

= 1.0 =
* Added support for widgetized themes
* Setup scheduled task to update tweetmeme counters

= 0.1 =
* Initial version

== Upgrade Notice ==

= 1.0 =
New stable release! Now with support for widgetized themes

= 0.1 =
Initial version