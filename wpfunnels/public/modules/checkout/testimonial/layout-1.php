<?php
/**
 * Testimonial section – Layout 1 (stacked)
 *
 * Part A: quote block (open-quote icon, stars, text, author)
 * Part B: benefits block (title + checkmark list)
 *
 * @package WPFunnels
 * @var array $args Full testimonial config array passed via load_template().
 */

defined( 'ABSPATH' ) || exit;

$testimonial = isset( $args['testimonial'] ) && is_array( $args['testimonial'] ) ? $args['testimonial'] : array();
$benefits    = isset( $args['benefits'] )    && is_array( $args['benefits'] )    ? $args['benefits']    : array();

$text    = isset( $testimonial['text'] )   ? $testimonial['text']   : '';
$author  = isset( $testimonial['author'] ) ? $testimonial['author'] : '';
$rating  = isset( $testimonial['rating'] ) ? (int) $testimonial['rating'] : 5;
$rating  = max( 1, min( 5, $rating ) );

$ben_title = isset( $benefits['title'] ) ? $benefits['title'] : '';
$ben_items = isset( $benefits['items'] ) && is_array( $benefits['items'] ) ? $benefits['items'] : array();
$ben_items = array_filter( $ben_items, static function ( $item ) {
	return '' !== trim( $item );
} );
?>

<div class="wpfnl-testimonial-section">
	<!-- Part A: Testimonial quote block -->
	<div class="wpfnl-checkout-testimonial1">
		<div class="wpfnl-testimonial-quote-icon" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="39" height="26" viewBox="0 0 39 26" fill="none"><path d="M31.7776 8.90091C32.5698 6.88793 33.8183 4.89914 35.4869 2.98311C36.0151 2.37678 36.0871 1.50369 35.655 0.824603C35.3188 0.291032 34.7667 4.94675e-06 34.1664 4.89427e-06C33.9984 4.87958e-06 33.8303 0.0120708 33.6622 0.0727645C30.133 1.11564 21.8859 4.81424 21.6578 16.6741C21.5738 21.2458 24.887 25.1748 29.1966 25.6235C31.5855 25.866 33.9623 25.0779 35.727 23.4771C37.4916 21.8643 38.5 19.5602 38.5 17.1591C38.5 13.1574 35.6909 9.65275 31.7776 8.90091Z" fill="#fe7e6d"/><path d="M7.55144 25.6235C9.92857 25.866 12.3056 25.0779 14.0704 23.4771C15.8353 21.8643 16.8438 19.5602 16.8438 17.1591C16.8438 13.1574 14.0344 9.65276 10.1206 8.9009C10.913 6.88792 12.1616 4.89913 13.8304 2.98311C14.3586 2.37678 14.4306 1.5037 13.9984 0.824603C13.6623 0.291032 13.11 4.94671e-06 12.5097 4.89423e-06C12.3417 4.87954e-06 12.1736 0.0120708 12.0055 0.0727645C8.47588 1.11564 0.228107 4.81424 7.86154e-07 16.6741L7.71317e-07 16.8438C3.78001e-07 21.3428 3.27748 25.1748 7.55144 25.6235Z" fill="#fe7e6d"/></svg>
		</div>

		<div class="wpfnl-testimonial-stars" aria-label="<?php echo esc_attr( sprintf( __( 'Rating: %d out of 5 stars', 'wpfnl' ), $rating ) ); ?>">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<span class="wpfnl-testimonial-star<?php echo $i <= $rating ? ' wpfnl-testimonial-star-filled' : ' wpfnl-testimonial-star-empty'; ?>" aria-hidden="true">&#9733;</span>
			<?php endfor; ?>
		</div>

		<?php if ( $text ) : ?>
			<p class="wpfnl-testimonial-text"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>

		<?php if ( $author ) : ?>
			<p class="wpfnl-testimonial-author"><?php echo esc_html( $author ); ?></p>
		<?php endif; ?>

	</div>
	<!-- /.wpfnl-checkout-testimonial1 -->
</div><!-- /.wpfnl-testimonial-section.wpfnl-testimonial-layout-1 -->


<?php if ( $ben_title || $ben_items ) : ?>
	<!-- Part B: Benefits block -->
	<div class="wpfnl-testimonial-benefits">

		<?php if ( $ben_title ) : ?>
			<h3 class="wpfnl-testimonial-benefits-title"><?php echo esc_html( $ben_title ); ?></h3>
		<?php endif; ?>

		<?php if ( $ben_items ) : ?>
		<ul class="wpfnl-testimonial-benefits-list">
			<?php foreach ( $ben_items as $item ) : ?>
				<li class="wpfnl-testimonial-benefits-item">
					<span class="wpfnl-testimonial-check-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7.33301 12.0003L10.6663 15.3337L17.333 8.66699" stroke="#039855" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect width="24" height="24" rx="12" fill="#039855" fill-opacity=".16"/></svg>
					</span>
					<span class="wpfnl-testimonial-benefits-item-text"><?php echo esc_html( $item ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

	</div>
	<!-- /.wpfnl-testimonial-benefits -->
<?php endif; ?>
