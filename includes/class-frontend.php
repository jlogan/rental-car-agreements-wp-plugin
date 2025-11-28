<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Frontend {

	public function __construct() {
		add_shortcode( 'rental_car_inventory', array( $this, 'render_inventory' ) );
		add_shortcode( 'rental_car_booking', array( $this, 'render_booking_form' ) );
		
		// Handle form submission via Init
		add_action( 'init', array( $this, 'process_booking_submission' ) );

        // Handle AJAX for Modal
        add_action( 'wp_ajax_rca_load_booking_form', array( $this, 'ajax_load_booking_form' ) );
        add_action( 'wp_ajax_nopriv_rca_load_booking_form', array( $this, 'ajax_load_booking_form' ) );
        
        // Handle AJAX form submission - start output buffering early
        add_action( 'wp_ajax_rca_submit_booking_ajax', array( $this, 'ajax_submit_booking' ), 1 );
        add_action( 'wp_ajax_nopriv_rca_submit_booking_ajax', array( $this, 'ajax_submit_booking' ), 1 );
        
        // Suppress errors for AJAX requests
        add_action( 'wp_ajax_rca_submit_booking_ajax', array( $this, 'suppress_errors_for_ajax' ), 0 );
        add_action( 'wp_ajax_nopriv_rca_submit_booking_ajax', array( $this, 'suppress_errors_for_ajax' ), 0 );

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
		// Get settings from ACF options
		$items_count = get_field( 'vehicles_count', 'option' );
		$items_per_row = get_field( 'vehicles_per_row', 'option' );
		
		// Default values if not set
		if ( $items_count === false || $items_count === null ) {
			$items_count = -1;
		}
		if ( $items_per_row === false || $items_per_row === null ) {
			$items_per_row = 3;
		}

		$query = new WP_Query( array(
			'post_type'      => 'rental_vehicle',
			'posts_per_page' => intval( $items_count ),
			'meta_key'       => '_rca_status',
			'meta_value'     => 'available', 
		) );

		// Pass items_per_row to template
		$items_per_row = intval( $items_per_row );

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

    /**
     * Suppress errors for AJAX requests
     */
    public function suppress_errors_for_ajax() {
        // Start output buffering as early as possible
        if ( ! ob_get_level() ) {
            ob_start();
        }
        
        // Suppress all notices and warnings for AJAX
        error_reporting( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );
        ini_set( 'display_errors', 0 );
    }

    /**
     * AJAX Callback to submit booking form
     */
    public function ajax_submit_booking() {
        // Ensure we're in AJAX mode
        if ( ! defined( 'DOING_AJAX' ) ) {
            define( 'DOING_AJAX', true );
        }
        
        // Ensure output buffering is active
        if ( ! ob_get_level() ) {
            ob_start();
        }
        
        // Set proper headers for JSON response
        if ( ! headers_sent() ) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        
        try {
            // Verify nonce
            if ( ! isset( $_POST['rca_booking_nonce'] ) || ! wp_verify_nonce( $_POST['rca_booking_nonce'], 'rca_submit_booking_action' ) ) {
                // Clean all output
                while ( ob_get_level() ) {
                    ob_end_clean();
                }
                wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ) );
                wp_die();
            }

            // Use the same processing logic as regular submission
            $this->process_booking_submission_ajax();
            
        } catch ( Exception $e ) {
            // Clean all output
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'An error occurred. Please try again.' ) );
            wp_die();
        } catch ( Error $e ) {
            // Clean all output
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'An error occurred. Please try again.' ) );
            wp_die();
        }
    }

    /**
     * Process booking submission for AJAX (returns JSON instead of redirecting)
     */
    private function process_booking_submission_ajax() {
        // Validate required fields first
        $required_fields = array(
            'vehicle_id',
            'rca_fullname',
            'rca_email',
            'rca_phone',
            'rca_license',
            'rca_address',
            'rca_agreement_date',
            'rca_signature',
            'rca_start_date',
            'rca_end_date',
            'rca_base_fee_weekly',
            'rca_insurance_option',
        );

        foreach ( $required_fields as $field ) {
            if ( ! isset( $_POST[ $field ] ) || empty( $_POST[ $field ] ) ) {
                // Clean all output buffers
                while ( ob_get_level() ) {
                    ob_end_clean();
                }
                wp_send_json_error( array( 'message' => 'Please fill in all required fields. Missing: ' . $field ) );
                wp_die();
            }
        }

        // Generate Renter ID
        $renter_id = $this->generate_renter_id();

        // Sanitize and Validate Inputs - Renter Information
        $vehicle_id = intval( $_POST['vehicle_id'] );
        
        // Validate and sanitize full name
        $fullname = sanitize_text_field( $_POST['rca_fullname'] );
        if ( empty( $fullname ) || strlen( $fullname ) < 2 || strlen( $fullname ) > 100 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid name (2-100 characters).' ) );
            wp_die();
        }
        if ( ! preg_match( '/^[A-Za-z\s\'-]+$/', $fullname ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Name can only contain letters, spaces, hyphens, and apostrophes.' ) );
            wp_die();
        }
        
        // Sanitize email (browser handles validation)
        $email = sanitize_email( $_POST['rca_email'] );
        
        // Sanitize phone (no validation, browser handles it)
        $phone = sanitize_text_field( $_POST['rca_phone'] );
        
        // Sanitize license (no validation)
        $license = sanitize_text_field( $_POST['rca_license'] );
        
        // Sanitize address (no validation)
        $address = sanitize_textarea_field( $_POST['rca_address'] );
        
        // Validate agreement date
        $agreement_date = sanitize_text_field( $_POST['rca_agreement_date'] );
        if ( empty( $agreement_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $agreement_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid date.' ) );
            wp_die();
        }
        // Check if date is not in the future
        if ( strtotime( $agreement_date ) > strtotime( 'today' ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Agreement date cannot be in the future.' ) );
            wp_die();
        }
        
        // Validate start date
        $start_date = sanitize_text_field( $_POST['rca_start_date'] );
        if ( empty( $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid start date.' ) );
            wp_die();
        }
        // Check if start date is not before today
        if ( strtotime( $start_date ) < strtotime( 'today' ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Start date cannot be before today.' ) );
            wp_die();
        }
        
        // Validate end date
        $end_date = sanitize_text_field( $_POST['rca_end_date'] );
        if ( empty( $end_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid end date.' ) );
            wp_die();
        }
        // Check if end date is not before today
        if ( strtotime( $end_date ) < strtotime( 'today' ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'End date cannot be before today.' ) );
            wp_die();
        }
        // Check if end date is not before start date
        if ( strtotime( $end_date ) < strtotime( $start_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'End date cannot be before start date.' ) );
            wp_die();
        }
        
        // Validate signature
        $signature = sanitize_text_field( $_POST['rca_signature'] );
        if ( empty( $signature ) || strlen( $signature ) < 2 || strlen( $signature ) > 100 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter your full name as signature (2-100 characters).' ) );
            wp_die();
        }

        // Vehicle Information Snapshot (captured at time of booking)
        $vehicle_make = sanitize_text_field( $_POST['rca_vehicle_make'] );
        $vehicle_model = sanitize_text_field( $_POST['rca_vehicle_model'] );
        $vehicle_year = sanitize_text_field( $_POST['rca_vehicle_year'] );
        $vehicle_vin = sanitize_text_field( $_POST['rca_vehicle_vin'] );
        $vehicle_color = sanitize_text_field( $_POST['rca_vehicle_color'] );
        $vehicle_plate = sanitize_text_field( $_POST['rca_vehicle_plate'] );
        $vehicle_weekly_rate = sanitize_text_field( $_POST['rca_vehicle_weekly_rate'] );
        $vehicle_title = sanitize_text_field( $_POST['rca_vehicle_title'] );
        
        // Get vehicle image from backend at time of submission
        $vehicle_image_id = 0;
        $vehicle_image_url = '';
        if ( $vehicle_id ) {
            $vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
            if ( $vehicle_image_id ) {
                $vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
            }
        }

        // Rental Details
        $start_date = sanitize_text_field( $_POST['rca_start_date'] );
        $end_date   = sanitize_text_field( $_POST['rca_end_date'] );
        $base_fee_weekly = sanitize_text_field( $_POST['rca_base_fee_weekly'] );

        // Insurance
        $insurance_option = sanitize_text_field( $_POST['rca_insurance_option'] );
        $marketing_optin = isset( $_POST['rca_marketing_optin'] ) ? 'yes' : 'no';

        // Verify all required initials are checked
        $required_initials = array(
            'rca_rental_term_initial',
            'rca_mileage_initial',
            'rca_rental_fees_initial',
            'rca_keys_initial',
            'rca_damage_responsibility_initial',
            'rca_insurance_initial',
            'rca_insurance_claims_initial',
            'rca_indemnification_initial',
            'rca_risk_acknowledgment_initial',
            'rca_release_waiver_initial',
            'rca_violation_charges_initial',
            'rca_vehicle_condition_initial',
            'rca_checkin_checkout_initial',
            'rca_free_will_initial',
            'rca_discriminatory_initial',
            'rca_ecpa_initial',
            'rca_optin_optout_initial',
            'rca_arbitration_initial',
            'rca_breach_contract_initial',
            'rca_your_property_initial',
            'rca_returning_vehicle_initial',
            'rca_out_of_state_initial',
        );

        foreach ( $required_initials as $initial_field ) {
            if ( ! isset( $_POST[ $initial_field ] ) ) {
                // Clean all output buffers
                while ( ob_get_level() ) {
                    ob_end_clean();
                }
                wp_send_json_error( array( 'message' => 'You must acknowledge all sections of the agreement. Please review and check all required fields.' ) );
                wp_die();
            }
        }

        // Get vehicle title for the booking title
        $vehicle_title = get_the_title( $vehicle_id );
        if ( empty( $vehicle_title ) ) {
            $vehicle_title = 'Unknown Vehicle';
        }
        
        // Create Booking Post with title format: RENTER-XXXX - VEHICLE NAME
        $post_title = $renter_id . ' - ' . $vehicle_title;
        
        $booking_id = wp_insert_post( array(
            'post_type'   => 'rental_booking',
            'post_title'  => $post_title,
            'post_status' => 'publish', 
        ) );

        if ( $booking_id ) {
            // Renter Information
            update_post_meta( $booking_id, '_rca_vehicle_id', $vehicle_id );
            update_post_meta( $booking_id, '_rca_customer_name', $fullname );
            update_post_meta( $booking_id, '_rca_customer_email', $email );
            update_post_meta( $booking_id, '_rca_customer_phone', $phone );
            update_post_meta( $booking_id, '_rca_customer_license', $license );
            update_post_meta( $booking_id, '_rca_customer_address', $address );
            update_post_meta( $booking_id, '_rca_renter_id', $renter_id );
            update_post_meta( $booking_id, '_rca_agreement_date', $agreement_date );
            update_post_meta( $booking_id, '_rca_signature', $signature );

            // Vehicle Information Snapshot (stored at time of booking)
            update_post_meta( $booking_id, '_rca_vehicle_make', $vehicle_make );
            update_post_meta( $booking_id, '_rca_vehicle_model', $vehicle_model );
            update_post_meta( $booking_id, '_rca_vehicle_year', $vehicle_year );
            update_post_meta( $booking_id, '_rca_vehicle_vin', $vehicle_vin );
            update_post_meta( $booking_id, '_rca_vehicle_color', $vehicle_color );
            update_post_meta( $booking_id, '_rca_vehicle_plate', $vehicle_plate );
            update_post_meta( $booking_id, '_rca_vehicle_weekly_rate_snapshot', $vehicle_weekly_rate );
            update_post_meta( $booking_id, '_rca_vehicle_title_snapshot', $vehicle_title );
            update_post_meta( $booking_id, '_rca_vehicle_image_url_snapshot', $vehicle_image_url );
            update_post_meta( $booking_id, '_rca_vehicle_image_id_snapshot', $vehicle_image_id );

            // Rental Details
            update_post_meta( $booking_id, '_rca_start_date', $start_date );
            update_post_meta( $booking_id, '_rca_end_date', $end_date );
            update_post_meta( $booking_id, '_rca_base_fee_weekly', $base_fee_weekly );

            // Insurance
            update_post_meta( $booking_id, '_rca_insurance_option', $insurance_option );
            update_post_meta( $booking_id, '_rca_marketing_optin', $marketing_optin );

            // Store all initials as confirmation
            $initials_data = array();
            foreach ( $required_initials as $initial_field ) {
                $initials_data[ $initial_field ] = isset( $_POST[ $initial_field ] ) ? 'yes' : 'no';
            }
            update_post_meta( $booking_id, '_rca_all_initials', $initials_data );

            // Legacy fields for compatibility
            update_post_meta( $booking_id, '_rca_terms_accepted', 'yes' );
            update_post_meta( $booking_id, '_rca_booking_status', 'pending' );

            // Send Notifications (suppress any output from email function)
            $this->send_emails( $booking_id, $email, $fullname );

            // Clean all output buffers before sending JSON
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_success( array( 'message' => 'Booking submitted successfully!', 'booking_id' => $booking_id ) );
            wp_die();
        } else {
            // Clean all output buffers before sending JSON
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Failed to create booking. Please try again.' ) );
            wp_die();
        }
    }

	public function process_booking_submission() {
		// Don't process if this is an AJAX request
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		
		if ( ! isset( $_POST['rca_submit_booking'] ) ) {
			return;
		}

		if ( ! isset( $_POST['rca_booking_nonce'] ) || ! wp_verify_nonce( $_POST['rca_booking_nonce'], 'rca_submit_booking_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Generate Renter ID
		$renter_id = $this->generate_renter_id();

		// Sanitize Inputs - Renter Information
		$vehicle_id = intval( $_POST['vehicle_id'] );
		$fullname   = sanitize_text_field( $_POST['rca_fullname'] );
		$email      = sanitize_email( $_POST['rca_email'] );
		$phone      = sanitize_text_field( $_POST['rca_phone'] );
		$license    = sanitize_text_field( $_POST['rca_license'] );
		$address    = sanitize_textarea_field( $_POST['rca_address'] );
		$agreement_date = sanitize_text_field( $_POST['rca_agreement_date'] );
		$signature  = sanitize_text_field( $_POST['rca_signature'] );

		// Vehicle Information Snapshot (captured at time of booking)
		$vehicle_make = sanitize_text_field( $_POST['rca_vehicle_make'] );
		$vehicle_model = sanitize_text_field( $_POST['rca_vehicle_model'] );
		$vehicle_year = sanitize_text_field( $_POST['rca_vehicle_year'] );
		$vehicle_vin = sanitize_text_field( $_POST['rca_vehicle_vin'] );
		$vehicle_color = sanitize_text_field( $_POST['rca_vehicle_color'] );
		$vehicle_plate = sanitize_text_field( $_POST['rca_vehicle_plate'] );
		$vehicle_weekly_rate = sanitize_text_field( $_POST['rca_vehicle_weekly_rate'] );
		$vehicle_title = sanitize_text_field( $_POST['rca_vehicle_title'] );
		
		// Get vehicle image from backend at time of submission
		$vehicle_image_id = 0;
		$vehicle_image_url = '';
		if ( $vehicle_id ) {
			$vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
			if ( $vehicle_image_id ) {
				$vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
			}
		}

		// Rental Details
		$start_date = sanitize_text_field( $_POST['rca_start_date'] );
		$end_date   = sanitize_text_field( $_POST['rca_end_date'] );
		$base_fee_weekly = sanitize_text_field( $_POST['rca_base_fee_weekly'] );
		
		// Validate start date
		if ( empty( $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			wp_die( 'Please enter a valid start date.' );
		}
		// Check if start date is not before today
		if ( strtotime( $start_date ) < strtotime( 'today' ) ) {
			wp_die( 'Start date cannot be before today.' );
		}
		
		// Validate end date
		if ( empty( $end_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_die( 'Please enter a valid end date.' );
		}
		// Check if end date is not before today
		if ( strtotime( $end_date ) < strtotime( 'today' ) ) {
			wp_die( 'End date cannot be before today.' );
		}
		// Check if end date is not before start date
		if ( strtotime( $end_date ) < strtotime( $start_date ) ) {
			wp_die( 'End date cannot be before start date.' );
		}

		// Insurance
		$insurance_option = sanitize_text_field( $_POST['rca_insurance_option'] );
		$marketing_optin = isset( $_POST['rca_marketing_optin'] ) ? 'yes' : 'no';

		// Verify all required initials are checked
		$required_initials = array(
			'rca_rental_term_initial',
			'rca_mileage_initial',
			'rca_rental_fees_initial',
			'rca_keys_initial',
			'rca_damage_responsibility_initial',
			'rca_insurance_initial',
			'rca_insurance_claims_initial',
			'rca_indemnification_initial',
			'rca_risk_acknowledgment_initial',
			'rca_release_waiver_initial',
			'rca_violation_charges_initial',
			'rca_vehicle_condition_initial',
			'rca_checkin_checkout_initial',
			'rca_free_will_initial',
			'rca_discriminatory_initial',
			'rca_ecpa_initial',
			'rca_optin_optout_initial',
			'rca_arbitration_initial',
			'rca_breach_contract_initial',
			'rca_your_property_initial',
			'rca_returning_vehicle_initial',
			'rca_out_of_state_initial',
		);

		foreach ( $required_initials as $initial_field ) {
			if ( ! isset( $_POST[ $initial_field ] ) ) {
				wp_die( 'You must acknowledge all sections of the agreement. Please review and check all required fields.' );
			}
		}

		// Get vehicle title for the booking title
		$vehicle_title = get_the_title( $vehicle_id );
		if ( empty( $vehicle_title ) ) {
			$vehicle_title = 'Unknown Vehicle';
		}
		
		// Create Booking Post with title format: RENTER-XXXX - VEHICLE NAME
		$post_title = $renter_id . ' - ' . $vehicle_title;
		
		$booking_id = wp_insert_post( array(
			'post_type'   => 'rental_booking',
			'post_title'  => $post_title,
			'post_status' => 'publish', 
		) );

		if ( $booking_id ) {
			// Renter Information
			update_post_meta( $booking_id, '_rca_vehicle_id', $vehicle_id );
			update_post_meta( $booking_id, '_rca_customer_name', $fullname );
			update_post_meta( $booking_id, '_rca_customer_email', $email );
			update_post_meta( $booking_id, '_rca_customer_phone', $phone );
			update_post_meta( $booking_id, '_rca_customer_license', $license );
			update_post_meta( $booking_id, '_rca_customer_address', $address );
			update_post_meta( $booking_id, '_rca_renter_id', $renter_id );
			update_post_meta( $booking_id, '_rca_agreement_date', $agreement_date );
			update_post_meta( $booking_id, '_rca_signature', $signature );

			// Vehicle Information Snapshot (stored at time of booking)
			update_post_meta( $booking_id, '_rca_vehicle_make', $vehicle_make );
			update_post_meta( $booking_id, '_rca_vehicle_model', $vehicle_model );
			update_post_meta( $booking_id, '_rca_vehicle_year', $vehicle_year );
			update_post_meta( $booking_id, '_rca_vehicle_vin', $vehicle_vin );
			update_post_meta( $booking_id, '_rca_vehicle_color', $vehicle_color );
			update_post_meta( $booking_id, '_rca_vehicle_plate', $vehicle_plate );
			update_post_meta( $booking_id, '_rca_vehicle_weekly_rate_snapshot', $vehicle_weekly_rate );
			update_post_meta( $booking_id, '_rca_vehicle_title_snapshot', $vehicle_title );

			// Rental Details
			update_post_meta( $booking_id, '_rca_start_date', $start_date );
			update_post_meta( $booking_id, '_rca_end_date', $end_date );
			update_post_meta( $booking_id, '_rca_base_fee_weekly', $base_fee_weekly );

			// Insurance
			update_post_meta( $booking_id, '_rca_insurance_option', $insurance_option );
			update_post_meta( $booking_id, '_rca_marketing_optin', $marketing_optin );

			// Store all initials as confirmation
			$initials_data = array();
			foreach ( $required_initials as $initial_field ) {
				$initials_data[ $initial_field ] = isset( $_POST[ $initial_field ] ) ? 'yes' : 'no';
			}
			update_post_meta( $booking_id, '_rca_all_initials', $initials_data );

			// Legacy fields for compatibility
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

	/**
	 * Generate unique Renter ID in format RENTER-XXXX
	 */
	private function generate_renter_id() {
		// Get the highest existing renter ID
		$args = array(
			'post_type' => 'rental_booking',
			'posts_per_page' => 1,
			'meta_key' => '_rca_renter_id',
			'orderby' => 'meta_value',
			'order' => 'DESC',
		);
		
		$query = new WP_Query( $args );
		$last_id = '0000';
		
		if ( $query->have_posts() ) {
			$query->the_post();
			$last_renter_id = get_post_meta( get_the_ID(), '_rca_renter_id', true );
			if ( $last_renter_id && preg_match( '/RENTER-(\d+)/', $last_renter_id, $matches ) ) {
				$last_id = $matches[1];
			}
			wp_reset_postdata();
		}
		
		// Increment and format
		$next_number = intval( $last_id ) + 1;
		return 'RENTER-' . str_pad( $next_number, 4, '0', STR_PAD_LEFT );
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

