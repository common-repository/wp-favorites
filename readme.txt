=== wp_favorites ===
Contributors: mrzerog
Donate link: http://www.josephcarrington.com/donate
Tags: Favorites, Favorite, Tags
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

wp_favorites allows a logged in user to favorite a tag or tags and then show only posts that have that tag or a combination of those tags

== Description ==
WP Favorites allows your logged in users to favorite a tag, and allows them to see only posts that have that tag. They can also favorite multiple tags, and see only posts that have all those tags (or optionally any post with any of those tags). Example: You run a site about tea, iced and hot, green black and white. You tag every post green, black, or white, as well as iced or hot. Your user only likes iced green tea, so they favorite the 'iced' tag, and the 'green' tag. Then, they can see only posts pertaining to iced green tea (or optionally any post's tagged either iced or green). Users are able to favorite a tag from it's archive page.

== Installation ==
1. Install wp_favorites from the plugin page, or upload it, whatever.
2. Activate it
3. You can drop the widget into any of your sidebars if your theme is widget compatible, other wise drop `if(function_exists('wp_favorites')) wp_favorites();` into your template wherever you want the navigation to show up.

== Changelog ==
= 0.6 =
* Added custom html option for what to display if a user is logged in but has no favorites
* Added two conditional tags: *is_favorite()*, which returns true if all the tags currently being browsed are favorited, and *is_multiterm()*, which returns true if there are multiple tags being browsed concurrently, such as example.com/?tag=tag1,tag-2
= 0.5.3 =
* Added the ability to use an inclusive or an exclusive search. That is: let your users search for posts that contain ALL selected tags (exclusive), or let them search for ALL posts that contain any selected tags (inclusive).
* Added the 'View All' button, which selects all the favorites.
== Screenshots ==
1. Basic stying
