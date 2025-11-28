<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_CPT_Vehicles {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Vehicles',
			'singular_name'      => 'Vehicle',
			'menu_name'          => 'Car Rental',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Vehicle',
			'edit_item'          => 'Edit Vehicle',
			'new_item'           => 'New Vehicle',
			'view_item'          => 'View Vehicle',
			'all_items'          => 'All Vehicles',
			'search_items'       => 'Search Vehicles',
			'not_found'          => 'No vehicles found.',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'vehicle' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-car',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'rental_vehicle', $args );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'rca_vehicle_details',
			'Vehicle Details',
			array( $this, 'render_meta_box' ),
			'rental_vehicle',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'rca_save_vehicle_meta', 'rca_vehicle_meta_nonce' );

		$make          = get_post_meta( $post->ID, '_rca_make', true );
		$model         = get_post_meta( $post->ID, '_rca_model', true );
		$year          = get_post_meta( $post->ID, '_rca_year', true );
		$color         = get_post_meta( $post->ID, '_rca_color', true );
		$vin           = get_post_meta( $post->ID, '_rca_vin', true );
		$plate         = get_post_meta( $post->ID, '_rca_plate', true );
		$daily_rate    = get_post_meta( $post->ID, '_rca_daily_rate', true );
		$weekly_rate   = get_post_meta( $post->ID, '_rca_weekly_rate', true );
		$deposit       = get_post_meta( $post->ID, '_rca_deposit', true );
		$insurance     = get_post_meta( $post->ID, '_rca_insurance_included', true );
		$status        = get_post_meta( $post->ID, '_rca_status', true );
		$notes         = get_post_meta( $post->ID, '_rca_notes', true );

		?>
		<style>
			.rca-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px; }
			.rca-col { flex: 1; min-width: 200px; }
			.rca-col label { display: block; font-weight: bold; margin-bottom: 5px; }
			.rca-col input, .rca-col select, .rca-col textarea { width: 100%; }
		</style>
		<div class="rca-meta-box">
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_year">Year</label>
					<input type="number" id="rca_year" name="rca_year" value="<?php echo esc_attr( $year ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_make">Make</label>
					<input type="text" id="rca_make" name="rca_make" value="<?php echo esc_attr( $make ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_model">Model</label>
					<input type="text" id="rca_model" name="rca_model" value="<?php echo esc_attr( $model ); ?>">
				</div>
			</div>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_color">Color</label>
					<input type="text" id="rca_color" name="rca_color" value="<?php echo esc_attr( $color ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_plate">License Plate</label>
					<input type="text" id="rca_plate" name="rca_plate" value="<?php echo esc_attr( $plate ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_vin">VIN</label>
					<input type="text" id="rca_vin" name="rca_vin" value="<?php echo esc_attr( $vin ); ?>">
				</div>
			</div>
			<hr>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_daily_rate">Daily Rate ($)</label>
					<input type="number" step="0.01" id="rca_daily_rate" name="rca_daily_rate" value="<?php echo esc_attr( $daily_rate ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_weekly_rate">Weekly Rate ($)</label>
					<input type="number" step="0.01" id="rca_weekly_rate" name="rca_weekly_rate" value="<?php echo esc_attr( $weekly_rate ); ?>">
				</div>
				<div class="rca-col">
					<label for="rca_deposit">Deposit Amount ($)</label>
					<input type="number" step="0.01" id="rca_deposit" name="rca_deposit" value="<?php echo esc_attr( $deposit ); ?>">
				</div>
			</div>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_insurance_included">Insurance Included?</label>
					<select id="rca_insurance_included" name="rca_insurance_included">
						<option value="no" <?php selected( $insurance, 'no' ); ?>>No</option>
						<option value="yes" <?php selected( $insurance, 'yes' ); ?>>Yes</option>
					</select>
				</div>
				<div class="rca-col">
					<label for="rca_status">Status</label>
					<select id="rca_status" name="rca_status">
						<option value="available" <?php selected( $status, 'available' ); ?>>Available</option>
						<option value="rented" <?php selected( $status, 'rented' ); ?>>Rented</option>
						<option value="maintenance" <?php selected( $status, 'maintenance' ); ?>>Maintenance</option>
					</select>
				</div>
			</div>
			<div class="rca-row">
				<div class="rca-col">
					<label for="rca_notes">Internal Notes</label>
					<textarea id="rca_notes" name="rca_notes" rows="3"><?php echo esc_textarea( $notes ); ?></textarea>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['rca_vehicle_meta_nonce'] ) || ! wp_verify_nonce( $_POST['rca_vehicle_meta_nonce'], 'rca_save_vehicle_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'rca_make',
			'rca_model',
			'rca_year',
			'rca_color',
			'rca_vin',
			'rca_plate',
			'rca_daily_rate',
			'rca_weekly_rate',
			'rca_deposit',
			'rca_insurance_included',
			'rca_status',
			'rca_notes',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}

