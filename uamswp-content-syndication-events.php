<?php
/*
Plugin Name: UAMSWP Content Syndication Events from News
Plugin URI: -
Description: Retrieve events for display from news.uams.edu.
Author: uams, Todd McKee, MEd
Author URI: https://web.uams.edu/
Version: 1.0.0
*/

namespace UAMS\ContentSyndicate\Events;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', 'UAMS\ContentSyndicate\Events\activate_shortcodes' );
/**
 * Activates the uamswp_events shortcode.
 *
 * @since 1.0.0
 */
function activate_shortcodes() {
	include_once( dirname( __FILE__ ) . '/includes/class-uams-syndication-shortcode-events.php' );

	/// Add the [uamswp_events] shortcode to pull calendar events.
	new \UAMS_Syndicate_Shortcode_Events();

}
