<?php
/**
 * Plugin Name: Gravity Forms Iyzico
 * Description: A custom Gravity Forms payment add-on for Iyzico.
 * Version: 1.0
 * Author: Ugur Terzi
 * Text Domain: gravityforms-iyzico
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug logging helper function
 */
function gf_iyzico_debug_log( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( $message );
	}
}

// Load plugin translations
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'gravityforms-iyzico', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

// Debug log
gf_iyzico_debug_log( __( 'IYZICO: Ana eklenti dosyası yüklendi', 'gravityforms-iyzico' ) );

// Include Composer autoloader if present
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	gf_iyzico_debug_log( __( 'IYZICO: Composer autoloader yüklendi', 'gravityforms-iyzico' ) );
}

// Include core function logic
require_once plugin_dir_path( __FILE__ ) . 'includes/iyzipay-functions.php';
gf_iyzico_debug_log( __( 'IYZICO: Fonksiyon dosyası yüklendi', 'gravityforms-iyzico' ) );

// Hook into Gravity Forms
add_action( 'gform_loaded', array( 'GF_Iyzico_Bootstrap', 'load' ), 5 );

class GF_Iyzico_Bootstrap {

	private static $loaded = false;

	public static function load() {
		if ( self::$loaded ) {
			return;
		}

		gf_iyzico_debug_log( __( 'IYZICO: Bootstrap load() fonksiyonu çağrıldı', 'gravityforms-iyzico' ) );

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			gf_iyzico_debug_log( __( 'IYZICO: GFForms ödeme altyapısı bulunamadı', 'gravityforms-iyzico' ) );
			return;
		}

		$class_file = plugin_dir_path( __FILE__ ) . 'includes/class-gf-iyzico.php';
		if ( ! file_exists( $class_file ) ) {
			gf_iyzico_debug_log( __( 'IYZICO: Sınıf dosyası bulunamadı', 'gravityforms-iyzico' ) );
			return;
		}

		require_once $class_file;
		gf_iyzico_debug_log( __( 'IYZICO: Sınıf dosyası dahil edildi', 'gravityforms-iyzico' ) );

		if ( ! class_exists( 'GF_Iyzico' ) ) {
			gf_iyzico_debug_log( __( 'IYZICO: GF_Iyzico sınıfı dahil edildikten sonra bulunamadı', 'gravityforms-iyzico' ) );
			return;
		}

		GFAddOn::register( 'GF_Iyzico' );
		gf_iyzico_debug_log( __( 'IYZICO: GF_Iyzico, GFAddOn ile kayıt edildi', 'gravityforms-iyzico' ) );

		self::$loaded = true;
	}
}

// Register Turkish Lira (TRY) in Gravity Forms currencies when GF is available
add_action(
	'init',
	function () {
		if ( class_exists( 'GFCommon' ) ) {
			add_filter( 'gform_currencies', 'gf_iyzico_register_try_currency', 50 );
		}
	},
	20
);


/**
 * Registers Turkish Lira (TRY) for Gravity Forms if not already available.
 *
 * @param array $currencies Existing currencies.
 * @return array Modified currencies with TRY added.
 */
function gf_iyzico_register_try_currency( $currencies ) {
	if ( ! isset( $currencies['TRY'] ) ) {
		$currencies['TRY'] = array(
			'name'               => __( 'Türk Lirası', 'gravityforms-iyzico' ),
			'code'               => 'TRY',
			'symbol_left'        => '₺',
			'symbol_right'       => '',
			'symbol_padding'     => ' ',
			'thousand_separator' => '.',
			'decimal_separator'  => ',',
			'decimals'           => 2,
			'symbol_old'         => 'TL',
		);
	}

	return $currencies;
}


/**
 * Render payment confirmation shortcode with security.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output or redirect.
 */
function gf_iyzico_render_payment_confirmation( $atts ) {
	// Parse shortcode attributes with translatable defaults
	$atts = shortcode_atts(
		array(
			'default_title'   => __( 'Ödeme Onayı', 'gravityforms-iyzico' ),
			'default_message' => __( 'Bu sayfa ödeme sonucunu gösterir.', 'gravityforms-iyzico' ),
			'show_default'    => 'yes',
			'redirect_home'   => 'no',
			'redirect_delay'  => '5',
			'mask_email'      => 'yes',
			'require_token'   => 'yes',
			'expire_minutes'  => '60',
		),
		$atts,
		'iyzico_teyit'
	);

	// Get parameters from URL
	$data = array(
		'entry_id'   => isset( $_GET['entry_id'] ) ? intval( $_GET['entry_id'] ) : 0,
		'payment_id' => isset( $_GET['payment_id'] ) ? sanitize_text_field( $_GET['payment_id'] ) : '',
		'amount'     => isset( $_GET['amount'] ) ? floatval( $_GET['amount'] ) : 0,
		'status'     => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
		'token'      => isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '',
	);

	gf_iyzico_debug_log( 'IYZICO: Payment confirmation shortcode called with entry_id: ' . $data['entry_id'] . ', status: ' . $data['status'] );

	// Check if we have payment data
	$has_payment_data = ( $data['entry_id'] > 0 && ! empty( $data['status'] ) );

	// If no payment data, show default
	if ( ! $has_payment_data ) {
		gf_iyzico_debug_log( 'IYZICO: No payment data found, showing default content' );
		
		if ( $atts['redirect_home'] === 'yes' ) {
			return gf_iyzico_get_template(
				'redirect-home',
				array(
					'delay' => intval( $atts['redirect_delay'] ),
				)
			);
		}

		if ( $atts['show_default'] === 'no' ) {
			return '';
		}

		return gf_iyzico_get_template(
			'default-state',
			array(
				'title'   => $atts['default_title'],
				'message' => $atts['default_message'],
			)
		);
	}

	// Get entry
	$entry = GFAPI::get_entry( $data['entry_id'] );
	if ( is_wp_error( $entry ) ) {
		gf_iyzico_debug_log( 'IYZICO: Entry not found for ID: ' . $data['entry_id'] );
		return gf_iyzico_get_template(
			'error',
			array(
				'message' => __(
					'Kayıt bulunamadı.',
					'gravityforms-iyzico'
				),
			)
		);
	}

	gf_iyzico_debug_log( 'IYZICO: Entry found: ' . $data['entry_id'] . ', Payment Status: ' . rgar( $entry, 'payment_status' ) );

	// Security Check 1: Verify token if required
	if ( $atts['require_token'] === 'yes' ) {
		$expected_token = gf_iyzico_generate_entry_token( $entry );
		if ( empty( $data['token'] ) || $data['token'] !== $expected_token ) {
			gf_iyzico_debug_log( 'IYZICO: Invalid or missing security token' );
			return gf_iyzico_get_template(
				'error',
				array(
					'message' => __( 'Geçersiz veya eksik güvenlik anahtarı.', 'gravityforms-iyzico' ),
				)
			);
		}
		gf_iyzico_debug_log( 'IYZICO: Security token verified successfully' );
	}
	
	// ✅ FIXED: Check if token was used AFTER showing success page
	$token_key = 'iyzico_token_' . $data['token'];
	$token_status = get_transient( $token_key );
	
	if ( $token_status === 'used' ) {
		gf_iyzico_debug_log( 'IYZICO: Token already used, showing redirect page' );
		return gf_iyzico_get_template(
			'redirect-home',
			array(
				'delay' => intval( $atts['redirect_delay'] ),
			)
		);
	}

	// Security Check 2: Check if entry is too old
	if ( $atts['expire_minutes'] > 0 ) {
		$entry_date  = strtotime( $entry['date_created'] );
		$expire_time = $entry_date + ( $atts['expire_minutes'] * 60 );
		if ( time() > $expire_time ) {
			gf_iyzico_debug_log( 'IYZICO: Entry expired. Created: ' . $entry['date_created'] . ', Expire time: ' . date( 'Y-m-d H:i:s', $expire_time ) );
			return gf_iyzico_get_template(
				'error',
				array(
					'message' => __( 'Bu bağlantının süresi dolmuş.', 'gravityforms-iyzico' ),
				)
			);

		}
	}

	// Security Check 3: Verify payment status
	$stored_payment_status = rgar( $entry, 'payment_status' );
	if ( empty( $stored_payment_status ) || $stored_payment_status === 'Processing' ) {
		gf_iyzico_debug_log( 'IYZICO: Payment not processed yet. Status: ' . $stored_payment_status );
		return gf_iyzico_get_template(
			'error',
			array(
				'message' => __( 'Ödeme bilgisi henüz işlenmemiş.', 'gravityforms-iyzico' ),
			)
		);
	}

	// Security Check 4: Verify amount matches
	$stored_amount = rgar( $entry, 'payment_amount' );
	if ( $data['amount'] != $stored_amount ) {
		gf_iyzico_debug_log( 'IYZICO: Amount mismatch. URL: ' . $data['amount'] . ', Stored: ' . $stored_amount );
		return gf_iyzico_get_template(
			'error',
			array(
				'message' => __( 'Ödeme tutarı uyuşmuyor.', 'gravityforms-iyzico' ),
			)
		);

	}

	$form  = GFAPI::get_form( $entry['form_id'] );
	$addon = gf_iyzico();

	if ( ! $addon ) {
		gf_iyzico_debug_log( 'IYZICO: Addon instance not available' );
		return gf_iyzico_get_template(
			'error',
			array(
				'message' => __( 'Ödeme sistemi yüklenemedi.', 'gravityforms-iyzico' ),
			)
		);

	}

	// Get feed to retrieve customer field mappings
	$feed = $addon->get_payment_feed( $entry, $form );

	// Get customer information
	$customer_info             = iyzipay_extract_customer_info( $entry, $feed );
	$customer_info['fullName'] = trim( $customer_info['firstName'] . ' ' . $customer_info['lastName'] );

	// Mask sensitive data if enabled
	if ( $atts['mask_email'] === 'yes' ) {
		$customer_info['email'] = gf_iyzico_mask_email( $customer_info['email'] );
		$customer_info['phone'] = gf_iyzico_mask_phone( $customer_info['phone'] );
	}

    // Get payment details
    $payment_details = array(
        'amount'           => $stored_amount,
        'transaction_id'   => rgar( $entry, 'transaction_id', '' ),
        'status' => $stored_payment_status === 'Paid' ? __( 'Ödendi', 'gravityforms-iyzico' ) : 
           ($stored_payment_status === 'Failed' ? __( 'Başarısız', 'gravityforms-iyzico' ) : 
           $stored_payment_status),
        'date'             => rgar( $entry, 'payment_date', $entry['date_created'] ),
        'formatted_amount' => number_format( $stored_amount, 2, ',', '.' ) . ' ₺',
        'formatted_date'   => date_i18n( 'd F Y, H:i', strtotime( rgar( $entry, 'payment_date', $entry['date_created'] ) ) ),
    );
    
	// Prepare template data
	$template_data = array(
		'customer' => $customer_info,
		'payment'  => $payment_details,
		'entry'    => $entry,
		'form'     => $form,
		'settings' => $atts,
	);

	gf_iyzico_debug_log( 'IYZICO: All security checks passed, rendering template for status: ' . $payment_details['status'] );

	// Check payment status and render appropriate template
	if ( $payment_details['status'] === 'Failed' ) {
		return gf_iyzico_get_template( 'payment-failed', $template_data );
	}

	// Hook for additional processing
	do_action( 'gf_iyzico_shortcode_rendered', $data, $template_data );
    
    // ✅ FIXED: Mark token as used ONLY AFTER showing success page
    set_transient( $token_key, 'used', 12 * HOUR_IN_SECONDS );
	gf_iyzico_debug_log( 'IYZICO: Token marked as used after showing success page: ' . $data['token'] );
	
	// Render success template
	return gf_iyzico_get_template( 'payment-confirmation', $template_data );
}

// Register the shortcode
add_shortcode( 'iyzico_teyit', 'gf_iyzico_render_payment_confirmation' );

/**
 * Generate secure token for entry
 */


/**
 * Mask email address
 */
function gf_iyzico_mask_email( $email ) {
	if ( empty( $email ) ) {
		return '';
	}

	$parts = explode( '@', $email );
	if ( count( $parts ) !== 2 ) {
		return $email;
	}

	$name   = $parts[0];
	$domain = $parts[1];

	// Show first 2 and last 1 character of name
	if ( strlen( $name ) <= 3 ) {
		$masked_name = str_repeat( '*', strlen( $name ) );
	} else {
		$masked_name = substr( $name, 0, 2 ) . str_repeat( '*', strlen( $name ) - 3 ) . substr( $name, -1 );
	}

	// Show first character and TLD of domain
	$domain_parts = explode( '.', $domain );
	if ( count( $domain_parts ) >= 2 ) {
		$masked_domain = substr( $domain_parts[0], 0, 1 ) . str_repeat( '*', strlen( $domain_parts[0] ) - 1 ) . '.' . end( $domain_parts );
	} else {
		$masked_domain = $domain;
	}

	return $masked_name . '@' . $masked_domain;
}

/**
 * Mask phone number
 */
function gf_iyzico_mask_phone( $phone ) {
	if ( empty( $phone ) ) {
		return '';
	}

	// Remove all non-numeric characters
	$phone = preg_replace( '/[^0-9+]/', '', $phone );

	if ( strlen( $phone ) < 7 ) {
		return $phone;
	}

	// Show country code and last 4 digits
	if ( strpos( $phone, '+' ) === 0 ) {
		// Has country code
		$country_code_end = strpos( $phone, '90' ) !== false ? 3 : 2;
		$visible_start    = substr( $phone, 0, $country_code_end );
		$visible_end      = substr( $phone, -4 );
		$mask_length      = strlen( $phone ) - $country_code_end - 4;

		return $visible_start . str_repeat( '*', $mask_length ) . $visible_end;
	} else {
		// No country code
		return str_repeat( '*', strlen( $phone ) - 4 ) . substr( $phone, -4 );
	}
}

/**
 * Load template file
 */
function gf_iyzico_get_template( $template_name, $data = array() ) {
	// Extract data for use in template
	extract( $data );

	// Look for template in theme first
	$theme_template = get_stylesheet_directory() . '/gravityforms-iyzico/' . $template_name . '.php';

	// Then check plugin templates directory
	$plugin_template = plugin_dir_path( __FILE__ ) . 'templates/' . $template_name . '.php';

	// Use theme template if it exists, otherwise use plugin template
	$template_file = file_exists( $theme_template ) ? $theme_template : $plugin_template;

	if ( ! file_exists( $template_file ) ) {
		gf_iyzico_debug_log( 'IYZICO: Template not found: ' . $template_name . ' (looked in: ' . $theme_template . ', ' . $plugin_template . ')' );
		return '<div class="error">Template not found: ' . esc_html( $template_name ) . '</div>';
	}

	gf_iyzico_debug_log( 'IYZICO: Loading template: ' . $template_name . ' from: ' . $template_file );

	ob_start();
	include $template_file;
	return ob_get_clean();
}

add_action( 'wp_enqueue_scripts', 'gf_iyzico_enqueue_assets' );

function gf_iyzico_enqueue_assets() {
	global $post;

	$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'iyzico_teyit' );
	$has_params    = isset( $_GET['entry_id'] ) && isset( $_GET['status'] );

	if ( $has_shortcode || $has_params ) {
		gf_iyzico_debug_log( 'IYZICO: Enqueuing assets for payment confirmation' );
		
		// Enqueue CSS
		wp_enqueue_style(
			'gf-iyzico-payment-confirmation',
			plugin_dir_url( __FILE__ ) . 'assets/css/payment-confirmation.css',
			array(),
			'1.0.0'
		);

		// Prepare dynamic data for JS
		$delay    = 5; // Default, or fetch dynamically if possible
		$home_url = home_url();

		// Register and localize JS
		wp_register_script(
			'iyzico-redirect',
			plugin_dir_url( __FILE__ ) . 'assets/js/iyzico-redirect.js',
			array(),
			'1.0',
			true
		);
		wp_localize_script( 'iyzico-redirect', 'iyzicoRedirectDelay', $delay );
		wp_localize_script( 'iyzico-redirect', 'iyzicoRedirectUrl', esc_url( $home_url ) );

		// Enqueue JS
		wp_enqueue_script( 'iyzico-redirect' );
	}
}


/**
 * Filter to add secure payment confirmation link to notifications
 */
add_filter( 'gform_replace_merge_tags', 'gf_iyzico_add_secure_payment_link', 10, 7 );

function gf_iyzico_add_secure_payment_link( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
	// Only process for paid entries
	if ( rgar( $entry, 'payment_status' ) !== 'Paid' ) {
		return $text;
	}

	// Check if our merge tag exists
	if ( strpos( $text, '{payment_confirmation_link}' ) === false &&
		strpos( $text, '{secure_payment_link}' ) === false ) {
		return $text;
	}

	gf_iyzico_debug_log( 'IYZICO: Processing merge tags for entry: ' . $entry['id'] );

	// Get addon instance and feed
	$addon = gf_iyzico();
	if ( ! $addon ) {
		gf_iyzico_debug_log( 'IYZICO: Addon not available for merge tag processing' );
		return $text;
	}

	$feed             = $addon->get_payment_feed( $entry, $form );
	$redirect_page_id = rgars( $feed, 'meta/redirectPage' );

	if ( ! $redirect_page_id ) {
		gf_iyzico_debug_log( 'IYZICO: No redirect page configured in feed' );
		return str_replace( array( '{payment_confirmation_link}', '{secure_payment_link}' ), '', $text );
	}

	// Generate secure token
	$token = gf_iyzico_generate_entry_token( $entry );

	// Build secure URL
	$confirmation_url = add_query_arg(
		array(
			'entry_id'   => $entry['id'],
			'payment_id' => rgar( $entry, 'transaction_id' ),
			'amount'     => rgar( $entry, 'payment_amount' ),
			'status'     => 'success',
			'token'      => $token,
		),
		get_permalink( $redirect_page_id )
	);

	gf_iyzico_debug_log( 'IYZICO: Generated secure confirmation URL for entry: ' . $entry['id'] );

	// Create nice HTML link
	$link_html = sprintf(
		'<a href="%s" style="background-color: #1DC489; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">%s</a>',
		esc_url( $confirmation_url ),
		__( 'Ödeme Detaylarını Görüntüle', 'gravityforms-iyzico' )
	);

	// Replace merge tags
	$text = str_replace( '{payment_confirmation_link}', $confirmation_url, $text );
	$text = str_replace( '{secure_payment_link}', $format === 'html' ? $link_html : $confirmation_url, $text );

	return $text;
}

/**
 * Add merge tags to the list
 */
add_filter( 'gform_custom_merge_tags', 'gf_iyzico_add_merge_tags', 10, 4 );

function gf_iyzico_add_merge_tags( $merge_tags, $form_id, $fields, $element_id ) {
	$merge_tags[] = array(
		'label' => __( 'Güvenli Ödeme Onay Linki', 'gravityforms-iyzico' ),
		'tag'   => '{secure_payment_link}',
	);

	$merge_tags[] = array(
		'label' => __( 'Ödeme Onay URL', 'gravityforms-iyzico' ),
		'tag'   => '{payment_confirmation_link}',
	);

	return $merge_tags;
}

// Helper function to get addon instance
if ( ! function_exists( 'gf_iyzico' ) ) {
	function gf_iyzico() {
		return class_exists( 'GF_Iyzico' ) ? GF_Iyzico::get_instance() : false;
	}
}


// Debug helper - only for development
add_action(
	'init',
	function () {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( class_exists( 'GF_Iyzico' ) ) {
				$instance = gf_iyzico();
				if ( $instance ) {
					gf_iyzico_debug_log(
						sprintf(
							__( 'IYZICO: Addon instance available - Slug: %s', 'gravityforms-iyzico' ),
							$instance->get_slug()
						)
					);
				}
			}
		}
	},
	999
);