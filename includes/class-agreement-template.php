<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RCA_Agreement_Template {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_print_request' ), 1 );
		add_action( 'admin_menu', array( $this, 'register_hidden_page' ) );
	}

	public function check_print_request() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'rental_car_agreement' ) {
			if ( isset( $_GET['download'] ) && $_GET['download'] === '1' ) {
				if ( ! isset( $_GET['booking_id'] ) ) {
					wp_die( 'No booking ID provided.' );
				}

				$booking_id = intval( $_GET['booking_id'] );
				$booking = get_post( $booking_id );

				if ( ! $booking || $booking->post_type !== 'rental_booking' ) {
					wp_die( 'Invalid booking.' );
				}

				$this->download_pdf( $booking_id );
			} elseif ( isset( $_GET['print'] ) && $_GET['print'] === '1' ) {
				if ( ! isset( $_GET['booking_id'] ) ) {
					wp_die( 'No booking ID provided.' );
				}

				$booking_id = intval( $_GET['booking_id'] );
				$booking = get_post( $booking_id );

				if ( ! $booking || $booking->post_type !== 'rental_booking' ) {
					wp_die( 'Invalid booking.' );
				}

				$this->render_print_page( $booking_id );
			}
		}
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

		// Check if print is requested - render clean page without admin wrapper
		if ( isset( $_GET['print'] ) && $_GET['print'] === '1' ) {
			$this->render_print_page( $booking_id );
			return;
		}

		// Check if PDF is requested
		if ( isset( $_GET['pdf'] ) && $_GET['pdf'] === '1' ) {
			$this->generate_pdf( $booking_id );
			return;
		}

		// Gather Data - Use snapshot data from time of booking
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

		// Use vehicle snapshot data (from time of booking)
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

		// Get all initials data
		$all_initials = get_post_meta( $booking_id, '_rca_all_initials', true );
		if ( ! is_array( $all_initials ) ) {
			$all_initials = array();
		}

		$settings = get_option( 'rca_settings' );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Load Template
		include RCA_PLUGIN_DIR . 'templates/agreement-template.php';
	}

	public function render_print_page( $booking_id ) {
		// Gather Data - Use snapshot data from time of booking
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

		// Use vehicle snapshot data (from time of booking)
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

		// Get all initials data
		$all_initials = get_post_meta( $booking_id, '_rca_all_initials', true );
		if ( ! is_array( $all_initials ) ) {
			$all_initials = array();
		}

		$settings = get_option( 'rca_settings' );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Set print mode flag
		$print_mode = true;

		// Render clean HTML page without WordPress admin wrapper
		// Prevent WordPress from adding admin bar and other admin elements
		show_admin_bar( false );
		
		// Output the template directly
		include RCA_PLUGIN_DIR . 'templates/agreement-template.php';
		exit;
	}

	public function download_pdf( $booking_id ) {
		// Suppress all output and errors for clean PDF generation
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		
		error_reporting( 0 );
		ini_set( 'display_errors', 0 );
		@ini_set( 'output_buffering', 'Off' );
		
		// Prevent WordPress from outputting anything
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );
		remove_all_actions( 'admin_head' );
		remove_all_actions( 'admin_footer' );
		
		// Load TCPDF if available
		$tcpdf_path = RCA_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';
		if ( file_exists( $tcpdf_path ) ) {
			// Load autoconfig first to set up constants
			$autoconfig_path = RCA_PLUGIN_DIR . 'lib/tcpdf/tcpdf_autoconfig.php';
			if ( file_exists( $autoconfig_path ) ) {
				require_once $autoconfig_path;
			}
			require_once $tcpdf_path;
			$this->generate_pdf_with_tcpdf( $booking_id );
			return;
		}

		// Try to use DomPDF if available
		$dompdf_path = RCA_PLUGIN_DIR . 'vendor/dompdf/dompdf/autoload.inc.php';
		if ( file_exists( $dompdf_path ) ) {
			require_once $dompdf_path;
			$this->generate_pdf_with_dompdf( $booking_id );
			return;
		}

		// Fallback: Use simple HTML to PDF conversion via headers
		$this->generate_pdf_simple( $booking_id );
	}

	private function generate_pdf_simple( $booking_id ) {
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

		// Use a service or create a downloadable HTML file that browsers can convert
		// For now, output as HTML with proper headers to trigger download
		$filename = 'Car-Rental-Agreement-' . sanitize_file_name( $meta['renter_id'] ) . '.html';
		
		// Set headers for download
		header( 'Content-Type: text/html' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );
		
		echo $full_html;
		exit;
	}

	private function generate_pdf_with_tcpdf( $booking_id ) {
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
		
		// Set margins
		$pdf->SetMargins( 15, 15, 15 );
		$pdf->SetAutoPageBreak( true, 15 );
		
		// Set font to Times New Roman
		$pdf->SetFont( 'times', '', 11 );
		
		// Add a page
		$pdf->AddPage();
		
		// Convert HTML to PDF (TCPDF can handle basic HTML)
		// Note: TCPDF will use the font set above, but HTML font-family in styles will override
		// So we need to update the HTML styles to use 'times' font family
		$full_html = str_replace( "font-family: 'Times New Roman', Times, serif;", "font-family: 'times', 'Times New Roman', Times, serif;", $full_html );
		
		// Replace check mark HTML entities with DejaVu font which supports Unicode check marks
		// TCPDF's Times font doesn't support check mark Unicode, so we use DejaVu for check marks only
		$full_html = str_replace( '<span class="checkmark">&#10003;</span>', '<span style="font-family:dejavusans;font-size:12pt;font-weight:bold;color:#000;">✓</span>', $full_html );
		
		// Ensure UTF-8 encoding is properly handled
		$pdf->writeHTML( $full_html, true, false, true, false, '' );
		
		// Generate filename
		$filename = 'Car-Rental-Agreement-' . sanitize_file_name( $meta['renter_id'] ) . '.pdf';
		
		// Output PDF for download
		$pdf->Output( $filename, 'D' );
		exit;
	}

	private function generate_pdf_with_dompdf( $booking_id ) {
		// Implementation for DomPDF
		// This would be implemented if DomPDF is available
		wp_die( 'DomPDF not fully implemented. Please install a PDF library.' );
	}

	public function generate_pdf( $booking_id ) {
		// For now, redirect to print-friendly version
		// In a production environment, you'd use a PDF library like TCPDF or DomPDF
		wp_redirect( admin_url( 'admin.php?page=rental_car_agreement&booking_id=' . $booking_id . '&print=1' ) );
		exit;
	}
}

