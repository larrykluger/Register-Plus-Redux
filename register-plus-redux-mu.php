<?php
$register_plus_redux_mu_options = get_option( 'register_plus_redux_options' );

if ( !class_exists( 'RegisterPlusReduxMU' ) ) {
	class RegisterPlusReduxMU {
		function RegisterPlusReduxMU() {
			if ( is_multisite() ) {
				add_action( 'wpmu_activate_user', array( $this, 'rpr_restore_signup_fields' ), 10, 3 ); // Add stored metadata for new user to database
				//add_action( 'wpmu_activate_blog', array( $this, 'rpr_restore_signup_fields_stub' ), 10, 5 );
				add_filter( 'random_password', array( $this, 'filter_random_password_mu' ), 10, 1 ); // Replace random password with user set password
			}
		}

		function GetReduxOption( $option ) {
			global $register_plus_redux_mu_options;
			if ( empty( $option ) ) return NULL;
			$this->LoadReduxOptions( FALSE );
			if ( array_key_exists( $option, $register_plus_redux_mu_options ) )
				return $register_plus_redux_mu_options[$option];
			return NULL;
		}

		function LoadReduxOptions( $force_refresh = FALSE ) {
			global $register_plus_redux_mu_options;
			if ( empty( $register_plus_redux_mu_options ) || $force_refresh === TRUE ) {
				$register_plus_redux_mu_options = get_option( 'register_plus_redux_options' );
			}
		}

		function SaveMetaField( $meta_field, $user_id, $value ) {
			// convert array to string
			if ( is_array( $value ) && count( $value ) ) $value = implode( ',', $value );
			// santize url
			if ( $meta_field['escape_url'] == TRUE ) {
				$value = esc_url_raw( $value );
				$value = preg_match( '/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $value ) ? $value : 'http://' . $value;
			}
			
			$valid_value = TRUE;
			// poor man's way to ensure required fields aren't blanked out, really should have a seperate config per field
			if ( !empty( $meta_field['require_on_registration'] ) && empty( $value ) ) $valid_value = FALSE;
			// check text field against regex if specified
			if ( ( $meta_field['display'] == 'textbox' ) && !empty( $meta_field['options'] ) && !preg_match( $meta_field['options'], $value ) ) $valid_value = FALSE;
			if ( $meta_field['display'] != 'textarea' ) $value = sanitize_text_field( $value );
			if ( $meta_field['display'] = 'textarea' ) $value = wp_filter_kses( $value );
			
			if ( $valid_value ) update_user_meta( $user_id, $meta_field['meta_key'], $value );
		}

		function sendUserMessage( $user_id, $plaintext_pass ) {
			$user_info = get_userdata( $user_id );
			$subject = $this->defaultOptions( 'user_message_subject' );
			$message = $this->defaultOptions( 'user_message_body' );
			add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_text' ), 10, 1 );
			if ( $this->GetReduxOption( 'custom_user_message' ) == TRUE ) {
				$subject = esc_html( $this->GetReduxOption( 'user_message_subject' ) );
				$message = $this->GetReduxOption( 'user_message_body' );
				if ( $this->GetReduxOption( 'send_user_message_in_html' ) == TRUE && $this->GetReduxOption( 'user_message_newline_as_br' ) == TRUE )
					$message = nl2br( $message );
				if ( $this->GetReduxOption( 'user_message_from_name' ) )
					add_filter( 'wp_mail_from_name', array( $this, 'filter_user_message_from_name' ), 10, 1 );
				if ( is_email( $this->GetReduxOption( 'user_message_from_email' ) ) )
					add_filter( 'wp_mail_from', array( $this, 'filter_user_message_from' ), 10, 1 );
				if ( $this->GetReduxOption( 'send_user_message_in_html' ) == TRUE )
					add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_html' ), 10, 1 );
			}
			$subject = $this->replaceKeywords( $subject, $user_info );
			$message = $this->replaceKeywords( $message, $user_info, $plaintext_pass );
			wp_mail( $user_info->user_email, $subject, $message );
		}

		function defaultOptions( $option = '' )
		{
			$blogname = stripslashes( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
			$default = array(
				'custom_logo_url' => '',
				'verify_user_email' => '0',
				'message_verify_user_email' => __( 'Please verify your account using the verification link sent to your email address.', 'register-plus-redux' ),
				'verify_user_admin' => '0',
				'message_verify_user_admin' => __( 'Your account will be reviewed by an administrator and you will be notified when it is activated.', 'register-plus-redux' ),
				'delete_unverified_users_after' => 7,
				'autologin_user' => '0',

				'username_is_email' => '0',
				'double_check_email' => '0',
				'show_fields' => array(),
				'required_fields' => array(),
				'user_set_password' => '0',
				'min_password_length' => 6,
				'disable_password_confirmation' => '0',
				'show_password_meter' => '0',
				'message_empty_password' => 'Strength Indicator',
				'message_short_password' => 'Too Short',
				'message_bad_password' => 'Bad Password',
				'message_good_password' => 'Good Password',
				'message_strong_password' => 'Strong Password',
				'message_mismatch_password' => 'Password Mismatch',
				'enable_invitation_code' => '0',
				'require_invitation_code' => '0',
				'invitation_code_case_sensitive' => '0',
				'invitation_code_unique' => '0',
				'enable_invitation_tracking_widget' => '0',
				'show_disclaimer' => '0',
				'message_disclaimer_title' => 'Disclaimer',
				'message_disclaimer' => '',
				'require_disclaimer_agree' => '1',
				'message_disclaimer_agree' => 'Accept the Disclaimer',
				'show_license' => '0',
				'message_license_title' => 'License Agreement',
				'message_license' => '',
				'require_license_agree' => '1',
				'message_license_agree' => 'Accept the License Agreement',
				'show_privacy_policy' => '0',
				'message_privacy_policy_title' => 'Privacy Policy',
				'message_privacy_policy' => '',
				'require_privacy_policy_agree' => '1',
				'message_privacy_policy_agree' => 'Accept the Privacy Policy',
				'default_css' => '1',
				'required_fields_style' => 'border:solid 1px #E6DB55; background-color:#FFFFE0;',
				'required_fields_asterisk' => '0',
				'starting_tabindex' => 21,

				/*
				'datepicker_firstdayofweek' => 6,
				'datepicker_dateformat' => 'mm/dd/yyyy',
				'datepicker_startdate' => '',
				'datepicker_calyear' => '',
				'datepicker_calmonth' => 'cur',
				*/

				'disable_user_message_registered' => '0',
				'disable_user_message_created' => '0',
				'custom_user_message' => '0',
				'user_message_from_email' => get_option( 'admin_email' ),
				'user_message_from_name' => $blogname,
				'user_message_subject' => '[' . $blogname . '] ' . __( 'Your Login Information', 'register-plus-redux' ),
				'user_message_body' => "Username: %user_login%\nPassword: %user_password%\n\n%site_url%\n",
				'send_user_message_in_html' => '0',
				'user_message_newline_as_br' => '0',
				'custom_verification_message' => '0',
				'verification_message_from_email' => get_option( 'admin_email' ),
				'verification_message_from_name' => $blogname,
				'verification_message_subject' => '[' . $blogname . '] ' . __( 'Verify Your Account', 'register-plus-redux' ),
				'verification_message_body' => "Verification URL: %verification_url%\nPlease use the above link to verify your email address and activate your account\n",
				'send_verification_message_in_html' => '0',
				'verification_message_newline_as_br' => '0',

				'disable_admin_message_registered' => '0',
				'disable_admin_message_created' => '0',
				'admin_message_when_verified' => '0',
				'custom_admin_message' => '0',
				'admin_message_from_email' => get_option( 'admin_email' ),
				'admin_message_from_name' => $blogname,
				'admin_message_subject' => '[' . $blogname . '] ' . __( 'New User Registered', 'register-plus-redux' ),
				'admin_message_body' => "New user registered on your site %blogname%\n\nUsername: %user_login%\nE-mail: %user_email%\n",
				'send_admin_message_in_html' => '0',
				'admin_message_newline_as_br' => '0',

				'custom_registration_page_css' => '',
				'custom_login_page_css' => '',
				
				'registration_redirect_url' => '',
				'verification_redirect_url' => '',
				
				'filter_random_password' => ''
			);
			if ( !empty( $option ) )
				if ( array_key_exists( $option, $default ) )
					return $default[$option];
				else
					return FALSE;
			else
				return $default;
		}

		function replaceKeywords( $message = '', $user_info = array(), $plaintext_pass = '', $verification_code = '' ) {
			if ( empty( $message ) ) return '%blogname% %site_url% %http_referer% %http_user_agent% %registered_from_ip% %registered_from_host% %user_login% %user_email% %stored_user_login% %user_password% %verification_code% %verification_url%';
			// TODO: replaceKeywords could be attempting to get_user_meta before it is even written
			$message = str_replace( '%blogname%', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $message );
			$message = str_replace( '%site_url%', site_url(), $message );
			if ( !empty( $_SERVER ) ) {
				$message = str_replace( '%http_referer%', isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '', $message );
				$message = str_replace( '%http_user_agent%', isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '', $message );
				$message = str_replace( '%registered_from_ip%', isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '', $message );
				$message = str_replace( '%registered_from_host%', isset( $_SERVER['REMOTE_ADDR'] ) ? gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) : '', $message );
			}
			if ( !empty( $user_info ) ) {
				if ( ( $this->GetReduxOption( 'verify_user_email' ) == TRUE ) || ( $this->GetReduxOption( 'verify_user_admin' ) == TRUE ) ) {
					$login = $user_info->stored_user_login;
					if ( empty( $login ) ) $login = $user_info->user_login;
					$message = str_replace( '%user_login%', $login, $message );
				}
				else {
					$message = str_replace( '%user_login%', $user_info->user_login, $message );
				}
				$message = str_replace( '%user_email%', $user_info->user_email, $message );
				$message = str_replace( '%stored_user_login%', $user_info->stored_user_login, $message );
			}
			if ( !empty( $plaintext_pass ) ) {
				$message = str_replace( '%user_password%', $plaintext_pass, $message );
			}
			if ( !empty( $verification_code ) ) {
				$message = str_replace( '%verification_code%', $verification_code, $message );
				$message = str_replace( '%verification_link%', wp_login_url() . '?verification_code=' . $verification_code, $message );
				$message = str_replace( '%verification_url%', wp_login_url() . '?verification_code=' . $verification_code, $message );
			}
			return $message;
		}

		function rpr_restore_signup_fields( $user_id, $password, $meta ) {
			global $pagenow;

			$source = $meta;
			echo $user_id, ', ', $password, ', ', print_r( $meta );
			
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'first_name', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['first_name'] ) ) update_user_meta( $user_id, 'first_name', sanitize_text_field( $source['first_name'] ) );
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'last_name', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['last_name'] ) ) update_user_meta( $user_id, 'last_name', sanitize_text_field( $source['last_name'] ) );
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'url', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['user_url'] ) ) {
				$user_url = esc_url_raw( $source['user_url'] );
				$user_url = preg_match( '/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $user_url ) ? $user_url : 'http://' . $user_url;
				// HACK: update_user_meta does not allow update of user_url
				wp_update_user( array( 'ID' => $user_id, 'user_url' => sanitize_text_field( $user_url ) ) );
			}
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'aim', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['aim'] ) ) update_user_meta( $user_id, 'aim', sanitize_text_field( $source['aim'] ) );
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'yahoo', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['yahoo'] ) ) update_user_meta( $user_id, 'yim', sanitize_text_field( $source['yahoo'] ) );
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'jabber', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['jabber'] ) ) update_user_meta( $user_id, 'jabber', sanitize_text_field( $source['jabber'] ) );
			if ( is_array( $this->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $this->GetReduxOption( 'show_fields' ) ) && !empty( $source['description'] ) ) update_user_meta( $user_id, 'description', wp_filter_kses( $source['description'] ) );

			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( current_user_can( 'edit_users' ) || !empty( $meta_field['show_on_registration'] ) ) {
					$this->SaveMetaField( $meta_field, $user_id, $source[$meta_field['meta_key']] );
				}
			}

			if ( $this->GetReduxOption( 'enable_invitation_code' ) == TRUE && !empty( $source['invitation_code'] ) ) update_user_meta( $user_id, 'invitation_code', sanitize_text_field( $source['invitation_code'] ) );

			// TODO: Verify autologin works
			if ( $pagenow != 'user-new.php' && $this->GetReduxOption( 'autologin_user' ) == TRUE && $this->GetReduxOption( 'verify_user_admin' ) == FALSE ) {
				$user_info = get_userdata( $user_id );
				$credentials['user_login'] = sanitize_text_field( $user_info->user_login );
				if ( empty( $_POST['pass1'] ) ) {
					$plaintext_pass = wp_generate_password();
					update_user_option( $user_id, 'default_password_nag', TRUE, TRUE );
					wp_set_password( $plaintext_pass, $user_id );
					$credentials['user_password'] = $plaintext_pass;
					if ( $this->GetReduxOption( 'disable_user_message_registered' ) == FALSE )
						$this->sendUserMessage( $user_id, $plaintext_pass );
				}
				else {
					$credentials['user_password'] = sanitize_text_field( $_POST['pass1'] );
				}
				$credentials['remember'] = FALSE;
				$user = wp_signon( $credentials, FALSE ); 
			}

			if ( $this->GetReduxOption( 'user_set_password' ) == TRUE && !empty( $source['password'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['password'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}
			if ( ( $pagenow == 'user-new.php' ) && !empty( $source['pass1'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['pass1'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}

			if ( ( $pagenow != 'user-new.php' ) && ( $this->GetReduxOption( 'verify_user_admin' ) == TRUE ) ) {
				global $wpdb;
				$user_info = get_userdata( $user_id );
				update_user_meta( $user_id, 'stored_user_login', sanitize_text_field( $user_info->user_login ) );
				update_user_meta( $user_id, 'stored_user_password', sanitize_text_field( $plaintext_pass ) );
				$temp_user_login = 'unverified_' . wp_generate_password( 7, FALSE );
				$wpdb->update( $wpdb->users, array( 'user_login' => $temp_user_login ), array( 'ID' => $user_id ) );
			}
		}

		function rpr_restore_signup_fields_stub( $blog_id, $user_id, $password, $signup, $meta ) {
			rpr_restore_signup_fields( $user_id, $password, $meta );
		}

		function filter_random_password_mu( $password ) {
			if ( $this->GetReduxOption( 'user_set_password' ) == TRUE ) {
				global $pagenow;
				if ( $pagenow == 'wp-activate.php' ) {
					$activation_key = isset( $_POST['key'] ) ? $_POST['key'] : '';
					if ( isset( $_GET['key'] ) ) $activation_key = $_GET['key'];
					if ( get_magic_quotes_gpc() ) stripslashes( $activation_key );
					if ( !empty( $activation_key ) ) {
						global $wpdb;
						$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s;", $activation_key ) );
						if ( !empty( $signup ) ) {
							$meta = unserialize( $signup->meta );
							if ( is_array( $meta ) && array_key_exists( 'pass1', $meta ) && !empty( $meta['pass1'] ) ) {
								$password = $meta['pass1'];
								unset( $meta['pass1'] );
								if ( array_key_exists( 'pass2', $meta ) ) unset( $meta['pass2'] );
								$meta = serialize( $meta );
								$wpdb->update( $wpdb->signups, array( 'meta' => $meta ), array( 'activation_key' => $activation_key ) );
							}
						}
					}
				}
			}
			return $password;
		}
	}
}

if ( class_exists( 'RegisterPlusReduxMU' ) )
	$register_plus_redux_mu = new RegisterPlusReduxMU();