<?php
if ( !class_exists( 'RPR_Login' ) ) {
	class RPR_Login {
		function __construct() {
			add_action( 'register_form', array( $this, 'rpr_additional_registration_fields' ), 9, 1); // Higher priority to avoid getting bumped by other plugins
			add_action( 'user_register', array( $this, 'rpr_save_registration_fields' ), 10, 1 ); // Runs when a user's profile is first created. Action function argument: user ID. 
			add_filter( 'registration_errors', array( $this, 'rpr_check_registration' ), 10, 3 ); // applied to the list of registration errors generated while registering a user for a new account. 
			add_filter( 'login_message', array( $this, 'filter_login_message' ), 10, 1 );
			add_filter( 'registration_redirect', array( $this, 'filter_registration_redirect' ), 10, 1 );
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

		function rpr_save_registration_fields( $user_id ) {
			global $register_plus_redux;
			global $pagenow;

			$source = get_magic_quotes_gpc() ? stripslashes_deep( $_POST ) : $_POST;

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
			if ( $pagenow != 'user-new.php' && $register_plus_redux->GetReduxOption( 'autologin_user' ) == TRUE && $register_plus_redux->GetReduxOption( 'verify_user_email' ) == FALSE && $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == FALSE ) {
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

			if ( ( $pagenow != 'user-new.php' ) && ( $register_plus_redux->GetReduxOption( 'verify_user_email' ) == TRUE || $register_plus_redux->GetReduxOption( 'verify_user_admin' ) == TRUE ) ) {
				global $wpdb;
				$user_info = get_userdata( $user_id );
				update_user_meta( $user_id, 'stored_user_login', sanitize_text_field( $user_info->user_login ) );
				update_user_meta( $user_id, 'stored_user_password', sanitize_text_field( $plaintext_pass ) );
				$temp_user_login = 'unverified_' . wp_generate_password( 7, FALSE );
				$wpdb->update( $wpdb->users, array( 'user_login' => $temp_user_login ), array( 'ID' => $user_id ) );
			}
		}

		function rpr_check_registration( $errors, $sanitized_user_login, $user_email ) {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE )  {
				if ( is_array( $errors->errors ) && array_key_exists( 'empty_username', $errors->errors ) ) unset( $errors->errors['empty_username'] );
				if ( is_array( $errors->error_data ) && array_key_exists( 'empty_username', $errors->error_data ) ) unset( $errors->error_data['empty_username'] );
				$sanitized_user_login = sanitize_user( $user_email );
				if ( $sanitized_user_login != $user_email ) {
					$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: Email address is not appropriate as a username, please enter another email address.', 'register-plus-redux' ) );
				}
			}
			if ( !empty( $sanitized_user_login ) ) {
				global $wpdb;
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'stored_user_login' AND meta_value = %s;", $sanitized_user_login ) ) ) {
					$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.', 'register-plus-redux' ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) {
				if ( empty( $_POST['user_email2'] ) ) {
					$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please confirm your e-mail address.', 'register-plus-redux' ) );
				}
				elseif ( $_POST['user_email'] != $_POST['user_email2'] ) {
					$errors->add( 'email_mismatch', __( '<strong>ERROR</strong>: Your e-mail address does not match.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['first_name'] ) ) {
					$errors->add( 'empty_first_name', __( '<strong>ERROR</strong>: Please enter your first name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['last_name'] ) ) {
					$errors->add( 'empty_last_name', __( '<strong>ERROR</strong>: Please enter your last name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['user_url'] ) ) {
					$errors->add( 'empty_user_url', __( '<strong>ERROR</strong>: Please enter your website URL.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['aim'] ) ) {
					$errors->add( 'empty_aim', __( '<strong>ERROR</strong>: Please enter your AIM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'yahoo' , $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['yahoo'] ) ) {
					$errors->add( 'empty_yahoo', __( '<strong>ERROR</strong>: Please enter your Yahoo IM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['jabber'] ) ) {
					$errors->add( 'empty_jabber', __( '<strong>ERROR</strong>: Please enter your Jabber / Google Talk username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['description'] ) ) {
					$errors->add( 'empty_description', __( '<strong>ERROR</strong>: Please enter some information about yourself.', 'register-plus-redux' ) );
				}
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				$meta_key = $meta_field['meta_key'];
				if ( !empty( $meta_field['show_on_registration'] ) && !empty( $meta_field['require_on_registration'] ) && empty( $_POST[$meta_key] ) ) {
					$errors->add( 'empty_' . $meta_key, sprintf( __( '<strong>ERROR</strong>: Please enter a value for %s.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
				if ( !empty( $meta_field['show_on_registration'] ) && ( $meta_field['display'] == 'textbox' ) && !empty( $meta_field['options'] ) && !preg_match( $meta_field['options'], $_POST[$meta_key] ) ) {
					$errors->add( 'invalid_' . $meta_key, sprintf( __( '<strong>ERROR</strong>: Please enter new value for %s, value specified is not in the correct format.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				if ( empty( $_POST['pass1'] ) && $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE ) {
					$errors->add( 'empty_password', __( '<strong>ERROR</strong>: Please enter a password.', 'register-plus-redux' ) );
				}
				elseif ( strlen( $_POST['pass1'] ) < absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) {
					$errors->add( 'password_length', sprintf( __( '<strong>ERROR</strong>: Your password must be at least %d characters in length.', 'register-plus-redux' ), absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) );
				}
				elseif ( $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE && ( $_POST['pass1'] != $_POST['pass2'] ) ) {
					$errors->add( 'password_mismatch', __( '<strong>ERROR</strong>: Your password does not match.', 'register-plus-redux' ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
				if ( empty( $_POST['invitation_code'] ) && $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) {
					$errors->add( 'empty_invitation_code', __( '<strong>ERROR</strong>: Please enter an invitation code.', 'register-plus-redux' ) );
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
						$errors->add( 'invitation_code_mismatch', __( '<strong>ERROR</strong>: That invitation code is invalid.', 'register-plus-redux' ) );
					}
					else {
						// reverts lowercase key to stored case
						$key = array_search( $_POST['invitation_code'], $invitation_code_bank );
						$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
						$_POST['invitation_code'] = $invitation_code_bank[$key];
						if ( $register_plus_redux->GetReduxOption( 'invitation_code_unique' ) == TRUE ) {
							global $wpdb;
							if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'invitation_code' AND meta_value = %s;", $_POST['invitation_code'] ) ) ) {
								$errors->add( 'invitation_code_exists', __( '<strong>ERROR</strong>: This invitation code is already in use, please enter a unique invitation code.', 'register-plus-redux' ) );
							}
						}
					}
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_disclaimer_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_disclaimer'] ) ) {
					$errors->add( 'accept_disclaimer', sprintf( __( '<strong>ERROR</strong>: Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_license_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_license'] ) ) {
					$errors->add( 'accept_license', sprintf( __( '<strong>ERROR</strong>: Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_license_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_privacy_policy_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_privacy_policy'] ) ) {
					$errors->add( 'accept_privacy_policy' , sprintf( __( '<strong>ERROR</strong>: Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_title' ) ) ) . '.' );
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
	}
}

if ( class_exists( 'RPR_Login' ) ) $rpr_login = new RPR_Login();
?>