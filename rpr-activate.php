<?php
if ( !class_exists( 'RPR_Activate' ) ) {
	class RPR_Activate {
		function __construct() {
			add_action( 'wpmu_activate_user', array( $this, 'rpr_restore_signup_fields' ), 10, 3 ); // Restore metadata to activated user's profile
			//add_action( 'wpmu_activate_blog', array( $this, 'rpr_restore_signup_fields_stub' ), 10, 5 );
		}

		function rpr_restore_signup_fields( $user_id, $password, $meta ) {
			global $register_plus_redux;
			global $pagenow;

			$source = $meta;
			echo $user_id, ', ', $password, ', ', print_r( $meta );
			
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['first_name'] ) ) update_user_meta( $user_id, 'first_name', sanitize_text_field( $source['first_name'] ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['last_name'] ) ) update_user_meta( $user_id, 'last_name', sanitize_text_field( $source['last_name'] ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'url', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['user_url'] ) ) {
				$user_url = esc_url_raw( $source['user_url'] );
				$user_url = preg_match( '/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $user_url ) ? $user_url : 'http://' . $user_url;
				// HACK: update_user_meta does not allow update of user_url
				wp_update_user( array( 'ID' => $user_id, 'user_url' => sanitize_text_field( $user_url ) ) );
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['aim'] ) ) update_user_meta( $user_id, 'aim', sanitize_text_field( $source['aim'] ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['yahoo'] ) ) update_user_meta( $user_id, 'yim', sanitize_text_field( $source['yahoo'] ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['jabber'] ) ) update_user_meta( $user_id, 'jabber', sanitize_text_field( $source['jabber'] ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'show_fields' ) ) && !empty( $source['description'] ) ) update_user_meta( $user_id, 'description', wp_filter_kses( $source['description'] ) );

			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( current_user_can( 'edit_users' ) || !empty( $meta_field['show_on_registration'] ) ) {
					$register_plus_redux->SaveMetaField( $meta_field, $user_id, $source[$meta_field['meta_key']] );
				}
			}

			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE && !empty( $source['invitation_code'] ) ) update_user_meta( $user_id, 'invitation_code', sanitize_text_field( $source['invitation_code'] ) );

			// TODO: Verify autologin works
			if ( $pagenow != 'user-new.php' && $register_plus_redux->GetReduxOption( 'autologin_user' ) == TRUE && $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == FALSE ) {
				$user_info = get_userdata( $user_id );
				$credentials['user_login'] = sanitize_text_field( $user_info->user_login );
				if ( empty( $_POST['pass1'] ) ) {
					$plaintext_pass = wp_generate_password();
					update_user_option( $user_id, 'default_password_nag', TRUE, TRUE );
					wp_set_password( $plaintext_pass, $user_id );
					$credentials['user_password'] = $plaintext_pass;
					if ( $register_plus_redux->GetReduxOption( 'disable_user_message_registered' ) == FALSE )
						$register_plus_redux->sendUserMessage( $user_id, $plaintext_pass );
				}
				else {
					$credentials['user_password'] = sanitize_text_field( $_POST['pass1'] );
				}
				$credentials['remember'] = FALSE;
				$user = wp_signon( $credentials, FALSE ); 
			}

			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE && !empty( $source['pass1'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['pass1'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}

			if ( ( $pagenow == 'user-new.php' ) && !empty( $source['pass1'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['pass1'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}

			if ( ( $pagenow != 'user-new.php' ) && ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) ) {
				global $wpdb;
				$user_info = get_userdata( $user_id );
				update_user_meta( $user_id, 'stored_user_login', sanitize_text_field( $user_info->user_login ) );
				update_user_meta( $user_id, 'stored_user_password', sanitize_text_field( $plaintext_pass ) );
				$temp_user_login = 'unverified_' . wp_generate_password( 7, FALSE );
				$wpdb->update( $wpdb->users, array( 'user_login' => $temp_user_login ), array( 'ID' => $user_id ) );
			}
		}

		function rpr_restore_signup_fields_stub( $blog_id, $user_id, $password, $signup, $meta ) {
			$this->rpr_restore_signup_fields( $user_id, $password, $meta );
		}
	}
}

if ( class_exists( 'RPR_Activate' ) ) $rpr_activate = new RPR_Activate();
?>