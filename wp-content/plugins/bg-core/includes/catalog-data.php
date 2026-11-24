<?php
/**
 * Termékadatok a sablon JavaScriptjének (a demóból örökölt felület ugyanabban a
 * formában kapja a termékeket, mint a demó data.js-e), de a forrás már a WooCommerce:
 * ár, akciós ár, készlet, láthatóság az adminból szerkeszthető.
 */

defined( 'ABSPATH' ) || exit;

const BG_CATALOG_CACHE = 'bg_catalog_v1';

function bg_catalog_products() {
	$cached = get_transient( BG_CATALOG_CACHE );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$ids = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => -1,
			'orderby' => 'menu_order',
			'order'   => 'ASC',
			'return'  => 'ids',
		)
	);
	$out = array();
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p || 'hidden' === $p->get_catalog_visibility() ) {
			continue;
		}
		$slug  = $p->get_slug();
		$cats  = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
		$price = (float) $p->get_price();
		$reg   = (float) $p->get_regular_price();
		$item  = array(
			'id'    => $slug,
			'wid'   => $id,
			'name'  => $p->get_name(),
			'cat'   => $cats ? $cats[0] : '',
			'occ'   => array_values( (array) get_post_meta( $id, '_bg_occ', true ) ),
			'pack'  => (string) get_post_meta( $id, '_bg_pack', true ),
			'price' => $price,
			'unit'  => ( $u = get_post_meta( $id, '_bg_unit', true ) ) ? (float) $u : null,
			'img'   => (string) ( get_post_meta( $id, '_bg_img', true ) ?: $slug ),
			'url'   => get_permalink( $id ),
			'stock' => $p->is_in_stock(),
		);
		if ( $p->is_on_sale() && $reg > $price ) {
			$item['old'] = $reg;
		}
		foreach ( array( 'tag', 'ingr', 'allergen', 'variant', 'desc' ) as $k ) {
			$v = get_post_meta( $id, '_bg_' . $k, true );
			if ( '' !== $v && null !== $v ) {
				$item[ $k ] = $v;
			}
		}
		if ( get_post_meta( $id, '_bg_giftcard', true ) ) {
			$item['giftcard'] = true;
		}
		$out[] = $item;
	}
	set_transient( BG_CATALOG_CACHE, $out, DAY_IN_SECONDS );
	return $out;
}

/** A termékadatlapok (összetevők, tárolás, kivitel) a sablonnak. */
function bg_catalog_adatlap() {
	$out = array();
	foreach ( bg_catalog_products() as $p ) {
		$a = get_post_meta( $p['wid'], '_bg_adatlap', true );
		if ( is_array( $a ) && $a ) {
			$out[ $p['id'] ] = $a;
		}
	}
	return $out;
}

function bg_catalog_flush() {
	delete_transient( BG_CATALOG_CACHE );
}
add_action( 'save_post_product', 'bg_catalog_flush' );
add_action( 'woocommerce_update_product', 'bg_catalog_flush' );
add_action( 'woocommerce_product_set_stock_status', 'bg_catalog_flush' );
add_action( 'woocommerce_variation_set_stock_status', 'bg_catalog_flush' );
add_action( 'deleted_post', 'bg_catalog_flush' );
add_action( 'woocommerce_scheduled_sales', 'bg_catalog_flush', 20 );
