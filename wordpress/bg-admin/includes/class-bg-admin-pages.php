<?php
/**
 * Admin oldalak: Mai nap, Járatlista (szerződés 05. tétel), Komissiózó lista,
 * valamint a „Kiszállítva / átvéve” jelölés és a CSV-export kezelése.
 *
 * @package BG_Admin
 */

defined( 'ABSPATH' ) || exit;

class BG_Admin_Pages {

	const CAP = 'edit_shop_orders';

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_bg_admin_mark_done', array( __CLASS__, 'handle_mark_done' ) );
		add_action( 'admin_post_bg_admin_route_csv', array( __CLASS__, 'handle_route_csv' ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'bg-admin' ) && false === strpos( (string) $hook, 'bg-jaratlista' ) && false === strpos( (string) $hook, 'bg-komissio' ) ) {
			return;
		}
		wp_enqueue_style( 'bg-admin', BG_ADMIN_URL . 'assets/admin.css', array(), BG_ADMIN_VERSION );
		wp_enqueue_script( 'bg-admin', BG_ADMIN_URL . 'assets/admin.js', array(), BG_ADMIN_VERSION, true );
	}

	/* ------------------------------------------------------------------ */
	/* Segédek                                                             */
	/* ------------------------------------------------------------------ */

	private static function ft( $amount ) {
		return number_format( (float) $amount, 0, ',', "\u{00A0}" ) . "\u{00A0}Ft";
	}

	/** A választott nap: a „Más nap” dátummező (nap2) elsőbbséget kap a legördülő (nap) előtt. */
	private static function get_date() {
		foreach ( array( 'nap2', 'nap' ) as $param ) {
			$d = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( BG_Admin_Orders::is_valid_date( $d ) ) {
				return $d;
			}
		}
		return BG_Admin_Orders::default_date();
	}

	private static function page_url( $page, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	private static function notice() {
		$done = isset( $_GET['bg_done'] ) ? absint( $_GET['bg_done'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $done ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( sprintf( '%d rendelés teljesítettre állítva.', $done ) ) );
		}
	}

	private static function status_badge( $order ) {
		$status = $order->get_status();
		return sprintf( '<span class="bg-status bg-status--%1$s">%2$s</span>', esc_attr( $status ), esc_html( wc_get_order_status_name( $status ) ) );
	}

	private static function day_options( $selected ) {
		$dates = BG_Admin_Orders::upcoming_dates();
		if ( ! isset( $dates[ $selected ] ) ) {
			$dates[ $selected ] = 0;
			ksort( $dates );
		}
		$out = '';
		foreach ( $dates as $d => $n ) {
			$out .= sprintf( '<option value="%1$s"%2$s>%3$s (%4$d)</option>', esc_attr( $d ), selected( $d, $selected, false ), esc_html( BG_Admin_Orders::format_day( $d ) ), (int) $n );
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Mai nap                                                             */
	/* ------------------------------------------------------------------ */

	public static function render_today() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'bg-admin' ) );
		}
		$today     = current_time( 'Y-m-d' );
		$open      = BG_Admin_Orders::open_orders();
		$new       = array_filter( $open, static fn( $o ) => 'processing' === $o->get_status() );
		$pickup    = array_filter( $open, array( 'BG_Admin_Orders', 'is_pickup' ) );
		$next_day  = BG_Admin_Orders::default_date();
		$next      = BG_Admin_Orders::route_orders( $next_day );
		$cash      = array_sum( array_map( static fn( $o ) => BG_Admin_Orders::is_pay_on_delivery( $o ) ? (float) $o->get_total() : 0, $next ) );
		$no_date   = array_filter( $open, static fn( $o ) => ! BG_Admin_Orders::is_pickup( $o ) && '' === BG_Admin_Orders::delivery_date( $o ) );
		$low_stock = BG_Admin_Orders::low_stock_products( 8 );
		$user      = wp_get_current_user();
		?>
		<div class="wrap bg-wrap">
			<h1 class="bg-h1">Szia<?php echo $user->first_name ? ', ' . esc_html( $user->first_name ) : ''; ?>! <span class="bg-muted"><?php echo esc_html( BG_Admin_Orders::format_day( $today ) ); ?></span></h1>
			<?php self::notice(); ?>
			<div class="bg-kpis">
				<a class="bg-kpi" href="<?php echo esc_url( BG_Admin_Menu::orders_url( array( 'status' => 'wc-processing', 'post_status' => 'wc-processing' ) ) ); ?>">
					<span class="bg-kpi__label">Feldolgozásra vár</span><span class="bg-kpi__value"><?php echo (int) count( $new ); ?></span><span class="bg-kpi__sub">rendelés</span>
				</a>
				<a class="bg-kpi bg-kpi--dark" href="<?php echo esc_url( self::page_url( 'bg-jaratlista', array( 'nap' => $next_day ) ) ); ?>">
					<span class="bg-kpi__label">Következő járat</span><span class="bg-kpi__value"><?php echo (int) count( $next ); ?></span><span class="bg-kpi__sub"><?php echo esc_html( BG_Admin_Orders::format_day( $next_day ) ); ?></span>
				</a>
				<a class="bg-kpi" href="<?php echo esc_url( self::page_url( 'bg-jaratlista', array( 'nap' => $next_day ) ) ); ?>">
					<span class="bg-kpi__label">Beszedendő a járaton</span><span class="bg-kpi__value"><?php echo esc_html( self::ft( $cash ) ); ?></span><span class="bg-kpi__sub">utánvét / készpénz</span>
				</a>
				<a class="bg-kpi" href="<?php echo esc_url( self::page_url( 'bg-jaratlista', array( 'nap' => $next_day ) ) . '#atvetel' ); ?>">
					<span class="bg-kpi__label">Személyes átvételre vár</span><span class="bg-kpi__value"><?php echo (int) count( $pickup ); ?></span><span class="bg-kpi__sub">Gesztely</span>
				</a>
			</div>

			<div class="bg-actions">
				<a class="button button-primary button-hero" href="<?php echo esc_url( self::page_url( 'bg-jaratlista', array( 'nap' => $next_day ) ) ); ?>">Járatlista</a>
				<a class="button button-hero" href="<?php echo esc_url( self::page_url( 'bg-komissio', array( 'nap' => $next_day ) ) ); ?>">Komissiózó lista</a>
				<a class="button button-hero" href="<?php echo esc_url( BG_Admin_Menu::orders_url() ); ?>">Összes rendelés</a>
				<?php if ( current_user_can( 'edit_products' ) ) : ?>
					<a class="button button-hero" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">Termékek és készlet</a>
				<?php endif; ?>
			</div>

			<div class="bg-cols">
				<section class="bg-card">
					<h2>Legutóbbi rendelések</h2>
					<?php
					$recent = wc_get_orders( array( 'limit' => 8, 'type' => 'shop_order', 'orderby' => 'date', 'order' => 'DESC' ) );
					if ( ! $recent ) {
						echo '<p class="bg-muted">Még nincs rendelés.</p>';
					} else {
						echo '<table class="widefat striped bg-table"><thead><tr><th>Rendelés</th><th>Vevő</th><th>Átvétel</th><th class="num">Összeg</th><th>Állapot</th></tr></thead><tbody>';
						foreach ( $recent as $o ) {
							if ( ! $o instanceof WC_Order ) {
								continue;
							}
							$when = BG_Admin_Orders::is_pickup( $o ) ? 'Személyes átvétel' : ( BG_Admin_Orders::delivery_date( $o ) ? BG_Admin_Orders::format_day( BG_Admin_Orders::delivery_date( $o ) ) . ' · ' . $o->get_shipping_city() : 'nincs járatnap' );
							printf(
								'<tr><td><a href="%1$s"><strong>#%2$s</strong></a></td><td>%3$s</td><td>%4$s</td><td class="num">%5$s</td><td>%6$s</td></tr>',
								esc_url( $o->get_edit_order_url() ),
								esc_html( $o->get_order_number() ),
								esc_html( BG_Admin_Orders::name( $o ) ),
								esc_html( $when ),
								esc_html( self::ft( $o->get_total() ) ),
								self::status_badge( $o ) // phpcs:ignore WordPress.Security.EscapeOutput
							);
						}
						echo '</tbody></table>';
					}
					?>
				</section>
				<section class="bg-card">
					<h2>Figyelmet igényel</h2>
					<?php if ( $no_date ) : ?>
						<p class="bg-warn"><strong><?php echo (int) count( $no_date ); ?> házhozszállításos rendelésnek nincs járatnapja.</strong> Ezek nem kerülnek járatlistára: nyisd meg és egyeztesd a napot.</p>
						<ul class="bg-list">
							<?php foreach ( array_slice( $no_date, 0, 6 ) as $o ) : ?>
								<li><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a> – <?php echo esc_html( BG_Admin_Orders::name( $o ) . ', ' . $o->get_shipping_city() ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<h3>Alacsony készlet</h3>
					<?php if ( ! $low_stock ) : ?>
						<p class="bg-muted">Nincs alacsony készletű termék (vagy a készletkövetés még nincs bekapcsolva).</p>
					<?php else : ?>
						<ul class="bg-list">
							<?php foreach ( $low_stock as $p ) : ?>
								<li><a href="<?php echo esc_url( get_edit_post_link( $p->get_id() ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a> – <strong><?php echo esc_html( (string) $p->get_stock_quantity() ); ?> db</strong><?php echo $p->get_stock_quantity() <= 0 ? ' <span class="bg-status bg-status--failed">elfogyott</span>' : ''; ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Járatlista                                                          */
	/* ------------------------------------------------------------------ */

	public static function render_route() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'bg-admin' ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification -- csak olvasó szűrők
		$date     = self::get_date();
		$zone     = isset( $_GET['zona'] ) ? sanitize_key( wp_unslash( $_GET['zona'] ) ) : '';
		$all      = isset( $_GET['allapot'] ) && 'mind' === $_GET['allapot'];
		// phpcs:enable
		$statuses = $all ? array_merge( BG_Admin_Orders::open_statuses(), array( 'completed' ) ) : null;
		$day_all  = BG_Admin_Orders::route_orders( $date, '', $statuses );
		$zones    = array_unique( array_map( array( 'BG_Admin_Orders', 'zone' ), $day_all ) );
		sort( $zones );
		$orders   = '' === $zone ? $day_all : array_values( array_filter( $day_all, static fn( $o ) => BG_Admin_Orders::zone( $o ) === $zone ) );
		$cash     = 0;
		$towns    = array();
		foreach ( $orders as $o ) {
			$towns[ $o->get_shipping_city() ] = true;
			if ( BG_Admin_Orders::is_pay_on_delivery( $o ) ) {
				$cash += (float) $o->get_total();
			}
		}
		$csv_url = wp_nonce_url(
			add_query_arg( array( 'action' => 'bg_admin_route_csv', 'nap' => $date, 'zona' => $zone, 'allapot' => $all ? 'mind' : '' ), admin_url( 'admin-post.php' ) ),
			'bg_admin_route_csv'
		);
		?>
		<div class="wrap bg-wrap">
			<h1 class="bg-h1">Napi járatlista <span class="bg-muted bg-print-only">– <?php echo esc_html( BG_Admin_Orders::format_day( $date ) . ( $zone ? ' · ' . BG_Admin_Orders::zone_label( $zone ) : '' ) ); ?></span></h1>
			<?php self::notice(); ?>
			<form method="get" class="bg-filters bg-noprint">
				<input type="hidden" name="page" value="bg-jaratlista">
				<label>Járatnap
					<select name="nap" onchange="this.form.submit()"><?php echo self::day_options( $date ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
				</label>
				<label>Más nap <input type="date" name="nap2" value="" onchange="this.form.submit()" aria-label="Más nap választása"></label>
				<label>Zóna
					<select name="zona" onchange="this.form.submit()">
						<option value="">Minden zóna</option>
						<?php foreach ( $zones as $z ) : ?>
							<option value="<?php echo esc_attr( $z ); ?>" <?php selected( $z, $zone ); ?>><?php echo esc_html( BG_Admin_Orders::zone_label( $z ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Állapot
					<select name="allapot" onchange="this.form.submit()">
						<option value="">Teljesítésre vár</option>
						<option value="mind" <?php selected( $all ); ?>>Teljesítettekkel együtt</option>
					</select>
				</label>
				<span class="bg-filters__spacer"></span>
				<button type="button" class="button" data-bg-print>Nyomtatás</button>
				<a class="button" href="<?php echo esc_url( $csv_url ); ?>">CSV (Excel)</a>
			</form>

			<div class="bg-kpis bg-kpis--sm">
				<div class="bg-kpi"><span class="bg-kpi__label">Rendelés</span><span class="bg-kpi__value"><?php echo (int) count( $orders ); ?></span></div>
				<div class="bg-kpi"><span class="bg-kpi__label">Település</span><span class="bg-kpi__value"><?php echo (int) count( $towns ); ?></span></div>
				<div class="bg-kpi bg-kpi--dark"><span class="bg-kpi__label">Beszedendő</span><span class="bg-kpi__value"><?php echo esc_html( self::ft( $cash ) ); ?></span></div>
				<div class="bg-kpi"><span class="bg-kpi__label">Előre fizetve</span><span class="bg-kpi__value"><?php echo (int) count( array_filter( $orders, static fn( $o ) => ! BG_Admin_Orders::is_pay_on_delivery( $o ) ) ); ?></span></div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bg_admin_mark_done">
				<input type="hidden" name="vissza" value="<?php echo esc_attr( self::page_url( 'bg-jaratlista', array( 'nap' => $date, 'zona' => $zone, 'allapot' => $all ? 'mind' : '' ) ) ); ?>">
				<?php wp_nonce_field( 'bg_admin_mark_done' ); ?>
				<table class="widefat striped bg-table bg-route">
					<thead><tr>
						<td class="check-column bg-noprint"><input type="checkbox" data-bg-check-all aria-label="Összes kijelölése"></td>
						<th class="num">#</th><th>Település</th><th>Vevő és cím</th><th>Telefon</th><th class="num">Tétel</th><th>Fizetés</th><th class="num">Beszedendő</th><th>Megjegyzés</th><th class="bg-noprint">Állapot</th><th class="bg-print-only">Átadva</th>
					</tr></thead>
					<tbody>
					<?php if ( ! $orders ) : ?>
						<tr><td colspan="11" class="bg-muted bg-empty">Erre a napra nincs kiszállítandó rendelés.</td></tr>
					<?php endif; ?>
					<?php
					$zone_prev = null;
					foreach ( $orders as $i => $o ) :
						$z = BG_Admin_Orders::zone( $o );
						if ( '' === $zone && $z !== $zone_prev ) :
							$zone_prev = $z;
							?>
							<tr class="bg-zone-row"><th colspan="11"><?php echo esc_html( BG_Admin_Orders::zone_label( $z ) ); ?></th></tr>
						<?php endif; ?>
						<tr>
							<th scope="row" class="check-column bg-noprint"><input type="checkbox" name="rendeles[]" value="<?php echo esc_attr( (string) $o->get_id() ); ?>" aria-label="<?php echo esc_attr( 'Kijelölés: #' . $o->get_order_number() ); ?>"></th>
							<td class="num bg-strong"><?php echo (int) $i + 1; ?></td>
							<td><strong><?php echo esc_html( $o->get_shipping_city() ); ?></strong><br><span class="bg-muted"><?php echo esc_html( $o->get_shipping_postcode() ); ?></span></td>
							<td><strong><?php echo esc_html( BG_Admin_Orders::name( $o ) ); ?></strong><br><?php echo esc_html( trim( $o->get_shipping_address_1() . ' ' . $o->get_shipping_address_2() ) ); ?><br><a class="bg-muted" href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
							<td><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) ( $o->get_shipping_phone() ?: $o->get_billing_phone() ) ) ); ?>"><?php echo esc_html( $o->get_shipping_phone() ?: $o->get_billing_phone() ); ?></a></td>
							<td class="num"><?php echo (int) BG_Admin_Orders::item_count( $o ); ?> db</td>
							<td><?php echo esc_html( $o->get_payment_method_title() ); ?></td>
							<td class="num bg-strong"><?php echo BG_Admin_Orders::is_pay_on_delivery( $o ) ? esc_html( self::ft( $o->get_total() ) ) : '–'; ?></td>
							<td><?php echo esc_html( $o->get_customer_note() ); ?></td>
							<td class="bg-noprint"><?php echo self::status_badge( $o ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="bg-print-only bg-sign"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( $orders ) : ?>
					<p class="bg-noprint"><button type="submit" class="button button-primary">Kijelöltek kiszállítva (teljesítve)</button> <span class="bg-muted">A rendelés „Teljesítve” állapotba kerül, a vevő megkapja a WooCommerce teljesítési e-mailjét.</span></p>
				<?php endif; ?>
			</form>

			<?php self::render_pickup_table(); ?>
		</div>
		<?php
	}

	private static function render_pickup_table() {
		$pickup = BG_Admin_Orders::pickup_orders();
		?>
		<section class="bg-card bg-pickup bg-noprint" id="atvetel">
			<h2>Személyes átvételre vár – Gesztely</h2>
			<?php if ( ! $pickup ) : ?>
				<p class="bg-muted">Nincs személyes átvételre váró rendelés.</p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="bg_admin_mark_done">
					<input type="hidden" name="vissza" value="<?php echo esc_attr( self::page_url( 'bg-jaratlista' ) . '#atvetel' ); ?>">
					<?php wp_nonce_field( 'bg_admin_mark_done' ); ?>
					<table class="widefat striped bg-table">
						<thead><tr><td class="check-column bg-noprint"></td><th>Rendelés</th><th>Vevő</th><th>Telefon</th><th class="num">Tétel</th><th>Fizetés</th><th class="num">Fizetendő helyben</th><th>Leadva</th></tr></thead>
						<tbody>
						<?php foreach ( $pickup as $o ) : ?>
							<tr>
								<th scope="row" class="check-column bg-noprint"><input type="checkbox" name="rendeles[]" value="<?php echo esc_attr( (string) $o->get_id() ); ?>" aria-label="<?php echo esc_attr( 'Kijelölés: #' . $o->get_order_number() ); ?>"></th>
								<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><strong>#<?php echo esc_html( $o->get_order_number() ); ?></strong></a></td>
								<td><?php echo esc_html( BG_Admin_Orders::name( $o ) ); ?></td>
								<td><?php echo esc_html( $o->get_billing_phone() ); ?></td>
								<td class="num"><?php echo (int) BG_Admin_Orders::item_count( $o ); ?> db</td>
								<td><?php echo esc_html( $o->get_payment_method_title() ); ?></td>
								<td class="num bg-strong"><?php echo BG_Admin_Orders::is_pay_on_delivery( $o ) ? esc_html( self::ft( $o->get_total() ) ) : '–'; ?></td>
								<td><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'Y.m.d. H:i' ) : '' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p class="bg-noprint"><button type="submit" class="button">Kijelöltek átvéve (teljesítve)</button></p>
				</form>
			<?php endif; ?>
		</section>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Komissiózó lista                                                    */
	/* ------------------------------------------------------------------ */

	public static function render_pick() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'bg-admin' ) );
		}
		$date   = self::get_date();
		$with_p = ! empty( $_GET['atvetel'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$orders = BG_Admin_Orders::route_orders( $date );
		if ( $with_p ) {
			$orders = array_merge( $orders, BG_Admin_Orders::pickup_orders() );
		}
		$rows   = BG_Admin_Orders::pick_totals( $orders );
		$by_cat = array();
		foreach ( $rows as $r ) {
			$by_cat[ $r['cat'] ][] = $r;
		}
		?>
		<div class="wrap bg-wrap">
			<h1 class="bg-h1">Komissiózó lista <span class="bg-muted bg-print-only">– <?php echo esc_html( BG_Admin_Orders::format_day( $date ) ); ?></span></h1>
			<form method="get" class="bg-filters bg-noprint">
				<input type="hidden" name="page" value="bg-komissio">
				<label>Járatnap <select name="nap" onchange="this.form.submit()"><?php echo self::day_options( $date ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select></label>
				<label>Más nap <input type="date" name="nap2" value="" onchange="this.form.submit()" aria-label="Más nap választása"></label>
				<label class="bg-check"><input type="checkbox" name="atvetel" value="1" <?php checked( $with_p ); ?> onchange="this.form.submit()"> Személyes átvételre várók is</label>
				<span class="bg-filters__spacer"></span>
				<button type="button" class="button" data-bg-print>Nyomtatás</button>
			</form>
			<p class="bg-muted"><?php echo esc_html( sprintf( '%d rendelés · %d tétel', count( $orders ), array_sum( array_column( $rows, 'qty' ) ) ) ); ?></p>
			<?php if ( ! $rows ) : ?>
				<p class="bg-card bg-muted">Erre a napra nincs összekészítendő rendelés.</p>
			<?php endif; ?>
			<div class="bg-pick">
			<?php foreach ( $by_cat as $cat => $items ) : ?>
				<section class="bg-card bg-pick__cat">
					<h2><?php echo esc_html( $cat ); ?></h2>
					<table class="widefat striped bg-table">
						<thead><tr><th>Termék</th><th>Cikkszám</th><th class="num">Mennyiség</th><th class="num">Rendelés</th><th class="num">Kész</th></tr></thead>
						<tbody>
						<?php foreach ( $items as $r ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $r['name'] ); ?></strong><?php echo $r['opt'] ? '<br><span class="bg-muted">' . esc_html( $r['opt'] ) . '</span>' : ''; ?></td>
								<td class="bg-muted"><?php echo esc_html( $r['sku'] ); ?></td>
								<td class="num bg-qty"><?php echo (int) $r['qty']; ?></td>
								<td class="num"><?php echo (int) $r['orders']; ?></td>
								<td class="num"><span class="bg-box" aria-hidden="true"></span></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Műveletek                                                           */
	/* ------------------------------------------------------------------ */

	public static function handle_mark_done() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Nincs jogosultságod ehhez a művelethez.', 403 );
		}
		check_admin_referer( 'bg_admin_mark_done' );
		$ids  = isset( $_POST['rendeles'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rendeles'] ) ) : array();
		$done = 0;
		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order instanceof WC_Order || ! current_user_can( 'edit_shop_order', $id ) ) {
				continue;
			}
			if ( in_array( $order->get_status(), BG_Admin_Orders::open_statuses(), true ) ) {
				$who = wp_get_current_user()->display_name;
				$order->update_status( 'completed', sprintf( BG_Admin_Orders::is_pickup( $order ) ? 'Személyesen átvéve (Bio-Goods admin, %s).' : 'Kiszállítva (Bio-Goods járatlista, %s).', $who ) );
				++$done;
			}
		}
		$back = isset( $_POST['vissza'] ) ? esc_url_raw( wp_unslash( $_POST['vissza'] ) ) : self::page_url( 'bg-jaratlista' );
		wp_safe_redirect( add_query_arg( 'bg_done', $done, $back ) );
		exit;
	}

	public static function handle_route_csv() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Nincs jogosultságod ehhez a művelethez.', 403 );
		}
		check_admin_referer( 'bg_admin_route_csv' );
		$date     = isset( $_GET['nap'] ) ? sanitize_text_field( wp_unslash( $_GET['nap'] ) ) : '';
		$zone     = isset( $_GET['zona'] ) ? sanitize_key( wp_unslash( $_GET['zona'] ) ) : '';
		$all      = isset( $_GET['allapot'] ) && 'mind' === $_GET['allapot'];
		$statuses = $all ? array_merge( BG_Admin_Orders::open_statuses(), array( 'completed' ) ) : null;
		if ( ! BG_Admin_Orders::is_valid_date( $date ) ) {
			wp_die( 'Érvénytelen dátum.', 400 );
		}
		$orders = BG_Admin_Orders::route_orders( $date, $zone, $statuses );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="jaratlista-' . $date . ( $zone ? '-' . $zone : '' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM, hogy az Excel UTF-8-ként nyissa.
		$cols = array( 'sorszam', 'rendeles', 'zona', 'telepules', 'irsz', 'nev', 'cim', 'telefon', 'tetel_db', 'fizetes', 'beszedendo', 'megjegyzes', 'allapot' );
		fputcsv( $out, $cols, ';', '"', '' );
		foreach ( $orders as $i => $o ) {
			fputcsv(
				$out,
				array_map(
					array( __CLASS__, 'csv_safe' ),
					array(
						$i + 1,
						$o->get_order_number(),
						BG_Admin_Orders::zone_label( BG_Admin_Orders::zone( $o ) ),
						$o->get_shipping_city(),
						$o->get_shipping_postcode(),
						BG_Admin_Orders::name( $o ),
						trim( $o->get_shipping_address_1() . ' ' . $o->get_shipping_address_2() ),
						$o->get_shipping_phone() ?: $o->get_billing_phone(),
						BG_Admin_Orders::item_count( $o ),
						$o->get_payment_method_title(),
						BG_Admin_Orders::is_pay_on_delivery( $o ) ? round( (float) $o->get_total() ) : 0,
						$o->get_customer_note(),
						wc_get_order_status_name( $o->get_status() ),
					)
				),
				';',
				'"',
				''
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/** CSV-képletinjektálás ellen: a =, +, -, @ kezdetű szövegeket aposztróffal semlegesítjük (a számokat nem). */
	public static function csv_safe( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		$value = (string) $value;
		if ( preg_match( '/^[+\-]?[0-9][0-9 ()\/.\-]*$/', $value ) ) {
			return $value; // Telefonszám, irányítószám: nem lehet képlet.
		}
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
