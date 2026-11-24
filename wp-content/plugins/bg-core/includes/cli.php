<?php
/**
 * WP-CLI parancsok a beállításhoz és az adatimporthoz.
 *
 *   wp bg setup                         – WooCommerce-beállítások, oldalak, szállítás, fizetési módok
 *   wp bg import-products <json> [--images=<mappa>]
 *   wp bg import-coupons <csv> [--campaign=xmas2026]
 */

defined( 'ABSPATH' ) || exit;

class BG_CLI {

	/**
	 * Alapbeállítások: pénznem, ország, oldalak, szállítási zóna, fizetési módok, fiókbeállítások.
	 */
	public function setup() {
		$opts = array(
			'woocommerce_currency'                       => 'HUF',
			'woocommerce_currency_pos'                   => 'right_space',
			'woocommerce_price_thousand_sep'             => ' ',
			'woocommerce_price_decimal_sep'              => ',',
			'woocommerce_price_num_decimals'             => 0,
			'woocommerce_default_country'                => 'HU',
			'woocommerce_store_address'                  => 'Aradi utca 22.',
			'woocommerce_store_city'                     => 'Gesztely',
			'woocommerce_store_postcode'                 => '3715',
			'woocommerce_allowed_countries'              => 'specific',
			'woocommerce_specific_allowed_countries'     => array( 'HU' ),
			'woocommerce_ship_to_countries'              => 'specific',
			'woocommerce_specific_ship_to_countries'     => array( 'HU' ),
			'woocommerce_calc_taxes'                     => 'no', // ÁFA-kulcsok: könyvelővel egyeztetendő, a számlázz.hu-bekötéssel együtt
			'woocommerce_prices_include_tax'             => 'yes',
			'woocommerce_enable_coupons'                 => 'yes',
			'woocommerce_calc_discounts_sequentially'    => 'no',
			'woocommerce_enable_guest_checkout'          => 'yes',
			'woocommerce_enable_checkout_login_reminder' => 'yes',
			'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
			'woocommerce_enable_myaccount_registration'  => 'yes',
			'woocommerce_registration_generate_password' => 'yes',
			'woocommerce_manage_stock'                   => 'yes',
			'woocommerce_hold_stock_minutes'             => 60,
			'woocommerce_weight_unit'                    => 'kg',
			'woocommerce_email_from_name'                => 'Bio-Goods Mangalica',
			'woocommerce_email_from_address'             => 'mangalica@bio-goods.hu',
			'woocommerce_feature_order_attribution_enabled' => 'no',
			'woocommerce_analytics_enabled'              => 'yes',
			'woocommerce_coming_soon'                    => 'no',
			'woocommerce_checkout_phone_field'           => 'required',
			'woocommerce_checkout_company_field'         => 'optional',
			'woocommerce_checkout_address_2_field'       => 'hidden',
			'blogdescription'                            => 'Mangalica hús és hústermékek a gesztelyi tanyáról, saját hűtött autós kiszállítással.',
			'default_comment_status'                     => 'closed',
			'default_ping_status'                        => 'closed',
			'users_can_register'                         => 0,
			'permalink_structure'                        => '/%postname%/',
		);
		foreach ( $opts as $k => $v ) {
			update_option( $k, $v );
		}

		// Termék-permalink: /termek/<slug>/, kategória: /kategoria/<slug>/
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/termek',
				'category_base'          => 'kategoria',
				'tag_base'               => 'cimke',
				'attribute_base'         => '',
				'use_verbose_page_rules' => false,
			)
		);

		// Oldalak: a demó oldalai (slug => [cím, sablon, tartalom])
		$pages = array(
			'fooldal'        => array( 'Főoldal', 'index', '' ),
			'termekek'       => array( 'Termékek', 'termekek', '' ),
			'kosar'          => array( 'Kosár', 'kosar', '' ),
			'penztar'        => array( 'Pénztár', 'woo', '[woocommerce_checkout]' ),
			'fiokom'         => array( 'Fiókom', 'woo', '[woocommerce_my_account]' ),
			'szallitas'      => array( 'Szállítás', 'szallitas', '' ),
			'fizetes'        => array( 'Fizetés', 'fizetes', '' ),
			'gyik'           => array( 'Gyakori kérdések', 'gyik', '' ),
			'kapcsolat'      => array( 'Kapcsolat', 'kapcsolat', '' ),
			'a-gazdasag'     => array( 'A gazdaság', 'a-gazdasag', '' ),
			'dijaink'        => array( 'Díjaink', 'dijaink', '' ),
			'receptek'       => array( 'Receptek', 'receptek', '' ),
			'recept'         => array( 'Recept', 'recept', '' ),
			'cegeknek'       => array( 'Cégeknek', 'cegeknek', '' ),
			'ceges-ajandek'  => array( 'Céges ajándék', 'ceges-ajandek', '' ),
			'varos'          => array( 'Kiszállítás településenként', 'varos', '' ),
			'aszf'           => array( 'Általános Szerződési Feltételek', 'aszf', '' ),
			'adatkezeles'    => array( 'Adatkezelési tájékoztató', 'woo', '<p>[Az adatkezelési tájékoztató szövegét a Selyemgubó adja – ide kerül.]</p>' ),
		);
		$ids = array();
		foreach ( $pages as $slug => $def ) {
			$existing = get_page_by_path( $slug );
			$id       = $existing ? $existing->ID : wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => $def[0],
					'post_content' => $def[2],
				)
			);
			update_post_meta( $id, '_bg_demo_page', $def[1] );
			$ids[ $slug ] = $id;
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['fooldal'] );
		update_option( 'woocommerce_shop_page_id', $ids['termekek'] );
		update_option( 'woocommerce_cart_page_id', $ids['kosar'] );
		update_option( 'woocommerce_checkout_page_id', $ids['penztar'] );
		update_option( 'woocommerce_myaccount_page_id', $ids['fiokom'] );
		update_option( 'woocommerce_terms_page_id', $ids['aszf'] );
		update_option( 'wp_page_for_privacy_policy', $ids['adatkezeles'] );

		// Szállítási zóna: Magyarország, Bio-Goods kiszállítás
		$zone_id = 0;
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			if ( 'Magyarország' === $z['zone_name'] ) {
				$zone_id = $z['id'];
			}
		}
		$zone = new WC_Shipping_Zone( $zone_id ?: null );
		if ( ! $zone_id ) {
			$zone->set_zone_name( 'Magyarország' );
			$zone->add_location( 'HU', 'country' );
			$zone->save();
		}
		$has = false;
		foreach ( $zone->get_shipping_methods() as $m ) {
			$has = $has || 'bg_delivery' === $m->id;
		}
		if ( ! $has ) {
			$zone->add_shipping_method( 'bg_delivery' );
		}

		// Fizetés: utánvét (készpénz vagy kártya a sofőrnél). A SimplePay a kulcsok után kerül be.
		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'            => 'yes',
				'title'              => 'Fizetés a sofőrnél (készpénz)',
				'description'        => 'A rendelés összegét kiszállításkor a sofőrnek fizeted.',
				'instructions'       => 'A rendelés összegét kiszállításkor a sofőrnek fizeted.',
				'enable_for_methods' => array(),
				'enable_for_virtual' => 'no',
			)
		);
		foreach ( array( 'bacs', 'cheque' ) as $g ) {
			update_option( "woocommerce_{$g}_settings", array( 'enabled' => 'no' ) );
		}

		flush_rewrite_rules();
		WP_CLI::success( 'Beállítások, oldalak (' . count( $ids ) . '), szállítás és fizetés kész.' );
	}

	/**
	 * Termékek importja a demó adataiból.
	 *
	 * ## OPTIONS
	 * <json>
	 * : A demó data.js-éből exportált JSON.
	 * [--images=<mappa>]
	 * : A demó assets/img mappája; ha megadod, a fotók a médiatárba kerülnek (kiemelt kép + galéria).
	 *
	 * @subcommand import-products
	 */
	public function import_products( $args, $assoc ) {
		$data = json_decode( file_get_contents( $args[0] ), true );
		if ( ! $data ) {
			WP_CLI::error( 'Nem olvasható a JSON.' );
		}
		$hidden = array_flip( $data['hidden'] ?? array() );
		$photos = $data['photos'] ?? array();
		$white  = array_flip( $data['whiteImg'] ?? array() );
		$imgdir = isset( $assoc['images'] ) ? rtrim( $assoc['images'], '/' ) : '';

		// kategóriák
		$cat_ids = array();
		foreach ( $data['categories'] as $i => $c ) {
			$t = get_term_by( 'slug', $c['id'], 'product_cat' );
			if ( ! $t ) {
				$r = wp_insert_term( $c['name'], 'product_cat', array( 'slug' => $c['id'], 'description' => $c['intro'] ?? '' ) );
				$id = is_wp_error( $r ) ? 0 : $r['term_id'];
			} else {
				$id = $t->term_id;
				wp_update_term( $id, 'product_cat', array( 'name' => $c['name'], 'description' => $c['intro'] ?? '' ) );
			}
			update_term_meta( $id, 'order', $i );
			$cat_ids[ $c['id'] ] = $id;
		}

		$n = 0;
		foreach ( $data['products'] as $order => $p ) {
			$existing = get_page_by_path( $p['id'], OBJECT, 'product' );
			$prod     = $existing ? wc_get_product( $existing->ID ) : new WC_Product_Simple();
			$prod->set_name( $p['name'] );
			$prod->set_slug( $p['id'] );
			$prod->set_status( isset( $hidden[ $p['id'] ] ) ? 'draft' : 'publish' );
			$prod->set_menu_order( $order );
			if ( ! empty( $p['old'] ) && $p['old'] > $p['price'] ) {
				$prod->set_regular_price( (string) $p['old'] );
				$prod->set_sale_price( (string) $p['price'] );
			} else {
				$prod->set_regular_price( (string) $p['price'] );
				$prod->set_sale_price( '' );
			}
			if ( isset( $cat_ids[ $p['cat'] ] ) ) {
				$prod->set_category_ids( array( $cat_ids[ $p['cat'] ] ) );
			}
			$desc = $p['desc'] ?? '';
			$prod->set_description( $desc );
			$prod->set_short_description( ( $p['pack'] ?? '' ) . ( ! empty( $p['unit'] ) ? ' · ' . number_format( $p['unit'], 0, ',', ' ' ) . ' Ft/kg' : '' ) );
			$prod->set_tax_status( 'taxable' );
			if ( ! empty( $p['giftcard'] ) ) {
				$prod->set_virtual( true );
			}
			$id = $prod->save();

			$A = $data['adatlap'][ $p['id'] ] ?? null;
			update_post_meta( $id, '_bg_occ', $p['occ'] ?? array() );
			update_post_meta( $id, '_bg_pack', $p['pack'] ?? '' );
			update_post_meta( $id, '_bg_unit', $p['unit'] ?? '' );
			update_post_meta( $id, '_bg_img', $p['img'] ?? $p['id'] );
			foreach ( array( 'tag', 'variant' ) as $k ) {
				update_post_meta( $id, '_bg_' . $k, $p[ $k ] ?? '' );
			}
			$ingr = $A ? $A['ossz'] . ( ! empty( $A['megj'] ) ? '. ' . $A['megj'] : '' ) : ( $p['ingr'] ?? '' );
			update_post_meta( $id, '_bg_ingr', $ingr );
			update_post_meta( $id, '_bg_allergen', $A['allerg'] ?? ( $p['allergen'] ?? '' ) );
			update_post_meta( $id, '_bg_desc', $desc );
			update_post_meta( $id, '_bg_giftcard', ! empty( $p['giftcard'] ) ? 1 : '' );
			update_post_meta( $id, '_bg_adatlap', $A ?: array() );
			update_post_meta( $id, '_bg_options', $A['opciok'] ?? array() );

			if ( $imgdir && ! $prod->get_image_id() ) {
				$this->attach_images( $prod, $p, $photos, $white, $imgdir );
			}
			$n++;
		}
		bg_catalog_flush();
		WP_CLI::success( "{$n} termék importálva (" . count( $hidden ) . ' piszkozatként, rejtve).' );
	}

	private function attach_images( WC_Product $prod, array $p, array $photos, array $white, $dir ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$img   = $p['img'] ?? $p['id'];
		$files = array();
		if ( ! empty( $photos[ $img ] ) ) {
			for ( $i = 1; $i <= (int) $photos[ $img ]; $i++ ) {
				$files[] = "{$dir}/foto/termek/{$img}" . ( $i > 1 ? "-{$i}" : '' ) . '.jpg';
			}
		} elseif ( isset( $white[ $img ] ) ) {
			$files[] = "{$dir}/termekek-feher/{$img}.jpg";
		} else {
			$files[] = "{$dir}/termekek/{$img}.jpg";
		}
		$ids = array();
		foreach ( $files as $i => $f ) {
			if ( ! file_exists( $f ) ) {
				continue;
			}
			$tmp = wp_tempnam( basename( $f ) );
			copy( $f, $tmp );
			$att = media_handle_sideload( array( 'name' => $p['id'] . ( $i ? '-' . ( $i + 1 ) : '' ) . '.jpg', 'tmp_name' => $tmp ), $prod->get_id(), $p['name'] );
			if ( ! is_wp_error( $att ) ) {
				update_post_meta( $att, '_wp_attachment_image_alt', $p['name'] );
				$ids[] = $att;
			}
		}
		if ( $ids ) {
			$prod->set_image_id( array_shift( $ids ) );
			$prod->set_gallery_image_ids( $ids );
			$prod->save();
		}
	}

	/**
	 * Karácsonyi kuponok importja (Sorszám;Kuponkód CSV).
	 *
	 * ## OPTIONS
	 * <csv>
	 * : A kuponkódok CSV-je (Sorszám;Kuponkód).
	 *
	 * [--campaign=<id>]
	 * : Kampányazonosító a „vásárlónként egyszer” szabályhoz. Alapértelmezés: xmas2026
	 *
	 * @subcommand import-coupons
	 */
	public function import_coupons( $args, $assoc ) {
		$campaign = $assoc['campaign'] ?? 'xmas2026';
		$rows     = array_map( function ( $l ) { return str_getcsv( $l, ';', '"', '' ); }, file( $args[0], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) );
		$n        = 0;
		foreach ( $rows as $r ) {
			$code = strtoupper( trim( preg_replace( '/^\xEF\xBB\xBF/', '', (string) ( $r[1] ?? '' ) ) ) );
			if ( ! preg_match( '/^BG10-[2-9A-HJKMNP-Z]{4}-[2-9A-HJKMNP-Z]{4}$/', $code ) ) {
				continue;
			}
			$c = new WC_Coupon( $code );
			if ( ! $c->get_id() ) {
				$c = new WC_Coupon();
				$c->set_code( $code );
			}
			$c->set_discount_type( 'percent' );
			$c->set_amount( 10 );
			$c->set_individual_use( true );       // más kuponnal nem vonható össze
			$c->set_exclude_sale_items( false );  // akciós termékekre is
			$c->set_minimum_amount( 15000 );      // kedvezmény előtti kosárérték
			$c->set_usage_limit( 1 );             // kódonként egyszer
			$c->set_usage_limit_per_user( 1 );
			$c->set_date_expires( strtotime( '2027-06-30 23:59:59 Europe/Budapest' ) );
			$c->set_description( 'Karácsonyi ajándékdobozos kártya 2026 (−10%, min. 15 000 Ft, 2027.06.30-ig)' );
			$id = $c->save();
			update_post_meta( $id, '_bg_campaign', $campaign );
			$n++;
		}
		WP_CLI::success( "{$n} kupon importálva ({$campaign})." );
	}
}

WP_CLI::add_command( 'bg', 'BG_CLI' );
