<?php
/**
 * Plugin Name:       Bio-Goods Admin
 * Description:       A Bio-Goods Mangalica webshop napi kezelőfelülete: „Bio-Goods kezelő” szerepkör szűkített menüvel, Mai nap áttekintő, napi járatlista a sofőrnek (szerződés 05. tétel) és komissiózó lista.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Stratos Webdesign
 * Author URI:        https://stratosweb.hu
 * Text Domain:       bg-admin
 *
 * @package BG_Admin
 */

defined( 'ABSPATH' ) || exit;

define( 'BG_ADMIN_VERSION', '1.0.0' );
define( 'BG_ADMIN_FILE', __FILE__ );
define( 'BG_ADMIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BG_ADMIN_URL', plugin_dir_url( __FILE__ ) );

/** A kezelő szerepkör azonosítója. */
define( 'BG_ADMIN_ROLE', 'bg_kezelo' );

require_once BG_ADMIN_DIR . 'includes/class-bg-admin-role.php';
require_once BG_ADMIN_DIR . 'includes/class-bg-admin-orders.php';
require_once BG_ADMIN_DIR . 'includes/class-bg-admin-menu.php';
require_once BG_ADMIN_DIR . 'includes/class-bg-admin-pages.php';

// HPOS (egyedi rendeléstáblák) kompatibilitás bejelentése.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', BG_ADMIN_FILE, true );
		}
	}
);

register_activation_hook( __FILE__, array( 'BG_Admin_Role', 'install' ) );
register_deactivation_hook( __FILE__, array( 'BG_Admin_Role', 'uninstall' ) );

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		BG_Admin_Role::init();
		BG_Admin_Menu::init();
		BG_Admin_Pages::init();
	}
);
