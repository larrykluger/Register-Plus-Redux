<?php
if ( !class_exists( 'RPR_Signup' ) ) {
	class RPR_Signup {
		function __construct() {
			add_action( 'signup_header', array( $this, 'rpr_signup_head_style_scripts' ), 10, 1 );
			add_action( 'signup_extra_fields', array( $this, 'rpr_additional_signup_fields' ), 9, 1 ); // Higher priority to avoid getting bumped by other plugins
			add_action( 'after_signup_form', array( $this, 'rpr_signup_foot_scripts' ), 10, 1 ); // Closest thing to signup_footer
			add_filter( 'wpmu_validate_user_signup', array( $this, 'filter_wpmu_validate_user_signup' ), 10, 1 );
			add_filter( 'add_signup_meta', array( $this, 'filter_add_signup_meta' ), 10, 1 ); // Store metadata until user is activated
			add_action( 'wpmu_activate_user', array( $this, 'rpr_restore_signup_fields' ), 10, 3 ); // Restore metadata to activated user's profile
			//add_action( 'wpmu_activate_blog', array( $this, 'rpr_restore_signup_fields_stub' ), 10, 5 );
		}

		function rpr_signup_head_style_scripts() {
			global $register_plus_redux;
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( !empty( $meta_field['show_on_registration'] ) ) {
					$meta_key = esc_attr( $meta_field['meta_key'] );
					if ( $meta_field['display'] == 'textbox' ) {
						if ( empty( $show_custom_text_fields ) )
							$show_custom_text_fields = '.mu_register #' . $meta_key;
						else
							$show_custom_text_fields .= ', .mu_register #' . $meta_key;
					}
					if ( $meta_field['display'] == 'select' ) {
						if ( empty( $show_custom_select_fields ) )
							$show_custom_select_fields = '.mu_register #' . $meta_key;
						else
							$show_custom_select_fields .= ', .mu_register #' . $meta_key;
					}
					if ( $meta_field['display'] == 'checkbox' ) {
						$field_options = explode( ',', $meta_field['options'] );
						foreach ( $field_options as $field_option ) {
							$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
							if ( empty( $show_custom_checkbox_fields ) )
								$show_custom_checkbox_fields = '.mu_register #' . $meta_key . '-' . $option . ', .mu_register #' . $meta_key . '-' . $option . '-label';
							else
								$show_custom_checkbox_fields .= ', .mu_register #' . $meta_key . '-' . $option . ', .mu_register #' . $meta_key . '-' . $option . '-label';
						}
					}
					if ( $meta_field['display'] == 'radio' ) {
						$field_options = explode( ',', $meta_field['options'] );
						foreach ( $field_options as $field_option ) {
							$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
							if ( empty( $show_custom_radio_fields ) )
								$show_custom_radio_fields = '.mu_register #' . $meta_key . '-' . $option . ', .mu_register #' . $meta_key . '-' . $option . '-label';
							else
								$show_custom_radio_fields .= ', .mu_register #' . $meta_key . '-' . $option . ', .mu_register #' . $meta_key . '-' . $option . '-label';
						}
					}
					if ( $meta_field['display'] == 'textarea' ) {
						if ( empty( $show_custom_textarea_fields ) )
							$show_custom_textarea_fields = '.mu_register #' . $meta_key . '-label';
						else
							$show_custom_textarea_fields .= ', .mu_register #' . $meta_key . '-label';
					}
					if ( !empty( $meta_field['require_on_registration'] ) ) {
						if ( empty( $required_meta_fields ) )
							$required_meta_fields = '.mu_register #' . $meta_key;
						else
							$required_meta_fields .= ', .mu_register #' . $meta_key;
					}
				}
			}

			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && count( $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) $show_fields = '.mu_register #' . implode( ', .mu_register #', $register_plus_redux->GetReduxOption( 'show_fields' ) );
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && count( $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) $required_fields = '.mu_register #' . implode( ', .mu_register #', $register_plus_redux->GetReduxOption( 'required_fields' ) );

			echo "\n<style type=\"text/css\">";
			if ( $register_plus_redux->GetReduxOption( 'default_css' ) == TRUE ) {
				if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) echo "\n.mu_register #user_email2 { width:100%; font-size: 24px; margin:5px 0; }";
				if ( !empty( $show_fields ) ) echo "\n$show_fields { width:100%; font-size: 24px; margin:5px 0; }";
				if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) echo "\n.mu_register #description { width:100%; font-size:24px; height: 60px; margin:5px 0; }";
				if ( !empty( $show_custom_text_fields ) ) echo "\n$show_custom_text_fields { width:100%; font-size: 24px; margin:5px 0; }";
				if ( !empty( $show_custom_select_fields ) ) echo "\n$show_custom_select_fields { width:100%; font-size:24px; margin:5px 0; }";
				if ( !empty( $show_custom_checkbox_fields ) ) echo "\n$show_custom_checkbox_fields { width:100%; font-size:18px; margin:5px 0; }";
				if ( !empty( $show_custom_radio_fields ) ) echo "\n$show_custom_radio_fields { width:100%; font-size:18px; margin:5px 0; }";
				if ( !empty( $show_custom_textarea_fields ) ) echo "\n$show_custom_textarea_fields { width:100%; font-size:24px; height: 60px; margin:5px 0; }";
				if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) echo "\n.mu_register #pass1, .mu_register #pass2 { width:100%; font-size: 24px; margin:5px 0; }";
				if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) echo "\n.mu_register #invitation_code { width:100%; font-size: 24px; margin:5px 0; }";
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE ) { echo "\n.mu_register #disclaimer { width: 100%; font-size:12px; margin:5px 0; display: block; "; if ( strlen( $register_plus_redux->GetReduxOption( 'message_disclaimer' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE ) { echo "\n.mu_register #license { width: 100%; font-size:12px; margin:5px 0; display: block; "; if ( strlen( $register_plus_redux->GetReduxOption( 'message_license' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) { echo "\n.mu_register #privacy_policy { width: 100%; font-size:12px; margin:5px 0; display: block; "; if ( strlen( $register_plus_redux->GetReduxOption( 'message_license' ) ) > 525) echo 'height: 160px; overflow:scroll;'; echo ' }'; }
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE || $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE || $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) echo "\n.mu_register .accept_check { display:block; margin:5px 0; }";
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				if ( $register_plus_redux->GetReduxOption( 'show_password_meter' ) == TRUE ) {
					echo "\n.mu_register #pass-strength-result { width: 100%; margin: 5px 0; border: 1px solid; padding: 6px; text-align: center; font-weight: bold; display: block; }";
					echo "\n.mu_register #pass-strength-result { background-color: #eee; border-color: #ddd !important; }";
					echo "\n.mu_register #pass-strength-result.bad { background-color: #ffb78c; border-color: #ff853c !important; }";
					echo "\n.mu_register #pass-strength-result.good { background-color: #ffec8b; border-color: #fc0 !important; }";
					echo "\n.mu_register #pass-strength-result.short { background-color: #ffa0a0; border-color: #f04040 !important; }";
					echo "\n.mu_register #pass-strength-result.strong { background-color: #c3ff88; border-color: #8dff1c !important; }";
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ) {
				echo "\n.mu_register #user_login, .mu_register #user_email { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), '} ';
				if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) echo "\n.mu_register #user_email2 { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
				if ( !empty( $required_fields ) ) echo "\n$required_fields { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ) , ' }';
				if ( !empty( $required_meta_fields ) ) echo "\n$required_meta_fields { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ) , ' }';
				if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) echo "\n.mu_register #pass1, .mu_register #pass2 { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
				if ( $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) echo "\n.mu_register #invitation_code { ", esc_html( $register_plus_redux->GetReduxOption( 'required_fields_style' ) ), ' }';
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

			if ( $register_plus_redux->GetReduxOption( 'custom_login_page_css' ) ) {
				echo "\n<style type=\"text/css\">";
				echo "\n", esc_html( $register_plus_redux->GetReduxOption( 'custom_login_page_css' ) );
				echo "\n</style>";
			}
		}

		function rpr_additional_signup_fields( $errors ) {
			global $register_plus_redux;
			if ( get_magic_quotes_gpc() ) $_POST = stripslashes_deep( $_POST );
			if ( get_magic_quotes_gpc() ) $_GET = stripslashes_deep( $_GET );
			if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) {
				$user_email2 = isset( $_POST['user_email2'] ) ? $_POST['user_email2'] : '';
				if ( isset( $_GET['user_email2'] ) ) $user_email2 = $_GET['user_email2'];
				echo "\n\t\t<label id=\"user_email2-label\" for=\"user_email2\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
				echo __( 'Confirm E-mail', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('user_email2') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" autocomplete=\"off\" name=\"user_email2\" id=\"user_email2\" value=\"", esc_attr( $user_email2 ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$first_name = isset( $_POST['first_name'] ) ? $_POST['first_name'] : '';
				if ( isset( $_GET['first_name'] ) ) $first_name = $_GET['first_name'];
				echo "\n\t\t<label id=\"first_name-label\" for=\"first_name\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'First Name', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('first_name') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"first_name\" id=\"first_name\" value=\"", esc_attr( $first_name ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$last_name = isset( $_POST['last_name'] ) ? $_POST['last_name'] : '';
				if ( isset( $_GET['last_name'] ) ) $last_name = $_GET['last_name'];
				echo "\n\t\t<label id=\"last_name-label\" for=\"last_name\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Last Name', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('last_name') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"last_name\" id=\"last_name\" value=\"", esc_attr( $last_name ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$user_url = isset( $_POST['user_url'] ) ? $_POST['user_url'] : '';
				if ( isset( $_GET['user_url'] ) ) $user_url = $_GET['user_url'];
				echo "\n\t\t<label id=\"user_url-label\" for=\"user_url\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Website', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('user_url') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"url\" id=\"user_url\" value=\"", esc_attr( $user_url ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$aim = isset( $_POST['aim'] ) ? $_POST['aim'] : '';
				if ( isset( $_GET['aim'] ) ) $aim = $_GET['aim'];
				echo "\n\t\t<label id=\"aim-label\" for=\"aim\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'AIM', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('aim') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"aim\" id=\"aim\" value=\"", esc_attr( $aim ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$yahoo = isset( $_POST['yahoo'] ) ? $_POST['yahoo'] : '';
				if ( isset( $_GET['yahoo'] ) ) $yahoo = $_GET['yahoo'];
				echo "\n\t\t<label id=\"yahoo-label\" for=\"yahoo\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Yahoo IM', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('yahoo') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"yahoo\" id=\"yahoo\" value=\"", esc_attr( $yahoo ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$jabber = isset( $_POST['jabber'] ) ? $_POST['jabber'] : '';
				if ( isset( $_GET['jabber'] ) ) $jabber = $_GET['jabber'];
				echo "\n\t\t<label id=\"jabber-label\" for=\"jabber\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'Jabber / Google Talk', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('jabber') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"jabber\" id=\"jabber\" value=\"", esc_attr( $jabber ), '" />';
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'show_fields' ) ) ) {
				$description = isset( $_POST['description'] ) ? $_POST['description'] : '';
				if ( isset( $_GET['description'] ) ) $description = $_GET['description'];
				echo "\n\t\t<label id=\"description-label\" for=\"description\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) echo '*';
				echo __( 'About Yourself', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('description') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<textarea name=\"description\" id=\"description\" cols=\"25\" rows=\"5\">", esc_textarea( $description ), '</textarea>';
				echo '<br />', __( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'register-plus-redux' );
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				if ( !empty( $meta_field['show_on_registration'] ) ) {
					$meta_key = esc_attr( $meta_field['meta_key'] );
					$value = isset( $_POST[$meta_key] ) ? $_POST[$meta_key] : '';
					if ( isset( $_GET[$meta_key] ) ) $value = $_GET[$meta_key];
					if ( ( $meta_field['display'] != 'hidden' ) && ( $meta_field['display'] != 'text' ) ) {
						echo "\n\t\t<label id=\"$meta_key-label\" for=\"$meta_key\">";
						if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && !empty( $meta_field['require_on_registration'] ) ) echo '*';
						echo esc_html( $meta_field['label'] ), ':</label>';
						if ( $errmsg = $errors->get_error_message($meta_key) ) {
							echo '<p class="error">'.$errmsg.'</p>';
						}
					}
					switch ( $meta_field['display'] ) {
						case 'textbox':
							echo "\n\t\t<input type=\"text\" name=\"", $meta_key, '" id="', $meta_key, '" ';
							if ( $meta_field['show_datepicker'] == TRUE ) echo 'class="datepicker" ';
							echo 'value="', esc_attr( $value ), '" />';
							break;
						case 'select':
							echo "\n\t\t<select name=\"", $meta_key, '" id="', $meta_key, '">';
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo "n\t\t\t<option id=\"", $meta_key, '-', $option, '" value="', $option, '"';
								if ( $value == $option ) echo ' selected="selected"';
								echo '>', esc_html( $field_option ), '</option>';
							}
							echo "n\t\t</select>";
							break;
						case 'checkbox':
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo "\n\t\t<input type=\"checkbox\" name=\"", $meta_key, '[]" id="', $meta_key, '-', $option, '" value="', $option, '" ';
								if ( is_array( $value ) && in_array( $option, $value ) ) echo 'checked="checked" ';
								if ( !is_array( $value ) && ( $value == $option) ) echo 'checked="checked" ';
								echo '><label id="', $meta_key, '-', $option, '-label" class="', $meta_key, '" for="', $meta_key, '-', $option, '">&nbsp;', esc_html( $field_option ), '</label>';
							}
							break;
						case 'radio':
							$field_options = explode( ',', $meta_field['options'] );
							foreach ( $field_options as $field_option ) {
								$option = esc_attr( $register_plus_redux->cleanupText( $field_option ) );
								echo "\n\t\t<input type=\"radio\" name=\"", $meta_key, '" id="', $meta_key, '-', $option, '" value="', $option, '" ';
								if ( $value == $option ) echo 'checked="checked" ';
								echo '><label id="', $meta_key, '-', $option, '-label" class="', $meta_key, '" for="', $meta_key, '-', $option, '">&nbsp;', esc_html( $field_option ), '</label>';
							}
							break;
						case 'textarea':
							echo "\n\t\t<textarea name=\"", $meta_key, '" id="', $meta_key, '" cols="25" rows="5">', esc_textarea( $value ), '</textarea>';
							break;
						case 'hidden':
							echo "\n\t\t<input type=\"hidden\" name=\"", $meta_key, '" id="', $meta_key, '" value="', esc_attr( $value ), '" />';
							break;
						case 'text':
							echo "\n\t\t", esc_html( $meta_field['label'] );
							break;
					}
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
				if ( isset( $_GET['password'] ) ) $password = $_GET['password'];
				echo "\n\t\t<label id=\"pass1-label\" for=\"pass1-label\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
				echo __( 'Password', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('pass1') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"password\" autocomplete=\"off\" name=\"pass1\" id=\"pass1\" value=\"", esc_attr( $password ), '" />';
				if ( $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE ) {
					echo "\n\t\t<label id=\"pass2-label\" for=\"pass2-label\">";
					if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE ) echo '*';
					echo __( 'Confirm Password', 'register-plus-redux' ), ':</label>';
					if ( $errmsg = $errors->get_error_message('pass2') ) {
						echo '<p class="error">'.$errmsg.'</p>';
					}
					echo "\n\t\t<input type=\"password\" autocomplete=\"off\" name=\"pass2\" id=\"pass2\" value=\"", esc_attr( $password ), '" />';
				}
				if ( $register_plus_redux->GetReduxOption( 'show_password_meter' ) == TRUE ) {
					echo "\n\t\t<div id=\"pass-strength-result\">", $register_plus_redux->GetReduxOption( 'message_empty_password' ), '</div>';
					echo "\n\t\t<p id=\"pass_strength_msg\" style=\"display: inline;\">", sprintf(__( 'Your password must be at least %d characters long. To make your password stronger, use upper and lower case letters, numbers, and the following symbols !@#$%%^&amp;*()', 'register-plus-redux' ), absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ), '</p>';
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
				$invitation_code = isset( $_POST['invitation_code'] ) ? $_POST['invitation_code'] : '';
				if ( isset( $_GET['invitation_code'] ) ) $invitation_code = $_GET['invitation_code'];
				echo "\n\t\t<label id=\"invitation_code-label\" for=\"invitation_code\">";
				if ( $register_plus_redux->GetReduxOption( 'required_fields_asterisk' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) echo '*';
				echo __( 'Invitation Code', 'register-plus-redux' ), ':</label>';
				if ( $errmsg = $errors->get_error_message('invitation_code') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
				echo "\n\t\t<input type=\"text\" name=\"invitation_code\" id=\"invitation_code\" value=\"", esc_attr( $invitation_code ), '" />';
				if ( $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE )
					echo "\n\t\t<p id=\"invitation_code_msg\" style=\"display: inline;\">", __( 'This website is currently closed to public registrations. You will need an invitation code to register.', 'register-plus-redux' ), '</p>';
				else
					echo "\n\t\t<p id=\"invitation_code_msg\" style=\"display: inline;\">", __( 'Have an invitation code? Enter it here. (This is not required)', 'register-plus-redux' ), '</p>';
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE ) {
				$accept_disclaimer = isset( $_POST['accept_disclaimer'] ) ? '1' : '0';
				if ( isset( $_GET['accept_disclaimer'] ) ) $accept_disclaimer = '1';
				echo "\n\t\t<label id=\"disclaimer-label\" for=\"disclaimer\">", esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_title' ) ), ':</label>';
				echo "\n\t\t<div name=\"disclaimer\" id=\"disclaimer\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_disclaimer' ) ), '</div>';
				if ( $register_plus_redux->GetReduxOption( 'require_disclaimer_agree' ) == TRUE ) {
					echo "\n\t\t<label id=\"accept_disclaimer-label\"><input type=\"checkbox\" name=\"accept_disclaimer\" id=\"accept_disclaimer\" value=\"1\""; if ( $accept_disclaimer ) echo ' checked="checked" />&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_agree' ) ), '</label>';
				}
				if ( $errmsg = $errors->get_error_message('disclaimer') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE ) {
				$accept_license = isset( $_POST['accept_license'] ) ? '1' : '0';
				if ( isset( $_GET['accept_license'] ) ) $accept_license = '1';
				echo "\n\t\t<label id=\"license-label\" for=\"license\">", esc_html( $register_plus_redux->GetReduxOption( 'message_license_title' ) ), ':</label>';
				echo "\n\t\t<div name=\"license\" id=\"license\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_license' ) ), '</div>';
				if ( $register_plus_redux->GetReduxOption( 'require_license_agree' ) == TRUE ) {
					echo "\n\t\t<label id=\"accept_license-label\"><input type=\"checkbox\" name=\"accept_license\" id=\"accept_license\" value=\"1\""; if ( $accept_license ) echo ' checked="checked" />&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_license_agree' ) ), '</label>';
				}
				if ( $errmsg = $errors->get_error_message('license') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE ) {
				$accept_privacy_policy = isset( $_POST['accept_privacy_policy'] ) ? '1' : '0';
				if ( isset( $_GET['accept_privacy_policy'] ) ) $accept_privacy_policy = '1';
				echo "\n\t\t<label id=\"privacy_policy-label\" for=\"privacy_policy\">", esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_title' ) ), ':</label>';
				echo "\n\t\t<div name=\"privacy_policy\" id=\"privacy_policy\" style=\"display: inline;\">", nl2br( $register_plus_redux->GetReduxOption( 'message_privacy_policy' ) ), '</div>';
				if ( $register_plus_redux->GetReduxOption( 'require_privacy_policy_agree' ) == TRUE ) {
					echo "\n\t\t<label id=\"accept_privacy_policy-label\"><input type=\"checkbox\" name=\"accept_privacy_policy\" id=\"accept_privacy_policy\" value=\"1\""; if ( $accept_privacy_policy ) echo ' checked="checked" />&nbsp;', esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_agree' ) ), '</label>';
				}
				if ( $errmsg = $errors->get_error_message('privacy_policy') ) {
					echo '<p class="error">'.$errmsg.'</p>';
				}
			}
		}

		function rpr_signup_foot_scripts() {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE ) {
				?>
				<!--[if (lte IE 8)]>
				<script type="text/javascript">
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[5]);
				document.getElementById("setupform").childNodes[5].style.display = "none";
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[6]);
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[6]);
				</script>
				<![endif]-->
				<!--[if (gt IE 8)|!(IE)]><!-->
				<script type="text/javascript">
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[6]);
				document.getElementById("setupform").childNodes[6].style.display = "none";
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[7]);
				document.getElementById("setupform").removeChild(document.getElementById("setupform").childNodes[7]);
				</script>
				<!--<![endif]-->
				<?php
			}
		}

		function filter_wpmu_validate_user_signup( $result ) {
			global $register_plus_redux;
			if ( $register_plus_redux->GetReduxOption( 'username_is_email' ) == TRUE ) {
				global $wpdb;

				if ( is_array( $result['errors']->errors ) && array_key_exists( 'user_name', $result['errors']->errors ) ) unset( $result['errors']->errors['user_name'] );
				if ( is_array( $result['errors']->error_data ) && array_key_exists( 'user_name', $result['errors']->error_data ) ) unset( $result['errors']->error_data['user_name'] );

				$result['user_name'] = $result['user_email'];
				$result['orig_username'] = $result['user_email'];

				// Check if the username has been used already.
				if ( username_exists( $result['user_name'] ) )
					$result['errors']->add('user_email', __('Sorry, that username already exists!'));

				// Has someone already signed up for this username?
				$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE user_login = %s", $result['user_name'] ) );
				if ( !empty( $signup ) ) {
					$registered_at =  mysql2date('U', $signup->registered);
					$now = current_time( 'timestamp', true );
					$diff = $now - $registered_at;
					// If registered more than two days ago, cancel registration and let this signup go through.
					if ( $diff > 172800 )
						$wpdb->delete( $wpdb->signups, array( 'user_login' => $result['user_name'] ) );
					else
						$result['errors']->add('user_email', __('That username is currently reserved but may be available in a couple of days.'));

					if ( $signup->active == 0 && $signup->user_email == $result['user_email'] )
						$result['errors']->add('user_email_used', __('username and email used'));
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'double_check_email' ) == TRUE ) {
				if ( empty( $_POST['user_email2'] ) ) {
					$result['errors']->add( 'user_email2', __( 'Please confirm your e-mail address.', 'register-plus-redux' ) );
				}
				elseif ( $_POST['user_email'] != $_POST['user_email2'] ) {
					$result['errors']->add( 'user_email2', __( 'Your e-mail address does not match.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['first_name'] ) ) {
					$result['errors']->add( 'first_name', __( 'Please enter your first name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['last_name'] ) ) {
					$result['errors']->add( 'last_name', __( 'Please enter your last name.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['user_url'] ) ) {
					$result['errors']->add( 'user_url', __( 'Please enter your website URL.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['aim'] ) ) {
					$result['errors']->add( 'aim', __( 'Please enter your AIM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'yahoo', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['yahoo'] ) ) {
					$result['errors']->add( 'yahoo', __( 'Please enter your Yahoo IM username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['jabber'] ) ) {
					$result['errors']->add( 'jabber', __( 'Please enter your Jabber / Google Talk username.', 'register-plus-redux' ) );
				}
			}
			if ( is_array( $register_plus_redux->GetReduxOption( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->GetReduxOption( 'required_fields' ) ) ) {
				if ( empty( $_POST['description'] ) ) {
					$result['errors']->add( 'description', __( 'Please enter some information about yourself.', 'register-plus-redux' ) );
				}
			}
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
			foreach ( $redux_usermeta as $index => $meta_field ) {
				$meta_key = $meta_field['meta_key'];
				if ( !empty( $meta_field['show_on_registration'] ) && !empty( $meta_field['require_on_registration'] ) && empty( $_POST[$meta_key] ) ) {
					$result['errors']->add( $meta_key, sprintf( __( 'Please enter a value for %s.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
				if ( !empty( $meta_field['show_on_registration'] ) && ( $meta_field['display'] == 'textbox' ) && !empty( $meta_field['options'] ) && !preg_match( $meta_field['options'], $_POST[$meta_key] ) ) {
					$result['errors']->add( $meta_key, sprintf( __( 'Please enter new value for %s, value specified is not in the correct format.', 'register-plus-redux' ), $meta_field['label'] ) );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'user_set_password' ) == TRUE ) {
				if ( empty( $_POST['pass1'] ) ) {
					$result['errors']->add( 'pass1', __( 'Please enter a password.', 'register-plus-redux' ) );
				}
				elseif ( strlen( $_POST['pass1'] ) < absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) {
					$result['errors']->add( 'pass1', sprintf( __( 'Your password must be at least %d characters in length.', 'register-plus-redux' ), absint( $register_plus_redux->GetReduxOption( 'min_password_length' ) ) ) );
				}
				elseif ( $register_plus_redux->GetReduxOption( 'disable_password_confirmation' ) == FALSE && ( $_POST['pass1'] != $_POST['pass2'] ) ) {
					$result['errors']->add( 'pass1', __( 'Your password does not match.', 'register-plus-redux' ) );
				}
				else {
					$_POST['password'] = $_POST['pass1'];
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'enable_invitation_code' ) == TRUE ) {
				if ( empty( $_POST['invitation_code'] ) && $register_plus_redux->GetReduxOption( 'require_invitation_code' ) == TRUE ) {
					$result['errors']->add( 'invitation_code', __( 'Please enter an invitation code.', 'register-plus-redux' ) );
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
						$result['errors']->add( 'invitation_code', __( 'That invitation code is invalid.', 'register-plus-redux' ) );
					}
					else {
						// reverts lowercase key to stored case
						$key = array_search( $_POST['invitation_code'], $invitation_code_bank );
						$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
						$_POST['invitation_code'] = $invitation_code_bank[$key];
						if ( $register_plus_redux->GetReduxOption( 'invitation_code_unique' ) == TRUE ) {
							global $wpdb;
							if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'invitation_code' AND meta_value = %s;", $_POST['invitation_code'] ) ) ) {
								$result['errors']->add( 'invitation_code', __( 'This invitation code is already in use, please enter a unique invitation code.', 'register-plus-redux' ) );
							}
						}
					}
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_disclaimer' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_disclaimer_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_disclaimer'] ) ) {
					$result['errors']->add( 'disclaimer', sprintf( __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_disclaimer_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_license' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_license_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_license'] ) ) {
					$result['errors']->add( 'license', sprintf( __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_license_title' ) ) ) . '.' );
				}
			}
			if ( $register_plus_redux->GetReduxOption( 'show_privacy_policy' ) == TRUE && $register_plus_redux->GetReduxOption( 'require_privacy_policy_agree' ) == TRUE ) {
				if ( empty( $_POST['accept_privacy_policy'] ) ) {
					$result['errors']->add( 'privacy_policy', sprintf( __( 'Please accept the %s', 'register-plus-redux' ), esc_html( $register_plus_redux->GetReduxOption( 'message_privacy_policy_title' ) ) ) . '.' );
				}
			}
			return $result;
		}

		function filter_add_signup_meta( $meta ) {
			$meta['signup_http_referer'] = $_SERVER['HTTP_REFERER'];
			$meta['signup_registered_from_ip'] = $_SERVER['REMOTE_ADDR'];
			$meta['signup_registered_from_host'] = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
			foreach ( $_POST as $key => $value )
				$meta[$key] = $value;
			return $meta;
		}

		//TODO: Create rpr-activate.php and move
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

if ( class_exists( 'RPR_Signup' ) ) $rpr_signup = new RPR_Signup();
?>