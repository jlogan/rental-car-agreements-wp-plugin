<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Frontend {

	public function __construct() {
		add_shortcode( 'rental_car_inventory', array( $this, 'render_inventory' ) );
		add_shortcode( 'rental_car_booking', array( $this, 'render_booking_form' ) );
		add_shortcode( 'rental_car_completion', array( $this, 'render_completion_form_shortcode' ) );
		
		// Handle form submission via Init
		add_action( 'init', array( $this, 'process_booking_submission' ) );
		
		// Ensure completion page exists
		add_action( 'init', array( $this, 'ensure_completion_page_exists' ) );

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
        
        // Handle completion page (legacy - now handled by shortcode on page)
        // Disabled to allow page with shortcode to render properly
        // add_action( 'template_redirect', array( $this, 'handle_completion_page' ) );
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
	 * [rental_car_inventory count="-1" per_row="3"]
	 */
	public function render_inventory( $atts ) {
		// Parse shortcode attributes
		$atts = shortcode_atts( array(
			'count' => '',
			'per_row' => '',
		), $atts );
		
		// Get settings from shortcode attributes first, then fallback to ACF options
		// Only use shortcode attribute if it's provided (not empty string)
		if ( $atts['count'] !== '' && $atts['count'] !== null ) {
			$items_count = intval( $atts['count'] );
		} else {
			$items_count = get_field( 'vehicles_count', 'option' );
		}
		
		if ( $atts['per_row'] !== '' && $atts['per_row'] !== null ) {
			$items_per_row = intval( $atts['per_row'] );
		} else {
			$items_per_row = get_field( 'vehicles_per_row', 'option' );
		}
		
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
        $is_modal = isset($_POST['is_modal']) ? intval($_POST['is_modal']) : 1; // Default to modal (simple form)
        
        if($vehicle_id) {
            $vehicle = get_post( $vehicle_id );
            if($vehicle && $vehicle->post_type === 'rental_vehicle') {
                ob_start();
                // Use simple form for modal (lead capture)
                if($is_modal) {
                    include RCA_PLUGIN_DIR . 'templates/booking-form-simple.php';
                } else {
                    // Full form for non-modal use
                    include RCA_PLUGIN_DIR . 'templates/booking-form.php';
                }
                echo ob_get_clean();
            } else {
                echo '<div class="rca-alert rca-alert-error">Vehicle not found.</div>';
            }
        } else {
            echo '<div class="rca-alert rca-alert-error">Vehicle ID is required.</div>';
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
        // Check if this is a completion form submission (updating existing booking)
        $is_completion_form = isset( $_POST['rca_complete_booking'] ) && $_POST['rca_complete_booking'] == '1';
        
        if ( $is_completion_form ) {
            $booking_id = isset( $_POST['rca_booking_id'] ) ? intval( $_POST['rca_booking_id'] ) : 0;
            // For AJAX requests, token should be in POST data (not GET)
            $token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
            if ( ! $token && isset( $_GET['token'] ) ) {
                $token = sanitize_text_field( $_GET['token'] );
            }
            
            if ( $booking_id && $token ) {
                $this->process_completion_form_submission_ajax( $booking_id, $token );
                return;
            } else {
                // Missing booking_id or token
                while ( ob_get_level() ) {
                    ob_end_clean();
                }
                wp_send_json_error( array( 
                    'message' => 'Missing booking ID or token. Please refresh the page and try again.',
                    'debug' => array(
                        'booking_id' => $booking_id,
                        'token_provided' => !empty($token),
                        'post_data' => array_keys($_POST)
                    )
                ) );
                wp_die();
            }
        }
        
        // Check if this is a simplified form submission (lead capture)
        $is_simple_form = isset( $_POST['rca_is_simple_form'] ) && $_POST['rca_is_simple_form'] == '1';
        
        if ( $is_simple_form ) {
            $this->process_simple_form_submission();
            return;
        }
        
        // Validate required fields first (full form)
        // Note: rca_base_fee_weekly is fetched from backend, not from POST
        $required_fields = array(
            'vehicle_id',
            'rca_first_name',
            'rca_last_name',
            'rca_email',
            'rca_phone',
            'rca_license',
            'rca_street_address',
            'rca_city',
            'rca_state',
            'rca_zip_code',
            'rca_agreement_date',
            'rca_signature',
            'rca_start_date',
            'rca_end_date',
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
        
        // Validate and sanitize first name
        $first_name = sanitize_text_field( $_POST['rca_first_name'] );
        if ( empty( $first_name ) || strlen( $first_name ) < 1 || strlen( $first_name ) > 50 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid first name.' ) );
            wp_die();
        }
        if ( ! preg_match( '/^[A-Za-z\s\'-]+$/', $first_name ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'First name can only contain letters, spaces, hyphens, and apostrophes.' ) );
            wp_die();
        }
        
        // Validate and sanitize last name
        $last_name = sanitize_text_field( $_POST['rca_last_name'] );
        if ( empty( $last_name ) || strlen( $last_name ) < 1 || strlen( $last_name ) > 50 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid last name.' ) );
            wp_die();
        }
        if ( ! preg_match( '/^[A-Za-z\s\'-]+$/', $last_name ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.' ) );
            wp_die();
        }
        
        // Combine first and last name for backward compatibility
        $fullname = trim( $first_name . ' ' . $last_name );
        
        // Sanitize email (browser handles validation)
        $email = sanitize_email( $_POST['rca_email'] );
        
        // Sanitize phone (no validation, browser handles it)
        $phone = sanitize_text_field( $_POST['rca_phone'] );
        
        // Sanitize license (no validation)
        $license = sanitize_text_field( $_POST['rca_license'] );
        
        // Sanitize address fields
        $street_address = sanitize_text_field( $_POST['rca_street_address'] );
        $apt_unit = isset( $_POST['rca_apt_unit'] ) ? sanitize_text_field( $_POST['rca_apt_unit'] ) : '';
        $city = sanitize_text_field( $_POST['rca_city'] );
        $state = sanitize_text_field( $_POST['rca_state'] );
        $zip_code = sanitize_text_field( $_POST['rca_zip_code'] );
        
        // Combine address fields for backward compatibility
        $address = $street_address;
        if ( $apt_unit ) {
            $address .= "\n" . $apt_unit;
        }
        $address .= "\n" . $city . ', ' . $state . ' ' . $zip_code;
        
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
        
        // Validate minimum booking days
        $min_booking_days = get_post_meta( $vehicle_id, '_rca_min_booking_days', true );
        if ( empty( $min_booking_days ) ) {
            $min_booking_days = 7; // Default to 7 days (1 week)
        }
        $min_booking_days = intval( $min_booking_days );
        
        $start_timestamp = strtotime( $start_date );
        $end_timestamp = strtotime( $end_date );
        $days_diff = ( $end_timestamp - $start_timestamp ) / ( 60 * 60 * 24 );
        
        if ( $days_diff < $min_booking_days ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            $min_date = date( 'Y-m-d', strtotime( $start_date . ' +' . $min_booking_days . ' days' ) );
            wp_send_json_error( array( 
                'message' => sprintf( 
                    'The minimum booking period for this vehicle is %d days. The end date must be at least %s.', 
                    $min_booking_days,
                    date( 'F j, Y', strtotime( $min_date ) )
                ) 
            ) );
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

        // Vehicle Information Snapshot - Fetch from backend at time of submission
        $vehicle_make = '';
        $vehicle_model = '';
        $vehicle_year = '';
        $vehicle_vin = '';
        $vehicle_color = '';
        $vehicle_plate = '';
        $vehicle_weekly_rate = '';
        $vehicle_title = '';
        $vehicle_image_id = 0;
        $vehicle_image_url = '';
        
        if ( $vehicle_id ) {
            $vehicle_post = get_post( $vehicle_id );
            if ( $vehicle_post ) {
                $vehicle_make = get_post_meta( $vehicle_id, '_rca_make', true );
                $vehicle_model = get_post_meta( $vehicle_id, '_rca_model', true );
                $vehicle_year = get_post_meta( $vehicle_id, '_rca_year', true );
                $vehicle_vin = get_post_meta( $vehicle_id, '_rca_vin', true );
                $vehicle_color = get_post_meta( $vehicle_id, '_rca_color', true );
                $vehicle_plate = get_post_meta( $vehicle_id, '_rca_plate', true );
                $vehicle_weekly_rate = get_post_meta( $vehicle_id, '_rca_weekly_rate', true );
                $vehicle_title = get_the_title( $vehicle_id );
                
                // Get vehicle image from backend at time of submission
                $vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
                if ( $vehicle_image_id ) {
                    $vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
                }
            }
        }

        // Rental Details
        $start_date = sanitize_text_field( $_POST['rca_start_date'] );
        $end_date   = sanitize_text_field( $_POST['rca_end_date'] );
        // Base fee fetched from backend above
        $base_fee_weekly = $vehicle_weekly_rate;

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

        // Vehicle title already fetched from backend above
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
            
            // Store individual fields for new structure
            update_post_meta( $booking_id, '_rca_first_name', $first_name );
            update_post_meta( $booking_id, '_rca_last_name', $last_name );
            update_post_meta( $booking_id, '_rca_street_address', $street_address );
            update_post_meta( $booking_id, '_rca_apt_unit', $apt_unit );
            update_post_meta( $booking_id, '_rca_city', $city );
            update_post_meta( $booking_id, '_rca_state', $state );
            update_post_meta( $booking_id, '_rca_zip_code', $zip_code );

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

    /**
     * Process simplified form submission (lead capture only)
     */
    private function process_simple_form_submission() {
        // Validate required fields for simplified form
        $required_fields = array(
            'vehicle_id',
            'rca_first_name',
            'rca_last_name',
            'rca_email',
            'rca_phone',
            'rca_license',
            'rca_street_address',
            'rca_city',
            'rca_state',
            'rca_zip_code',
            'rca_start_date',
            'rca_end_date',
        );

        foreach ( $required_fields as $field ) {
            if ( ! isset( $_POST[ $field ] ) || empty( $_POST[ $field ] ) ) {
                while ( ob_get_level() ) {
                    ob_end_clean();
                }
                wp_send_json_error( array( 'message' => 'Please fill in all required fields. Missing: ' . $field ) );
                wp_die();
            }
        }

        // Generate Renter ID
        $renter_id = $this->generate_renter_id();

        // Sanitize and Validate Inputs
        $vehicle_id = intval( $_POST['vehicle_id'] );
        
        // Validate and sanitize first name
        $first_name = sanitize_text_field( $_POST['rca_first_name'] );
        if ( empty( $first_name ) || strlen( $first_name ) < 1 || strlen( $first_name ) > 50 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid first name.' ) );
            wp_die();
        }
        if ( ! preg_match( '/^[A-Za-z\s\'-]+$/', $first_name ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'First name can only contain letters, spaces, hyphens, and apostrophes.' ) );
            wp_die();
        }
        
        // Validate and sanitize last name
        $last_name = sanitize_text_field( $_POST['rca_last_name'] );
        if ( empty( $last_name ) || strlen( $last_name ) < 1 || strlen( $last_name ) > 50 ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid last name.' ) );
            wp_die();
        }
        if ( ! preg_match( '/^[A-Za-z\s\'-]+$/', $last_name ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.' ) );
            wp_die();
        }
        
        // Combine first and last name for backward compatibility
        $fullname = trim( $first_name . ' ' . $last_name );
        
        // Sanitize email
        $email = sanitize_email( $_POST['rca_email'] );
        
        // Sanitize phone
        $phone = sanitize_text_field( $_POST['rca_phone'] );
        
        // Sanitize license
        $license = sanitize_text_field( $_POST['rca_license'] );
        
        // Sanitize address fields
        $street_address = sanitize_text_field( $_POST['rca_street_address'] );
        $apt_unit = isset( $_POST['rca_apt_unit'] ) ? sanitize_text_field( $_POST['rca_apt_unit'] ) : '';
        $city = sanitize_text_field( $_POST['rca_city'] );
        $state = sanitize_text_field( $_POST['rca_state'] );
        $zip_code = sanitize_text_field( $_POST['rca_zip_code'] );
        
        // Combine address fields for backward compatibility
        $address = $street_address;
        if ( $apt_unit ) {
            $address .= "\n" . $apt_unit;
        }
        $address .= "\n" . $city . ', ' . $state . ' ' . $zip_code;
        
        // Keep driver_state for backward compatibility (same as state)
        $driver_state = $state;
        
        // Validate start date
        $start_date = sanitize_text_field( $_POST['rca_start_date'] );
        if ( empty( $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'Please enter a valid start date.' ) );
            wp_die();
        }
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
        if ( strtotime( $end_date ) < strtotime( 'today' ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'End date cannot be before today.' ) );
            wp_die();
        }
        if ( strtotime( $end_date ) < strtotime( $start_date ) ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_error( array( 'message' => 'End date cannot be before start date.' ) );
            wp_die();
        }
        
        // Validate minimum booking days
        $min_booking_days = get_post_meta( $vehicle_id, '_rca_min_booking_days', true );
        if ( empty( $min_booking_days ) ) {
            $min_booking_days = 7; // Default to 7 days (1 week)
        }
        $min_booking_days = intval( $min_booking_days );
        
        $start_timestamp = strtotime( $start_date );
        $end_timestamp = strtotime( $end_date );
        $days_diff = ( $end_timestamp - $start_timestamp ) / ( 60 * 60 * 24 );
        
        if ( $days_diff < $min_booking_days ) {
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            $min_date = date( 'Y-m-d', strtotime( $start_date . ' +' . $min_booking_days . ' days' ) );
            wp_send_json_error( array( 
                'message' => sprintf( 
                    'The minimum booking period for this vehicle is %d days. The end date must be at least %s.', 
                    $min_booking_days,
                    date( 'F j, Y', strtotime( $min_date ) )
                ) 
            ) );
            wp_die();
        }

        // Vehicle Information Snapshot - Fetch from backend at time of submission
        $vehicle_make = '';
        $vehicle_model = '';
        $vehicle_year = '';
        $vehicle_vin = '';
        $vehicle_color = '';
        $vehicle_plate = '';
        $vehicle_weekly_rate = '';
        $vehicle_title = '';
        $vehicle_image_id = 0;
        $vehicle_image_url = '';
        
        if ( $vehicle_id ) {
            $vehicle_post = get_post( $vehicle_id );
            if ( $vehicle_post ) {
                $vehicle_make = get_post_meta( $vehicle_id, '_rca_make', true );
                $vehicle_model = get_post_meta( $vehicle_id, '_rca_model', true );
                $vehicle_year = get_post_meta( $vehicle_id, '_rca_year', true );
                $vehicle_vin = get_post_meta( $vehicle_id, '_rca_vin', true );
                $vehicle_color = get_post_meta( $vehicle_id, '_rca_color', true );
                $vehicle_plate = get_post_meta( $vehicle_id, '_rca_plate', true );
                $vehicle_weekly_rate = get_post_meta( $vehicle_id, '_rca_weekly_rate', true );
                $vehicle_title = get_the_title( $vehicle_id );
                
                // Get vehicle image from backend at time of submission
                $vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
                if ( $vehicle_image_id ) {
                    $vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
                }
            }
        }

        // Rental Details
        // Base fee fetched from backend above
        $base_fee_weekly = $vehicle_weekly_rate;

        // Vehicle title already fetched from backend above
        if ( empty( $vehicle_title ) ) {
            $vehicle_title = 'Unknown Vehicle';
        }
        
        // Create Booking Post - mark as lead
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
            update_post_meta( $booking_id, '_rca_driver_state', $driver_state );
            update_post_meta( $booking_id, '_rca_renter_id', $renter_id );
            
            // Store individual fields for new structure
            update_post_meta( $booking_id, '_rca_first_name', $first_name );
            update_post_meta( $booking_id, '_rca_last_name', $last_name );
            update_post_meta( $booking_id, '_rca_street_address', $street_address );
            update_post_meta( $booking_id, '_rca_apt_unit', $apt_unit );
            update_post_meta( $booking_id, '_rca_city', $city );
            update_post_meta( $booking_id, '_rca_state', $state );
            update_post_meta( $booking_id, '_rca_zip_code', $zip_code );

            // Vehicle Information Snapshot
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

            // Mark as pending (incomplete form - will be completed via link)
            update_post_meta( $booking_id, '_rca_booking_status', 'pending' );
            update_post_meta( $booking_id, '_rca_is_lead', 'yes' );

            // Automatically generate completion link
            if ( class_exists( 'RCA_CPT_Bookings' ) ) {
                RCA_CPT_Bookings::generate_completion_link( $booking_id );
            }

            // Send Notifications (simple email for lead capture)
            $this->send_emails( $booking_id, $email, $fullname, 'simple' );

            // Clean all output buffers before sending JSON
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            wp_send_json_success( array( 'message' => 'Booking request submitted successfully! We will contact you shortly.', 'booking_id' => $booking_id ) );
            wp_die();
        } else {
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
		
		// Handle completion form submission
		if ( isset( $_POST['rca_complete_booking'] ) && $_POST['rca_complete_booking'] == '1' ) {
			$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
			if ( ! $token && isset( $_POST['token'] ) ) {
				$token = sanitize_text_field( $_POST['token'] );
			}
			
			if ( $token ) {
				$booking_id = $this->find_booking_by_token( $token );
				if ( $booking_id ) {
					$this->process_completion_form_submission( $booking_id, $token );
					return;
				}
			}
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

		// Vehicle Information Snapshot - Fetch from backend at time of submission
		$vehicle_make = '';
		$vehicle_model = '';
		$vehicle_year = '';
		$vehicle_vin = '';
		$vehicle_color = '';
		$vehicle_plate = '';
		$vehicle_weekly_rate = '';
		$vehicle_title = '';
		$vehicle_image_id = 0;
		$vehicle_image_url = '';
		
		if ( $vehicle_id ) {
			$vehicle_post = get_post( $vehicle_id );
			if ( $vehicle_post ) {
				$vehicle_make = get_post_meta( $vehicle_id, '_rca_make', true );
				$vehicle_model = get_post_meta( $vehicle_id, '_rca_model', true );
				$vehicle_year = get_post_meta( $vehicle_id, '_rca_year', true );
				$vehicle_vin = get_post_meta( $vehicle_id, '_rca_vin', true );
				$vehicle_color = get_post_meta( $vehicle_id, '_rca_color', true );
				$vehicle_plate = get_post_meta( $vehicle_id, '_rca_plate', true );
				$vehicle_weekly_rate = get_post_meta( $vehicle_id, '_rca_weekly_rate', true );
				$vehicle_title = get_the_title( $vehicle_id );
				
				// Get vehicle image from backend at time of submission
				$vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
				if ( $vehicle_image_id ) {
					$vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
				}
			}
		}

		// Rental Details
		$start_date = sanitize_text_field( $_POST['rca_start_date'] );
		$end_date   = sanitize_text_field( $_POST['rca_end_date'] );
		// Base fee fetched from backend above
		$base_fee_weekly = $vehicle_weekly_rate;
		
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

		// Vehicle title already fetched from backend above
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
			
			// Store individual fields for new structure
			update_post_meta( $booking_id, '_rca_first_name', $first_name );
			update_post_meta( $booking_id, '_rca_last_name', $last_name );
			update_post_meta( $booking_id, '_rca_street_address', $street_address );
			update_post_meta( $booking_id, '_rca_apt_unit', $apt_unit );
			update_post_meta( $booking_id, '_rca_city', $city );
			update_post_meta( $booking_id, '_rca_state', $state );
			update_post_meta( $booking_id, '_rca_zip_code', $zip_code );

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

	/**
	 * Send booking email (unified function for both simple and completion forms)
	 * 
	 * @param int $booking_id Booking post ID
	 * @param string $customer_email Customer email address
	 * @param string $name Customer name
	 * @param string $type Email type: 'simple' for lead capture, 'completion' for completed booking
	 */
	private function send_emails( $booking_id, $customer_email, $name, $type = 'completion' ) {
		$options = get_option( 'rca_settings' );
		
		// Check if automated emails are enabled
		$emails_enabled = isset( $options['enable_emails'] ) && $options['enable_emails'] === '1';
		if ( ! $emails_enabled ) {
			return;
		}
		
		$business_email = isset( $options['business_email'] ) && ! empty( $options['business_email'] ) ? $options['business_email'] : '';
		
		// Don't send email if business email is not set
		if ( empty( $business_email ) ) {
			return;
		}

		$is_simple = ( $type === 'simple' );
		
		// Get common booking data
		$phone = get_post_meta( $booking_id, '_rca_customer_phone', true );
		$license = get_post_meta( $booking_id, '_rca_customer_license', true );
		$address = get_post_meta( $booking_id, '_rca_customer_address', true );
		$start_date = get_post_meta( $booking_id, '_rca_start_date', true );
		$end_date = get_post_meta( $booking_id, '_rca_end_date', true );
		
		// Format dates
		$start_date_formatted = $start_date ? date( 'F j, Y', strtotime( $start_date ) ) : 'N/A';
		$end_date_formatted = $end_date ? date( 'F j, Y', strtotime( $end_date ) ) : 'N/A';

		// Get vehicle info
		$vehicle_id = get_post_meta( $booking_id, '_rca_vehicle_id', true );
		$vehicle_title = '';
		if ( $vehicle_id ) {
			$vehicle_title = get_the_title( $vehicle_id );
		}

		// For simple form, get basic info only
		if ( $is_simple ) {
			$driver_state = get_post_meta( $booking_id, '_rca_driver_state', true );
			$completion_link = get_post_meta( $booking_id, '_rca_completion_link', true );
			
			// Create simple HTML email template
			$html_message = $this->get_simple_booking_email_template( array(
				'booking_id' => $booking_id,
				'name' => $name,
				'email' => $customer_email,
				'phone' => $phone,
				'license' => $license,
				'address' => $address,
				'driver_state' => $driver_state,
				'vehicle_title' => $vehicle_title,
				'start_date' => $start_date_formatted,
				'end_date' => $end_date_formatted,
				'completion_link' => $completion_link,
			) );

			$subject = 'New Booking Lead: ' . $name . ' - ' . $vehicle_title;
			$attachments = array(); // No PDF for simple form
		} else {
			// For completion form, get all detailed info
			$renter_id = get_post_meta( $booking_id, '_rca_renter_id', true );
			$base_fee = get_post_meta( $booking_id, '_rca_base_fee_weekly', true );
			$vehicle_make = get_post_meta( $booking_id, '_rca_vehicle_make', true );
			$vehicle_model = get_post_meta( $booking_id, '_rca_vehicle_model', true );
			$vehicle_year = get_post_meta( $booking_id, '_rca_vehicle_year', true );
			$vehicle_vin = get_post_meta( $booking_id, '_rca_vehicle_vin', true );
			$vehicle_color = get_post_meta( $booking_id, '_rca_vehicle_color', true );
			$vehicle_plate = get_post_meta( $booking_id, '_rca_vehicle_plate', true );
			$agreement_date = get_post_meta( $booking_id, '_rca_agreement_date', true );
			$agreement_date_formatted = $agreement_date ? date( 'F j, Y', strtotime( $agreement_date ) ) : 'N/A';

			// Generate PDF attachment
			$pdf_path = $this->generate_pdf_for_email( $booking_id );
			
			// Create detailed HTML email template
			$html_message = $this->get_booking_email_template( array(
				'booking_id' => $booking_id,
				'renter_id' => $renter_id,
				'name' => $name,
				'email' => $customer_email,
				'phone' => $phone,
				'license' => $license,
				'address' => $address,
				'vehicle_year' => $vehicle_year,
				'vehicle_make' => $vehicle_make,
				'vehicle_model' => $vehicle_model,
				'vehicle_vin' => $vehicle_vin,
				'vehicle_color' => $vehicle_color,
				'vehicle_plate' => $vehicle_plate,
				'start_date' => $start_date_formatted,
				'end_date' => $end_date_formatted,
				'base_fee' => $base_fee,
				'agreement_date' => $agreement_date_formatted,
			) );

			$vehicle_display = trim( $vehicle_year . ' ' . $vehicle_make . ' ' . $vehicle_model );
			$subject = 'Booking Form Completed: ' . $renter_id . ' - ' . $vehicle_display;
			
			$attachments = array();
			if ( $pdf_path && file_exists( $pdf_path ) && is_readable( $pdf_path ) ) {
				$attachments[] = $pdf_path;
			}
		}
		
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		// Send email
		wp_mail( $business_email, $subject, $html_message, $headers, $attachments );

		// Clean up temporary PDF file after sending (only for completion form)
		if ( ! $is_simple && isset( $pdf_path ) && $pdf_path && file_exists( $pdf_path ) ) {
			// Use a small delay to ensure email processing is complete
			sleep( 1 );
			@unlink( $pdf_path );
		}
	}

	/**
	 * Generate PDF for email attachment (returns file path)
	 */
	private function generate_pdf_for_email( $booking_id ) {
		// Load TCPDF if available
		$tcpdf_path = RCA_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';
		if ( ! file_exists( $tcpdf_path ) ) {
			return false;
		}

		// Clear any existing output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		
		// Suppress errors but keep them logged
		$old_error_reporting = error_reporting( E_ERROR | E_WARNING | E_PARSE );
		$old_display_errors = ini_get( 'display_errors' );
		ini_set( 'display_errors', 0 );

		// Load autoconfig first
		$autoconfig_path = RCA_PLUGIN_DIR . 'lib/tcpdf/tcpdf_autoconfig.php';
		if ( file_exists( $autoconfig_path ) ) {
			require_once $autoconfig_path;
		}
		require_once $tcpdf_path;

		// Gather Data
		$meta = array(
			'name'       => get_post_meta( $booking_id, '_rca_customer_name', true ),
			'address'    => get_post_meta( $booking_id, '_rca_customer_address', true ),
			'phone'      => get_post_meta( $booking_id, '_rca_customer_phone', true ),
			'email'      => get_post_meta( $booking_id, '_rca_customer_email', true ),
			'license'    => get_post_meta( $booking_id, '_rca_customer_license', true ),
			'start'      => get_post_meta( $booking_id, '_rca_start_date', true ),
			'end'        => get_post_meta( $booking_id, '_rca_end_date', true ),
			'insurance'  => get_post_meta( $booking_id, '_rca_insurance_option', true ),
			'renter_id'  => get_post_meta( $booking_id, '_rca_renter_id', true ),
			'agreement_date' => get_post_meta( $booking_id, '_rca_agreement_date', true ),
			'signature'  => get_post_meta( $booking_id, '_rca_signature', true ),
		);

		$v_meta = array(
			'make'   => get_post_meta( $booking_id, '_rca_vehicle_make', true ),
			'model'  => get_post_meta( $booking_id, '_rca_vehicle_model', true ),
			'year'   => get_post_meta( $booking_id, '_rca_vehicle_year', true ),
			'color'  => get_post_meta( $booking_id, '_rca_vehicle_color', true ),
			'vin'    => get_post_meta( $booking_id, '_rca_vehicle_vin', true ),
			'plate'  => get_post_meta( $booking_id, '_rca_vehicle_plate', true ),
			'rate_w' => get_post_meta( $booking_id, '_rca_vehicle_weekly_rate_snapshot', true ),
			'title'  => get_post_meta( $booking_id, '_rca_vehicle_title_snapshot', true ),
		);

		$all_initials = get_post_meta( $booking_id, '_rca_all_initials', true );
		if ( ! is_array( $all_initials ) ) {
			$all_initials = array();
		}

		$settings = get_option( 'rca_settings' );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Generate HTML content
		ob_start();
		$print_mode = false;
		include RCA_PLUGIN_DIR . 'templates/agreement-template.php';
		$html_content = ob_get_clean();

		// Extract body content and styles
		preg_match( '/<style>(.*?)<\/style>/s', $html_content, $style_matches );
		$styles = ! empty( $style_matches[1] ) ? $style_matches[1] : '';
		
		preg_match( '/<body[^>]*>(.*?)<\/body>/s', $html_content, $body_matches );
		$body = ! empty( $body_matches[1] ) ? $body_matches[1] : '';

		$full_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $styles . '</style></head><body>' . $body . '</body></html>';

		// Create TCPDF instance
		$pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
		
		// Set document information
		$pdf->SetCreator( 'PNS Global Resources L.L.C' );
		$pdf->SetAuthor( 'PNS Global Resources L.L.C' );
		$pdf->SetTitle( 'Car Rental Agreement - ' . $meta['renter_id'] );
		$pdf->SetSubject( 'Car Rental Agreement' );
		
		// Remove default header/footer
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		
		// Set margins (reduced bottom margin to minimize spacing)
		$pdf->SetMargins( 15, 15, 15 );
		$pdf->SetAutoPageBreak( true, 5 );
		
		// Set font
		$pdf->SetFont( 'dejavusans', '', 11 );
		
		// Reduce cell padding and line height to minimize spacing in TCPDF
		if ( method_exists( $pdf, 'setCellHeightRatio' ) ) {
			$pdf->setCellHeightRatio( 1.0 );
		}
		if ( method_exists( $pdf, 'setCellPadding' ) ) {
			$pdf->setCellPadding( 0 );
		}
		
		// Add a page
		$pdf->AddPage();
		
		// Update HTML for PDF
		$full_html = str_replace( "font-family: 'Times New Roman', Times, serif;", "font-family: 'dejavusans', 'Times New Roman', Times, serif;", $full_html );
		$full_html = str_replace( '<span class="checkmark">&#10003;</span>', '<span style="font-family:dejavusans;font-size:12pt;font-weight:bold;color:#000;">✓</span>', $full_html );
		
		// Remove all inline margin/padding styles from sections and other elements for PDF
		$full_html = preg_replace( '/style="[^"]*margin[^"]*"/', '', $full_html );
		$full_html = preg_replace( '/style="[^"]*padding[^"]*"/', '', $full_html );
		// Clean up empty style attributes
		$full_html = str_replace( 'style=""', '', $full_html );
		// Also remove margin-top specifically from section divs
		$full_html = preg_replace( '/<div class="section"[^>]*>/', '<div class="section">', $full_html );
		
		// Add CSS to reduce default paragraph and div spacing in PDF (TCPDF adds extra spacing)
		// Use very aggressive overrides to minimize gaps between sections
		$pdf_css = '<style>
			* { margin: 0 !important; padding: 0 !important; }
			.section { margin-bottom: 0px !important; margin-top: 0px !important; padding: 0 !important; }
			.section p { margin: 0px !important; line-height: 1.0 !important; padding: 0 !important; }
			.section-title { margin-top: 0px !important; margin-bottom: 0px !important; padding: 0 !important; }
			.field-line { margin: 0px !important; padding: 0 !important; }
			.initial-line { margin: 0px !important; padding: 0 !important; }
			.signature-section { margin-top: 1px !important; padding-top: 0px !important; }
			.renter-info-section { margin-top: 1px !important; padding-top: 0px !important; }
			ul { margin: 0px !important; padding: 0 !important; }
			li { margin: 0px !important; padding: 0 !important; }
			.insurance-option { margin: 0px !important; padding: 0 !important; }
			.insurance-option-title { margin-bottom: 0px !important; padding: 0 !important; }
			p { margin: 0px !important; padding: 0 !important; line-height: 1.0 !important; }
			div { margin: 0 !important; padding: 0 !important; }
			body { margin: 0 !important; padding: 0 !important; }
			h1, h2, h3, h4, h5, h6 { margin: 0 !important; padding: 0 !important; }
		</style>';
		
		// Insert PDF-specific CSS before closing </head>
		$full_html = str_replace( '</head>', $pdf_css . '</head>', $full_html );
		
		// Write HTML to PDF
		$pdf->writeHTML( $full_html, true, false, true, false, '' );
		
		// Generate filename and save to temp directory
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/rca-temp';
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}
		
		$filename = 'Car-Rental-Agreement-' . sanitize_file_name( $meta['renter_id'] ) . '.pdf';
		$file_path = $temp_dir . '/' . $filename;
		
		// Save PDF to file (use 'F' for file output)
		@$pdf->Output( $file_path, 'F' );
		
		// Restore error reporting
		error_reporting( $old_error_reporting );
		ini_set( 'display_errors', $old_display_errors );
		
		// Verify file was created and is readable
		if ( file_exists( $file_path ) && is_readable( $file_path ) && filesize( $file_path ) > 0 ) {
			return $file_path;
		}
		
		return false;
	}

	/**
	 * Get HTML email template for booking notification
	 */
	private function get_booking_email_template( $data ) {
		$html = '
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
				.email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
				.email-header { background-color: #1e293b; color: #ffffff; padding: 30px 20px; text-align: center; }
				.email-header h1 { margin: 0; font-size: 24px; font-weight: bold; }
				.email-body { padding: 30px 20px; }
				.section { margin-bottom: 25px; }
				.section-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #3b82f6; }
				.info-row { margin-bottom: 12px; }
				.info-label { font-weight: bold; color: #64748b; display: inline-block; min-width: 150px; }
				.info-value { color: #1e293b; }
				.highlight-box { background-color: #f1f5f9; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
				.footer { background-color: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 12px; }
				.button { display: inline-block; background-color: #3b82f6; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
			</style>
		</head>
		<body>
			<div class="email-container">
				<div class="email-header">
					<h1>Booking Form Completed</h1>
				</div>
				<div class="email-body">
					<div class="section">
						<div class="section-title">Booking Information</div>
						<div class="info-row">
							<span class="info-label">Booking ID:</span>
							<span class="info-value">#' . esc_html( $data['booking_id'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Renter ID:</span>
							<span class="info-value">' . esc_html( $data['renter_id'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Agreement Date:</span>
							<span class="info-value">' . esc_html( $data['agreement_date'] ) . '</span>
						</div>
					</div>

					<div class="section">
						<div class="section-title">Customer Information</div>
						<div class="info-row">
							<span class="info-label">Name:</span>
							<span class="info-value">' . esc_html( $data['name'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Email:</span>
							<span class="info-value">' . esc_html( $data['email'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Phone:</span>
							<span class="info-value">' . esc_html( $data['phone'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Driver\'s License:</span>
							<span class="info-value">' . esc_html( $data['license'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Address:</span>
							<span class="info-value">' . nl2br( esc_html( $data['address'] ) ) . '</span>
						</div>
					</div>

					<div class="section">
						<div class="section-title">Vehicle Information</div>
						<div class="info-row">
							<span class="info-label">Make and Model:</span>
							<span class="info-value">' . esc_html( $data['vehicle_make'] . ' ' . $data['vehicle_model'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Year:</span>
							<span class="info-value">' . esc_html( $data['vehicle_year'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">VIN:</span>
							<span class="info-value">' . esc_html( $data['vehicle_vin'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Color:</span>
							<span class="info-value">' . esc_html( $data['vehicle_color'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Plate Number:</span>
							<span class="info-value">' . esc_html( $data['vehicle_plate'] ) . '</span>
						</div>
					</div>

					<div class="section">
						<div class="section-title">Rental Details</div>
						<div class="info-row">
							<span class="info-label">Start Date:</span>
							<span class="info-value">' . esc_html( $data['start_date'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">End Date:</span>
							<span class="info-value">' . esc_html( $data['end_date'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Weekly Rate:</span>
							<span class="info-value">$' . esc_html( $data['base_fee'] ) . '</span>
						</div>
					</div>

					<div class="highlight-box">
						<strong>📎 PDF Attachment:</strong> The complete rental agreement PDF is attached to this email. You can download it by clicking on the attachment.
					</div>

					<div style="text-align: center; margin-top: 30px;">
						<a href="' . admin_url( 'post.php?post=' . $data['booking_id'] . '&action=edit' ) . '" class="button">View Booking in Admin</a>
					</div>
				</div>
				<div class="footer">
					<p>This is an automated notification from ' . get_bloginfo( 'name' ) . '</p>
					<p>Please do not reply to this email.</p>
				</div>
			</div>
		</body>
		</html>';

		return $html;
	}
	
	/**
	 * Get simple booking email template (for lead capture)
	 */
	private function get_simple_booking_email_template( $data ) {
		$html = '
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
				.email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
				.email-header { background-color: #1e293b; color: #ffffff; padding: 30px 20px; text-align: center; }
				.email-header h1 { margin: 0; font-size: 24px; font-weight: bold; }
				.email-body { padding: 30px 20px; }
				.section { margin-bottom: 25px; }
				.section-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #3b82f6; }
				.info-row { margin-bottom: 12px; }
				.info-label { font-weight: bold; color: #64748b; display: inline-block; min-width: 150px; }
				.info-value { color: #1e293b; }
				.highlight-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
				.footer { background-color: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 12px; }
				.button { display: inline-block; background-color: #3b82f6; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
			</style>
		</head>
		<body>
			<div class="email-container">
				<div class="email-header">
					<h1>New Booking Lead</h1>
				</div>
				<div class="email-body">
					<div class="highlight-box">
						<strong>📋 Lead Capture:</strong> This is a new booking lead. The customer has submitted basic information and will complete the full agreement via the completion link.
					</div>
					
					<div class="section">
						<div class="section-title">Customer Information</div>
						<div class="info-row">
							<span class="info-label">Name:</span>
							<span class="info-value">' . esc_html( $data['name'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Email:</span>
							<span class="info-value">' . esc_html( $data['email'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Phone:</span>
							<span class="info-value">' . esc_html( $data['phone'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Driver\'s License:</span>
							<span class="info-value">' . esc_html( $data['license'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Driver\'s State:</span>
							<span class="info-value">' . esc_html( $data['driver_state'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Address:</span>
							<span class="info-value">' . nl2br( esc_html( $data['address'] ) ) . '</span>
						</div>
					</div>

					<div class="section">
						<div class="section-title">Rental Details</div>
						<div class="info-row">
							<span class="info-label">Vehicle:</span>
							<span class="info-value">' . esc_html( $data['vehicle_title'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">Start Date:</span>
							<span class="info-value">' . esc_html( $data['start_date'] ) . '</span>
						</div>
						<div class="info-row">
							<span class="info-label">End Date:</span>
							<span class="info-value">' . esc_html( $data['end_date'] ) . '</span>
						</div>
					</div>

					<div style="text-align: center; margin-top: 30px;">
						<a href="' . admin_url( 'post.php?post=' . $data['booking_id'] . '&action=edit' ) . '" class="button">View Booking in Admin</a>
					</div>
				</div>
				<div class="footer">
					<p>This is an automated notification from ' . get_bloginfo( 'name' ) . '</p>
					<p>Please do not reply to this email.</p>
				</div>
			</div>
		</body>
		</html>';

		return $html;
	}
	
	/**
	 * Ensure completion page exists with shortcode
	 */
	public function ensure_completion_page_exists() {
		// Always check and create if needed (runs on init)
		$page_slug = 'complete-booking';
		$page_title = 'Complete Booking';
		
		// Check if page already exists by slug
		$page = get_page_by_path( $page_slug );
		
		// Also check by option
		$page_id_option = get_option( 'rca_completion_page_id' );
		if ( $page_id_option && ! $page ) {
			$page = get_post( $page_id_option );
			if ( $page && $page->post_type !== 'page' ) {
				$page = null;
			}
		}
		
		if ( ! $page ) {
			// Create the page
			$page_id = wp_insert_post( array(
				'post_title'   => $page_title,
				'post_name'    => $page_slug,
				'post_content' => '[rental_car_completion]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			) );
			
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				// Store page ID in options for reference
				update_option( 'rca_completion_page_id', $page_id );
			}
		} else {
			// Update existing page to ensure it has the shortcode
			$content = $page->post_content;
			if ( strpos( $content, '[rental_car_completion]' ) === false ) {
				wp_update_post( array(
					'ID'           => $page->ID,
					'post_content' => '[rental_car_completion]',
				) );
			}
			update_option( 'rca_completion_page_id', $page->ID );
		}
	}
	
	/**
	 * Handle completion page display
	 */
	public function handle_completion_page() {
		// Check if this is the completion page
		$request_uri = $_SERVER['REQUEST_URI'];
		if ( strpos( $request_uri, '/complete-booking/' ) === false ) {
			return;
		}
		
		// Get token from query string
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
		
		if ( empty( $token ) ) {
			wp_die( 'Invalid completion link. Please contact support.', 'Invalid Link', array( 'response' => 404 ) );
		}
		
		// Find booking by token
		$booking_id = $this->find_booking_by_token( $token );
		
		if ( ! $booking_id ) {
			wp_die( 'Completion link is invalid or has expired. Please contact support.', 'Invalid Link', array( 'response' => 404 ) );
		}
		
		// Check if booking is already completed
		// Simple form submissions create bookings with status 'pending' and _rca_is_lead = 'yes'
		// So we check for the lead flag instead of status
		$is_lead = get_post_meta( $booking_id, '_rca_is_lead', true );
		$status = get_post_meta( $booking_id, '_rca_booking_status', true );
		if ( $is_lead !== 'yes' && $status === 'completed' ) {
			wp_die( 'This booking has already been completed.', 'Already Completed', array( 'response' => 403 ) );
		}
		
		// Handle form submission if this is a POST
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['rca_complete_booking'] ) ) {
			$this->process_completion_form_submission( $booking_id, $token );
			return;
		}
		
		// Load completion form template
		$this->render_completion_form( $booking_id );
		exit;
	}
	
	/**
	 * Render completion form shortcode
	 */
	public function render_completion_form_shortcode( $atts ) {
		// Get token from query string
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
		
		// If no token, show a message (for testing - remove in production if needed)
		if ( empty( $token ) ) {
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">Invalid completion link. Please contact support. (Token missing)</div>';
		}
		
		// Find booking by token
		$booking_id = $this->find_booking_by_token( $token );
		
		if ( ! $booking_id ) {
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">Completion link is invalid or has expired. Please contact support.</div>';
		}
		
		// Check if this is a success redirect FIRST (before checking if already completed)
		if ( isset( $_GET['completed'] ) && $_GET['completed'] == '1' ) {
			return '<div class="rca-alert rca-alert-success" style="max-width: 800px; margin: 2rem auto; padding: 2rem; text-align: center; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><h3 style="color: #10b981; margin-bottom: 1rem;">Booking Completed Successfully!</h3><p style="color: #155724; line-height: 1.6;">Your rental agreement has been completed. We will contact you shortly to confirm your rental.</p></div>';
		}
		
		// Check if booking is already completed (only show error if NOT a success redirect)
		$is_lead = get_post_meta( $booking_id, '_rca_is_lead', true );
		$status = get_post_meta( $booking_id, '_rca_booking_status', true );
		if ( $is_lead !== 'yes' && $status === 'completed' ) {
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">This booking has already been completed.</div>';
		}
		
		// Render completion form
		ob_start();
		try {
			$this->render_completion_form_content( $booking_id );
			$output = ob_get_clean();
		} catch ( Exception $e ) {
			ob_end_clean();
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">Error loading form: ' . esc_html( $e->getMessage() ) . '</div>';
		} catch ( Error $e ) {
			ob_end_clean();
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">Error loading form: ' . esc_html( $e->getMessage() ) . '</div>';
		}
		
		// Return output or fallback message if empty
		if ( empty( trim( $output ) ) ) {
			return '<div class="rca-alert rca-alert-error" style="padding: 1rem; margin: 2rem auto; max-width: 800px; background: #fee; border: 1px solid #fcc; border-radius: 4px;">Error loading form. The form content is empty. Please contact support.</div>';
		}
		
		return $output;
	}
	
	/**
	 * Find booking by completion token
	 */
	private function find_booking_by_token( $token ) {
		$args = array(
			'post_type' => 'rental_booking',
			'posts_per_page' => 1,
			'meta_key' => '_rca_completion_token',
			'meta_value' => $token,
			'post_status' => 'publish',
		);
		
		$query = new WP_Query( $args );
		
		if ( $query->have_posts() ) {
			$query->the_post();
			$booking_id = get_the_ID();
			wp_reset_postdata();
			return $booking_id;
		}
		
		return false;
	}
	
	/**
	 * Render completion form content (used by shortcode)
	 */
	private function render_completion_form_content( $booking_id ) {
		// Get booking data
		$vehicle_id = get_post_meta( $booking_id, '_rca_vehicle_id', true );
		$fullname = get_post_meta( $booking_id, '_rca_customer_name', true );
		$email = get_post_meta( $booking_id, '_rca_customer_email', true );
		$phone = get_post_meta( $booking_id, '_rca_customer_phone', true );
		$license = get_post_meta( $booking_id, '_rca_customer_license', true );
		$address = get_post_meta( $booking_id, '_rca_customer_address', true );
		$driver_state = get_post_meta( $booking_id, '_rca_driver_state', true );
		$start_date = get_post_meta( $booking_id, '_rca_start_date', true );
		$end_date = get_post_meta( $booking_id, '_rca_end_date', true );
		$base_fee_weekly = get_post_meta( $booking_id, '_rca_base_fee_weekly', true );
		
		// Get vehicle data
		$vehicle = get_post( $vehicle_id );
		
		// Set up completion token
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
		
		// Render completion form with all variables available
		include RCA_PLUGIN_DIR . 'templates/booking-form-completion.php';
	}
	
	/**
	 * Render completion form with pre-filled data (legacy - for direct template rendering)
	 */
	private function render_completion_form( $booking_id ) {
		// Include header
		get_header();
		
		// Render completion form content
		$this->render_completion_form_content( $booking_id );
		
		// Include footer
		get_footer();
	}
	
	/**
	 * Process completion form submission
	 */
	private function process_completion_form_submission( $booking_id, $token ) {
		// Verify token again
		$stored_token = get_post_meta( $booking_id, '_rca_completion_token', true );
		if ( $stored_token !== $token ) {
			wp_die( 'Security verification failed. Please try again.', 'Security Error', array( 'response' => 403 ) );
		}
		
		// Verify nonce
		if ( ! isset( $_POST['rca_booking_nonce'] ) || ! wp_verify_nonce( $_POST['rca_booking_nonce'], 'rca_submit_booking_action' ) ) {
			wp_die( 'Security check failed. Please refresh the page and try again.', 'Security Error', array( 'response' => 403 ) );
		}
		
		// Get vehicle ID from booking
		$vehicle_id = get_post_meta( $booking_id, '_rca_vehicle_id', true );
		
		// Fetch vehicle data from backend at time of submission
		$vehicle_make = '';
		$vehicle_model = '';
		$vehicle_year = '';
		$vehicle_vin = '';
		$vehicle_color = '';
		$vehicle_plate = '';
		$vehicle_weekly_rate = '';
		$vehicle_title = '';
		$vehicle_image_id = 0;
		$vehicle_image_url = '';
		
		if ( $vehicle_id ) {
			$vehicle_post = get_post( $vehicle_id );
			if ( $vehicle_post ) {
				$vehicle_make = get_post_meta( $vehicle_id, '_rca_make', true );
				$vehicle_model = get_post_meta( $vehicle_id, '_rca_model', true );
				$vehicle_year = get_post_meta( $vehicle_id, '_rca_year', true );
				$vehicle_vin = get_post_meta( $vehicle_id, '_rca_vin', true );
				$vehicle_color = get_post_meta( $vehicle_id, '_rca_color', true );
				$vehicle_plate = get_post_meta( $vehicle_id, '_rca_plate', true );
				$vehicle_weekly_rate = get_post_meta( $vehicle_id, '_rca_weekly_rate', true );
				$vehicle_title = get_the_title( $vehicle_id );
				
				$vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
				if ( $vehicle_image_id ) {
					$vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
				}
			}
		}
		
		// Get existing renter ID or generate new one
		$renter_id = get_post_meta( $booking_id, '_rca_renter_id', true );
		if ( empty( $renter_id ) ) {
			$renter_id = $this->generate_renter_id();
		}
		
		// Sanitize and validate form data (similar to regular submission)
		$fullname = sanitize_text_field( $_POST['rca_fullname'] );
		$email = sanitize_email( $_POST['rca_email'] );
		$phone = sanitize_text_field( $_POST['rca_phone'] );
		$license = sanitize_text_field( $_POST['rca_license'] );
		$address = sanitize_textarea_field( $_POST['rca_address'] );
		// Agreement date is automatically set to current date (not from form)
		$agreement_date = date( 'Y-m-d' );
		$signature = sanitize_text_field( $_POST['rca_signature'] );
		$start_date = sanitize_text_field( $_POST['rca_start_date'] );
		$end_date = sanitize_text_field( $_POST['rca_end_date'] );
		$insurance_option = sanitize_text_field( $_POST['rca_insurance_option'] );
		$marketing_optin = isset( $_POST['rca_marketing_optin'] ) ? 'yes' : 'no';
		
		// Update booking with completion form data
		update_post_meta( $booking_id, '_rca_customer_name', $fullname );
		update_post_meta( $booking_id, '_rca_customer_email', $email );
		update_post_meta( $booking_id, '_rca_customer_phone', $phone );
		update_post_meta( $booking_id, '_rca_customer_license', $license );
		update_post_meta( $booking_id, '_rca_customer_address', $address );
		update_post_meta( $booking_id, '_rca_renter_id', $renter_id );
		update_post_meta( $booking_id, '_rca_agreement_date', $agreement_date );
		update_post_meta( $booking_id, '_rca_signature', $signature );
		
		// Store individual fields for new structure
		update_post_meta( $booking_id, '_rca_first_name', $first_name );
		update_post_meta( $booking_id, '_rca_last_name', $last_name );
		update_post_meta( $booking_id, '_rca_street_address', $street_address );
		update_post_meta( $booking_id, '_rca_apt_unit', $apt_unit );
		update_post_meta( $booking_id, '_rca_city', $city );
		update_post_meta( $booking_id, '_rca_state', $state );
		update_post_meta( $booking_id, '_rca_zip_code', $zip_code );
		
		// Update vehicle snapshot from backend data
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
		
		// Update rental details
		update_post_meta( $booking_id, '_rca_start_date', $start_date );
		update_post_meta( $booking_id, '_rca_end_date', $end_date );
		update_post_meta( $booking_id, '_rca_base_fee_weekly', $vehicle_weekly_rate );
		
		// Update insurance
		update_post_meta( $booking_id, '_rca_insurance_option', $insurance_option );
		update_post_meta( $booking_id, '_rca_marketing_optin', $marketing_optin );
		
		// Store all initials
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
		
		$initials_data = array();
		foreach ( $required_initials as $initial_field ) {
			$initials_data[ $initial_field ] = isset( $_POST[ $initial_field ] ) ? 'yes' : 'no';
		}
		update_post_meta( $booking_id, '_rca_all_initials', $initials_data );
		
		// Mark as completed
		update_post_meta( $booking_id, '_rca_booking_status', 'completed' );
		update_post_meta( $booking_id, '_rca_terms_accepted', 'yes' );
		delete_post_meta( $booking_id, '_rca_is_lead' );
		
		// Send notifications (detailed email with PDF for completed booking)
		$this->send_emails( $booking_id, $email, $fullname, 'completion' );
		
		// Redirect to success page - respects WordPress permalink structure from Settings > Permalinks
		$page_id = get_option( 'rca_completion_page_id' );
		if ( $page_id ) {
			// Use get_permalink() which automatically respects the permalink structure
			// configured in WordPress admin (Settings > Permalinks)
			$page_url = get_permalink( $page_id );
			if ( $page_url ) {
				$redirect_url = add_query_arg( array(
					'completed' => '1',
					'token' => urlencode( $token )
				), $page_url );
			} else {
				// Fallback: construct URL manually based on permalink structure
				$permalink_structure = get_option( 'permalink_structure' );
				if ( empty( $permalink_structure ) ) {
					// Plain permalinks: use ?page_id= format
					$redirect_url = add_query_arg( array(
						'page_id' => $page_id,
						'completed' => '1',
						'token' => urlencode( $token )
					), home_url( '/' ) );
				} else {
					// Pretty permalinks: use slug
					$page = get_post( $page_id );
					if ( $page ) {
						$redirect_url = home_url( '/' . $page->post_name . '/?completed=1&token=' . urlencode( $token ) );
					} else {
						$redirect_url = home_url( '/complete-booking/?completed=1&token=' . urlencode( $token ) );
					}
				}
			}
		} else {
			// Fallback to old URL structure
			$redirect_url = add_query_arg( array(
				'completed' => '1',
				'token' => urlencode( $token )
			), home_url( '/complete-booking/' ) );
		}
		wp_redirect( $redirect_url );
		exit;
	}
	
	/**
	 * Process completion form submission via AJAX
	 */
	private function process_completion_form_submission_ajax( $booking_id, $token ) {
		// Verify token
		$stored_token = get_post_meta( $booking_id, '_rca_completion_token', true );
		if ( $stored_token !== $token ) {
			while ( ob_get_level() ) {
				ob_end_clean();
			}
			wp_send_json_error( array( 'message' => 'Security verification failed. Please try again.' ) );
			wp_die();
		}
		
		// Get vehicle ID from booking
		$vehicle_id = get_post_meta( $booking_id, '_rca_vehicle_id', true );
		
		// Fetch vehicle data from backend at time of submission
		$vehicle_make = '';
		$vehicle_model = '';
		$vehicle_year = '';
		$vehicle_vin = '';
		$vehicle_color = '';
		$vehicle_plate = '';
		$vehicle_weekly_rate = '';
		$vehicle_title = '';
		$vehicle_image_id = 0;
		$vehicle_image_url = '';
		
		if ( $vehicle_id ) {
			$vehicle_post = get_post( $vehicle_id );
			if ( $vehicle_post ) {
				$vehicle_make = get_post_meta( $vehicle_id, '_rca_make', true );
				$vehicle_model = get_post_meta( $vehicle_id, '_rca_model', true );
				$vehicle_year = get_post_meta( $vehicle_id, '_rca_year', true );
				$vehicle_vin = get_post_meta( $vehicle_id, '_rca_vin', true );
				$vehicle_color = get_post_meta( $vehicle_id, '_rca_color', true );
				$vehicle_plate = get_post_meta( $vehicle_id, '_rca_plate', true );
				$vehicle_weekly_rate = get_post_meta( $vehicle_id, '_rca_weekly_rate', true );
				$vehicle_title = get_the_title( $vehicle_id );
				
				$vehicle_image_id = get_post_thumbnail_id( $vehicle_id );
				if ( $vehicle_image_id ) {
					$vehicle_image_url = wp_get_attachment_image_url( $vehicle_image_id, 'full' );
				}
			}
		}
		
		// Get existing renter ID or generate new one
		$renter_id = get_post_meta( $booking_id, '_rca_renter_id', true );
		if ( empty( $renter_id ) ) {
			$renter_id = $this->generate_renter_id();
		}
		
		// Validate and sanitize form data
		$fullname = sanitize_text_field( $_POST['rca_fullname'] );
		$email = sanitize_email( $_POST['rca_email'] );
		$phone = sanitize_text_field( $_POST['rca_phone'] );
		$license = sanitize_text_field( $_POST['rca_license'] );
		$address = sanitize_textarea_field( $_POST['rca_address'] );
		// Agreement date is automatically set to current date (not from form)
		$agreement_date = date( 'Y-m-d' );
		$signature = sanitize_text_field( $_POST['rca_signature'] );
		$start_date = sanitize_text_field( $_POST['rca_start_date'] );
		$end_date = sanitize_text_field( $_POST['rca_end_date'] );
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
				while ( ob_get_level() ) {
					ob_end_clean();
				}
				wp_send_json_error( array( 'message' => 'You must acknowledge all sections of the agreement. Please review and check all required fields.' ) );
				wp_die();
			}
		}
		
		// Update booking with completion form data
		update_post_meta( $booking_id, '_rca_customer_name', $fullname );
		update_post_meta( $booking_id, '_rca_customer_email', $email );
		update_post_meta( $booking_id, '_rca_customer_phone', $phone );
		update_post_meta( $booking_id, '_rca_customer_license', $license );
		update_post_meta( $booking_id, '_rca_customer_address', $address );
		update_post_meta( $booking_id, '_rca_renter_id', $renter_id );
		update_post_meta( $booking_id, '_rca_agreement_date', $agreement_date );
		update_post_meta( $booking_id, '_rca_signature', $signature );
		
		// Store individual fields for new structure
		update_post_meta( $booking_id, '_rca_first_name', $first_name );
		update_post_meta( $booking_id, '_rca_last_name', $last_name );
		update_post_meta( $booking_id, '_rca_street_address', $street_address );
		update_post_meta( $booking_id, '_rca_apt_unit', $apt_unit );
		update_post_meta( $booking_id, '_rca_city', $city );
		update_post_meta( $booking_id, '_rca_state', $state );
		update_post_meta( $booking_id, '_rca_zip_code', $zip_code );
		
		// Update vehicle snapshot from backend data
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
		
		// Update rental details
		update_post_meta( $booking_id, '_rca_start_date', $start_date );
		update_post_meta( $booking_id, '_rca_end_date', $end_date );
		update_post_meta( $booking_id, '_rca_base_fee_weekly', $vehicle_weekly_rate );
		
		// Update insurance
		update_post_meta( $booking_id, '_rca_insurance_option', $insurance_option );
		update_post_meta( $booking_id, '_rca_marketing_optin', $marketing_optin );
		
		// Store all initials
		$initials_data = array();
		foreach ( $required_initials as $initial_field ) {
			$initials_data[ $initial_field ] = isset( $_POST[ $initial_field ] ) ? 'yes' : 'no';
		}
		update_post_meta( $booking_id, '_rca_all_initials', $initials_data );
		
		// Mark as completed
		update_post_meta( $booking_id, '_rca_booking_status', 'completed' );
		update_post_meta( $booking_id, '_rca_terms_accepted', 'yes' );
		delete_post_meta( $booking_id, '_rca_is_lead' );
		
		// Send notifications (detailed email with PDF for completed booking)
		$this->send_emails( $booking_id, $email, $fullname, 'completion' );
		
		// Return success response with proper redirect URL
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		
		// Get the completion page URL - respects WordPress permalink structure
		$page_id = get_option( 'rca_completion_page_id' );
		if ( $page_id ) {
			$page_url = get_permalink( $page_id );
			if ( $page_url ) {
				$redirect_url = add_query_arg( array(
					'completed' => '1',
					'token' => urlencode( $token )
				), $page_url );
			} else {
				// Fallback: construct URL manually based on permalink structure
				$permalink_structure = get_option( 'permalink_structure' );
				if ( empty( $permalink_structure ) ) {
					// Plain permalinks: use ?page_id= format
					$redirect_url = add_query_arg( array(
						'page_id' => $page_id,
						'completed' => '1',
						'token' => urlencode( $token )
					), home_url( '/' ) );
				} else {
					// Pretty permalinks: use slug
					$page = get_post( $page_id );
					if ( $page ) {
						$redirect_url = home_url( '/' . $page->post_name . '/?completed=1&token=' . urlencode( $token ) );
					} else {
						$redirect_url = home_url( '/complete-booking/?completed=1&token=' . urlencode( $token ) );
					}
				}
			}
		} else {
			// Fallback to old URL structure
			$redirect_url = add_query_arg( array(
				'completed' => '1',
				'token' => urlencode( $token )
			), home_url( '/complete-booking/' ) );
		}
		
		wp_send_json_success( array( 
			'message' => 'Booking completed successfully!', 
			'booking_id' => $booking_id,
			'redirect_url' => $redirect_url
		) );
		wp_die();
	}
}

