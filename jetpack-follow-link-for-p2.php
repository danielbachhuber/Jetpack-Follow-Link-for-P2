<?php
/*
Plugin Name: Jetpack Follow Link for P2
Plugin URI: https://github.com/danielbachhuber/Jetpack-Follow-Link-for-P2
Description: Easily subscribe to a P2 comment thread without commenting using a "Follow" action link like WordPress.com has
Author: Daniel Bachhuber
Version: 0.0
Author URI: http://danielbachhuber.com/
*/


class JPFLFP2 {

	const user_meta_key = 'jpflfp2_posts_following';

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
		if ( !class_exists( 'P2' ) || !class_exists( 'Jetpack_Subscriptions' ) ) {
			add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
			return;
		}

		// Only logged in users should be able to subscribe for now because we use the registered email address
		if ( !is_user_logged_in() )
			return;

		// Add the action link to P2
		add_action( 'p2_action_links', array( $this, 'display_follow_link' ) );

		// Handle AJAX actions but downgrade gracefully
		add_action( 'template_redirect', array( $this, 'handle_following_action' ) );

	}

	/**
	 * Display an error notice if P2 or Jetpack aren't present
	 */
	function action_admin_notices() {
		$message = sprintf( __( "Jetpack Follow Link for P2 is enabled. You'll also need to activate the <a href='%s' target='_blank'>P2 theme</a> and <a href='%s' target='_blank'>Jetpack</a> Subscriptions to start using the plugin.", 'jpflfp2' ), 'http://p2theme.com/', 'http://jetpack.me' );
		echo '<div class="error"><p>' . $message . '</p></div>';
	}

	/**
	 * Add a "Follow" action link to P2
	 */
	function display_follow_link() {
		
		$subscribed_ids = (array)get_user_meta( wp_get_current_user()->ID, self::user_meta_key, true );
		if ( !in_array( get_the_ID(), $subscribed_ids ) ) {
			$query_args = array(
				'post-id' => get_the_ID(),
				'action' => 'post-comment-follow',
			);
			$link = add_query_arg( $query_args, home_url() );
			$link = wp_nonce_url( $link, 'post-comment-subscriptions' );
			echo '| <a class="follow-link" href="' . esc_url( $link ) . '" title="' . esc_attr( __( 'Follow comments', 'jpflfp2' ) ) . '">' . esc_html( __( 'Follow', 'jpflfp2' ) ) . '</a>';
		} else {
			echo '| ' . __( 'Following', 'jpflfp2' );
		}
	}

	/**
	 * Handle the action of following or unfollowing a post
	 */
	function handle_following_action() {

		// Bail if the action isn't ours
		if ( !isset( $_GET['post-id'], $_GET['action'], $_GET['_wpnonce'] ) || !in_array( $_GET['action'], array( 'post-comment-follow', 'post-comment-unfollow' ) ) )
			return;
	
		$error = false;	
		$current_user = wp_get_current_user();
		$post_id = intval( $_GET['post-id'] );
	
		$post = get_post( $post_id );
		if ( !$post )
			$error = __( 'Invalid post', 'jpflfp2' );
	
		if ( !wp_verify_nonce( $_GET['_wpnonce'], 'post-comment-subscriptions' ) )
			$error = __( 'Nonce error', 'jpflfp2' );

		if ( !comments_open( $post_id ) )
			$error = __( "Comments aren't open on this post", 'jpflfp2' );
	
		// Make the action happen if no errors occurred
		$message = '';
		if ( !$error ) {
			switch( $_GET['action'] ) {
				case 'post-comment-follow':
					$response = Jetpack_Subscriptions::subscribe( $current_user->user_email, array( $post_id ), false );
					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message( $response );
						break;
					}
					$subscribed_ids = get_user_meta( $current_user->ID, self::user_meta_key, true );
					$subscribed_ids[] = $post_id;
					$subscribed_ids = array_unique( $subscribed_ids );
					update_user_meta( $current_user->ID, self::user_meta_key, $subscribed_ids );
					break;
				case 'post-comment-unfollow':
					// @todo There isn't yet an unsubscribe method in Jetpack
					break;
			}
			$message = '1';
		} else {
			$message = $error;
		}

		// Echo data if this was an AJAX request, otherwise redirect
		if ( isset( $_GET['ajax'] ) ) {
			echo $message;
		} else if ( '1' == $message ) {
			wp_safe_redirect( get_permalink( $post->ID ) );
		} else {
			wp_die( $message );
		}
		die;

	}

}

global $jpflfp2;
$jpflfp2 = new JPFLFP2();