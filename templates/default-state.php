<?php
/**
 * Default State Template - When someone visits the page directly
 *
 * Available variables:
 * - $title (string): Default title
 * - $message (string): Default message
 */
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate and set fallbacks for template variables
$title = isset( $title ) && ! empty( $title ) ? $title : __( 'Ödeme Onayı', 'gravityforms-iyzico' );
$message = isset( $message ) && ! empty( $message ) ? $message : __( 'Bu sayfa ödeme sonucunu gösterir.', 'gravityforms-iyzico' );
?>
<meta name="robots" content="noindex, nofollow">

<div class="iyzico-default-wrapper">
	<div class="iyzico-default-card">
		<div class="iyzico-default-icon">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
				<rect x="3" y="11" width="18" height="10" rx="2" ry="2"></rect>
				<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
			</svg>
		</div>
		
		<h2 class="iyzico-default-title"><?php echo esc_html( $title ); ?></h2>
		<p class="iyzico-default-message"><?php echo esc_html( $message ); ?></p>
		
		<div class="iyzico-default-info">
			<p><?php echo esc_html__( 'Ödeme işleminizi tamamladıysanız, e-posta adresinize gönderilen onay linkini kullanarak bu sayfaya erişebilirsiniz.', 'gravityforms-iyzico' ); ?></p>
		</div>
		
		<div class="iyzico-default-help">
			<p class="iyzico-help-title"><?php echo esc_html__( 'Yardıma mı ihtiyacınız var?', 'gravityforms-iyzico' ); ?></p>
			<p class="iyzico-help-text">
                <?php echo esc_html__( 'Ödeme ile ilgili sorularınız için site yöneticisi ile iletişime geçebilirsiniz.', 'gravityforms-iyzico' ); ?>
            </p>
		</div>
		
		<a href="<?php echo esc_url( home_url() ); ?>" class="iyzico-btn iyzico-btn-primary" aria-label="<?php echo esc_attr__( 'Ana sayfaya geri dön', 'gravityforms-iyzico' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
				<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
				<polyline points="9 22 9 12 15 12 15 22"></polyline>
			</svg>
			<?php echo esc_html__( 'Ana Sayfaya Dön', 'gravityforms-iyzico' ); ?>
		</a>
	</div>
</div>