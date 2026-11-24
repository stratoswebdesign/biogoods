<?php
/**
 * Kosár: a sablon kosároldala a böngészőben épül (gyors, gyorsítótárazható), a pénztár előtt
 * pedig ez a végpont átmásolja a WooCommerce kosarába. Az árakat, a készletet és a
 * választható kivitelt itt, a szerveren ellenőrizzük – a böngészőből érkező árat nem használjuk.
 */

defined( 'ABSPATH' ) || exit;

/** A termék választható kivitelei (pl. Egészben / Szeletelve). */
function bg_product_options( $product_id ) {
	$opts = get_post_meta( $product_id, '_bg_options', true );
	return is_array( $opts ) ? array_values( array_filter( array_map( 'strval', $opts ) ) ) : array();
}

/** Azonos eredetű kérés-e (CSRF elleni védelem gyorsítótárazott oldalakhoz, nonce nélkül). */
function bg_same_origin_request() {
	$site = wp_parse_url( home_url(), PHP_URL_HOST );
	foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $h ) {
		if ( ! empty( $_SERVER[ $h ] ) ) {
			$host = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ), PHP_URL_HOST );
			return $host && strtolower( $host ) === strtolower( $site );
		}
	}
	return false;
}

function bg_ajax_sync_cart() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! bg_same_origin_request() ) {
		wp_send_json_error( array( 'message' => 'Érvénytelen kérés.' ), 403 );
	}
	$raw  = file_get_contents( 'php://input', false, null, 0, 65536 );
	$body = json_decode( (string) $raw, true );
	if ( ! is_array( $body ) || empty( $body['items'] ) || ! is_array( $body['items'] ) || count( $body['items'] ) > 80 ) {
		wp_send_json_error( array( 'message' => 'Üres vagy hibás kosár.' ), 400 );
	}

	if ( ! WC()->cart ) {
		wc_load_cart();
	}
	WC()->cart->empty_cart();

	$skipped = array();
	foreach ( $body['items'] as $item ) {
		$slug = sanitize_title( $item['id'] ?? '' );
		$qty  = max( 0, min( 99, (int) ( $item['qty'] ?? 0 ) ) );
		if ( ! $slug || ! $qty ) {
			continue;
		}
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		$prod = $post ? wc_get_product( $post->ID ) : null;
		if ( ! $prod || 'publish' !== $prod->get_status() || ! $prod->is_purchasable() || ! $prod->is_in_stock() ) {
			$skipped[] = $slug;
			continue;
		}
		$data = array();
		$opts = bg_product_options( $prod->get_id() );
		if ( $opts ) {
			$opt         = isset( $item['opt'] ) ? (string) $item['opt'] : '';
			$data['bg_opt'] = in_array( $opt, $opts, true ) ? $opt : $opts[0];
		}
		WC()->cart->add_to_cart( $prod->get_id(), $qty, 0, array(), $data );
	}

	// település és nap: a pénztár előtölti, de ott újra ellenőrizzük
	$town = sanitize_text_field( $body['town'] ?? '' );
	if ( $town && BG_Delivery::town( $town ) ) {
		WC()->session->set( 'bg_town', $town );
	}
	$day = sanitize_text_field( $body['day'] ?? '' );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
		WC()->session->set( 'bg_day', $day );
	}
	wc_clear_notices();

	WC()->cart->calculate_totals();
	wp_send_json_success(
		array(
			'checkout' => wc_get_checkout_url(),
			'count'    => WC()->cart->get_cart_contents_count(),
			'skipped'  => $skipped,
		)
	);
}
add_action( 'wp_ajax_bg_sync_cart', 'bg_ajax_sync_cart' );
add_action( 'wp_ajax_nopriv_bg_sync_cart', 'bg_ajax_sync_cart' );

/* A választott kivitel megjelenik a kosárban, a pénztárban, a rendelésben és az e-mailekben. */
add_filter( 'woocommerce_get_item_data', function ( $data, $cart_item ) {
	if ( ! empty( $cart_item['bg_opt'] ) ) {
		$data[] = array( 'key' => 'Kivitel', 'value' => esc_html( $cart_item['bg_opt'] ) );
	}
	return $data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( ! empty( $values['bg_opt'] ) ) {
		$item->add_meta_data( 'Kivitel', $values['bg_opt'], true );
	}
}, 10, 3 );
