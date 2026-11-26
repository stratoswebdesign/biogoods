<?php
/**
 * Menük: a „Bio-Goods” főmenü mindenkinek, aki rendelést kezelhet, és a kezelő
 * szerepkörnek szűkített admin (csak a napi munkához szükséges menük).
 *
 * A menük elrejtése kényelmi funkció; a hozzáférést a szerepkör jogai korlátozzák.
 *
 * @package BG_Admin
 */

defined( 'ABSPATH' ) || exit;

class BG_Admin_Menu {

	const SLUG = 'bg-admin';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'restrict' ), 9999 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 999 );
		add_action( 'load-index.php', array( __CLASS__, 'redirect_dashboard' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 20, 3 );
		add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'wc_login_redirect' ), 20, 2 );
		add_filter( 'parent_file', array( __CLASS__, 'parent_file' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'submenu_file' ), 10, 2 );
	}

	/** A rendelések admin URL-je (HPOS vagy régi bejegyzéstípus). */
	public static function orders_url( $args = array() ) {
		$hpos = class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		$base = $hpos ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order';
		return add_query_arg( $args, admin_url( $base ) );
	}

	public static function register() {
		$cap = 'edit_shop_orders';
		add_menu_page( 'Bio-Goods – Mai nap', 'Bio-Goods', $cap, self::SLUG, array( 'BG_Admin_Pages', 'render_today' ), 'dashicons-clipboard', 2 );
		add_submenu_page( self::SLUG, 'Mai nap', 'Mai nap', $cap, self::SLUG, array( 'BG_Admin_Pages', 'render_today' ) );
		add_submenu_page( self::SLUG, 'Napi járatlista', 'Járatlista', $cap, 'bg-jaratlista', array( 'BG_Admin_Pages', 'render_route' ) );
		add_submenu_page( self::SLUG, 'Komissiózó lista', 'Komissiózó lista', $cap, 'bg-komissio', array( 'BG_Admin_Pages', 'render_pick' ) );
	}

	/** A kezelőnek csak a whitelistelt menüket hagyjuk meg, és a fontos WooCommerce-oldalakat a Bio-Goods menü alá linkeljük. */
	public static function restrict() {
		if ( ! BG_Admin_Role::is_kezelo() ) {
			return;
		}
		global $menu, $submenu;

		// Linkek a Bio-Goods menü alá, a napi munka sorrendjében. A termékek (és a készlet)
		// a saját „Termékek” főmenüjükben maradnak, mert ott van az új termék, a kategóriák és az értékelések is.
		$links = array(
			array( 'Rendelések', 'edit_shop_orders', self::orders_url() ),
			array( 'Kuponok', 'edit_shop_coupons', admin_url( 'edit.php?post_type=shop_coupon' ) ),
			array( 'Riport', 'view_woocommerce_reports', admin_url( 'admin.php?page=wc-admin&path=/analytics/overview' ) ),
		);
		$own   = isset( $submenu[ self::SLUG ] ) ? $submenu[ self::SLUG ] : array();
		$first = array_shift( $own ); // Mai nap.
		$new   = array( $first );
		$new[] = array( $links[0][0], $links[0][1], $links[0][2], $links[0][0] );
		foreach ( $own as $item ) { // Járatlista, Komissiózó lista.
			$new[] = $item;
		}
		foreach ( array_slice( $links, 1 ) as $l ) {
			$new[] = array( $l[0], $l[1], $l[2], $l[0] );
		}
		$submenu[ self::SLUG ] = array_values( array_filter( $new ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride

		$keep = (array) apply_filters( 'bg_admin_kezelo_menus', array( self::SLUG, 'edit.php?post_type=product', 'profile.php' ) );
		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( '' === $slug || in_array( $slug, $keep, true ) ) {
				continue;
			}
			if ( isset( $item[4] ) && false !== strpos( (string) $item[4], 'wp-menu-separator' ) ) {
				continue;
			}
			remove_menu_page( $slug );
		}
	}

	/** Felső sáv: a kezelőnek csak a webshop és a profil marad. */
	public static function admin_bar( $bar ) {
		if ( ! BG_Admin_Role::is_kezelo() ) {
			return;
		}
		foreach ( array( 'wp-logo', 'comments', 'new-content', 'updates', 'customize', 'search', 'archive', 'woocommerce-site-visibility-badge' ) as $node ) {
			$bar->remove_node( $node );
		}
	}

	/** A WordPress-vezérlőpult helyett a Mai nap oldal. */
	public static function redirect_dashboard() {
		if ( BG_Admin_Role::is_kezelo() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
		}
	}

	public static function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && BG_Admin_Role::is_kezelo( $user ) ) {
			$default = admin_url();
			if ( empty( $requested ) || $requested === $default || false !== strpos( $requested, 'wp-admin/index.php' ) || rtrim( $requested, '/' ) === rtrim( $default, '/' ) ) {
				return admin_url( 'admin.php?page=' . self::SLUG );
			}
		}
		return $redirect_to;
	}

	/** A Fiókom-oldali (WooCommerce) belépés után is a Mai nap. */
	public static function wc_login_redirect( $redirect, $user ) {
		if ( $user instanceof WP_User && BG_Admin_Role::is_kezelo( $user ) ) {
			return admin_url( 'admin.php?page=' . self::SLUG );
		}
		return $redirect;
	}

	/** A Rendelések / Kuponok / Riport oldalon a Bio-Goods menü legyen kiemelve a kezelőnek. */
	public static function parent_file( $parent_file ) {
		if ( ! BG_Admin_Role::is_kezelo() ) {
			return $parent_file;
		}
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order', 'edit-shop_coupon', 'shop_coupon' ), true ) ) {
			return self::SLUG;
		}
		return $parent_file;
	}

	public static function submenu_file( $submenu_file, $parent_file ) {
		if ( ! BG_Admin_Role::is_kezelo() || self::SLUG !== $parent_file ) {
			return $submenu_file;
		}
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ), true ) ) {
			return self::orders_url();
		}
		if ( $screen && in_array( $screen->id, array( 'edit-shop_coupon', 'shop_coupon' ), true ) ) {
			return admin_url( 'edit.php?post_type=shop_coupon' );
		}
		return $submenu_file;
	}
}
