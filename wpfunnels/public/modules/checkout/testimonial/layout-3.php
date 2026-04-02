<?php
/**
 * Testimonial section – Layout 3 (compact)
 *
 * Single row: stars + short quote + em dash + author name.
 * Benefits checklist below in compact style.
 *
 * @package WPFunnels
 * @var array $args Full testimonial config array.
 */

defined( 'ABSPATH' ) || exit;

$testimonial = isset( $args['testimonial'] ) ? $args['testimonial'] : array();
$benefits    = isset( $args['benefits'] )    ? $args['benefits']    : array();

$text    = isset( $testimonial['text'] )   ? $testimonial['text']   : '';
$author  = isset( $testimonial['author'] ) ? $testimonial['author'] : '';
$rating  = isset( $testimonial['rating'] ) ? (int) $testimonial['rating'] : 5;
$rating  = max( 1, min( 5, $rating ) );

$ben_title = isset( $benefits['title'] ) ? $benefits['title'] : '';
$ben_items = isset( $benefits['items'] ) && is_array( $benefits['items'] ) ? $benefits['items'] : array();
?>
<div class="wpfnl-reset wpfnl-testimonial wpfnl-testimonial--layout-3">

	<!-- Compact inline row -->
	<div class="wpfnl-testimonial__compact-row">
		<span class="wpfnl-testimonial__stars wpfnl-testimonial__stars--inline" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'wpfnl' ), $rating ) ); ?>">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<span class="wpfnl-testimonial__star<?php echo $i <= $rating ? ' wpfnl-testimonial__star--filled' : ''; ?>" aria-hidden="true">&#9733;</span>
			<?php endfor; ?>
		</span>

		<?php if ( $text ) : ?>
			<span class="wpfnl-testimonial__text wpfnl-testimonial__text--inline"><?php echo wp_kses_post( $text ); ?></span>
		<?php endif; ?>

		<?php if ( $author ) : ?>
			<span class="wpfnl-testimonial__author wpfnl-testimonial__author--inline">&mdash; <?php echo esc_html( $author ); ?></span>
		<?php endif; ?>
	</div>
	<!-- /compact row -->

	<?php if ( $ben_title || $ben_items ) : ?>
	<!-- Benefits (compact) -->
	<div class="wpfnl-testimonial__benefits-card wpfnl-testimonial__benefits-card--compact">
		<?php if ( $ben_title ) : ?>
			<h4 class="wpfnl-testimonial__benefits-title"><?php echo esc_html( $ben_title ); ?></h4>
		<?php endif; ?>

		<?php if ( $ben_items ) : ?>
			<ul class="wpfnl-testimonial__benefits-list wpfnl-testimonial__benefits-list--compact">
				<?php foreach ( $ben_items as $item ) : ?>
					<?php if ( '' !== trim( $item ) ) : ?>
					<li class="wpfnl-testimonial__benefits-item">
						<span class="wpfnl-testimonial__check-icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="#22C55E"/><path d="M4.5 8.5l2.5 2.5 4.5-5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
						<span class="wpfnl-testimonial__benefits-item-text"><?php echo esc_html( $item ); ?></span>
					</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<!-- /benefits -->
	<?php endif; ?>

</div>
