<?php
/**
 * Helper Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rca_get_currency_symbol() {
	return '$'; // Simplification for this scope
}

/**
 * Utility to format dates
 */
function rca_format_date( $date_string ) {
	if ( empty( $date_string ) ) return 'N/A';
	return date( 'F j, Y', strtotime( $date_string ) );
}

