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
	if ( $out ) {
		set_transient( BG_CATALOG_CACHE, $out, DAY_IN_SECONDS );
	}
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
	delete_transient( 'bg_catalog_hash' );
}
add_action( 'save_post_product', 'bg_catalog_flush' );
add_action( 'woocommerce_update_product', 'bg_catalog_flush' );
add_action( 'woocommerce_product_set_stock_status', 'bg_catalog_flush' );
add_action( 'woocommerce_variation_set_stock_status', 'bg_catalog_flush' );
add_action( 'deleted_post', 'bg_catalog_flush' );
add_action( 'woocommerce_scheduled_sales', 'bg_catalog_flush', 20 );

/** A sablon termékadat-szkriptje: tartalom-hash a verzió, így a böngésző és a CDN hosszan gyorsítótárazhatja. */
function bg_catalog_script_body() {
	$adatlap = (object) bg_catalog_adatlap();
	$js  = '(function(B,P,A){if(!P||!P.length)return;B.products=P;B.adatlap=Object.assign({},B.adatlap||{},A||{});';
	$js .= 'var ok={};P.forEach(function(p){ok[p.id]=1;});B.featured=(B.featured||[]).filter(function(i){return ok[i];});';
	$js .= '(B.recipes||[]).forEach(function(r){if(r.shop)r.shop=r.shop.filter(function(s){return ok[s[0]];});});})(window.BG,';
	$js .= wp_json_encode( bg_catalog_products() ) . ',' . wp_json_encode( $adatlap ) . ');';
	$js .= '(function(B,D){B.cutoffHour=D.cutoffHour;B.areas=D.areas;B.towns=D.towns.map(function(t){t.min=Math.min.apply(null,t.runs.map(function(r){return r.min;}));return t;});})(window.BG,' . wp_json_encode( BG_Delivery::data() ) . ');';
	return $js;
}

function bg_catalog_script_url() {
	$hash = get_transient( 'bg_catalog_hash' );
	if ( ! $hash ) {
		$hash = substr( md5( bg_catalog_script_body() ), 0, 12 );
		set_transient( 'bg_catalog_hash', $hash, DAY_IN_SECONDS );
	}
	return add_query_arg( 'bg_catalog', $hash, home_url( '/' ) );
}

add_action( 'init', function () {
	if ( empty( $_GET['bg_catalog'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$body = bg_catalog_script_body();
	$hash = substr( md5( $body ), 0, 12 );
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'X-Content-Type-Options: nosniff' );
	if ( sanitize_key( wp_unslash( $_GET['bg_catalog'] ) ) === $hash ) { // phpcs:ignore WordPress.Security.NonceVerification
		header( 'Cache-Control: public, max-age=31536000, immutable' );
	} else {
		header( 'Cache-Control: public, max-age=300' );
	}
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-kódolt adat
	exit;
}, 99 ); // a WooCommerce termék-típusai és taxonómiái már regisztrálva

/* Tartalomváltozáskor új hash (új URL), így a régi gyorsítótár nem számít. */
add_action( 'update_option_bg_towns', function () {
	delete_transient( 'bg_catalog_hash' );
} );
add_action( 'add_option_bg_towns', function () {
	delete_transient( 'bg_catalog_hash' );
} );
add_action( 'delete_option_bg_towns', function () {
	delete_transient( 'bg_catalog_hash' );
} );
