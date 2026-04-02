<?php
/**
 * Testimonial section – Layout 2 (Trust Badge)
 *
 * Top: bordered card — badge seal on left, headline + body text on right.
 * Bottom: benefits checklist.
 *
 * @package WPFunnels
 * @var array $args Full testimonial config array passed via load_template().
 */

defined( 'ABSPATH' ) || exit;

$guarantee = isset( $args['guarantee'] ) && is_array( $args['guarantee'] ) ? $args['guarantee'] : array();
$benefits  = isset( $args['benefits'] )  && is_array( $args['benefits'] )  ? $args['benefits']  : array();

$headline   = isset( $guarantee['headline'] ) ? $guarantee['headline'] : '';
$body_text  = isset( $guarantee['text'] )     ? $guarantee['text']     : '';
$days       = isset( $guarantee['days'] )     ? (int) $guarantee['days'] : 30;
$days       = max( 1, $days );
$badge_url  = isset( $guarantee['image']['url'] ) ? esc_url( $guarantee['image']['url'] ) : '';
$badge_id   = isset( $guarantee['image']['id'] )  ? (int) $guarantee['image']['id']       : 0;

$ben_title = isset( $benefits['title'] ) ? $benefits['title'] : '';
$ben_items = isset( $benefits['items'] ) && is_array( $benefits['items'] ) ? $benefits['items'] : array();
$ben_items = array_filter( $ben_items, static function ( $item ) {
	return '' !== trim( $item );
} );
?>
<div class="wpfnl-testimonial-trust-badge">
	<!-- Trust badge card -->
	<div class="wpfnl-testimonial-trust-card">

		<!-- Badge: uploaded image takes priority; SVG seal is the fallback -->
		<div class="wpfnl-testimonial-badge" aria-hidden="true">
			<?php if ( $badge_url ) : ?>
				<img
					src="<?php echo $badge_url; ?>"
					alt="<?php echo esc_attr( $headline ? $headline : __( 'Guarantee badge', 'wpfnl' ) ); ?>"
					class="wpfnl-testimonial-badge__img"
					width="130"
					height="130"
				/>
			<?php else : ?>
			<svg width="130" height="130" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M65 4 L69.5 0L74 4L79.5 1.5L83 6.5L89 5.5L91.5 11L97.5 11.5L99 17.5L105 19.5L105.5 25.5L111 29L110.5 35L115.5 39.5L114 45.5L118.5 51L116 57L119.5 63L116 69L118.5 75L115 80L116 86.5L111 90.5L110.5 96.5L105 100L104.5 106L98.5 108L97.5 114L91 114.5L89 120L83 119L79.5 124L74 121.5L69.5 125.5L65 121.5L60.5 125.5L56 121.5L50.5 124L47 119L41 120L39 114.5L32.5 114L31.5 108L25.5 106L25 100L19.5 96.5L19 90.5L14 86.5L15 80L11.5 75L14 69L10.5 63L14 57L11.5 51L16 45.5L14.5 39.5L19.5 35L19 29L24.5 25.5L25 19.5L31 17.5L32.5 11.5L38.5 11L41 5.5L47 6.5L50.5 1.5L56 4L60.5 0Z" fill="#D6EAC8"/>
				<circle cx="65" cy="65" r="48" fill="#1E2557"/>
				<circle cx="65" cy="65" r="36" fill="#FFFFFF"/>
				<text x="65" y="62" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" font-weight="800" fill="#1E2557"><?php echo esc_html( $days ); ?></text>
				<text x="65" y="78" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#5CB85C">DAY</text>
				<path id="wpfnl-top-arc-<?php echo esc_attr( $days ); ?>" d="M 27,65 A 38,38 0 0,1 103,65" fill="none"/>
				<text font-family="Arial, sans-serif" font-size="8" font-weight="700" fill="#FFFFFF" letter-spacing="1"><textPath href="#wpfnl-top-arc-<?php echo esc_attr( $days ); ?>" startOffset="8%">MONEY BACK</textPath></text>
				<path id="wpfnl-bot-arc-<?php echo esc_attr( $days ); ?>" d="M 27,65 A 38,38 0 0,0 103,65" fill="none"/>
				<text font-family="Arial, sans-serif" font-size="8" font-weight="700" fill="#FFFFFF" letter-spacing="1"><textPath href="#wpfnl-bot-arc-<?php echo esc_attr( $days ); ?>" startOffset="12%">GUARANTEE</textPath></text>
				<circle cx="36" cy="65" r="2.5" fill="#FFFFFF"/>
				<circle cx="94" cy="65" r="2.5" fill="#FFFFFF"/>
			</svg>
			<?php endif; ?>
		</div><!-- /.wpfnl-testimonial-badge -->

		<!-- Content: headline + body -->
		<div class="wpfnl-testimonial-trust-content">
			<?php if ( $headline ) : ?>
				<h3 class="wpfnl-testimonial-headline"><?php echo esc_html( $headline ); ?></h3>
			<?php endif; ?>
			<?php if ( $body_text ) : ?>
				<p class="wpfnl-testimonial-text"><?php echo esc_html( $body_text ); ?></p>
			<?php endif; ?>
		</div>

	</div><!-- /.wpfnl-testimonial-trust-card -->
</div><!-- /.wpfnl-testimonial-section.wpfnl-testimonial-layout-2 -->


<?php if ( $ben_title || $ben_items ) : ?>
	<!-- Benefits block -->
	<div class="wpfnl-testimonial-benefits">

		<?php if ( $ben_title ) : ?>
			<h3 class="wpfnl-testimonial-benefits-title"><?php echo esc_html( $ben_title ); ?></h3>
		<?php endif; ?>

		<?php if ( $ben_items ) : ?>
		<ul class="wpfnl-testimonial-benefits-list">
			<?php foreach ( $ben_items as $item ) : ?>
				<li class="wpfnl-testimonial-benefits-item">
					<span class="wpfnl-testimonial-check-icon" aria-hidden="true">
						<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="14" cy="14" r="14" fill="#C8EDD6"/>
							<path d="M8.5 14.5l4 4 7-9" stroke="#3BB566" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<span class="wpfnl-testimonial-benefits-item-text"><?php echo esc_html( $item ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

	</div><!-- /.wpfnl-testimonial-benefits-block -->
<?php endif; ?>
