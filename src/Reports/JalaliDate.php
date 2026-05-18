<?php

namespace WooPilot\Bale\Reports;

defined( 'ABSPATH' ) || exit;

final class JalaliDate {

	public static function jalali_to_gregorian_date( string $date ): string {
		$date = trim( $date );

		if ( ! preg_match( '/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $date, $matches ) ) {
			return '';
		}

		$jy = absint( $matches[1] );
		$jm = absint( $matches[2] );
		$jd = absint( $matches[3] );

		$gy = 0;
		$gm = 0;
		$gd = 0;

		self::jalali_to_gregorian( $jy, $jm, $jd, $gy, $gm, $gd );

		return sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );
	}

	private static function jalali_to_gregorian( int $jy, int $jm, int $jd, int &$gy, int &$gm, int &$gd ): void {
		$jy += 1595;

		$days = -355668 + ( 365 * $jy ) + (int) ( $jy / 33 ) * 8 + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd;

		if ( $jm < 7 ) {
			$days += ( $jm - 1 ) * 31;
		} else {
			$days += ( ( $jm - 7 ) * 30 ) + 186;
		}

		$gy = 400 * (int) ( $days / 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$gy += 100 * (int) ( --$days / 36524 );
			$days %= 36524;

			if ( $days >= 365 ) {
				$days++;
			}
		}

		$gy += 4 * (int) ( $days / 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;

		$sal_a = array(
			0,
			31,
			( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28,
			31,
			30,
			31,
			30,
			31,
			31,
			30,
			31,
			30,
			31,
		);

		for ( $gm = 1; $gm <= 12 && $gd > $sal_a[ $gm ]; $gm++ ) {
			$gd -= $sal_a[ $gm ];
		}
	}
}