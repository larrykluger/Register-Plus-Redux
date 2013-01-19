<?php
if ( !class_exists( 'RPR_Dashboard_Widget' ) ) {
	class RPR_Dashboard_Widget {
		function __construct() {
			add_action( 'wp_dashboard_setup', array( $this, 'rpr_dashboard_setup' ) );
		}

		function rpr_dashboard_setup() {
			global $register_plus_redux;
			if ( $register_plus_redux->rpr_get_option( 'enable_invitation_tracking_widget' ) == TRUE )
				wp_add_dashboard_widget( 'rpr_invitation_tracking_widget', __( 'Invitation Code Tracking', 'register-plus-redux' ), array( $this, 'rpr_invitation_tracking_widget' ) );
		}

		function rpr_invitation_tracking_widget() {
			global $wpdb;
			$invitation_code_bank = get_option( 'register_plus_redux_invitation_code_bank-rv1' );
			foreach ( $invitation_code_bank as $invitation_code ) {
				$user_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'invitation_code' AND meta_value = %s;", $invitation_code ) );
				echo '<h3>', esc_html( $invitation_code ), ': <small style="font-weight:normal">', sprintf( __( '%s Users Registered.', 'register-plus-redux' ), $user_count ), '</small></h3>';
			}		
		}
	}
}

if ( class_exists( 'RPR_Dashboard_Widget' ) ) $rpr_dashboard_widget = new RPR_Dashboard_Widget();
?>