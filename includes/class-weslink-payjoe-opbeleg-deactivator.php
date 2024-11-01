<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://payjoe.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 * @author     PayJoe <info@payjoe.de>
 */
class Weslink_Payjoe_Opbeleg_Deactivator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// remove option
		$arr_options = array(
			'payjoe_username',
			'payjoe_apikey',
			'payjoe_zugangsid',
			'payjoe_interval',
			'payjoe_startrenr',
			'payjoe_log',
		);

		foreach ( $arr_options as $e_option ) {
			delete_option( $e_option );
		}

		// remove cronjob
		wp_clear_scheduled_hook( 'weslink-payjoe-opbeleg-create-cronjob' );
	}

}
