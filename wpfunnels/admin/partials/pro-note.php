<?php
/**
 * Inline upgrade note for a Pro-gated setting.
 *
 * A disabled control on its own says nothing about how to get it, so every
 * gated settings box closes with this note: a short line on the left, an
 * upgrade link on the right.
 *
 * Callers set $pro_note_text (the line) and $pro_note_utm (a slug naming the
 * spot the click came from) before requiring this file. The slug drives the
 * campaign parameters so each note is attributable on its own.
 *
 * @package
 */

if ( empty( $pro_note_text ) ) {
	return;
}

$pro_note_area = 'wpfnl-plugin-' . ( empty( $pro_note_utm ) ? 'settings' : $pro_note_utm );
$pro_note_url  = GETWPFUNNEL_PRICING_URL . '?' . http_build_query(
	array(
		'utm_source'   => $pro_note_area . '-pro-button',
		'utm_medium'   => $pro_note_area,
		'utm_campaign' => $pro_note_area . '-pro-CTA',
		'utm_id'       => $pro_note_area . '-pro-CTA',
	)
) . '#price';
?>
<p class="wpfnl-pro-note">
	<span class="wpfnl-pro-note__text"><?php echo esc_html( $pro_note_text ); ?></span>
	<a
		class="wpfnl-pro-note__link"
		href="<?php echo esc_url( $pro_note_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		title="<?php esc_attr_e( 'Click to Upgrade Pro', 'wpfnl' ); ?>"
	>
		<?php esc_html_e( 'Go Pro', 'wpfnl' ); ?>
		<svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 12 12" fill="none"><path d="M11 1L1 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 11V1H1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</a>
</p>
