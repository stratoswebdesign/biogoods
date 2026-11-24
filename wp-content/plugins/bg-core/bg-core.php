<?php
/**
 * Plugin Name: Bio-Goods Mangalica – webshop mag
 * Description: Kiszállítási települések és járatnapok, rendelési határidő és minimum, kivitel (szeletelve/egészben), karácsonyi kuponszabályok, biztonsági beállítások.
 * Version: 0.2.0
 * Author: Stratos Webdesign
 * Requires Plugins: woocommerce
 * Text Domain: bg-core
 */

defined( 'ABSPATH' ) || exit;

define( 'BG_CORE_VERSION', '0.3.0' );
define( 'BG_CORE_DIR', __DIR__ );
define( 'BG_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once BG_CORE_DIR . '/includes/delivery.php';
require_once BG_CORE_DIR . '/includes/cart.php';
require_once BG_CORE_DIR . '/includes/checkout.php';
require_once BG_CORE_DIR . '/includes/coupons.php';
require_once BG_CORE_DIR . '/includes/security.php';
require_once BG_CORE_DIR . '/includes/catalog-data.php';
require_once BG_CORE_DIR . '/includes/installer.php';
require_once BG_CORE_DIR . '/includes/tracking.php';
require_once BG_CORE_DIR . '/includes/weight.php';
require_once BG_CORE_DIR . '/includes/redirects.php';
require_once BG_CORE_DIR . '/includes/giftcards.php';
require_once BG_CORE_DIR . '/includes/seo.php';

if ( is_admin() ) {
	require_once BG_CORE_DIR . '/includes/admin.php';
	require_once BG_CORE_DIR . '/includes/admin-views.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BG_CORE_DIR . '/includes/cli.php';
}
