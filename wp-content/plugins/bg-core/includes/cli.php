<?php
/**
 * WP-CLI parancsok.
 *
 *   wp bg setup
 *   wp bg import-products [<json>] [--images=<mappa>]
 *   wp bg import-coupons <csv> [--campaign=xmas2026]
 */

defined( 'ABSPATH' ) || exit;

class BG_CLI {

	/**
	 * WooCommerce-beállítások, oldalak, szállítási zóna, fizetési módok.
	 */
	public function setup() {
		WP_CLI::success( ( new BG_Installer() )->setup() );
	}

	/**
	 * Termékimport.
	 *
	 * ## OPTIONS
	 * [<json>]
	 * : A demó adatainak JSON-ja. Alapértelmezés: a bővítmény data/bg-data.json fájlja.
	 *
	 * [--images=<mappa>]
	 * : Képmappa (foto/termek, termekek, termekek-feher). Alapértelmezés: a sablon assets/img mappája.
	 *
	 * @subcommand import-products
	 */
	public function import_products( $args, $assoc ) {
		$json = $args[0] ?? BG_CORE_DIR . '/data/bg-data.json';
		$img  = $assoc['images'] ?? get_template_directory() . '/assets/img';
		$r    = ( new BG_Installer() )->import_products( $json, $img );
		is_wp_error( $r ) ? WP_CLI::error( $r->get_error_message() ) : WP_CLI::success( "{$r['done']} termék importálva." );
	}

	/**
	 * Kuponimport (Sorszám;Kuponkód CSV).
	 *
	 * ## OPTIONS
	 * <csv>
	 * : A kuponkódok CSV-je.
	 *
	 * [--campaign=<id>]
	 * : Kampányazonosító. Alapértelmezés: xmas2026
	 *
	 * @subcommand import-coupons
	 */
	public function import_coupons( $args, $assoc ) {
		$n = ( new BG_Installer() )->import_coupons( file( $args[0], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), $assoc['campaign'] ?? 'xmas2026' );
		WP_CLI::success( "{$n} kupon importálva." );
	}
}

WP_CLI::add_command( 'bg', 'BG_CLI' );
