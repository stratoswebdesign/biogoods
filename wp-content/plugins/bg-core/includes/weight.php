<?php
/**
 * Tényleges súly szerinti elszámolás.
 *
 * A „súly szerint számolt” termékeknél (alapból a tőkehúsok; termékenként kapcsolható) a
 * boltkezelő összekészítés után beírja a rendelésben a lemért súlyt, és a tétel ára a
 * kilóár × mért súly lesz. A rendelés végösszege újraszámolódik, így a járatlistán a
 * beszedendő összeg is a valós lesz. Online fizetésnél ezt a SimplePay kétlépcsős
 * fizetése (zárolás, majd terhelés a végösszeggel) követi majd.
 */

defined( 'ABSPATH' ) || exit;

function bg_is_weighted( $product_id ) {
	$v = get_post_meta( $product_id, '_bg_weighted', true );
	if ( '' !== $v ) {
		return 'yes' === $v;
	}
	return has_term( 'tokehusok', 'product_cat', $product_id ) && (float) get_post_meta( $product_id, '_bg_unit', true ) > 0;
}

/* Termékszerkesztő: kapcsoló és kilóár */
add_action( 'woocommerce_product_options_pricing', function () {
	global $post;
	woocommerce_wp_text_input(
		array(
			'id'                => '_bg_unit',
			'label'             => 'Kilóár (Ft/kg)',
			'type'              => 'number',
			'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
			'desc_tip'          => true,
			'description'       => 'A kilóár kötelezően feltüntetendő, és a súly szerinti elszámolás alapja.',
		)
	);
	woocommerce_wp_select(
		array(
			'id'          => '_bg_weighted',
			'label'       => 'Súly szerinti elszámolás',
			'options'     => array( '' => 'Alapértelmezés (tőkehúsnál igen)', 'yes' => 'Igen', 'no' => 'Nem' ),
			'desc_tip'    => true,
			'description' => 'Ha igen, a rendelésben megadható a lemért súly, és a tétel ára ahhoz igazodik.',
			'value'       => get_post_meta( $post->ID, '_bg_weighted', true ),
		)
	);
} );
add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification -- a WooCommerce termékmentése ellenőrzi
	if ( isset( $_POST['_bg_unit'] ) ) {
		$product->update_meta_data( '_bg_unit', absint( $_POST['_bg_unit'] ) ?: '' );
	}
	if ( isset( $_POST['_bg_weighted'] ) ) {
		$v = sanitize_key( wp_unslash( $_POST['_bg_weighted'] ) );
		$product->update_meta_data( '_bg_weighted', in_array( $v, array( 'yes', 'no' ), true ) ? $v : '' );
	}
	// phpcs:enable
} );

/* Rendelés: „Mért súlyok” doboz */
add_action( 'add_meta_boxes', function () {
	$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'bg-weights', 'Mért súlyok (súly szerinti elszámolás)', 'bg_weights_box', $screen, 'normal', 'high' );
} );

function bg_weights_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	$rows = array();
	foreach ( $order->get_items() as $item_id => $item ) {
		$pid = $item->get_product_id();
		if ( $pid && bg_is_weighted( $pid ) ) {
			$rows[ $item_id ] = $item;
		}
	}
	if ( ! $rows ) {
		echo '<p>Ebben a rendelésben nincs súly szerint elszámolt termék.</p>';
		return;
	}
	wp_nonce_field( 'bg_weights', 'bg_weights_nonce' );
	echo '<p>Összekészítés után írd be tételenként a <strong>lemért összsúlyt grammban</strong> (ha 2 darab van, a kettő együtt). A tétel ára: kilóár × mért súly. Mentéskor a rendelés végösszege újraszámolódik.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Termék</th><th>Db</th><th>Rendelt kiszerelés</th><th>Kilóár</th><th>Eredeti ár</th><th>Mért súly (g)</th><th>Új ár</th></tr></thead><tbody>';
	foreach ( $rows as $item_id => $item ) {
		$pid    = $item->get_product_id();
		$unit   = (float) ( $item->get_meta( '_bg_unit' ) ?: get_post_meta( $pid, '_bg_unit', true ) );
		$orig   = $item->get_meta( '_bg_orig_total' );
		$orig   = '' !== $orig ? (float) $orig : (float) $item->get_total();
		$weight = $item->get_meta( '_bg_weight_g' );
		echo '<tr><td>' . esc_html( $item->get_name() ) . ( $item->get_meta( 'Kivitel' ) ? '<br><span class="description">' . esc_html( $item->get_meta( 'Kivitel' ) ) . '</span>' : '' ) . '</td>';
		echo '<td>' . (int) $item->get_quantity() . '</td>';
		echo '<td>' . esc_html( (string) get_post_meta( $pid, '_bg_pack', true ) ) . '</td>';
		echo '<td>' . esc_html( BG_Delivery::ft( $unit ) ) . '/kg</td>';
		echo '<td>' . esc_html( BG_Delivery::ft( $orig ) ) . '</td>';
		echo '<td><input type="number" min="0" step="1" name="bg_weight[' . (int) $item_id . ']" value="' . esc_attr( $weight ) . '" style="width:110px" placeholder="pl. 1320"></td>';
		echo '<td>' . ( '' !== $weight ? '<strong>' . esc_html( BG_Delivery::ft( (float) $item->get_total() ) ) . '</strong>' : '–' ) . '</td></tr>';
	}
	echo '</tbody></table><p class="description">Üresen hagyott mezőnél az eredeti ár marad. A mező törlésével visszaáll az eredeti ár.</p>';
}

add_action( 'woocommerce_process_shop_order_meta', function ( $order_id ) {
	if ( empty( $_POST['bg_weights_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bg_weights_nonce'] ) ), 'bg_weights' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_shop_orders' ) || empty( $_POST['bg_weight'] ) || ! is_array( $_POST['bg_weight'] ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$changed = array();
	foreach ( wp_unslash( $_POST['bg_weight'] ) as $item_id => $grams ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- számmá alakítjuk
		$item = $order->get_item( absint( $item_id ) );
		if ( ! $item instanceof WC_Order_Item_Product || ! bg_is_weighted( $item->get_product_id() ) ) {
			continue;
		}
		$grams = '' === trim( (string) $grams ) ? '' : absint( $grams );
		$prev  = $item->get_meta( '_bg_weight_g' );
		if ( (string) $prev === (string) $grams ) {
			continue;
		}
		$orig = $item->get_meta( '_bg_orig_total' );
		if ( '' === $orig ) {
			$orig = (float) $item->get_total();
			$item->update_meta_data( '_bg_orig_total', $orig );
			$item->update_meta_data( '_bg_unit', (float) get_post_meta( $item->get_product_id(), '_bg_unit', true ) );
		}
		$unit = (float) $item->get_meta( '_bg_unit' );
		if ( '' === $grams || ! $unit ) {
			$new = (float) $orig;
			$item->delete_meta_data( '_bg_weight_g' );
			$item->delete_meta_data( 'Mért súly' );
		} else {
			$new = round( $unit * $grams / 1000 );
			$item->update_meta_data( '_bg_weight_g', $grams );
			$item->update_meta_data( 'Mért súly', number_format( $grams, 0, ',', ' ' ) . ' g × ' . number_format( $unit, 0, ',', ' ' ) . ' Ft/kg' );
		}
		$item->set_subtotal( $new );
		$item->set_total( $new );
		$item->save();
		$changed[] = $item->get_name() . ': ' . ( '' === $grams ? 'eredeti ár' : $grams . ' g' ) . ' → ' . BG_Delivery::ft( $new );
	}
	if ( $changed ) {
		$before = (float) $order->get_total();
		$order->calculate_totals( false );
		$order->add_order_note( 'Súly szerinti elszámolás: ' . implode( '; ', $changed ) . '. Végösszeg: ' . BG_Delivery::ft( $before ) . ' → ' . BG_Delivery::ft( $order->get_total() ) . '.' );
		$order->save();
	}
}, 20 );

/* A belső mezők ne látszódjanak a vásárlónak (a „Mért súly” igen). */
add_filter( 'woocommerce_hidden_order_itemmeta', function ( $hidden ) {
	return array_merge( $hidden, array( '_bg_weight_g', '_bg_orig_total', '_bg_unit' ) );
} );

/* Tájékoztatás a pénztárban, ha van súly szerint számolt termék a kosárban. */
add_action( 'woocommerce_review_order_before_payment', function () {
	if ( ! WC()->cart ) {
		return;
	}
	foreach ( WC()->cart->get_cart() as $ci ) {
		if ( bg_is_weighted( $ci['product_id'] ) ) {
			echo '<p class="bg-weight-note" style="font-size:14px;margin:12px 0">A tőkehúsok ára a lemért súly szerint kis mértékben eltérhet: a végösszeget a kiszerelés tényleges súlya alapján, a feltüntetett kilóárral számoljuk.</p>';
			return;
		}
	}
} );
