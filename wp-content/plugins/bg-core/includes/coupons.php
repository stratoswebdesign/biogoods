<?php
/**
 * Kuponszabályok.
 *
 * Karácsonyi ajándékdobozos kártya (Petra, 2026-11-24): 200 egyedi BG10-XXXX-XXXX kód, −10%,
 * min. 15 000 Ft (kedvezmény előtt), 2027-06-30-ig, akciós termékekre is, más kuponnal nem vonható össze,
 * kódonként egyszer, vásárlónként a kampányból egyszer.
 * Újra felhasználható: sikertelen fizetés, törölt rendelés, teljes visszatérítés.
 * Elhasználtnak számít: teljesített rendelés, részleges visszatérítés.
 *
 * Az alapbeállításokat (százalék, minimum, lejárat, egyszeri használat, „csak önmagában”)
 * a kuponokon tároljuk (lásd: wp bg import-coupons). Ami a WooCommerce-ből hiányzik, az itt van:
 *  - a kampány egészére vonatkozó „vásárlónként egyszer” szabály (_bg_campaign meta),
 *  - a teljes visszatérítésnél felszabaduló kód.
 */

defined( 'ABSPATH' ) || exit;

/** Teljes visszatérítésnél (Visszatérítve állapot) is szabaduljon fel a kód. */
add_filter( 'woocommerce_update_coupon_usage_invalid_statuses', function ( $statuses ) {
	$statuses[] = 'refunded';
	return array_unique( $statuses );
} );
add_action( 'woocommerce_order_status_refunded', 'wc_update_coupon_usage_counts' );

/** A kampány kódjait használó, „élő” rendelések száma egy e-mail-címhez. */
function bg_campaign_uses_by_email( $campaign, $email, $exclude_order_id = 0 ) {
	if ( ! $email ) {
		return 0;
	}
	$orders = wc_get_orders(
		array(
			'billing_email' => $email,
			'status'        => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ),
			'limit'         => 50,
			'return'        => 'objects',
		)
	);
	$n = 0;
	foreach ( $orders as $o ) {
		if ( (int) $o->get_id() === (int) $exclude_order_id ) {
			continue;
		}
		foreach ( $o->get_coupon_codes() as $code ) {
			$c = new WC_Coupon( $code );
			if ( $c->get_id() && get_post_meta( $c->get_id(), '_bg_campaign', true ) === $campaign ) {
				$n++;
			}
		}
	}
	return $n;
}

function bg_campaign_once_msg( $campaign ) {
	return 'xmas2026' === $campaign ? 'A karácsonyi kártyából vásárlónként egy kód váltható be.' : 'Ezt a kedvezményt vásárlónként egyszer lehet beváltani.';
}

/** Pénztárkor: egy vásárló a kampányból csak egy kódot válthat be (e-mail-cím alapján). */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	$email = sanitize_email( $data['billing_email'] ?? '' );
	foreach ( WC()->cart->get_applied_coupons() as $code ) {
		$c        = new WC_Coupon( $code );
		$campaign = $c->get_id() ? get_post_meta( $c->get_id(), '_bg_campaign', true ) : '';
		if ( $campaign && bg_campaign_uses_by_email( $campaign, $email ) > 0 ) {
			$errors->add( 'bg_coupon_once', bg_campaign_once_msg( $campaign ) . ' Ehhez az e-mail-címhez már tartozik beváltás.' );
		}
	}
}, 20, 2 );

/** Bejelentkezett vásárlónál már a kód beírásakor jelezzük. */
add_filter( 'woocommerce_coupon_is_valid', function ( $valid, $coupon ) {
	if ( ! $valid || ! is_user_logged_in() ) {
		return $valid;
	}
	$campaign = get_post_meta( $coupon->get_id(), '_bg_campaign', true );
	if ( $campaign ) {
		$user = wp_get_current_user();
		if ( bg_campaign_uses_by_email( $campaign, $user->user_email ) > 0 ) {
			throw new Exception( bg_campaign_once_msg( $campaign ) ); // a WooCommerce ezt hibaüzenetként mutatja
		}
	}
	return $valid;
}, 10, 2 );

/** Kisbetűvel, szóközzel beírt kódot is elfogadunk (BG10 xxxx xxxx). */
add_filter( 'woocommerce_coupon_code', function ( $code ) {
	$c = strtoupper( preg_replace( '/\s+/', '', (string) $code ) );
	if ( preg_match( '/^BG10([2-9A-HJKMNP-Z]{4})([2-9A-HJKMNP-Z]{4})$/', $c, $m ) ) {
		return 'BG10-' . $m[1] . '-' . $m[2];
	}
	return $code;
}, 5 ); // a WooCommerce utána kisbetűsít

/** A kódok nagybetűvel jelenjenek meg (a WooCommerce kisbetűsen tárolja). */
$bg_upper = function ( $text ) {
	return preg_replace_callback( '/(?:bg10|bgak)-[a-z0-9]{4}-[a-z0-9]{4}/', function ( $m ) {
		return strtoupper( $m[0] );
	}, (string) $text );
};
add_filter( 'woocommerce_coupon_error', $bg_upper );
add_filter( 'woocommerce_coupon_message', $bg_upper );
add_filter( 'woocommerce_cart_totals_coupon_label', $bg_upper );
