<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Agreement_Template {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_hidden_page' ) );
	}

	public function register_hidden_page() {
		add_submenu_page(
			null, // Hidden from menu
			'Rental Agreement',
			'Rental Agreement',
			'edit_posts',
			'rental_car_agreement',
			array( $this, 'render_agreement' )
		);
	}

	public function render_agreement() {
		if ( ! isset( $_GET['booking_id'] ) ) {
			wp_die( 'No booking ID provided.' );
		}

		$booking_id = intval( $_GET['booking_id'] );
		$booking = get_post( $booking_id );

		if ( ! $booking || $booking->post_type !== 'rental_booking' ) {
			wp_die( 'Invalid booking.' );
		}

		// Gather Data
		$vehicle_id = get_post_meta( $booking_id, '_rca_vehicle_id', true );
		$vehicle = get_post( $vehicle_id );

		$meta = array(
			'name'    => get_post_meta( $booking_id, '_rca_customer_name', true ),
			'address' => get_post_meta( $booking_id, '_rca_customer_address', true ),
			'phone'   => get_post_meta( $booking_id, '_rca_customer_phone', true ),
			'email'   => get_post_meta( $booking_id, '_rca_customer_email', true ),
			'license' => get_post_meta( $booking_id, '_rca_customer_license', true ),
			'start'   => get_post_meta( $booking_id, '_rca_start_date', true ),
			'end'     => get_post_meta( $booking_id, '_rca_end_date', true ),
			'insurance' => get_post_meta( $booking_id, '_rca_insurance_option', true ),
		);

		$v_meta = array();
		if ( $vehicle ) {
			$v_meta = array(
				'make'  => get_post_meta( $vehicle->ID, '_rca_make', true ),
				'model' => get_post_meta( $vehicle->ID, '_rca_model', true ),
				'year'  => get_post_meta( $vehicle->ID, '_rca_year', true ),
				'color' => get_post_meta( $vehicle->ID, '_rca_color', true ),
				'vin'   => get_post_meta( $vehicle->ID, '_rca_vin', true ),
				'plate' => get_post_meta( $vehicle->ID, '_rca_plate', true ),
				'rate_d' => get_post_meta( $vehicle->ID, '_rca_daily_rate', true ),
				'rate_w' => get_post_meta( $vehicle->ID, '_rca_weekly_rate', true ),
			);
		}

		$settings = get_option( 'rca_settings' );

		// Load Template
		include RCA_PLUGIN_DIR . 'templates/agreement-template.php';
	}
}

