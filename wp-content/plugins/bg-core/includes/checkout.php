<?php
/**
 * Pénztár: település és kiszállítási nap kiválasztása, szerveroldali ellenőrzés
 * (határidő, járat, minimum, irányítószám), mentés a rendelésbe.
 */

defined( 'ABSPATH' ) || exit;

/** A pénztár mezői: csak magyar cím, a település a listából, plusz kiszállítási nap. */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	$towns   = array( '' => 'Válassz települést…' );
	foreach ( BG_Delivery::towns() as $t ) {
		$towns[ $t['name'] ] = $t['name'];
	}
	ksort( $towns, SORT_LOCALE_STRING );

	$session_town = WC()->session ? (string) WC()->session->get( 'bg_town' ) : '';
	$session_day  = WC()->session ? (string) WC()->session->get( 'bg_day' ) : '';

	$b = &$fields['billing'];
	unset( $b['billing_address_2'], $b['billing_state'] );
	$b['billing_country']['default'] = 'HU';
	$b['billing_last_name']['priority']  = 10;
	$b['billing_first_name']['priority'] = 20;
	$b['billing_company']['label']       = 'Cégnév (céges számlához)';
	$b['billing_postcode']['priority']   = 60;
	$b['billing_city']                  = array(
		'type'     => 'select',
		'label'    => 'Település',
		'required' => true,
		'options'  => $towns,
		'default'  => $session_town,
		'class'    => array( 'form-row-wide', 'bg-town' ),
		'priority' => 65,
	);
	$b['billing_address_1']['label']    = 'Utca, házszám';
	$b['billing_address_1']['priority'] = 70;
	$b['billing_vat_number']            = array(
		'type'     => 'text',
		'label'    => 'Adószám (céges számlához)',
		'required' => false,
		'class'    => array( 'form-row-wide' ),
		'priority' => 35,
	);

	$days = array( '' => 'Előbb válassz települést' );
	if ( $session_town && ( $t = BG_Delivery::town( $session_town ) ) ) {
		$days = bg_day_options( $t );
	}
	$fields['order']['bg_day'] = array(
		'type'        => 'select',
		'label'       => 'Kiszállítási nap',
		'required'    => true,
		'options'     => $days,
		'default'     => $session_day,
		'class'       => array( 'form-row-wide', 'bg-day' ),
		'priority'    => 5,
		'description' => 'Rendelés a kiszállítást megelőző napon 12 óráig. Ha nem vagy otthon, a csomagot a kapura akasztjuk, az ajtó elé tesszük vagy a szomszédnál hagyjuk.',
	);
	return $fields;
} );

/** A választható napok egy településhez: 'Y-m-d' => 'november 27., péntek · min. 5 000 Ft'. */
function bg_day_options( array $town ) {
	$out = array( '' => 'Válassz napot…' );
	foreach ( BG_Delivery::next_deliveries( $town, 6 ) as $d ) {
		$out[ $d['date'] ] = BG_Delivery::date_label( $d['date'] ) . ' · min. ' . BG_Delivery::ft( $d['min'] );
	}
	if ( 1 === count( $out ) ) {
		$out = array( '' => 'Jelenleg nincs meghirdetett járat' );
	}
	return $out;
}

/** Csak Magyarországra szállítunk; a szállítási cím a számlázási címmel egyezik. */
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );
add_filter( 'pre_option_woocommerce_ship_to_destination', function () {
	return 'billing_only';
} );
add_filter( 'pre_option_woocommerce_allowed_countries', function () {
	return 'specific';
} );
add_filter( 'pre_option_woocommerce_specific_allowed_countries', function () {
	return array( 'HU' );
} );

/** Napok a pénztár JavaScriptjének: település => [ [date, label], … ]. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	$map = array();
	foreach ( BG_Delivery::towns() as $t ) {
		$list = array();
		foreach ( BG_Delivery::next_deliveries( $t, 6 ) as $d ) {
			$list[] = array( $d['date'], BG_Delivery::date_label( $d['date'] ) . ' · min. ' . BG_Delivery::ft( $d['min'] ), $d['min'] );
		}
		$map[ $t['name'] ] = $list;
	}
	$sub = WC()->cart ? (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax() : 0;
	wp_register_script( 'bg-checkout', BG_CORE_URL . 'assets/checkout.js', array( 'jquery' ), BG_CORE_VERSION, true );
	wp_localize_script( 'bg-checkout', 'BG_DAYS', array( 'towns' => $map, 'subtotal' => $sub ) );
	wp_enqueue_script( 'bg-checkout' );
} );

/** Szerveroldali ellenőrzés: ez dönt, nem a böngésző. */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	$town = (string) ( $data['billing_city'] ?? '' );
	$day  = isset( $_POST['bg_day'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_day'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- a WooCommerce pénztár már ellenőrizte a nonce-ot
	$sub  = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();

	$err = BG_Delivery::validate( $town, $day, $sub );
	if ( $err ) {
		$errors->add( 'bg_delivery', $err );
		return;
	}

	$t   = BG_Delivery::town( $town );
	$zip = preg_replace( '/\D/', '', (string) ( $data['billing_postcode'] ?? '' ) );
	if ( $t && $zip ) {
		$ok = false;
		foreach ( $t['zips'] as $z ) {
			if ( $z === $zip || ( strlen( $z ) < 4 && 0 === strpos( $zip, $z ) ) ) {
				$ok = true;
				break;
			}
		}
		if ( ! $ok ) {
			$errors->add( 'bg_zip', 'Az irányítószám nem a választott településhez tartozik. Ellenőrizd a címet.' );
		}
	}
}, 10, 2 );

/** Mentés a rendelésbe. */
add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	$day = isset( $_POST['bg_day'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_day'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $day ) {
		$order->update_meta_data( '_bg_day', $day );
	}
	$order->update_meta_data( '_bg_town', $order->get_billing_city() );
	if ( ! empty( $_POST['billing_vat_number'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$order->update_meta_data( '_billing_vat_number', sanitize_text_field( wp_unslash( $_POST['billing_vat_number'] ) ) );
	}
	if ( WC()->session ) {
		WC()->session->__unset( 'bg_day' );
	}
} );

/** Megjelenítés: admin rendelés, köszönőoldal, e-mailek. */
function bg_order_delivery_line( $order ) {
	$day = $order->get_meta( '_bg_day' );
	if ( ! $day ) {
		return '';
	}
	return 'Kiszállítás: ' . BG_Delivery::date_label( $day ) . ' · ' . $order->get_meta( '_bg_town' );
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	$line = bg_order_delivery_line( $order );
	if ( $line ) {
		echo '<p><strong>' . esc_html( $line ) . '</strong></p>';
	}
	$vat = $order->get_meta( '_billing_vat_number' );
	if ( $vat ) {
		echo '<p>Adószám: ' . esc_html( $vat ) . '</p>';
	}
} );

add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	$line  = $order ? bg_order_delivery_line( $order ) : '';
	if ( $line ) {
		echo '<p class="bg-delivery-line"><strong>' . esc_html( $line ) . '</strong></p>';
	}
}, 5 );

add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin, $plain_text ) {
	$line = bg_order_delivery_line( $order );
	if ( ! $line ) {
		return;
	}
	echo $plain_text ? "\n" . esc_html( $line ) . "\n" : '<p><strong>' . esc_html( $line ) . '</strong></p>';
}, 10, 3 );

/** Rendeléslista az adminban: oszlop a kiszállítási nappal (HPOS és régi tárolás). */
foreach ( array( 'manage_woocommerce_page_wc-orders_columns', 'manage_edit-shop_order_columns' ) as $hook ) {
	add_filter( $hook, function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'order_status' === $k ) {
				$new['bg_day'] = 'Kiszállítás';
			}
		}
		return $new;
	} );
}
add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( $col, $order ) {
	if ( 'bg_day' === $col ) {
		$d = $order->get_meta( '_bg_day' );
		echo $d ? esc_html( BG_Delivery::date_label( $d ) . ' · ' . $order->get_meta( '_bg_town' ) ) : '–';
	}
}, 10, 2 );
