<?php
/**
 * Rendelés-lekérdezések a járatlistához és a komissiózó listához.
 *
 * Adatszerződés a járatlogikával (szerződés 04. tétel): a pénztár a rendeléshez
 * az alábbi meta-mezőket írja. A kulcsok szűrővel átírhatók, ha a járatlogika
 * más neveket használ.
 *
 *   _bg_delivery_date  a választott járatnap, ÉÉÉÉ-HH-NN
 *   _bg_zone           a zóna azonosítója (pl. borsod, budapest)
 *   _bg_mode           'pickup' személyes átvételnél (különben üres vagy 'ship')
 *
 * Személyes átvételnek számít az is, ha a rendelés szállítási módja
 * local_pickup vagy pickup_location.
 *
 * @package BG_Admin
 */

defined( 'ABSPATH' ) || exit;

class BG_Admin_Orders {

	public static function date_key() {
		return (string) apply_filters( 'bg_admin_delivery_date_key', '_bg_delivery_date' );
	}

	public static function zone_key() {
		return (string) apply_filters( 'bg_admin_zone_key', '_bg_zone' );
	}

	/** Teljesítésre váró státuszok (prefix nélkül). */
	public static function open_statuses() {
		return (array) apply_filters( 'bg_admin_open_statuses', array( 'processing', 'on-hold' ) );
	}

	/** Helyszínen / sofőrnél fizetett fizetési módok – ezek összege a beszedendő. */
	public static function pay_on_delivery_methods() {
		return (array) apply_filters( 'bg_admin_pay_on_delivery_methods', array( 'cod' ) );
	}

	/** A zónák megjelenített neve. */
	public static function zone_label( $zone ) {
		$labels = (array) apply_filters( 'bg_admin_zone_labels', array() );
		if ( '' === (string) $zone ) {
			return '–';
		}
		return isset( $labels[ $zone ] ) ? $labels[ $zone ] : ucfirst( (string) $zone );
	}

	public static function is_valid_date( $date ) {
		$d = DateTime::createFromFormat( 'Y-m-d', (string) $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}

	/** @param WC_Order $order */
	public static function is_pickup( $order ) {
		if ( 'pickup' === $order->get_meta( '_bg_mode' ) ) {
			return true;
		}
		foreach ( $order->get_shipping_methods() as $method ) {
			if ( in_array( $method->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Név magyar sorrendben (vezetéknév, keresztnév); a szállítási név hiányában a számlázási.
	 *
	 * @param WC_Order $order
	 */
	public static function name( $order ) {
		$name = trim( $order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name() );
		if ( '' === $name ) {
			$name = trim( $order->get_billing_last_name() . ' ' . $order->get_billing_first_name() );
		}
		$company = $order->get_shipping_company() ?: $order->get_billing_company();
		return $company ? $company . ( $name ? ' – ' . $name : '' ) : $name;
	}

	/** @param WC_Order $order */
	public static function delivery_date( $order ) {
		$date = (string) $order->get_meta( self::date_key() );
		return self::is_valid_date( $date ) ? $date : '';
	}

	/** @param WC_Order $order */
	public static function zone( $order ) {
		return (string) $order->get_meta( self::zone_key() );
	}

	/** @param WC_Order $order */
	public static function is_pay_on_delivery( $order ) {
		return in_array( $order->get_payment_method(), self::pay_on_delivery_methods(), true );
	}

	/** @param WC_Order $order */
	public static function item_count( $order ) {
		$n = 0;
		foreach ( $order->get_items() as $item ) {
			$n += (int) $item->get_quantity();
		}
		return $n;
	}

	/**
	 * Teljesítésre váró rendelések.
	 *
	 * @param string[] $statuses
	 * @return WC_Order[]
	 */
	public static function open_orders( $statuses = null ) {
		$statuses = $statuses ? $statuses : self::open_statuses();
		$orders   = wc_get_orders(
			array(
				'limit'   => -1,
				'status'  => array_map( static fn( $s ) => 'wc-' . $s, $statuses ),
				'type'    => 'shop_order',
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		);
		return array_values( array_filter( $orders, static fn( $o ) => $o instanceof WC_Order ) );
	}

	/**
	 * Egy járatnap rendelései (házhozszállítás), zónára és státuszra szűrve,
	 * zóna → település → irányítószám → név szerint rendezve.
	 *
	 * @param string   $date     ÉÉÉÉ-HH-NN.
	 * @param string   $zone     Üres = minden zóna.
	 * @param string[] $statuses Prefix nélküli státuszok.
	 * @return WC_Order[]
	 */
	public static function route_orders( $date, $zone = '', $statuses = null ) {
		if ( ! self::is_valid_date( $date ) ) {
			return array();
		}
		$statuses = $statuses ? $statuses : self::open_statuses();
		// A meta_query-t a régi (bejegyzés alapú) rendelés-tároló nem adja tovább, ezért a
		// járatnapra PHP-ben szűrünk. A teljesített rendeléseket a járatnap előtti 60 napra
		// korlátozzuk, hogy a lekérdezés az évek során se nőjön.
		$args = array(
			'limit'  => -1,
			'type'   => 'shop_order',
			'status' => array_map( static fn( $s ) => 'wc-' . $s, $statuses ),
		);
		if ( in_array( 'completed', $statuses, true ) ) {
			$args['date_created'] = '>' . gmdate( 'Y-m-d', strtotime( $date . ' -60 days' ) );
		}
		$orders = array_filter(
			wc_get_orders( $args ),
			static function ( $o ) use ( $date, $zone ) {
				return $o instanceof WC_Order
					&& ! self::is_pickup( $o )
					&& self::delivery_date( $o ) === $date
					&& ( '' === $zone || self::zone( $o ) === $zone );
			}
		);
		usort(
			$orders,
			static function ( $a, $b ) {
				return strcmp( self::zone( $a ), self::zone( $b ) )
					?: strcoll( (string) $a->get_shipping_city(), (string) $b->get_shipping_city() )
					?: strcmp( (string) $a->get_shipping_postcode(), (string) $b->get_shipping_postcode() )
					?: strcoll( self::name( $a ), self::name( $b ) );
			}
		);
		return array_values( $orders );
	}

	/**
	 * Személyes átvételre váró rendelések.
	 *
	 * @return WC_Order[]
	 */
	public static function pickup_orders() {
		return array_values( array_filter( self::open_orders(), array( __CLASS__, 'is_pickup' ) ) );
	}

	/**
	 * A nyitott rendelésekben szereplő járatnapok, növekvő sorrendben, darabszámmal.
	 *
	 * @return array<string,int> dátum => rendelésszám
	 */
	public static function upcoming_dates() {
		$dates = array();
		foreach ( self::open_orders() as $order ) {
			if ( self::is_pickup( $order ) ) {
				continue;
			}
			$d = self::delivery_date( $order );
			if ( $d ) {
				$dates[ $d ] = ( $dates[ $d ] ?? 0 ) + 1;
			}
		}
		ksort( $dates );
		return $dates;
	}

	/**
	 * Az alapértelmezett nap: a mai vagy a legközelebbi jövőbeli járatnap, ahol van rendelés;
	 * ha nincs ilyen, a legutolsó, ahol van; végül a mai nap.
	 */
	public static function default_date() {
		$today = current_time( 'Y-m-d' );
		$dates = array_keys( self::upcoming_dates() );
		foreach ( $dates as $d ) {
			if ( $d >= $today ) {
				return $d;
			}
		}
		return $dates ? end( $dates ) : $today;
	}

	/**
	 * Összekészítendő mennyiségek termékenként (és választott kivitel szerint).
	 *
	 * @param WC_Order[] $orders
	 * @return array<string,array{cat:string,name:string,sku:string,opt:string,qty:int,orders:int}>
	 */
	public static function pick_totals( $orders ) {
		$rows = array();
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				/** @var WC_Order_Item_Product $item */
				$product = $item->get_product();
				$opt     = self::item_option_text( $item );
				$key     = $item->get_product_id() . ':' . $item->get_variation_id() . ':' . md5( $opt );
				if ( ! isset( $rows[ $key ] ) ) {
					$rows[ $key ] = array(
						'cat'    => self::product_category( $item->get_product_id() ),
						'name'   => $product ? $product->get_name() : $item->get_name(),
						'sku'    => $product ? (string) $product->get_sku() : '',
						'opt'    => $opt,
						'qty'    => 0,
						'orders' => 0,
					);
				}
				$rows[ $key ]['qty']    += (int) $item->get_quantity();
				$rows[ $key ]['orders'] += 1;
			}
		}
		uasort(
			$rows,
			static fn( $a, $b ) => strcoll( $a['cat'], $b['cat'] ) ?: ( $b['qty'] <=> $a['qty'] ) ?: strcoll( $a['name'], $b['name'] )
		);
		return $rows;
	}

	/** A tétel választott kivitele (változat-attribútumok és látható tétel-meta). */
	public static function item_option_text( $item ) {
		$parts = array();
		foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
			$parts[] = wp_strip_all_tags( $meta->display_key . ': ' . $meta->display_value );
		}
		return trim( implode( ', ', $parts ) );
	}

	private static function product_category( $product_id ) {
		static $cache = array();
		if ( ! isset( $cache[ $product_id ] ) ) {
			$terms                = get_the_terms( $product_id, 'product_cat' );
			$cache[ $product_id ] = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Egyéb';
		}
		return $cache[ $product_id ];
	}

	/** Alacsony vagy elfogyott készletű, készletkövetett termékek. */
	public static function low_stock_products( $limit = 10 ) {
		$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
		$ids       = wc_get_products(
			array(
				'limit'        => 200,
				'status'       => 'publish',
				'manage_stock' => true,
				'return'       => 'ids',
			)
		);
		$low       = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p && null !== $p->get_stock_quantity() && $p->get_stock_quantity() <= $threshold ) {
				$low[] = $p;
			}
		}
		usort( $low, static fn( $a, $b ) => $a->get_stock_quantity() <=> $b->get_stock_quantity() );
		return array_slice( $low, 0, $limit );
	}

	/** Magyar dátum: „csütörtök, nov. 26.” */
	public static function format_day( $date ) {
		if ( ! self::is_valid_date( $date ) ) {
			return '';
		}
		$days   = array( 'vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat' );
		$months = array( 'jan.', 'febr.', 'márc.', 'ápr.', 'máj.', 'jún.', 'júl.', 'aug.', 'szept.', 'okt.', 'nov.', 'dec.' );
		$ts     = strtotime( $date . ' 12:00:00' );
		return $days[ (int) gmdate( 'w', $ts ) ] . ', ' . $months[ (int) gmdate( 'n', $ts ) - 1 ] . ' ' . (int) gmdate( 'j', $ts ) . '.';
	}
}
