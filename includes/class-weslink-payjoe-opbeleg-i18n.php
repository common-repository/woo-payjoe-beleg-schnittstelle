<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://payjoe.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 * @author     PayJoe <info@payjoe.de>
 */
class Weslink_Payjoe_Opbeleg_I18n {



	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woo-payjoe-beleg-schnittstelle',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}


}
