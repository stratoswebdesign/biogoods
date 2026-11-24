<?php
/**
 * 301-es átirányítások a régi (OneMin) bgmangalica.hu címekről az új oldalakra,
 * hogy a Google-helyezés és a mentett hivatkozások ne vesszenek el.
 *
 * A régi oldal felépítése (2026-11-24-i bejárás, www.bgmangalica.hu):
 *   /order, /order/kategoriak/<kategória>, /terulet, /rolunk, /dijaink,
 *   /legal/privacy-policy, /legal/terms-and-conditions — a termékeknek nem volt saját címe.
 */

defined( 'ABSPATH' ) || exit;

function bg_redirect_map() {
	$shop = '/termekek/';
	return array(
		'/order'                                 => $shop,
		'/order/kategoriak'                      => $shop,
		'/order/kategoriak/reggelire'            => $shop . '?alkalom=reggelire',
		'/order/kategoriak/uzsonnacsomag'        => '/kategoria/felvagottak/',
		'/order/kategoriak/akciok'               => $shop . '?alkalom=akciok',
		'/order/kategoriak/ajandek-otletek'      => '/kategoria/ajandek/',
		'/order/kategoriak/tokehusok'            => '/kategoria/tokehusok/',
		'/order/kategoriak/kerti-parti'          => $shop . '?alkalom=kerti-parti',
		'/order/kategoriak/szalonnak-fustoltek'  => '/kategoria/szalonnak/',
		'/order/kategoriak/klasszikusok'         => '/kategoria/hagyomanyos/',
		'/order/kategoriak/szalamik-kolbaszok'   => '/kategoria/kolbaszok/',
		'/terulet'                               => '/szallitas/',
		'/rolunk'                                => '/a-gazdasag/',
		'/legal/privacy-policy'                  => '/adatkezeles/',
		'/legal/terms-and-conditions'            => '/aszf/',
	);
}

add_action( 'template_redirect', function () {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = '/' . trim( strtolower( (string) $path ), '/' );
	// angol nyelvű régi címek (/en/...) a magyar megfelelőre
	if ( 0 === strpos( $path, '/en/' ) || '/en' === $path ) {
		$path = substr( $path, 3 ) ?: '/';
	}
	$map    = bg_redirect_map();
	$target = $map[ $path ] ?? null;
	if ( ! $target && 0 === strpos( $path, '/order/' ) ) {
		$target = '/termekek/'; // ismeretlen régi rendelési cím
	}
	if ( ! $target && '/' !== $path && is_404() && 0 === strpos( $path, '/en' ) ) {
		$target = '/';
	}
	if ( $target ) {
		wp_safe_redirect( home_url( $target ), 301, 'Bio-Goods' );
		exit;
	}
}, 1 );
