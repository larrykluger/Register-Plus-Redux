<?php
/*
Author: radiok
Plugin Name: Register Plus Redux
Author URI: http://radiok.info/
Plugin URI: http://radiok.info/blog/category/register-plus-redux/
Description: Enhances the user registration process with complete customization and additional administration options.
Version: 3.9
Text Domain: register-plus-redux
*/

// NOTE: Debug, no more echoing
// trigger_error( sprintf( __( 'Register Plus Redux DEBUG: function($parameter=%s) from %s', 'register-plus-redux' ), $value, $pagenow ) ); 

// TODO: Define some "universal" functions and isolate features into separate php files
// TODO: meta key could be changed and ruin look ups
// TODO: Datepicker is never exposed as an option
// TODO: Add code to detect whether network activated?  Show admin_notice and/or disable functionality?
// TODO: Custom messages may not work with Wordpress MS as it uses wpmu_welcome_user_notification not wp_new_user_notification 
// TODO: Verify wp_new_user_notification triggers when used in MS due to the $pagenow checks

// TODO: Enhancement- Create rpr-signups table and mirror wpms
// TODO: Enhancement- Signups table needs an edit view
// TODO: Enhancement- MS users aren't being linked to a site, this is by design, as a setting to automatically add users at specified level
// TODO: Enhancement- Alter admin pages to match registration/signup
// TODO: Enhancement- Widget is lame/near worthless

if ( !class_exists( 'Register_Plus_Redux' ) ) {
	class Register_Plus_Redux {
		private $_options;
		function __construct() {
			global $wp_version;
			
			add_action( 'init', array( $this, 'InitI18n' ), 10, 1 );

			if ( !is_multisite() ) {
				add_filter( 'pre_user_login', array( $this, 'filter_pre_user_login_swp' ), 10, 1 ); // Changes user_login to user_email
			}

			add_action( 'user_register', array( $this, 'rpr_save_registration_fields' ), 10, 1 ); // Runs when a user's profile is first created. Action function argument: user ID. 

			add_action( 'show_user_profile', array( $this, 'rpr_show_custom_fields' ), 10, 1 ); // Runs near the end of the user profile editing screen.
			add_action( 'edit_user_profile', array( $this, 'rpr_show_custom_fields' ), 10, 1 ); // Runs near the end of the user profile editing screen in the admin menus. 
			add_action( 'profile_update', array( $this, 'rpr_save_custom_fields' ), 10, 1 ); // Runs when a user's profile is updated. Action function argument: user ID.

			add_action( 'admin_head-profile.php', array( $this, 'DatepickerHead' ), 10, 1 ); // Runs in the HTML <head> section of the admin panel of a page or a plugin-generated page.
			add_action( 'admin_head-user-edit.php', array( $this, 'DatepickerHead' ), 10, 1 ); // Runs in the HTML <head> section of the admin panel of a page or a plugin-generated page.
			add_filter( 'random_password', array( $this, 'filter_random_password' ), 10, 1 ); // Replace random password with user set password

			if ( $wp_version < 3.2 )
				add_action( 'admin_notices', array( $this, 'rpr_version_warning' ), 10, 1 ); // Runs after the admin menu is printed to the screen. 
		}

		function InitI18n() {
			// Place your language file in the languages subfolder and name it "register-plus-redux-{language}.mo" replace {language} with your language value from wp-config.php
			load_plugin_textdomain( 'register-plus-redux', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		function SaveReduxOptions( $options = array() ) {
			global $_options;
			if ( empty( $options ) && empty( $_options ) ) return FALSE;
			if ( !empty( $options ) ) {
				update_option( 'register_plus_redux_options', $options );
				$_options = $options;
			}
			else {
				update_option( 'register_plus_redux_options', $_options );
			}
			return TRUE;
		}

		function GetReduxOption( $option ) {
			global $_options;
			if ( empty( $option ) ) return NULL;
			$this->LoadReduxOptions( FALSE );
			if ( array_key_exists( $option, $_options ) )
				return $_options[$option];
			return NULL;
		}

		function LoadReduxOptions( $force_refresh = FALSE ) {
			global $_options;
			if ( empty( $_options ) || $force_refresh === TRUE ) {
				$_options = get_option( 'register_plus_redux_options' );
			}
			if ( empty( $_options ) ) {
				$this->SaveReduxOptions( $this->defaultOptions() );
			}
		}

		function SetReduxOption( $option, $value, $save_now = FALSE ) {
			global $_options;
			if ( empty( $option ) ) return FALSE;
			$this->LoadReduxOptions( FALSE );
			$_options[$option] = $value;
			if ( $save_now === TRUE ) {
				$this->SaveReduxOptions();
			}
			return TRUE;
		}

		function RemoveReduxOption( $option, $save_now = FALSE ) {
			global $_options;
			if ( empty( $option ) ) return FALSE;
			$this->LoadReduxOptions( FALSE );
			unset( $_options[$option] );
			if ( $save_now === TRUE ) {
				$this->SaveReduxOptions();
			}
			return TRUE;
		}

		function DatepickerHead() {
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( !empty( $meta_field['show_on_profile'] ) ) {
					if ( $meta_field['show_datepicker'] == TRUE ) {
						$show_custom_date_fields = TRUE;
						break;
					}
				}
			}
			if ( !empty( $show_custom_date_fields ) ) {
				if ( empty( $jquery_loaded ) ) {
					wp_print_scripts( 'jquery' );
					$jquery_loaded = TRUE;
				}
				wp_print_scripts( 'jquery-ui-core' );
				?>
				<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'js/theme/jquery.ui.all.css', __FILE__ ); ?>" />
				<script type="text/javascript" src="<?php echo plugins_url( 'js/jquery.ui.datepicker.min.js', __FILE__ ); ?>"></script>
				<script type="text/javascript">
				jQuery(function() {
					jQuery(".datepicker").datepicker();
				});
				</script>
				<?php
			}
		}

		function rpr_show_custom_fields( $profileuser ) {
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			if ( $this->GetReduxOption( 'enable_invitation_code' ) == TRUE || !empty( $redux_usermeta ) ) {
				echo '<h3>', __( 'Additional Information', 'register-plus-redux' ), '</h3>';
				echo '<table class="form-table">';
				if ( $this->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
					echo "\n\t<tr>";
					echo "\n\t\t<th><label for=\"invitation_code\">", __( 'Invitation Code', 'register-plus-redux' ), '</label></th>';
					echo "\n\t\t<td><input type=\"text\" name=\"invitation_code\" id=\"invitation_code\" value=\"", esc_attr( $profileuser->invitation_code ), '" class="regular-text" ';
					if ( !current_user_can( 'edit_users' ) ) echo 'readonly="readonly" ';
					echo '/></td>';
					echo "\n\t</tr>";
				}
				foreach ( $redux_usermeta as $index => $meta_field ) {
					if ( current_user_can( 'edit_users' ) || !empty( $meta_field['show_on_profile'] ) ) {
						$meta_key = esc_attr( $meta_field['meta_key'] );
						$value = get_user_meta( $profileuser->ID, $meta_key, TRUE );
						echo "\n\t<tr>";
						echo "\n\t\t<th><label for=\"", $meta_key, '">', esc_html( $meta_field['label'] );
						if ( empty( $meta_field['show_on_profile'] ) ) echo ' <span class="description">(hidden)</span>';
						if ( !empty( $meta_field['require_on_registration'] ) ) echo ' <span class="description">(required)</span>';
						echo '</label></th>';
						switch ( $meta_field['display'] ) {
							case 'textbox':
								echo "\n\t\t<td><input type=\"text\" name=\"", $meta_key, "\" id=\"", $meta_key, "\" ";
								if ( $meta_field['show_datepicker'] == TRUE ) echo 'class="datepicker" ';
								echo 'value="', esc_attr( $value ), '" class="regular-text" /></td>';
								break;
							case 'select':
								echo "\n\t\t<td>";
								echo "\n\t\t\t<select name=\"", $meta_key, '" id="', $meta_key, '" style="width: 15em;">';
								$field_options = explode( ',', $meta_field['options'] );
								foreach ( $field_options as $field_option ) {
									$option = esc_attr( $this->cleanupText( $field_option ) );
									echo "n\t\t\t\t<option value=\"", $option, '"';
									if ( $value == $option ) echo ' selected="selected"';
									echo '>', esc_html( $field_option ), '</option>';
								}
								echo "\n\t\t\t</select>";
								echo "\n\t\t</td>";
								break;
							case 'checkbox':
								echo "\n\t\t<td>";
								$field_options = explode( ',', $meta_field['options'] );
								$values = explode( ',', $value );
								foreach ( $field_options as $field_option ) {
									$option = esc_attr( $this->cleanupText( $field_option ) );
									echo "\n\t\t\t<label><input type=\"checkbox\" name=\"", $meta_key, '[]" value="', $option, '" ';
									if ( is_array( $values ) && in_array( $option, $values ) ) echo 'checked="checked" ';
									if ( !is_array( $values ) && ( $value == $option ) ) echo 'checked="checked" ';
									echo '/>&nbsp;', esc_html( $field_option ), '</label><br />';
								}
								echo "\n\t\t</td>";
								break;
							case 'radio':
								echo "\n\t\t<td>";
								$field_options = explode( ',', $meta_field['options'] );
								foreach ( $field_options as $field_option ) {
									$option = esc_attr( $this->cleanupText( $field_option ) );
									echo "\n\t\t\t<label><input type=\"radio\" name=\"", $meta_key, '" value="', $option, '" ';
									if ( $value == $option ) echo 'checked="checked" ';
									echo 'class="tog">&nbsp;', esc_html( $field_option ), '</label><br />';
								}
								echo "\n\t\t</td>";
								break;
							case 'textarea':
								echo "\n\t\t<td><textarea name=\"", $meta_key, '" id="', $meta_key, '" cols="25" rows="5">', esc_textarea( $value ), '</textarea></td>';
								break;
							case 'hidden':
								echo "\n\t\t<td><input type=\"text\" disabled=\"disabled\" name=\"", $meta_key, '" id="', $meta_key, '" value="', esc_attr( $value ), '" /></td>';
								break;
							case 'text':
								echo "\n\t\t<td><span class=\"description\">", esc_html( $meta_field['label'] ), '</span></td>';
								break;
						}
						echo "\n\t</tr>";
					}
				}
				echo '</table>';
			}
		}

		function rpr_save_custom_fields( $user_id ) {
			// TODO: Error check invitation code?
			if ( array_key_exists( 'invitation_code', $_POST ) ) {
				$invitation_code = get_magic_quotes_gpc() ? stripslashes( $_POST['invitation_code'] ) : $_POST['invitation_code'];
				update_user_meta( $user_id, 'invitation_code', sanitize_text_field( $invitation_code ) );
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( current_user_can( 'edit_users' ) || !empty( $meta_field['show_on_profile'] ) ) {
					if ( array_key_exists( $meta_field['meta_key'], $_POST ) ) {
						$value = get_magic_quotes_gpc() ? stripslashes( $_POST[$meta_field['meta_key']] ) : $_POST[$meta_field['meta_key']];
						$this->SaveMetaField( $meta_field, $user_id, $value );
					}
				}
			}
		}

		//wp-activate.php calls this page when activating user
		function rpr_save_registration_fields( $user_id ) {
			global $pagenow;
			trigger_error( sprintf( __( 'Register Plus Redux DEBUG: rpr_save_registration_fields($user_id=%s) from %s', 'register-plus-redux' ), $user_id, $pagenow ) ); 
			//deal with signup fields in RPR_Activate::rpr_restore_signup_fields
			if ( $pagenow == 'wp-activate.php' ) return;

			$source = get_magic_quotes_gpc() ? stripslashes_deep( $_POST ) : $_POST;

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
					if ( !empty( $source[$meta_field['meta_key']] ) ) $register_plus_redux->SaveMetaField( $meta_field, $user_id, $source[$meta_field['meta_key']] );
				}
			}

			if ( $this->GetReduxOption( 'enable_invitation_code' ) == TRUE && !empty( $source['invitation_code'] ) ) update_user_meta( $user_id, 'invitation_code', sanitize_text_field( $source['invitation_code'] ) );

			if ( $this->GetReduxOption( 'user_set_password' ) == TRUE && !empty( $source['pass1'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['pass1'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}

			if ( ( $pagenow == 'user-new.php' ) && !empty( $source['pass1'] ) ) {
				$plaintext_pass = sanitize_text_field( $source['pass1'] );
				update_user_option( $user_id, 'default_password_nag', FALSE, TRUE );
				wp_set_password( $plaintext_pass, $user_id );
			}

			if ( ( $pagenow != 'user-new.php' ) && ( $this->GetReduxOption( 'verify_user_email' ) == TRUE || $this->GetReduxOption( 'verify_user_admin' ) == TRUE ) ) {
				global $wpdb;
				$user_info = get_userdata( $user_id );
				update_user_meta( $user_id, 'stored_user_login', sanitize_text_field( $user_info->user_login ) );
				update_user_meta( $user_id, 'stored_user_password', sanitize_text_field( $plaintext_pass ) );
				$temp_user_login = 'unverified_' . wp_generate_password( 7, FALSE );
				$wpdb->update( $wpdb->users, array( 'user_login' => $temp_user_login ), array( 'ID' => $user_id ) );
			}

			// TODO: Verify autologin works
			if ( $pagenow != 'user-new.php' && $this->GetReduxOption( 'autologin_user' ) == TRUE && $this->GetReduxOption( 'verify_user_email' ) == FALSE && $this->GetReduxOption( 'verify_user_admin' ) == FALSE ) {
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

		}

		function SaveMetaField( $meta_field, $user_id, $value ) {
			// convert array to string
			if ( is_array( $value ) && count( $value ) ) $value = implode( ',', $value );
			// sanitize url
			if ( $meta_field['escape_url'] == TRUE ) {
				$value = esc_url_raw( $value );
				$value = preg_match( '/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $value ) ? $value : 'http://' . $value;
			}
			
			$valid_value = TRUE;
			// poor man's way to ensure required fields aren't blanked out, really should have a separate config per field
			if ( !empty( $meta_field['require_on_registration'] ) && empty( $value ) ) $valid_value = FALSE;
			// check text field against regex if specified
			if ( ( $meta_field['display'] == 'textbox' ) && !empty( $meta_field['options'] ) && !preg_match( $meta_field['options'], $value ) ) $valid_value = FALSE;
			if ( $meta_field['display'] != 'textarea' ) $value = sanitize_text_field( $value );
			if ( $meta_field['display'] = 'textarea' ) $value = wp_filter_kses( $value );
			
			if ( $valid_value ) update_user_meta( $user_id, $meta_field['meta_key'], $value );
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

		function filter_pre_user_login_swp( $user_login ) {
			// TODO: Review, this could be overriding some other stuff
			if ( $this->GetReduxOption( 'username_is_email' ) == TRUE ) {
				if ( array_key_exists( 'user_email', $_POST ) ) {
					$user_email = get_magic_quotes_gpc() ? stripslashes( $_POST['user_email'] ) : $_POST['user_email'];
					$user_email = strtolower( sanitize_user( $user_email ) );
				}
				if ( !empty( $user_email ) ) $user_login = $user_email;
			}
			return $user_login;
		}

		function filter_random_password( $password ) {
			if ( $this->GetReduxOption( 'user_set_password' ) == TRUE && $this->GetReduxOption( 'filter_random_password' ) == TRUE ) {
				if ( array_key_exists( 'pass1', $_POST ) ) {
					$password = get_magic_quotes_gpc() ? stripslashes( $_POST['pass1'] ) : $_POST['pass1'];
				}
				if ( array_key_exists( 'password', $_POST ) ) {
					$password = get_magic_quotes_gpc() ? stripslashes( $_POST['password'] ) : $_POST['password'];
				}
			}
			return $password;
		}

		function cleanupText( $text ) {
			$text = str_replace( ' ', '_', $text );
			$text = str_replace( '"' , '', $text );
			$text = str_replace( "'" , '', $text );
			$text = strtolower( $text );
			return $text;
		}

		function sendVerificationMessage( $user_id, $verification_code ) {
			$user_info = get_userdata( $user_id );
			$subject = $this->defaultOptions( 'verification_message_subject' );
			$message = $this->defaultOptions( 'verification_message_body' );
			add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_text' ), 10, 1 );
			if ( $this->GetReduxOption( 'custom_verification_message' ) == TRUE ) {
				$subject = esc_html( $this->GetReduxOption( 'verification_message_subject' ) );
				$message = $this->GetReduxOption( 'verification_message_body' );
				if ( $this->GetReduxOption( 'send_verification_message_in_html' ) == TRUE && $this->GetReduxOption( 'verification_message_newline_as_br' ) == TRUE )
					$message = nl2br( $message );
				if ( $this->GetReduxOption( 'verification_message_from_name' ) )
					add_filter( 'wp_mail_from_name', array( $this, 'filter_verification_message_from_name' ), 10, 1 );
				if ( is_email( $this->GetReduxOption( 'verification_message_from_email' ) ) )
					add_filter( 'wp_mail_from', array( $this, 'filter_verification_message_from' ), 10, 1 );
				if ( $this->GetReduxOption( 'send_verification_message_in_html' ) == TRUE )
					add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_html' ), 10, 1 );
			}
			$subject = $this->replaceKeywords( $subject, $user_info );
			$message = $this->replaceKeywords( $message, $user_info, '', $verification_code );
			wp_mail( $user_info->user_email, $subject, $message );
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

		function sendAdminMessage( $user_id, $plaintext_pass, $verification_code ) {
			$user_info = get_userdata( $user_id );
			$subject = $this->defaultOptions( 'admin_message_subject' );
			$message = $this->defaultOptions( 'admin_message_body' );
			add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_text' ), 10, 1 );
			if ( $this->GetReduxOption( 'custom_admin_message' ) == TRUE ) {
				$subject = esc_html( $this->GetReduxOption( 'admin_message_subject' ) );
				$message = $this->GetReduxOption( 'admin_message_body' );
				if ( $this->GetReduxOption( 'send_admin_message_in_html' ) == TRUE && $this->GetReduxOption( 'admin_message_newline_as_br' ) == TRUE )
					$message = nl2br( $message );
				if ( $this->GetReduxOption( 'admin_message_from_name' ) )
					add_filter( 'wp_mail_from_name', array( $this, 'filter_admin_message_from_name' ), 10, 1 );
				if ( is_email( $this->GetReduxOption( 'admin_message_from_email' ) ) )
					add_filter( 'wp_mail_from', array( $this, 'filter_admin_message_from' ), 10, 1 );
				if ( $this->GetReduxOption( 'send_admin_message_in_html' ) == TRUE )
					add_filter( 'wp_mail_content_type', array( $this, 'filter_message_content_type_html' ), 10, 1 );
			}
			$subject = $this->replaceKeywords( $subject, $user_info );
			$message = $this->replaceKeywords( $message, $user_info, $plaintext_pass, $verification_code );
			wp_mail( get_option( 'admin_email' ), $subject, $message );
		}

		function replaceKeywords( $message = '', $user_info = array(), $plaintext_pass = '', $verification_code = '' ) {
			global $pagenow;
			if ( empty( $message ) ) return '%blogname% %site_url% %http_referer% %http_user_agent% %registered_from_ip% %registered_from_host% %user_login% %user_email% %stored_user_login% %user_password% %verification_code% %verification_url%';
			// TODO: replaceKeywords could be attempting to get_user_meta before it is even written
			$message = str_replace( '%blogname%', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $message );
			$message = str_replace( '%site_url%', site_url(), $message );
			$message = str_replace( '%pagenow%', $pagenow, $message ); //debug keyword
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

		function filter_verification_message_from( $from_email ) {
			return is_email( $this->GetReduxOption( 'verification_message_from_email' ) );
		}

		function filter_verification_message_from_name( $from_name ) {
			return esc_html( $this->GetReduxOption( 'verification_message_from_name' ) );
		}

		function filter_user_message_from( $from_email ) {
			return is_email( $this->GetReduxOption( 'user_message_from_email' ) );
		}

		function filter_user_message_from_name( $from_name ) {
			return esc_html( $this->GetReduxOption( 'user_message_from_name' ) );
		}

		function filter_admin_message_from( $from_email ) {
			return is_email( $this->GetReduxOption( 'admin_message_from_email' ) );
		}

		function filter_admin_message_from_name( $from_name ) {
			return esc_html( $this->GetReduxOption( 'admin_message_from_name' ) );
		}

		function filter_message_content_type_html( $content_type ) {
			return 'text/html';
		}

		function filter_message_content_type_text( $content_type ) {
			return 'text/plain';
		}

		function rpr_version_warning() {
			global $wp_version;
			echo "\n<div id=\"register-plus-redux-warning\" class=\"updated fade-ff0000\"><p><strong>", sprintf( __( 'Register Plus Redux requires WordPress 3.2 or greater. You are currently using WordPress %s, please upgrade or deactivate Register Plus Redux.', 'register-plus-redux' ), $wp_version ), '</strong></p></div>';
		}

		function rpr_new_user_notification_warning() {
			if ( current_user_can(10) && isset( $_GET['page'] ) && ( $_GET['page'] == 'register-plus-redux' ) )
			echo "\n<div id=\"register-plus-redux-warning\" class=\"updated fade-ff0000\"><p><strong>", sprintf( __( 'There is another active plugin that is conflicting with Register Plus Redux. The conflicting plugin is creating its own wp_new_user_notification function, this function is used to alter the messages sent out following the creation of a new user. Please refer to <a href="%s">radiok.info</a> for help resolving this issue.', 'register-plus-redux' ), 'http://radiok.info/blog/wp_new_user_notification-conflicts/' ), '</strong></p></div>';
		}
	}
}

// include secondary php files outside of object otherwise $register_plus_redux will not be an instance yet
if ( class_exists( 'Register_Plus_Redux' ) ) {
	global $pagenow;
	//rumor has it this may need to declared global in order to be available at plugin activation
	$register_plus_redux = new Register_Plus_Redux();

	if ( is_admin() ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-admin.php' );

	$do_include = FALSE;
	if ( $register_plus_redux->GetReduxOption( 'enable_invitation_tracking_widget' ) == TRUE ) $do_include = TRUE;
	if ( $do_include ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-dashboard-widget.php' );

	$do_include = FALSE;
	if ( $register_plus_redux->GetReduxOption( 'verify_user_email' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'disable_user_message_registered' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'disable_user_message_created' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'custom_user_message' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'disable_admin_message_registered' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'disable_admin_message_created' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'custom_admin_message' ) == TRUE ) $do_include = TRUE;
	if ( $do_include ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-new-user-notification.php' );

	//TODO: Determine which features require the following file
	$do_include = TRUE;
	if ( $do_include ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-login.php' );

	//TODO: Determine which features require the following file
	$do_include = TRUE;
	if ( $do_include & is_multisite() ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-signup.php' );

	$do_include = FALSE;
	if ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) $do_include = TRUE;
	if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) $do_include = TRUE;
	if ( is_array( get_option( 'register_plus_redux_usermeta-rv2' ) ) ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) $do_include = TRUE;
	if ( $register_plus_redux->GetReduxOption( 'autologin_user' ) == TRUE ) $do_include = TRUE;
	if ( $do_include & is_multisite() ) require_once( plugin_dir_path( __FILE__ ) . 'rpr-activate.php' );
}
?>