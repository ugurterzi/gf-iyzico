<?php
/**
 * Iyzipay Functions for Gravity Forms Integration
 * Security and functionality improvements
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Iyzico API base URL based on mode
 */
function iyzipay_get_base_url( $mode ) {
	return $mode === 'production'
		? 'https://api.iyzipay.com'
		: 'https://sandbox-api.iyzipay.com';
}

/**
 * Generate signature for Iyzico API requests
 */
function iyzipay_generate_signature( $api_key, $secret_key, $token, $conversation_id ) {
	$random_string = $api_key . $conversation_id . $token;
	return base64_encode( hash_hmac( 'sha1', $random_string, $secret_key, true ) );
}

/**
 * Safely get entry data with proper error handling
 */
function iyzipay_get_entry_data( $entry_id ) {
	if ( ! is_numeric( $entry_id ) || $entry_id <= 0 ) {
		iyzipay_log_event( 'Invalid entry ID provided: ' . $entry_id, 'error' );
		return null;
	}

	$entry = GFAPI::get_entry( $entry_id );
	if ( is_wp_error( $entry ) ) {
		iyzipay_log_event( 'Failed to load entry ' . $entry_id . ': ' . $entry->get_error_message(), 'error' );
		return null;
	}

	iyzipay_log_event( 'Entry ' . $entry_id . ' loaded successfully' );
	return $entry;
}

/**
 * Log API responses with better error handling
 */
function iyzipay_log_response( $response, $context = '' ) {
	$prefix = ! empty( $context ) ? "[{$context}] " : '';

	if ( is_wp_error( $response ) ) {
		iyzipay_log_event( $prefix . 'WP Error: ' . $response->get_error_message(), 'error' );
	} else {
		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
		iyzipay_log_event( $prefix . "Response Code: {$code}, Body: " . substr( $body, 0, 500 ) );
	}
}

/**
 * Enhanced logging function with multiple fallbacks
 */
function iyzipay_log_event( $message, $level = 'info' ) {
	// Only log when WP_DEBUG is enabled
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$formatted_message = '[IYZICO] ' . $message;

	// Try GF Logging first
	if ( class_exists( 'GFLogging' ) ) {
		GFLogging::log_message( 'gravityforms-iyzico', $formatted_message, $level );
	}

	// Always log to error_log as backup
	error_log( $formatted_message );
}

/**
 * Extract customer info using feed mapping (improved version)
 */
function iyzipay_extract_customer_info( $entry, $feed ) {
	$customer = array(
		'email'     => '',
		'firstName' => '',
		'lastName'  => '',
		'phone'     => '',
		'identity'  => '',
		'address'   => '',
		'city'      => '',
		'zip'       => '',
	);

	if ( ! is_array( $entry ) || empty( $feed ) ) {
		iyzipay_log_event( 'Invalid entry or feed data provided to extract_customer_info', 'error' );
		return $customer;
	}

	// Get field mapping from feed
	$field_map = rgars( $feed, 'meta' );
	if ( ! $field_map ) {
		iyzipay_log_event( 'No field mapping found in feed', 'warning' );
		return $customer;
	}

	// Extract using feed mapping
	$mappings = array(
		'email'     => 'customerInformation_email',
		'firstName' => 'customerInformation_firstName',
		'lastName'  => 'customerInformation_lastName',
		'phone'     => 'customerInformation_phone',
		'identity'  => 'customerInformation_identity',
		'address'   => 'customerInformation_address',
		'city'      => 'customerInformation_city',
		'zip'       => 'customerInformation_zip',
	);

	foreach ( $mappings as $key => $mapping_key ) {
		$field_id = rgar( $field_map, $mapping_key );
		if ( $field_id ) {
			$value = rgar( $entry, $field_id );
			if ( ! empty( $value ) ) {
				$customer[ $key ] = sanitize_text_field( $value );
				iyzipay_log_event( "Mapped {$key} from field {$field_id}: " . substr( $value, 0, 50 ) );
			}
		}
	}

	// Fallback to hardcoded field IDs if mapping fails
	if ( empty( $customer['email'] ) && isset( $entry['2'] ) ) {
		$customer['email'] = sanitize_email( $entry['2'] );
		iyzipay_log_event( __( 'Fallback: Found email in field 2', 'gravityforms-iyzico' ) );
	}

	if ( empty( $customer['firstName'] ) && isset( $entry['1.3'] ) ) {
		$customer['firstName'] = sanitize_text_field( $entry['1.3'] );
		iyzipay_log_event( __( 'Fallback: Found firstName in field 1.3', 'gravityforms-iyzico' ) );
	}

	if ( empty( $customer['lastName'] ) && isset( $entry['1.6'] ) ) {
		$customer['lastName'] = sanitize_text_field( $entry['1.6'] );
		iyzipay_log_event( __( 'Fallback: Found lastName in field 1.6', 'gravityforms-iyzico' ) );
	}

	if ( empty( $customer['phone'] ) && isset( $entry['6'] ) ) {
		$customer['phone'] = sanitize_text_field( $entry['6'] );
		iyzipay_log_event( __( 'Fallback: Found phone in field 6', 'gravityforms-iyzico' ) );
	}

	return $customer;
}

/**
 * Safely get entry ID from request with validation
 */
function iyzipay_get_entry_id_from_request() {
	// Check GET first
	if ( isset( $_GET['entry_id'] ) && is_numeric( $_GET['entry_id'] ) ) {
		$entry_id = intval( $_GET['entry_id'] );
		if ( $entry_id > 0 ) {
			return $entry_id;
		}
	}

	// Check POST as fallback
	if ( isset( $_POST['entry_id'] ) && is_numeric( $_POST['entry_id'] ) ) {
		$entry_id = intval( $_POST['entry_id'] );
		if ( $entry_id > 0 ) {
			return $entry_id;
		}
	}

	iyzipay_log_event( __( 'No valid entry_id found in request', 'gravityforms-iyzico' ), 'warning' );
	return null;
}

/**
 * Validate phone number and format for Turkey
 */
function iyzipay_format_phone( $phone ) {
	if ( empty( $phone ) ) {
		return '+905555555555'; // Default fallback
	}

	// Remove all non-numeric characters except +
	$phone = preg_replace( '/[^0-9+]/', '', trim( $phone ) );

	// Already formatted correctly
	if ( preg_match( '/^\+90[0-9]{10}$/', $phone ) ) {
		return $phone;
	}

	// Remove + if exists for processing
	$digits = str_replace( '+', '', $phone );

	// Handle different formats
	if ( strlen( $digits ) >= 10 ) {
		if ( strpos( $digits, '90' ) === 0 && strlen( $digits ) === 12 ) {
			// Format: 905xxxxxxxxx
			return '+' . $digits;
		} elseif ( strpos( $digits, '0' ) === 0 && strlen( $digits ) === 11 ) {
			// Format: 05xxxxxxxxx
			return '+9' . $digits;
		} elseif ( strlen( $digits ) === 10 ) {
			// Format: 5xxxxxxxxx
			return '+90' . $digits;
		}
	}

	iyzipay_log_event(
		sprintf( __( 'Phone number format not recognized: %s, using default', 'gravityforms-iyzico' ), $phone ),
		'warning'
	);
	return '+905555555555';
}

/**
 * Validate email address
 */
function iyzipay_validate_email( $email ) {
	if ( empty( $email ) || ! is_email( $email ) ) {
		iyzipay_log_event(
			sprintf( __( 'Invalid email provided: %s, using default', 'gravityforms-iyzico' ), $email ),
			'warning'
		);
		return 'dummy@example.com';
	}
	return sanitize_email( $email );
}


/**
 * Security function to generate token (used by shortcode)
 */
function gf_iyzico_generate_entry_token( $entry ) {
	if ( ! $entry || ! isset( $entry['id'] ) ) {
		return false;
	}

	$secret = wp_salt( 'auth' );
	$data   = $entry['id'] . '|' . $entry['form_id'] . '|' . $entry['date_created'];
	return substr( hash_hmac( 'sha256', $data, $secret ), 0, 32 );
}

/**
 * Check if current request is for Iyzico callback
 */
function iyzipay_is_callback_request() {
	return ( isset( $_GET['iyzico_callback'] ) && $_GET['iyzico_callback'] == '1' ) ||
			( isset( $_POST['token'] ) && ! empty( $_POST['token'] ) );
}

/**
 * Get addon instance safely
 */
function iyzipay_get_addon_instance() {
	if ( function_exists( 'gf_iyzico' ) ) {
		return gf_iyzico();
	}

	if ( class_exists( 'GF_Iyzico' ) ) {
		return GF_Iyzico::get_instance();
	}

	return false;
}

/**
 * Debug function - only works when WP_DEBUG is enabled
 */
function iyzipay_debug( $data, $label = '' ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$message = ! empty( $label ) ? "[{$label}] " : '';
		
		if ( is_array( $data ) || is_object( $data ) ) {
			$message .= json_encode( $data );
		} else {
			$message .= (string) $data;
		}
		
		iyzipay_log_event( $message, 'debug' );
	}
}
