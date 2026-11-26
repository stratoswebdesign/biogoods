<?php
/**
 * Munkatársi admin: „Bio-Goods kezelő” szerepkör, szűkített menü és Mai nap áttekintő.
 *
 * Szerződés BG-2026-10: 05. tétel (napi járatlista), 17. tétel (admin-oktatás:
 * rendeléskezelés, termékfelvitel, járatlista), 2. sz. melléklet 9. fejezet („az admin
 * felületet arra szabjuk, aki naponta dolgozik vele”), 4.4. pont (a termékek, árak,
 * készletek kezelése a Megrendelőé; téma, bővítmények, beállítások egyeztetés nélkül nem).
 *
 * A kezelő a boltkezelő jogaiból indul, de nem fér hozzá a WooCommerce-beállításokhoz,
 * a témához, a bővítményekhez, a felhasználókhoz, a bloghoz és az oldalakhoz. A menük
 * elrejtése kényelmi funkció; a hozzáférést a jogok korlátozzák.
 */

defined( 'ABSPATH' ) || exit;

const BG_STAFF_ROLE         = 'bg_kezelo';
const BG_STAFF_CAPS_VERSION = '1';

/** A boltkezelő jogai közül ezeket NEM kapja meg a kezelő. */
function bg_staff_removed_caps() {
	return array(
		'manage_woocommerce', // WooCommerce-beállítások, állapot, kiterjesztések, Marketing, járatok és minimumok szerkesztése.
		'edit_theme_options',
		'list_users',
		'create_customers',
		'import',
		'export',
		'edit_posts', 'edit_pages', 'edit_published_posts', 'edit_published_pages', 'edit_private_pages', 'edit_private_posts',
		'edit_others_posts', 'edit_others_pages', 'publish_posts', 'publish_pages', 'delete_posts', 'delete_pages',
		'delete_private_pages', 'delete_private_posts', 'delete_published_pages', 'delete_published_posts',
		'delete_others_posts', 'delete_others_pages', 'read_private_pages', 'read_private_posts', 'manage_categories', 'manage_links',
		// Rendelés végleges törlése: csak lomtárba tehetnek.
		'delete_others_shop_orders', 'delete_published_shop_orders', 'delete_private_shop_orders',
		'level_9', 'level_8', 'level_7', 'level_6', 'level_5', 'level_4', 'level_3', 'level_2', 'level_1',
	);
}

function bg_staff_caps() {
	$base = get_role( 'shop_manager' );
	$caps = $base ? array_filter( $base->capabilities ) : array();
	foreach ( bg_staff_removed_caps() as $cap ) {
		unset( $caps[ $cap ] );
	}
	$caps['read']         = true;
	$caps['upload_files'] = true; // Termékképek.
	// Enélkül a WooCommerce (edit_posts és manage_woocommerce híján) a Fiókom oldalra irányítaná.
	$caps['view_admin_dashboard'] = true;
	return apply_filters( 'bg_staff_caps', $caps );
}

function bg_staff_install_role() {
	if ( ! get_role( 'shop_manager' ) && class_exists( 'WC_Install' ) ) {
		WC_Install::create_roles();
	}
	remove_role( BG_STAFF_ROLE );
	add_role( BG_STAFF_ROLE, 'Bio-Goods kezelő', bg_staff_caps() );
	update_option( 'bg_staff_caps_version', BG_STAFF_CAPS_VERSION, false );
}

/* A szerepkör létrehozása / frissítése (a GitHub-os frissítés nem futtat aktiválási hookot). */
add_action( 'init', function () {
	if ( get_option( 'bg_staff_caps_version' ) !== BG_STAFF_CAPS_VERSION || ! get_role( BG_STAFF_ROLE ) ) {
		bg_staff_install_role();
	}
} );

/** Igaz, ha a felhasználó kezelő (és nem adminisztrátor). */
function bg_is_staff( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && in_array( BG_STAFF_ROLE, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' );
}

/** A rendelések admin URL-je (HPOS vagy régi tároló). */
function bg_staff_orders_url( $args = array() ) {
	$hpos = class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	return add_query_arg( $args, admin_url( $hpos ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order' ) );
}

/* ---------- Menü ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'bg-route', 'Mai nap', 'Mai nap', 'edit_shop_orders', 'bg-today', 'bg_view_today', 0 );
}, 11 );

/* A kezelőnek csak a napi munkához szükséges menük. */
add_action( 'admin_menu', function () {
	if ( ! bg_is_staff() ) {
		return;
	}
	global $menu, $submenu;
	$own = isset( $submenu['bg-route'] ) ? $submenu['bg-route'] : array();
	$by  = array();
	foreach ( $own as $item ) {
		$by[ $item[2] ] = $item;
	}
	$new = array();
	foreach ( array( 'bg-today' ) as $slug ) {
		if ( isset( $by[ $slug ] ) ) {
			$new[] = $by[ $slug ];
		}
	}
	$new[] = array( 'Rendelések', 'edit_shop_orders', bg_staff_orders_url(), 'Rendelések' );
	foreach ( array( 'bg-route', 'bg-pick' ) as $slug ) {
		if ( isset( $by[ $slug ] ) ) {
			$new[] = $by[ $slug ];
		}
	}
	$new[] = array( 'Kuponok', 'edit_shop_coupons', admin_url( 'edit.php?post_type=shop_coupon' ), 'Kuponok' );
	$new[] = array( 'Riport', 'view_woocommerce_reports', admin_url( 'admin.php?page=wc-admin&path=/analytics/overview' ), 'Riport' );
	$submenu['bg-route'] = $new; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

	$keep = apply_filters( 'bg_staff_menus', array( 'bg-route', 'edit.php?post_type=product', 'profile.php' ) );
	foreach ( (array) $menu as $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( '' === $slug || in_array( $slug, $keep, true ) || ( isset( $item[4] ) && false !== strpos( (string) $item[4], 'wp-menu-separator' ) ) ) {
			continue;
		}
		remove_menu_page( $slug );
	}
}, 9999 );

/* Rendelések / kuponok oldalon a Bio-Goods menü legyen kiemelve. */
add_filter( 'parent_file', function ( $parent ) {
	if ( ! bg_is_staff() ) {
		return $parent;
	}
	$s = get_current_screen();
	return ( $s && in_array( $s->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order', 'edit-shop_coupon', 'shop_coupon' ), true ) ) ? 'bg-route' : $parent;
} );
add_filter( 'submenu_file', function ( $file, $parent ) {
	if ( ! bg_is_staff() || 'bg-route' !== $parent ) {
		return $file;
	}
	$s = get_current_screen();
	if ( $s && in_array( $s->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ), true ) ) {
		return bg_staff_orders_url();
	}
	if ( $s && in_array( $s->id, array( 'edit-shop_coupon', 'shop_coupon' ), true ) ) {
		return admin_url( 'edit.php?post_type=shop_coupon' );
	}
	return $file;
}, 10, 2 );

/* Felső sáv: a kezelőnek nem kell az „Új”, a hozzászólás és a WordPress-menü. */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( bg_is_staff() ) {
		foreach ( array( 'wp-logo', 'comments', 'new-content', 'updates', 'customize', 'search', 'woocommerce-site-visibility-badge' ) as $node ) {
			$bar->remove_node( $node );
		}
	}
}, 999 );

/* Vezérlőpult helyett és belépés után: Mai nap. */
add_action( 'load-index.php', function () {
	if ( bg_is_staff() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=bg-today' ) );
		exit;
	}
} );
add_filter( 'login_redirect', function ( $to, $requested, $user ) {
	if ( $user instanceof WP_User && bg_is_staff( $user ) ) {
		$admin = rtrim( admin_url(), '/' );
		if ( empty( $requested ) || rtrim( $requested, '/' ) === $admin || false !== strpos( $requested, 'wp-admin/index.php' ) ) {
			return admin_url( 'admin.php?page=bg-today' );
		}
	}
	return $to;
}, 20, 3 );
add_filter( 'woocommerce_login_redirect', function ( $to, $user ) {
	return ( $user instanceof WP_User && bg_is_staff( $user ) ) ? admin_url( 'admin.php?page=bg-today' ) : $to;
}, 20, 2 );

/* ---------- Mai nap ---------- */

function bg_view_today() {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( 'Nincs jogosultságod ehhez az oldalhoz.' );
	}
	$active  = wc_get_orders( array( 'limit' => 1000, 'status' => bg_active_statuses(), 'return' => 'objects' ) );
	$new     = array_filter( $active, function ( $o ) {
		return 'processing' === $o->get_status();
	} );
	$no_day  = array_filter( $active, function ( $o ) {
		return '' === (string) $o->get_meta( '_bg_day' );
	} );
	$days    = bg_order_days();
	$next    = bg_default_day( $days );
	$route   = bg_orders_for_day( $next );
	$cash    = array_sum( array_map( 'bg_cash_due', $route ) );
	$recent  = wc_get_orders( array( 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) );
	$low     = bg_staff_low_stock( 8 );
	$user    = wp_get_current_user();
	bg_admin_styles();
	?>
	<style>
		.bg-adm .bg-today-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:12px 0}
		.bg-adm a.bg-kpi{text-decoration:none;display:block}
		.bg-adm a.bg-kpi:hover,.bg-adm a.bg-kpi:focus{outline:2px solid #E3AF00;outline-offset:2px}
		.bg-adm .bg-kpi small{display:block;opacity:.75}
		.bg-adm .bg-cols{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:16px;margin-top:8px}
		@media (max-width:1100px){.bg-adm .bg-cols{grid-template-columns:1fr}}
		.bg-adm .bg-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:14px 16px}
		.bg-adm .bg-card h2{margin:0 0 10px;font-size:16px}
		.bg-adm .bg-warn{background:#fff8e1;border-left:4px solid #E3AF00;padding:8px 12px;margin:0 0 8px}
	</style>
	<div class="wrap bg-adm">
		<h1>Szia<?php echo $user->first_name ? ', ' . esc_html( $user->first_name ) : ''; ?>! <span class="bg-muted"><?php echo esc_html( BG_Delivery::date_label( BG_Delivery::now()->format( 'Y-m-d' ) ) ); ?></span></h1>
		<div class="bg-today-kpis">
			<a class="bg-kpi" href="<?php echo esc_url( bg_staff_orders_url( array( 'status' => 'wc-processing', 'post_status' => 'wc-processing' ) ) ); ?>">Feldolgozásra vár<b><?php echo (int) count( $new ); ?></b><small>rendelés</small></a>
			<a class="bg-kpi" href="<?php echo esc_url( admin_url( 'admin.php?page=bg-route&day=' . rawurlencode( $next ) ) ); ?>">Következő járat<b><?php echo (int) count( $route ); ?></b><small><?php echo esc_html( BG_Delivery::date_label( $next ) ); ?></small></a>
			<a class="bg-kpi" href="<?php echo esc_url( admin_url( 'admin.php?page=bg-route&day=' . rawurlencode( $next ) ) ); ?>">Beszedendő a járaton<b><?php echo esc_html( BG_Delivery::ft( $cash ) ); ?></b><small>utánvét</small></a>
		</div>
		<div class="bg-actions">
			<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=bg-route&day=' . rawurlencode( $next ) ) ); ?>">Járatlista</a>
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=bg-pick&day=' . rawurlencode( $next ) ) ); ?>">Komissiózó lista</a>
			<a class="button button-hero" href="<?php echo esc_url( bg_staff_orders_url() ); ?>">Összes rendelés</a>
			<?php if ( current_user_can( 'edit_products' ) ) : ?>
				<a class="button button-hero" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">Termékek és készlet</a>
			<?php endif; ?>
		</div>
		<div class="bg-cols">
			<section class="bg-card">
				<h2>Legutóbbi rendelések</h2>
				<?php if ( ! $recent ) : ?>
					<p class="bg-muted">Még nincs rendelés.</p>
				<?php else : ?>
					<table class="bg-table">
						<thead><tr><th>Rendelés</th><th>Vevő</th><th>Kiszállítás</th><th class="num">Összeg</th><th>Állapot</th></tr></thead>
						<tbody>
						<?php foreach ( $recent as $o ) : ?>
							<?php
							if ( ! $o instanceof WC_Order ) {
								continue;
							}
							$d = (string) $o->get_meta( '_bg_day' );
							?>
							<tr>
								<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><strong>#<?php echo esc_html( $o->get_order_number() ); ?></strong></a></td>
								<td><?php echo esc_html( trim( $o->get_billing_last_name() . ' ' . $o->get_billing_first_name() ) ); ?></td>
								<td><?php echo esc_html( $d ? BG_Delivery::date_label( $d ) . ' · ' . $o->get_meta( '_bg_town' ) : '–' ); ?></td>
								<td class="num"><?php echo esc_html( BG_Delivery::ft( $o->get_total() ) ); ?></td>
								<td><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
			<section class="bg-card">
				<h2>Figyelmet igényel</h2>
				<?php if ( $no_day ) : ?>
					<p class="bg-warn"><strong><?php echo (int) count( $no_day ); ?> rendelésnek nincs kiszállítási napja</strong>, ezért nem kerül járatlistára. Nyisd meg, és egyeztesd a napot.</p>
					<ul>
						<?php foreach ( array_slice( $no_day, 0, 6 ) as $o ) : ?>
							<li><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a> – <?php echo esc_html( trim( $o->get_billing_last_name() . ' ' . $o->get_billing_first_name() ) . ', ' . $o->get_billing_city() ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<h3>Alacsony készlet</h3>
				<?php if ( ! $low ) : ?>
					<p class="bg-muted">Nincs alacsony készletű termék (készletkövetéses termékek közül).</p>
				<?php else : ?>
					<ul>
						<?php foreach ( $low as $p ) : ?>
							<li><a href="<?php echo esc_url( get_edit_post_link( $p->get_id() ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a> – <strong><?php echo esc_html( (string) $p->get_stock_quantity() ); ?> db</strong><?php echo $p->get_stock_quantity() <= 0 ? ' (elfogyott)' : ''; ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		</div>
	</div>
	<?php
}

/** Készletkövetéses termékek az alacsonykészlet-küszöb alatt. */
function bg_staff_low_stock( $limit = 8 ) {
	$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
	$low       = array();
	foreach ( wc_get_products( array( 'limit' => 300, 'status' => 'publish', 'manage_stock' => true ) ) as $p ) {
		if ( null !== $p->get_stock_quantity() && $p->get_stock_quantity() <= $threshold ) {
			$low[] = $p;
		}
	}
	usort( $low, function ( $a, $b ) {
		return $a->get_stock_quantity() <=> $b->get_stock_quantity();
	} );
	return array_slice( $low, 0, $limit );
}
