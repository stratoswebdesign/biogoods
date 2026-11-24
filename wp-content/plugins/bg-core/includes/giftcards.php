<?php
/**
 * Ajándékkártya egyenleggel (Petra, 2026-11-10: egyenleges, 6 hónapig érvényes, részletekben levásárolható).
 *
 * - Az „Ajándékkártya” termékek (_bg_giftcard) megvásárlásakor, amikor a rendelés Feldolgozás alatt /
 *   Teljesítve állapotba kerül, darabonként egy BGAK-XXXX-XXXX kód készül a kártya értékével, 6 hónapos
 *   lejárattal; a kódot a vásárló e-mailben kapja, és a rendelésben is látszik.
 * - Beváltás a pénztár kuponmezőjében. A kártya legfeljebb a kosár összegéig vonódik le; a maradék
 *   egyenleg a kódon marad. Törölt, sikertelen vagy visszatérített rendelésnél az egyenleg visszaíródik.
 * - Admin: WooCommerce → Kuponok (a kártyák ott látszanak, „Ajándékkártya” leírással), a régi, már eladott
 *   kártyák ugyanígy felvehetők kézzel vagy importtal.
 *
 * Könyvelési megjegyzés: a beváltás a WooCommerce-ben kedvezményként látszik; a számlázásnál (számlázz.hu)
 * a könyvelő döntse el, hogyan kezeljük (többcélú utalvány: az áfa a beváltáskor esedékes).
 */

defined( 'ABSPATH' ) || exit;

const BG_GC_MONTHS = 6;

function bg_gc_new_code() {
	$abc = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
	do {
		$r = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$r .= $abc[ random_int( 0, strlen( $abc ) - 1 ) ];
		}
		$code = 'BGAK-' . substr( $r, 0, 4 ) . '-' . substr( $r, 4 );
	} while ( wc_get_coupon_id_by_code( $code ) );
	return $code;
}

/** Kártya létrehozása (vásárláskor vagy régi kártya felvételekor). */
function bg_gc_create( $value, $expires_ts, $order_id = 0, $code = '' ) {
	$c = new WC_Coupon();
	$c->set_code( $code ?: bg_gc_new_code() );
	$c->set_discount_type( 'fixed_cart' );
	$c->set_amount( (float) $value );
	$c->set_individual_use( false );
	$c->set_usage_limit( 0 );
	$c->set_date_expires( $expires_ts );
	$c->set_description( 'Ajándékkártya (' . wc_price( $value ) . ')' . ( $order_id ? ', rendelés: #' . $order_id : '' ) );
	$id = $c->save();
	update_post_meta( $id, '_bg_giftcard', 1 );
	update_post_meta( $id, '_bg_gc_initial', (float) $value );
	if ( $order_id ) {
		update_post_meta( $id, '_bg_gc_order', (int) $order_id );
	}
	return $c;
}

/* 1) Kártya kiadása a megvásárolt ajándékkártya-termékekre. */
function bg_gc_issue( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_bg_gc_issued' ) ) {
		return;
	}
	$codes = array();
	foreach ( $order->get_items() as $item ) {
		$pid = $item->get_product_id();
		if ( ! $pid || ! get_post_meta( $pid, '_bg_giftcard', true ) ) {
			continue;
		}
		$value = (float) $order->get_item_total( $item, true );
		for ( $i = 0; $i < (int) $item->get_quantity(); $i++ ) {
			$c       = bg_gc_create( $value, strtotime( '+' . BG_GC_MONTHS . ' months 23:59:59', current_time( 'timestamp' ) ), $order_id );
			$codes[] = strtoupper( $c->get_code() );
		}
		$item->update_meta_data( 'Ajándékkártya-kód', implode( ', ', array_slice( $codes, -$item->get_quantity() ) ) );
		$item->save();
	}
	if ( $codes ) {
		$order->update_meta_data( '_bg_gc_issued', $codes );
		$order->add_order_note( 'Ajándékkártya kiadva: ' . implode( ', ', $codes ) . ' (' . BG_GC_MONTHS . ' hónapig érvényes).' );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_processing', 'bg_gc_issue', 5 );
add_action( 'woocommerce_order_status_completed', 'bg_gc_issue', 5 );

/* 2) Beváltás: a felhasznált összeg levonása az egyenlegből, visszaírás törlésnél. */
function bg_gc_used_amounts( WC_Order $order ) {
	$out = array();
	foreach ( $order->get_items( 'coupon' ) as $ci ) {
		$code = $ci->get_code();
		$id   = wc_get_coupon_id_by_code( $code );
		if ( $id && get_post_meta( $id, '_bg_giftcard', true ) ) {
			$out[ $id ] = (float) $ci->get_discount() + (float) $ci->get_discount_tax();
		}
	}
	return $out;
}
function bg_gc_debit( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_bg_gc_debited' ) ) {
		return;
	}
	$used = bg_gc_used_amounts( $order );
	if ( ! $used ) {
		return;
	}
	foreach ( $used as $id => $amt ) {
		$c   = new WC_Coupon( $id );
		$new = max( 0, (float) $c->get_amount() - $amt );
		$c->set_amount( $new );
		$c->save();
		$order->add_order_note( 'Ajándékkártya ' . strtoupper( $c->get_code() ) . ': ' . BG_Delivery::ft( $amt ) . ' levonva, maradék egyenleg ' . BG_Delivery::ft( $new ) . '.' );
	}
	$order->update_meta_data( '_bg_gc_debited', $used );
	$order->save();
}
foreach ( array( 'pending', 'on-hold', 'processing', 'completed' ) as $st ) {
	add_action( 'woocommerce_order_status_' . $st, 'bg_gc_debit', 6 );
}
function bg_gc_credit( $order_id ) {
	$order = wc_get_order( $order_id );
	$used  = $order ? $order->get_meta( '_bg_gc_debited' ) : null;
	if ( ! $used || ! is_array( $used ) ) {
		return;
	}
	foreach ( $used as $id => $amt ) {
		$c = new WC_Coupon( (int) $id );
		if ( ! $c->get_id() ) {
			continue;
		}
		$c->set_amount( (float) $c->get_amount() + (float) $amt );
		$c->save();
		$order->add_order_note( 'Ajándékkártya ' . strtoupper( $c->get_code() ) . ': ' . BG_Delivery::ft( $amt ) . ' visszaírva.' );
	}
	$order->delete_meta_data( '_bg_gc_debited' );
	$order->save();
}
foreach ( array( 'cancelled', 'failed', 'refunded' ) as $st ) {
	add_action( 'woocommerce_order_status_' . $st, 'bg_gc_credit', 6 );
}

/* 3) Kiürült kártya nem érvényes; a hibaüzenet érthető legyen. */
add_filter( 'woocommerce_coupon_is_valid', function ( $valid, $coupon ) {
	if ( $valid && get_post_meta( $coupon->get_id(), '_bg_giftcard', true ) && (float) $coupon->get_amount() <= 0 ) {
		throw new Exception( 'Ezen az ajándékkártyán már nincs felhasználható egyenleg.' );
	}
	return $valid;
}, 20, 2 );
add_filter( 'woocommerce_coupon_code', function ( $code ) {
	$c = strtoupper( preg_replace( '/\s+/', '', (string) $code ) );
	if ( preg_match( '/^BGAK-?([2-9A-HJKMNP-Z]{4})-?([2-9A-HJKMNP-Z]{4})$/', $c, $m ) ) {
		return 'BGAK-' . $m[1] . '-' . $m[2];
	}
	return $code;
}, 5 );

/* 4) A vásárló e-mailjében a kód(ok) és az egyenleg felhasználásának módja. */
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin, $plain_text ) {
	$codes = $order->get_meta( '_bg_gc_issued' );
	if ( ! $codes || $sent_to_admin ) {
		return;
	}
	$exp = date_i18n( 'Y. F j.', strtotime( '+' . BG_GC_MONTHS . ' months', $order->get_date_created()->getTimestamp() ) );
	if ( $plain_text ) {
		echo "\nAjándékkártya-kód(ok): " . esc_html( implode( ', ', $codes ) ) . "\nÉrvényes: " . esc_html( $exp ) . "-ig. A pénztárban a kuponmezőbe írva váltható be, részletekben is.\n";
		return;
	}
	echo '<div style="margin:0 0 24px;padding:16px 18px;border:2px dashed #E3AF00;border-radius:6px"><p style="margin:0 0 6px;font-size:15px"><strong>Ajándékkártya-kód' . ( count( $codes ) > 1 ? 'ok' : '' ) . '</strong></p>';
	foreach ( $codes as $c ) {
		echo '<p style="margin:0;font-family:monospace;font-size:20px;letter-spacing:.08em">' . esc_html( $c ) . '</p>';
	}
	echo '<p style="margin:8px 0 0;font-size:14px">Érvényes: ' . esc_html( $exp ) . '-ig. A pénztárban a „Van kuponod?” mezőbe írva váltható be, részletekben is; a maradék egyenleg a kódon marad.</p></div>';
}, 6, 3 );

/* Az ajándékkártya fizetőeszköz, nem kedvezmény: a „csak önmagában használható” kuponok (pl. BG10) mellett is beváltható. */
function bg_is_gc_coupon( $coupon ) {
	return $coupon && get_post_meta( $coupon->get_id(), '_bg_giftcard', true );
}
add_filter( 'woocommerce_apply_with_individual_use_coupon', function ( $allow, $the_coupon ) {
	return $allow || bg_is_gc_coupon( $the_coupon );
}, 10, 2 );
add_filter( 'woocommerce_apply_individual_use_coupon', function ( $keep, $the_coupon, $applied ) {
	foreach ( (array) $applied as $code ) {
		if ( bg_is_gc_coupon( new WC_Coupon( $code ) ) ) {
			$keep[] = $code;
		}
	}
	return array_unique( $keep );
}, 10, 3 );
