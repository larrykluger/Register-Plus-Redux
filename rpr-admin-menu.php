<?php
if ( !class_exists( 'RPR_Admin_Menu' ) ) {
	class RPR_Admin_Menu {
		function __construct() {
			global $register_plus_redux;
			global $wp_version;
			if ( $wp_version < 3.2 )
				add_action( 'admin_notices', array( $this, 'rpr_version_warning' ), 10, 0 ); // Runs after the admin menu is printed to the screen.
			if ( is_multisite() && !$register_plus_redux->rpr_is_network_activated() )
				add_action( 'admin_notices', array( $this, 'rpr_network_activate_warning' ), 10, 0 ); // Runs after the admin menu is printed to the screen.

			add_action( 'admin_menu', array( $this, 'rpr_admin_menu' ), 10, 0 );
		}

		function rpr_version_warning() {
			global $wp_version;
			global $pagenow;
			if ( $pagenow == 'plugins.php' || ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && ( $_GET['page'] == 'register-plus-redux' ) ) )
				echo '<div id="register-plus-redux-warning" class="updated"><p><strong>', sprintf( __( 'Register Plus Redux requires WordPress 3.2 or greater. You are currently using WordPress %s, please upgrade WordPress or deactivate Register Plus Redux.', 'register-plus-redux' ), $wp_version ), '</strong></p></div>', "\n";
		}

		function rpr_network_activate_warning() {
			//TODO: Write up network activation and edit link here
			global $pagenow;
			if ( $pagenow == 'plugins.php' || ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && ( $_GET['page'] == 'register-plus-redux' ) ) )
				echo '<div id="register-plus-redux-warning" class="updated"><p><strong>', sprintf( __( 'Register Plus Redux must be Network Activated by Super Admin under WordPress Multisite. You will have limited functionality while not Network Activated. Please refer to <a href="%s">radiok.info</a> for help resolving this issue.', 'register-plus-redux' ), 'http://radiok.info/' ), '</strong></p></div>', "\n";
		}

		function rpr_new_user_notification_warning() {
			global $pagenow;
			if ( $pagenow == 'plugins.php' || ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && ( $_GET['page'] == 'register-plus-redux' ) ) )
				echo '<div id="register-plus-redux-warning" class="updated"><p><strong>', sprintf( __( 'There is another active plugin that is conflicting with Register Plus Redux. The conflicting plugin is creating its own wp_new_user_notification function, this function is used to alter the messages sent out following the creation of a new user. Please refer to <a href="%s">radiok.info</a> for help resolving this issue.', 'register-plus-redux' ), 'http://radiok.info/blog/wp_new_user_notification-conflicts/' ), '</strong></p></div>', "\n";
		}

		function rpr_admin_menu() {
			global $register_plus_redux;
			global $wpdb;
			$admin_menu_hook = array( $this, 'rpr_options_submenu' );
			//if ( is_multisite() ) $admin_menu_hook = array( $this, 'rpr_options_submenu_ms' );
			$hookname = add_submenu_page( 'options-general.php', __( 'Register Plus Redux Settings', 'register-plus-redux' ), 'Register Plus Redux', 'manage_options', 'register-plus-redux', $admin_menu_hook );
			// NOTE: $hookname = settings_page_register-plus-redux 
			add_action( 'admin_print_scripts-' . $hookname, array( $this, 'rpr_options_submenu_scripts' ), 10, 1 );
			add_action( 'admin_print_styles-' . $hookname, array( $this, 'rpr_options_submenu_styles' ), 10, 1 );
			add_action( 'admin_footer-' . $hookname, array( $this, 'rpr_options_submenu_footer' ), 10, 1 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'rpr_filter_plugin_action_links' ), 10, 4 );
			if ( ( $register_plus_redux->rpr_get_option( 'verify_user_email' ) == TRUE ) || ( $register_plus_redux->rpr_get_option( 'verify_user_admin' ) == TRUE) || $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'stored_user_login';" ) )
				add_submenu_page( 'users.php', __( 'Unverified Users', 'register-plus-redux' ), __( 'Unverified Users', 'register-plus-redux' ), 'promote_users', 'unverified-users', array( $this, 'rpr_users_submenu' ) );
		}

		function rpr_filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
			// before other links
			array_unshift( $actions, '<a href="options-general.php?page=register-plus-redux">' . __( 'Settings', 'register-plus-redux' ) . '</a>' );
			// ... or after other links
			//$links[] = '<a href="options-general.php?page=register-plus-redux">' . __( 'Settings', 'register-plus-redux' ) . '</a>';
			return $actions;
		}

		function rpr_options_submenu_scripts() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			if ( !is_multisite() ) wp_enqueue_script( 'media-upload' );
			if ( !is_multisite() ) wp_enqueue_script( 'thickbox' );
		}

		function rpr_options_submenu_styles() {
			if ( !is_multisite() ) wp_enqueue_style( 'thickbox' );
		}

		function rpr_options_submenu() {
			global $register_plus_redux;
			if ( isset( $_POST['update_settings'] ) ) {
				check_admin_referer( 'register-plus-redux-update-settings' );
				$this->update_settings();
				echo '<div id="message" class="updated"><p><strong>', __( 'Settings Saved', 'register-plus-redux' ), '</strong></p></div>', "\n";
			}
			?>
			<div class="wrap">
			<h2><?php _e( 'Register Plus Redux Settings', 'register-plus-redux' ) ?></h2>
			<form enctype="multipart/form-data" method="post">
				<?php wp_nonce_field( 'register-plus-redux-update-settings' ); ?>
				<table class="form-table">
					<?php if ( !is_multisite() ) { ?>
					<tr valign="top">
						<th scope="row"><?php _e( 'Custom Logo URL', 'register-plus-redux' ); ?></th>
						<td>
							<input type="text" name="custom_logo_url" id="custom_logo_url" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'custom_logo_url' ) ); ?>" style="width: 60%;" /><input type="button" class="button" name="upload_custom_logo_button" id="upload_custom_logo_button" value="<?php esc_attr_e( 'Upload Image', 'register-plus-redux' ); ?>" /><br />
							<?php _e( 'Custom Logo will be shown on Registration and Login Forms in place of the default Wordpress logo. For the best results custom logo should not exceed 350px width.', 'register-plus-redux' ); ?>
							<?php if ( $register_plus_redux->rpr_get_option( 'custom_logo_url' ) ) { ?>
								<br /><img src="<?php echo esc_url( $register_plus_redux->rpr_get_option( 'custom_logo_url' ) ); ?>" /><br />
								<?php if ( ini_get( 'allow_url_fopen' ) ) list( $custom_logo_width, $custom_logo_height ) = getimagesize( esc_url( $register_plus_redux->rpr_get_option( 'custom_logo_url' ) ) ); ?>
								<?php if ( ini_get( 'allow_url_fopen' ) ) echo $custom_logo_width, 'x', $custom_logo_height, '<br />', "\n"; ?>
								<label><input type="checkbox" name="remove_logo" value="1" />&nbsp;<?php _e( 'Remove Logo', 'register-plus-redux' ); ?></label><br />
								<?php _e( 'You must Save Changes to remove logo.', 'register-plus-redux' ); ?>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
					<tr valign="top">
						<th scope="row"><?php _e( 'Email Verification', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="verify_user_email" id="verify_user_email" class="showHideSettings" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'verify_user_email' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Verify all new users email address...', 'register-plus-redux' ); ?></label><br />
							<?php _e( 'A verification code will be sent to any new users email address, new users will not be able to login or reset their password until they have completed the verification process. Administrators may authorize new users from the Unverified Users Page at their own discretion.', 'register-plus-redux' ); ?>
							<div id="verify_user_email_settings"<?php if ( $register_plus_redux->rpr_get_option( 'verify_user_email' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<br /><?php _e( 'The following message will be shown to users after registering. You may include HTML in this message.', 'register-plus-redux' ); ?><br />
								<textarea name="message_verify_user_email" rows="2" style="width: 60%; display: block;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'message_verify_user_email' ) ); ?></textarea>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Admin Verification', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="verify_user_admin" id="verify_user_admin" class="showHideSettings" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'verify_user_admin' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Moderate all new user registrations...', 'register-plus-redux' ); ?></label><br />
							<?php _e( 'New users will not be able to login or reset their password until they have been authorized by an administrator from the Unverified Users Page. If both verification options are enabled, users will not be able to login until an administrator authorizes them, regardless of whether they complete the email verification process.', 'register-plus-redux' ); ?>
							<div id="verify_user_admin_settings"<?php if ( $register_plus_redux->rpr_get_option( 'verify_user_admin' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<br /><?php _e( 'The following message will be shown to users after registering (or verifying their email if both verification options are enabled). You may include HTML in this message.', 'register-plus-redux' ); ?><br />
								<textarea name="message_verify_user_admin" rows="2" style="width: 60%; display: block;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'message_verify_user_admin' ) ); ?></textarea>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Grace Period', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="text" name="delete_unverified_users_after" id="delete_unverified_users_after" style="width:50px;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'delete_unverified_users_after' ) ); ?>" />&nbsp;<?php _e( 'days', 'register-plus-redux' ); ?></label><br />
							<?php _e( 'All unverified users will automatically be deleted after the Grace Period specified, to disable this process enter 0 to never automatically delete unverified users.', 'register-plus-redux' ); ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Registration Redirect', 'register-plus-redux' ); ?></th>
						<td>
							<input type="text" name="registration_redirect_url" id="registration_redirect_url" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'registration_redirect_url' ) ); ?>" style="width: 60%;" /><br />
							<?php echo sprintf( __( 'By default, after registering, users will be sent to %s/wp-login.php?checkemail=registered, leave this value empty if you do not wish to change this behavior. You may enter another address here, however, if that address is not on the same domain, Wordpress will ignore the redirect.', 'register-plus-redux' ), home_url() ); ?><br />
						</td>
					</tr>
					<tr valign="top" class="disabled" style="display: none;">
						<th scope="row"><?php _e( 'Verification Redirect', 'register-plus-redux' ); ?></th>
						<td>
							<input type="text" name="verification_redirect_url" id="verification_redirect_url" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'verification_redirect_url' ) ); ?>" style="width: 60%;" /><br />
							<?php echo sprintf( __( 'By default, after verifying, users will be sent to %s/wp-login.php, leave this value empty if you do not wish to change this behavior. You may enter another address here, however, if that addresses is not on the same domain, Wordpress will ignore the redirect.', 'register-plus-redux' ), home_url() ); ?><br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Autologin user', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="autologin_user" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'autologin_user' ) == TRUE ) echo 'checked="checked"'; ?>/>&nbsp;<?php _e( 'Autologin user after registration.', 'register-plus-redux' ); ?></label><br />
							<?php echo sprintf( __( 'Works if Email Verification and Admin Verification are turned off. By default users will be sent to %s, to change this behavior, set up Registration Redirect field above.', 'register-plus-redux' ), admin_url() ); ?>
						</td>
					</tr>						
				</table>
				<?php if ( !is_multisite() ) { ?>
				<h3 class="title"><?php _e( 'Registration Form', 'register-plus-redux' ); ?></h3>
				<p><?php _e( 'Select which fields to show on the Registration Form. Users will not be able to register without completing any fields marked required.', 'register-plus-redux' ); ?></p>
				<?php } else { ?>
				<h3 class="title"><?php _e( 'Signup Form', 'register-plus-redux' ); ?></h3>
				<p><?php _e( 'Select which fields to show on the Signup Form. Users will not be able to signup without completing any fields marked required.', 'register-plus-redux' ); ?></p>
				<?php } ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Use Email as Username', 'register-plus-redux' ); ?></th>
						<td><label><input type="checkbox" name="username_is_email" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'username_is_email' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'New users will not be asked to enter a username, instead their email address will be used as their username.', 'register-plus-redux' ); ?></label></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Confirm Email', 'register-plus-redux' ); ?></th>
						<td><label><input type="checkbox" name="double_check_email" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'double_check_email' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Require new users to enter e-mail address twice during registration.', 'register-plus-redux' ); ?></label></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Profile Fields', 'register-plus-redux' ); ?></th>
						<td>
							<table>
								<thead valign="top">
									<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"></td>
									<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><?php _e( 'Show', 'register-plus-redux' ); ?></td>
									<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><?php _e( 'Require', 'register-plus-redux' ); ?></td>
								</thead>
								<tbody>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'First Name', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="first_name" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'first_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="first_name" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'first_name', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'first_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'Last Name', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="last_name" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'last_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="last_name" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'last_name', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'last_name', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'Website', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="user_url" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'user_url', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="user_url" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'user_url', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'user_url', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'AIM', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="aim" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'aim', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="aim" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'aim', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'aim', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'Yahoo IM', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="yahoo" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'yahoo', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="yahoo" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'yahoo', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'yahoo', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'Jabber / Google Talk', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="jabber" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'jabber', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="jabber" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'jabber', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'jabber', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
									<tr valign="center">
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><?php _e( 'About Yourself', 'register-plus-redux' ); ?></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="show_fields[]" value="about" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && in_array( 'about', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'checked="checked"'; ?> class="modifyNextCellInput" /></td>
										<td align="center" style="padding-top: 0px; padding-bottom: 0px;"><input type="checkbox" name="required_fields[]" value="about" <?php if ( is_array( $register_plus_redux->rpr_get_option( 'required_fields' ) ) && in_array( 'about', $register_plus_redux->rpr_get_option( 'required_fields' ) ) ) echo 'checked="checked"'; ?> <?php if ( is_array( $register_plus_redux->rpr_get_option( 'show_fields' ) ) && !in_array( 'about', $register_plus_redux->rpr_get_option( 'show_fields' ) ) ) echo 'disabled="disabled"'; ?> /></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'User Set Password', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="user_set_password" id="user_set_password" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'user_set_password' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Require new users enter a password during registration...', 'register-plus-redux' ); ?></label><br />
							<div id="password_settings"<?php if ( $register_plus_redux->rpr_get_option( 'user_set_password' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<label><?php _e( 'Minimum password length: ', 'register-plus-redux' ); ?><input type="text" name="min_password_length" id="min_password_length" style="width:50px;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'min_password_length' ) ); ?>" /></label><br />
								<label><input type="checkbox" name="disable_password_confirmation" id="disable_password_confirmation" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'disable_password_confirmation' ) == TRUE ) echo 'checked="checked"'; ?>/>&nbsp;<?php _e( 'Do not require users to confirm password.', 'register-plus-redux' ); ?></label><br />
								<label><input type="checkbox" name="show_password_meter" id="show_password_meter" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'show_password_meter' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Show password strength meter...', 'register-plus-redux' ); ?></label>
								<div id="meter_settings"<?php if ( $register_plus_redux->rpr_get_option( 'show_password_meter' ) == FALSE ) echo ' style="display: none;"'; ?>>
									<table>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_empty_password"><?php _e( 'Empty', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_empty_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_empty_password' ) ); ?>" /></td>
										</tr>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_short_password"><?php _e( 'Short', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_short_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_short_password' ) ); ?>" /></td>
										</tr>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_bad_password"><?php _e( 'Bad', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_bad_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_bad_password' ) ); ?>" /></td>
										</tr>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_good_password"><?php _e( 'Good', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_good_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_good_password' ) ); ?>" /></td>
										</tr>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_strong_password"><?php _e( 'Strong', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_strong_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_strong_password' ) ); ?>" /></td>
										</tr>
										<tr>
											<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="message_mismatch_password"><?php _e( 'Mismatch', 'register-plus-redux' ); ?></label></td>
											<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="message_mismatch_password" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_mismatch_password' ) ); ?>" /></td>
										</tr>
									</table>
								</div>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Invitation Code', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="enable_invitation_code" id="enable_invitation_code" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'enable_invitation_code' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Use invitation codes to track or authorize new user registration...', 'register-plus-redux' ); ?></label>
							<div id="invitation_code_settings"<?php if ( $register_plus_redux->rpr_get_option( 'enable_invitation_code' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<label><input type="checkbox" name="require_invitation_code" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'require_invitation_code' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Require new user enter one of the following invitation codes to register.', 'register-plus-redux' ); ?></label><br />
								<label><input type="checkbox" name="invitation_code_case_sensitive" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'invitation_code_case_sensitive' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Enforce case-sensitivity of invitation codes.', 'register-plus-redux' ); ?></label><br />
								<label><input type="checkbox" name="invitation_code_unique" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'invitation_code_unique' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Each invitation code may only be used once.', 'register-plus-redux' ); ?></label><br />
								<label><input type="checkbox" name="enable_invitation_tracking_widget" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'enable_invitation_tracking_widget' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Show Invitation Code Tracking widget on Dashboard.', 'register-plus-redux' ); ?></label><br />
								<div id="invitation_code_bank">
								<?php
									$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
									if ( !is_array( $invitation_code_bank ) ) $invitation_code_bank = array();
									$size = sizeof( $invitation_code_bank );
									for ( $x = 0; $x < $size; $x++ ) {
										echo "\n", '<div class="invitation_code"';
										if ( $x > 5 ) echo ' style="display: none;"';
										echo '><input type="text" name="invitation_code_bank[]" value="', esc_attr( $invitation_code_bank[$x] ) , '" />&nbsp;<img src="', plugins_url( 'images\minus-circle.png', __FILE__ ), '" alt="', esc_attr__( 'Remove Code', 'register-plus-redux' ), '" title="', esc_attr__( 'Remove Code', 'register-plus-redux' ), '" class="removeInvitationCode" style="cursor: pointer;" /></div>';
									}
									if ( $size > 5 )
										echo '<div id="showHiddenInvitationCodes" style="cursor: pointer;">', sprintf( _n( 'Show %d hidden invitation code', 'Show %d hidden invitation codes', ( $size - 5 ), 'register-plus-redux' ), ( $size - 5 ) ), '</div>';
										//echo '<div id="showHiddenInvitationCodes" style="cursor: pointer;">', sprintf( __( 'Show %d hidden invitation codes', 'register-plus-redux' ), ( $size - 5 ) ), '</div>';
								?>
								</div>
								<img src="<?php echo plugins_url( 'images\plus-circle.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Add Code', 'register-plus-redux' ) ?>" title="<?php esc_attr_e( 'Add Code', 'register-plus-redux' ) ?>" id="addInvitationCode" style="cursor: pointer;" />&nbsp;<?php _e( 'Add a new invitation code', 'register-plus-redux' ) ?><br />
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Disclaimer', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="show_disclaimer" id="show_disclaimer" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'show_disclaimer' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Show Disclaimer during registration...', 'register-plus-redux' ); ?></label>
							<div id="disclaimer_settings"<?php if ( $register_plus_redux->rpr_get_option( 'show_disclaimer' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 40%;">
											<label for="message_disclaimer_title"><?php _e( 'Disclaimer Title', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_disclaimer_title" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_disclaimer_title' ) ); ?>" style="width: 100%;" />
										</td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="message_disclaimer"><?php _e( 'Disclaimer Content', 'register-plus-redux' ); ?></label><br />
											<textarea name="message_disclaimer" style="width: 100%; height: 160px; display: block;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'message_disclaimer' ) ); ?></textarea>
										</td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label><input type="checkbox" name="require_disclaimer_agree" class="enableDisableText" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'require_disclaimer_agree' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Require Agreement', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_disclaimer_agree" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_disclaimer_agree' ) ); ?>" <?php if ( $register_plus_redux->rpr_get_option( 'require_disclaimer_agree' ) == FALSE ) echo 'readonly="readonly"'; ?> style="width: 100%;" />
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'License Agreement' , 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="show_license" id="show_license" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'show_license' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Show License Agreement during registration...', 'register-plus-redux' ); ?></label>
							<div id="license_settings"<?php if ( $register_plus_redux->rpr_get_option( 'show_license' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 40%;">
											<label for="message_license_title"><?php _e( 'License Agreement Title', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_license_title" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_license_title' ) ); ?>" style="width: 100%;" />
										</td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="message_license"><?php _e( 'License Agreement Content', 'register-plus-redux' ); ?></label><br />
											<textarea name="message_license" style="width: 100%; height: 160px; display: block;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'message_license' ) ); ?></textarea>
										</td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label><input type="checkbox" name="require_license_agree" class="enableDisableText" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'require_license_agree' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Require Agreement', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_license_agree" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_license_agree' ) ); ?>" <?php if ( $register_plus_redux->rpr_get_option( 'require_license_agree' ) == FALSE ) echo 'readonly="readonly"'; ?> style="width: 100%;" />
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Privacy Policy', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="show_privacy_policy" id="show_privacy_policy" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'show_privacy_policy' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Show Privacy Policy during registration...', 'register-plus-redux' ); ?></label>
							<div id="privacy_policy_settings"<?php if ( $register_plus_redux->rpr_get_option( 'show_privacy_policy' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 40%;">
											<label for="message_privacy_policy_title"><?php _e( 'Privacy Policy Title', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_privacy_policy_title" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_privacy_policy_title' ) ); ?>" style="width: 100%;" />
										</td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="message_privacy_policy"><?php _e( 'Privacy Policy Content', 'register-plus-redux' ); ?></label><br />
											<textarea name="message_privacy_policy" style="width: 100%; height: 160px; display: block;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'message_privacy_policy' ) ); ?></textarea>
										</td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label><input type="checkbox" name="require_privacy_policy_agree" class="enableDisableText" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'require_privacy_policy_agree' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Require Agreement', 'register-plus-redux' ); ?></label>
										</td>
										<td style="padding-top: 0px; padding-bottom: 0px;">
											<input type="text" name="message_privacy_policy_agree" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'message_privacy_policy_agree' ) ); ?>" <?php if ( $register_plus_redux->rpr_get_option( 'require_privacy_policy_agree' ) == FALSE ) echo 'readonly="readonly"'; ?> style="width: 100%;" />
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Use Default Style Rules', 'register-plus-redux' ); ?></th>
						<td><label><input type="checkbox" name="default_css" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'default_css' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Apply default Wordpress 3.0.1 styling to all fields.', 'register-plus-redux' ); ?></label></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Required Fields Style Rules', 'register-plus-redux' ); ?></th>
						<td><input type="text" name="required_fields_style" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'required_fields_style' ) ); ?>" style="width: 60%;" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Required Fields Asterisk', 'register-plus-redux' ); ?></th>
						<td><label><input type="checkbox" name="required_fields_asterisk" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'required_fields_asterisk' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Add asterisk to left of all required field\'s name.', 'register-plus-redux' ); ?></label></td>
					</tr>
					<?php if ( !is_multisite() ) { ?>
					<tr valign="top">
						<th scope="row"><?php _e( 'Starting Tabindex', 'register-plus-redux' ); ?></th>
						<td>
							<input type="text" name="starting_tabindex" style="width:50px;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'starting_tabindex' ) ); ?>" /><br />
							<?php _e( 'The first field added will have this tabindex, the tabindex will increment by 1 for each additional field. Enter 0 to remove all tabindex\'s.', 'register-plus-redux' ); ?>
						</td>
					</tr>
					<?php } ?>
				</table>
				<h3 class="title"><?php _e( 'Additional Fields', 'register-plus-redux' ); ?></h3>
				<p><?php _e( 'Enter additional fields to show on the User Profile and/or Registration Pages. Additional fields will be shown after existing profile fields on User Profile, and after selected profile fields on Registration Page but before Password, Invitation Code, Disclaimer, License Agreement, or Privacy Policy (if any of those fields are enabled). Options must be entered for Select, Checkbox, and Radio fields. Options should be entered with commas separating each possible value. For example, a Radio field named "Gender" could have the following options, "Male,Female".', 'register-plus-redux' ); ?></p>
				<table id="meta_fields" style="padding-left: 0px; width: 90%;">
					<tbody class="fields">
						<?php
						$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
						if ( !is_array( $redux_usermeta ) ) $redux_usermeta = array();
						foreach ( $redux_usermeta as $index => $meta_field ) {
							echo "\n", '<tr><td>';
	
							echo "\n", '<table>';

							echo "\n", '<tr class="label"><td><img src="', plugins_url( 'images\arrow-move.png', __FILE__ ), '" alt="', esc_attr__( 'Reorder', 'register-plus-redux' ), '" title="', esc_attr__( 'Drag to Reorder', 'register-plus-redux' ), '" class="sortHandle" style="cursor: move;" />&nbsp;<input type="text" name="label[', $index, ']" value="', esc_attr( $meta_field['label'] ), '" />&nbsp;<span class="enableDisableFieldSettings" style="color:#0000FF; cursor: pointer;">Show Settings</span></td></tr>';
							echo "\n", '<tr class="settings" style="display: none;"><td>';
	
							echo "\n", '<table>';

							echo "\n", '<tr><td>', __( 'Display', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><select name="display[', $index, ']" class="enableDisableOptions" style="width: 100%;">';
							echo "\n", '<option value="textbox"'; if ( $meta_field['display'] == 'textbox' ) echo ' selected="selected"'; echo '>', __( 'Textbox Field', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="select"'; if ( $meta_field['display'] == 'select' ) echo ' selected="selected"'; echo '>', __( 'Select Field', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="checkbox"'; if ( $meta_field['display'] == 'checkbox' ) echo ' selected="selected"'; echo '>', __( 'Checkbox Fields', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="radio"'; if ( $meta_field['display'] == 'radio' ) echo ' selected="selected"'; echo '>', __( 'Radio Fields', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="textarea"'; if ( $meta_field['display'] == 'textarea' ) echo ' selected="selected"'; echo '>', __( 'Text Area', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="hidden"'; if ( $meta_field['display'] == 'hidden' ) echo ' selected="selected"'; echo '>', __( 'Hidden Field', 'register-plus-redux' ), '</option>';
							echo "\n", '<option value="text"'; if ( $meta_field['display'] == 'text' ) echo ' selected="selected"'; echo '>', __( 'Static Text', 'register-plus-redux' ), '</option>';
							echo "\n", '</select></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Options', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><input type="text" name="options[', $index, ']" value="', esc_attr( $meta_field['options'] ), '"'; if ( $meta_field['display'] != 'textbox' && $meta_field['display'] != 'select' && $meta_field['display'] != 'checkbox' && $meta_field['display'] != 'radio' ) echo ' readonly="readonly"'; echo ' style="width: 100%;" /></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Database Key', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><input type="text" name="meta_key[', $index, ']" value="', esc_attr( $meta_field['meta_key'] ), '" style="width: 100%;" /></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Show on Profile', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><input type="checkbox" name="show_on_profile[', $index, ']" value="1"'; if ( !empty( $meta_field['show_on_profile'] ) ) echo ' checked="checked"'; echo ' /></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Show on Registration', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><input type="checkbox" name="show_on_registration[', $index, ']" value="1"'; if ( !empty( $meta_field['show_on_registration'] ) ) echo ' checked="checked"'; echo ' class="modifyNextCellInput" /></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Required Field', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><input type="checkbox" name="require_on_registration[', $index, ']" value="1"'; if ( !empty( $meta_field['require_on_registration'] ) ) echo ' checked="checked"'; if ( empty( $meta_field['show_on_registration'] ) ) echo ' disabled="disabled"'; echo ' /></td></tr>';
	
							echo "\n", '<tr><td>', __( 'Actions', 'register-plus-redux' ), '</td>';
							echo "\n", '<td><img src="', plugins_url( 'images\question.png', __FILE__ ), '" alt="', esc_attr__( 'Help', 'register-plus-redux' ), '" title="', esc_attr__( 'No help available', 'register-plus-redux' ), '" class="helpButton" style="cursor: pointer;" />';
							echo "\n", '<img src="', plugins_url( 'images\minus-circle.png', __FILE__ ), '" alt="', esc_attr__( 'Remove', 'register-plus-redux' ), '" title="', esc_attr__( 'Remove Field', 'register-plus-redux' ), '" class="removeButton" style="cursor: pointer;" /></td></tr>';
							echo "\n", '</table>';
	
							echo "\n", '</td></tr>';
							echo "\n", '</table>';
	
							echo "\n", '</td></tr>';
						}
						?>
					</tbody>
				</table>
				<img src="<?php echo plugins_url( 'images\plus-circle.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Add Field', 'register-plus-redux' ) ?>" title="<?php esc_attr_e( 'Add Field', 'register-plus-redux' ) ?>" id="addField" style="cursor: pointer;" />&nbsp;<?php _e( 'Add a new custom field.', 'register-plus-redux' ) ?>
				<?php /*
				<table class="form-table">
					<tr valign="top" class="disabled" style="display: none;">
						<th scope="row"><?php _e( 'Date Field Settings', 'register-plus-redux' ); ?></th>
						<td>
							<label for="datepicker_firstdayofweek"><?php _e( 'First Day of the Week', 'register-plus-redux' ); ?>:</label>
							<select type="select" name="datepicker_firstdayofweek">
								<option value="7" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '7' ) echo 'selected="selected"'; ?>><?php _e( 'Monday', 'register-plus-redux' ); ?></option>
								<option value="1" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '1' ) echo 'selected="selected"'; ?>><?php _e( 'Tuesday', 'register-plus-redux' ); ?></option>
								<option value="2" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '2' ) echo 'selected="selected"'; ?>><?php _e( 'Wednesday', 'register-plus-redux' ); ?></option>
								<option value="3" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '3' ) echo 'selected="selected"'; ?>><?php _e( 'Thursday', 'register-plus-redux' ); ?></option>
								<option value="4" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '4' ) echo 'selected="selected"'; ?>><?php _e( 'Friday', 'register-plus-redux' ); ?></option>
								<option value="5" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '5' ) echo 'selected="selected"'; ?>><?php _e( 'Saturday', 'register-plus-redux' ); ?></option>
								<option value="6" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_firstdayofweek' ) == '6' ) echo 'selected="selected"'; ?>><?php _e( 'Sunday', 'register-plus-redux' ); ?></option>
							</select><br />
							<label for="datepicker_dateformat"><?php _e( 'Date Format', 'register-plus-redux' ); ?>:</label><input type="text" name="datepicker_dateformat" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'datepicker_dateformat' ) ); ?>" style="width:100px;" /><br />
							<label for="datepicker_startdate"><?php _e( 'First Selectable Date', 'register-plus-redux' ); ?>:</label><input type="text" name="datepicker_startdate" id="datepicker_startdate" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'datepicker_startdate' ) ); ?>" style="width:100px;" /><br />
							<label for="datepicker_calyear"><?php _e( 'Default Year', 'register-plus-redux' ); ?>:</label><input type="text" name="datepicker_calyear" id="datepicker_calyear" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'datepicker_calyear' ) ); ?>" style="width:40px;" /><br />
							<label for="datepicker_calmonth"><?php _e( 'Default Month', 'register-plus-redux' ); ?>:</label>
							<select name="datepicker_calmonth" id="datepicker_calmonth">
								<option value="cur" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == 'cur' ) echo 'selected="selected"'; ?>><?php _e( 'Current Month', 'register-plus-redux' ); ?></option>
								<option value="0" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '0' ) echo 'selected="selected"'; ?>><?php _e( 'Jan', 'register-plus-redux' ); ?></option>
								<option value="1" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '1' ) echo 'selected="selected"'; ?>><?php _e( 'Feb', 'register-plus-redux' ); ?></option>
								<option value="2" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '2' ) echo 'selected="selected"'; ?>><?php _e( 'Mar', 'register-plus-redux' ); ?></option>
								<option value="3" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '3' ) echo 'selected="selected"'; ?>><?php _e( 'Apr', 'register-plus-redux' ); ?></option>
								<option value="4" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '4' ) echo 'selected="selected"'; ?>><?php _e( 'May', 'register-plus-redux' ); ?></option>
								<option value="5" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '5' ) echo 'selected="selected"'; ?>><?php _e( 'Jun', 'register-plus-redux' ); ?></option>
								<option value="6" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '6' ) echo 'selected="selected"'; ?>><?php _e( 'Jul', 'register-plus-redux' ); ?></option>
								<option value="7" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '7' ) echo 'selected="selected"'; ?>><?php _e( 'Aug', 'register-plus-redux' ); ?></option>
								<option value="8" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '8' ) echo 'selected="selected"'; ?>><?php _e( 'Sep', 'register-plus-redux' ); ?></option>
								<option value="9" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '9' ) echo 'selected="selected"'; ?>><?php _e( 'Oct', 'register-plus-redux' ); ?></option>
								<option value="10" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '10' ) echo 'selected="selected"'; ?>><?php _e( 'Nov', 'register-plus-redux' ); ?></option>
								<option value="11" <?php if ( $register_plus_redux->rpr_get_option( 'datepicker_calmonth' ) == '11' ) echo 'selected="selected"'; ?>><?php _e( 'Dec', 'register-plus-redux' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				*/ ?>
				<h3 class="title"><?php _e( 'Autocomplete URL', 'register-plus-redux' ); ?></h3>
				<p><?php _e( 'You can create a URL to autocomplete specific fields for the user. Additional fields use the database key. Included below are available keys and an example URL.', 'register-plus-redux' ); ?></p>
				<p><code>user_login user_email first_name last_name user_url aim yahoo jabber description invitation_code<?php foreach ( $redux_usermeta as $index => $meta_field ) echo ' ', $meta_field['meta_key']; ?></code></p>
				<p><code>http://www.radiok.info/wp-login.php?action=register&user_login=radiok&user_email=radiok@radiok.info&first_name=Radio&last_name=K&user_url=www.radiok.info&aim=radioko&invitation_code=1979&middle_name=Billy</code></p>
				<h3 class="title"><?php _e( 'New User Message Settings', 'register-plus-redux' ); ?></h3>
				<table class="form-table"> 
					<tr valign="top">
						<th scope="row"><label><?php _e( 'New User Message', 'register-plus-redux' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="disable_user_message_registered" id="disable_user_message_registered" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'disable_user_message_registered' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Do NOT send user an email after they are registered', 'register-plus-redux' ); ?></label><br />
							<label><input type="checkbox" name="disable_user_message_created" id="disable_user_message_created" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'disable_user_message_created' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Do NOT send user an email when created by an administrator', 'register-plus-redux' ); ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Custom New User Message', 'register-plus-redux' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="custom_user_message" id="custom_user_message" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'custom_user_message' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Enable...', 'register-plus-redux' ); ?></label>
							<div id="custom_user_message_settings"<?php if ( $register_plus_redux->rpr_get_option( 'custom_user_message' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 20%;"><label for="user_message_from_email"><?php _e( 'From Email', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="user_message_from_email" id="user_message_from_email" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'user_message_from_email' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="user_message_from_name"><?php _e( 'From Name', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="user_message_from_name" id="user_message_from_name" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'user_message_from_name' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="user_message_subject"><?php _e( 'Subject', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="user_message_subject" id="user_message_subject" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'user_message_subject' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="user_message_body"><?php _e( 'User Message', 'register-plus-redux' ); ?></label><br />
											<textarea name="user_message_body" id="user_message_body" style="width: 95%; height: 160px;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'user_message_body' ) ); ?></textarea><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /><br />
											<strong><?php _e( 'Replacement Keywords', 'register-plus-redux' ); ?>:</strong> <?php echo $register_plus_redux->replace_keywords(); ?><br />
											<label><input type="checkbox" name="send_user_message_in_html" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'send_user_message_in_html' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Send as HTML', 'register-plus-redux' ); ?></label><br />
											<label><input type="checkbox" name="user_message_newline_as_br" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'user_message_newline_as_br' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Convert new lines to &lt;br /&gt; tags (HTML only)', 'register-plus-redux' ); ?></label>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Custom Verification Message', 'register-plus-redux' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="custom_verification_message" id="custom_verification_message" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'custom_verification_message' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Enable...', 'register-plus-redux' ); ?></label>
							<div id="custom_verification_message_settings"<?php if ( $register_plus_redux->rpr_get_option( 'custom_verification_message' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 20%;"><label for="verification_message_from_email"><?php _e( 'From Email', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="verification_message_from_email" id="verification_message_from_email" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'verification_message_from_email' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="verification_message_from_name"><?php _e( 'From Name', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="verification_message_from_name" id="verification_message_from_name" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'verification_message_from_name' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="verification_message_subject"><?php _e( 'Subject', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="verification_message_subject" id="verification_message_subject" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'verification_message_subject' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="verification_message_body"><?php _e( 'User Message', 'register-plus-redux' ); ?></label><br />
											<textarea name="verification_message_body" id="verification_message_body" style="width: 95%; height: 160px;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'verification_message_body' ) ); ?></textarea><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /><br />
											<strong><?php _e( 'Replacement Keywords', 'register-plus-redux' ); ?>:</strong> <?php echo $register_plus_redux->replace_keywords(); ?><br />
											<label><input type="checkbox" name="send_verification_message_in_html" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'send_verification_message_in_html' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Send as HTML', 'register-plus-redux' ); ?></label><br />
											<label><input type="checkbox" name="verification_message_newline_as_br" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'verification_message_newline_as_br' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Convert new lines to &lt;br /&gt; tags (HTML only)', 'register-plus-redux' ); ?></label>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Summary', 'register-plus-redux' ); ?></label></th>
						<td>
							<span id="user_message_summary"></span>
						</td>
					</tr>
				</table>
				<h3 class="title"><?php _e( 'Admin Notification Settings', 'register-plus-redux' ); ?></h3>
				<table class="form-table"> 
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Admin Notification', 'register-plus-redux' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="disable_admin_message_registered" id="disable_admin_message_registered" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'disable_admin_message_registered' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Do NOT send administrator an email whenever a new user registers', 'register-plus-redux' ); ?></label><br />
							<label><input type="checkbox" name="disable_admin_message_created" id="disable_admin_message_created" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'disable_admin_message_created' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Do NOT send administrator an email whenever a new user is created by an administrator', 'register-plus-redux' ); ?></label><br />
							<label><input type="checkbox" name="admin_message_when_verified" id="admin_message_when_verified" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'admin_message_when_verified' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Send administrator an email after a new user is verified', 'register-plus-redux' ); ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Custom Admin Notification', 'register-plus-redux' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="custom_admin_message" id="custom_admin_message" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'custom_admin_message' ) == TRUE ) echo 'checked="checked"'; ?> class="showHideSettings" />&nbsp;<?php _e( 'Enable...', 'register-plus-redux' ); ?></label>
							<div id="custom_admin_message_settings"<?php if ( $register_plus_redux->rpr_get_option( 'custom_admin_message' ) == FALSE ) echo ' style="display: none;"'; ?>>
								<table width="60%">
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px; width: 20%;"><label for="admin_message_from_email"><?php _e( 'From Email', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="admin_message_from_email" id="admin_message_from_email" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'admin_message_from_email' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="admin_message_from_name"><?php _e( 'From Name', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="admin_message_from_name" id="admin_message_from_name" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'admin_message_from_name' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;"><label for="admin_message_subject"><?php _e( 'Subject', 'register-plus-redux' ); ?></label></td>
										<td style="padding-top: 0px; padding-bottom: 0px;"><input type="text" name="admin_message_subject" id="admin_message_subject" style="width: 90%;" value="<?php echo esc_attr( $register_plus_redux->rpr_get_option( 'admin_message_subject' ) ); ?>" /><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /></td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top: 0px; padding-bottom: 0px; padding-left: 0px;">
											<label for="admin_message_body"><?php _e( 'Admin Message', 'register-plus-redux' ); ?></label><br />
											<textarea name="admin_message_body" id="admin_message_body" style="width: 95%; height: 160px;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'admin_message_body' ) ); ?></textarea><img src="<?php echo plugins_url( 'images\arrow-return-180.png', __FILE__ ); ?>" alt="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" title="<?php esc_attr_e( 'Restore Default', 'register-plus-redux' ); ?>" class="default" style="cursor: pointer;" /><br />
											<strong><?php _e( 'Replacement Keywords', 'register-plus-redux' ); ?>:</strong> <?php echo $register_plus_redux->replace_keywords(); ?><br />
											<label><input type="checkbox" name="send_admin_message_in_html" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'send_admin_message_in_html' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Send as HTML', 'register-plus-redux' ); ?></label><br />
											<label><input type="checkbox" name="admin_message_newline_as_br" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'admin_message_newline_as_br' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Convert new lines to &lt;br /&gt; tags (HTML only)', 'register-plus-redux' ); ?></label>
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Summary', 'register-plus-redux' ); ?></label></th>
						<td>
							<span id="admin_message_summary"></span>
						</td>
					</tr>
				</table>
				<br />
				<h3 class="title"><?php _e( 'Custom CSS for Register & Login Pages', 'register-plus-redux' ); ?></h3>
				<p><?php _e( 'CSS Rule Example:', 'register-plus-redux' ); ?>&nbsp;<code>#user_login { font-size: 20px; width: 100%; padding: 3px; margin-right: 6px; }</code></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="custom_registration_page_css"><?php _e( 'Custom Register CSS', 'register-plus-redux' ); ?></label></th>
						<td><textarea name="custom_registration_page_css" id="custom_registration_page_css" style="width:60%; height:160px;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'custom_registration_page_css' ) ); ?></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="custom_login_page_css"><?php _e( 'Custom Login CSS', 'register-plus-redux' ); ?></label></th>
						<td><textarea name="custom_login_page_css" id="custom_login_page_css" style="width:60%; height:160px;"><?php echo esc_textarea( $register_plus_redux->rpr_get_option( 'custom_login_page_css' ) ); ?></textarea></td>
					</tr>
				</table>
				<br />
				<h3 class="title"><?php _e( 'Hacks & Fixes', 'register-plus-redux' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Random Password Appears in Messages', 'register-plus-redux' ); ?></th>
						<td>
							<label><input type="checkbox" name="filter_random_password" value="1" <?php if ( $register_plus_redux->rpr_get_option( 'filter_random_password' ) == TRUE ) echo 'checked="checked"'; ?> />&nbsp;<?php _e( 'Filter Random Passwords.', 'register-plus-redux' ); ?></label><br />
							<?php _e( 'When user set password is enabled, and another plugin is being used to modify outgoing messages, a random password may appear in those messages, regardless of the fact that a user entered password was specified. This option will filter all password requests and show the user entered password if possible.', 'register-plus-redux' ); ?>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'register-plus-redux' ); ?>" name="update_settings" />
					<input type="button" class="button" value="<?php esc_attr_e( 'Preview Registration Page', 'register-plus-redux' ); ?>" name="preview" onclick="window.open('<?php echo wp_login_url(), '?action=register'; ?>');" />
				</p>
			</form>
			</div>
			<?php
		}

		function rpr_options_submenu_footer() {
			global $register_plus_redux;
			?>
			<script type="text/javascript">
			function addInvitationCode() {
				jQuery("#invitation_code_bank")
					.append(jQuery("<div>")
						.attr("class", "invitation_code")
						.append(jQuery("<input>")
							.attr("type", "text")
							.attr("name", "invitation_code_bank[]")
							.attr("value", "")
						)
						.append("&nbsp;")
						.append(jQuery("<img>")
							.attr("src", "<?php echo plugins_url( 'images\minus-circle.png', __FILE__ ); ?>")
							.attr("alt", "<?php esc_attr_e( 'Remove Code', 'register-plus-redux' ); ?>")
							.attr("title", "<?php esc_attr_e( 'Remove Code', 'register-plus-redux' ); ?>")
							.attr("class", "removeInvitationCode")
							.attr("style", "cursor: pointer;")
						)
					);
			}

			function addField() {
				jQuery("#meta_fields").find("tbody.fields")
					.append(jQuery("<tr>")
						.append(jQuery("<td>")
							.append(jQuery("<img>")
								.attr("src", "<?php echo plugins_url( 'images\asterisk-yellow.png', __FILE__ ); ?>")
								.attr("alt", "<?php esc_attr_e( 'New', 'register-plus-redux' ); ?>")
								.attr("title", "<?php esc_attr_e( 'New Field', 'register-plus-redux' ); ?>")
							)
							.append("&nbsp;")
							.append(jQuery("<input>")
								.attr("type", "text")
								.attr("name", "newMetaFields[]")
							)
							.append("&nbsp;")
							.append(jQuery("<img>")
								.attr("src", "<?php echo plugins_url( 'images\minus-circle.png', __FILE__ ); ?>")
								.attr("alt", "<?php esc_attr_e( 'Remove', 'register-plus-redux' ); ?>")
								.attr("title", "<?php esc_attr_e( 'Remove Field', 'register-plus-redux' ); ?>")
								.attr("class", "removeNewButton")
								.attr("style", "cursor: pointer;")
							)
							.append("&nbsp;")
							.append(jQuery("<img>")
								.attr("src", "<?php echo plugins_url( 'images\question.png', __FILE__ ); ?>")
								.attr("alt", "<?php esc_attr_e( 'Help', 'register-plus-redux' ); ?>")
								.attr("title", "<?php esc_attr_e( 'You must save after adding new fields before all options become available.', 'register-plus-redux' ); ?>")
								.attr("class", "helpButton")
								.attr("style", "cursor: pointer;")
							)
						)
					);
			}

			function updateUserMessagesSummary() {
				jQuery("#user_message_summary").empty();
				if (!jQuery("#verify_user_email").prop("checked")) {
					jQuery("#custom_verification_message").prop("disabled", true);
					jQuery("#custom_verification_message").prop("checked", false);
					jQuery("#custom_verification_message_settings").hide();
				}
				else {
					jQuery("#custom_verification_message").prop("disabled", false);
					jQuery("#user_message_summary").append("<?php _e( 'The following message will be sent when a user is registered:', 'register-plus-redux' ); ?>");
					var verification_message_from_name = "<?php echo $register_plus_redux->default_options( 'verification_message_from_name' ); ?>";
					var verification_message_from_email = "<?php echo $register_plus_redux->default_options( 'verification_message_from_email' ); ?>";
					var verification_message_subject = "<?php echo $register_plus_redux->default_options( 'verification_message_subject' ); ?>";
					var verification_message_content_type = "text/plain";
					var verification_message_body = "<?php echo str_replace( "\n", '\n', $register_plus_redux->default_options( 'verification_message_body' ) ); ?>";
					if (jQuery("#custom_verification_message").prop("checked")) {
						verification_message_from_name = jQuery("#verification_message_from_name").val();
						verification_message_from_email = jQuery("#verification_message_from_email").val();
						verification_message_subject = jQuery("#verification_message_subject").val();
						if (jQuery("#send_verification_message_in_html").prop("checked")) verification_message_content_type = "text/html";
						verification_message_body = jQuery("#verification_message_body").val();
					}
					var verificationMessage = jQuery("<p>").attr("style", "font-size: 11px; display: block; width: 50%; background-color: #efefef; padding: 8px 10px; border: solid 1px #dfdfdf; margin: 1px; overflow:auto; white-space:pre;");
					verificationMessage.append(jQuery("<div>").text("<?php _e( 'To: ', 'register-plus-redux' ); ?>" + "%user_email%"));
					verificationMessage.append(jQuery("<div>").text("<?php _e( 'From: ', 'register-plus-redux' ); ?>" + verification_message_from_name + " (" + verification_message_from_email + ")"));
					verificationMessage.append(jQuery("<div>").text("<?php _e( 'Subject: ', 'register-plus-redux' ); ?>" + verification_message_subject));
					verificationMessage.append(jQuery("<div>").text("<?php _e( 'Content-Type: ', 'register-plus-redux' ); ?>" + verification_message_content_type));
					verificationMessage.append(jQuery("<div>").text(verification_message_body));
					jQuery("#user_message_summary").append(verificationMessage);
				}
				if (jQuery("#disable_user_message_registered").prop("checked") && jQuery("#disable_user_message_created").prop("checked")) {
					jQuery("#custom_user_message").prop("disabled", true);
					jQuery("#custom_user_message").prop("checked", false);
					jQuery("#custom_user_message_settings").hide();
					jQuery("#user_message_summary").append("<?php _e( 'No message will be sent to user whether they are registered or created by an administrator.', 'register-plus-redux' ); ?>");
				}
				else {
					jQuery("#custom_user_message").prop("disabled", false);
					var when = "<?php _e( 'The following message will be sent when a user is ', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_user_message_registered").prop("checked")) when = when + "<?php _e( 'registered', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_user_message_registered").prop("checked") && !jQuery("#disable_user_message_created").prop("checked")) when = when + "<?php _e( ' or ', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_user_message_created").prop("checked")) when = when + "<?php _e( 'created', 'register-plus-redux' ); ?>";
					if (jQuery("#verify_user_email").prop("checked") || jQuery("#verify_user_admin").prop("checked")) when = when + "<?php _e( ' after ', 'register-plus-redux' ); ?>";
					if (jQuery("#verify_user_email").prop("checked"))
						when = when + "<?php _e( 'the user has verified their email address', 'register-plus-redux' ); ?>";
					if (jQuery("#verify_user_email").prop("checked") && jQuery("#verify_user_admin").prop("checked")) when = when + "<?php _e( ' and/or ', 'register-plus-redux' ); ?>";
					if (jQuery("#verify_user_admin").prop("checked"))
						when = when + "<?php _e( 'an administrator has approved the new user', 'register-plus-redux' ); ?>";
					jQuery("#user_message_summary").append(when + ":");
					var user_message_from_name = "<?php echo $register_plus_redux->default_options( 'user_message_from_name' ); ?>";
					var user_message_from_email = "<?php echo $register_plus_redux->default_options( 'user_message_from_email' ); ?>";
					var user_message_subject = "<?php echo $register_plus_redux->default_options( 'user_message_subject' ); ?>";
					var user_message_content_type = "text/plain";
					var user_message_body = "<?php echo str_replace( "\n", '\n', $register_plus_redux->default_options( 'user_message_body' ) ); ?>";
					if (jQuery("#custom_user_message").prop("checked")) {
						user_message_from_name = jQuery("#user_message_from_name").val();
						user_message_from_email = jQuery("#user_message_from_email").val();
						user_message_subject = jQuery("#user_message_subject").val();
						if (jQuery("#send_user_message_in_html").prop("checked")) user_message_content_type = "text/html";
						user_message_body = jQuery("#user_message_body").val();
					}
					var userMessage = jQuery("<p>").attr("style", "font-size: 11px; display: block; width: 50%; background-color: #efefef; padding: 8px 10px; border: solid 1px #dfdfdf; margin: 1px; overflow:auto; white-space:pre;");
					userMessage.append(jQuery("<div>").text("<?php _e( 'To: ', 'register-plus-redux' ); ?>" + "%user_email%"));
					userMessage.append(jQuery("<div>").text("<?php _e( 'From: ', 'register-plus-redux' ); ?>" + user_message_from_name + " (" + user_message_from_email + ")"));
					userMessage.append(jQuery("<div>").text("<?php _e( 'Subject: ', 'register-plus-redux' ); ?>" + user_message_subject));
					userMessage.append(jQuery("<div>").text("<?php _e( 'Content-Type: ', 'register-plus-redux' ); ?>" + user_message_content_type));
					userMessage.append(jQuery("<div>").text(user_message_body));
					jQuery("#user_message_summary").append(userMessage);
				}
			}

			function updateAdminMessageSummary() {
				jQuery("#admin_message_summary").empty();
				if (jQuery("#disable_admin_message_registered").prop("checked") && jQuery("#disable_admin_message_created").prop("checked")) {
					jQuery("#custom_admin_message").prop("disabled", true);
					jQuery("#custom_admin_message").prop("checked", false);
					jQuery("#custom_admin_message_settings").hide();
					jQuery("#admin_message_summary").append("<?php _e( 'No message will be sent to administrator whether a user is registered or created.', 'register-plus-redux' ); ?>");
				}
				else {
					jQuery("#custom_admin_message").prop("disabled", false);
					var when = "<?php _e( 'The following message will be sent when a user is ', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_admin_message_registered").prop("checked")) when = when + "<?php _e( 'registered', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_admin_message_registered").prop("checked") && !jQuery("#disable_admin_message_created").prop("checked")) when = when + "<?php _e( ' or ', 'register-plus-redux' ); ?>";
					if (!jQuery("#disable_admin_message_created").prop("checked")) when = when + "<?php _e( 'created', 'register-plus-redux' ); ?>";
					jQuery("#admin_message_summary").append(when + ":");
					var admin_message_from_name = "<?php echo $register_plus_redux->default_options( 'admin_message_from_name' ); ?>";
					var admin_message_from_email = "<?php echo $register_plus_redux->default_options( 'admin_message_from_email' ); ?>";
					var admin_message_subject = "<?php echo $register_plus_redux->default_options( 'admin_message_subject' ); ?>";
					var admin_message_content_type = "text/plain";
					var admin_message_body = "<?php echo str_replace( "\n", '\n', $register_plus_redux->default_options( 'admin_message_body' ) ); ?>";
					if (jQuery("#custom_admin_message").prop("checked")) {
						admin_message_from_name = jQuery("#admin_message_from_name").val();
						admin_message_from_email = jQuery("#admin_message_from_email").val();
						admin_message_subject = jQuery("#admin_message_subject").val();
						if (jQuery("#send_admin_message_in_html").prop("checked")) admin_message_content_type = "text/html";
						admin_message_body = jQuery("#admin_message_body").val();
					}
					var adminMessage = jQuery("<p>").attr("style", "font-size: 11px; display: block; width: 50%; background-color: #efefef; padding: 8px 10px; border: solid 1px #dfdfdf; margin: 1px; overflow:auto; white-space:pre;");
					adminMessage.append(jQuery("<div>").text("<?php _e( 'To: ', 'register-plus-redux' ); echo get_option( 'admin_email' ); ?>"));
					adminMessage.append(jQuery("<div>").text("<?php _e( 'From: ', 'register-plus-redux' ); ?>" + admin_message_from_name + " (" + admin_message_from_email + ")"));
					adminMessage.append(jQuery("<div>").text("<?php _e( 'Subject: ', 'register-plus-redux' ); ?>" + admin_message_subject));
					adminMessage.append(jQuery("<div>").text("<?php _e( 'Content-Type: ', 'register-plus-redux' ); ?>" + admin_message_content_type));
					adminMessage.append(jQuery("<div>").text(admin_message_body));
					jQuery("#admin_message_summary").append(adminMessage);
				}
			}

			jQuery(document).ready(function() {
				jQuery("#upload_custom_logo_button").bind("click", function() {
					formfield = jQuery("#custom_logo_url").attr("name");
					tb_show("", "<?php echo admin_url('media-upload.php') ?>?post_id=0&type=image&context=custom-logo&TB_iframe=1");
				});
				 
				window.send_to_editor = function(html) {
					jQuery("#custom_logo_url").val(jQuery("img",html).attr("src"));
					tb_remove();
				}

				jQuery("#meta_fields tbody.fields").sortable({handle:'.sortHandle'});
				//jQuery("#meta_fields tbody.fields").disableSelection();

				jQuery(".showHideSettings").bind("click", function() {
					if (jQuery(this).prop("checked"))
						jQuery(this).parent().nextAll("div").first().show();
					else
						jQuery(this).parent().nextAll("div").first().hide();
				});

				jQuery("#showHiddenInvitationCodes").bind("click", function() {
					jQuery(this).parent().children().show();
					jQuery(this).remove();
				});

				jQuery("#addInvitationCode").bind("click", function() {
					addInvitationCode();
				});

				jQuery(".removeInvitationCode").live("click", function() {
					jQuery(this).parent().remove();
				});

				jQuery(".enableDisableText").live("click", function() {
					if (jQuery(this).prop("checked"))
						jQuery(this).parent().parent().next().find("input").prop("readOnly", false);
					else
						jQuery(this).parent().parent().next().find("input").prop("readOnly", true);
				});

				jQuery(".helpButton").live("click", function() {
					alert(jQuery(this).attr("title") );
				});

				jQuery("#addField").bind("click", function() {
					addField();
				});

				jQuery(".removeNewButton").live("click", function() {
					jQuery(this).parent().parent().remove();
				});

				jQuery(".removeButton").live("click", function() {
					jQuery(this).parent().parent().parent().parent().parent().parent().parent().remove();
				});

				jQuery(".enableDisableFieldSettings").live("click", function() {
					if (jQuery(this).text() == "Show Settings") {
						jQuery(this).text("Hide Settings");
						jQuery(this).parent().parent().parent().find(".settings").show();
					}
					else {
						jQuery(this).text("Show Settings");
						jQuery(this).parent().parent().parent().find(".settings").hide();
					}
						
				});

				jQuery(".enableDisableOptions").live("change", function() {
					if (jQuery(this).val() == "textbox" || jQuery(this).val() == "select" || jQuery(this).val() == "checkbox" || jQuery(this).val() == "radio" || jQuery(this).val() == "text")
						jQuery(this).parent().next().next().find("input").prop("readOnly", false);
					else
						jQuery(this).parent().next().next().find("input").prop("readOnly", true);
				});

				jQuery(".modifyNextCellInput").live("click", function() {
					if (jQuery(this).prop("checked"))
						jQuery(this).parent().next().find("input").prop("disabled", false);
					else {
						jQuery(this).parent().next().find("input").prop("checked", false);
						jQuery(this).parent().next().find("input").prop("disabled", true);
					}
				});

				jQuery(".upButton,.downButton").live("click", function() {
					var row = jQuery(this).parents("tr:first");
					if (jQuery(this).is(".upButton")) {
						row.insertBefore(row.prev() );
					}
					else {
						row.insertAfter(row.next() );
					}
				});
				
				jQuery("#verify_user_email,#verify_user_admin,#disable_user_message_registered,#disable_user_message_created,#custom_user_message,#user_message_from_name,#user_message_from_email,#user_message_subject,#user_message_body,#send_user_message_in_html,#custom_verification_message,#verification_message_from_name,#verification_message_from_email,#verification_message_subject,#verification_message_body,#verification_admin_message_in_html").change(function() {
					updateUserMessagesSummary();
				});

				jQuery("#disable_admin_message_registered,#disable_admin_message_created,#custom_admin_message,#admin_message_from_name,#admin_message_from_email,#admin_message_subject,#admin_message_body,#send_admin_message_in_html").change(function() {
					updateAdminMessageSummary();
				});

				updateUserMessagesSummary();
				updateAdminMessageSummary();
			});
			</script>
			<?php
		}

		function rpr_users_submenu() {
			global $register_plus_redux;
			global $wpdb;
			if ( ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'verify_users' ) ) || isset( $_REQUEST['verify_users'] ) ) {
				check_admin_referer( 'register-plus-redux-unverified-users' );
				if ( isset( $_REQUEST['users'] ) && is_array( $_REQUEST['users'] ) && !empty( $_REQUEST['users'] ) ) {
					$update = 'verify_users';
					foreach ( (array) $_REQUEST['users'] as $id ) {
						$user_id = (int) $id;
						$stored_user_login = get_user_meta( $user_id, 'stored_user_login', TRUE );
						$plaintext_pass = get_user_meta( $user_id, 'stored_user_password', TRUE );
						$wpdb->update( $wpdb->users, array( 'user_login' => $stored_user_login ), array( 'ID' => $user_id ) );
						if ( empty( $plaintext_pass ) ) {
							$plaintext_pass = wp_generate_password();
							update_user_option( $user_id, 'default_password_nag', TRUE, TRUE );
							wp_set_password( $plaintext_pass, $user_id );
						}
						if ( $register_plus_redux->rpr_get_option( 'disable_user_message_registered' ) == FALSE )
							$register_plus_redux->send_welcome_user_mail( $user_id, $plaintext_pass );
						if ( $register_plus_redux->rpr_get_option( 'admin_message_when_verified' ) == TRUE )
							$register_plus_redux->send_admin_mail( $user_id, $plaintext_pass );
						delete_user_meta( $user_id, 'email_verification_code' );
						delete_user_meta( $user_id, 'email_verification_sent' );
						delete_user_meta( $user_id, 'email_verified' );
						delete_user_meta( $user_id, 'stored_user_login' );
						delete_user_meta( $user_id, 'stored_user_password' );
					}
				}
			}
			if ( ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'send_verification_email' ) ) || isset( $_REQUEST['send_verification_email'] ) ) {
				check_admin_referer( 'register-plus-redux-unverified-users' );
				if ( isset( $_REQUEST['users'] ) && is_array( $_REQUEST['users'] ) && !empty( $_REQUEST['users'] ) ) {
					$update = 'send_verification_email';
					foreach ( (array) $_REQUEST['users'] as $id ) {
						$id = (int) $id;
						if ( !current_user_can( 'promote_user', $id ) )
							wp_die( __( 'You cannot edit that user.', 'register-plus-redux' ) );
						$verification_code = wp_generate_password( 20, FALSE );
						update_user_meta( $user_id, 'email_verification_code', $verification_code );
						update_user_meta( $user_id, 'email_verification_sent', gmdate( 'Y-m-d H:i:s' ) );
						$register_plus_redux->send_verification_mail( $user_id, $verification_code );
					}
				}
			}
			if ( ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'delete_users' ) ) || isset( $_REQUEST['delete_users'] ) ) {
				check_admin_referer( 'register-plus-redux-unverified-users' );
				check_admin_referer( 'delete-users' );
				if ( isset( $_REQUEST['users'] ) && is_array( $_REQUEST['users'] ) && !empty( $_REQUEST['users'] ) ) {
					$update = 'delete_users';
					//necessary for wp_delete_user to function
					if ( !function_exists( 'wp_delete_user' ) ) require_once( ABSPATH . '/wp-admin/includes/user.php' );
					foreach ( (array) $_REQUEST['users'] as $id ) {
						$id = (int) $id;
						if ( current_user_can( 'delete_user', $id ) ) wp_delete_user( $id );
					}
				}
			}
			if ( !empty( $update ) ) {
				switch( $update ) {
					case 'verify_users':
						echo '<div id="message" class="updated"><p>', __( 'Users approved.', 'register-plus-redux' ), '</p></div>';
						break;
					case 'send_verification_email':
						echo '<div id="message" class="updated"><p>', __( 'Verification emails sent.', 'register-plus-redux' ), '</p></div>';
						break;
					case 'delete_users':
						echo '<div id="message" class="updated"><p>', __( 'Users deleted.', 'register-plus-redux' ), '</p></div>';
						break;
				}
			}
			?>
			<div class="wrap">
				<h2><?php _e( 'Unverified Users', 'register-plus-redux' ) ?></h2>
				<form id="verify-filter" method="post">
				<?php wp_nonce_field( 'register-plus-redux-unverified-users' ); ?>
				<div class="tablenav">
					<div class="alignleft actions">
						<select name="action">
							<option value="" selected="selected"><?php _e( 'Bulk Actions', 'register-plus-redux' ); ?></option>
							<?php if ( current_user_can( 'promote_users' ) ) echo '<option value="verify_users">', __( 'Approve', 'register-plus-redux' ), '</option>', "\n"; ?>
							<option value="send_verification_email"><?php _e( 'Send E-mail Verification', 'register-plus-redux' ); ?></option>
							<?php if ( current_user_can( 'delete_users' ) ) echo '<option value="delete_users">', __( 'Delete', 'register-plus-redux' ), '</option>', "\n"; ?>
						</select>
						<input type="submit" value="<?php esc_attr_e( 'Apply', 'register-plus-redux' ); ?>" name="doaction" id="doaction" class="button-secondary action" />

					</div>
					<br class="clear">
				</div>
				<table class="widefat fixed" cellspacing="0">
					<thead>
						<tr class="thead">
							<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
							<th scope="col" id="stored_username" class="manage-column column-stored_username" style=""><?php _e( 'Username', 'register-plus-redux' ); ?></th>
							<th scope="col" id="temp_username" class="manage-column column-temp_username" style=""><?php _e( 'Temp Username', 'register-plus-redux' ); ?></th>
							<th scope="col" id="email" class="manage-column column-email" style=""><?php _e( 'E-mail', 'register-plus-redux' ); ?></th>
							<th scope="col" id="registered" class="manage-column column-registered" style=""><?php _e( 'Registered', 'register-plus-redux' ); ?></th>
							<th scope="col" id="verification_sent"class="manage-column column-verification_sent" style=""><?php _e( 'Verification Sent', 'register-plus-redux' ); ?></th>
							<th scope="col" id="verified"class="manage-column column-verified" style=""><?php _e( 'Verified', 'register-plus-redux' ); ?></th>
						</tr>
					</thead>
					<tbody id="users" class="list:user user-list">
						<?php 
						$unverified_users = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'stored_user_login';" );
						if ( !empty( $unverified_users ) ) {
							$style = '';
							foreach ( $unverified_users as $unverified_user ) {
								$user_info = get_userdata( $unverified_user->user_id );
								$style = ( $style == ' class="alternate"' ) ? '' : ' class="alternate"';
								?>
								<tr id="user-<?php echo $user_info->ID; ?>"<?php echo $style; ?>>
									<th scope="row" class="check-column"><input type="checkbox" name="users[]" id="user_<?php echo $user_info->ID; ?>" name="user_<?php echo $user_info->ID; ?>" value="<?php echo $user_info->ID; ?>"></th>
									<td class="username column-username">
										<strong><?php if ( current_user_can( 'edit_users' ) ) echo '<a href="', esc_url( add_query_arg( array( 'user_id' => $user_info->ID, 'wp_http_referer' => urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), 'user-edit.php') ) , '">', $user_info->stored_user_login, '</a>'; else echo $user_info->stored_user_login; ?></strong><br />
										<div class="row-actions">
											<?php if ( current_user_can( 'edit_users' ) ) echo '<span class="edit"><a href="', esc_url( add_query_arg( array( 'user_id' => $user_info->ID, 'wp_http_referer' => urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), 'user-edit.php') ), '">', __( 'Edit', 'register-plus-redux' ), '</a></span>', "\n"; ?>
											<?php if ( current_user_can( 'delete_users' ) ) echo '<span class="delete"> | <a href="', wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'user' => $user_info->ID, 'wp_http_referer' => urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), 'users.php'), 'delete-users' ), '" class="submitdelete">', __( 'Delete', 'register-plus-redux' ), '</a></span>', "\n"; ?>
										</div>
									</td>
									<td><?php echo $user_info->user_login; ?></td>
									<td class="email column-email"><a href="mailto:<?php echo $user_info->user_email; ?>" title="<?php esc_attr_e( 'E-mail: ', 'register-plus-redux' ); echo $user_info->user_email; ?>"><?php echo $user_info->user_email; ?></a></td>
									<td><?php echo $user_info->user_registered; ?></td>
									<td><?php echo $user_info->email_verification_sent; ?></td>
									<td><?php echo $user_info->email_verified; ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
				<div class="tablenav">
					<div class="alignleft actions">
						<?php if ( current_user_can( 'promote_users' ) ) echo '<input type="submit" value="', esc_attr__( 'Approve Selected Users', 'register-plus-redux' ), '" name="verify_users" class="button-secondary action" />&nbsp;', "\n"; ?>
						<input type="submit" value="<?php esc_attr_e( 'Send E-mail Verification to Selected Users', 'register-plus-redux' ); ?>" name="send_verification_email" class="button-secondary action" />&nbsp;
						<?php if ( current_user_can( 'delete_users' ) ) echo '&nbsp;<input type="submit" value="', esc_attr__( 'Delete Selected Users', 'register-plus-redux' ), '" name="delete_users" class="button-secondary action" />', "\n"; ?>
					</div>
					<br class="clear">
				</div>
				</form>
			</div>
			<br class="clear" />
			<?php
		}

		function update_settings() {
			global $register_plus_redux;
			$options = array();
			$redux_usermeta = array();

			if ( get_magic_quotes_gpc() ) $_POST = stripslashes_deep( $_POST );

			$options['custom_logo_url'] = isset( $_POST['custom_logo_url'] ) ? esc_url_raw( $_POST['custom_logo_url'] ) : '';
			if ( isset( $_POST['remove_logo'] ) ) $options['custom_logo_url'] = '';
			$options['verify_user_email'] = isset( $_POST['verify_user_email'] ) ? '1' : '0';
			$options['message_verify_user_email'] = isset( $_POST['message_verify_user_email'] ) ? wp_kses_data( $_POST['message_verify_user_email'] ) : '';
			$options['verify_user_admin'] = isset( $_POST['verify_user_admin'] ) ? '1' : '0';
			$options['message_verify_user_admin'] = isset( $_POST['message_verify_user_admin'] ) ? wp_kses_data( $_POST['message_verify_user_admin'] ) : '';
			$options['delete_unverified_users_after'] = isset( $_POST['delete_unverified_users_after'] ) ? absint( $_POST['delete_unverified_users_after'] ) : '0';
			$options['registration_redirect_url'] = isset( $_POST['registration_redirect_url'] ) ? esc_url_raw( $_POST['registration_redirect_url'] ) : '';
			$options['verification_redirect_url'] = isset( $_POST['verification_redirect_url'] ) ? esc_url_raw( $_POST['verification_redirect_url'] ) : '';
			$options['autologin_user'] = isset( $_POST['autologin_user'] ) ? '1' : '0';

			$options['username_is_email'] = isset( $_POST['username_is_email'] ) ? '1' : '0';
			$options['double_check_email'] = isset( $_POST['double_check_email'] ) ? '1' : '0';
			$options['show_fields'] = ( isset( $_POST['show_fields'] ) && is_array( $_POST['show_fields'] ) ) ? $_POST['show_fields'] : array();
			$options['required_fields'] = ( isset( $_POST['required_fields'] ) && is_array( $_POST['required_fields'] ) ) ? $_POST['required_fields'] : array();
			$options['user_set_password'] = isset( $_POST['user_set_password'] ) ? '1' : '0';
			$options['min_password_length'] = isset( $_POST['min_password_length'] ) ? absint( $_POST['min_password_length'] ) : '0';
			$options['disable_password_confirmation'] = isset( $_POST['disable_password_confirmation'] ) ? '1' : '0';
			$options['show_password_meter'] = isset( $_POST['show_password_meter'] ) ? '1' : '0';
			$options['message_empty_password'] = isset( $_POST['message_empty_password'] ) ? wp_kses_data( $_POST['message_empty_password'] ) : '';
			$options['message_short_password'] = isset( $_POST['message_short_password'] ) ? wp_kses_data( $_POST['message_short_password'] ) : '';
			$options['message_bad_password'] = isset( $_POST['message_bad_password'] ) ? wp_kses_data( $_POST['message_bad_password'] ) : '';
			$options['message_good_password'] = isset( $_POST['message_good_password'] ) ? wp_kses_data( $_POST['message_good_password'] ) : '';
			$options['message_strong_password'] = isset( $_POST['message_strong_password'] ) ? wp_kses_data( $_POST['message_strong_password'] ) : '';
			$options['message_mismatch_password'] = isset( $_POST['message_mismatch_password'] ) ? wp_kses_data( $_POST['message_mismatch_password'] ) : '';
			$options['enable_invitation_code'] = isset( $_POST['enable_invitation_code'] ) ? '1' : '0';
			$invitation_code_bank = ( isset( $_POST['invitation_code_bank'] ) && is_array( $_POST['invitation_code_bank'] ) ) ? $_POST['invitation_code_bank'] : array();
			$options['require_invitation_code'] = isset( $_POST['require_invitation_code'] ) ? '1' : '0';
			$options['invitation_code_case_sensitive'] = isset( $_POST['invitation_code_case_sensitive'] ) ? '1' : '0';
			$options['invitation_code_unique'] = isset( $_POST['invitation_code_unique'] ) ? '1' : '0';
			$options['enable_invitation_tracking_widget'] = isset( $_POST['enable_invitation_tracking_widget'] ) ? '1' : '0';
			$options['show_disclaimer'] = isset( $_POST['show_disclaimer'] ) ? '1' : '0';
			$options['message_disclaimer_title'] = isset( $_POST['message_disclaimer_title'] ) ? sanitize_text_field( $_POST['message_disclaimer_title'] ) : '';
			$options['message_disclaimer'] = isset( $_POST['message_disclaimer'] ) ? wp_kses_data( $_POST['message_disclaimer'] ) : '';
			$options['require_disclaimer_agree'] = isset( $_POST['require_disclaimer_agree'] ) ? '1' : '0';
			$options['message_disclaimer_agree'] = isset( $_POST['message_disclaimer_agree'] ) ? sanitize_text_field( $_POST['message_disclaimer_agree'] ) : '';
			$options['show_license'] = isset( $_POST['show_license'] ) ? '1' : '0';
			$options['message_license_title'] = isset( $_POST['message_license_title'] ) ? sanitize_text_field( $_POST['message_license_title'] ) : '';
			$options['message_license'] = isset( $_POST['message_license'] ) ? wp_kses_data( $_POST['message_license'] ) : '';
			$options['require_license_agree'] = isset( $_POST['require_license_agree'] ) ? '1' : '0';
			$options['message_license_agree'] = isset( $_POST['message_license_agree'] ) ? sanitize_text_field( $_POST['message_license_agree'] ) : '';
			$options['show_privacy_policy'] = isset( $_POST['show_privacy_policy'] ) ? '1' : '0';
			$options['message_privacy_policy_title'] = isset( $_POST['message_privacy_policy_title'] ) ? sanitize_text_field( $_POST['message_privacy_policy_title'] ) : '';
			$options['message_privacy_policy'] = isset( $_POST['message_privacy_policy'] ) ? wp_kses_data( $_POST['message_privacy_policy'] ) : '';
			$options['require_privacy_policy_agree'] = isset( $_POST['require_privacy_policy_agree'] ) ? '1' : '0';
			$options['message_privacy_policy_agree'] = isset( $_POST['message_privacy_policy_agree'] ) ? sanitize_text_field( $_POST['message_privacy_policy_agree'] ) : '';
			$options['default_css'] = isset( $_POST['default_css'] ) ? '1' : '0';
			$options['required_fields_style'] = '';
			if ( isset( $_POST['required_fields_style'] ) ) {
				// Stolen from Jetpack 2.0.4 custom-css.php Jetpack_Custom_CSS::filter_attr()
				require_once( 'csstidy/class.csstidy.php' );
				$csstidy = new csstidy();
				$csstidy->set_cfg( 'remove_bslash', false );
				$csstidy->set_cfg( 'compress_colors', false );
				$csstidy->set_cfg( 'compress_font-weight', false );
				$csstidy->set_cfg( 'discard_invalid_properties', true );
				$csstidy->set_cfg( 'merge_selectors', false );
				$csstidy->set_cfg( 'remove_last_;', false );
				$csstidy->set_cfg( 'css_level', 'CSS3.0' );
				$required_fields_style = 'div {' . $_POST['required_fields_style'] . '}';
				$required_fields_style = preg_replace( '/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $required_fields_style );
				$required_fields_style = wp_kses_split( $required_fields_style, array(), array() );
				$csstidy->parse( $required_fields_style );
				$required_fields_style = $csstidy->print->plain();
				$required_fields_style = str_replace( array( "\n", "\r", "\t" ), '', $required_fields_style );
				preg_match( "/^div\s*{(.*)}\s*$/", $required_fields_style, $matches );
				if ( !empty( $matches[1] ) ) $options['required_fields_style'] = $matches[1];
			}
			isset( $_POST['required_fields_style'] ) ? sanitize_text_field( $_POST['required_fields_style'] ) :
			$options['required_fields_asterisk'] = isset( $_POST['required_fields_asterisk'] ) ? '1' : '0';
			$options['starting_tabindex'] = isset( $_POST['starting_tabindex'] ) ? absint( $_POST['starting_tabindex'] ) : '0';

			/*
			if ( isset( $_POST['datepicker_firstdayofweek'] ) ) $options['datepicker_firstdayofweek'] = absint( $_POST['datepicker_firstdayofweek'] );
			if ( isset( $_POST['datepicker_dateformat'] ) ) $options['datepicker_dateformat'] = sanitize_text_field( $_POST['datepicker_dateformat'] );
			if ( isset( $_POST['datepicker_startdate'] ) ) $options['datepicker_startdate'] = sanitize_text_field( $_POST['datepicker_startdate'] );
			if ( isset( $_POST['datepicker_calyear'] ) ) $options['datepicker_calyear'] = sanitize_text_field( $_POST['datepicker_calyear'] );
			if ( isset( $_POST['datepicker_calmonth'] ) ) $options['datepicker_calmonth'] = sanitize_text_field( $_POST['datepicker_calmonth'] );
			*/

			$options['disable_user_message_registered'] = isset( $_POST['disable_user_message_registered'] ) ? '1' : '0';
			$options['disable_user_message_created'] = isset( $_POST['disable_user_message_created'] ) ? '1' : '0';
			$options['custom_user_message'] = isset( $_POST['custom_user_message'] ) ? '1' : '0';
			$options['user_message_from_email'] = isset( $_POST['user_message_from_email'] ) ? sanitize_text_field( $_POST['user_message_from_email'] ) : '';
			$options['user_message_from_name'] = isset( $_POST['user_message_from_name'] ) ? sanitize_text_field( $_POST['user_message_from_name'] ) : '';
			$options['user_message_subject'] = isset( $_POST['user_message_subject'] ) ? sanitize_text_field( $_POST['user_message_subject'] ) : '';
			$options['user_message_body'] = isset( $_POST['user_message_body'] ) ? wp_kses_data( $_POST['user_message_body'] ) : '';
			$options['send_user_message_in_html'] = isset( $_POST['send_user_message_in_html'] ) ? '1' : '0';
			$options['user_message_newline_as_br'] = isset( $_POST['user_message_newline_as_br'] ) ? '1' : '0';
			$options['custom_verification_message'] = isset( $_POST['custom_verification_message'] ) ? '1' : '0';
			$options['verification_message_from_email'] = isset( $_POST['verification_message_from_email'] ) ? sanitize_text_field( $_POST['verification_message_from_email'] ) : '';
			$options['verification_message_from_name'] = isset( $_POST['verification_message_from_name'] ) ? sanitize_text_field( $_POST['verification_message_from_name'] ) : '';
			$options['verification_message_subject'] = isset( $_POST['verification_message_subject'] ) ? sanitize_text_field( $_POST['verification_message_subject'] ) : '';
			$options['verification_message_body'] = isset( $_POST['verification_message_body'] ) ? wp_kses_data( $_POST['verification_message_body'] ) : '';
			$options['send_verification_message_in_html'] = isset( $_POST['send_verification_message_in_html'] ) ? '1' : '0';
			$options['verification_message_newline_as_br'] = isset( $_POST['verification_message_newline_as_br'] ) ? '1' : '0';

			$options['disable_admin_message_registered'] = isset( $_POST['disable_admin_message_registered'] ) ? '1' : '0';
			$options['disable_admin_message_created'] = isset( $_POST['disable_admin_message_created'] ) ? '1' : '0';
			$options['admin_message_when_verified'] = isset( $_POST['admin_message_when_verified'] ) ? '1' : '0';
			$options['custom_admin_message'] = isset( $_POST['custom_admin_message'] ) ? '1' : '0';
			$options['admin_message_from_email'] = isset( $_POST['admin_message_from_email'] ) ? sanitize_text_field( $_POST['admin_message_from_email'] ) : '';
			$options['admin_message_from_name'] = isset( $_POST['admin_message_from_name'] ) ? sanitize_text_field( $_POST['admin_message_from_name'] ) : '';
			$options['admin_message_subject'] = isset( $_POST['admin_message_subject'] ) ? sanitize_text_field( $_POST['admin_message_subject'] ) : '';
			$options['admin_message_body'] = isset( $_POST['admin_message_body'] ) ? wp_kses_data( $_POST['admin_message_body'] ) : '';
			$options['send_admin_message_in_html'] = isset( $_POST['send_admin_message_in_html'] ) ? '1' : '0';
			$options['admin_message_newline_as_br'] = isset( $_POST['admin_message_newline_as_br'] ) ? '1' : '0';

			$options['custom_registration_page_css'] = '';
			if ( isset( $_POST['custom_registration_page_css'] ) ) {
				// Stolen from Jetpack 2.0.4 custom-css.php Jetpack_Custom_CSS::init()
				require_once( 'csstidy/class.csstidy.php' );
				$csstidy = new csstidy();
				$csstidy->set_cfg( 'remove_bslash', false );
				$csstidy->set_cfg( 'compress_colors', false );
				$csstidy->set_cfg( 'compress_font-weight', false );
				$csstidy->set_cfg( 'optimise_shorthands', 0 );
				$csstidy->set_cfg( 'remove_last_;', false );
				$csstidy->set_cfg( 'case_properties', false );
				$csstidy->set_cfg( 'discard_invalid_properties', true );
				$csstidy->set_cfg( 'css_level', 'CSS3.0' );
				$csstidy->set_cfg( 'preserve_css', true );
				$csstidy->set_cfg( 'template', dirname( __FILE__ ) . '/csstidy/wordpress-standard.tpl' );
				$custom_registration_page_css = $_POST['custom_registration_page_css'];
				$custom_registration_page_css = preg_replace( '/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $custom_registration_page_css );
				$custom_registration_page_css = str_replace( '<=', '&lt;=', $custom_registration_page_css );
				$custom_registration_page_css = wp_kses_split( $custom_registration_page_css, array(), array() );
				$custom_registration_page_css = str_replace( '&gt;', '>', $custom_registration_page_css );
				$custom_registration_page_css = strip_tags( $custom_registration_page_css );
				$csstidy->parse( $custom_registration_page_css );
				$options['custom_registration_page_css'] = $csstidy->print->plain();
			}

			$options['custom_login_page_css'] = '';
			if ( isset( $_POST['custom_login_page_css'] ) ) {
				// Stolen from Jetpack 2.0.4 custom-css.php Jetpack_Custom_CSS::init()
				require_once( 'csstidy/class.csstidy.php' );
				$csstidy = new csstidy();
				$csstidy->set_cfg( 'remove_bslash', false );
				$csstidy->set_cfg( 'compress_colors', false );
				$csstidy->set_cfg( 'compress_font-weight', false );
				$csstidy->set_cfg( 'optimise_shorthands', 0 );
				$csstidy->set_cfg( 'remove_last_;', false );
				$csstidy->set_cfg( 'case_properties', false );
				$csstidy->set_cfg( 'discard_invalid_properties', true );
				$csstidy->set_cfg( 'css_level', 'CSS3.0' );
				$csstidy->set_cfg( 'preserve_css', true );
				$csstidy->set_cfg( 'template', dirname( __FILE__ ) . '/csstidy/wordpress-standard.tpl' );
				$custom_login_page_css = $_POST['custom_login_page_css'];
				$custom_login_page_css = preg_replace( '/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $custom_login_page_css );
				$custom_login_page_css = str_replace( '<=', '&lt;=', $custom_login_page_css );
				$custom_login_page_css = wp_kses_split( $custom_login_page_css, array(), array() );
				$custom_login_page_css = str_replace( '&gt;', '>', $custom_login_page_css );
				$custom_login_page_css = strip_tags( $custom_login_page_css );
				$csstidy->parse( $custom_login_page_css );
				$options['custom_login_page_css'] = $csstidy->print->plain();
			}
			 
			$options['filter_random_password'] = isset( $_POST['filter_random_password'] ) ? '1' : '0';

			$usermeta_key = 0;
			if ( isset( $_POST['label'] ) ) {
				foreach ( $_POST['label'] as $index => $v ) {
					$meta_field = array();
					if ( !empty( $_POST['label'][$index] ) ) {
						$meta_field['label'] = isset( $_POST['label'][$index] ) ? sanitize_text_field( $_POST['label'][$index] ) : '';
						$meta_field['meta_key'] = isset( $_POST['meta_key'][$index] ) ? $_POST['meta_key'][$index] : '';
						$meta_field['display'] = isset( $_POST['display'][$index] ) ? sanitize_text_field( $_POST['display'][$index] ) : '';
						$meta_field['options'] = isset( $_POST['options'][$index] ) ? sanitize_text_field( $_POST['options'][$index] ) : '';
						$meta_field['show_datepicker'] = '0';
						$meta_field['escape_url'] = '0';
						$meta_field['show_on_profile'] = isset( $_POST['show_on_profile'][$index] ) ? '1' : '0';
						$meta_field['show_on_registration'] = isset( $_POST['show_on_registration'][$index] ) ? '1' : '0';
						$meta_field['require_on_registration'] = isset( $_POST['require_on_registration'][$index] ) ? '1' : '0';
						if ( empty( $meta_field['meta_key'] ) ) {
							if ( $meta_field['display'] )
							$meta_field['meta_key'] = 'rpr_' . $meta_field['label'];
						}
						$meta_field['meta_key'] = sanitize_text_field( $register_plus_redux->clean_text( $meta_field['meta_key'] ) );
					}
					$redux_usermeta[$usermeta_key++] = $meta_field;
				}
			}

			if ( isset( $_POST['newMetaFields'] ) ) {
				foreach ( $_POST['newMetaFields'] as $label ) {
					$meta_field = array();
					$meta_field['label'] = sanitize_text_field( $label );
					$meta_field['meta_key'] = '';
					$meta_field['display'] = '';
					$meta_field['options'] = '';
					$meta_field['show_datepicker'] = '0';
					$meta_field['escape_url'] = '0';
					$meta_field['show_on_profile'] = '0';
					$meta_field['show_on_registration'] = '0';
					$meta_field['require_on_registration'] = '0';
					if ( empty( $meta_field['meta_key'] ) ) {
						if ( $meta_field['display'] )
						$meta_field['meta_key'] = 'rpr_' . $meta_field['label'];
					}
					$meta_field['meta_key'] = sanitize_key( $register_plus_redux->clean_text( $meta_field['meta_key'] ) );
					$redux_usermeta[$usermeta_key++] = $meta_field;
				}
			}

			$register_plus_redux->rpr_update_options( $options );
			if ( !empty( $invitation_code_bank ) ) update_option( 'register_plus_redux_invitation_code_bank-rv1', $invitation_code_bank );
			if ( !empty( $redux_usermeta ) ) update_option( 'register_plus_redux_usermeta-rv2', $redux_usermeta );
		}
	}
}

if ( class_exists( 'RPR_Admin_Menu' ) ) $rpr_admin_menu = new RPR_Admin_Menu();
?>