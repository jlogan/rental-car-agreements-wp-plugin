<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Shortcodes {

	public function __construct() {
		add_shortcode( 'rental_car_inventory', array( $this, 'render_inventory' ) );
		add_shortcode( 'rental_car_booking', array( $this, 'render_booking_form' ) );
		
		// Handle form submission via Init
		add_action( 'init', array( $this, 'process_booking_submission' ) );

        // Handle AJAX for Modal
        add_action( 'wp_ajax_rca_load_booking_form', array( $this, 'ajax_load_booking_form' ) );
        add_action( 'wp_ajax_nopriv_rca_load_booking_form', array( $this, 'ajax_load_booking_form' ) );

        // Output Modal in Footer
        add_action( 'wp_footer', array( $this, 'render_modal_markup' ) );
	}

    /**
     * Render Modal Markup in Footer
     */
    public function render_modal_markup() {
        ?>
        <div id="rca-booking-modal" class="rca-modal" style="display: none;">
            <div class="rca-modal-content">
                <div class="rca-modal-header-bar">
                    <button type="button" class="rca-close-btn rca-close">
                        <span>&times;</span> Close
                    </button>
                </div>
                <div class="rca-modal-inner">
                    <div id="rca-modal-body">
                        <!-- AJAX content loads here -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

	/**
	 * [rental_car_inventory]
	 */
	public function render_inventory( $atts ) {
		$query = new WP_Query( array(
			'post_type'      => 'rental_vehicle',
			'posts_per_page' => -1,
			'meta_key'       => '_rca_status',
			'meta_value'     => 'available', 
		) );

		ob_start();
		include RCA_PLUGIN_DIR . 'templates/inventory-grid.php';
		return ob_get_clean();
	}

	/**
	 * [rental_car_booking vehicle_id="123"]
	 */
	public function render_booking_form( $atts ) {
		$atts = shortcode_atts( array(
			'vehicle_id' => 0,
		), $atts );

		$vehicle_id = intval( $atts['vehicle_id'] );
		
		// If ID comes from URL param, override
		if ( isset( $_GET['vehicle_id'] ) ) {
			$vehicle_id = intval( $_GET['vehicle_id'] );
		}

		$vehicle = get_post( $vehicle_id );

		ob_start();
		include RCA_PLUGIN_DIR . 'templates/booking-form.php';
		return ob_get_clean();
	}

    /**
     * AJAX Callback to load form
     */
    public function ajax_load_booking_form() {
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        if($vehicle_id) {
            echo do_shortcode('[rental_car_booking vehicle_id="' . $vehicle_id . '"]');
        } else {
            echo 'Vehicle not found.';
        }
        wp_die();
    }

	public function process_booking_submission() {
		if ( ! isset( $_POST['rca_submit_booking'] ) ) {
			return;
		}

		if ( ! isset( $_POST['rca_booking_nonce'] ) || ! wp_verify_nonce( $_POST['rca_booking_nonce'], 'rca_submit_booking_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Sanitize Inputs
		$vehicle_id = intval( $_POST['vehicle_id'] );
		$fullname   = sanitize_text_field( $_POST['rca_fullname'] );
		$email      = sanitize_email( $_POST['rca_email'] );
		$phone      = sanitize_text_field( $_POST['rca_phone'] );
		$license    = sanitize_text_field( $_POST['rca_license'] );
		$address    = sanitize_textarea_field( $_POST['rca_address'] );
		$start_date = sanitize_text_field( $_POST['rca_start_date'] );
		$end_date   = sanitize_text_field( $_POST['rca_end_date'] );
		$insurance  = sanitize_text_field( $_POST['rca_insurance'] );
		$terms      = isset( $_POST['rca_terms'] ) ? 'yes' : 'no';

		if( $terms !== 'yes' ) {
			wp_die('You must accept the terms.');
		}

		// Create Booking Post
		$post_title = $fullname . ' - ' . $start_date;
		
		$booking_id = wp_insert_post( array(
			'post_type'   => 'rental_booking',
			'post_title'  => $post_title,
			'post_status' => 'publish', 
		) );

		if ( $booking_id ) {
			update_post_meta( $booking_id, '_rca_vehicle_id', $vehicle_id );
			update_post_meta( $booking_id, '_rca_customer_name', $fullname );
			update_post_meta( $booking_id, '_rca_customer_email', $email );
			update_post_meta( $booking_id, '_rca_customer_phone', $phone );
			update_post_meta( $booking_id, '_rca_customer_license', $license );
			update_post_meta( $booking_id, '_rca_customer_address', $address );
			update_post_meta( $booking_id, '_rca_start_date', $start_date );
			update_post_meta( $booking_id, '_rca_end_date', $end_date );
			update_post_meta( $booking_id, '_rca_insurance_option', $insurance );
			update_post_meta( $booking_id, '_rca_terms_accepted', 'yes' );
			update_post_meta( $booking_id, '_rca_booking_status', 'pending' );

			// Send Notifications
			$this->send_emails( $booking_id, $email, $fullname );

			// Redirect to success
			$redirect = add_query_arg( 'rca_booking_success', '1', get_permalink() );
			wp_redirect( $redirect );
			exit;
		}
	}

	private function send_emails( $booking_id, $customer_email, $name ) {
		$options = get_option( 'rca_settings' );
		$admin_email = isset( $options['business_email'] ) ? $options['business_email'] : get_option( 'admin_email' );

		// Admin Notification
		$subject = 'New Rental Booking Request: #' . $booking_id;
		$message = "A new booking request has been received from $name.\n\nPlease login to the admin dashboard to view and approve it.";
		wp_mail( $admin_email, $subject, $message );

		// Customer Confirmation
		$subject_customer = 'Booking Request Received';
		$message_customer = "Hi $name,\n\nWe have received your booking request. We will review it and contact you shortly.\n\nThank you!";
		wp_mail( $customer_email, $subject_customer, $message_customer );
	}
}
