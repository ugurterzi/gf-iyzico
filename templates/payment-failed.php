<?php
/**
 * Payment Failed Template
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

// Validate required data and set fallbacks
$customer = isset( $customer ) ? $customer : array();
$payment = isset( $payment ) ? $payment : array();

// Safe fallbacks for missing data
$customer_name = isset( $customer['fullName'] ) && ! empty( $customer['fullName'] ) 
    ? $customer['fullName'] 
    : __( 'Değerli Müşteri', 'gravityforms-iyzico' );

$transaction_id = isset( $payment['transaction_id'] ) && ! empty( $payment['transaction_id'] ) 
    ? $payment['transaction_id'] 
    : __( 'Belirtilmemiş', 'gravityforms-iyzico' );

$payment_date = isset( $payment['formatted_date'] ) && ! empty( $payment['formatted_date'] ) 
    ? $payment['formatted_date'] 
    : __( 'Belirtilmemiş', 'gravityforms-iyzico' );

$payment_amount = isset( $payment['formatted_amount'] ) && ! empty( $payment['formatted_amount'] ) 
    ? $payment['formatted_amount'] 
    : null;
?>
<meta name="robots" content="noindex, nofollow">

<div class="iyzico-failed-wrapper">
	<div class="iyzico-failed-card">
		<div class="iyzico-failed-icon" role="img" aria-label="<?php echo esc_attr__( 'Başarısız ödeme simgesi', 'gravityforms-iyzico' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</div>
		
		<h1 class="iyzico-failed-title"><?php echo esc_html__( 'Ödeme Başarısız', 'gravityforms-iyzico' ); ?></h1>
		<p class="iyzico-failed-subtitle">
			<?php echo esc_html__( 'Üzgünüz', 'gravityforms-iyzico' ); ?> <span class="iyzico-customer-name"><?php echo esc_html( $customer_name ); ?></span>, <?php echo esc_html__( 'ödemeniz tamamlanamadı.', 'gravityforms-iyzico' ); ?>
		</p>
		
		<div class="iyzico-failed-details">
			<?php if ( $payment_amount ): ?>
			<p><?php echo esc_html__( 'Tutar:', 'gravityforms-iyzico' ); ?> <strong><?php echo esc_html( $payment_amount ); ?></strong></p>
			<?php endif; ?>
			<p><?php echo esc_html__( 'İşlem No:', 'gravityforms-iyzico' ); ?> <strong><?php echo esc_html( $transaction_id ); ?></strong></p>
			<p><?php echo esc_html__( 'Tarih:', 'gravityforms-iyzico' ); ?> <?php echo esc_html( $payment_date ); ?></p>
		</div>
		
		<div class="iyzico-failed-help">
			<h3><?php echo esc_html__( 'Ne yapabilirsiniz?', 'gravityforms-iyzico' ); ?></h3>
			<ul>
				<li><?php echo esc_html__( 'Kart bilgilerinizi kontrol ederek tekrar deneyin', 'gravityforms-iyzico' ); ?></li>
				<li><?php echo esc_html__( 'Farklı bir ödeme yöntemi kullanın', 'gravityforms-iyzico' ); ?></li>
				<li><?php echo esc_html__( 'Bankanızla iletişime geçin', 'gravityforms-iyzico' ); ?></li>
				<li><?php echo esc_html__( 'Sorun devam ederse site yöneticisi ile iletişime geçin', 'gravityforms-iyzico' ); ?></li>
			</ul>
		</div>
		
		<div class="iyzico-failed-actions">
			<button onclick="window.history.back()" class="iyzico-btn iyzico-btn-primary" type="button">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
					<polyline points="1 4 1 10 7 10"></polyline>
					<path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
				</svg>
				<?php echo esc_html__( 'Tekrar Dene', 'gravityforms-iyzico' ); ?>
			</button>
			<a href="<?php echo esc_url( home_url() ); ?>" class="iyzico-btn iyzico-btn-secondary" aria-label="<?php echo esc_attr__( 'Ana sayfaya geri dön', 'gravityforms-iyzico' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
					<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
					<polyline points="9 22 9 12 15 12 15 22"></polyline>
				</svg>
				<?php echo esc_html__( 'Ana Sayfaya Dön', 'gravityforms-iyzico' ); ?>
			</a>
		</div>
	</div>
</div>