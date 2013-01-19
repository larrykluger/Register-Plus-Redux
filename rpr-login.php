<?php
if ( !class_exists( 'RPR_Login' ) ) {
	class RPR_Login {
		function __construct() {
			add_filter( 'random_password', array( $this, 'filter_random_password' ), 10, 1 ); // Replace random password with user set password

			add_action( 'register_form', array( $this, 'rpr_additional_registration_fields' ), 9, 1); // Higher priority to avoid getting bumped by other plugins
			add_filter( 'registration_errors', array( $this, 'rpr_check_registration' ), 10, 3 ); // applied to the list of registration errors generated while registering a user for a new account. 
			add_filter( 'login_message', array( $this, 'filter_login_message' ), 10, 1 );
			add_filter( 'registration_redirect', array( $this, 'filter_registration_redirect' ), 10, 1 );

			add_action( 'login_head', array( $this, 'rpr_login_head_style_scripts' ), 10, 1 );
			add_action( 'login_footer', array( $this, 'rpr_login_foot_scripts' ), 10, 1 ); // Hides user_login, changed username to e-mail
			add_filter( 'login_headerurl', array( $this, 'filter_login_headerurl' ), 10, 1); // Modify url to point to site
			add_filter( 'login_headertitle', array( $this, 'filter_login_headertitle' ), 10, 1 ); // Modify header to blogname
			add_filter( 'allow_password_reset', array( $this, 'filter_password_reset' ), 10, 2 );
		}

		function filter_random_password( $password ) {
			global $register_plus_redux;
			global $pagenow;
			if ( $pagenow == 'wp-login.php' && $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				if ( is_array( $_REQUEST ) && array_key_exists( 'action', $_REQUEST ) && $_REQUEST['action'] == 'register' ) {
					if ( array_key_exists( 'pass1', $_POST ) ) {
						$password = get_magic_quotes_gpc() ? stripslashes( $_POST['pass1'] ) : $_POST['pass1'];
					}
				}
			}
			return $password;
		}

		function rpr_additional_registration_fields() {
			global $register_plus_redux;
			if ( get_magic_quotes_gpc() ) $_POST = stripslashes_deep( $_POST );
			if ( get_magic_quotes_gpc() ) $_GET = stripslashes_deep( $_GET );
			$tabindex = absint( $register_plus_redux->GetReduxOption( 'starting_tabindex' ) );
			if ( !is_numeric( $tabindex ) || $tabindex < 1 ) $tabindex = 0;
			if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) {
				$user_email2 = isset( $_POST['user_email2'] ) ? $_POST['user_email2'] : '';
				if ( isset( $_GET['user_email2'] ) ) $user_email2 = $_GET['user_email2'];
				echo "\n<p id=\"user_email2-p\"><label id=\"user_email2-label\" for=\"user_email2\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
				echo __( 'Confirm E-mail', 'register-plus-redux' ), '<br /><input type="text" autocomplete="off" name="user_email2" id="user_email2" class="input" value="', esc_attr( $user_email2 ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$first_name = isset( $_POST['first_name'] ) ? $_POST['first_name'] : '';
				if ( isset( $_GET['first_name'] ) ) $first_name = $_GET['first_name'];
				echo "\n<p id=\"first_name-p\"><label id=\"first_name-label\" for=\"first_name\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'First Name', 'register-plus-redux' ), '<br /><input type="text" name="first_name" id="first_name" class="input" value="', esc_attr( $first_name ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$last_name = isset( $_POST['last_name'] ) ? $_POST['last_name'] : '';
				if ( isset( $_GET['last_name'] ) ) $last_name = $_GET['last_name'];
				echo "\n<p id=\"last_name-p\"><label id=\"last_name-label\" for=\"last_name\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Last Name', 'register-plus-redux' ), '<br /><input type="text" name="last_name" id="last_name" class="input" value="', esc_attr( $last_name ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$user_url = isset( $_POST['user_url'] ) ? $_POST['user_url'] : '';
				if ( isset( $_GET['user_url'] ) ) $user_url = $_GET['user_url'];
				echo "\n<p id=\"user_url-p\"><label id=\"user_url-label\" for=\"user_url\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Website', 'register-plus-redux' ), '<br /><input type="text" name="url" id="user_url" class="input" value="', esc_attr( $user_url ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$aim = isset( $_POST['aim'] ) ? $_POST['aim'] : '';
				if ( isset( $_GET['aim'] ) ) $aim = $_GET['aim'];
				echo "\n<p id=\"aim-p\"><label id=\"aim-label\" for=\"aim\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'AIM', 'register-plus-redux' ), '<br /><input type="text" name="aim" id="aim" class="input" value="', esc_attr( $aim ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$yahoo = isset( $_POST['yahoo'] ) ? $_POST['yahoo'] : '';
				if ( isset( $_GET['yahoo'] ) ) $yahoo = $_GET['yahoo'];
				echo "\n<p id=\"yahoo-p\"><label id=\"yahoo-label\" for=\"yahoo\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Yahoo IM', 'register-plus-redux' ), '<br /><input type="text" name="yahoo" id="yahoo" class="input" value="', esc_attr( $yahoo ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$jabber = isset( $_POST['jabber'] ) ? $_POST['jabber'] : '';
				if ( isset( $_GET['jabber'] ) ) $_POST['jabber'] = $_GET['jabber'];
				echo "\n<p id=\"jabber-p\"><label id=\"jabber-label\" for=\"jabber\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Jabber / Google Talk', 'register-plus-redux' ), '<br /><input type="text" name="jabber" id="jabber" class="input" value="', esc_attr( $jabber ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$description = isset( $_POST['description'] ) ? $_POST['description'] : '';
				if ( isset( $_GET['description'] ) ) $description = $_GET['description'];
				echo "\n<p id=\"description-p\"><label id=\"description-label\" for=\"description\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'About Yourself', 'register-plus-redux' ), '</label><br />';
				echo "\n<small id=\"description_msg\">", __( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'register-plus-redux' ), '</small><br />';
				echo "\n<textarea name=\"description\" id=\"description\" cols=\"25\" rows=\"5\"";
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '>', esc_textarea( $description ), '</textarea></p>';
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( !empty( $meta_field['show_on_registration'] ) ) {
					$meta_key = esc_attr( $meta_field['meta_key'] );
					$value = isset( $_POST[$meta_key] ) ? $_POST[$meta_key] : '';
					if ( isset( $_GET[$meta_key] ) ) $value = $_GET[$meta_key];
					switch ( $meta_field['display'] ) {
						case 'textbox':
							echo "\n<p id=\"", $meta_key, '-p"><label id="', $meta_key, '-label" for="', $meta_key, '">';
							if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
							echo esc_html( $meta_field['label'] ), '<br /><input type="text" name="', $meta_key, '" id="', $meta_key, '" ';
							if ( $meta_field['show_datepicker'] == TRUE ) echo 'class="datepicker" '; else echo 'class="input" ';
							echo 'value="', esc_attr( $value ), '" size="25" ';
							if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
							echo '/></label></p>';
							break;
						case 'select':
							echo "\n<p id=\"", $meta_key, '-p"><label id="', $meta_key, '-label" for="', $meta_key, '">';
							if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
							echo esc_html( $meta_field['label'] ), '<br />';
							echo "\n<select name=\"", $meta_key, '" id="', $meta_key, '"';
							if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
							echo '>';
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo '<option id="', $meta_key, '-', $option, '" value="', $option, '"';
								if ( $value == $option ) echo ' selected="selected"';
								echo '>', esc_html( $field_option ), '</option>';
							}
							echo '</select>';
							echo "\n</label></p>";
							break;
						case 'checkbox':
							echo "\n<p id=\"", $meta_key, '-p" style="margin-bottom:16px;"><label id="', $meta_key, '-label" for="', $meta_key, '">';
							if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
							echo esc_html( $meta_field['label'] ), '</label><br />';
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo "\n<input type=\"checkbox\" name=\"", $meta_key, '[]" id="', $meta_key, '-', $option, '" value="$option" ';
								if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
								if ( is_array( $value ) && in_array( $option, $value ) ) echo 'checked="checked" ';
								if ( !is_array( $value ) && ( $value == $option ) ) echo 'checked="checked" ';
								echo '/><label id="', $meta_key, '-', $option, '-label" class="', $meta_key, '" for="', $meta_key, '-', $option, '">&nbsp;', esc_html( $field_option ), '</label><br />';
							}
							echo "\n</p>";
							break;
						case 'radio':
							echo "\n<p id=\"", $meta_key, '-p" style="margin-bottom:16px;"><label id="', $meta_key, '-label" for="', $meta_key, '">';
							if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
							echo esc_html( $meta_field['label'] ), '</label><br />';
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo "\n<input type=\"radio\" name=\"", $meta_key, '" id="', $meta_key, '-', $option, '" value="', $option, '" ';
								if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
								if ( $value == $option ) echo 'checked="checked" ';
								echo '/><label id="', $meta_key, '-', $option, '-label" class="', $meta_key, '" for="', $meta_key, '-', $option, '">&nbsp;', esc_html( $field_option ), '</label><br />';
							}
							echo "\n</p>";
							break;
						case 'textarea':
							echo "\n<p id=\"", $meta_key, '-p"><label id="', $meta_key, '-label" for="', $meta_key, '">';
							if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
							echo esc_html( $meta_field['label'] ), '<br /><textarea name="', $meta_key, '" id="', $meta_key, '" cols="25" rows="5"';
							if ( $tabindex != 0 ) echo " tabindex=\"", $tabindex++, "\" ";
							echo '>', esc_textarea( $value ), '</textarea></label></p>';
							break;
						case 'hidden':
							echo "\n<input type=\"hidden\" name=\"", $meta_key, '" id="', $meta_key, '" value="', esc_attr( $value ), '" ';
							if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
							echo '/>';
							break;
						case 'text':
							echo "\n<p id=\"", $meta_key, '-p"><small id="', $meta_key, '-small">', esc_html( $meta_field['label'] ), '</small></p>';
							break;
					}
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
				if ( isset( $_GET['password'] ) ) $password = $_GET['password'];
				echo "\n<p id=\"pass1-p\"><label id=\"pass1-label\" for=\"pass1\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
				echo __( 'Password', 'register-plus-redux' ), '<br /><input type="password" autocomplete="off" name="pass1" id="pass1" value="', esc_attr( $password ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
				if ( $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE ) {
					echo "\n<p id=\"pass2-p\"><label id=\"pass2-label\" for=\"pass2\">";
					if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
					echo __( 'Confirm Password', 'register-plus-redux' ), '<br /><input type="password" autocomplete="off" name="pass2" id="pass2" value="', esc_attr( $password ), '" size="25" ';
					if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
					echo '/></label></p>';
				}
				if ( $register_plus_redux->GetReduxOption( 'show_password_meter' ) == TRUE ) {
					echo "\n<div id=\"pass-strength-result\">", $register_plus_redux->GetReduxOption( 'message_empty_password' ), '</div>';
					echo "\n<small id=\"pass_strength_msg\">", sprintf(__( 'Your password must be at least %d characters long. To make your password stronger, use upper and lower case letters, numbers, and the following symbols !@#$%%^&amp;*()', 'register-plus-redux' ), absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ), '</small>';
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
				$invitation_code = isset( $_POST['invitation_code'] ) ? $_POST['invitation_code'] : '';
				if ( isset( $_GET['invitation_code'] ) ) $invitation_code = $_GET['invitation_code'];
				echo "\n<p id=\"invitation_code-p\"><label id=\"invitation_code-label\" for=\"invitation_code\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) echo '*';
				echo __( 'Invitation Code', 'register-plus-redux' ), '<br /><input type="text" name="invitation_code" id="invitation_code" class="input" value="', esc_attr( $invitation_code ), '" size="25" ';
				if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
				echo '/></label></p>';
				if ( $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE )
					echo "\n<small id=\"invitation_code_msg\">", __( 'This website is currently closed to public registrations. You will need an invitation code to register.', 'register-plus-redux' ), '</small>';
				else
					echo "\n<small id=\"invitation_code_msg\">", __( 'Have an invitation code? Enter it here. (This is not required)', 'register-plus-redux' ), '</small>';
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE ) {
				$accept_disclaimer = isset( $_POST['accept_disclaimer'] ) ? '1' : '0';
				if ( isset( $_GET['accept_disclaimer'] ) ) $accept_disclaimer = $_GET['accept_disclaimer'];
				echo "\n<p id=\"disclaimer-p\">";
				echo "\n\t<label id=\"disclaimer_title\">", esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_title' ) ), '</label><br />';
				echo "\n\t<div name=\"disclaimer\" id=\"disclaimer\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_disclaimer' ) ), '</div>';
				if ( $register_plus_redux->GetReduxOption( 'require_disclaimer_agree' ) == TRUE ) {
					echo "\n\t<label id=\"accept_disclaimer-label\" class=\"accept_check\" for=\"accept_disclaimer\"><input type=\"checkbox\" name=\"accept_disclaimer\" id=\"accept_disclaimer\" value=\"1\""; if ( !empty( $accept_disclaimer ) ) echo ' checked="checked" ';
					if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
					echo '/>&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_agree' ) ), '</label>';
				}
				echo "\n</p>";
			}
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE ) {
				$accept_license = isset( $_POST['accept_license'] ) ? '1' : '0';
				if ( isset( $_GET['accept_license'] ) ) $accept_license = $_GET['accept_license'];
				echo "\n<p id=\"license-p\">";
				echo "\n\t<label id=\"license_title\">", esc_html( $register_plus_redux->GetReduxOption( 'message_license_title' ) ), '</label><br />';
				echo "\n\t<div name=\"license\" id=\"license\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_license' ) ), '</div>';
				if ( $register_plus_redux->GetReduxOption( 'require_license_agree' ) == TRUE ) {
					echo "\n\t<label id=\"accept_license-label\" class=\"accept_check\" for=\"accept_license\"><input type=\"checkbox\" name=\"accept_license\" id=\"accept_license\" value=\"1\""; if ( !empty( $accept_license ) ) echo ' checked="checked" ';
					if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
					echo '/>&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_license_agree' ) ), '</label>';
				}
				echo "\n</p>";
			}
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) {
				$accept_privacy_policy = isset( $_POST['accept_privacy_policy'] ) ? '1' : '0';
				if ( isset( $_GET['accept_privacy_policy'] ) ) $accept_privacy_policy = $_GET['accept_privacy_policy'];
				echo "\n<p id=\"privacy_policy-p\">";
				echo "\n\t<label id=\"privacy_policy_title\">", esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_title' ) ), "</label><br />";
				echo "\n\t<div name=\"privacy_policy\" id=\"privacy_policy\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_privacy_policy' ) ), "</div>";
				if ( $register_plus_redux->GetReduxOption( 'require_privacy_policy_agree' ) == TRUE ) {
					echo "\n\t<label id=\"accept_privacy_policy-label\" class=\"accept_check\" for=\"accept_privacy_policy\"><input type=\"checkbox\" name=\"accept_privacy_policy\" id=\"accept_privacy_policy\" value=\"1\""; if ( !empty( $accept_privacy_policy ) ) echo ' checked="checked" ';
					if ( $tabindex != 0 ) echo 'tabindex="', $tabindex++, '" ';
					echo '/>&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_agree' ) ), "</label>";
				}
				echo "\n</p>";
			}
		}

		function rpr_check_registration( $errors, $sanitized_user_login, $user_email ) {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE )  {
				if ( is_array( $errors->errors ) && array_key_exists( 'empty_username', $errors->errors ) ) unset( $errors->errors['empty_username'] );
				if ( is_array( $errors->error_data ) && array_key_exists( 'empty_username', $errors->error_data ) ) unset( $errors->error_data['empty_username'] );
				$sanitized_user_login = sanitize_user( $user_email );
				if ( $sanitized_user_login != $user_email ) {
					$errors->add( 'invalid_email', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Email address is not appropriate as a username, please enter another email address.', 'register-plus-redux' ) );
				}
			}
			if ( !empty( $sanitized_user_login ) ) {
				global $wpdb;
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'stored_user_login' AND meta_value = %s;", $sanitized_user_login ) ) ) {
					$errors->add( 'username_exists', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'This username is already registered, please choose another one.', 'register-plus-redux' ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) {
				if ( empty( $_POST['user_email2'] ) ) {
					$errors->add( 'empty_email', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please confirm your e-mail address.', 'register-plus-redux' ) );
				}
				elseif ( $_POST['user_email'] != $_POST['user_email2'] ) {
					$errors->add( 'email_mismatch', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Your e-mail address does not match.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['first_name'] ) ) {
					$errors->add( 'empty_first_name', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your first name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['last_name'] ) ) {
					$errors->add( 'empty_last_name', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your last name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['user_url'] ) ) {
					$errors->add( 'empty_user_url', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your website URL.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['aim'] ) ) {
					$errors->add( 'empty_aim', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your AIM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'yahoo' , $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['yahoo'] ) ) {
					$errors->add( 'empty_yahoo', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your Yahoo IM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['jabber'] ) ) {
					$errors->add( 'empty_jabber', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter your Jabber / Google Talk username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['description'] ) ) {
					$errors->add( 'empty_description', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter some information about yourself.', 'register-plus-redux' ) );
				}
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				$meta_key = $meta_field['meta_key'];
				if ( !empty( $meta_field['show_on_registration'] ) && !empty( $meta_field['require_on_registration'] ) && empty( $_POST[$meta_key] ) ) {
					$errors->add( 'empty_' . $meta_key, sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter a value for %s.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
				if ( !empty( $meta_field['show_on_registration'] ) && ( $meta_field['display'] == 'textbox' ) && !empty( $meta_field['options'] ) && !preg_match( $meta_field['options'], $_POST[$meta_key] ) ) {
					$errors->add( 'invalid_' . $meta_key, sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter new value for %s, value specified is not in the correct format.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				if ( empty( $_POST['pass1'] ) && $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE ) {
					$errors->add( 'empty_password', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter a password.', 'register-plus-redux' ) );
				}
				elseif ( strlen( $_POST['pass1'] ) < absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) {
					$errors->add( 'password_length', sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Your password must be at least %d characters in length.', 'register-plus-redux' ), absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) );
				}
				elseif ( $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE && ( $_POST['pass1'] != $_POST['pass2'] ) ) {
					$errors->add( 'password_mismatch', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Your password does not match.', 'register-plus-redux' ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
				if ( empty( $_POST['invitation_code'] ) && $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) {
					$errors->add( 'empty_invitation_code', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please enter an invitation code.', 'register-plus-redux' ) );
				}
				elseif ( !empty( $_POST['invitation_code'] ) ) {
					$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
					if ( !is_array( $invitation_code_bank ) ) $invitation_code_bank = array();
					if ( $register_plus_redux->GetReduxOption( 'invitation_code_case_sensitive' ) == FALSE ) {
						$_POST['invitation_code'] = strtolower( $_POST['invitation_code'] );
						foreach ( $invitation_code_bank as $index => $invitation_code )
							$invitation_code_bank[$index] = strtolower( $invitation_code );
					}
					if ( is_array( $invitation_code_bank ) && !in_array( $_POST['invitation_code'], $invitation_code_bank ) ) {
						$errors->add( 'invitation_code_mismatch', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'That invitation code is invalid.', 'register-plus-redux' ) );
					}
					else {
						// reverts lowercase key to stored case
						$key = array_search( $_POST['invitation_code'], $invitation_code_bank );
						$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
						$_POST['invitation_code'] = $invitation_code_bank[$key];
						if ( $register_plus_redux->GetReduxOption( 'invitation_code_unique' ) == TRUE ) {
							global $wpdb;
							if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'invitation_code' AND meta_value = %s;", $_POST['invitation_code'] ) ) ) {
								$errors->add( 'invitation_code_exists', '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'This invitation code is already in use, please enter a unique invitation code.', 'register-plus-redux' ) );
							}
						}
					}
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_disclaimer_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_disclaimer'] ) ) {
					$errors->add( 'accept_disclaimer', sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_license_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_license'] ) ) {
					$errors->add( 'accept_license', sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_license_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_privacy_policy_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_privacy_policy'] ) ) {
					$errors->add( 'accept_privacy_policy' , sprintf( '<strong>' . __( 'ERROR', 'register-plus-redux' ) . '</strong>:&nbsp;' . __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_title' ) ) ) . '.' );
				}
			}
			return $errors;
		}

		function filter_login_message( $message ) {
			global $register_plus_redux;
			// WordPress quirk, must throw errors now
			global $errors;
			if ( ( $register_plus_redux->GetReduxOption( 'verify_user_email' ) == TRUE ) && isset( $_GET['verification_code'] ) ) {
				global $wpdb;
				$verification_code = get_magic_quotes_gpc() ? stripslashes( $_GET['verification_code'] ) : $_GET['verification_code'];
				$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'email_verification_code' AND meta_value = %s;", $verification_code ) );
				if ( !empty( $user_id ) ) {
					if ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == FALSE ) {
						$stored_user_login = get_user_meta( $user_id, 'stored_user_login', TRUE );
						$plaintext_pass = get_user_meta( $user_id, 'stored_user_password', TRUE );
						$wpdb->update( $wpdb->users, array( 'user_login' => $stored_user_login ), array( 'ID' => $user_id ) );
						delete_user_meta( $user_id, 'email_verification_code' );
						delete_user_meta( $user_id, 'email_verification_sent' );
						delete_user_meta( $user_id, 'stored_user_login' );
						delete_user_meta( $user_id, 'stored_user_password' );
						if ( empty( $plaintext_pass ) ) {
							$plaintext_pass = wp_generate_password();
							update_user_option( $user_id, 'default_password_nag', TRUE, TRUE );
							wp_set_password( $plaintext_pass, $user_id );
						}
						if ( $register_plus_redux->GetReduxOption( 'disable_user_message_registered' ) == FALSE )
							$register_plus_redux->sendUserMessage( $user_id, $plaintext_pass );
						if ( $register_plus_redux->GetReduxOption( 'admin_message_when_verified' ) == TRUE )
							$register_plus_redux->sendAdminMessage( $user_id, $plaintext_pass );
						if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE )
							$errors->add( 'account_verified', sprintf( __( 'Thank you %s, your account has been verified, please login with the password you specified during registration.', 'register-plus-redux' ), $stored_user_login ), 'message' );
						else
							$errors->add( 'account_verified_checkemail', sprintf( __( 'Thank you %s, your account has been verified, your password will be emailed to you.', 'register-plus-redux' ), $stored_user_login ), 'message' );
					}
					elseif ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) {
						update_user_meta( $user_id, 'email_verified', gmdate( 'Y-m-d H:i:s' ) );
						$errors->add( 'admin_review', __( 'Your account will be reviewed by an administrator and you will be notified when it is activated.', 'register-plus-redux' ), 'message' );
					}
				}
				else {
					$errors->add( 'invalid_verification_code', __( 'Invalid verification code.', 'register-plus-redux' ), 'error' );
				}
			}
			if ( isset( $_GET['checkemail'] ) && ( $_GET['checkemail'] == 'registered' ) ) {
				if ( $register_plus_redux->GetReduxOption( 'verify_user_email' ) == TRUE ) {
					if ( is_array( $errors->errors ) && array_key_exists( 'registered', $errors->errors ) ) unset( $errors->errors['registered'] );
					if ( is_array( $errors->error_data ) && array_key_exists( 'registered', $errors->error_data ) ) unset( $errors->error_data['registered'] );
					$errors->add( 'verify_user_email', nl2br( $register_plus_redux->GetReduxOption( 'message_verify_user_email' ) ), 'message' );
				}
				elseif ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) {
					if ( is_array( $errors->errors ) && array_key_exists( 'registered', $errors->errors ) ) unset( $errors->errors['registered'] );
					if ( is_array( $errors->error_data ) && array_key_exists( 'registered', $errors->error_data ) ) unset( $errors->error_data['registered'] );
					$errors->add( 'verify_user_admin', nl2br( $register_plus_redux->GetReduxOption( 'message_verify_user_admin' ) ), 'message' );
				}
			}
			return $message;
		}

		function filter_registration_redirect( $redirect_to ) {
			global $register_plus_redux;
			// NOTE: default $redirect_to = 'wp-login.php?checkemail=registered'
			// TODO: Verify autologin works
			if ( $register_plus_redux->GetReduxOption( 'autologin_user' ) == TRUE ) $redirect_to = admin_url();
			if ( $register_plus_redux->GetReduxOption( 'registration_redirect_url' ) ) $redirect_to = esc_url( $register_plus_redux->GetReduxOption( 'registration_redirect_url' ) );
			return $redirect_to;
		}

		function rpr_login_head_style_scripts() {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'custom_logo_url' ) ) {
				if ( ini_get( 'allow_url_fopen' ) )
					list( $width, $height, $type, $attr ) = getimagesize( esc_url( $register_plus_redux->GetReduxOption( 'custom_logo_url' ) ) );
				?>
				<style type="text/css">
					#login h1 a {
						background-image: url("<?php echo esc_url( $register_plus_redux->GetReduxOption( 'custom_logo_url' ) ); ?>");
						margin: 0 0 0 8px;
						<?php if ( !empty( $width ) ) echo 'width: ', $width, "px;\n"; ?>
						<?php if ( !empty( $height ) ) echo 'height: ', $height, "px;\n"; ?>
					}
				</style>
				<?php
			}
			if ( isset( $_GET['checkemail'] ) && ( $_GET['checkemail'] == 'registered' ) && ( $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE || $register_plus_redux->GetReduxOption( 'verify_user_email' ) == TRUE ) ) {
				?>
				<style type="text/css">
					#loginform { display: none; }
					#nav { display: none; }
				</style>
				<?php
			}
			if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'register' ) ) {
				$user_login = isset( $_POST['user_login'] ) ? $_POST['user_login'] : '';
				if ( isset( $_GET['user_login'] ) ) $user_login = $_GET['user_login'];
				if ( get_magic_quotes_gpc() ) $user_login = stripslashes( $user_login );
				$user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
				if ( isset( $_GET['user_email'] ) ) $user_email = $_GET['user_email'];
				if ( get_magic_quotes_gpc() ) $user_email = stripslashes( $user_email );
				if ( !empty( $user_login ) || !empty( $user_email ) ) {
					if ( empty( $jquery_loaded ) ) {
						wp_print_scripts( 'jquery' );
						$jquery_loaded = TRUE;
					}
					// TODO: I'd rather escape than sanitize
					?>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#user_login").val("<?php echo sanitize_user( $user_login ); ?>");
						jQuery("#user_email").val("<?php echo is_email( $user_email ); ?>");
					});
					</script>
					<?php
				}
				$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
				if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
				foreach ( $redux_usermeta as $index => $meta_field ) {
					if ( !empty( $meta_field['show_on_registration'] ) ) {
						$meta_key = esc_attr( $meta_field['meta_key'] );
						if ( $meta_field['display'] == 'textbox' ) {
							if ( empty( $show_custom_text_fields ) )
								$show_custom_text_fields = '#' . $meta_key;
							else
								$show_custom_text_fields .= ', #' . $meta_key;
						}
						if ( $meta_field['display'] == 'select' ) {
							if ( empty( $show_custom_select_fields ) )
								$show_custom_select_fields = '#' . $meta_key;
							else
								$show_custom_select_fields .= ', #' . $meta_key;
						}
						if ( $meta_field['display'] == 'checkbox' ) {
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								if ( empty( $show_custom_checkbox_fields ) )
									$show_custom_checkbox_fields = '#' . $meta_key . '-' . $option . ', #' . $meta_key . '-' . $option . '-label';
								else
									$show_custom_checkbox_fields .= ', #' . $meta_key . '-' . $option . ', #' . $meta_key . '-' . $option . '-label';
							}
						}
						if ( $meta_field['display'] == 'radio' ) {
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								if ( empty( $show_custom_radio_fields ) )
									$show_custom_radio_fields = '#' . $meta_key . '-' . $option . ', #' . $meta_key . '-' . $option . '-label';
								else
									$show_custom_radio_fields .= ', #' . $meta_key . '-' . $option . ', #' . $meta_key . '-' . $option . '-label';
							}
						}
						if ( $meta_field['display'] == 'textarea' ) {
							if ( empty( $show_custom_textarea_fields ) )
								$show_custom_textarea_fields = '#' . $meta_key . '-label';
							else
								$show_custom_textarea_fields .= ', #' . $meta_key . '-label';
						}
						if ( !empty( $meta_field['require_on_registration'] ) ) {
							if ( empty( $required_meta_fields ) )
								$required_meta_fields = '#' . $meta_key;
							else
								$required_meta_fields .= ', #' . $meta_key;
						}
					}
				}

				if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && count( $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) $show_fields = '#' . implode( ', #', $register_plus_redux->GetReduxOption( 'show_fields' ) );
				if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && count( $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) $required_fields = '#' . implode( ', #', $register_plus_redux->GetReduxOption( 'required_fields' ) );

				echo "\n<style type=\"text/css\">";
				echo "\nsmall { display:block; margin-bottom:8px; }";
				if ( $register_plus_redux->GetReduxOption( 'default_css' ) == TRUE ) {
					if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) echo "\n#user_email2 { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( !empty( $show_fields ) ) echo "\n$show_fields { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) echo "\n#description { font-size:24px; height: 60px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( !empty( $show_custom_text_fields ) ) echo "\n$show_custom_text_fields { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( !empty( $show_custom_select_fields ) ) echo "\n$show_custom_select_fields { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( !empty( $show_custom_checkbox_fields ) ) echo "\n$show_custom_checkbox_fields { font-size:18px; }";
					if ( !empty( $show_custom_radio_fields ) ) echo "\n$show_custom_radio_fields { font-size:18px; }";
					if ( !empty( $show_custom_textarea_fields ) ) echo "\n$show_custom_textarea_fields { font-size:24px; height: 60px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( !empty( $show_custom_date_fields ) ) echo "\n$show_custom_date_fields { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) echo "\n#pass1, #pass2 { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:16px; border:1px solid #e5e5e5; background:#fbfbfb; }";
					if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) echo "\n#invitation_code { font-size:24px; width:100%; padding:3px; margin-top:2px; margin-right:6px; margin-bottom:4px; border:1px solid #e5e5e5; background:#fbfbfb; }";
				}
				if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE ) { echo "\n#disclaimer { font-size:12px; display: block; width: 100%; padding: 3px; margin-top:2px; margin-right:6px; margin-bottom:8px; background-color:#fff; border:solid 1px #A7A6AA; font-weight:normal;"; if ( strlen( $register_plus_redux->GetReduxOption( 'message_disclaimer' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
				if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE ) { echo "\n#license { font-size:12px; display: block; width: 100%; padding: 3px; margin-top:2px; margin-right:6px; margin-bottom:8px; background-color:#fff; border:solid 1px #A7A6AA; font-weight:normal;"; if ( strlen( $register_plus_redux->GetReduxOption( 'message_license' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
				if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) { echo "\n#privacy_policy { font-size:12px; display: block; width: 100%; padding: 3px; margin-top:2px; margin-right:6px; margin-bottom:8px; background-color:#fff; border:solid 1px #A7A6AA; font-weight:normal;"; if ( strlen( $register_plus_redux->GetReduxOption( 'message_privacy_policy' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
				if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE || $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE || $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) echo "\n.accept_check { display:block; margin-bottom:8px; }";
				if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
					echo "\n#reg_passmail { display: none; }";
					if ( $register_plus_redux->GetReduxOption( 'show_password_meter' ) == TRUE ) {
						echo "\n#pass-strength-result { width: 100%; margin-top: 2px; margin-right: 6px; margin-bottom: 6px; border: 1px solid; padding: 3px; text-align: center; font-weight: bold; display: block; }";
						echo "\n#pass-strength-result { background-color: #eee; border-color: #ddd !important; }";
						echo "\n#pass-strength-result.bad { background-color: #ffb78c; border-color: #ff853c !important; }";
						echo "\n#pass-strength-result.good { background-color: #ffec8b; border-color: #fc0 !important; }";
						echo "\n#pass-strength-result.short { background-color: #ffa0a0; border-color: #f04040 !important; }";
						echo "\n#pass-strength-result.strong { background-color: #c3ff88; border-color: #8dff1c !important; }";
					}
				}
				if ( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ) {
					echo "\n#user_login, #user_email { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), '} ';
					if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) echo "\n#user_email2 { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
					if ( !empty( $required_fields ) ) echo "\n$required_fields { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
					if ( !empty( $required_meta_fields ) ) echo "\n$required_meta_fields { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
					if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) echo "\n#pass1, #pass2 { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
					if ( $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) echo "\n#invitation_code { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
				}
				if ( $register_plus_redux->GetReduxOption( 'custom_registration_page_css' ) ) echo "\n", esc_html( $register_plus_redux->GetReduxOption( 'custom_registration_page_css' ) );
				echo "\n</style>";

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
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) {
					if ( empty( $jquery_loaded ) ) {
						wp_print_scripts( 'jquery' );
						$jquery_loaded = TRUE;
					}
					?>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery("#user_login").parent().prepend("*");
						jQuery("#user_email").parent().prepend("*");
					});
					</script>
					<?php
				}
				if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE && $register_plus_redux->GetReduxOption( 'show_password_meter' ) == TRUE ) {
					if ( empty( $jquery_loaded ) ) {
						wp_print_scripts( 'jquery' );
						$jquery_loaded = TRUE;
					}
					// TODO: Messages could be compromised, needs to be escaped, look into methods used by comments to display
					?>
					<script type="text/javascript">
						/* <![CDATA[ */
						pwsL10n={
							empty: "<?php echo $register_plus_redux->GetReduxOption( 'message_empty_password' ); ?>",
							short: "<?php echo $register_plus_redux->GetReduxOption( 'message_short_password' ); ?>",
							bad: "<?php echo $register_plus_redux->GetReduxOption( 'message_bad_password' ); ?>",
							good: "<?php echo $register_plus_redux->GetReduxOption( 'message_good_password' ); ?>",
							strong: "<?php echo $register_plus_redux->GetReduxOption( 'message_strong_password' ); ?>",
							mismatch: "<?php echo $register_plus_redux->GetReduxOption( 'message_mismatch_password' ); ?>"
						}
						/* ]]> */
						function check_pass_strength() {
							// HACK support username_is_email in function
							var user = jQuery("<?php if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE ) echo '#user_email'; else echo '#user_login'; ?>").val();
							var pass1 = jQuery("#pass1").val();
							var pass2 = jQuery("#pass2").val();
							var strength;
							jQuery("#pass-strength-result").removeClass("short bad good strong mismatch");
							if (!pass1) {
								jQuery("#pass-strength-result").html( pwsL10n.empty );
								return;
							}
							strength = passwordStrength(pass1, user, pass2);
							switch (strength) {
								case 2:
									jQuery("#pass-strength-result").addClass("bad").html( pwsL10n['bad'] );
									break;
								case 3:
									jQuery("#pass-strength-result").addClass("good").html( pwsL10n['good'] );
									break;
								case 4:
									jQuery("#pass-strength-result").addClass("strong").html( pwsL10n['strong'] );
									break;
								case 5:
									jQuery("#pass-strength-result").addClass("mismatch").html( pwsL10n['mismatch'] );
									break;
								default:
									jQuery("#pass-strength-result").addClass("short").html( pwsL10n['short'] );
							}
						}
						function passwordStrength(password1, username, password2) {
							// HACK support disable_password_confirmation in function
							password2 = typeof password2 !== 'undefined' ? password2 : '';
							var shortPass = 1, badPass = 2, goodPass = 3, strongPass = 4, mismatch = 5, symbolSize = 0, natLog, score;
							// password 1 != password 2
							if ((password1 != password2) && password2.length > 0)
								return mismatch
							// password < <?php echo absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ); ?> 
							if (password1.length < <?php echo absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ); ?>)
								return shortPass
							// password1 == username
							if (password1.toLowerCase() == username.toLowerCase())
								return badPass;
							if (password1.match(/[0-9]/))
								symbolSize +=10;
							if (password1.match(/[a-z]/))
								symbolSize +=26;
							if (password1.match(/[A-Z]/))
								symbolSize +=26;
							if (password1.match(/[^a-zA-Z0-9]/))
								symbolSize +=31;
							natLog = Math.log(Math.pow(symbolSize, password1.length));
								score = natLog / Math.LN2;
							if (score < 40)
								return badPass
							if (score < 56)
								return goodPass
							return strongPass;
						}
						jQuery(document).ready( function() {
							jQuery("#pass1").val("").keyup( check_pass_strength );
							jQuery("#pass2").val("").keyup( check_pass_strength );
						});
					</script>
					<?php
				}
			}
			else {
				if ( $register_plus_redux->GetReduxOption( 'custom_login_page_css' ) ) {
					echo "\n<style type=\"text/css\">";
					echo "\n", esc_html( $register_plus_redux->GetReduxOption( 'custom_login_page_css' ) );
					echo "\n</style>";
				}
			}
		}

		function rpr_login_foot_scripts() {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE ) {
				if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'register' ) ) {
					?>
					<!--[if (lte IE 8)]>
					<script type="text/javascript">
					document.getElementById("registerform").childNodes[0].style.display = "none";
					</script>
					<![endif]-->
					<!--[if (gt IE 8)|!(IE)]><!-->
					<script type="text/javascript">
					document.getElementById("registerform").childNodes[1].style.display = "none";
					</script>
					<!--<![endif]-->
					<?php
				} 
				elseif ( isset( $_GET['action'] ) && ( $_GET['action'] == 'lostpassword' ) ) {
					?>
					<!--[if (lte IE 8)]>
					<script type="text/javascript">
					document.getElementById("lostpasswordform").childNodes[0].childNodes[0].childNodes[0].nodeValue = "<?php _e( 'E-mail', 'register-plus-redux' ); ?>";
					</script>
					<![endif]-->
					<!--[if (gt IE 8)|!(IE)]><!-->
					<script type="text/javascript">
					document.getElementById("lostpasswordform").childNodes[1].childNodes[1].childNodes[0].nodeValue = "<?php _e( 'E-mail', 'register-plus-redux' ); ?>";
					</script>
					<!--<![endif]-->
					<?php
				}
				elseif ( !isset( $_GET['action'] ) ) {
					?>
					<!--[if (lte IE 8)]>
					<script type="text/javascript">
					document.getElementById("loginform").childNodes[0].childNodes[0].childNodes[0].nodeValue = "<?php _e( 'E-mail', 'register-plus-redux' ); ?>";
					</script>
					<![endif]-->
					<!--[if (gt IE 8)|!(IE)]><!-->
					<script type="text/javascript">
					document.getElementById("loginform").childNodes[1].childNodes[1].childNodes[0].nodeValue = "<?php _e( 'E-mail', 'register-plus-redux' ); ?>";
					</script>
					<!--<![endif]-->
					<?php
				}
			}
		}

		function filter_login_headerurl( $href ) {
			return home_url();
		}

		function filter_login_headertitle( $title ) {
			$desc = get_option( 'blogdescription' );
			if ( empty( $desc ) )
				$title = get_option( 'blogname' ) . ' - ' . $desc;
			else
				$title = get_option( 'blogname' );
			return $title;
		}

		function filter_password_reset( $allow, $user_id ) {
			global $wpdb;
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = 'stored_user_login';", $user_id ) ) ) $allow = FALSE;
			return $allow;
		}
	}
}

if ( class_exists( 'RPR_Login' ) ) $rpr_login = new RPR_Login();
?>