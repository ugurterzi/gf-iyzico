<?php
/**
 * Gravity Forms Iyzico Integration - Complete with Integrated Callback
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

GFForms::include_payment_addon_framework();
GFLogging::include_logger();

class GF_Iyzico extends GFPaymentAddOn {

	protected $_version                  = '1.0';
	protected $_min_gravityforms_version = '2.5';
	protected $_slug                     = 'gravityforms-iyzico';
	protected $_path                     = 'gravityforms-iyzico/gravityforms-iyzico.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Gravity Forms Iyzico Add-On';
	protected $_short_title              = 'Iyzico';

	protected $_supports_callbacks         = true;
	protected $_capabilities_settings_page = 'gravityforms_iyzico';
	protected $_capabilities_form_settings = 'gravityforms_iyzico';
	protected $_capabilities_uninstall     = 'gravityforms_iyzico_uninstall';
	protected $_is_payment_gateway         = true;
	protected $_requires_settings          = true;
	protected $_enable_rg_autoupgrade      = true;
	protected $_no_conflict_scripts        = array();

	// Singleton instance
	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	// === Override methods to prevent separate menu creation ===
	public function has_plugin_settings_page() {
		return true; }
	public function has_app_menu() {
		return false; }
	public function create_app_menu() {
		return; }
	public function create_plugin_page_menu( $menus ) {
		return $menus; }

	// === Init ===
    public function init() {
        parent::init();
        gf_iyzico_debug_log( 'IYZICO: init() method called' );
        add_action( 'template_redirect', array( $this, 'handle_iyzico_checkout' ) );
        }
	public function plugin_page() {
		echo '<div class="wrap">';
		echo '<h2>' . esc_html( sprintf( __( '%s Ayarları', 'gravityforms-iyzico' ), $this->get_short_title() ) ) . '</h2>';

		if ( $this->maybe_save_plugin_settings() ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Ayarlar başarıyla kaydedildi!', 'gravityforms-iyzico' ) . '</p></div>';
		}
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'gform_save_plugin_settings', 'gform_save_plugin_settings_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="api_key">API Key</label></th>
					<td>
						<input type="text" id="api_key" name="_gform_setting_api_key" value="<?php echo esc_attr( $this->get_plugin_setting( 'api_key' ) ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="secret_key">Secret Key</label></th>
					<td>
						<input type="text" id="secret_key" name="_gform_setting_secret_key" value="<?php echo esc_attr( $this->get_plugin_setting( 'secret_key' ) ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mode"><?php esc_html_e( 'Mode', 'gravityforms-iyzico' ); ?></label></th>
					<td>
						<label>
	<input type="radio" name="_gform_setting_mode" value="sandbox" <?php checked( $this->get_plugin_setting( 'mode' ), 'sandbox' ); ?> />
		<?php esc_html_e( 'Sandbox', 'gravityforms-iyzico' ); ?>
</label><br>
<label>
	<input type="radio" name="_gform_setting_mode" value="production" <?php checked( $this->get_plugin_setting( 'mode' ), 'production' ); ?> />
		<?php esc_html_e( 'Production', 'gravityforms-iyzico' ); ?>
</label>

					</td>
				</tr>
				<tr>
					<th scope="row"><label for="enable_redirect"><?php esc_html_e( 'Yönlendirmeyi Etkinleştir', 'gravityforms-iyzico' ); ?></label></th>
					<td>
						<label>
	<input type="checkbox" id="enable_redirect" name="_gform_setting_enable_redirect" value="1" <?php checked( $this->get_plugin_setting( 'enable_redirect' ), '1' ); ?> />
		<?php esc_html_e( 'Evet, formu gönderdikten sonra kullanıcıları Iyzico ödeme ekranına yönlendir.', 'gravityforms-iyzico' ); ?>
</label>

					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="gform_save_plugin_settings" class="button-primary" value="Save Settings" />
			</p>
		</form>
		<?php
		echo '</div>';
	}

	public function init_admin() {
		parent::init_admin();
		add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
	}
	public function init_frontend() {
		parent::init_frontend();
		add_filter( 'gform_confirmation', array( $this, 'redirect_to_iyzico_checkout' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'handle_callback' ) );
		error_log( __( 'IYZICO: template_redirect eylemi init_frontend() içinde eklendi.', 'gravityforms-iyzico' ) );
	}

	// === Callback Endpoint ===
	public function add_callback_endpoint() {
		add_rewrite_rule( '^iyzico-callback/?$', 'index.php?iyzico_callback=1', 'top' );
		if ( get_option( 'iyzico_rewrite_rules_flushed' ) != '1' ) {
			flush_rewrite_rules();
			update_option( 'iyzico_rewrite_rules_flushed', '1' );
		}
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'iyzico_callback';
		return $vars;
	}

    public function handle_callback() {
        gf_iyzico_debug_log( 'IYZICO: handle_callback() method called' );
        if ( isset( $_GET['iyzico_callback'] ) && $_GET['iyzico_callback'] == '1' ) {
            gf_iyzico_debug_log( __( 'IYZICO: Callback parametresi algılandı, process_callback() çağrılıyor.', 'gravityforms-iyzico' ) );
            $this->process_callback();
            exit;
        }
    }

    public function process_callback() {
        gf_iyzico_debug_log( 'IYZICO CALLBACK: process_callback() triggered' );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            gf_iyzico_debug_log( 'IYZICO CALLBACK: POST data: ' . json_encode( $_POST ) );
            gf_iyzico_debug_log( 'IYZICO CALLBACK: GET data: ' . json_encode( $_GET ) );
        }
    
        if ( ! isset( $_POST['token'] ) ) {
            gf_iyzico_debug_log( __( 'IYZICO CALLBACK: POST verisinde eksik token', 'gravityforms-iyzico' ) );
            wp_die( 'Missing token.' );
        }
    
        $iyzico_token = sanitize_text_field( $_POST['token'] );
        gf_iyzico_debug_log( __( 'IYZICO CALLBACK: Iyzico Token:', 'gravityforms-iyzico' ) . ' ' . $iyzico_token );
    
		// Get entry ID and security token from URL
		$entry_id       = isset( $_GET['entry_id'] ) ? intval( $_GET['entry_id'] ) : null;
		$security_token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

        if ( empty( $entry_id ) ) {
            gf_iyzico_debug_log( __( 'IYZICO CALLBACK: Missing entry_id in GET query!', 'gravityforms-iyzico' ) );
            wp_die( __( 'Entry ID not found in callback URL.', 'gravityforms-iyzico' ) );
        }
        
        $entry = GFAPI::get_entry( $entry_id );
        if ( is_wp_error( $entry ) ) {
            gf_iyzico_debug_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: ID\'si %s olan kayıt bulunamadı.', 'gravityforms-iyzico' ), $entry_id ) );
            wp_die( sprintf( __( 'Kayıt bulunamadı. Kayıt ID\'si: %s', 'gravityforms-iyzico' ), $entry_id ) );
        }
        
        // Security Check: Verify the security token
        $expected_token = $this->generate_entry_token( $entry );
        if ( empty( $security_token ) || $security_token !== $expected_token ) {
            gf_iyzico_debug_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Güvenlik anahtarı uyuşmazlığı. Beklenen: %1$s, Gelen: %2$s', 'gravityforms-iyzico' ), $expected_token, $security_token ) );
            wp_die( __( 'Geçersiz güvenlik anahtarı. Erişim reddedildi.', 'gravityforms-iyzico' ) );
        }
        
        // ✅ REMOVED: Token reuse check from callback - this should only be in shortcode
        gf_iyzico_debug_log( __( 'IYZICO GERİ ÇAĞIRIM: Güvenlik anahtarı başarıyla doğrulandı', 'gravityforms-iyzico' ) );

		$settings   = $this->get_plugin_settings();
		$api_key    = rgar( $settings, 'api_key' );
		$secret_key = rgar( $settings, 'secret_key' );
		$mode       = rgar( $settings, 'mode', 'sandbox' );

		$vendor_path = plugin_dir_path( __DIR__ ) . 'vendor/autoload.php';
		if ( ! file_exists( $vendor_path ) ) {
			wp_die( __( 'Iyzico SDK bulunamadı.', 'gravityforms-iyzico' ) );

		}
		require_once $vendor_path;

		$options = new \Iyzipay\Options();
		$options->setApiKey( $api_key );
		$options->setSecretKey( $secret_key );
		$options->setBaseUrl( $mode === 'sandbox' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com' );

		$request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
		$request->setLocale( \Iyzipay\Model\Locale::TR );
		$request->setToken( $iyzico_token );

		try {
			$checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve( $request, $options );

			error_log( 'IYZICO CALLBACK: Status: ' . $checkoutForm->getStatus() );
			error_log( 'IYZICO CALLBACK: Raw Result: ' . $checkoutForm->getRawResult() );

			if ( $checkoutForm->getStatus() === 'success' ) {
				$payment_id     = $checkoutForm->getPaymentId();
				$payment_status = $checkoutForm->getPaymentStatus();

				// IMPORTANT: Get the paid price (actual amount paid)
				$paid_price  = $checkoutForm->getPaidPrice();
				$price       = $checkoutForm->getPrice();
				$currency    = $checkoutForm->getCurrency();
				$installment = $checkoutForm->getInstallment();

				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Kayıt ID: %s', 'gravityforms-iyzico' ), $entry_id ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Ödeme ID: %s', 'gravityforms-iyzico' ), $payment_id ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Ödeme Durumu: %s', 'gravityforms-iyzico' ), $payment_status ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Fiyat: %s', 'gravityforms-iyzico' ), $price ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Ödenen Fiyat: %s', 'gravityforms-iyzico' ), $paid_price ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Para Birimi: %s', 'gravityforms-iyzico' ), $currency ) );
				error_log( sprintf( __( 'IYZICO GERİ ÇAĞIRIM: Taksit Sayısı: %s', 'gravityforms-iyzico' ), $installment ) );

				if ( $payment_status === 'SUCCESS' ) {
							// Create the action array with proper 'amount' key
							$action = array(
								'transaction_id' => $payment_id,
								'amount'         => $paid_price, // THIS IS THE KEY - must be 'amount', not 'payment_amount'
								'payment_date'   => gmdate( 'Y-m-d H:i:s' ),
								'currency'       => $currency ?: 'TRY',
								'payment_method' => 'Iyzico',
								'note'           => sprintf(
									/* translators: Payment confirmation message with amount, currency, and transaction ID */
									__( 'Ödeme onaylandı. Tutar: %1$s %2$s. İşlem kimliği: %3$s', 'gravityforms-iyzico' ),
									number_format( $paid_price, 2, ',', '.' ),
									$currency ?: 'TRY',
									$payment_id
								),
							);

							// Call complete_payment with the proper action array
							$this->complete_payment( $entry, $action );

							// Also update the payment amount meta directly to ensure it's saved
							GFAPI::update_entry_property( $entry_id, 'payment_amount', $paid_price );

							// Trigger post payment actions
							do_action( 'gform_post_payment_callback', $entry, $action, 'success' );
							do_action( 'gform_iyzico_post_payment_completed', $entry, $action );

							// Get form and feed for redirect
							$form             = GFAPI::get_form( $entry['form_id'] );
							$feed             = $this->get_payment_feed( $entry, $form );
							$redirect_page_id = rgars( $feed, 'meta/redirectPage' );

							// Use the same security token for redirect (already verified above)
							if ( $redirect_page_id ) {
								$redirect_url = add_query_arg(
									array(
										'entry_id'   => $entry_id,
										'payment_id' => $payment_id,
										'amount'     => $paid_price,
										'status'     => 'success',
										'token'      => $security_token, // Reuse the verified token
									),
									get_permalink( $redirect_page_id )
								);
							} else {
								$redirect_url = site_url();
							}
                            
                            // ✅ Don't mark token as used here - let shortcode handle it AFTER showing success page
                                gf_iyzico_debug_log( 'IYZICO CALLBACK: Redirecting to: ' . $redirect_url );
                                wp_redirect( $redirect_url );
                                exit;
				} else {
					// Payment failed
					$this->fail_payment(
						$entry,
						array(
							'type'           => 'fail_payment',
							'transaction_id' => $payment_id ?: '',
							'amount'         => $price,
							'note'           => sprintf(
							/* translators: Payment failure message with error details */
								__( 'Ödeme başarısız: %s', 'gravityforms-iyzico' ),
								$checkoutForm->getErrorMessage()
							),
						)
					);

					echo '<!DOCTYPE html><html lang="tr"><head><title>' . esc_html__( 'Ödeme Başarısız', 'gravityforms-iyzico' ) . '</title><meta charset="utf-8"></head><body>';
					echo '<h2>' . esc_html__( 'Ödeme Başarısız', 'gravityforms-iyzico' ) . '</h2>';
					echo '<p>' . esc_html__( 'Ödeme işlemi tamamlanamadı.', 'gravityforms-iyzico' ) . '</p>';
					echo '</body></html>';

				}
			} else {
				error_log(
					sprintf(
						__( 'IYZICO GERİ ÇAĞIRIM: Ödeme doğrulama başarısız: %s', 'gravityforms-iyzico' ),
						$checkoutForm->getErrorMessage()
					)
				);

				echo '<!DOCTYPE html><html lang="tr"><head><title>' . esc_html__( 'Ödeme Doğrulanamadı', 'gravityforms-iyzico' ) . '</title><meta charset="utf-8"></head><body>';
				echo '<h2>' . esc_html__( 'Ödeme Doğrulanamadı', 'gravityforms-iyzico' ) . '</h2>';
				echo '<p>' . esc_html( $checkoutForm->getErrorMessage() ) . '</p>';
				echo '</body></html>';

			}
		} catch ( Exception $e ) {
			error_log(
				sprintf(
					__( 'IYZICO GERİ ÇAĞIRIM: İstisna: %s', 'gravityforms-iyzico' ),
					$e->getMessage()
				)
			);

			echo '<!DOCTYPE html><html lang="tr"><head><title>' . esc_html__( 'Sistem Hatası', 'gravityforms-iyzico' ) . '</title><meta charset="utf-8"></head><body>';
			echo '<h2>' . esc_html__( 'Sistem Hatası', 'gravityforms-iyzico' ) . '</h2>';
			echo '<p>' . esc_html__( 'Ödeme doğrulanırken bir hata oluştu:', 'gravityforms-iyzico' ) . ' ' . esc_html( $e->getMessage() ) . '</p>';
			echo '</body></html>';

		}
		exit;
	}

	// Generate secure token for entry
	private function generate_entry_token( $entry ) {
		$secret = wp_salt( 'auth' );
		$data   = $entry['id'] . '|' . $entry['form_id'] . '|' . $entry['date_created'];
		return substr( hash_hmac( 'sha256', $data, $secret ), 0, 32 );
	}

	// === Tooltips ===
	public function tooltips( $tooltips ) {
		$tooltips['iyzico_feed_name'] = '<h6>' . esc_html__( 'Ad', 'gravityforms-iyzico' ) . '</h6>' .
		'<p>' . esc_html__( 'Bu kurulumu benzersiz şekilde tanımlamak için bir feed adı girin.', 'gravityforms-iyzico' ) . '</p>';
		return $tooltips;
	}
    
    /**
     * Get secure, translatable shortcode documentation HTML
     *
     * @return string Sanitized HTML documentation
     */
    private function get_shortcode_documentation_html() {
        $html = '<div style="background: #f0f6fc; border: 1px solid #c3dfff; border-radius: 6px; padding: 15px; margin-top: 10px;">';
        $html .= '<h4 style="margin-top: 0;">📋 ' . esc_html__( 'Kısa Kod Kullanımı', 'gravityforms-iyzico' ) . '</h4>';
        
        // Basic usage example
        $html .= '<p><code>[iyzico_teyit]</code> - ' . esc_html__( 'Varsayılan ayarlar', 'gravityforms-iyzico' ) . '</p>';
        
        // Advanced usage example  
        $html .= '<p><code>[iyzico_teyit mask_email="no" expire_minutes="120"]</code> - ' . esc_html__( 'Özelleştirilmiş ayarlar', 'gravityforms-iyzico' ) . '</p>';
        
        // Toggle button for quick reference
        $html .= '<div style="margin-top: 10px;">';
        $html .= '<button type="button" onclick="toggleIyzicoReference()" style="background: #0073aa; color: white; border: none; padding: 8px 12px; border-radius: 3px; cursor: pointer; font-weight: 600;">';
        $html .= '▼ ' . esc_html__( 'Hızlı Referans Tablosunu Göster/Gizle', 'gravityforms-iyzico' );
        $html .= '</button>';
        $html .= '</div>';
        
        // Hidden reference table
        $html .= '<div id="iyzico-reference-table" style="display: none; margin-top: 10px; background: white; border-radius: 3px; padding: 10px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr style="background: #f9f9f9;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . esc_html__( 'Seçenek', 'gravityforms-iyzico' ) . '</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . esc_html__( 'Varsayılan', 'gravityforms-iyzico' ) . '</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . esc_html__( 'Açıklama', 'gravityforms-iyzico' ) . '</th></tr>';
        
        // Table rows with options
        $options = array(
            'mask_email' => array(
                'default' => 'yes',
                'description' => __( 'E-posta adreslerini maskele', 'gravityforms-iyzico' )
            ),
            'expire_minutes' => array(
                'default' => '60',
                'description' => __( 'Link süresi (dakika)', 'gravityforms-iyzico' )
            ),
            'redirect_delay' => array(
                'default' => '5', 
                'description' => __( 'Yönlendirme gecikmesi (saniye)', 'gravityforms-iyzico' )
            ),
            'show_default' => array(
                'default' => 'yes',
                'description' => __( 'Varsayılan içerik göster', 'gravityforms-iyzico' )
            ),
            'require_token' => array(
                'default' => 'yes',
                'description' => __( 'Güvenlik anahtarı gerekli', 'gravityforms-iyzico' )
            ),
        );
        
        $row_count = 0;
        foreach ( $options as $option => $data ) {
            $bg_color = ($row_count % 2 == 0) ? '#fff' : '#f9f9f9';
            $html .= '<tr style="background: ' . $bg_color . ';">';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;"><code>' . esc_html( $option ) . '</code></td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">"' . esc_html( $data['default'] ) . '"</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html( $data['description'] ) . '</td>';
            $html .= '</tr>';
            $row_count++;
        }
        
        $html .= '</table>';
        $html .= '<p style="margin-bottom: 0; margin-top: 10px;"><strong>' . esc_html__( 'Örnek:', 'gravityforms-iyzico' ) . '</strong> <code>[iyzico_teyit mask_email="no" expire_minutes="30" redirect_delay="10"]</code></p>';
        $html .= '</div>';
        
        // JavaScript for toggle functionality
        $html .= '<script type="text/javascript">';
        $html .= 'function toggleIyzicoReference() {';
        $html .= '    var table = document.getElementById("iyzico-reference-table");';
        $html .= '    var button = event.target;';
        $html .= '    if (table.style.display === "none" || table.style.display === "") {';
        $html .= '        table.style.display = "block";';
        $html .= '        button.innerHTML = "▲ ' . esc_js( __( 'Hızlı Referans Tablosunu Göster/Gizle', 'gravityforms-iyzico' ) ) . '";';
        $html .= '    } else {';
        $html .= '        table.style.display = "none";';
        $html .= '        button.innerHTML = "▼ ' . esc_js( __( 'Hızlı Referans Tablosunu Göster/Gizle', 'gravityforms-iyzico' ) ) . '";';
        $html .= '    }';
        $html .= '}';
        $html .= '</script>';
        
        $html .= '</div>';
        
        return $html;
    }
    
	// === Feed Settings and Payment Logic ===
	public function feed_settings_fields() {
		return $this->get_feed_settings_fields(); }
	public function can_create_feed() {
		return $this->is_configured(); }
	public function is_configured() {
		$settings = $this->get_plugin_settings();
		return ! empty( $settings['api_key'] ) && ! empty( $settings['secret_key'] );
	}
	public function supported_billing_intervals() {
		return array(
			'day'   => array(
				'label' => esc_html__( 'gün', 'gravityforms-iyzico' ),
				'min'   => 1,
				'max'   => 365,
			),
			'week'  => array(
				'label' => esc_html__( 'hafta', 'gravityforms-iyzico' ),
				'min'   => 1,
				'max'   => 52,
			),
			'month' => array(
				'label' => esc_html__( 'ay', 'gravityforms-iyzico' ),
				'min'   => 1,
				'max'   => 12,
			),
			'year'  => array(
				'label' => esc_html__( 'yıl', 'gravityforms-iyzico' ),
				'min'   => 1,
				'max'   => 5,
			),
		);
	}

	public function get_payment_amount( $feed, $form, $entry ) {
		$payment_field = rgars( $feed, 'meta/paymentAmount' );
		if ( $payment_field == 'form_total' ) {
			return GFCommon::get_order_total( $form, $entry );
		} else {
			return rgar( $entry, $payment_field );
		}
	}
	public function redirect_to_iyzico_checkout( $confirmation, $form, $entry, $ajax ) {
		error_log( sprintf( __( 'IYZICO YÖNLENDİRME: Form ID çağrıldı: %s', 'gravityforms-iyzico' ), $form['id'] ) );
		error_log( sprintf( __( 'IYZICO YÖNLENDİRME: Kayıt ID: %s', 'gravityforms-iyzico' ), rgar( $entry, 'id' ) ) );

		$enable_redirect_setting = $this->get_plugin_setting( 'enable_redirect' );
		error_log( sprintf( __( 'IYZICO YÖNLENDİRME: Yönlendirme ayarı: %s', 'gravityforms-iyzico' ), $enable_redirect_setting ) );

		if ( $enable_redirect_setting !== '1' ) {
			error_log( __( 'IYZICO YÖNLENDİRME: Yönlendirme ayarlarıyla devre dışı bırakıldı', 'gravityforms-iyzico' ) );
			return $confirmation;
		}

		$has_feed = $this->has_feed( $form['id'] );
		error_log(
			sprintf(
				__( 'IYZICO YÖNLENDİRME: Feed var mı: %s', 'gravityforms-iyzico' ),
				$has_feed ? __( 'EVET', 'gravityforms-iyzico' ) : __( 'HAYIR', 'gravityforms-iyzico' )
			)
		);

		if ( $has_feed ) {
			$feeds = $this->get_feeds( $form['id'] );
			error_log(
				sprintf(
					__( 'IYZICO YÖNLENDİRME: Bulunan feed sayısı: %d', 'gravityforms-iyzico' ),
					count( $feeds )
				)
			);

			$redirect_url = add_query_arg(
				array(
					'iyzico_checkout' => '1',
					'entry_id'        => rgar( $entry, 'id' ),
				),
				site_url( '/' )
			);
			error_log(
				sprintf(
					__( 'IYZICO YÖNLENDİRME: Yönlendirme URL\'si: %s', 'gravityforms-iyzico' ),
					$redirect_url
				)
			);

			return array( 'redirect' => $redirect_url );
		}
		error_log( __( 'IYZICO YÖNLENDİRME: Uygun Iyzico feedi bulunamadı', 'gravityforms-iyzico' ) );

		return $confirmation;
	}
	public function get_payment_feed( $entry, $form = false ) {
		if ( ! $form ) {
			$form = GFAPI::get_form( $entry['form_id'] ); }
		$feeds = $this->get_feeds( $form['id'] );
		if ( empty( $feeds ) ) {
			error_log(
				sprintf(
					__( 'IYZICO: %s form ID için feed bulunamadı', 'gravityforms-iyzico' ),
					$form['id']
				)
			);

			return false;
		}
		foreach ( $feeds as $feed ) {
			if ( rgar( $feed, 'is_active' ) != '1' ) {
				error_log(
					sprintf(
						__( 'IYZICO: %s ID\'li feed aktif değil', 'gravityforms-iyzico' ),
						$feed['id']
					)
				);

				continue;
			}
			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				error_log(
					sprintf(
						__( 'IYZICO: %s ID\'li feed kullanılıyor', 'gravityforms-iyzico' ),
						$feed['id']
					)
				);

				return $feed;
			}
		}
		error_log( __( 'IYZICO: Hiçbir feed koşulu karşılanmadı', 'gravityforms-iyzico' ) );

		return false;
	}
	public function process_feed( $feed, $entry, $form ) {
		if ( $this->_is_payment_gateway && $this->has_feed( $form['id'] ) ) {
			$submission_data = array();
			return $this->authorize( $feed, $submission_data, $form, $entry );
		}
		return array();
	}
	public function authorize( $feed, $submission_data, $form, $entry ) {
		$payment_amount = $this->get_payment_amount( $feed, $form, $entry );
		if ( $payment_amount == 0 ) {
			$this->log_debug(
				sprintf(
					__( '%s(): Ödeme tutarı 0, işlenmiyor.', 'gravityforms-iyzico' ),
					__METHOD__
				)
			);
			return array();
		}
		return array(
			'is_authorized'  => true,
			'transaction_id' => uniqid(),
			'amount'         => $payment_amount,
			'payment_method' => 'Iyzico',
		);
	}

	// === Checkout Handler ===
	public function handle_iyzico_checkout() {
		if ( ! $this->validate_checkout_request() ) {
			return;
		}

		$entry_id = intval( $_GET['entry_id'] );
		list($entry, $form, $feed) = $this->get_entry_and_feed($entry_id);
		$payment_amount = $this->get_payment_amount( $feed, $form, $entry );
		$buyer = $this->get_customer_info( $feed, $entry );
		$settings = $this->get_plugin_settings();
		$api_key = rgar( $settings, 'api_key' );
		$secret_key = rgar( $settings, 'secret_key' );
		$mode = rgar( $settings, 'mode', 'sandbox' );
		$options = $this->get_api_options($api_key, $secret_key, $mode);
		$security_token = $this->generate_entry_token( $entry );
		$request = $this->build_checkout_request($entry, $feed, $payment_amount, $security_token, $buyer, $settings);
		$this->add_addresses_to_request($request, $buyer);
		$this->add_basket_items_to_request($request, number_format( (float) $payment_amount, 2, '.', '' ));
		error_log( 'IYZICO CHECKOUT: Request data: ' . json_encode( $request->getJsonObject() ) );
		try {
			$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create( $request, $options );
			$status = $checkoutFormInitialize->getStatus();
			$token = $checkoutFormInitialize->getToken();
			$errorMessage = $checkoutFormInitialize->getErrorMessage();
			error_log( 'IYZICO CHECKOUT: Response Status: ' . $status );
			error_log( 'IYZICO CHECKOUT: Error Message: ' . $errorMessage );
			error_log( 'IYZICO CHECKOUT: Token: ' . $token );
			if ( $status === 'success' && $token ) {
				gform_update_meta( $entry_id, 'iyzico_token', $token );
				$checkoutFormContent = $checkoutFormInitialize->getCheckoutFormContent();
				$this->render_checkout_form($checkoutFormContent);
			} else {
				$this->handle_checkout_error($errorMessage);
			}
		} catch ( \Exception $e ) {
			error_log( sprintf( __( 'IYZICO ÖDEME: İstisna oluştu: %s', 'gravityforms-iyzico' ), $e->getMessage() ) );
			error_log( sprintf( __( 'IYZICO ÖDEME: Stack trace: %s', 'gravityforms-iyzico' ), $e->getTraceAsString() ) );
			wp_die( __( 'Ödeme sistemi hatası: ', 'gravityforms-iyzico' ) . esc_html( $e->getMessage() ) );
		}
	}

	private function validate_checkout_request() {
		if ( ! isset( $_GET['iyzico_checkout'] ) || $_GET['iyzico_checkout'] != '1' ) {
			return false;
		}
		if ( ! isset( $_GET['entry_id'] ) || ! intval( $_GET['entry_id'] ) ) {
			wp_die( 'Invalid entry ID' );
		}
		return true;
	}

	private function get_entry_and_feed($entry_id) {
		error_log( sprintf( __( 'IYZICO ÖDEME: %s ID\'li kayıt için ödeme başlatıldı', 'gravityforms-iyzico' ), $entry_id ) );
		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			wp_die( 'Invalid entry' );
		}
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_payment_feed( $entry, $form );
		if ( ! $feed ) {
			wp_die( __( 'Ödeme feedi bulunamadı', 'gravityforms-iyzico' ) );
		}
		return array($entry, $form, $feed);
	}

	private function get_api_options($api_key, $secret_key, $mode) {
		$vendor_path = plugin_dir_path( __DIR__ ) . 'vendor/autoload.php';
		if ( ! file_exists( $vendor_path ) ) {
			error_log( sprintf( __( 'IYZICO ÖDEME: Vendor autoload bulunamadı: %s', 'gravityforms-iyzico' ), $vendor_path ) );
			wp_die( __( 'Iyzico SDK bulunamadı. Lütfen yönetici ile iletişime geçin.', 'gravityforms-iyzico' ) );
		}
		require_once $vendor_path;
		$options = new \Iyzipay\Options();
		$options->setApiKey( $api_key );
		$options->setSecretKey( $secret_key );
		$options->setBaseUrl( $mode === 'sandbox' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com' );
		error_log( sprintf( __( 'IYZICO ÖDEME: Mod: %s', 'gravityforms-iyzico' ), $mode ) );
		error_log( sprintf( __( 'IYZICO ÖDEME: API Anahtarı: %s...', 'gravityforms-iyzico' ), substr( $api_key, 0, 10 ) ) );
		return $options;
	}

	private function build_checkout_request($entry, $feed, $payment_amount, $security_token, $buyer, $settings) {
		$entry_id = $entry['id'];
		$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
		$request->setLocale( \Iyzipay\Model\Locale::TR );
		$request->setConversationId( strval( $entry_id ) );
		$formatted_price = number_format( (float) $payment_amount, 2, '.', '' );
		$request->setPrice( $formatted_price );
		$request->setPaidPrice( $formatted_price );
		$request->setCurrency( \Iyzipay\Model\Currency::TL );
		$request->setBasketId( 'B' . $entry_id );
		$request->setPaymentGroup( \Iyzipay\Model\PaymentGroup::PRODUCT );
		$callback_url = site_url( '?iyzico_callback=1&entry_id=' . $entry_id . '&token=' . $security_token );
		$request->setCallbackUrl( $callback_url );
		$request->setEnabledInstallments( array( 1 ) );
		$buyer->setId( 'BY' . $entry_id );
		$request->setBuyer( $buyer );
		error_log( sprintf( __( 'IYZICO ÖDEME: Güvenlik anahtarlı geri dönüş URL\'si: %s', 'gravityforms-iyzico' ), $callback_url ) );
		return $request;
	}

	private function add_addresses_to_request($request, $buyer) {
		foreach ( array( 'Shipping', 'Billing' ) as $type ) {
			$address = new \Iyzipay\Model\Address();
			$address->setContactName( $buyer->getName() . ' ' . $buyer->getSurname() );
			$address->setCity( $buyer->getCity() );
			$address->setCountry( $buyer->getCountry() );
			$address->setAddress( $buyer->getRegistrationAddress() );
			$address->setZipCode( $buyer->getZipCode() );
			$setter = "set{$type}Address";
			$request->$setter( $address );
		}
	}

	private function add_basket_items_to_request($request, $formatted_price) {
		$basketItems = array();
		$firstBasketItem = new \Iyzipay\Model\BasketItem();
		$firstBasketItem->setId( 'BI101' );
		$firstBasketItem->setName( 'Birlestirici' );
		$firstBasketItem->setCategory1( 'Collectibles' );
		$firstBasketItem->setCategory2( 'Accessories' );
		$firstBasketItem->setItemType( \Iyzipay\Model\BasketItemType::PHYSICAL );
		$firstBasketItem->setPrice( $formatted_price );
		$basketItems[0] = $firstBasketItem;
		$request->setBasketItems( $basketItems );
	}

	private function render_checkout_form($checkoutFormContent) {
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( substr( get_locale(), 0, 2 ) ); ?>">
		<head>
			<title><?php echo esc_html__( 'Ödeme İşlemi', 'gravityforms-iyzico' ); ?></title>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		</head>
		<body>
			<div id="iyzipay-checkout-form" class="responsive"></div>
			<?php echo $checkoutFormContent; ?>
		</body>
		</html>
		<?php
		exit;
	}

	private function handle_checkout_error($errorMessage) {
		error_log( __( 'IYZICO ÖDEME: Ödeme formu başlatılamadı', 'gravityforms-iyzico' ) );
		$error_msg = $errorMessage ?: __( 'Ödeme başlatılamadı', 'gravityforms-iyzico' );
		wp_die( __( 'Ödeme başlatılamadı: ', 'gravityforms-iyzico' ) . esc_html( $error_msg ) );
	}

	// === Customer Info Helper ===
	public function get_customer_info( $feed, $entry ) {
		$first_name_id = rgar( $feed['meta'], 'customerInformation_firstName' );
		$last_name_id  = rgar( $feed['meta'], 'customerInformation_lastName' );
		$email_id      = rgar( $feed['meta'], 'customerInformation_email' );
		$phone_id      = rgar( $feed['meta'], 'customerInformation_phone' );
		$identity_id   = rgar( $feed['meta'], 'customerInformation_identity' );
		$address_id    = rgar( $feed['meta'], 'customerInformation_address' );
		$city_id       = rgar( $feed['meta'], 'customerInformation_city' );
		$zip_id        = rgar( $feed['meta'], 'customerInformation_zip' );

		$first_name = rgar( $entry, $first_name_id );
		$last_name  = rgar( $entry, $last_name_id );
		$email      = rgar( $entry, $email_id );
		$phone      = rgar( $entry, $phone_id );
		$identity   = rgar( $entry, $identity_id );
		$address    = rgar( $entry, $address_id );
		$city       = rgar( $entry, $city_id );
		$zip        = rgar( $entry, $zip_id );

		// === Logging for Debug ===
		error_log(
			sprintf(
				__( 'IYZICO: Müşteri verileri - Ad: %1$s | Soyad: %2$s | Telefon: %3$s | E-posta: %4$s', 'gravityforms-iyzico' ),
				$first_name,
				$last_name,
				$phone,
				$email
			)
		);

		// === Fallbacks ===
		if ( empty( $first_name ) ) {
			$first_name = __( 'İsim', 'gravityforms-iyzico' );
		}
		if ( empty( $last_name ) ) {
			$last_name = __( 'Soyisim', 'gravityforms-iyzico' );
		}
		if ( empty( $email ) ) {
			$email = 'dummy@example.com';
		}
		if ( empty( $phone ) ) {
			$phone = '+905555555555';
		}
		if ( empty( $identity ) ) {
			$identity = '74300864791';
		}
		if ( empty( $address ) ) {
			$address = __( 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1', 'gravityforms-iyzico' );
		}
		if ( empty( $city ) ) {
			$city = __( 'Istanbul', 'gravityforms-iyzico' );
		}
		if ( empty( $zip ) ) {
			$zip = '34732';
		}

		// === Normalize phone ===
		$phone = trim( $phone );
		if ( strpos( $phone, '+' ) !== 0 ) {
			$digits = preg_replace( '/\D+/', '', $phone );
			if ( strpos( $digits, '90' ) === 0 ) {
				$phone = '+' . $digits;
			} elseif ( strpos( $digits, '0' ) === 0 ) {
				$phone = '+9' . $digits;
			} else {
				$phone = '+90' . $digits;
			}
		}

		$buyer = new \Iyzipay\Model\Buyer();
		$buyer->setId( 'BY' . rand( 10000, 99999 ) );
		$buyer->setName( $first_name );
		$buyer->setSurname( $last_name );
		$buyer->setGsmNumber( $phone );
		$buyer->setEmail( $email );
		$buyer->setIdentityNumber( $identity );
		$buyer->setLastLoginDate( '2015-10-05 12:43:35' );
		$buyer->setRegistrationDate( '2013-04-21 15:12:09' );
		$buyer->setRegistrationAddress( $address );
		$buyer->setIp( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' );
		$buyer->setCity( $city );
		$buyer->setCountry( 'Turkey' );
		$buyer->setZipCode( $zip );

		return $buyer;
	}


	// === Settings ===
	public function plugin_settings_fields() {
		return $this->get_settings_fields(); }
	public function get_settings_fields() {
		return array(
			array(
				'title'  => __( 'Iyzico Ayarları', 'gravityforms-iyzico' ),
				'fields' => array(
					array(
						'label'    => __( 'API Key', 'gravityforms-iyzico' ),
						'type'     => 'text',
						'name'     => 'api_key',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'    => __( 'Secret Key', 'gravityforms-iyzico' ),
						'type'     => 'text',
						'name'     => 'secret_key',
						'class'    => 'medium',
						'required' => true,
					),
					array(
						'label'         => __( 'Mode', 'gravityforms-iyzico' ),
						'type'          => 'radio',
						'name'          => 'mode',
						'choices'       => array(
							array(
								'label' => __( 'Sandbox', 'gravityforms-iyzico' ),
								'value' => 'sandbox',
							),
							array(
								'label' => __( 'Production', 'gravityforms-iyzico' ),
								'value' => 'production',
							),
						),
						'horizontal'    => true,
						'default_value' => 'sandbox',
					),
					array(
						'label'   => __( 'Iyzico Ödeme Sayfasına Yönlendirmeyi Etkinleştir', 'gravityforms-iyzico' ),
						'type'    => 'checkbox',
						'name'    => 'enable_redirect',
						'choices' => array(
							array(
								'label' => __( 'Evet, form gönderildikten sonra kullanıcıları Iyzico ödeme ekranına yönlendir.', 'gravityforms-iyzico' ),
								'name'  => 'enable_redirect',
							),
						),
					),
				),
			),
		);
	}

	public function get_feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Iyzico Feed Settings', 'gravityforms-iyzico' ),
				'fields' => array(
					array(
						'label'    => esc_html__( 'Ad', 'gravityforms-iyzico' ),
						'type'     => 'text',
						'name'     => 'feedName',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Ad', 'gravityforms-iyzico' ) . '</h6><p>' . esc_html__( 'Bu kurulumu benzersiz şekilde tanımlamak için bir feed adı girin.', 'gravityforms-iyzico' ) . '</p>',

					),
					array(
						'name'          => 'transactionType',
						'label'         => esc_html__( 'İşlem Türü', 'gravityforms-iyzico' ),
						'type'          => 'select',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Ürünler ve Hizmetler', 'gravityforms-iyzico' ),
								'value' => 'product',
							),
						),
						'default_value' => 'product',
					),

					array(
						'name'          => 'paymentAmount',
						'label'         => esc_html__( 'Ödeme Tutarı', 'gravityforms-iyzico' ),
						'type'          => 'select',
						'choices'       => $this->get_payment_choices(),
						'required'      => true,
						'default_value' => 'form_total',

					),
				),
			),
			array(
				'title'  => esc_html__( 'Müşteri Bilgileri', 'gravityforms-iyzico' ),
				'fields' => array(
					array(
						'name'      => 'customerInformation',
						'label'     => esc_html__( 'Müşteri Bilgileri', 'gravityforms-iyzico' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'     => 'email',
								'label'    => esc_html__( 'E-posta', 'gravityforms-iyzico' ),
								'required' => true,
							),
							array(
								'name'     => 'firstName',
								'label'    => esc_html__( 'Ad', 'gravityforms-iyzico' ),
								'required' => true,
							),
							array(
								'name'     => 'lastName',
								'label'    => esc_html__( 'Soyad', 'gravityforms-iyzico' ),
								'required' => true,
							),
							array(
								'name'  => 'phone',
								'label' => esc_html__( 'Telefon', 'gravityforms-iyzico' ),
							),
							array(
								'name'  => 'identity',
								'label' => esc_html__( 'Kimlik Numarası', 'gravityforms-iyzico' ),
							),
							array(
								'name'  => 'address',
								'label' => esc_html__( 'Adres', 'gravityforms-iyzico' ),
							),
							array(
								'name'  => 'city',
								'label' => esc_html__( 'Şehir', 'gravityforms-iyzico' ),
							),
							array(
								'name'  => 'zip',
								'label' => esc_html__( 'Posta Kodu', 'gravityforms-iyzico' ),
							),

						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Other Settings', 'gravityforms-iyzico' ),
				'fields' => array(
					array(
						'name'    => 'redirectPage',
						'label'   => esc_html__( 'Ödemeden Sonra Yönlendirilecek Sayfa', 'gravityforms-iyzico' ),
						'type'    => 'select',
						'choices' => $this->get_pages_as_choices(),
						'tooltip' => esc_html__( 'Başarılı ödeme sonrası kullanıcıların yönlendirileceği sayfayı seçin. Lütfen bu sayfaya [iyzico_teyit] kısa kodunu eklemeyi unutmayın.', 'gravityforms-iyzico' ),
					),
					array(
                        'label'       => esc_html__( 'Kısa Kod Kullanımı', 'gravityforms-iyzico' ),
                        'type'        => 'text',
                        'name'        => 'shortcode_info_dummy',
                        'description' => $this->get_shortcode_documentation_html(),
                        'style'       => 'display: none !important;',
                        'class'       => 'hidden',
                    ),
					array(
						'name'           => 'conditionalLogic',
						'label'          => esc_html__( 'Koşullu Mantık', 'gravityforms-iyzico' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Koşullu Mantığı Etkinleştir', 'gravityforms-iyzico' ),
						'instructions'   => esc_html__( 'Bu feed\'i işleme al eğer', 'gravityforms-iyzico' ),
					),
				),
			),
		);
	}

	private function get_pages_as_choices() {
		$pages   = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'asc',
			)
		);
		$choices = array();
		foreach ( $pages as $page ) {
			$choices[] = array(
				'label' => $page->post_title,
				'value' => $page->ID,
			);
		}
		return $choices;
	}
	public function get_payment_choices( $form = null ) {
		$choices = array(
			array(
				'label' => esc_html__( 'Form Toplamı', 'gravityforms-iyzico' ),
				'value' => 'form_total',
			),
		);
		$choices = array_merge( $choices, $this->get_numeric_field_choices( $form ) );
		return $choices;
	}
	public function get_numeric_field_choices( $form = null ) {
		if ( is_null( $form ) ) {
			$form = $this->get_current_form();
		}
		$choices = array();
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( in_array( $field->type, array( 'number', 'total', 'calculation', 'price', 'hiddenproduct', 'singleproduct' ) ) ) {
					$choices[] = array(
						'value' => $field->id,
						'label' => GFCommon::get_label( $field ),
					);
				}
			}
		}
		return $choices;
	}
	public function get_form_settings_fields() {
		return array(); // No additional form-level settings for now
	}
	public function has_feed( $form_id, $meets_conditional_logic = null ) {
		$feeds = $this->get_feeds( $form_id );
		if ( is_null( $meets_conditional_logic ) ) {
			return ! empty( $feeds );
		}
		foreach ( $feeds as $feed ) {
			if ( $this->is_feed_condition_met( $feed, $form_id ) ) {
				return true;
			}
		}
		return false;
	}
}

add_filter(
	'gform_logging_supported',
	function ( $loggers ) {
		$loggers['gravityforms-iyzico'] = __( 'Iyzico Eklentisi', 'gravityforms-iyzico' );
		return $loggers;
	}
);