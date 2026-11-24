<?php
/**
 * Admin nézetek a napi munkához (a demó admin/ oldalai alapján):
 *  - Járatlista: a kiválasztott kiszállítási nap rendelései településenként, nyomtatható, CSV, „Kiszállítva” jelölés
 *  - Komissiózó lista: ugyanannak a napnak az összesítése termékenként és kivitelenként
 *  - Járatok és minimumok: a települések, járatnapok, minimumok, irányítószámok szerkesztése
 *
 * Jogosultság: boltkezelő és adminisztrátor (edit_shop_orders). A szerkesztés: manage_woocommerce.
 */

defined( 'ABSPATH' ) || exit;

const BG_VIEW_CAP = 'edit_shop_orders';

add_action( 'admin_menu', function () {
	add_menu_page( 'Járatlista', 'Bio-Goods', BG_VIEW_CAP, 'bg-route', 'bg_view_route', 'dashicons-car', 56 );
	add_submenu_page( 'bg-route', 'Járatlista', 'Járatlista', BG_VIEW_CAP, 'bg-route', 'bg_view_route' );
	add_submenu_page( 'bg-route', 'Komissiózó lista', 'Komissiózó lista', BG_VIEW_CAP, 'bg-pick', 'bg_view_pick' );
	add_submenu_page( 'bg-route', 'Járatok és minimumok', 'Járatok és minimumok', 'manage_woocommerce', 'bg-zones', 'bg_view_zones' );
} );

/* ---------- közös ---------- */

/** Az aktív (kiszállítandó) rendelésállapotok. */
function bg_active_statuses() {
	return array( 'processing', 'on-hold' );
}

/** Kiszállítási napok, amelyekre van rendelés (az elmúlt 30 naptól előre), a legközelebbivel elöl. */
function bg_order_days() {
	$orders = wc_get_orders(
		array(
			'limit'        => 2000,
			'status'       => array( 'processing', 'on-hold', 'completed' ),
			'date_created' => '>' . ( time() - 90 * DAY_IN_SECONDS ),
			'return'       => 'objects',
		)
	);
	$days = array();
	foreach ( $orders as $o ) {
		$d = $o->get_meta( '_bg_day' );
		if ( $d ) {
			$days[ $d ] = true;
		}
	}
	$days  = array_keys( $days );
	sort( $days );
	$today = BG_Delivery::now()->format( 'Y-m-d' );
	$from  = gmdate( 'Y-m-d', strtotime( $today . ' -30 days' ) );
	return array_values( array_filter( $days, function ( $d ) use ( $from ) {
		return $d >= $from;
	} ) );
}

/** Az alapértelmezett nap: a legközelebbi mai vagy jövőbeli nap, amelyre van rendelés. */
function bg_default_day( array $days ) {
	$today = BG_Delivery::now()->format( 'Y-m-d' );
	foreach ( $days as $d ) {
		if ( $d >= $today ) {
			return $d;
		}
	}
	return $days ? end( $days ) : $today;
}

/** Egy nap rendelései, zóna → település → irányítószám sorrendben. */
function bg_orders_for_day( $day, $status = 'active', $zone = '' ) {
	$orders = wc_get_orders(
		array(
			'limit'      => 1000,
			'status'     => 'all' === $status ? array( 'processing', 'on-hold', 'completed' ) : bg_active_statuses(),
			'meta_key'   => '_bg_day', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => $day, // phpcs:ignore WordPress.DB.SlowDBQuery
			'return'     => 'objects',
		)
	);
	$zone_of = array();
	$order_z = array();
	foreach ( BG_Delivery::areas() as $i => $a ) {
		$order_z[ $a['id'] ] = $i;
	}
	foreach ( BG_Delivery::towns() as $t ) {
		$zone_of[ $t['name'] ] = $t['zone'];
	}
	if ( $zone ) {
		$orders = array_filter( $orders, function ( $o ) use ( $zone, $zone_of ) {
			return ( $zone_of[ $o->get_meta( '_bg_town' ) ] ?? '' ) === $zone;
		} );
	}
	usort( $orders, function ( $a, $b ) use ( $zone_of, $order_z ) {
		$ta = $a->get_meta( '_bg_town' );
		$tb = $b->get_meta( '_bg_town' );
		$za = $order_z[ $zone_of[ $ta ] ?? '' ] ?? 99;
		$zb = $order_z[ $zone_of[ $tb ] ?? '' ] ?? 99;
		return array( $za, $ta, $a->get_billing_postcode(), $a->get_id() ) <=> array( $zb, $tb, $b->get_billing_postcode(), $b->get_id() );
	} );
	return array_values( $orders );
}

/** Beszedendő összeg: utánvétnél a végösszeg, egyébként 0 (online fizetve). */
function bg_cash_due( WC_Order $o ) {
	return 'cod' === $o->get_payment_method() ? (float) $o->get_total() : 0.0;
}

function bg_items_text( WC_Order $o, $sep = '; ' ) {
	$out = array();
	foreach ( $o->get_items() as $item ) {
		$opt   = $item->get_meta( 'Kivitel' );
		$out[] = $item->get_quantity() . ' × ' . $item->get_name() . ( $opt ? ' (' . $opt . ')' : '' );
	}
	return implode( $sep, $out );
}

function bg_admin_styles() {
	?>
	<style>
		.bg-adm .bg-filters{display:flex;flex-wrap:wrap;gap:16px;align-items:end;margin:16px 0;padding:14px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bg-adm .bg-filters label{display:flex;flex-direction:column;gap:4px;font-weight:600}
		.bg-adm .bg-kpis{display:flex;flex-wrap:wrap;gap:12px;margin:12px 0}
		.bg-adm .bg-kpi{background:#161615;color:#F5F0E6;border-radius:8px;padding:12px 18px;min-width:150px}
		.bg-adm .bg-kpi b{display:block;font-size:22px;color:#E3AF00}
		.bg-adm table.bg-table{border-collapse:collapse;width:100%;background:#fff}
		.bg-adm table.bg-table th,.bg-adm table.bg-table td{border:1px solid #dcdcde;padding:8px 10px;vertical-align:top;text-align:left}
		.bg-adm table.bg-table th{background:#f6f7f7}
		.bg-adm table.bg-table td.num,.bg-adm table.bg-table th.num{text-align:right;white-space:nowrap}
		.bg-adm tr.bg-zone td{background:#F5F0E6;font-weight:700}
		.bg-adm .bg-actions{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
		.bg-adm .bg-pick{display:grid;grid-template-columns:repeat(auto-fill,minmax(420px,1fr));gap:16px;align-items:start}
		.bg-adm .bg-pick h3{margin:0;padding:10px 12px;background:#161615;color:#F5F0E6;font-size:15px}
		.bg-adm input.bg-small{width:110px}
		.bg-adm .bg-muted{color:#646970}
		.bg-adm .bg-check{width:22px;height:22px;border:2px solid #1d2327;display:inline-block;border-radius:3px}
		@media print{
			#adminmenumain,#wpadminbar,#wpfooter,.notice,.update-nag,.no-print,#screen-meta-links{display:none!important}
			#wpcontent{margin:0!important;padding:0!important}
			html.wp-toolbar{padding-top:0!important}
			.bg-adm .bg-pick{grid-template-columns:1fr 1fr}
			.bg-adm table.bg-table{font-size:11px}
			.bg-adm table.bg-table th,.bg-adm table.bg-table td{padding:4px 6px}
			.bg-adm .bg-kpi{background:#fff;color:#000;border:1px solid #000}
			.bg-adm .bg-kpi b{color:#000}
			body{background:#fff}
		}
	</style>
	<?php
}

function bg_day_select( array $days, $current ) {
	if ( ! in_array( $current, $days, true ) ) {
		$days[] = $current;
		sort( $days );
	}
	$out = '<select name="day">';
	foreach ( $days as $d ) {
		$out .= '<option value="' . esc_attr( $d ) . '"' . selected( $d, $current, false ) . '>' . esc_html( BG_Delivery::date_label( $d ) ) . '</option>';
	}
	return $out . '</select>';
}

function bg_req( $key, $default = '' ) {
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default; // phpcs:ignore WordPress.Security.NonceVerification
}

/* ---------- Járatlista ---------- */

function bg_view_route() {
	if ( ! current_user_can( BG_VIEW_CAP ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	$days   = bg_order_days();
	$day    = preg_match( '/^\d{4}-\d{2}-\d{2}$/', bg_req( 'day' ) ) ? bg_req( 'day' ) : bg_default_day( $days );
	$zone   = bg_req( 'zone' );
	$status = 'all' === bg_req( 'status' ) ? 'all' : 'active';
	$orders = bg_orders_for_day( $day, $status, $zone );
	$msg    = get_transient( 'bg_route_msg_' . get_current_user_id() );
	delete_transient( 'bg_route_msg_' . get_current_user_id() );

	$cash  = 0;
	$items = 0;
	foreach ( $orders as $o ) {
		$cash  += bg_cash_due( $o );
		$items += $o->get_item_count();
	}
	$zones = array( '' => 'Minden terület' );
	foreach ( BG_Delivery::areas() as $a ) {
		$zones[ $a['id'] ] = $a['name'];
	}
	$zone_of = array();
	foreach ( BG_Delivery::towns() as $t ) {
		$zone_of[ $t['name'] ] = $t['zone'];
	}

	bg_admin_styles();
	echo '<div class="wrap bg-adm"><h1>Járatlista – ' . esc_html( BG_Delivery::date_label( $day ) ) . '</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<form method="get" class="bg-filters no-print"><input type="hidden" name="page" value="bg-route">';
	echo '<label>Kiszállítási nap ' . bg_day_select( $days, $day ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escapelt
	echo '<label>Terület <select name="zone">';
	foreach ( $zones as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $k, $zone, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select></label><label>Állapot <select name="status"><option value="active">Kiszállításra vár</option><option value="all"' . selected( $status, 'all', false ) . '>Mind (kiszállítottal együtt)</option></select></label>';
	submit_button( 'Szűrés', 'secondary', '', false );
	echo '</form>';

	echo '<div class="bg-kpis"><div class="bg-kpi">Rendelés<b>' . count( $orders ) . '</b></div><div class="bg-kpi">Tétel<b>' . (int) $items . '</b></div><div class="bg-kpi">Beszedendő készpénz<b>' . esc_html( BG_Delivery::ft( $cash ) ) . '</b></div></div>';

	$csv = wp_nonce_url( add_query_arg( array( 'action' => 'bg_route_csv', 'day' => $day, 'zone' => $zone, 'status' => $status ), admin_url( 'admin-post.php' ) ), 'bg_route_csv' );
	echo '<div class="bg-actions no-print"><button type="button" class="button button-primary" onclick="window.print()">Nyomtatás a sofőrnek</button><a class="button" href="' . esc_url( $csv ) . '">CSV export</a>';
	echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'bg-pick', 'day' => $day ), admin_url( 'admin.php' ) ) ) . '">Komissiózó lista erre a napra</a></div>';

	if ( ! $orders ) {
		echo '<p>Erre a napra nincs kiszállítandó rendelés.</p></div>';
		return;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bg_route_done' );
	echo '<input type="hidden" name="action" value="bg_route_done"><input type="hidden" name="back" value="' . esc_attr( add_query_arg( array( 'page' => 'bg-route', 'day' => $day, 'zone' => $zone, 'status' => $status ), admin_url( 'admin.php' ) ) ) . '">';
	echo '<table class="bg-table"><thead><tr><th class="no-print"><input type="checkbox" onclick="document.querySelectorAll(\'.bg-sel\').forEach(c=>c.checked=this.checked)" aria-label="Mind kijelölése"></th><th>#</th><th>Település</th><th>Vevő, cím</th><th>Telefon</th><th>Tételek</th><th>Fizetés</th><th class="num">Beszedendő</th><th>Megjegyzés</th><th>Átadva</th></tr></thead><tbody>';
	$last_zone = null;
	foreach ( $orders as $o ) {
		$town = $o->get_meta( '_bg_town' );
		$z    = $zone_of[ $town ] ?? '';
		if ( $z !== $last_zone ) {
			echo '<tr class="bg-zone"><td colspan="10">' . esc_html( $zones[ $z ] ?? 'Egyéb' ) . '</td></tr>';
			$last_zone = $z;
		}
		$due  = bg_cash_due( $o );
		$name = trim( $o->get_billing_last_name() . ' ' . $o->get_billing_first_name() );
		echo '<tr>';
		echo '<td class="no-print"><input type="checkbox" class="bg-sel" name="ids[]" value="' . (int) $o->get_id() . '"></td>';
		echo '<td><a href="' . esc_url( $o->get_edit_order_url() ) . '">' . esc_html( $o->get_order_number() ) . '</a>' . ( $o->has_status( 'completed' ) ? '<br><span class="bg-muted">kiszállítva</span>' : '' ) . '</td>';
		echo '<td>' . esc_html( $town ) . '</td>';
		echo '<td><strong>' . esc_html( $name ) . '</strong>' . ( $o->get_billing_company() ? '<br>' . esc_html( $o->get_billing_company() ) : '' ) . '<br>' . esc_html( $o->get_billing_postcode() . ' ' . $o->get_billing_city() . ', ' . $o->get_billing_address_1() ) . '</td>';
		echo '<td style="white-space:nowrap">' . esc_html( $o->get_billing_phone() ) . '</td>';
		echo '<td>' . esc_html( bg_items_text( $o ) ) . '</td>';
		echo '<td>' . esc_html( $o->get_payment_method_title() ) . '</td>';
		echo '<td class="num"><strong>' . ( $due ? esc_html( BG_Delivery::ft( $due ) ) : 'fizetve' ) . '</strong></td>';
		echo '<td>' . esc_html( $o->get_customer_note() ) . '</td>';
		echo '<td><span class="bg-check"></span></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<div class="bg-actions no-print"><span class="bg-muted">Kijelöltek:</span>';
	submit_button( 'Kiszállítva (Teljesítve állapot)', 'primary', 'submit', false );
	echo '</div></form>';
	echo '<p class="bg-muted no-print">A sorrend: terület, település, irányítószám. A „Kiszállítva” a rendelést Teljesítve állapotba teszi (a vásárló e-mailt kap róla).</p></div>';
}

add_action( 'admin_post_bg_route_done', function () {
	if ( ! current_user_can( BG_VIEW_CAP ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	check_admin_referer( 'bg_route_done' );
	$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
	$n   = 0;
	foreach ( $ids as $id ) {
		$o = wc_get_order( $id );
		if ( $o && $o->has_status( bg_active_statuses() ) ) {
			$o->update_status( 'completed', 'Kiszállítva (járatlista).' );
			$n++;
		}
	}
	set_transient( 'bg_route_msg_' . get_current_user_id(), $n . ' rendelés kiszállítottnak jelölve.', 120 );
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : admin_url( 'admin.php?page=bg-route' );
	wp_safe_redirect( $back );
	exit;
} );

add_action( 'admin_post_bg_route_csv', function () {
	if ( ! current_user_can( BG_VIEW_CAP ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	check_admin_referer( 'bg_route_csv' );
	$day    = preg_match( '/^\d{4}-\d{2}-\d{2}$/', bg_req( 'day' ) ) ? bg_req( 'day' ) : '';
	$orders = $day ? bg_orders_for_day( $day, 'all' === bg_req( 'status' ) ? 'all' : 'active', bg_req( 'zone' ) ) : array();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="jaratlista-' . $day . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // Excel miatt
	fputcsv( $out, array( 'Rendelés', 'Település', 'Név', 'Cég', 'Irányítószám', 'Cím', 'Telefon', 'E-mail', 'Tételek', 'Fizetés', 'Beszedendő', 'Végösszeg', 'Megjegyzés', 'Állapot' ), ';' );
	foreach ( $orders as $o ) {
		fputcsv(
			$out,
			array(
				$o->get_order_number(),
				$o->get_meta( '_bg_town' ),
				trim( $o->get_billing_last_name() . ' ' . $o->get_billing_first_name() ),
				$o->get_billing_company(),
				$o->get_billing_postcode(),
				$o->get_billing_address_1(),
				$o->get_billing_phone(),
				$o->get_billing_email(),
				bg_items_text( $o ),
				$o->get_payment_method_title(),
				bg_cash_due( $o ),
				$o->get_total(),
				$o->get_customer_note(),
				wc_get_order_status_name( $o->get_status() ),
			),
			';'
		);
	}
	fclose( $out );
	exit;
} );

/* ---------- Komissiózó lista ---------- */

function bg_view_pick() {
	if ( ! current_user_can( BG_VIEW_CAP ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	$days   = bg_order_days();
	$day    = preg_match( '/^\d{4}-\d{2}-\d{2}$/', bg_req( 'day' ) ) ? bg_req( 'day' ) : bg_default_day( $days );
	$orders = bg_orders_for_day( $day );

	// termék + kivitel → mennyiség; kategóriánként
	$groups = array();
	$cat_name = array();
	foreach ( $orders as $o ) {
		foreach ( $o->get_items() as $item ) {
			$pid  = $item->get_product_id();
			$cats = $pid ? wp_get_post_terms( $pid, 'product_cat' ) : array();
			$cat  = $cats ? $cats[0]->slug : 'egyeb';
			$cat_name[ $cat ] = $cats ? $cats[0]->name : 'Egyéb';
			$opt  = (string) $item->get_meta( 'Kivitel' );
			$key  = $pid . '|' . $opt;
			if ( ! isset( $groups[ $cat ][ $key ] ) ) {
				$groups[ $cat ][ $key ] = array(
					'name'   => $item->get_name(),
					'opt'    => $opt,
					'pack'   => $pid ? (string) get_post_meta( $pid, '_bg_pack', true ) : '',
					'qty'    => 0,
					'orders' => array(),
				);
			}
			$groups[ $cat ][ $key ]['qty']     += $item->get_quantity();
			$groups[ $cat ][ $key ]['orders'][] = $o->get_order_number();
		}
	}
	// kategóriák a bolt sorrendjében
	$order_cat = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
		$order_cat[ $t->slug ] = (int) get_term_meta( $t->term_id, 'order', true );
	}
	uksort( $groups, function ( $a, $b ) use ( $order_cat ) {
		return ( $order_cat[ $a ] ?? 99 ) <=> ( $order_cat[ $b ] ?? 99 );
	} );

	$total = 0;
	foreach ( $groups as $g ) {
		foreach ( $g as $r ) {
			$total += $r['qty'];
		}
	}

	bg_admin_styles();
	echo '<div class="wrap bg-adm"><h1>Komissiózó lista – ' . esc_html( BG_Delivery::date_label( $day ) ) . '</h1>';
	echo '<form method="get" class="bg-filters no-print"><input type="hidden" name="page" value="bg-pick"><label>Kiszállítási nap ' . bg_day_select( $days, $day ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
	submit_button( 'Mutasd', 'secondary', '', false );
	echo '<button type="button" class="button button-primary" onclick="window.print()">Nyomtatás</button></form>';
	echo '<p>' . count( $orders ) . ' rendelés, összesen ' . (int) $total . ' tétel (darab/csomag). Csak a kiszállításra váró rendelések.</p>';
	if ( ! $groups ) {
		echo '<p>Erre a napra nincs összekészítendő tétel.</p></div>';
		return;
	}
	echo '<div class="bg-pick">';
	foreach ( $groups as $cat => $rows ) {
		uasort( $rows, function ( $a, $b ) {
			return strcmp( $a['name'] . $a['opt'], $b['name'] . $b['opt'] );
		} );
		echo '<div><h3>' . esc_html( $cat_name[ $cat ] ) . '</h3><table class="bg-table"><thead><tr><th>Termék</th><th>Kiszerelés</th><th class="num">Db</th><th>Rendelések</th><th>Kész</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			echo '<tr><td><strong>' . esc_html( $r['name'] ) . '</strong>' . ( $r['opt'] ? '<br>' . esc_html( $r['opt'] ) : '' ) . '</td><td>' . esc_html( $r['pack'] ) . '</td><td class="num" style="font-size:18px"><strong>' . (int) $r['qty'] . '</strong></td><td class="bg-muted">#' . esc_html( implode( ', #', array_unique( $r['orders'] ) ) ) . '</td><td><span class="bg-check"></span></td></tr>';
		}
		echo '</tbody></table></div>';
	}
	echo '</div></div>';
}

/* ---------- Járatok és minimumok ---------- */

const BG_DOW = array( 1 => 'hétfő', 2 => 'kedd', 3 => 'szerda', 4 => 'csütörtök', 5 => 'péntek', 6 => 'szombat', 0 => 'vasárnap' );

/** 'kedd:10000, péntek:5000, 2026-12-21:10000' → runs tömb. Hibánál null. */
function bg_parse_runs( $text ) {
	$runs = array();
	$map  = array_flip( BG_DOW );
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $text ) ) ) as $part ) {
		if ( ! preg_match( '/^(.+?)\s*:\s*(\d[\d\s]*)$/u', $part, $m ) ) {
			return null;
		}
		$when = mb_strtolower( trim( $m[1] ) );
		$min  = (int) preg_replace( '/\D/', '', $m[2] );
		if ( isset( $map[ $when ] ) ) {
			$runs[] = array( 'day' => $map[ $when ], 'min' => $min );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $when ) ) {
			$runs[] = array( 'date' => $when, 'min' => $min );
		} else {
			return null;
		}
	}
	return $runs;
}

function bg_runs_text( array $runs ) {
	return implode( ', ', array_map( function ( $r ) {
		return ( isset( $r['date'] ) ? $r['date'] : BG_DOW[ (int) $r['day'] ] ) . ':' . (int) $r['min'];
	}, $runs ) );
}

function bg_view_zones() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	$data  = BG_Delivery::data();
	$msg   = get_transient( 'bg_zones_msg_' . get_current_user_id() );
	delete_transient( 'bg_zones_msg_' . get_current_user_id() );
	$areas = $data['areas'];
	bg_admin_styles();
	echo '<div class="wrap bg-adm"><h1>Járatok és minimumok</h1>';
	if ( $msg ) {
		echo '<div class="notice ' . ( 0 === strpos( $msg, 'Hiba' ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<p>Ez a tábla vezérli a pénztárt (választható napok, minimum, irányítószám-ellenőrzés), a kosársávot és a Szállítás oldalt.<br>';
	echo 'Járat formátuma: <code>kedd:10000, péntek:5000</code> (heti járat a nap nevével) vagy <code>2026-12-21:10000</code> (egyszeri járat dátummal). Irányítószámok vesszővel; Budapest: <code>1</code> (minden 1-gyel kezdődő).</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bg_zones_save' );
	echo '<input type="hidden" name="action" value="bg_zones_save">';
	echo '<p><label><strong>Rendelési határidő:</strong> a kiszállítás előtti napon <input type="number" min="0" max="23" name="cutoff" value="' . (int) $data['cutoffHour'] . '" style="width:64px"> óráig</label></p>';
	echo '<table class="bg-table"><thead><tr><th>Település</th><th>Terület</th><th>Járatok (nap:minimum)</th><th>Irányítószámok</th><th>Törlés</th></tr></thead><tbody>';
	$rows   = $data['towns'];
	$rows[] = array( 'name' => '', 'zone' => $areas[0]['id'], 'runs' => array(), 'zips' => array() );
	$rows[] = array( 'name' => '', 'zone' => $areas[0]['id'], 'runs' => array(), 'zips' => array() );
	foreach ( $rows as $i => $t ) {
		echo '<tr><td><input type="text" name="t[' . (int) $i . '][name]" value="' . esc_attr( $t['name'] ) . '" placeholder="' . ( $t['name'] ? '' : 'Új település' ) . '"></td>';
		echo '<td><select name="t[' . (int) $i . '][zone]">';
		foreach ( $areas as $a ) {
			echo '<option value="' . esc_attr( $a['id'] ) . '"' . selected( $a['id'], $t['zone'], false ) . '>' . esc_html( $a['name'] ) . '</option>';
		}
		echo '</select></td>';
		echo '<td><input type="text" name="t[' . (int) $i . '][runs]" value="' . esc_attr( bg_runs_text( $t['runs'] ) ) . '" style="width:100%"></td>';
		echo '<td><input type="text" name="t[' . (int) $i . '][zips]" value="' . esc_attr( implode( ', ', $t['zips'] ) ) . '" style="width:100%"></td>';
		echo '<td style="text-align:center">' . ( $t['name'] ? '<input type="checkbox" name="t[' . (int) $i . '][del]" value="1" aria-label="Törlés">' : '' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<div class="bg-actions">';
	submit_button( 'Mentés', 'primary', 'submit', false );
	echo '</div></form>';
	$reset = wp_nonce_url( admin_url( 'admin-post.php?action=bg_zones_reset' ), 'bg_zones_reset' );
	echo '<p class="bg-muted">' . count( $data['towns'] ) . ' település. <a href="' . esc_url( $reset ) . '" onclick="return confirm(\'Visszaállítod az eredeti (OneMin) táblát? A módosítások elvesznek.\')">Alapértékek visszaállítása</a></p></div>';
}

add_action( 'admin_post_bg_zones_save', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	check_admin_referer( 'bg_zones_save' );
	$data   = BG_Delivery::data();
	$zones  = wp_list_pluck( $data['areas'], 'id' );
	$towns  = array();
	$errors = array();
	$rows   = isset( $_POST['t'] ) && is_array( $_POST['t'] ) ? wp_unslash( $_POST['t'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- mezőnként tisztítjuk
	foreach ( $rows as $r ) {
		$name = sanitize_text_field( $r['name'] ?? '' );
		if ( '' === $name || ! empty( $r['del'] ) ) {
			continue;
		}
		$runs = bg_parse_runs( sanitize_text_field( $r['runs'] ?? '' ) );
		if ( null === $runs || ! $runs ) {
			$errors[] = $name . ': hibás járat';
			continue;
		}
		$zips = array_values( array_filter( array_map( function ( $z ) {
			return preg_replace( '/\D/', '', $z );
		}, explode( ',', sanitize_text_field( $r['zips'] ?? '' ) ) ) ) );
		$zone = in_array( $r['zone'] ?? '', $zones, true ) ? $r['zone'] : $zones[0];
		$towns[] = array( 'name' => $name, 'zone' => $zone, 'runs' => $runs, 'zips' => $zips );
	}
	if ( $errors ) {
		set_transient( 'bg_zones_msg_' . get_current_user_id(), 'Hiba, nem mentettem: ' . implode( '; ', $errors ), 120 );
	} else {
		usort( $towns, function ( $a, $b ) {
			return strcoll( $a['name'], $b['name'] );
		} );
		$cutoff = isset( $_POST['cutoff'] ) ? max( 0, min( 23, (int) $_POST['cutoff'] ) ) : 12;
		update_option( 'bg_towns', array( 'cutoffHour' => $cutoff, 'areas' => $data['areas'], 'towns' => $towns ), false );
		set_transient( 'bg_zones_msg_' . get_current_user_id(), 'Mentve: ' . count( $towns ) . ' település.', 120 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=bg-zones' ) );
	exit;
} );

add_action( 'admin_post_bg_zones_reset', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nincs jogosultság.' );
	}
	check_admin_referer( 'bg_zones_reset' );
	delete_option( 'bg_towns' );
	set_transient( 'bg_zones_msg_' . get_current_user_id(), 'Visszaállítva az eredeti táblára.', 120 );
	wp_safe_redirect( admin_url( 'admin.php?page=bg-zones' ) );
	exit;
} );
