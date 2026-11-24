<?php
/**
 * Minden oldal ide fut: ha van hozzá demósablon, azt rajzoljuk ki (a felületet az app.js építi),
 * különben a WordPress/WooCommerce tartalmát (pénztár, fiók, jogi oldalak) a demó keretében.
 */
defined( 'ABSPATH' ) || exit;

get_header();
$bg_demo = bg_current_demo();
$bg_part = $bg_demo ? bg_demo_parts( $bg_demo ) : null;

if ( $bg_part ) {
	echo $bg_part['body']; // phpcs:ignore WordPress.Security.EscapeOutput -- saját sablonfájl
	if ( 'termek' === $bg_demo ) {
		// keresőknek és JavaScript nélkül: a termék alapadatai szerveroldalon is
		$bg_prod = wc_get_product( get_queried_object_id() );
		if ( $bg_prod ) {
			echo '<noscript><div class="wrap section"><h1>' . esc_html( $bg_prod->get_name() ) . '</h1><p>' . esc_html( $bg_prod->get_short_description() ) . '</p><p><strong>' . wp_kses_post( $bg_prod->get_price_html() ) . '</strong></p><p>' . esc_html( get_post_meta( $bg_prod->get_id(), '_bg_ingr', true ) ) . '</p></div></noscript>';
		}
	}
} else {
	echo '<main id="main"><div class="wrap section bg-woo">';
	while ( have_posts() ) {
		the_post();
		if ( ! is_checkout() && ! is_account_page() ) {
			echo '<h1>' . esc_html( get_the_title() ) . '</h1>';
		}
		the_content();
	}
	echo '</div></main>';
}
get_footer();
