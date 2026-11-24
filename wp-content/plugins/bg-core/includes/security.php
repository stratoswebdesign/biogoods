<?php
/**
 * Biztonsági keményítés. A tárhely (Kinsta) adja a tűzfalat, DDoS-védelmet, a konténeres
 * elkülönítést, a napi mentést és a kártevőirtást; ez a réteg a WordPress oldali támadási
 * felületet csökkenti. A bankkártyaadat nem érinti a webshopot: a fizetés a SimplePay oldalán történik.
 */

defined( 'ABSPATH' ) || exit;

/* XML-RPC ki (jelszópróbálgatás és pingback-visszaélés gyakori célpontja). */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'xmlrpc_methods', '__return_empty_array' );

/* Alkalmazásjelszavak ki: nincs külső integráció, amelyik igényelné. */
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/* Felhasználónév-felderítés ellen: /?author=1 és a REST felhasználólista csak adminnak. */
add_action( 'template_redirect', function () {
	if ( ! is_admin() && isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! current_user_can( 'list_users' ) ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

/* Egységes hibaüzenet a belépésnél (ne derüljön ki, létezik-e a felhasználó). */
add_filter( 'login_errors', function () {
	return 'Hibás belépési adatok.';
} );

/* Verziószám elrejtése. */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* Biztonsági HTTP-fejlécek (a HSTS-t a tárhely/Cloudflare adja élesben). */
add_action( 'send_headers', function () {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
} );

/* Belépési kísérletek korlátozása (IP-nként 10 hibás próbálkozás / 15 perc). */
function bg_login_ip() {
	// Cloudflare mögött a valódi IP a CF-Connecting-IP fejlécben jön.
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
	return preg_replace( '/[^0-9a-fA-F:.]/', '', (string) $ip );
}
add_filter( 'authenticate', function ( $user ) {
	$key = 'bg_lf_' . md5( bg_login_ip() );
	if ( (int) get_transient( $key ) >= 10 ) {
		return new WP_Error( 'bg_locked', 'Túl sok sikertelen belépés. Próbáld újra 15 perc múlva.' );
	}
	return $user;
}, 30 );
add_action( 'wp_login_failed', function () {
	$key = 'bg_lf_' . md5( bg_login_ip() );
	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
} );

/* Erős jelszó a boltkezelőknek és adminoknak. */
add_action( 'user_profile_update_errors', function ( $errors, $update, $user ) {
	if ( empty( $_POST['pass1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$pass  = (string) wp_unslash( $_POST['pass1'] ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	$roles = isset( $user->role ) ? (array) $user->role : ( isset( $user->ID ) ? (array) get_userdata( $user->ID )->roles : array() );
	if ( array_intersect( $roles, array( 'administrator', 'shop_manager' ) ) && ( strlen( $pass ) < 14 || ! preg_match( '/[A-Za-z]/', $pass ) || ! preg_match( '/\d/', $pass ) ) ) {
		$errors->add( 'bg_weak', 'Adminisztrátornak és boltkezelőnek legalább 14 karakteres, betűt és számot is tartalmazó jelszó kell.' );
	}
}, 10, 3 );
