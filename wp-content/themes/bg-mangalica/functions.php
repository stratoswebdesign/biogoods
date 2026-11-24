<?php
/**
 * Bio-Goods Mangalica sablon.
 *
 * A jóváhagyott demó (stratoswebdesign.github.io/biogoods) felülete egy az egyben: ugyanaz a CSS és
 * a felületet kirajzoló JavaScript. A termékek, a kosár véglegesítése, a pénztár, a fizetés, a
 * rendelések és a fiókok a WooCommerce-ben futnak (bg-core bővítmény).
 */

defined( 'ABSPATH' ) || exit;

define( 'BG_THEME_VER', '0.1.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );

/** A demó oldalsablonjai: demo-pages/<név>.html. */
function bg_demo_template_path( $name ) {
	$name = preg_replace( '/[^a-z0-9-]/', '', (string) $name );
	$file = get_template_directory() . '/demo-pages/' . $name . '.html';
	return $name && is_readable( $file ) ? $file : '';
}

/** Melyik demóoldal tartozik a mostani kéréshez (null: WooCommerce/WordPress tartalom). */
function bg_current_demo() {
	static $cur = false;
	if ( false !== $cur ) {
		return $cur;
	}
	$cur = null;
	if ( function_exists( 'is_product' ) && is_product() ) {
		$cur = 'termek';
	} elseif ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() ) ) {
		$cur = 'termekek';
	} elseif ( is_front_page() ) {
		$cur = 'index';
	} elseif ( is_page() ) {
		$tpl = get_post_meta( get_queried_object_id(), '_bg_demo_page', true );
		$cur = ( $tpl && 'woo' !== $tpl && bg_demo_template_path( $tpl ) ) ? $tpl : null;
	} elseif ( is_404() ) {
		$cur = '404';
	}
	return $cur;
}

/** A demó HTML-fájl részei: body-attribútumok, body-tartalom (szkriptek nélkül), cím, leírás. */
function bg_demo_parts( $name ) {
	$file = bg_demo_template_path( $name );
	if ( ! $file ) {
		return null;
	}
	$html = file_get_contents( $file );
	$out  = array( 'attrs' => array(), 'body' => '', 'title' => '', 'desc' => '' );
	if ( preg_match( '#<title>(.*?)</title>#s', $html, $m ) ) {
		$out['title'] = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}
	if ( preg_match( '#<meta name="description" content="([^"]*)"#', $html, $m ) ) {
		$out['desc'] = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	}
	if ( preg_match( '#<body([^>]*)>(.*)</body>#s', $html, $m ) ) {
		preg_match_all( '#\s(data-[a-z-]+)="([^"]*)"#', $m[1], $am, PREG_SET_ORDER );
		foreach ( $am as $a ) {
			$out['attrs'][ $a[1] ] = $a[2];
		}
		$body        = preg_replace( '#<script\b[^>]*>.*?</script>#s', '', $m[2] );
		$body        = preg_replace( '#<span class="demo-badge">DEMO</span>\s*#', '', $body );
		$out['body'] = bg_rewrite_links( $body );
	}
	return $out;
}

/** A demó relatív hivatkozásai WordPress-címekre (oldalak) és a sablon mappájára (képek, videók). */
function bg_route( $path ) {
	$hash = '';
	$qs   = '';
	if ( false !== ( $h = strpos( $path, '#' ) ) ) {
		$hash = substr( $path, $h );
		$path = substr( $path, 0, $h );
	}
	if ( false !== ( $q = strpos( $path, '?' ) ) ) {
		$qs   = substr( $path, $q + 1 );
		$path = substr( $path, 0, $q );
	}
	$name = preg_replace( '/\.html$/', '', $path );
	if ( 'termek' === $name ) {
		parse_str( $qs, $args );
		if ( ! empty( $args['id'] ) ) {
			$post = get_page_by_path( sanitize_title( $args['id'] ), OBJECT, 'product' );
			return ( $post ? get_permalink( $post ) : home_url( '/termek/' . rawurlencode( $args['id'] ) . '/' ) ) . $hash;
		}
	}
	if ( 'index' === $name || '' === $name ) {
		return home_url( '/' ) . ( $qs ? '?' . $qs : '' ) . $hash;
	}
	if ( 0 === strpos( $name, 'admin/' ) ) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	$map = bg_page_map();
	$url = $map[ $name ] ?? home_url( '/' . $name . '/' );
	return $url . ( $qs ? '?' . $qs : '' ) . $hash;
}

function bg_page_map() {
	$map = array(
		'termekek'  => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/termekek/' ),
		'kosar'     => home_url( '/kosar/' ),
		'penztar'   => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/penztar/' ),
		'fiokom'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/fiokom/' ),
		'koszonjuk' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/fiokom/' ),
	);
	return $map;
}

function bg_rewrite_links( $html ) {
	$assets = get_template_directory_uri() . '/assets/';
	$html   = preg_replace( '#(src|href|poster|srcset)="assets/#', '$1="' . $assets, $html );
	return preg_replace_callback(
		'#href="((?!https?:|mailto:|tel:|\#|/)[a-z0-9/_-]+\.html(?:[?\#][^"]*)?)"#i',
		function ( $m ) {
			return 'href="' . esc_url( bg_route( html_entity_decode( $m[1] ) ) ) . '"';
		},
		$html
	);
}

/** Cím és leírás a demóoldalakhoz; terméknél a termék neve. */
add_filter( 'pre_get_document_title', function ( $title ) {
	$demo = bg_current_demo();
	if ( 'termek' === $demo || ( function_exists( 'is_product_category' ) && is_product_category() ) ) {
		return '';
	}
	if ( $demo && ( $p = bg_demo_parts( $demo ) ) && $p['title'] ) {
		return $p['title'];
	}
	return $title;
} );
add_filter( 'document_title_separator', function () {
	return '–';
} );

add_action( 'wp_head', function () {
	$desc = '';
	if ( function_exists( 'is_product' ) && is_product() ) {
		$prod = wc_get_product( get_queried_object_id() );
		if ( $prod ) {
			$desc = wp_strip_all_tags( $prod->get_description() ?: get_post_meta( $prod->get_id(), '_bg_ingr', true ) );
			$desc = $prod->get_name() . ' – ' . $prod->get_short_description() . ( $desc ? '. ' . $desc : '' );
		}
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$desc = term_description();
	} elseif ( ( $d = bg_current_demo() ) && ( $p = bg_demo_parts( $d ) ) ) {
		$desc = $p['desc'];
	}
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_html_excerpt( wp_strip_all_tags( $desc ), 300, '…' ) ) . '">' . "\n";
	}
	echo '<link rel="icon" href="' . esc_url( get_template_directory_uri() . '/assets/img/site/logo.png' ) . '">' . "\n";
	if ( is_checkout() || is_account_page() || is_page( 'kosar' ) ) {
		echo '<meta name="robots" content="noindex">' . "\n";
	}
}, 1 );

/** Stílusok és szkriptek. */
add_action( 'wp_enqueue_scripts', function () {
	$uri = get_template_directory_uri() . '/assets';
	$dir = get_template_directory() . '/assets';
	$ver = function ( $f ) use ( $dir ) {
		return BG_THEME_VER . '.' . ( @filemtime( $dir . $f ) ?: 0 );
	};

	// Betűtípusok: TODO élesítés előtt saját tárhelyre (GDPR: ne a Google szerveréről töltődjön)
	wp_enqueue_style( 'bg-fonts', 'https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600&family=Manrope:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'bg-styles', $uri . '/css/styles.css', array(), $ver( '/css/styles.css' ) );
	wp_enqueue_style( 'bg-wp', $uri . '/css/bg-wp.css', array( 'bg-styles' ), $ver( '/css/bg-wp.css' ) );

	// A WordPress blokkstílusai nem kellenek; a WooCommerce stílusai csak a pénztárban és a fiókban.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
	if ( ! is_checkout() && ! is_account_page() ) {
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'wc-blocks-style' );
	}

	$cfg = array(
		'home'          => home_url( '/' ),
		'assets'        => $uri . '/',
		'ajax'          => admin_url( 'admin-ajax.php' ),
		'checkout'      => wc_get_checkout_url(),
		'admin'         => admin_url( 'admin.php?page=wc-orders' ),
		'pages'         => bg_page_map(),
		'purl'          => array(),
		'orderReceived' => is_wc_endpoint_url( 'order-received' ),
		'isCheckout'    => is_checkout(),
	);
	$products = function_exists( 'bg_catalog_products' ) ? bg_catalog_products() : array();
	foreach ( $products as $p ) {
		$cfg['purl'][ $p['id'] ] = $p['url'];
	}

	wp_enqueue_script( 'bg-data', $uri . '/js/data.js', array(), $ver( '/js/data.js' ), true );
	wp_add_inline_script(
		'bg-data',
		'window.BG_WP_CFG=' . wp_json_encode( $cfg ) . ';' .
		// élő termékadatok a WooCommerce-ből (ár, akció, készlet, láthatóság)
		'(function(B,P,A){if(!P||!P.length)return;B.products=P;B.adatlap=Object.assign({},B.adatlap||{},A||{});' .
		'var ok={};P.forEach(function(p){ok[p.id]=1;});B.featured=(B.featured||[]).filter(function(i){return ok[i];});' .
		'(B.recipes||[]).forEach(function(r){if(r.shop)r.shop=r.shop.filter(function(s){return ok[s[0]];});});})(window.BG,' .
		wp_json_encode( $products ) . ',' . wp_json_encode( function_exists( 'bg_catalog_adatlap' ) ? (object) bg_catalog_adatlap() : new stdClass() ) . ');',
		'after'
	);
	wp_enqueue_script( 'bg-wp', $uri . '/js/bg-wp.js', array( 'bg-data' ), $ver( '/js/bg-wp.js' ), true );
	if ( bg_current_demo() || is_checkout() || is_account_page() ) {
		wp_enqueue_script( 'bg-app', $uri . '/js/app.js', array( 'bg-wp' ), $ver( '/js/app.js' ), true );
	}
}, 20 );

/** A body-attribútumok (data-page, data-nav, data-id, data-cat) a demó szerint. */
function bg_body_attrs() {
	$demo  = bg_current_demo();
	$attrs = array();
	if ( $demo && ( $p = bg_demo_parts( $demo ) ) ) {
		$attrs = $p['attrs'];
	} elseif ( is_checkout() || is_account_page() ) {
		$attrs = array( 'data-page' => 'woo', 'data-nav' => '' );
	}
	if ( 'termek' === $demo ) {
		$attrs['data-id'] = get_post_field( 'post_name', get_queried_object_id() );
	}
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$attrs['data-cat'] = get_queried_object()->slug;
	}
	$out = '';
	foreach ( $attrs as $k => $v ) {
		$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $out;
}

/** Strukturált termékadat (Google): a WooCommerce saját generátorával, a sablon nem futtatja az alapsablont. */
add_action( 'wp', function () {
	if ( function_exists( 'is_product' ) && is_product() && isset( WC()->structured_data ) ) {
		$prod = wc_get_product( get_queried_object_id() );
		if ( $prod ) {
			WC()->structured_data->generate_product_data( $prod );
		}
	}
} );

/** Az admin sáv elrejtése a vásárlóknak. */
add_filter( 'show_admin_bar', function ( $show ) {
	return $show && current_user_can( 'edit_shop_orders' );
} );

/** A böngésző ne töltse elő a pénztárat, a kosarat és a fiókot (az előtöltött átirányítás üres kosarat mutatna). */
add_filter( 'wp_speculation_rules_href_exclude_paths', function ( $paths ) {
	return array_merge( $paths, array( '/penztar/*', '/penztar', '/kosar/*', '/fiokom/*', '/*\\?*add-to-cart=*' ) );
} );
