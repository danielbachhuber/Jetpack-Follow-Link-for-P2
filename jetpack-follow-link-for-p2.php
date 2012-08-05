<?php
/*
Plugin Name: Jetpack Follow Link for P2
Plugin URI: https://github.com/danielbachhuber/Jetpack-Follow-Link-for-P2
Description: Add a nifty "Follow" action link to P2 like WordPress.com has. Allows you to easily subscribe to a comment thread without commenting
Author: Daniel Bachhuber
Version: 0.0
Author URI: http://danielbachhuber.com/
*/


class JPFLFP2 {

	/**
	 * Construct the plugin
	 */
	function __construct() {

		add_action( 'init', array( $this, 'action_init' ) );

	}

	/**
	 *
	 */
	function action_init() {

		// Check to make sure Jetpack and P2 are running
		// @todo Also check to make sure subscriptions are configured
		if ( !class_exists( 'P2' ) || !class_exists( 'Jetpack' ) ) {
			add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
			return;
		}

	}

	/**
	 * Display an error notice if P2 or Jetpack aren't present
	 */
	function action_admin_notices() {
		$message = sprintf( __( "Jetpack Follow Link for P2 is enabled. You'll also need to activate the <a href='%s' target='_blank'>P2 theme</a> and <a href='%s' target='_blank'>Jetpack</a> to start using the plugin.", 'jpflfp2' ), 'http://p2theme.com/', 'http://jetpack.me' );
		echo '<div class="error"><p>' . $message . '</p></div>';
	}

}

global $jpflfp2;
$jpflfp2 = new JPFLFP2();