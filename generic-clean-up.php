<?php
/**
 * General Wordpress cleanup and security improvements.
 *
 * Below are functions, filters and hooks that I include into most Wordpress websites that I develop to reduce bloat and
 * improve security. To make this work simply include this file into functions.php
 *
 * @link https://github.com/robert-kampas/generic-wordpress-cleanup
 * @version 1.0
 */

/**
 * Generates a hash value using the contents of a given file to be used as file version for cache busting.
 *
 * This function assumes that files passed to it are kept in themes/your-theme/assets/optimised/
 *
 * @param string $file full file name
 *
 * @return string
 */
function theme_asset_version($file = null) {
    return hash_file('md5', get_template_directory().'/assets/optimised/'.$file);
}

/**
 * Removing wp-embed.min.js script from frontend templates as its not really needed
 */
add_action('wp_footer', function() { wp_deregister_script('wp-embed'); } );

/**
 * Removing everything to do with emojis from frontend and backend.
 *
 * @return void
 */
function rkwp_cleanup_disable_wp_emojicons() {
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	add_filter('emoji_svg_url', '__return_false');
}
add_action('init', 'rkwp_cleanup_disable_wp_emojicons');

/**
 * Removing redundant meta tags to improve security and page speed. More info here:
 * http://cubiq.org/clean-up-and-optimize-wordpress-for-your-next-theme
 *
 * @return void
 */
function rkwp_cleanup_removing_meta_tags_and_inline_stuff() {
	remove_action('wp_head', 'wp_generator'); // No need to  expose my WordPress version to everyone
	remove_action('wp_head', 'wlwmanifest_link'); // Nobody is using Windows Live Writer
	remove_action('wp_head', 'rsd_link'); // Users will not be allowed to edit posts from external services
	remove_action('wp_head', 'wp_shortlink_wp_head'); // No need to print shortlink in the head
	remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10); // Removes a link to the next and previous post from the document header
	remove_action('wp_head', 'rest_output_link_wp_head', 10); // Disable REST API link tag
	remove_action('wp_head', 'wp_oembed_add_discovery_links', 10); // Disable oEmbed Discovery Links
	remove_action('template_redirect', 'rest_output_link_header', 11, 0); // Disable REST API link in HTTP headers
    remove_action('wp_head', 'feed_links', 2); // No need to list feeds
	remove_action('wp_head', 'feed_links_extra', 3); // This website will not need RSS feeds
	add_filter('the_generator', '__return_false'); // Removes the generator name from the RSS feeds.
}
add_action('after_setup_theme', 'rkwp_cleanup_removing_meta_tags_and_inline_stuff');

/**
 * Feeds are disabled therefore when accessing any feed page user is redirected to 404 page instead.
 *
 * @return void
 */
function rkwp_cleanup_disable_feed_page() {
    global $wp_query;

    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    require get_404_template();

    exit;
}
add_action('do_feed', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_rdf', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_rss', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_rss2', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_atom', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_rss2_comments', 'rkwp_cleanup_disable_feed_page', 1);
add_action('do_feed_atom_comments', 'rkwp_cleanup_disable_feed_page', 1);

/**
 * Disable xmlrpc.php because WordPress API will not be used.
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove comments column from pages and posts listing page.
 *
 * @return void
 */
function rkwp_cleanup_hide_comments_column() {
	add_filter('manage_pages_columns', function( $columns ) { unset($columns['comments']); return $columns; } );
	add_filter('manage_posts_columns', function( $columns ) { unset($columns['comments']); return $columns; } );
}
add_action('admin_init', 'rkwp_cleanup_hide_comments_column');

/**
 * Remove comments menu icon from admin bar.
 */
add_action('wp_before_admin_bar_render', function() { global $wp_admin_bar; $wp_admin_bar->remove_menu('comments'); } );

/**
 * Remove comments menu item from admin menu.
 */
add_action('admin_menu', function() { remove_menu_page('edit-comments.php'); } );

/**
 * Disable comments support for all post types and pages.
 *
 * @return void
 */
function rkwp_cleanup_disable_comments_support() {
	$post_types = get_post_types();
	
	foreach ($post_types as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
		}
	}
}
add_action('admin_init', 'rkwp_cleanup_disable_comments_support');

/**
 * Disable comments metaboxes for all post types and pages.
 *
 * @return void
 */
function rkwp_cleanup_hide_comments_metaboxes() {
    remove_meta_box('commentsdiv', 'page', 'normal');
    remove_meta_box('commentstatusdiv', 'page', 'normal');
    remove_meta_box('commentsdiv', 'post', 'normal');
    remove_meta_box('commentstatusdiv', 'post', 'normal');
}
add_action('admin_head', 'rkwp_cleanup_hide_comments_metaboxes');

/**
 * Disable comments support on frontend
 */
add_filter('comments_open', function() { return false; }, 20, 2);

/**
 * Remove recent comments inline style.
 *
 *  @return void
 */
function rkwp_cleanup_remove_recent_comments_style() {
    global $wp_widget_factory;

    remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
}
add_action('widgets_init', 'rkwp_cleanup_remove_recent_comments_style');

/**
 * Hide existing comments
 */
add_filter( 'comments_array', '__return_empty_array', 10, 2);

/**
 * Redirect any user trying to access comments page.
 *
 *  @return void
 */
function rkwp_cleanup_disable_comments_admin_menu_redirect() {
	global $pagenow;

	if ($pagenow === 'edit-comments.php') {
		wp_redirect(admin_url());

		exit;
	}
}
add_action('admin_init', 'rkwp_cleanup_disable_comments_admin_menu_redirect');

/**
 * Disable metaboxes on Dashboard page.
 *
 *  @return void
 */
function rkwp_cleanup_disable_dashboard_metaboxes() {
    global $wp_meta_boxes;

    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
}
add_action('admin_head', 'rkwp_cleanup_disable_dashboard_metaboxes');

/**
 * Disable admin footer text.
 */
add_filter('admin_footer_text', '__return_null');

/**
 * Disable Welcome metabox on Dashboard page.
 */
remove_action('welcome_panel', 'wp_welcome_panel');

/**
 * Disable help tabs.
 */
function rkwp_cleanup_remove_help_tab($old_help, $screen_id, $screen){
    $screen->remove_help_tabs();

    return $old_help;
}
add_filter('contextual_help', 'rkwp_cleanup_remove_help_tab', 999, 3);

/**
 * Disable "Quick Edit" functionality.
 */
add_filter('page_row_actions', function( $actions ) { unset($actions['inline hide-if-no-js']); return $actions; }, 10, 1);
add_filter('post_row_actions', function( $actions ) { unset($actions['inline hide-if-no-js']); return $actions; }, 10, 1);
