<?php
if ( !class_exists( 'RPR_Activate' ) ) {
	class RPR_Activate {
		function __construct() {
			add_filter( 'random_password', array( $this, 'rpr_activate_filter_random_password' ), 10, 1 ); // Replace random password with user set password
			add_filter( 'wpmu_welcome_user_notification', array( $this, 'rpr_filter_wpmu_welcome_user_notification' ), 10, 3 );
			add_action( 'wpmu_activate_user', array( $this, 'rpr_wpmu_activate_user' ), 10, 3 ); // Restore metadata to activated user's profile
			//add_action( 'wpmu_activate_blog', array( $this, 'rpr_wpmu_activate_blog' ), 10, 5 );
		}

		function rpr_activate_filter_random_password( $password ) {
			global $register_plus_redux;
			global $pagenow;
			if ( $pagenow == 'wp-activate.php' && $register_plus_redux->rpr_get_option( 'user_set_password' ) == TRUE ) {
				$key = isset( $_POST['key'] ) ? $_POST['key'] : isset( $_GET['key'] ) ? $_GET['key'] : '';
				if ( !empty( $key ) ) {
					global $wpdb;
					$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s;", $key ) );
					if ( !empty( $signup ) ) {
						$meta = unserialize( $signup->meta );
						if ( is_array( $meta ) && array_key_exists( 'password', $meta ) && !empty( $meta['password'] ) ) {
							$password = $meta['password'];
							unset( $meta['password'] );
							$meta = serialize( $meta );
							$wpdb->update( $wpdb->signups, array( 'meta' => $meta ), array( 'activation_key' => $key ) );
						}
					}
				}
			}
			return $password;
		}

		function rpr_filter_wpmu_welcome_user_notification( $user_id, $password, $meta ) {
			global $register_plus_redux;
			if ( $register_plus_redux->rpr_get_option( 'disable_user_message_registered' ) == TRUE ) return FALSE;
			else return TRUE;
		}

		function rpr_wpmu_activate_user( $user_id, $password, $meta ) {
			global $register_plus_redux;

			//TODO: Not the most elegant solution, it would be better to interupt the activation and keep the data in the signups table with a flag to alert admin to complete activation			
			if ( $register_plus_redux->rpr_get_option( 'verify_user_admin' ) == TRUE ) {
				global $wpdb;
				$user_info = get_userdata( $user_id );
				update_user_meta( $user_id, 'stored_user_login', sanitize_text_field( $user_info->user_login ) );
				update_user_meta( $user_id, 'stored_user_password', sanitize_text_field( $plaintext_pass ) );
				$temp_user_login = 'unverified_' . wp_generate_password( 7, FALSE );
				$wpdb->update( $wpdb->users, array( 'user_login' => $temp_user_login ), array( 'ID' => $user_id ) );
			}

			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'first_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['first_name'] ) ) update_user_meta( $user_id, 'first_name', sanitize_text_field( $meta['first_name'] ) );
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'last_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['last_name'] ) ) update_user_meta( $user_id, 'last_name', sanitize_text_field( $meta['last_name'] ) );
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'url', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['user_url'] ) ) {
				$user_url = esc_url_raw( $meta['user_url'] );
				$user_url = preg_match( '/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $user_url ) ? $user_url : 'http://' . $user_url;
				// HACK: update_user_meta does not allow update of user_url
				wp_update_user( array( 'ID' => $user_id, 'user_url' => sanitize_text_field( $user_url ) ) );
			}
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'aim', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['aim'] ) ) update_user_meta( $user_id, 'aim', sanitize_text_field( $meta['aim'] ) );
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'yahoo', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['yahoo'] ) ) update_user_meta( $user_id, 'yim', sanitize_text_field( $meta['yahoo'] ) );
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'jabber', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['jabber'] ) ) update_user_meta( $user_id, 'jabber', sanitize_text_field( $meta['jabber'] ) );
			if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !empty( $meta['description'] ) ) update_user_meta( $user_id, 'description', wp_filter_kses( $meta['description'] ) );

			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( current_user_can( 'edit_users' ) || !empty( $meta_field['show_on_registration'] ) ) {
					if ( !empty( $meta[$meta_field['meta_key']] ) ) $register_plus_redux->rpr_update_user_meta( $user_id, $meta_field, $meta[$meta_field['meta_key']] );
				}
			}

			if ( $register_plus_redux->rpr_get_option( 'enable_invitation_code' ) == TRUE && !empty( $meta['invitation_code'] ) ) update_user_meta( $user_id, 'invitation_code', sanitize_text_field( $meta['invitation_code'] ) );

			/* filter_random_password replaces the random password with the password stored in meta
			if ( $register_plus_redux->rpr_get_option( 'user_set_password' ) == TRUE && !empty( $meta['password'] ) ) {
				$password = sanitize_text_field( $meta['password'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $password, $user_id );
			}
			*/

			// TODO: Verify autologin works
			if ( $register_plus_redux->rpr_get_option( 'autologin_user' ) == TRUE && $register_plus_redux->rpr_get_option( 'verify_user_admin' ) == FALSE ) {
				$user_info = get_userdata( $user_id );
				$credentials['user_login'] = $user_info->user_login;
				$credentials['user_password'] = $password;
				$credentials['remember'] = FALSE;
				//$user = wp_signon( $credentials, FALSE ); 
				//wp_redirect( admin_url() );
				//exit();
			}
		}

		function rpr_wpmu_activate_blog( $blog_id, $user_id, $password, $signup, $meta ) {
			$this->rpr_wpmu_activate_user( $user_id, $password, $meta );
		}
	}
}

if ( class_exists( 'RPR_Activate' ) ) $rpr_activate = new RPR_Activate();
?>