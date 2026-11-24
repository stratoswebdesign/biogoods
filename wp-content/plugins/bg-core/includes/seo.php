<?php
/**
 * Keresők és közösségi megosztás:
 *  - Open Graph / Twitter-kártya (Facebook, Messenger, Viber előnézet)
 *  - morzsamenü-adat (BreadcrumbList) a termék- és kategóriaoldalakon
 *  - robots.txt: kosár, pénztár, fiók kizárva; oldaltérkép megadva
 *  - Google Merchant termékfeed: /?bg_feed=google
 */

defined( 'ABSPATH' ) || exit;

function bg_default_share_image() {
	return get_template_directory_uri() . '/assets/video/hero-poster.jpg';
}

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$title = wp_get_document_title();
	$desc  = get_bloginfo( 'description' );
	$url   = home_url( add_query_arg( array() ) );
	$img   = bg_default_share_image();
	$type  = 'website';
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p ) {
			$type = 'product';
			$url  = get_permalink( $p->get_id() );
			$desc = trim( $p->get_short_description() . '. ' . wp_strip_all_tags( $p->get_description() ?: (string) get_post_meta( $p->get_id(), '_bg_ingr', true ) ), '. ' );
			if ( $p->get_image_id() ) {
				$img = wp_get_attachment_image_url( $p->get_image_id(), 'large' );
			}
		}
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		$url  = get_term_link( $term );
		$desc = wp_strip_all_tags( term_description( $term ) ) ?: $desc;
	} elseif ( is_singular() ) {
		$url = get_permalink();
	}
	$tags = array(
		'og:locale'      => 'hu_HU',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:type'        => $type,
		'og:title'       => $title,
		'og:description' => wp_html_excerpt( $desc, 200, '…' ),
		'og:url'         => $url,
		'og:image'       => $img,
	);
	foreach ( $tags as $k => $v ) {
		if ( $v ) {
			echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
	if ( 'product' === $type && isset( $p ) && $p ) {
		echo '<meta property="product:price:amount" content="' . esc_attr( (string) $p->get_price() ) . '">' . "\n";
		echo '<meta property="product:price:currency" content="HUF">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	if ( is_singular() || ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_category' ) && is_product_category() ) ) {
		echo '<link rel="canonical" href="' . esc_url( strtok( $url, '?' ) ) . '">' . "\n";
	}

	// morzsamenü
	$crumbs = array( array( 'Főoldal', home_url( '/' ) ) );
	if ( isset( $p ) && $p && 'product' === $type ) {
		$cats = wp_get_post_terms( $p->get_id(), 'product_cat' );
		if ( $cats ) {
			$crumbs[] = array( $cats[0]->name, get_term_link( $cats[0] ) );
		}
		$crumbs[] = array( $p->get_name(), get_permalink( $p->get_id() ) );
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$crumbs[] = array( 'Termékek', wc_get_page_permalink( 'shop' ) );
		$crumbs[] = array( get_queried_object()->name, get_term_link( get_queried_object() ) );
	}
	if ( count( $crumbs ) > 1 ) {
		$items = array();
		foreach ( $crumbs as $i => $c ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 5 );

/* Az oldal canonicalja a fenti, a WordPress-é ne duplázódjon. */
remove_action( 'wp_head', 'rel_canonical' );

add_filter( 'robots_txt', function ( $out, $public ) {
	if ( ! $public ) {
		return $out;
	}
	$out .= "Disallow: /kosar/\nDisallow: /penztar/\nDisallow: /fiokom/\nDisallow: /*?add-to-cart=*\nDisallow: /*?bg_catalog=*\n";
	$out .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
	return $out;
}, 10, 2 );

/* ---------- Google Merchant feed ---------- */

/** Kiszerelésből egységár-mérték: '500 g' → [500, 'g'], '1,5 kg' → [1.5, 'kg']; darabos → null. */
function bg_pack_measure( $pack ) {
	if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(kg|g|dkg)\b/u', (string) $pack, $m ) ) {
		$n = (float) str_replace( ',', '.', $m[1] );
		if ( 'dkg' === $m[2] ) {
			return array( $n * 10, 'g' );
		}
		return array( $n, $m[2] );
	}
	return null;
}

function bg_merchant_feed() {
	$cached = get_transient( 'bg_merchant_feed' );
	if ( $cached ) {
		return $cached;
	}
	$x  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$x .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
	$x .= '<title>' . esc_xml( get_bloginfo( 'name' ) ) . '</title><link>' . esc_url( home_url( '/' ) ) . '</link><description>' . esc_xml( get_bloginfo( 'description' ) ) . '</description>';
	foreach ( bg_catalog_products() as $row ) {
		$p = wc_get_product( $row['wid'] );
		if ( ! $p || get_post_meta( $p->get_id(), '_bg_giftcard', true ) || ! $p->get_image_id() ) {
			continue;
		}
		$desc = trim( (string) get_post_meta( $p->get_id(), '_bg_desc', true ) . ' ' . (string) get_post_meta( $p->get_id(), '_bg_ingr', true ) );
		$x   .= '<item>';
		$x   .= '<g:id>' . esc_xml( $p->get_slug() ) . '</g:id>';
		$x   .= '<g:title>' . esc_xml( 'Mangalica ' === mb_substr( $p->get_name(), 0, 10 ) ? $p->get_name() : $p->get_name() . ' – mangalica' ) . '</g:title>';
		$x   .= '<g:description>' . esc_xml( wp_html_excerpt( $desc ?: $p->get_name(), 4900 ) ) . '</g:description>';
		$x   .= '<g:link>' . esc_url( get_permalink( $p->get_id() ) ) . '</g:link>';
		$x   .= '<g:image_link>' . esc_url( wp_get_attachment_image_url( $p->get_image_id(), 'full' ) ) . '</g:image_link>';
		foreach ( array_slice( $p->get_gallery_image_ids(), 0, 5 ) as $gid ) {
			$x .= '<g:additional_image_link>' . esc_url( wp_get_attachment_image_url( $gid, 'full' ) ) . '</g:additional_image_link>';
		}
		$x .= '<g:availability>' . ( $p->is_in_stock() ? 'in_stock' : 'out_of_stock' ) . '</g:availability>';
		$x .= '<g:price>' . esc_xml( number_format( (float) $p->get_regular_price(), 0, '.', '' ) ) . ' HUF</g:price>';
		if ( $p->is_on_sale() ) {
			$x .= '<g:sale_price>' . esc_xml( number_format( (float) $p->get_sale_price(), 0, '.', '' ) ) . ' HUF</g:sale_price>';
		}
		$m = bg_pack_measure( get_post_meta( $p->get_id(), '_bg_pack', true ) );
		if ( $m ) {
			$x .= '<g:unit_pricing_measure>' . esc_xml( rtrim( rtrim( number_format( $m[0], 2, '.', '' ), '0' ), '.' ) . ' ' . $m[1] ) . '</g:unit_pricing_measure>';
			$x .= '<g:unit_pricing_base_measure>1 kg</g:unit_pricing_base_measure>';
		}
		$cats = wp_get_post_terms( $p->get_id(), 'product_cat' );
		$x   .= '<g:product_type>' . esc_xml( $cats ? $cats[0]->name : 'Mangalica' ) . '</g:product_type>';
		$x   .= '<g:google_product_category>' . esc_xml( 'Food, Beverages & Tobacco > Food Items > Meat, Seafood & Eggs > Meat' ) . '</g:google_product_category>';
		$x   .= '<g:brand>Bio-Goods Mangalica</g:brand><g:condition>new</g:condition><g:identifier_exists>no</g:identifier_exists>';
		$x   .= '<g:shipping><g:country>HU</g:country><g:price>0 HUF</g:price></g:shipping>';
		$x   .= '</item>';
	}
	$x .= '</channel></rss>';
	set_transient( 'bg_merchant_feed', $x, HOUR_IN_SECONDS );
	return $x;
}

add_action( 'init', function () {
	if ( ! isset( $_GET['bg_feed'] ) || 'google' !== $_GET['bg_feed'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo bg_merchant_feed(); // phpcs:ignore WordPress.Security.EscapeOutput -- esc_xml-lel épített XML
	exit;
}, 99 );

add_action( 'woocommerce_update_product', function () {
	delete_transient( 'bg_merchant_feed' );
} );
