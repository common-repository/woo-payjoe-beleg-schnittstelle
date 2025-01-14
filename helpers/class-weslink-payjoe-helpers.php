<?php
/**
 * Helpers.
 *
 * @package    Weslink_Payjoe_Opbeleg
 */

/**
 * Helper class.
 */
class Weslink_Payjoe_Helpers {
	/**
	 * Get currency code by iso code
	 */
	public static function get_currency_code( $iso_code ) {
		$iso2numeric = array(
			'AED' => 784,
			'AFN' => 971,
			'EGP' => 818,
			'EUR' => 978,
			'ALL' => 8,
			'DZD' => 12,
			'USD' => 840,
			'USD' => 840,
			'EUR' => 978,
			'AOA' => 973,
			'XCD' => 951,
			'XCD' => 951,
			'XAF' => 950,
			'ARS' => 32,
			'AMD' => 51,
			'AWG' => 533,
			'AZN' => 944,
			'ETB' => 230,
			'AUD' => 36,
			'BSD' => 44,
			'BHD' => 48,
			'BDT' => 50,
			'BBD' => 52,
			'BYR' => 974,
			'EUR' => 978,
			'BZD' => 84,
			'XOF' => 952,
			'BMD' => 60,
			'BTN' => 64,
			'BOB' => 68,
			'USD' => 840,
			'BAM' => 977,
			'BWP' => 72,
			'BRL' => 986,
			'USD' => 840,
			'BND' => 96,
			'BGN' => 975,
			'XOF' => 952,
			'BIF' => 108,
			'CLP' => 152,
			'CNY' => 156,
			'CRC' => 188,
			'XOF' => 952,
			'ANG' => 532,
			'DKK' => 208,
			'EUR' => 978,
			'EUR' => 978,
			'XCD' => 951,
			'DOP' => 214,
			'DJF' => 262,
			'AED' => 784,
			'USD' => 840,
			'USD' => 840,
			'ERN' => 232,
			'EUR' => 978,
			'FJD' => 242,
			'EUR' => 978,
			'EUR' => 978,
			'EUR' => 978,
			'XPF' => 953,
			'XAF' => 950,
			'GMD' => 270,
			'GEL' => 981,
			'GHS' => 936,
			'GIP' => 292,
			'XCD' => 951,
			'EUR' => 978,
			'GBP' => 826,
			'EUR' => 978,
			'USD' => 840,
			'GTQ' => 320,
			'GNF' => 324,
			'XOF' => 952,
			'GYD' => 328,
			'HTG' => 332,
			'HNL' => 340,
			'HKD' => 344,
			'HKD' => 356,
			'IDR' => 360,
			'IQD' => 368,
			'IRR' => 364,
			'EUR' => 978,
			'ISK' => 352,
			'ILS' => 376,
			'EUR' => 978,
			'JMD' => 388,
			'JPY' => 392,
			'YER' => 886,
			'JOD' => 400,
			'KYD' => 136,
			'KHR' => 116,
			'XAF' => 950,
			'CAD' => 124,
			'CVE' => 132,
			'KZT' => 398,
			'QAR' => 634,
			'KES' => 404,
			'KGS' => 417,
			'AUD' => 36,
			'COP' => 170,
			'KMF' => 174,
			'XAF' => 950,
			'CDF' => 976,
			'KPW' => 408,
			'KRW' => 410,
			'EUR' => 978,
			'HRK' => 191,
			'CUP' => 192,
			'KWD' => 414,
			'LAK' => 418,
			'LSL' => 426,
			'EUR' => 978,
			'LBP' => 422,
			'LRD' => 430,
			'LYD' => 434,
			'CHF' => 756,
			'EUR' => 978,
			'MOP' => 446,
			'MAG' => 969,
			'MWK' => 454,
			'MYR' => 458,
			'MVR' => 462,
			'XOF' => 952,
			'EUR' => 978,
			'MAD' => 504,
			'USD' => 840,
			'EUR' => 978,
			'MRO' => 478,
			'MUR' => 480,
			'EUR' => 978,
			'MKD' => 807,
			'MXN' => 484,
			'USD' => 840,
			'USD' => 840,
			'MDL' => 498,
			'EUR' => 978,
			'MNT' => 496,
			'EUR' => 978,
			'XCD' => 951,
			'MZN' => 943,
			'MMK' => 104,
			'NAD' => 516,
			'AUD' => 36,
			'NPR' => 524,
			'XPF' => 953,
			'NZD' => 554,
			'NIO' => 558,
			'EUR' => 978,
			'XOF' => 952,
			'NGN' => 566,
			'USD' => 840,
			'TRY' => 949,
			'AUD' => 36,
			'NOK' => 578,
			'EUR' => 978,
			'PKR' => 586,
			'USD' => 840,
			'PAB' => 590,
			'PGK' => 598,
			'PYG' => 600,
			'PEN' => 604,
			'PHP' => 608,
			'PLN' => 985,
			'EUR' => 978,
			'USD' => 840,
			'EUR' => 978,
			'RWF' => 646,
			'RON' => 946,
			'RUB' => 643,
			'USD' => 840,
			'SBD' => 90,
			'ZMK' => 894,
			'WST' => 882,
			'EUR' => 978,
			'STD' => 678,
			'SAR' => 682,
			'SEK' => 752,
			'CHF' => 756,
			'XOF' => 952,
			'RSD' => 941,
			'SCR' => 690,
			'SLL' => 694,
			'ZWD' => 716,
			'SGD' => 702,
			'USD' => 840,
			'ANG' => 532,
			'EUR' => 978,
			'EUR' => 978,
			'SOS' => 706,
			'EUR' => 978,
			'LKR' => 144,
			'EUR' => 978,
			'XCD' => 951,
			'XCD' => 951,
			'EUR' => 978,
			'EUR' => 978,
			'XCD' => 951,
			'ZAR' => 710,
			'SDG' => 938,
			'SSP' => 938,
			'SRD' => 968,
			'SZL' => 748,
			'SYP' => 760,
			'TJS' => 972,
			'TWD' => 901,
			'TZS' => 834,
			'THB' => 764,
			'USD' => 840,
			'XOF' => 952,
			'TOP' => 776,
			'TTD' => 780,
			'XAF' => 950,
			'CZK' => 203,
			'TND' => 788,
			'TRY' => 949,
			'TMT' => 795,
			'USD' => 840,
			'AUD' => 36,
			'UGX' => 800,
			'UAH' => 980,
			'HUF' => 348,
			'UYU' => 858,
			'UZS' => 860,
			'VUV' => 548,
			'EUR' => 978,
			'VEF' => 937,
			'AED' => 784,
			'USD' => 840,
			'VND' => 704,
			'XPF' => 953,
			'AUD' => 36,
			'XAF' => 950,
			'EUR' => 978,
		);

		$currency_code = null;
		$iso_code      = ( isset( $iso_code ) && trim( $iso_code ) ) ? trim( $iso_code ) : null;

		if ( array_key_exists( $iso_code, $iso2numeric ) ) {
			$currency_code = $iso2numeric[ $iso_code ];
		}

		return $currency_code;
	}
}
