<?php
/**
 * Mérés: Google Analytics 4 / Google Ads (Consent Mode v2) és Meta Pixel, a sütihozzájárulás szerint.
 *
 * - Az azonosítókat a Bio-Goods → Mérés oldalon kell beírni; amíg üresek, semmi nem töltődik be.
 * - Google: alapból minden tárolás „denied”; „Mindet elfogadom” után „granted” (Consent Mode v2, haladó mód).
 * - Meta Pixel: csak hozzájárulás után töltődik be.
 * - Események: view_item, add_to_cart, begin_checkout, purchase (GA4) / ViewContent, AddToCart, InitiateCheckout, Purchase (Meta).
 */

defined( 'ABSPATH' ) || exit;

function bg_tracking_ids() {
	$o = get_option( 'bg_tracking', array() );
	return array(
		'ga4'  => preg_match( '/^G-[A-Z0-9]{4,}$/', $o['ga4'] ?? '' ) ? $o['ga4'] : '',
		'ads'  => preg_match( '/^AW-\d{6,}$/', $o['ads'] ?? '' ) ? $o['ads'] : '',
		'adsl' => preg_match( '/^[A-Za-z0-9_-]{6,}$/', $o['adsl'] ?? '' ) ? $o['adsl'] : '', // Ads konverziós címke (purchase)
		'meta' => preg_match( '/^\d{8,20}$/', $o['meta'] ?? '' ) ? $o['meta'] : '',
	);
}

/** Consent Mode alapállapot + gtag betöltés: a <head> legelején. */
add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$ids = bg_tracking_ids();
	if ( ! $ids['ga4'] && ! $ids['ads'] && ! $ids['meta'] ) {
		return;
	}
	$cfg = array(
		'ga4'      => $ids['ga4'],
		'ads'      => $ids['ads'],
		'adsLabel' => $ids['adsl'],
		'meta'     => $ids['meta'],
		'currency' => 'HUF',
	);
	echo "<script>\n";
	echo 'window.BG_TRACK=' . wp_json_encode( $cfg ) . ";\n";
	echo "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}\n";
	echo "(function(){var c=null;try{c=JSON.parse(localStorage.getItem('bg_cookie'));}catch(e){}var g=c&&c.choice==='all'?'granted':'denied';\n";
	echo "gtag('consent','default',{ad_storage:g,analytics_storage:g,ad_user_data:g,ad_personalization:g,functionality_storage:'granted',security_storage:'granted',wait_for_update:500});\n";
	echo "gtag('set','ads_data_redaction',true);gtag('set','url_passthrough',true);})();\n";
	echo "</script>\n";
	$first = $ids['ga4'] ?: $ids['ads'];
	if ( $first ) {
		echo '<script async src="' . esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . $first ) . '"></script>' . "\n";
		echo "<script>gtag('js',new Date());" . ( $ids['ga4'] ? "gtag('config'," . wp_json_encode( $ids['ga4'] ) . ');' : '' ) . ( $ids['ads'] ? "gtag('config'," . wp_json_encode( $ids['ads'] ) . ');' : '' ) . "</script>\n";
	}
}, 0 );

add_action( 'wp_enqueue_scripts', function () {
	$ids = bg_tracking_ids();
	if ( ! $ids['ga4'] && ! $ids['ads'] && ! $ids['meta'] ) {
		return;
	}
	wp_enqueue_script( 'bg-tracking', BG_CORE_URL . 'assets/tracking.js', array(), BG_CORE_VERSION, true );

	$data = array();
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && WC()->cart ) {
		$data['begin_checkout'] = bg_tracking_cart_items();
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		$key      = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $order && hash_equals( $order->get_order_key(), $key ) && ! $order->get_meta( '_bg_tracked' ) ) {
			$items = array();
			foreach ( $order->get_items() as $it ) {
				$p       = $it->get_product();
				$items[] = array(
					'item_id'   => $p ? $p->get_slug() : (string) $it->get_product_id(),
					'item_name' => $it->get_name(),
					'price'     => (float) $order->get_item_total( $it, true ),
					'quantity'  => (int) $it->get_quantity(),
				);
			}
			$data['purchase'] = array(
				'transaction_id' => (string) $order->get_order_number(),
				'value'          => (float) $order->get_total(),
				'items'          => $items,
				'coupon'         => implode( ',', $order->get_coupon_codes() ),
			);
			$order->update_meta_data( '_bg_tracked', 1 );
			$order->save();
		}
	}
	wp_add_inline_script( 'bg-tracking', 'window.BG_TRACK_EVENTS=' . wp_json_encode( (object) $data ) . ';', 'before' );
} );

function bg_tracking_cart_items() {
	$items = array();
	$value = 0;
	foreach ( WC()->cart->get_cart() as $ci ) {
		$p       = $ci['data'];
		$price   = (float) wc_get_price_including_tax( $p );
		$items[] = array(
			'item_id'   => $p->get_slug(),
			'item_name' => $p->get_name(),
			'price'     => $price,
			'quantity'  => (int) $ci['quantity'],
		);
		$value += $price * (int) $ci['quantity'];
	}
	return array( 'value' => $value, 'items' => $items );
}

/* Beállítási oldal: Bio-Goods → Mérés */
add_action( 'admin_menu', function () {
	add_submenu_page( 'bg-route', 'Mérés', 'Mérés (GA4, Meta)', 'manage_options', 'bg-tracking', 'bg_tracking_page' );
}, 20 );

function bg_tracking_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	if ( isset( $_POST['bg_tracking_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bg_tracking_nonce'] ) ), 'bg_tracking' ) ) {
		$in = array();
		foreach ( array( 'ga4', 'ads', 'adsl', 'meta' ) as $k ) {
			$in[ $k ] = isset( $_POST[ $k ] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) ) : '';
		}
		$in['adsl'] = isset( $_POST['adsl'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['adsl'] ) ) ) : '';
		update_option( 'bg_tracking', $in, true );
		echo '<div class="notice notice-success"><p>Mentve.</p></div>';
	}
	$o   = get_option( 'bg_tracking', array() );
	$ids = bg_tracking_ids();
	echo '<div class="wrap"><h1>Mérés</h1><p>A kódok csak akkor töltődnek be, ha itt meg vannak adva. A Google a sütisávban adott hozzájárulás szerint mér (Consent Mode v2); a Meta Pixel csak „Mindet elfogadom” után töltődik be.</p>';
	echo '<form method="post"><table class="form-table">';
	$rows = array(
		'ga4'  => array( 'Google Analytics 4 mérési azonosító', 'G-XXXXXXXXXX' ),
		'ads'  => array( 'Google Ads azonosító', 'AW-123456789' ),
		'adsl' => array( 'Google Ads konverziós címke (vásárlás)', 'AbC-D_efG-h12_34-567' ),
		'meta' => array( 'Meta Pixel azonosító', '123456789012345' ),
	);
	foreach ( $rows as $k => $r ) {
		$ok = ! empty( $o[ $k ] ) && $ids[ $k ] ? ' <span style="color:#2F5E2B">✓ érvényes</span>' : ( ! empty( $o[ $k ] ) ? ' <span style="color:#8A2B1A">hibás formátum, nem töltődik be</span>' : '' );
		echo '<tr><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_html( $r[0] ) . '</label></th><td><input class="regular-text" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( $o[ $k ] ?? '' ) . '" placeholder="' . esc_attr( $r[1] ) . '">' . wp_kses_post( $ok ) . '</td></tr>';
	}
	echo '</table>';
	wp_nonce_field( 'bg_tracking', 'bg_tracking_nonce' );
	submit_button( 'Mentés' );
	echo '</form></div>';
}
