<?php
if ( !class_exists( 'RPR_Admin' ) ) {
	class RPR_Admin {
		function __construct() {
			add_action( 'init', array( $this, 'rpr_admin_init' ), 10, 1 ); // Runs after WordPress has finished loading but before any headers are sent.
			add_action( 'init', array( $this, 'rpr_delete_unverified_users' ), 10, 1 ); // Runs after WordPress has finished loading but before any headers are sent.
		}

		function rpr_admin_init() {
			global $register_plus_redux;
			// TODO: Write function to migrate register plus settings to redux
			// should not be in init, likely to use similar code to rename

			// Rename options as necessary, prior to defaulting any new options
			$rename_options = array(
				'registration_redirect' => 'registration_redirect_url'
			);

			foreach ( $rename_options as $old_option => $new_option ) {
				$old_value = $register_plus_redux->rpr_get_option( $old_option );
				$new_value = $register_plus_redux->rpr_get_option( $new_option );
				if ( !isset( $new_value ) && isset( $old_value ) ) {
					$register_plus_redux->rpr_set_option( $new_option, $old_value );
					$register_plus_redux->rpr_unset_option( $old_option );
					$updated = TRUE;
				}
			}

			// Load defaults for any options
			foreach ( $register_plus_redux->default_options() as $option => $default_value ) {
				$option_value = $register_plus_redux->rpr_get_option( $option );
				if ( !isset( $option_value ) ) {
					$register_plus_redux->rpr_set_option( $option, $default_value );
					$updated = TRUE;
				}
			}

			if ( !empty( $updated ) ) $register_plus_redux->rpr_update_options();

			// Added 03/28/11 in 3.7.4 converting invitation_code_bank to own option
			$old_invitation_code_bank = $register_plus_redux->rpr_get_option( 'invitation_code_bank' );
			$new_invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
			if ( !isset( $new_invitation_code_bank ) && isset( $old_invitation_code_bank ) ) {
				update_option( 'register_plus_redux_invitation_code_bank-rv1', $old_invitation_code_bank );
				//TODO: Confirm old invitation codes are migrating successfully, then kill old option
				//$register_plus_redux->rpr_unset_option( $old_option );
			}

			// Added 03/28/11 in 3.7.4 converting custom fields
			$redux_usermeta = get_option( 'register_plus_redux_usermeta-rv2' );
			if ( empty( $redux_usermeta ) ) {
				$redux_usermeta_rv1 = get_option( 'register_plus_redux_usermeta-rv1' );
				$custom_fields = get_option( 'register_plus_redux_custom_fields' );
				if ( !empty( $redux_usermeta_rv1 ) ) {
					$redux_usermeta = array();
					if ( !is_array( $redux_usermeta_rv1 ) ) $redux_usermeta_rv1 = array();
					foreach ( $redux_usermeta_rv1 as $k => $meta_field_rv1 ) {
						$meta_field = array();
						$meta_field['label'] = $meta_field_rv1['label'];
						$meta_field['meta_key'] = $meta_field_rv1['meta_key'];
						$meta_field['display'] = $meta_field_rv1['control'];
						$meta_field['options'] = $meta_field_rv1['options'];
						$meta_field['show_datepicker'] = '0';
						$meta_field['escape_url'] = '0';
						$meta_field['show_on_profile'] = $meta_field_rv1['show_on_profile'];
						$meta_field['show_on_registration'] = $meta_field_rv1['show_on_registration'];
						$meta_field['require_on_registration'] = $meta_field_rv1['required_on_registration'];
						if ( $meta_field['display'] == 'text' ) $meta_field['display'] = 'textbox';
						elseif ( $meta_field['display'] == 'date' ) {
							$meta_field['display'] = 'text';
							$meta_field['show_datepicker'] = '1';
						}
						elseif ( $meta_field['display'] == 'url' ) {
							$meta_field['display'] = 'text';
							$meta_field['escape_url'] = '1';
						}
						elseif ( $meta_field['display'] == 'static' ) $meta_field['display'] = 'text';
						$redux_usermeta[$k] = $meta_field;
					}
					// TODO: Confirm old custom fields are migrating successfully, then kill old option
					//delete_option( 'register_plus_redux_usermeta-rv1' );
					if ( !empty( $redux_usermeta ) ) update_option( 'register_plus_redux_usermeta-rv2', $redux_usermeta );
				} 
				elseif ( !empty( $custom_fields ) ) {
					$redux_usermeta = array();
					if ( !is_array( $custom_fields ) ) $custom_fields = array();
					foreach ( $custom_fields as $k => $custom_field ) {
						$meta_field = array();
						$meta_field['label'] = $custom_field['custom_field_name'];
						$meta_field['meta_key'] = $register_plus_redux->clean_text( $custom_field['custom_field_name'] );
						$meta_field['display'] = $custom_field['custom_field_type'];
						$meta_field['options'] = $custom_field['custom_field_options'];
						$meta_field['show_datepicker'] = '0';
						$meta_field['escape_url'] = '0';
						$meta_field['show_on_profile'] = $custom_field['show_on_profile'];
						$meta_field['show_on_registration'] = $custom_field['show_on_registration'];
						$meta_field['require_on_registration'] = $custom_field['required_on_registration'];
						if ( $meta_field['display'] == 'text' ) $meta_field['display'] = 'textbox';
						elseif ( $meta_field['display'] == 'date' ) {
							$meta_field['display'] = 'text';
							$meta_field['show_datepicker'] = '1';
						}
						elseif ( $meta_field['display'] == 'url' ) {
							$meta_field['display'] = 'text';
							$meta_field['escape_url'] = '1';
						}
						elseif ( $meta_field['display'] == 'static' ) $meta_field['display'] = 'text';
						$redux_usermeta[$k] = $meta_field;
					}
					// TODO: Confirm old custom fields are migrating successfully, then kill old option
					//delete_option( 'register_plus_redux_custom_fields' );
					if ( !empty( $redux_usermeta ) ) update_option( 'register_plus_redux_usermeta-rv2', $redux_usermeta );
				}
			}
		}

		function rpr_delete_unverified_users() {
			global $register_plus_redux;
			$delete_unverified_users_after = $register_plus_redux->rpr_get_option( 'delete_unverified_users_after' );
			if ( is_numeric( $delete_unverified_users_after ) && absint( $delete_unverified_users_after ) > 0 ) {
				global $wpdb;
				$unverified_users = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'stored_user_login';" );
				//TODO: How often is this triggered?
				if ( !empty( $unverified_users ) ) {
					$expirationdate = date( 'Ymd', strtotime( '-' . absint( $register_plus_redux->rpr_get_option( 'delete_unverified_users_after' ) ) . ' days' ) );
					//NOTE: necessary for wp_delete_user
					if ( !function_exists( 'wp_delete_user' ) ) require_once( ABSPATH . '/wp-admin/includes/user.php' );
					foreach ( $unverified_users as $unverified_user ) {
						$user_info = get_userdata( $unverified_user->user_id );
						if ( !empty( $user_info->stored_user_login ) && ( substr( $user_info->user_login, 0, 11 ) == 'unverified_' ) ) {
							if ( date( 'Ymd', strtotime( $user_info->user_registered ) ) < $expirationdate ) {
								if ( !empty( $user_info->email_verification_sent ) ) {
									if ( date( 'Ymd', strtotime( $user_info->email_verification_sent ) ) < $expirationdate ) {
										if ( !empty( $user_info->email_verified ) ) {
											if ( date( 'Ymd', strtotime( $user_info->email_verified ) ) < $expirationdate ) {
												wp_delete_user( $unverified_user->user_id );
											}
										}
										else {
											wp_delete_user( $unverified_user->user_id );
										}
									}
								}
								else {
									wp_delete_user( $unverified_user->user_id );
								}
							}
						}
					}
				}
			}
		}
	}
}

if ( class_exists( 'RPR_Admin' ) ) $rpr_admin = new RPR_Admin();
?>