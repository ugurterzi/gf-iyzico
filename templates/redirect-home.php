<?php
/**
 * Redirect Home Template
 *
 * Available variables:
 * - $delay (int): Seconds before redirect
 */
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate and set fallbacks for template variables
$delay = isset( $delay ) && is_numeric( $delay ) && $delay > 0 ? intval( $delay ) : 5;
$home_url = home_url();
?>
<meta name="robots" content="noindex, nofollow">

<div class="iyzico-redirect-wrapper">
	<div class="iyzico-redirect-card">
		<div class="iyzico-redirect-icon" role="img" aria-label="<?php echo esc_attr__( 'Yönlendirme simgesi', 'gravityforms-iyzico' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
				<polyline points="23 4 23 10 17 10"></polyline>
				<path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
			</svg>
		</div>
		
		<h2 class="iyzico-redirect-title"><?php echo esc_html__( 'Yönlendiriliyorsunuz...', 'gravityforms-iyzico' ); ?></h2>
		
		<div class="iyzico-redirect-info">
			<p><?php echo esc_html__( 'Bu sayfa zaten görüntülendi. Güvenlik nedeniyle ödeme onay sayfaları yalnızca bir kez görüntülenebilir.', 'gravityforms-iyzico' ); ?></p>
		</div>
		
		<div class="iyzico-countdown-container">
			<p class="iyzico-countdown-text">
				<span id="iyzico-countdown"><?php echo esc_html( $delay ); ?></span> <?php echo esc_html__( 'saniye içinde ana sayfaya yönlendirileceksiniz.', 'gravityforms-iyzico' ); ?>
			</p>
		</div>
		
		<div class="iyzico-redirect-actions">
			<a href="<?php echo esc_url( $home_url ); ?>" class="iyzico-btn iyzico-btn-primary" aria-label="<?php echo esc_attr__( 'Ana sayfaya git', 'gravityforms-iyzico' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
					<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
					<polyline points="9 22 9 12 15 12 15 22"></polyline>
				</svg>
				<?php echo esc_html__( 'Hemen Git', 'gravityforms-iyzico' ); ?>
			</a>
		</div>
	</div>
</div>