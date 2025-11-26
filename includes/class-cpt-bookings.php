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
			'hierarchical'       => false,
			'supports'           => array( 'title' ), // Title is auto-generated
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
	}

	public function render_actions_box( $post ) {
		$status = get_post_meta( $post->ID, '_rca_booking_status', true );
		?>
		<div class="rca-actions">
			<p>
				<strong>Current Status:</strong> 
				<span style="text-transform:uppercase; font-weight:bold;"><?php echo esc_html( $status ? $status : 'Pending' ); ?></span>
			</p>
			
			<div style="margin-top: 10px;">
				<a href="<?php echo admin_url( 'admin.php?page=rental_car_agreement&booking_id=' . $post->ID ); ?>" target="_blank" class="button button-primary button-large" style="width:100%; text-align:center; margin-bottom: 5px;">Print Agreement</a>
			</div>
		</div>
		<?php
	}

	public function render_details_box( $post ) {
		wp_nonce_field( 'rca_save_booking_meta', 'rca_booking_meta_nonce' );

		// Fetch Meta
		$vehicle_id   = get_post_meta( $post->ID, '_rca_vehicle_id', true );
		$fullname     = get_post_meta( $post->ID, '_rca_customer_name', true );
		$license      = get_post_meta( $post->ID, '_rca_customer_license', true );
		$address      = get_post_meta( $post->ID, '_rca_customer_address', true );
		$phone        = get_post_meta( $post->ID, '_rca_customer_phone', true );
		$email        = get_post_meta( $post->ID, '_rca_customer_email', true );
		$start_date   = get_post_meta( $post->ID, '_rca_start_date', true );
		$end_date     = get_post_meta( $post->ID, '_rca_end_date', true );
		$insurance    = get_post_meta( $post->ID, '_rca_insurance_option', true );
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
		</style>

		<div class="rca-meta-box">
			<h3>Booking Info</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_vehicle_id">Assigned Vehicle</label>
					<select id="rca_vehicle_id" name="rca_vehicle_id">
						<option value="">-- Select Vehicle --</option>
						<?php foreach ( $vehicles as $vehicle ) : ?>
							<option value="<?php echo $vehicle->ID; ?>" <?php selected( $vehicle_id, $vehicle->ID ); ?>>
								<?php echo esc_html( $vehicle->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
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
			
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_start_date">Start Date</label>
					<input type="date" id="rca_start_date" name="rca_start_date" value="<?php echo esc_attr( $start_date ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_end_date">End Date</label>
					<input type="date" id="rca_end_date" name="rca_end_date" value="<?php echo esc_attr( $end_date ); ?>">
				</div>
			</div>

			<hr>
			<h3>Customer Info</h3>
			<div class="rca-row">
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
			</div>

			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_customer_address">Address</label>
					<textarea id="rca_customer_address" name="rca_customer_address" rows="2"><?php echo esc_textarea( $address ); ?></textarea>
				</div>
			</div>

			<hr>
			<h3>Additional Options</h3>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_insurance_option">Insurance Selection</label>
					<select id="rca_insurance_option" name="rca_insurance_option">
						<option value="basic" <?php selected( $insurance, 'basic' ); ?>>Basic Coverage</option>
						<option value="premium" <?php selected( $insurance, 'premium' ); ?>>Premium Coverage</option>
						<option value="declined" <?php selected( $insurance, 'declined' ); ?>>Decline Coverage</option>
					</select>
				</div>
				<div class="rca-col">
					<label>Terms Accepted?</label>
					<p>
						<strong><?php echo $terms === 'yes' ? 'YES, Customer accepted terms.' : 'NO / Not recorded'; ?></strong>
					</p>
					<!-- Hidden field to preserve value if not editing -->
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
			'rca_insurance_option',
			'rca_booking_status',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
		
		// Also update title for easier searching
		$name = isset($_POST['rca_customer_name']) ? sanitize_text_field($_POST['rca_customer_name']) : 'New Booking';
		$date = isset($_POST['rca_start_date']) ? sanitize_text_field($_POST['rca_start_date']) : date('Y-m-d');
		
		// Unhook to prevent infinite loop
		remove_action('save_post', array($this, 'save_meta'));
		wp_update_post( array(
			'ID' => $post_id,
			'post_title' => $name . ' - ' . $date
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

