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
		register_setting( 'rca_options_group', 'rca_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'rca_email_settings',
			'Email Settings',
			null,
			'rental_car_settings'
		);

		add_settings_field( 'rca_business_email', 'Email Address', array( $this, 'field_email' ), 'rental_car_settings', 'rca_email_settings', array( 'label_for' => 'business_email' ) );
		add_settings_field( 'rca_enable_emails', 'Enable Automated Emails', array( $this, 'field_checkbox' ), 'rental_car_settings', 'rca_email_settings', array( 'label_for' => 'enable_emails' ) );
	}

	public function sanitize_settings( $input ) {
		// Handle checkbox - if not set, set to 0
		if ( ! isset( $input['enable_emails'] ) ) {
			$input['enable_emails'] = '0';
		}
		return $input;
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
	public function field_email( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) ? $options[ $id ] : '';
		echo '<input type="email" id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
		echo '<p class="description">This is where booking emails will be sent. All booking notifications, including new lead submissions and completed booking forms, will be sent to this email address.</p>';
	}

	public function field_checkbox( $args ) {
		$options = get_option( 'rca_settings' );
		$id = $args['label_for'];
		$val = isset( $options[ $id ] ) && $options[ $id ] === '1' ? '1' : '0';
		echo '<label for="' . esc_attr( $id ) . '">';
		echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="rca_settings[' . esc_attr( $id ) . ']" value="1" ' . checked( $val, '1', false ) . '> ';
		echo 'Enable automated booking emails</label>';
		echo '<p class="description">When enabled, automated emails will be sent for new booking leads and completed booking forms.</p>';
	}
}

