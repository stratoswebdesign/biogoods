<?php
/**
 * Kiszállítás: települések, járatok, rendelési határidő és minimum.
 *
 * Szabály (a mostani bgmangalica.hu szerint): kiszállítás a település járatnapján,
 * rendelés a kiszállítást megelőző napon déli 12 óráig. Minden járatnak saját
 * rendelési minimuma van (pl. Budapest: kedd 10 000 Ft, péntek 5 000 Ft). Szállítási díj nincs.
 *
 * Az adatok a data/towns.json-ból jönnek; a `bg_towns` opció (ha létezik) felülírja,
 * így az admin felületről is szerkeszthető lesz.
 */

defined( 'ABSPATH' ) || exit;

final class BG_Delivery {

	/** @var array|null */
	private static $data = null;

	public static function data() {
		if ( null === self::$data ) {
			$opt = get_option( 'bg_towns' );
			if ( is_array( $opt ) && ! empty( $opt['towns'] ) ) {
				self::$data = $opt;
			} else {
				$json       = file_get_contents( BG_CORE_DIR . '/data/towns.json' );
				self::$data = json_decode( $json, true );
			}
		}
		return self::$data;
	}

	public static function towns() {
		return self::data()['towns'];
	}

	public static function areas() {
		return self::data()['areas'];
	}

	public static function cutoff_hour() {
		return (int) ( self::data()['cutoffHour'] ?? 12 );
	}

	public static function town( $name ) {
		foreach ( self::towns() as $t ) {
			if ( $t['name'] === $name ) {
				return $t;
			}
		}
		return null;
	}

	/** Település irányítószám alapján (Budapest: '1' előtag). */
	public static function town_by_zip( $zip ) {
		$zip = preg_replace( '/\D/', '', (string) $zip );
		if ( '' === $zip ) {
			return null;
		}
		foreach ( self::towns() as $t ) {
			foreach ( $t['zips'] as $z ) {
				if ( $z === $zip || ( strlen( $z ) < 4 && 0 === strpos( $zip, $z ) ) ) {
					return $t;
				}
			}
		}
		return null;
	}

	private static function tz() {
		return new DateTimeZone( 'Europe/Budapest' );
	}

	public static function now() {
		return new DateTimeImmutable( 'now', self::tz() );
	}

	/**
	 * A járat az adott napon (Y-m-d), vagy null.
	 */
	public static function run_for_date( array $town, $ymd ) {
		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, self::tz() );
		if ( ! $day ) {
			return null;
		}
		$dow = (int) $day->format( 'w' ); // 0 = vasárnap … 6 = szombat (mint a JS getDay)
		foreach ( $town['runs'] as $run ) {
			if ( isset( $run['date'] ) ? $run['date'] === $ymd : ( isset( $run['day'] ) && (int) $run['day'] === $dow ) ) {
				return $run;
			}
		}
		return null;
	}

	/** A rendelési határidő az adott szállítási napra: előző nap 12:00. */
	public static function cutoff_for( $ymd ) {
		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, self::tz() );
		return $day->modify( '-1 day' )->setTime( self::cutoff_hour(), 0 );
	}

	/**
	 * A következő rendelhető járatok.
	 *
	 * @return array[] [ 'date' => 'Y-m-d', 'min' => int, 'cutoff' => DateTimeImmutable, 'once' => bool ]
	 */
	public static function next_deliveries( array $town, $count = 4, ?DateTimeImmutable $now = null ) {
		$now   = $now ?: self::now();
		$today = $now->setTime( 0, 0 );
		$out   = array();
		for ( $i = 1; $i <= 70 && count( $out ) < $count; $i++ ) {
			$day = $today->modify( "+{$i} day" );
			$ymd = $day->format( 'Y-m-d' );
			$run = self::run_for_date( $town, $ymd );
			if ( ! $run ) {
				continue;
			}
			$cutoff = self::cutoff_for( $ymd );
			if ( $now < $cutoff ) {
				$out[] = array(
					'date'   => $ymd,
					'min'    => (int) $run['min'],
					'cutoff' => $cutoff,
					'once'   => isset( $run['date'] ),
				);
			}
		}
		return $out;
	}

	/**
	 * Ellenőrzi a kiválasztott települést és napot. Hibaüzenetet ad vissza, vagy null-t.
	 */
	public static function validate( $town_name, $ymd, $subtotal, ?DateTimeImmutable $now = null ) {
		$town = self::town( $town_name );
		if ( ! $town ) {
			return __( 'Erre a településre jelenleg nem szállítunk. Válassz a listából.', 'bg-core' );
		}
		if ( ! $ymd || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return __( 'Válassz kiszállítási napot.', 'bg-core' );
		}
		$run = self::run_for_date( $town, $ymd );
		if ( ! $run ) {
			return __( 'Ezen a napon nincs járat a településedre. Válassz másik napot.', 'bg-core' );
		}
		$now = $now ?: self::now();
		if ( $now >= self::cutoff_for( $ymd ) ) {
			return __( 'Erre a napra már lejárt a rendelési határidő (előző nap 12:00). Válassz egy későbbi napot.', 'bg-core' );
		}
		if ( $subtotal < (int) $run['min'] ) {
			/* translators: %s: minimum összeg */
			return sprintf( __( 'Erre a járatra a rendelési minimum %s. Tegyél még a kosárba, vagy válassz másik napot.', 'bg-core' ), self::ft( (int) $run['min'] ) );
		}
		return null;
	}

	public static function ft( $n ) {
		return number_format( (float) $n, 0, ',', "\u{00a0}" ) . "\u{00a0}Ft";
	}

	public static function date_label( $ymd ) {
		$d    = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, self::tz() );
		$days = array( 'vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat' );
		$mon  = array( '', 'január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december' );
		return $mon[ (int) $d->format( 'n' ) ] . ' ' . $d->format( 'j' ) . '., ' . $days[ (int) $d->format( 'w' ) ];
	}
}

/**
 * Szállítási mód: saját hűtött autós kiszállítás, díjmentes.
 * Csak akkor érhető el, ha a település a listán van (ezt a pénztár ellenőrzi).
 */
add_action( 'woocommerce_shipping_init', function () {
	if ( class_exists( 'BG_Shipping_Method' ) ) {
		return;
	}
	class BG_Shipping_Method extends WC_Shipping_Method {
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'bg_delivery';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = 'Bio-Goods kiszállítás';
			$this->method_description = 'Saját hűtött autós kiszállítás a járatnapokon, díjmentesen.';
			$this->supports           = array( 'shipping-zones', 'instance-settings' );
			$this->title              = 'Házhozszállítás saját hűtött autóval (ingyenes)';
			$this->enabled            = 'yes';
		}
		public function calculate_shipping( $package = array() ) {
			$this->add_rate(
				array(
					'id'    => $this->get_rate_id(),
					'label' => $this->title,
					'cost'  => 0,
				)
			);
		}
	}
} );

add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
	$methods['bg_delivery'] = 'BG_Shipping_Method';
	return $methods;
} );
