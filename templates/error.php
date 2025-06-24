<?php
/**
 * Error Template
 *
 * Available variables:
 * - $message (string): Error message
 */
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate and set fallbacks for template variables
$message = isset( $message ) && ! empty( $message ) ? $message : __( 'Bir hata oluştu. Lütfen tekrar deneyin.', 'gravityforms-iyzico' );
?>
<meta name="robots" content="noindex, nofollow">

<div class="iyzico-error-wrapper">
	<div class="iyzico-error-card">
		<div class="iyzico-error-icon" role="img" aria-label="<?php echo esc_attr__( 'Hata simgesi', 'gravityforms-iyzico' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
				<circle cx="12" cy="12" r="10"></circle>
				<line x1="12" y1="8" x2="12" y2="12"></line>
				<line x1="12" y1="16" x2="12.01" y2="16"></line>
			</svg>
		</div>
		
		<h2 class="iyzico-error-title"><?php echo esc_html__( 'Hata', 'gravityforms-iyzico' ); ?></h2>
		<p class="iyzico-error-message"><?php echo esc_html( $message ); ?></p>
		
		<div class="iyzico-error-help">
			<h3 class="iyzico-help-title"><?php echo esc_html__( 'Sorun devam ediyor mu?', 'gravityforms-iyzico' ); ?></h3>
			<ul class="iyzico-help-list">
				<li><?php echo esc_html__( 'Sayfayı yenilemeyi deneyin', 'gravityforms-iyzico' ); ?></li>
				<li><?php echo esc_html__( 'İnternet bağlantınızı kontrol edin', 'gravityforms-iyzico' ); ?></li>
				<li>
					<?php 
					printf( 
						esc_html__( 'Sorun devam ederse %s ile iletişime geçin', 'gravityforms-iyzico' ),
						'<a href="' . esc_url( home_url( '/iletisim' ) ) . '">' . esc_html__( 'destek ekibimiz', 'gravityforms-iyzico' ) . '</a>'
					); 
					?>
				</li>
			</ul>
		</div>
		
		<div class="iyzico-error-actions">
			<button onclick="window.location.reload()" class="iyzico-btn iyzico-btn-primary" type="button">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
					<polyline points="23 4 23 10 17 10"></polyline>
					<path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
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