<?php
/**
 * Admin: Eszközök → Bio-Goods telepítő.
 * Tárhelyen, ahol nincs parancssor, innen futtatható a beállítás, a termék- és a kuponimport,
 * valamint a sablon és a bővítmény frissítése a GitHub-ról (stratoswebdesign/biogoods, wordpress ág).
 * Csak adminisztrátor érheti el, minden művelet nonce-szal védett.
 */

defined( 'ABSPATH' ) || exit;

const BG_REPO_ZIP = 'https://codeload.github.com/stratoswebdesign/biogoods/zip/refs/heads/wordpress';

add_action( 'admin_menu', function () {
	add_management_page( 'Bio-Goods telepítő', 'Bio-Goods telepítő', 'manage_options', 'bg-installer', 'bg_installer_page' );
} );

function bg_installer_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	$msg = get_transient( 'bg_installer_msg' );
	delete_transient( 'bg_installer_msg' );
	$url = admin_url( 'admin-post.php' );
	echo '<div class="wrap"><h1>Bio-Goods telepítő</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-info"><p>' . wp_kses_post( $msg ) . '</p></div>';
	}
	$count = wp_count_posts( 'product' );
	echo '<p>Termékek: ' . (int) ( $count->publish ?? 0 ) . ' közzétett, ' . (int) ( $count->draft ?? 0 ) . ' piszkozat. Kuponok: ' . (int) wp_count_posts( 'shop_coupon' )->publish . '. Bővítmény: ' . esc_html( BG_CORE_VERSION ) . '.</p>';

	$btn = function ( $action, $label, $extra = '' ) use ( $url ) {
		echo '<form method="post" action="' . esc_url( $url ) . '" style="margin:12px 0">';
		wp_nonce_field( 'bg_inst_' . $action );
		echo '<input type="hidden" name="action" value="bg_inst_' . esc_attr( $action ) . '">' . $extra; // phpcs:ignore WordPress.Security.EscapeOutput
		submit_button( $label, 'primary', 'submit', false );
		echo '</form>';
	};
	echo '<h2>1. Frissítés a GitHub-ról</h2><p>Letölti a <code>wordpress</code> ág sablonját és bővítményét, és lecseréli a telepítettet.</p>';
	$btn( 'update', 'Sablon és bővítmény frissítése' );
	echo '<h2>2. Alapbeállítások</h2><p>Pénznem, ország, oldalak, szállítási zóna, utánvét, permalinkek.</p>';
	$btn( 'setup', 'Beállítások futtatása' );
	echo '<h2>3. Termékimport</h2><p>76 termék a demó adataiból, fotókkal (15-ösével, a végéig automatikusan továbblép).</p>';
	$btn( 'products', 'Termékek importálása' );
	echo '<h2>4. Kuponimport</h2><p>Soronként egy kód, vagy a CSV tartalma (Sorszám;Kuponkód).</p>';
	$btn( 'coupons', 'Kuponok importálása', '<textarea name="bg_codes" rows="6" cols="60" style="font-family:monospace"></textarea><br>' );
	echo '</div>';
}

function bg_installer_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	check_admin_referer( 'bg_inst_' . $action );
	@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}

function bg_installer_back( $msg, $args = array() ) {
	set_transient( 'bg_installer_msg', $msg, 300 );
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => 'bg-installer' ), $args ), admin_url( 'tools.php' ) ) );
	exit;
}

add_action( 'admin_post_bg_inst_setup', function () {
	bg_installer_guard( 'setup' );
	bg_installer_back( esc_html( ( new BG_Installer() )->setup() ) );
} );

add_action( 'admin_post_bg_inst_products', function () {
	bg_installer_guard( 'products' );
	$offset = isset( $_POST['bg_offset'] ) ? absint( $_POST['bg_offset'] ) : 0;
	$r      = ( new BG_Installer() )->import_products( BG_CORE_DIR . '/data/bg-data.json', get_template_directory() . '/assets/img', $offset, 15 );
	if ( is_wp_error( $r ) ) {
		bg_installer_back( esc_html( $r->get_error_message() ) );
	}
	if ( $r['next'] < $r['total'] ) {
		// következő adag: önmagát küldő űrlap új nonce-szal
		echo '<!doctype html><meta charset="utf-8"><p style="font:16px sans-serif">Termékimport: ' . (int) $r['next'] . ' / ' . (int) $r['total'] . '…</p>';
		echo '<form id="f" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bg_inst_products' );
		echo '<input type="hidden" name="action" value="bg_inst_products"><input type="hidden" name="bg_offset" value="' . (int) $r['next'] . '"></form><script>document.getElementById("f").submit();</script>';
		exit;
	}
	bg_installer_back( 'Termékimport kész: ' . (int) $r['total'] . ' termék.' );
} );

add_action( 'admin_post_bg_inst_coupons', function () {
	bg_installer_guard( 'coupons' );
	$text  = isset( $_POST['bg_codes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bg_codes'] ) ) : '';
	$lines = array_filter( array_map( 'trim', preg_split( '/\R/', $text ) ) );
	$n     = ( new BG_Installer() )->import_coupons( $lines );
	bg_installer_back( (int) $n . ' kupon importálva.' );
} );

add_action( 'admin_post_bg_inst_update', function () {
	bg_installer_guard( 'update' );
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	$tmp = download_url( BG_REPO_ZIP, 300 );
	if ( is_wp_error( $tmp ) ) {
		bg_installer_back( 'Letöltési hiba: ' . esc_html( $tmp->get_error_message() ) );
	}
	$dir = trailingslashit( get_temp_dir() ) . 'bg-update-' . wp_generate_password( 8, false );
	$res = unzip_file( $tmp, $dir );
	wp_delete_file( $tmp );
	if ( is_wp_error( $res ) ) {
		bg_installer_back( 'Kicsomagolási hiba: ' . esc_html( $res->get_error_message() ) );
	}
	$roots = glob( $dir . '/*', GLOB_ONLYDIR );
	$src   = $roots ? $roots[0] . '/wp-content' : '';
	$done  = array();
	foreach ( array( 'themes/bg-mangalica', 'plugins/bg-core' ) as $rel ) {
		if ( ! $src || ! is_dir( $src . '/' . $rel ) ) {
			continue;
		}
		$target = WP_CONTENT_DIR . '/' . $rel;
		$wp_filesystem->delete( $target . '.old', true );
		if ( is_dir( $target ) ) {
			$wp_filesystem->move( $target, $target . '.old', true );
		}
		$ok = copy_dir( $src . '/' . $rel, $target );
		if ( is_wp_error( $ok ) ) {
			$wp_filesystem->move( $target . '.old', $target, true ); // visszaállítás
			bg_installer_back( 'Másolási hiba (' . esc_html( $rel ) . '): ' . esc_html( $ok->get_error_message() ) );
		}
		$wp_filesystem->delete( $target . '.old', true );
		$done[] = $rel;
	}
	$wp_filesystem->delete( $dir, true );
	if ( function_exists( 'bg_catalog_flush' ) ) {
		bg_catalog_flush();
	}
	bg_installer_back( 'Frissítve: ' . esc_html( implode( ', ', $done ) ) . '.' );
} );
