<?php
/**
 * Payment Confirmation Template
 *
 * Available variables:
 * - $customer (array): Customer information
 * - $payment (array): Payment details
 * - $entry (array): Gravity Forms entry
 * - $form (array): Gravity Forms form
 */
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate required data
$customer = isset( $customer ) ? $customer : array();
$payment = isset( $payment ) ? $payment : array();

// Fallbacks for missing data
$customer_name = isset( $customer['fullName'] ) ? $customer['fullName'] : __( 'Değerli Müşteri', 'gravityforms-iyzico' );
$payment_amount = isset( $payment['formatted_amount'] ) ? $payment['formatted_amount'] : __( 'Belirtilmemiş', 'gravityforms-iyzico' );
$transaction_id = isset( $payment['transaction_id'] ) ? $payment['transaction_id'] : __( 'Belirtilmemiş', 'gravityforms-iyzico' );
$payment_date = isset( $payment['formatted_date'] ) ? $payment['formatted_date'] : __( 'Belirtilmemiş', 'gravityforms-iyzico' );
$customer_email = isset( $customer['email'] ) ? $customer['email'] : __( 'Belirtilmemiş', 'gravityforms-iyzico' );
$payment_status = isset( $payment['status'] ) ? $payment['status'] : __( 'Başarılı', 'gravityforms-iyzico' );
?>
<style media="print">
  .iyzico-actions { display: none !important; }
  .iyzico-confirmation-wrapper { box-shadow: none !important; margin: 0 !important; }
  body { background: white !important; }
</style>
<meta name="robots" content="noindex, nofollow">

<div class="iyzico-confirmation-wrapper payment-confirmation">
	<div class="iyzico-confirmation-card iyzico-success-content">
		<div class="iyzico-success-icon">
			<svg viewBox="0 0 24 24">
				<polyline points="20 6 9 17 4 12"></polyline>
			</svg>
		</div>
		
		<h1 class="iyzico-confirmation-title"><?php echo esc_html__( 'Ödeme Başarılı!', 'gravityforms-iyzico' ); ?></h1>
		<p class="iyzico-confirmation-subtitle">
			<?php echo esc_html__( 'Teşekkürler', 'gravityforms-iyzico' ); ?> <span class="iyzico-customer-name"><?php echo esc_html( $customer_name ); ?></span>
		</p>
		
		<div class="iyzico-amount-display">
			<p class="iyzico-amount-label"><?php echo esc_html__( 'Ödeme Tutarı', 'gravityforms-iyzico' ); ?></p>
			<p class="iyzico-amount-value"><?php echo esc_html( $payment_amount ); ?></p>
		</div>
		
		<div class="iyzico-details-grid">
			<div class="iyzico-detail-item">
				<p class="iyzico-detail-label"><?php echo esc_html__( 'İşlem No', 'gravityforms-iyzico' ); ?></p>
				<p class="iyzico-detail-value"><?php echo esc_html( $transaction_id ); ?></p>
			</div>
			<div class="iyzico-detail-item">
				<p class="iyzico-detail-label"><?php echo esc_html__( 'Tarih', 'gravityforms-iyzico' ); ?></p>
				<p class="iyzico-detail-value"><?php echo esc_html( $payment_date ); ?></p>
			</div>
			<div class="iyzico-detail-item">
				<p class="iyzico-detail-label"><?php echo esc_html__( 'E-posta', 'gravityforms-iyzico' ); ?></p>
				<p class="iyzico-detail-value"><?php echo esc_html( $customer_email ); ?></p>
			</div>
			<div class="iyzico-detail-item">
				<p class="iyzico-detail-label"><?php echo esc_html__( 'Durum', 'gravityforms-iyzico' ); ?></p>
				<p class="iyzico-detail-value"><?php echo esc_html( $payment_status ); ?></p>
			</div>
		</div>
		
		<div class="iyzico-actions">
			<a href="<?php echo esc_url( home_url() ); ?>" class="iyzico-btn iyzico-btn-primary">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
					<polyline points="9 22 9 12 15 12 15 22"></polyline>
				</svg>
				<?php echo esc_html__( 'Ana Sayfaya Dön', 'gravityforms-iyzico' ); ?>
			</a>
			<button onclick="window.print()" class="iyzico-btn iyzico-btn-secondary" type="button" aria-label="<?php echo esc_attr__( 'Ödeme onayını yazdır', 'gravityforms-iyzico' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<polyline points="6 9 6 2 18 2 18 9"></polyline>
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
					<rect x="6" y="14" width="12" height="8"></rect>
				</svg>
				<?php echo esc_html__( 'Yazdır', 'gravityforms-iyzico' ); ?>
			</button>
		</div>
	</div>
</div>