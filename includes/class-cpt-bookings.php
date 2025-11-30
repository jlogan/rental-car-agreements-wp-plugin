<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_CPT_Bookings {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_filter( 'manage_rental_booking_posts_columns', array( $this, 'set_columns' ) );
		add_action( 'manage_rental_booking_posts_custom_column', array( $this, 'custom_column' ), 10, 2 );
		// Hide "Add New" button from admin
		add_action( 'admin_head', array( $this, 'hide_add_new_button' ) );
		// Remove visibility meta box
		add_action( 'admin_head', array( $this, 'remove_visibility_meta_box' ) );
		// Handle completion link generation/revocation
		add_action( 'admin_post_rca_generate_completion_link', array( $this, 'handle_generate_completion_link' ) );
		add_action( 'admin_post_rca_revoke_completion_link', array( $this, 'handle_revoke_completion_link' ) );
		// Update completion links on admin load to match current permalink structure
		add_action( 'admin_init', array( $this, 'update_completion_links_on_admin_load' ) );
	}

	public function hide_add_new_button() {
		global $typenow;
		if ( $typenow === 'rental_booking' ) {
			echo '<style>
				.page-title-action,
				.wrap .page-title-action,
				.wrap a.page-title-action {
					display: none !important;
				}
			</style>';
		}
	}

	public function remove_visibility_meta_box() {
		global $typenow;
		if ( $typenow === 'rental_booking' ) {
			// Hide visibility section via CSS
			echo '<style>
				#visibility,
				.misc-pub-visibility,
				.post-visibility-label,
				.post-visibility-select,
				#post-visibility-select,
				.misc-pub-section.misc-pub-visibility {
					display: none !important;
				}
			</style>';
		}
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Bookings',
			'singular_name'      => 'Booking',
			'menu_name'          => 'Bookings',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Booking',
			'edit_item'          => 'Edit Booking',
			'new_item'           => 'New Booking',
			'view_item'          => 'View Booking',
			'all_items'          => 'All Bookings',
			'search_items'       => 'Search Bookings',
			'not_found'          => 'No bookings found.',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=rental_vehicle',
			'capability_type'    => 'post',
			'capabilities'       => array(
				'create_posts' => 'do_not_allow', // Prevent creating new bookings from admin
			),
			'map_meta_cap'       => true,
			'hierarchical'       => false,
			'supports'           => array( 'title' ), // Title is auto-generated
			'publicly_queryable' => false,
			'exclude_from_search' => true,
		);

		register_post_type( 'rental_booking', $args );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'rca_booking_actions',
			'Actions',
			array( $this, 'render_actions_box' ),
			'rental_booking',
			'side',
			'high'
		);

		add_meta_box(
			'rca_booking_details',
			'Booking & Customer Details',
			array( $this, 'render_details_box' ),
			'rental_booking',
			'normal',
			'high'
		);
		
		// Remove default WordPress meta boxes (but keep submitdiv for publish/update box)
		remove_meta_box( 'slugdiv', 'rental_booking', 'normal' );
		remove_meta_box( 'authordiv', 'rental_booking', 'normal' );
		remove_meta_box( 'postcustom', 'rental_booking', 'normal' );
		remove_meta_box( 'commentsdiv', 'rental_booking', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'rental_booking', 'normal' );
		remove_meta_box( 'revisionsdiv', 'rental_booking', 'normal' );
	}

	public function render_actions_box( $post ) {
		$completion_link = get_post_meta( $post->ID, '_rca_completion_link', true );
		$completion_token = get_post_meta( $post->ID, '_rca_completion_token', true );
		
		// Check for admin notices
		if ( isset( $_GET['rca_link_generated'] ) && $_GET['rca_link_generated'] == '1' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Completion link generated successfully!</p></div>';
		}
		if ( isset( $_GET['rca_link_revoked'] ) && $_GET['rca_link_revoked'] == '1' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Completion link revoked successfully!</p></div>';
		}
		?>
		<div class="rca-actions">
			<div style="margin-top: 10px;">
				<a href="<?php echo admin_url( 'admin.php?page=rental_car_agreement&booking_id=' . $post->ID . '&download=1' ); ?>" class="button button-primary button-large" style="width:100%; text-align:center; margin-bottom: 5px;">Download Agreement</a>
			</div>
			<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
				<h4>Completion Link</h4>
				<?php 
				// If no link exists, generate it automatically
				if ( ! $completion_link ) {
					self::generate_completion_link( $post->ID );
					$completion_link = get_post_meta( $post->ID, '_rca_completion_link', true );
				}
				?>
				<?php if ( $completion_link ) : ?>
					<div style="margin-bottom: 10px;">
						<input type="text" readonly value="<?php echo esc_attr( $completion_link ); ?>" id="rca-completion-link" style="width: 100%; padding: 5px; font-size: 12px;" onclick="this.select();">
					</div>
					<button type="button" class="button button-primary" style="width:100%; margin-bottom: 5px;" onclick="document.getElementById('rca-completion-link').select(); document.execCommand('copy'); alert('Link copied to clipboard!');">Copy Link</button>
				<?php else : ?>
					<p style="color: #d63638;">Error: Unable to generate completion link. Please refresh the page.</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Handle completion link generation
	 */
	public function handle_generate_completion_link() {
		// Get booking ID from POST
		$booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
		
		if ( ! $booking_id ) {
			wp_die( 'Invalid booking ID. Please go back and try again.', 'Error', array( 'back_link' => true ) );
		}
		
		// Verify nonce manually to avoid auto-redirect on failure
		$nonce = isset( $_POST['rca_generate_nonce'] ) ? $_POST['rca_generate_nonce'] : '';
		if ( ! wp_verify_nonce( $nonce, 'rca_generate_completion_link_' . $booking_id ) ) {
			wp_die( 'Security check failed. Please go back and try again.', 'Security Error', array( 'back_link' => true ) );
		}
		
		if ( ! current_user_can( 'edit_post', $booking_id ) ) {
			wp_die( 'You do not have permission to perform this action.', 'Permission Denied', array( 'back_link' => true ) );
		}
		
		// Generate the completion link
		self::generate_completion_link( $booking_id );
		
		// Get the referrer URL as fallback, or construct the edit URL
		$referer = wp_get_referer();
		if ( $referer && strpos( $referer, 'post.php' ) !== false && strpos( $referer, 'post=' . $booking_id ) !== false ) {
			// Use referrer if it's the correct edit page
			$redirect_url = add_query_arg( 'rca_link_generated', '1', $referer );
		} else {
			// Construct the edit page URL
			$redirect_url = add_query_arg( array(
				'post' => $booking_id,
				'action' => 'edit',
				'rca_link_generated' => '1'
			), admin_url( 'post.php' ) );
		}
		
		wp_safe_redirect( $redirect_url );
		exit;
	}
	
	/**
	 * Handle completion link revocation
	 */
	public function handle_revoke_completion_link() {
		$booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
		
		if ( ! $booking_id ) {
			wp_die( 'Invalid booking ID.' );
		}
		
		check_admin_referer( 'rca_revoke_completion_link_' . $booking_id, 'rca_revoke_nonce' );
		
		if ( ! current_user_can( 'edit_post', $booking_id ) ) {
			wp_die( 'You do not have permission to perform this action.' );
		}
		
		delete_post_meta( $booking_id, '_rca_completion_link' );
		delete_post_meta( $booking_id, '_rca_completion_token' );
		
		wp_redirect( admin_url( 'post.php?post=' . $booking_id . '&action=edit&rca_link_revoked=1' ) );
		exit;
	}
	
	/**
	 * Generate unique completion link for booking
	 * Static method so it can be called from anywhere
	 */
	public static function generate_completion_link( $booking_id, $regenerate_token = false ) {
		// Get or generate token
		$token = get_post_meta( $booking_id, '_rca_completion_token', true );
		if ( empty( $token ) || $regenerate_token ) {
			$token = uniqid( 'rca_' . $booking_id . '_', true );
		}
		
		// Get the completion page
		$page_id = get_option( 'rca_completion_page_id' );
		if ( ! $page_id ) {
			// Try to find page by slug
			$page = get_page_by_path( 'complete-booking' );
			if ( $page ) {
				$page_id = $page->ID;
				update_option( 'rca_completion_page_id', $page_id );
			}
		}
		
		// Get the completion page URL - respects WordPress permalink structure from Settings > Permalinks
		if ( $page_id ) {
			// Use get_permalink() which automatically respects the permalink structure
			// configured in WordPress admin (Settings > Permalinks)
			// - For pretty permalinks: returns http://site.com/complete-booking/
			// - For plain permalinks: returns http://site.com/?page_id=123
			$page_url = get_permalink( $page_id );
			
			if ( $page_url ) {
				// Add token as query parameter
				// add_query_arg handles both pretty and plain permalink URLs correctly
				$completion_url = add_query_arg( 'token', urlencode( $token ), $page_url );
			} else {
				// Fallback: construct URL manually based on permalink structure
				$permalink_structure = get_option( 'permalink_structure' );
				if ( empty( $permalink_structure ) ) {
					// Plain permalinks: use ?page_id= format
					$completion_url = add_query_arg( array(
						'page_id' => $page_id,
						'token' => urlencode( $token )
					), home_url( '/' ) );
				} else {
					// Pretty permalinks: use slug
					$page = get_post( $page_id );
					if ( $page ) {
						$completion_url = home_url( '/' . $page->post_name . '/?token=' . urlencode( $token ) );
					} else {
						$completion_url = home_url( '/complete-booking/?token=' . urlencode( $token ) );
					}
				}
			}
		} else {
			// Fallback to old URL structure
			$completion_url = home_url( '/complete-booking/?token=' . urlencode( $token ) );
		}
		
		// Save token and link
		update_post_meta( $booking_id, '_rca_completion_token', $token );
		update_post_meta( $booking_id, '_rca_completion_link', $completion_url );
		
		return $completion_url;
	}
	
	/**
	 * Update all existing completion links to match current permalink structure
	 * Runs on admin_init to ensure links are always up-to-date
	 */
	public function update_completion_links_on_admin_load() {
		// Only run in admin area
		if ( ! is_admin() ) {
			return;
		}
		
		// Get all bookings that have completion tokens
		$bookings = get_posts( array(
			'post_type' => 'rental_booking',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'meta_key' => '_rca_completion_token',
			'meta_compare' => 'EXISTS',
		) );
		
		if ( empty( $bookings ) ) {
			return;
		}
		
		// Update each booking's completion link to match current permalink structure
		// This preserves the existing token but updates the URL format
		foreach ( $bookings as $booking ) {
			$token = get_post_meta( $booking->ID, '_rca_completion_token', true );
			if ( empty( $token ) ) {
				continue;
			}
			
			// Regenerate link with existing token (don't regenerate token)
			self::generate_completion_link( $booking->ID, false );
		}
	}

	public function render_details_box( $post ) {
		wp_nonce_field( 'rca_save_booking_meta', 'rca_booking_meta_nonce' );

		// Fetch Meta - Renter Information
		$vehicle_id   = get_post_meta( $post->ID, '_rca_vehicle_id', true );
		$fullname     = get_post_meta( $post->ID, '_rca_customer_name', true );
		$license      = get_post_meta( $post->ID, '_rca_customer_license', true );
		$address      = get_post_meta( $post->ID, '_rca_customer_address', true );
		$phone        = get_post_meta( $post->ID, '_rca_customer_phone', true );
		$email        = get_post_meta( $post->ID, '_rca_customer_email', true );
		$renter_id    = get_post_meta( $post->ID, '_rca_renter_id', true );
		$agreement_date = get_post_meta( $post->ID, '_rca_agreement_date', true );
		$signature    = get_post_meta( $post->ID, '_rca_signature', true );

		// Vehicle Information Snapshot (from time of booking)
		$vehicle_make = get_post_meta( $post->ID, '_rca_vehicle_make', true );
		$vehicle_model = get_post_meta( $post->ID, '_rca_vehicle_model', true );
		$vehicle_year = get_post_meta( $post->ID, '_rca_vehicle_year', true );
		$vehicle_vin = get_post_meta( $post->ID, '_rca_vehicle_vin', true );
		$vehicle_color = get_post_meta( $post->ID, '_rca_vehicle_color', true );
		$vehicle_plate = get_post_meta( $post->ID, '_rca_vehicle_plate', true );
		$vehicle_weekly_rate_snapshot = get_post_meta( $post->ID, '_rca_vehicle_weekly_rate_snapshot', true );
		$vehicle_title_snapshot = get_post_meta( $post->ID, '_rca_vehicle_title_snapshot', true );
		$vehicle_image_url_snapshot = get_post_meta( $post->ID, '_rca_vehicle_image_url_snapshot', true );
		$vehicle_image_id_snapshot = get_post_meta( $post->ID, '_rca_vehicle_image_id_snapshot', true );

		// Rental Details
		$start_date   = get_post_meta( $post->ID, '_rca_start_date', true );
		$end_date     = get_post_meta( $post->ID, '_rca_end_date', true );
		$base_fee_weekly = get_post_meta( $post->ID, '_rca_base_fee_weekly', true );

		// Insurance
		$insurance    = get_post_meta( $post->ID, '_rca_insurance_option', true );
		$marketing_optin = get_post_meta( $post->ID, '_rca_marketing_optin', true );
		$all_initials = get_post_meta( $post->ID, '_rca_all_initials', true );
		$status       = get_post_meta( $post->ID, '_rca_booking_status', true );
		$terms        = get_post_meta( $post->ID, '_rca_terms_accepted', true );

		// Get Vehicles for Dropdown
		$vehicles = get_posts( array( 'post_type' => 'rental_vehicle', 'numberposts' => -1 ) );
		?>
		<style>
			.rca-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px; }
			.rca-col { flex: 1; min-width: 200px; }
			.rca-col label { display: block; font-weight: bold; margin-bottom: 5px; }
			.rca-col input, .rca-col select, .rca-col textarea { width: 100%; }
			.rca-section-divider { border-top: 2px solid #ddd; margin: 20px 0; padding-top: 20px; }
			.rca-initials-list { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-top: 10px; }
			.rca-initials-list ul { margin: 0; padding-left: 20px; }
			.rca-initials-list li { margin: 5px 0; }
		</style>

		<div class="rca-meta-box">
			<h3>Booking Status</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_booking_status">Booking Status</label>
					<select id="rca_booking_status" name="rca_booking_status">
						<option value="pending" <?php selected( $status, 'pending' ); ?>>Pending</option>
						<option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
						<option value="active" <?php selected( $status, 'active' ); ?>>Active (On Rent)</option>
						<option value="completed" <?php selected( $status, 'completed' ); ?>>Completed</option>
						<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>>Cancelled</option>
					</select>
				</div>
			</div>

			<div class="rca-section-divider"></div>
			<h3>Rental Vehicle Information (Snapshot at Time of Booking)</h3>
			<p style="font-style: italic; color: #666; margin-bottom: 15px;">This information reflects the vehicle's state when the booking was submitted and cannot be changed.</p>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_vehicle_title_snapshot">Vehicle Title</label>
					<input type="text" id="rca_vehicle_title_snapshot" name="rca_vehicle_title_snapshot" value="<?php echo esc_attr( $vehicle_title_snapshot ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_vehicle_make">Make</label>
					<input type="text" id="rca_vehicle_make" name="rca_vehicle_make" value="<?php echo esc_attr( $vehicle_make ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_vehicle_model">Model</label>
					<input type="text" id="rca_vehicle_model" name="rca_vehicle_model" value="<?php echo esc_attr( $vehicle_model ); ?>" readonly>
				</div>
			</div>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_vehicle_year">Year</label>
					<input type="text" id="rca_vehicle_year" name="rca_vehicle_year" value="<?php echo esc_attr( $vehicle_year ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_vehicle_vin">VIN</label>
					<input type="text" id="rca_vehicle_vin" name="rca_vehicle_vin" value="<?php echo esc_attr( $vehicle_vin ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_vehicle_color">Color</label>
					<input type="text" id="rca_vehicle_color" name="rca_vehicle_color" value="<?php echo esc_attr( $vehicle_color ); ?>" readonly>
				</div>
			</div>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_vehicle_plate">Plate Number</label>
					<input type="text" id="rca_vehicle_plate" name="rca_vehicle_plate" value="<?php echo esc_attr( $vehicle_plate ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_vehicle_weekly_rate_snapshot">Weekly Rate (at time of booking) $</label>
					<?php 
					// Strip any $ signs from the stored value to ensure clean display
					$clean_rate = str_replace( array( '$', ',' ), '', $vehicle_weekly_rate_snapshot );
					?>
					<input type="text" id="rca_vehicle_weekly_rate_snapshot" name="rca_vehicle_weekly_rate_snapshot" value="<?php echo esc_attr( $clean_rate ); ?>" readonly>
				</div>
			</div>
			<?php if ( $vehicle_image_url_snapshot ) : ?>
			<div class="rca-row">
				<div class="rca-col" style="flex: 1 1 100%;">
					<label>Vehicle Image (at time of booking)</label>
					<div style="margin-top: 10px;">
						<img src="<?php echo esc_url( $vehicle_image_url_snapshot ); ?>" alt="Vehicle snapshot" style="max-width: 400px; height: auto; border: 1px solid #ddd; border-radius: 4px; padding: 5px; background: #fff;">
					</div>
					<p style="margin-top: 5px; font-size: 12px; color: #666;">
						<strong>Image URL:</strong> <a href="<?php echo esc_url( $vehicle_image_url_snapshot ); ?>" target="_blank"><?php echo esc_html( $vehicle_image_url_snapshot ); ?></a>
					</p>
				</div>
			</div>
			<?php endif; ?>

			<div class="rca-section-divider"></div>
			<h3>Rental Term & Fees</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_start_date">Start Date</label>
					<input type="date" id="rca_start_date" name="rca_start_date" value="<?php echo esc_attr( $start_date ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_end_date">End Date</label>
					<input type="date" id="rca_end_date" name="rca_end_date" value="<?php echo esc_attr( $end_date ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_base_fee_weekly">Base Fee (Weekly)</label>
					<input type="text" id="rca_base_fee_weekly" name="rca_base_fee_weekly" value="<?php echo esc_attr( $base_fee_weekly ); ?>">
				</div>
			</div>

			<div class="rca-section-divider"></div>
			<h3>Renter Information</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_renter_id">Renter I.D. Information</label>
					<input type="text" id="rca_renter_id" name="rca_renter_id" value="<?php echo esc_attr( $renter_id ); ?>" readonly>
				</div>
				<div class="rca-col">
					<label for="rca_customer_name">Full Name</label>
					<input type="text" id="rca_customer_name" name="rca_customer_name" value="<?php echo esc_attr( $fullname ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_customer_license">Driver License #</label>
					<input type="text" id="rca_customer_license" name="rca_customer_license" value="<?php echo esc_attr( $license ); ?>">
				</div>
			</div>

			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_customer_email">Email</label>
					<input type="email" id="rca_customer_email" name="rca_customer_email" value="<?php echo esc_attr( $email ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_customer_phone">Phone</label>
					<input type="text" id="rca_customer_phone" name="rca_customer_phone" value="<?php echo esc_attr( $phone ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_agreement_date">Agreement Date</label>
					<input type="date" id="rca_agreement_date" name="rca_agreement_date" value="<?php echo esc_attr( $agreement_date ); ?>" readonly>
				</div>
			</div>

			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_customer_address">Address</label>
					<textarea id="rca_customer_address" name="rca_customer_address" rows="2"><?php echo esc_textarea( $address ); ?></textarea>
				</div>
				<div class="rca-col">
					<label for="rca_signature">Signature (Electronic)</label>
					<input type="text" id="rca_signature" name="rca_signature" value="<?php echo esc_attr( $signature ); ?>" readonly>
				</div>
			</div>

			<div class="rca-section-divider"></div>
			<h3>Insurance Selection</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_insurance_option">Insurance Option Selected</label>
					<select id="rca_insurance_option" name="rca_insurance_option">
						<option value="option1" <?php selected( $insurance, 'option1' ); ?>>Option 1: Own Insurance</option>
						<option value="option2" <?php selected( $insurance, 'option2' ); ?>>Option 2: RCP ($20/day)</option>
						<option value="option3" <?php selected( $insurance, 'option3' ); ?>>Option 3: Personalized Policy</option>
						<option value="option4" <?php selected( $insurance, 'option4' ); ?>>Option 4: Included in Price</option>
					</select>
				</div>
				<div class="rca-col">
					<label>Marketing Opt-In</label>
					<p><strong><?php echo $marketing_optin === 'yes' ? 'YES - Opted in to marketing' : 'NO - Did not opt in'; ?></strong></p>
					<input type="hidden" name="rca_marketing_optin" value="<?php echo esc_attr( $marketing_optin ); ?>">
				</div>
			</div>

			<div class="rca-section-divider"></div>
			<h3>Agreement Acknowledgments</h3>
			<div class="rca-initials-list">
				<p><strong>All Required Sections Initialed:</strong></p>
				<?php if ( is_array( $all_initials ) && ! empty( $all_initials ) ) : ?>
					<ul>
						<?php 
						$section_labels = array(
							'rca_rental_term_initial' => 'Rental Term',
							'rca_mileage_initial' => 'Mileage',
							'rca_rental_fees_initial' => 'Rental Fees',
							'rca_keys_initial' => 'Loss of Keys',
							'rca_damage_responsibility_initial' => 'Damage Responsibility',
							'rca_insurance_initial' => 'Insurance Coverage',
							'rca_insurance_claims_initial' => 'Insurance Claims',
							'rca_indemnification_initial' => 'Indemnification',
							'rca_risk_acknowledgment_initial' => 'Acknowledgment of Risk',
							'rca_release_waiver_initial' => 'Release & Waiver',
							'rca_violation_charges_initial' => 'Violation Charges',
							'rca_vehicle_condition_initial' => 'Vehicle Condition',
							'rca_checkin_checkout_initial' => 'Check In & Check Out',
							'rca_free_will_initial' => 'Free Will Clause',
							'rca_discriminatory_initial' => 'Discriminatory Clause',
							'rca_ecpa_initial' => 'ECPA Clause',
							'rca_optin_optout_initial' => 'Opt-In/Opt-Out',
							'rca_arbitration_initial' => 'Arbitration Agreement',
							'rca_breach_contract_initial' => 'Breach of Contract',
							'rca_your_property_initial' => 'Your Property',
							'rca_returning_vehicle_initial' => 'Returning Vehicle',
							'rca_out_of_state_initial' => 'Out of State Driving',
						);
						foreach ( $all_initials as $key => $value ) : 
							$label = isset( $section_labels[ $key ] ) ? $section_labels[ $key ] : $key;
						?>
							<li><?php echo esc_html( $label ); ?>: <strong><?php echo $value === 'yes' ? '✓ Initialed' : '✗ Not Initialed'; ?></strong></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p>No initials data recorded.</p>
				<?php endif; ?>
			</div>
			<div class="rca-row" style="margin-top: 15px;">
				<div class="rca-col">
					<label>Terms Accepted?</label>
					<p><strong><?php echo $terms === 'yes' ? 'YES, Customer accepted all terms.' : 'NO / Not recorded'; ?></strong></p>
					<input type="hidden" name="rca_terms_accepted" value="<?php echo esc_attr( $terms ); ?>">
				</div>
			</div>

		</div>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['rca_booking_meta_nonce'] ) || ! wp_verify_nonce( $_POST['rca_booking_meta_nonce'], 'rca_save_booking_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'rca_vehicle_id',
			'rca_customer_name',
			'rca_customer_license',
			'rca_customer_address',
			'rca_customer_email',
			'rca_customer_phone',
			'rca_start_date',
			'rca_end_date',
			'rca_base_fee_weekly',
			'rca_insurance_option',
			'rca_booking_status',
			// Vehicle snapshot fields (read-only, but included for completeness)
			'rca_vehicle_make',
			'rca_vehicle_model',
			'rca_vehicle_year',
			'rca_vehicle_vin',
			'rca_vehicle_color',
			'rca_vehicle_plate',
			'rca_vehicle_weekly_rate_snapshot',
			'rca_vehicle_title_snapshot',
			'rca_vehicle_image_url_snapshot',
			'rca_vehicle_image_id_snapshot',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
		
		// Also update title for easier searching (format: RENTER-XXXX - VEHICLE NAME)
		$renter_id = get_post_meta( $post_id, '_rca_renter_id', true );
		$vehicle_title = get_post_meta( $post_id, '_rca_vehicle_title_snapshot', true );
		if ( empty( $renter_id ) ) {
			$renter_id = 'RENTER-0000';
		}
		if ( empty( $vehicle_title ) ) {
			$vehicle_title = 'Unknown Vehicle';
		}
		
		// Unhook to prevent infinite loop
		remove_action('save_post', array($this, 'save_meta'));
		wp_update_post( array(
			'ID' => $post_id,
			'post_title' => $renter_id . ' - ' . $vehicle_title
		));
		add_action('save_post', array($this, 'save_meta'));
	}

	public function set_columns( $columns ) {
		$columns['rca_status'] = 'Status';
		$columns['rca_vehicle'] = 'Vehicle';
		$columns['rca_dates'] = 'Dates';
		return $columns;
	}

	public function custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'rca_status':
				$status = get_post_meta( $post_id, '_rca_booking_status', true );
				// Default to 'pending' if status is empty or 'lead'
				if ( empty( $status ) || $status === 'lead' ) {
					$status = 'pending';
				}
				echo ucfirst( $status );
				break;
			case 'rca_vehicle':
				$vid = get_post_meta( $post_id, '_rca_vehicle_id', true );
				if ( $vid ) {
					echo get_the_title( $vid );
				} else {
					echo 'Unassigned';
				}
				break;
			case 'rca_dates':
				$start = get_post_meta( $post_id, '_rca_start_date', true );
				$end = get_post_meta( $post_id, '_rca_end_date', true );
				echo esc_html( $start . ' to ' . $end );
				break;
		}
	}
}

