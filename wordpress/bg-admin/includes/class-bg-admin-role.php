<?php
/**
 * „Bio-Goods kezelő” szerepkör.
 *
 * A WooCommerce boltkezelő (shop_manager) jogaiból indul, de elveszi azt, amit a
 * szerződés 4.4. pontja szerint a Megrendelő egyeztetés nélkül nem módosíthat:
 * WooCommerce-beállítások, téma, bővítmények, felhasználók, blogbejegyzések és oldalak.
 * Marad: rendelések, termékek és készlet, kuponok, termékértékelések, riportok.
 *
 * @package BG_Admin
 */

defined( 'ABSPATH' ) || exit;

class BG_Admin_Role {

	/** A szerepkör-definíció verziója; ha változik, a jogokat újraszinkronizáljuk. */
	const CAPS_VERSION = '1';

	/**
	 * A shop_manager jogai közül ezeket NEM kapja meg a kezelő.
	 *
	 * @var string[]
	 */
	const REMOVED_CAPS = array(
		// WooCommerce-beállítások, állapot, bővítmények oldal, Marketing menü.
		'manage_woocommerce',
		// Téma, menük, widgetek.
		'edit_theme_options',
		// Felhasználók.
		'list_users',
		'create_customers',
		// Import / export (WordPress-eszközök).
		'import',
		'export',
		// Blog és oldalak – a tartalmat a Stratos kezeli (SEO, 3.4. pont).
		'edit_posts',
		'edit_pages',
		'edit_published_posts',
		'edit_published_pages',
		'edit_private_pages',
		'edit_private_posts',
		'edit_others_posts',
		'edit_others_pages',
		'publish_posts',
		'publish_pages',
		'delete_posts',
		'delete_pages',
		'delete_private_pages',
		'delete_private_posts',
		'delete_published_pages',
		'delete_published_posts',
		'delete_others_posts',
		'delete_others_pages',
		'read_private_pages',
		'read_private_posts',
		'manage_categories',
		'manage_links',
		// Rendelések végleges törlése – csak lomtárba tehetnek, véglegesen a Stratos töröl.
		'delete_others_shop_orders',
		'delete_published_shop_orders',
		'delete_private_shop_orders',
		// Régi szint-jogok, amelyeket egyes bővítmények adminjogként értelmeznek.
		'level_9',
		'level_8',
		'level_7',
		'level_6',
		'level_5',
		'level_4',
		'level_3',
		'level_2',
		'level_1',
	);

	public static function init() {
		if ( get_option( 'bg_admin_caps_version' ) !== self::CAPS_VERSION ) {
			self::install();
		}
	}

	/**
	 * A kezelő jogai: shop_manager mínusz REMOVED_CAPS.
	 *
	 * @return array<string,bool>
	 */
	public static function caps() {
		$base = get_role( 'shop_manager' );
		$caps = $base ? array_filter( $base->capabilities ) : array( 'read' => true );
		foreach ( self::REMOVED_CAPS as $cap ) {
			unset( $caps[ $cap ] );
		}
		$caps['read']         = true;
		$caps['upload_files'] = true; // Termékképek feltöltése.
		// A WooCommerce ezen jog nélkül (edit_posts és manage_woocommerce hiányában)
		// a Fiókom oldalra irányítaná a felhasználót, és elrejtené a felső sávot.
		$caps['view_admin_dashboard'] = true;
		/**
		 * A kezelő szerepkör jogainak utólagos módosítása.
		 *
		 * @param array<string,bool> $caps
		 */
		return apply_filters( 'bg_admin_role_caps', $caps );
	}

	public static function install() {
		if ( ! get_role( 'shop_manager' ) && class_exists( 'WC_Install' ) ) {
			WC_Install::create_roles();
		}
		remove_role( BG_ADMIN_ROLE );
		add_role( BG_ADMIN_ROLE, 'Bio-Goods kezelő', self::caps() );
		update_option( 'bg_admin_caps_version', self::CAPS_VERSION, false );
	}

	/**
	 * Kikapcsoláskor a szerepkör megmarad, hogy a felhasználók ne veszítsék el;
	 * csak a verziójelzőt töröljük, hogy újraaktiváláskor friss jogokat kapjon.
	 */
	public static function uninstall() {
		delete_option( 'bg_admin_caps_version' );
	}

	/** Igaz, ha az aktuális felhasználó a kezelő szerepkörben van (és nem adminisztrátor). */
	public static function is_kezelo( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		return in_array( BG_ADMIN_ROLE, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' );
	}
}
