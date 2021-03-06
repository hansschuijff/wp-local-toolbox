<?php

if (defined('WPLT_SERVER') && WPLT_SERVER) {

	/**
	 * =======================================
	 * ===============Admin Bar===============
	 * =======================================
	 */
	function environment_notice() {
		$env_text = strtoupper(WPLT_SERVER);

		// add search engine visibility status
		
		if (defined('WPLT_ROBOTS') && WPLT_ROBOTS) {
			
			if (strtoupper(WPLT_ROBOTS) === 'NOINDEX') {
	            $env_text = $env_text . ' (NOINDEX)';
	    		
	    		// hide from robots
				add_filter('pre_option_blog_public', '__return_zero');
			}
	        
	        if (strtoupper(WPLT_ROBOTS) === 'INDEX') {
	        	$env_text = $env_text . ' (INDEX)';

	        	// force robots to play with me
	        	add_filter('pre_option_blog_public', '__return_true');
	        }
	    }

		$admin_notice = array(
			// puts it on the right side.
			'parent' => 'top-secondary',
			'id' => 'environment-notice',
			'title' => '<span>' . $env_text . '</span>',
		);
		global $wp_admin_bar;
		$wp_admin_bar->add_menu($admin_notice);
	}

	// Style the admin bar
	function environment_notice_css() {

		if (defined('WPLT_COLOR') && WPLT_COLOR) {
			$env_color = strtolower(WPLT_COLOR);
		} else {
			$env = strtoupper(WPLT_SERVER);

			if ($env == 'LIVE' or $env == 'PRODUCTION') {
				$env_color = 'red';

			} elseif ($env == 'STAGING' or $env == 'TESTING') {
				$env_color = '#FD9300';

			} elseif ($env == 'LOCAL' or $env == 'DEV') {
				$env_color = 'green';

			} else {
				$env_color = 'red';
			}

		}
		/**
		 * Some nice readable CSS so no one wonder's what's going on
		 * when inspecting the head. I think it's best to just jack
		 * these styles into the head and not bother loading another
		 * stylesheet.
		 */
		echo "
<!-- WPLT Admin Bar Notice -->
<style type='text/css'>
	#wp-admin-bar-environment-notice>div,
	#wpadminbar { background-color: $env_color!important }
	#wp-admin-bar-environment-notice { display: none }
	@media only screen and (min-width:1030px) {
	    #wp-admin-bar-environment-notice { display: block }
	    #wp-admin-bar-environment-notice>div>span {
	        color: #EEE!important;
	        font-size: 20px!important;
	    }
	}
	#wp-admin-bar-airplane-mode-toggle span.airplane-http-count {
	    position: relative;
	    display: inline-block;
	    width: 21px;
	    height: 21px;
	    line-height: 21px;
	    margin-left: 3px;
	    border-radius: 50%;
	    border: 2px solid #EEE;
	    text-align: center;
	}
	#adminbarsearch:before,
	.ab-icon:before,
	.ab-item:before { color: #EEE!important }
</style>";
	}

	// Literally cannot even
	function goodbye_howdy($wp_admin_bar) {
		if (is_user_logged_in()) {
			$my_account = $wp_admin_bar->get_node('my-account');
			$newtitle = str_replace('Howdy,', '', $my_account->title);
			$wp_admin_bar->add_node(array(
				'id' => 'my-account',
				'title' => $newtitle,
			));
		}
	}

	function wplt_server_init() {

		// Control the frontend admin bar
		if (defined('WPLT_ADMINBAR') && WPLT_ADMINBAR) {
			if (strtoupper(WPLT_ADMINBAR) == 'FALSE') {
				add_filter('show_admin_bar', '__return_false');
			} elseif (strtoupper(WPLT_ADMINBAR) == 'TRUE' or strtoupper(WPLT_ADMINBAR) == 'ALWAYS') {
				add_filter('show_admin_bar', '__return_true');
			}
			if (strtoupper(WPLT_ADMINBAR) == 'ALWAYS') {
				/**
				 * @author Jeff Star (https://twitter.com/perishable)
				 * @link http://digwp.com/2011/04/admin-bar-tricks/
				 */
				function always_show_adminbar($wp_admin_bar) {
					if (!is_user_logged_in()) {
						$wp_admin_bar->add_menu(array(
							'id'    => 'wpadminbar',
							'title' => __('Log In'),
							'href' => wp_login_url()
						));
					}
				}
				add_action('admin_bar_menu', 'always_show_adminbar');
				add_filter('show_admin_bar', '__return_true', 1000);
			}
		}

		if (is_admin_bar_showing()) {

			// Add the environment to the admin panel
			add_action('admin_bar_menu', 'environment_notice');

			// Add CSS to admin and wp head
			add_action('admin_head', 'environment_notice_css');
			add_action('wp_head', 'environment_notice_css');

			// Cannot. Even.
			add_filter('admin_bar_menu', 'goodbye_howdy', 25);
		}
	}
	add_action('init', 'wplt_server_init');
} else {
	// if WPLT_SERVER is undefined
	function welcome_to_wplt() {
    ?>
<div class="notice notice-info is-dismissible">
    <h2><?php _e( 'Thanks for trying WP Local Toolbox', 'wp-local-toolbox' ); ?></h2>
    <p><?php _e( "WP Local Toolbox is configured with constants added to your wp-config.php file.", 'wp-local-toolbox' ); ?></p>
    <p><?php _e( "Read more: ", 'wp-local-toolbox' ); ?><a href="https://github.com/joeguilmette/wp-local-toolbox" style="top:0">https://github.com/joeguilmette/wp-local-toolbox</a></p>
</div>
<?php
	}
	// yuck
	// this fires for every user on every pageload, what was I thinking
	// add_action( 'admin_notices', 'welcome_to_wplt' );
}

/**
 * =======================================
 * =============Notifications=============
 * =======================================
 */

if (defined('WPLT_NOTIFY') && WPLT_NOTIFY) {

	// Send notification when a post status changes
	add_action( 'transition_post_status', 'notify_on_post_update', 10, 3 );

	// Send a notification for new attachments, unless we don't want to
	if (defined('WPLT_DISABLE_ATTACHMENT_NOTIFY') && WPLT_DISABLE_ATTACHMENT_NOTIFY) {
		return;
	} else {
		add_action( 'add_attachment','notify_on_attachment_update', 1, 1);
	}

	// The add_attachment hook doesn't return the post status, so we'll just
	// assume that it's a new attachment and off we go.
	function notify_on_attachment_update($post_id) {
		notify_on_post_update('publish','new',$post_id);
	}

	// The mean of WPLT_NOTIFY. This will check to see if we've set a Slack API
	// key, and if not, we'll assume we've set an email address.
	function notify_on_post_update($new_status, $old_status, $post_id) {

		// Not a post revision
		if (wp_is_post_revision($post_id)) {
			return;
		}

		// And only if it's published
		if (get_post_status($post_id) == 'publish') {

			// Only look for the author if it's not an attachment, and only
			// tell us about the author if he has a name.
			if (get_post_type( $post_id ) != 'attachment' ) {
				if (get_the_modified_author($post_id) != null) {
					$author = " by " . get_the_modified_author($post_id);
				}
			} else {
				$author = null;
			}

			// Some useful variables for building our messages.
			$post_title = get_the_title( $post_id );
			$post_url = get_permalink( $post_id );
			$post_type = get_post_type( $post_id );
			$post_type = get_post_type_object( $post_type );
			$post_type = ucwords($post_type->labels->singular_name);

			// Building the subject and body depending on whether this is a new post or not.
			if (is_new_post($new_status,$old_status)) {
				$title = "New " . $post_type;
				$short_message = "New " . $post_type . " Added" . ucwords($author);
				$email_body = "A new " . strtolower($post_type) . ", '" . $post_title . "' (" . $post_url . "), has been published" . $author . ".";
			} else {
				$title = "Updated " . $post_type;
				$short_message = $post_type . " Updated" . ucwords($author);
				$email_body = "The " . strtolower($post_type) . " '" . $post_title . "' (" . $post_url . ") has been updated" . $author . ".";
			}

			$subject = get_bloginfo('name') . ': ' . $short_message;

			// Check if it's a Slack Webhook URL
			if (strpos(WPLT_NOTIFY,'hooks.slack.com') !== false) {

				// Check if we've defined a Slack channel
				$bot_args = array(
					'attachments' => array(
						0 => array(
							'fallback' => $short_message,
							'color' => '#F40101',
							'author_name' => $short_message,
							'author_link' => $post_url,
							'title' => $post_title,
							'title_link' => $post_url,
							'text' => $post_url,
						),
					),
					'icon_emoji' => ':triangular_flag_on_post:',
					'username' => get_bloginfo( 'name' ),
					'unfurl_links' => true,
				);

				// Check if we've defined a Slack channel. If we have, slip the
				// channel into our Slack payload.
				if (defined('WPLT_NOTIFY_CHANNEL') && WPLT_NOTIFY_CHANNEL) {
					$bot_args['channel'] = WPLT_NOTIFY_CHANNEL;
				}

				$payload = array( 'payload' => json_encode( $bot_args ) );

				// Send the payload to Slack.
				$posting = wp_remote_post( WPLT_NOTIFY, array( 'body' => $payload ) );

			// If it's not a Slack Webhook URL, send an email instead.
			} else {

				wp_mail(WPLT_NOTIFY, $subject, $email_body);

			}
		} // end if (get_post_status($post_id) == 'publish')
	} // end function notify_on_post_update

	// Detect if this is a new post or not.
	function is_new_post( $new_status, $old_status ) {
		$published = false;
		if ( $new_status === 'publish' && $old_status !== 'publish' ) {
			$published = true;
		}
		return $published;
	}
}

/**
 * =======================================
 * =============Airplane Mode=============
 * =======================================
 */
if (defined('WPLT_AIRPLANE') && WPLT_AIRPLANE) {

	require_once __DIR__ . '/lib/airplane-mode/airplane-mode.php';

	// load our own CSS
	add_action( 'wp_enqueue_scripts', 'wplt_airplane_css', 99999 );
	add_action( 'admin_enqueue_scripts', 'wplt_airplane_css', 99999 );

	function wplt_airplane_css() {
		if (is_admin_bar_showing()) {
			wp_dequeue_style( 'airplane-mode' );

			/**
			 * Some nice readable CSS so no one wonder's what's going on
			 * when inspecting the head. I think it's best to just jack
			 * these styles into the head and not bother loading another
			 * stylesheet.
			 */
			echo "
<!-- WPLT Airplane Mode -->
<style type='text/css'>
#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon { padding-right: 3px }
#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon-on:before { content: '✓' }
#wp-admin-bar-airplane-mode-toggle span.airplane-toggle-icon-off:before { content: '✗' }
.airplane-mode-enabled .plugin-install-php a.upload.add-new-h2,.airplane-mode-enabled .theme-browser.content-filterable.rendered,.airplane-mode-enabled .wp-filter,.airplane-mode-enabled a.browse-themes.add-new-h2{display:none!important}
</style>";
		}
	}
}

/**
 * =======================================
 * ===========Disabled Plugins============
 * =======================================
 */
if (defined('WPLT_DISABLED_PLUGINS') && WPLT_DISABLED_PLUGINS) {

	require_once __DIR__ . '/inc/WPLT_Disable_Plugins.php';
	new WPLT_Disable_Plugins(unserialize(WPLT_DISABLED_PLUGINS));
}

/**
 * =======================================
 * =========Media from Production=========
 * =======================================
 */
if ( defined( 'WPLT_MEDIA_FROM_PROD_URL' ) && WPLT_MEDIA_FROM_PROD_URL ) {
	// Require Bill Erickson's Media from Production plugin 
	require_once __DIR__ . '/lib/BE-Media-from-Production/be-media-from-production.php';
	// MEDIA URL
	add_filter( 'be_media_from_production_url', function( $url) { return WPLT_MEDIA_FROM_PROD_URL; } );
	// Start Month
	if ( defined( 'WPLT_MEDIA_FROM_PROD_START_MONTH' ) && WPLT_MEDIA_FROM_PROD_START_MONTH ) {
		add_filter( 'be_media_from_production_start_month', function( $month) { return PLT_MEDIA_FROM_PROD_START_MONTH; } );
	}
	// Start Year
	if ( defined( 'WPLT_MEDIA_FROM_PROD_START_YEAR' ) && WPLT_MEDIA_FROM_PROD_START_YEAR ) {
		add_filter( 'be_media_from_production_start_year', function( $year) { return WPLT_MEDIA_FROM_PROD_START_YEAR; } );
	// Array of Directories
	}
	if ( defined( 'WPLT_MEDIA_FROM_PROD_DIRECTORIES' ) && WPLT_MEDIA_FROM_PROD_DIRECTORIES ) {
		add_filter( 'be_media_from_production_url', function( $directories) { return WPLT_MEDIA_FROM_PROD_DIRECTORIES;	} );
	}
}
