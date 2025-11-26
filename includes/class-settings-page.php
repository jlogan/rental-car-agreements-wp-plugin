<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Settings_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=rental_vehicle',
			'Rental Car Settings',
			'Settings',
			'manage_options',
			'rental_car_settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'rca_options_group', 'rca_settings' );

		add_settings_section(
			'rca_business_info',
			'Business Information',
			null,
			'rental_car_settings'
		);

		add_settings_field( 'rca_business_name', 'Business Name', array( $this, 'field_text' ), 'rental_car_settings', 'rca_business_info', array( 'label_for' => 'business_name' ) );
		add_settings_field( 'rca_business_address', 'Address', array( $this, 'field_textarea' ), 'rental_car_settings', 'rca_business_info', array( 'label_for' => 'business_address' ) );
		add_settings_field( 'rca_business_phone', 'Phone', array( $this, 'field_text' ), 'rental_car_settings', 'rca_business_info', array( 'label_for' => 'business_phone' ) );
		add_settings_field( 'rca_business_email', 'Email', array( $this, 'field_email' ), 'rental_car_settings', 'rca_business_info', array( 'label_for' => 'business_email' ) );

		add_settings_section(
			'rca_agreement_defaults',
			'Agreement Defaults',
			null,
			'rental_car_settings'
		);

		add_settings_field( 'rca_terms', 'Terms & Conditions', array( $this, 'field_textarea_large' ), 'rental_car_settings', 'rca_agreement_defaults', array( 'label_for' => 'terms' ) );
		add_settings_field( 'rca_insurance_text', 'Insurance Policy Text', array( $this, 'field_textarea' ), 'rental_car_settings', 'rca_agreement_defaults', array( 'label_for' => 'insurance_text' ) );
		add_settings_field( 'rca_footer_text', 'Footer Text', array( $this, 'field_text' ), 'rental_car_settings', 'rca_agreement_defaults', array( 'label_for' => 'footer_text' ) );
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Rental Car Agreement Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'rca_options_group' );
				do_settings_sections( 'rental_car_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	// Field Callbacks
	public function field_text( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) ? $options[ $id ] : '';
		echo '<input type="text" id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
	}

	public function field_email( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) ? $options[ $id ] : '';
		echo '<input type="email" id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
	}

	public function field_textarea( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) ? $options[ $id ] : '';
		echo '<textarea id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" rows="4" cols="50" class="large-text">' . esc_textarea( $val ) . '</textarea>';
	}

	public function field_textarea_large( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) ? $options[ $id ] : '';
		echo '<textarea id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" rows="10" cols="50" class="large-text">' . esc_textarea( $val ) . '</textarea>';
		echo '<p class="description">This text will appear on the printable rental agreement.</p>';
	}
}

