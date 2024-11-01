<?php

/**
 * Fired during plugin activation
 *
 * @link       https://payjoe.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 * @author     PayJoe <info@payjoe.de>
 */

require_once plugin_dir_path( __FILE__ ) . '/../helpers/class-weslink-payjoe-helpers.php';
require_once plugin_dir_path( __FILE__ ) . '/partials/constants.php';

use Automattic\Jetpack\Constants;

class Weslink_Payjoe_Opbeleg_Orders {

	/**
	 * @var array
	 */
	private $orders_to_exclude;

	/**
	 * @var string
	 */
	private $php_version;

	/**
	 * @var string
	 */
	private $wp_version;

	/**
	 * @var string
	 */
	private $wc_version;

	/**
	 * @var string
	 */
	private $user_agent;

	/**
	 * @var number
	 */
	private $plugin_type;

	public function __construct() {
		$this->orders_to_exclude = array();

		require ABSPATH . WPINC . '/version.php';
		$php_version = phpversion();
		$wc_version  = Constants::get_constant( 'WC_VERSION' );
		$version     = Constants::get_constant( 'PAYJOE_PLUGIN_VERSION' );
		$type        = (int) get_option( 'payjoe_invoice_options' );

		$this->php_version = $php_version;
		$this->wp_version  = $wp_version;
		$this->wc_version  = $wc_version;
		$this->plugin_type = $type;

		$this->user_agent = "PayJoePlugin/$version (PHP $php_version, WP $wp_version, WC $wc_version, Type $type)";
	}


	/**
	 * Returns the posts that are orders.
	 *
	 * @return WP_Post[]
	 */
	public function getOrderPosts() {
		$enable_log = get_option( 'payjoe_log' );

		try {
			$this->mapInvoiceNumbers();
			if ( $enable_log ) {
				echo( "Done mapping invoice numbers.\n" );
			}
		} catch ( Throwable $e ) {
			$message = __( 'Failed to map invoice numbers.', 'woo-payjoe-beleg-schnittstelle' );
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload-error', $message . '<pre>' . (string) $e . '</pre>' );
			echo $message . "\n" .  esc_html( (string) $e ), "\n";
			return [];
		}

		try {
			$posts = $this->_getOrderPosts();
			if ( $enable_log ) {
				echo( "Done getting order posts.\n" );
			}
			return $posts;
		} catch ( Throwable $e ) {
			$message = __( 'Failed to get order posts.', 'woo-payjoe-beleg-schnittstelle' );
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload-error', $message . '<pre>' . (string) $e . '</pre>' );
			echo $message . "\n" .  esc_html( (string) $e ), "\n";
			return [];
		}
	}

	/**
	 * Returns the posts that are orders.
	 *
	 * @return WP_Post[]
	 */
	private function _getOrderPosts() {
		$transfer_count   = (int) get_option( 'payjoe_transfer_count', 10 );
		$start_order_date = get_option( 'payjoe_start_order_date' );

		$args = array(
			'post_type'      => wc_get_order_types(),
			'posts_per_page' => $transfer_count,
			'post_status'    => array_keys( wc_get_order_statuses() ),
			'meta_key'       => '_payjoe_status',
			// Prefer orders with a low numeric _payjoe_status to avoid
			// old orders with errors blocking the upload of newer orders.
			// Then make sure older orders are uploaded first.
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'ID'             => 'ASC',
			),
			'meta_query'     => array(
				'relation' => 'AND',
				/*
				,
				array(
					'key' => '_payjoe_invoice_number',
					'value' => (int)get_option('payjoe_startrenr'),
					'type' => 'NUMERIC',
					'compare' => '>='
				)
				*/
				array(
					'key'     => '_payjoe_invoice_number',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_payjoe_status',
						'value'   => (int) PAYJOE_STATUS_OK,
						'type'    => 'NUMERIC',
						'compare' => '!=', // already submitted to API but error
					),
					array(
						'key'     => '_payjoe_status',
						'compare' => 'NOT EXISTS', // not submitted to API yet
					),
				),
			),
		);

		if ( $start_order_date ) {
			$args['date_query'] = array(
				'after'     => $start_order_date,
				'inclusive' => true,
			);
		}

		// Germanized
		if ( $this->plugin_type === PAYJOE_PLUGIN_TYPE_GERMANIZED ) {
			// Not sure if it applies to the others as well.
			$args['parent'] = 0;
		}

		$posts = new WP_Query( $args );
		return $posts->get_posts();
	}

	/**
	 * Goes through the posts and checks if the custom metadata fields contain invoice information.
	 *
	 * @param WP_Post[]|WC_Abstract_Order[] $posts
	 *
	 * @return PayJoe_Invoice[]
	 */
	public function itemsToInvoices( array $posts ) {
		$enable_log = get_option( 'payjoe_log' );

		if ( $enable_log ) {
			_e( "Converting posts to invoices...\n", 'woo-payjoe-beleg-schnittstelle' );
		}

		try {
			return $this->_itemsToInvoices( $posts );
		} catch ( Throwable $e ) {
			$message = __('Failed converting posts to invoices.', 'woo-payjoe-beleg-schnittstelle' );
			WC_Admin_Notices::add_custom_notice( 'payjoe-upload-error', $message . '<pre>' . (string) $e . '</pre>' );
			echo $message . "\n" .  esc_html( (string) $e ), "\n";
			return [];
		} finally {
			if ( $enable_log ) {
				_e( "Done converting posts to invoices.\n", 'woo-payjoe-beleg-schnittstelle' );
			}
		}
	}

	/**
	 * Goes through the posts and checks if the custom metadata fields contain invoice information.
	 *
	 * @param WP_Post[]|WC_Abstract_Order[] $posts
	 *
	 * @return PayJoe_Invoice[]
	 */
	private function _itemsToInvoices( array $posts ) {
		$invoice_objects = array();

		foreach ( $posts as $post ) {
			$item_id = NULL;
			$order_details = NULL;
			if ( $post instanceof WP_Post) {
				$item_id = $post->ID;
				$order_details = wc_get_order( $item_id );
			} else {
				$item_id = $post->get_id();
				$order_details = $post;
			}

			$invoice_id     = get_post_meta( $item_id, '_payjoe_invoice_id', true );
			$invoice_number = get_post_meta( $item_id, '_payjoe_invoice_number', true );
			$invoice_date   = get_post_meta( $item_id, '_payjoe_invoice_date', true );
			$invoices       = get_post_meta( $item_id, '_payjoe_invoices', true );

			// Will not be set for the non-germanized invoicing plugins and for orders that
			// were not yet updated. Fill it with default values.
			if ( ! $invoices ) {
				if ( ! $invoice_number || $invoice_number < 0 ) {
					// No useful data, should be ignored in the next upload
					delete_post_meta( $item_id, '_payjoe_invoice_number' );
					continue;
				}
				$invoices = array(
					array(
						'id'     => $invoice_id,
						'number' => $invoice_number,
						'date'   => $invoice_date,
						// Refunds were not handled by the old code
						'type'   => 0,
					),
				);
			}

			foreach ( $invoices as $invoice_data ) {
				$invoice_object                   = new PayJoe_Invoice( $order_details, $invoice_data['type'], $invoice_data['number'], $invoice_data['date'] );
				$invoice_object->id               = $invoice_data['id'];
				$invoice_object->refund_order_id  = isset( $invoice_data['refund_order_id'] ) ? $invoice_data['refund_order_id'] : null;
				$invoice_object->address_order_id = isset( $invoice_data['address_order_id'] ) ? $invoice_data['address_order_id'] : null;
				$invoice_object->number_formatted = isset( $invoice_data['number_formatted'] ) ? $invoice_data['number_formatted'] : null;

				if ( $this->plugin_type === PAYJOE_PLUGIN_TYPE_GERMANIZED ) {
					$sab_invoice = sab_get_invoice( $invoice_object->id );
					if ( $sab_invoice !== false ) {
						$invoice_object->total_gross = $sab_invoice->get_total();
						$invoice_object->total_net   = $sab_invoice->get_total_net();
					} else {
						echo( 'Could not get invoice '. $invoice_object->id . ' of order ' . $item_id . ".\n" );
					}
				}

				$invoice_objects[] = $invoice_object;
			}
		}

		return $invoice_objects;
	}

	/**
	 * @param PayJoe_Invoice[] $invoices
	 */
	public function uploadInvoices( array $invoices, $log_json_data = false ) {
		$numOrders = count( $invoices );
		if ( $numOrders === 0 ) {
			esc_html_e( "No invoices availabe for upload to PayJoe\n", 'woo-payjoe-beleg-schnittstelle' );
			// new WP_Error('no_orders', __('Keine Betellungen für den Export vorhanden.', 'woocommerce-simply-order-export'));
		}
		/* translators: %d number of orders */
		echo sprintf( esc_html__( "%d invoices selected for upload to PayJoe.\n", 'woo-payjoe-beleg-schnittstelle' ), esc_html( $numOrders ) );

		/**
		 * This will be returned data
		 */
		$wlOrders = array();
		/**
		 * Loop over each order
		 */
		foreach ( $invoices as $invoice ) {
			$order_id = $invoice->order->get_id();

			/* translators: %1$1s the order number, %2$2s the invoice */
			echo sprintf( esc_html__( "Will upload order %1\$1s with invoice %2\$2s\n", 'woo-payjoe-beleg-schnittstelle' ), esc_html( $order_id ), esc_html( $invoice ) );

			$obj_op_auftragsposten = $this->getOpData( $invoice );
			$wlOrders[]            = $obj_op_auftragsposten;
		}

		// API limit
		$transfer_count  = (int) get_option( 'payjoe_transfer_count', 10 );
		$chunk_size      = min( 500, $transfer_count );
		$wlOrders_chunks = array_chunk( $wlOrders, $chunk_size );

		$theOrders = array();

		foreach ( $wlOrders_chunks as $aChunk ) {
			$theOrders[] = array(
				'UserName'         => get_option( 'payjoe_username' ),
				'APIKey'           => get_option( 'payjoe_apikey' ),
				'OPBelegZugangID'  => (int) get_option( 'payjoe_zugangsid' ),
				'OPAuftragsposten' => $aChunk,
			);
		}

		foreach ( $theOrders as $aOrder ) {
			// send order to payJoe
			$result = $this->uploadBelegtoPayJoe( $aOrder );

			if ( $log_json_data ) {
				echo "\n ----------------------------  NEXT ORDER ----------------------------\n";
				echo "\n ---------------------------- API RESULT----------------------------\n";
				echo esc_html( $result );
				echo "\n ---------------------------- JSON DATA ----------------------------\n";
				$json_result = wp_json_encode( $aOrder );
				$json_data   = $json_result === false ? json_last_error_msg() : $json_result;
				echo esc_html( $json_data );
				echo "\n --------------------------------------------------------------------\n";
				echo "\n -------------------------- Data array view -------------------------\n";
				print_r( $aOrder );
				print_r( json_decode( $result ) );
				echo "\n --------------------------------------------------------------------\n";
			}

			if ( $result ) {
				$this->handleAPIResult( $result, $aOrder['OPAuftragsposten'], $log_json_data );
			}
		}
	}

	/**
	 * @param PayJoe_Invoice $invoice
	 */
	private function getOpData( PayJoe_Invoice $invoice ) {
		$enable_log = get_option( 'payjoe_log' );

		/**
		 * @var WC_Abstract_order
		 */
		$order_details  = $invoice->order;
		$invoice_number = $invoice->number;
		$invoice_date   = $invoice->date;
		$invoice_type   = $invoice->type;

		$refund_order_id  = $invoice->refund_order_id;
		$address_order_id = $invoice->address_order_id;
		$is_refund = false;

		/**
		 * @var WC_Order
		 */
		$order_base = null;
		if ( $order_details instanceof WC_Order ) {
			$order_base = $order_details;
		} elseif ( $order_details instanceof WC_Order_Refund ) {
			$order_base = wc_get_order( $order_details->get_parent_id() );
			$is_refund = true;
		}

		/**
		 * @var WC_Order_Refund
		 */
		$refund_details = null;
		if ( $refund_order_id > 0 && $order_details->get_id() !== $refund_order_id ) {
			$refund_details = wc_get_order( $refund_order_id );
			$is_refund = true;
		}

		/**
		 * @var WC_Order
		 */
		$address_details = $order_base;
		// Use different order for address if needed
		if ( $address_order_id > 0 && $order_details->get_id() !== $address_order_id ) {
			$address_details = wc_get_order( $address_order_id );
		}

		// Since 1.5.0 for germanized
		$invoice_id               = $invoice->id;
		$invoice_number_formatted = $invoice->number_formatted;

		if ( $invoice_date ) {
			$m_datetime   = new DateTime( $invoice_date );
			$invoice_date = $m_datetime->format( 'c' );
		} else {
			$invoice_date = $order_details->order_date;
		}

		// OPBeleg - invoice
		$currency_code = Weslink_Payjoe_Helpers::get_currency_code( $order_details->get_currency() );

		$obj_op_auftragsposten            = array();
		$obj_op_auftragsposten['OPBeleg'] = array(
			'OPBelegZugangID'       => intval( get_option( 'payjoe_zugangsid' ) ),
			'OPBelegtyp'            => $invoice_type, // 0= rechnung, 1= gutschrift
			'OPZahlungsArt'         => $order_base->get_payment_method(),
			'OPBelegHerkunft'       => 'woocommerce',
			'OPBelegdatum'          => $invoice_date,
			'OPBelegNummer'         => $invoice_number,
			// _wcpdf_invoice_number
			'OPBelegKundenNr'       => $order_base->get_customer_id(),
			// get_post_meta(get_the_ID, '_customer_user', true);
			// 'OPBelegDebitorenNr'    =>  "", // ?
			'OPBelegBestellNr'      => $order_base->get_order_number(),
			'OPBelegWaehrung'       => intval( $currency_code ? $currency_code : '' ),
			// currency code
			// 'OPBelegUstID'          =>  "", // VAT ID <--- is customer VAT ID ?
			'OPBelegTransaktionsID' => $order_base->get_transaction_id(),
			// 'OPBelegFaelligBis'     =>  "", // ? due date
			// Post ID can be different from order number
			'OPBelegReferenz1'      => $order_details->get_id(),
			'OPBelegReferenz2'      => $invoice_number_formatted,
			// Will be overwritten with _ebay_order_id, magnalister MOrderID
			// 'OPBelegReferenz3'      =>  "",
			// Will be overwritten with _ebay_extended_order_id, magnalister ExtendedOrderID
			// 'OPBelegReferenz4'      =>  "",
			// Will be overwritten with _ebay_user_id, magnalister BuyerUsername
			// 'OPBelegReferenz5'      =>  ""
		);

		$this->enrich_order( $obj_op_auftragsposten['OPBeleg'], $order_base, $is_refund );

		// OPBelegLieferadresse - delivery address
		$obj_op_auftragsposten['OPBelegLieferadresse'] = array(
			'OPBelegAdresseLand'    => $address_details->get_shipping_country(),
			'OPBelegAdresseFirma'   => $address_details->get_shipping_company(),
			'OPBelegAdresseName'    => $address_details->get_shipping_last_name(),
			'OPBelegAdresseVorname' => $address_details->get_shipping_first_name(),
			'OPBelegAdresseEmail'   => $address_details->get_billing_email(),
			// not available, so we reuse here the billing address
			'OPBelegAdresseStrasse' => $address_details->get_shipping_address_1() . ' ' . $address_details->get_shipping_address_2(),
			'OPBelegAdressePLZ'     => $address_details->get_shipping_postcode(),
			'OPBelegAdresseOrt'     => $address_details->get_shipping_city(),
		);

		// OPBelegRechnungsadresse - billing address
		$obj_op_auftragsposten['OPBelegRechnungsadresse'] = array(
			'OPBelegAdresseLand'    => $address_details->get_billing_country(),
			'OPBelegAdresseFirma'   => $address_details->get_billing_company(),
			'OPBelegAdresseName'    => $address_details->get_billing_last_name(),
			'OPBelegAdresseVorname' => $address_details->get_billing_first_name(),
			'OPBelegAdresseEmail'   => $address_details->get_billing_email(),
			'OPBelegAdresseStrasse' => $address_details->get_billing_address_1() . ' ' . $address_details->get_billing_address_2(),
			'OPBelegAdressePLZ'     => $address_details->get_billing_postcode(),
			'OPBelegAdresseOrt'     => $address_details->get_billing_city(),
		);

		if ( strlen( trim( $obj_op_auftragsposten['OPBelegLieferadresse']['OPBelegAdresseLand'] ) ) === 0 ) {
			$obj_op_auftragsposten['OPBelegLieferadresse']['OPBelegAdresseLand'] = $obj_op_auftragsposten['OPBelegRechnungsadresse']['OPBelegAdresseLand'];
		}

		// For the OP positons the refund is relevant
		if ( $refund_details ) {
			$order_details = $refund_details;
		}

		// OPBelegpositionen - invoice items
		$obj_op_auftragsposten['OPBelegpositionen'] = array();

		// create OPBelegposition Objects
		$items            = $order_details->get_items( array( 'line_item', 'coupon', 'fee', 'shipping', 'tax' ) );
		$OPBelegpositions = array();

		// tax group
		foreach ( $items as $item_id => $item ) {
			$this->itemToOpPositions( $OPBelegpositions, $item, $enable_log );
		}

		// discount / coupon amount
		// Each line item might be discounted by an arbitrary amount. Sum these amounts
		// by their tax class and add them to the OP positions.
		if ( $enable_log ) {
			echo sprintf( "\tDiscount: %1\$1s, tax discount: %2\$2s<br>", esc_html( $order_details->get_discount_total() ), esc_html( $order_details->get_discount_tax() ) );
		}

		$discount_by_tax_class = array(
			'net'   => array(),
			'tax'   => array(),
			'gross' => array(),
		);
		foreach ( $order_details->get_items() as $line_item ) {
			$discount_net   = 0;
			$discount_tax   = 0;
			$discount_gross = 0;

			// Calculate the net discount and tax discount
			$discount_net = $line_item->get_subtotal() - $line_item->get_total();

			if ( $line_item instanceof WC_Order_Item_Product ) {
				$discount_tax = $line_item->get_subtotal_tax() - $line_item->get_total_tax();
			}

			$discount_gross = $discount_net + $discount_tax;

			if ( $enable_log ) {
				echo "\t\t" . esc_html( $line_item->get_name() ) . ': '
					. 'Discount net ' . esc_html( $discount_net ) . ', '
					. 'discount tax: ' . esc_html( $discount_tax ) . '<br>';
			}

			if ( abs( $discount_gross ) < 0.01 ) {
				continue;
			}

			$tax_class = $line_item->get_tax_class();

			if ( ! array_key_exists( $tax_class, $discount_by_tax_class['net'] ) ) {
				$discount_by_tax_class['net'][ $tax_class ] = 0;
			}
			if ( ! array_key_exists( $tax_class, $discount_by_tax_class['tax'] ) ) {
				$discount_by_tax_class['tax'][ $tax_class ] = 0;
			}
			if ( ! array_key_exists( $tax_class, $discount_by_tax_class['gross'] ) ) {
				$discount_by_tax_class['gross'][ $tax_class ] = 0;
			}

			$discount_by_tax_class['net'][ $tax_class ]   += $discount_net;
			$discount_by_tax_class['tax'][ $tax_class ]   += $discount_tax;
			$discount_by_tax_class['gross'][ $tax_class ] += $discount_gross;
		}

		foreach ( $discount_by_tax_class['net'] as $tax_class => $discount_net ) {
			$discount_tax   = $discount_by_tax_class['tax'][ $tax_class ];
			$discount_gross = $discount_by_tax_class['gross'][ $tax_class ];
			$discount_rate  = 0;

			$tax_rates = WC_Tax::get_rates( $tax_class );
			if ( count( $tax_rates ) > 0 ) {
				// Return first an single element of array
				$discount_rate = current( $tax_rates )['rate'];
			}

			$OPBelegpositions[ 'discount-' . $tax_class ] = array(
				'OPBelegBuchungstext'       => 1, // Discount
				'OPBelegPostenGesamtNetto'  => round( $discount_net, 4 ),
				'OPBelegPostenGesamtBrutto' => round( $discount_gross, 4 ),
				'OPBelegSteuersatz'         => $discount_rate,
			);
		}

		// Check if all amounts match
		if ( $invoice->total_net !== null && $invoice->total_gross !== null ) {
			$expected_net   = $invoice->total_net;
			$expected_gross = $invoice->total_gross;
			$op_net         = 0;
			$op_gross       = 0;
			foreach ( $OPBelegpositions as $key => $op_pos ) {
				switch ( $op_pos['OPBelegBuchungstext'] ) {
					case 1: // Discount
						$op_net   -= abs( $op_pos['OPBelegPostenGesamtNetto'] );
						$op_gross -= abs( $op_pos['OPBelegPostenGesamtBrutto'] );
						break;
					default:
						$op_net   += $op_pos['OPBelegPostenGesamtNetto'];
						$op_gross += $op_pos['OPBelegPostenGesamtBrutto'];
						break;
				}
			}

			$diff_net   = round( $expected_net - $op_net, 2 );
			$diff_gross = round( $expected_gross - $op_gross, 2 );
			$op_net     = round( $op_net, 2 );
			$op_gross   = round( $op_gross, 2 );

			echo wp_kses( "<p>Expected: \tnet $expected_net, gross $expected_gross<br>got: \t\tnet $op_net, gross $op_gross<br>diff: \t\tnet $diff_net, gross $diff_gross</p>", 'post' );
		}

		// print_r($OPBelegpositions);

		$obj_op_auftragsposten['OPBelegpositionen'] = array_values( $OPBelegpositions );

		return $obj_op_auftragsposten;
	}

	private function enrich_order( array &$order_data, \WC_Abstract_Order &$order, bool $is_refund ) {
		// Ref 3, 4, 5 are fee

		// Set by WP-Lister for EBay
		if ( $order->meta_exists( '_ebay_order_id' ) ) {
			$order_data['OPBelegReferenz3'] = $order->get_meta( '_ebay_order_id' );
		}

		if ( $order->meta_exists( '_ebay_extended_order_id' ) ) {
			$order_data['OPBelegReferenz4'] = $order->get_meta( '_ebay_extended_order_id' );
		}

		if ( $order->meta_exists( '_ebay_user_id' ) ) {
			$order_data['OPBelegReferenz5'] = $order->get_meta( '_ebay_user_id' );
		}

		// Set by WP-Lister for Amazon
		if ( $order->meta_exists( '_wpla_amazon_order_id' ) ) {
			$order_data['OPBelegReferenz3'] = $order->get_meta( '_wpla_amazon_order_id' );
		}

		// Set by WooCommerce Amazon Pay
		// See woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php

		// P02-1234567-3578635-C016359
		if ( $order->meta_exists( 'amazon_charge_id' ) ) {
			$order_data['OPBelegReferenz3'] = $order->get_meta( 'amazon_charge_id' );
		} else if ( $order->meta_exists( 'amazon_capture_id' ) ) {
			$order_data['OPBelegReferenz3'] = $order->get_meta( 'amazon_capture_id' );
		}

		// P02-1234567-3578635-R076082
		if ( $is_refund ) {
			if ( $order->meta_exists( 'amazon_refund_id' ) ) {
				$order_data['OPBelegReferenz3'] = $order->get_meta( 'amazon_refund_id' );
			}
		}

		// P02-1234567-3578635
		if ( $order->meta_exists( 'amazon_charge_permission_id' ) ) {
			$order_data['OPBelegReferenz4'] = $order->get_meta( 'amazon_charge_permission_id' );
		}

		if ( empty( $order_data['OPBelegTransaktionsID'] ) ) {
			$order_data['OPBelegTransaktionsID'] = $order_data['OPBelegReferenz3'];
		}

		$this->enrich_order_from_magnalister( $order_data, $order );
	}

	private function enrich_order_from_magnalister( array &$order_data, \WC_Abstract_Order &$order ) {
		if ( ! defined( 'MAGNALISTER_PLUGIN_CORE_FILE' ) ) {
			return;
		}

		require_once MAGNALISTER_PLUGIN_CORE_FILE;

		if ( ! ML::isInstalled() ) {
			return;
		}

		$ml_order      = MLOrder::factory()->set( 'current_orders_id', $order->get_id() );
		$ml_order_data = $ml_order->get( 'data' );
		// var_dump ( $ml_order_data );

		// eBay, Amazon, Check24
		if ( isset( $ml_order_data['MOrderID'] ) ) {
			$value                               = $ml_order_data['MOrderID'];
			$order_data['OPBelegReferenz3']      = $value;
			$order_data['OPBelegTransaktionsID'] = $value;
		}

		// eBay
		if ( isset( $ml_order_data['ExtendedOrderID'] ) ) {
			$value                               = $ml_order_data['ExtendedOrderID'];
			$order_data['OPBelegReferenz4']      = $value;
			$order_data['OPBelegTransaktionsID'] = $value;
		}

		// eBay, Check24
		if ( isset( $ml_order_data['BuyerUsername'] ) ) {
			$value                          = $ml_order_data['BuyerUsername'];
			$order_data['OPBelegReferenz5'] = $value;
		}
	}

	private function itemToOpPositions( array &$opPositions, \WC_Order_Item $item, bool $enable_log = false ) {
		$name           = $item->get_type();
		$opPositionType = null;
		$taxes          = array();
		$item_total     = 0;
		$item_tax_total = 0;

		$split_taxes = false;

		if ( $item instanceof WC_Order_Item_Product ) {
			$opPositionType = 0;
			$tax_data       = $item->get_taxes();
			// in case discount/coupon is applied, use 'subtotal' instead which is the amount before
			// the discount. Discounts will be handled separately.
			$taxes          = array_key_exists( 'subtotal', $tax_data ) ? $tax_data['subtotal'] : $tax_data['total'];
			$item_total     = $item->get_subtotal();
			$item_tax_total = $item->get_subtotal_tax();
		} elseif ( $item instanceof WC_Order_Item_Fee ) {
			$opPositionType = 3;
			$item_total     = $item->get_total();
			$item_tax_total = $item->get_total_tax();
			$tax_data       = $item->get_taxes();
			$taxes          = $tax_data['total'];
			$split_taxes    = true;
		} elseif ( $item instanceof WC_Order_Item_Shipping ) {
			$opPositionType = 2;
			$item_total     = $item->get_total();
			$item_tax_total = $item->get_total_tax();
			$tax_data       = $item->get_taxes();
			$taxes          = $tax_data['total'];
			$split_taxes    = true;
		} elseif ( $item instanceof WC_Order_Item_Coupon ) {
			$opPositionType = 1;
			// Coupons are applied directly to the product amount and will be handled by the separate
			// discount code.
			return;
		} elseif ( $item instanceof WC_Order_Item_Tax ) {
			$opPositionType = 1;
			// Summary of the taxes, can be ignored.
			return;
		} else {
			// TODO: Log
			return;
		}

		if ( $enable_log ) {
			echo "\tPosition: " . esc_html( $name ) . ' (' . esc_html( $item->get_name() ) . ')<br>';
		}

		// Some items might not be taxed but still contain entries in the taxes array.
		// Filter empty values and check if anything remains.
		$taxes = array_filter( $taxes, 'is_numeric' );
		if ( $split_taxes && count( $taxes ) ) {
			foreach ( $taxes as $tax_key => $tax_amount ) {
				if ( ! is_numeric( $tax_amount ) ) {
					continue;
				}

				// Calculated the amount that was taxed based on the tax amount and tax rate.
				// Adding the tax amount to the taxed amount results in the gross value.
				$percent_value = WC_Tax::get_rate_percent_value( $tax_key );
				if ( abs( $percent_value ) < 0.01 ) {
					// Manually added tax amount might not have a rate.
					// Use the tax amount as the base amount.
					$net_amount = 0;
				} else {
					$net_amount = $tax_amount / ( $percent_value / 100.0 );
				}

				$gross_amount = $net_amount + $tax_amount;

				if ( $enable_log ) {
					echo esc_html( "\t\tTax $tax_key: amount $tax_amount ($percent_value%), net $net_amount, gross $gross_amount" ) . '<br>';
				}

				$key = "$name-tax-$tax_key";

				if ( ! array_key_exists( $key, $opPositions ) ) {
					$opPositions[ $key ] = array(
						'OPBelegBuchungstext'       => $opPositionType,
						'OPBelegPostenGesamtNetto'  => 0,
						'OPBelegPostenGesamtBrutto' => 0,
						'OPBelegSteuersatz'         => $percent_value,
						// 'OPSteuerschluessel'        =>  '',
						// 'OPBelegKostenstelle'       =>  '',
						// 'OPBelegKostentraeger'      =>  '',
					);
				}

				$opPositions[ $key ]['OPBelegPostenGesamtNetto']  += round( $net_amount, 4 );
				$opPositions[ $key ]['OPBelegPostenGesamtBrutto'] += round( $gross_amount, 4 );
			}
		} else {
			$tax_key       = array_key_first( $taxes );
			$percent_value = WC_Tax::get_rate_percent_value( $tax_key );
			// $percent_value = round(($item_tax_total / $item_total * 100), 2);
			$gross_amount = $item_total + $item_tax_total;

			if ( $enable_log ) {
				echo esc_html( "\t\tTax $item_tax_total ($percent_value%), net $item_total, gross $gross_amount" ) . '<br>';
			}

			$key = "$name-percent-$percent_value";

			if ( ! array_key_exists( $key, $opPositions ) ) {
				$opPositions[ $key ] = array(
					'OPBelegBuchungstext'       => $opPositionType,
					'OPBelegPostenGesamtNetto'  => 0,
					'OPBelegPostenGesamtBrutto' => 0,
					'OPBelegSteuersatz'         => $percent_value,
					// 'OPSteuerschluessel'        =>  '',
					// 'OPBelegKostenstelle'       =>  '',
					// 'OPBelegKostentraeger'      =>  '',
				);
			}

			$opPositions[ $key ]['OPBelegPostenGesamtNetto']  += round( $item_total, 4 );
			$opPositions[ $key ]['OPBelegPostenGesamtBrutto'] += round( $gross_amount, 4 );
		}
	}

	private function getInvoiceString( $number, $date ) {
		return "pjin_${number}_${date}";
	}

	/**
	 * @return int[]
	 */
	private function queryWcpdfOrders( $mapRecent = true ) {
		$enable_log       = get_option( 'payjoe_log' );
		$transfer_count   = (int) get_option( 'payjoe_transfer_count', 10 );
		$start_order_date = get_option( 'payjoe_start_order_date' );

		$recentOrders = array();
		if ( $mapRecent ) {
			// Process recently updated orders. Necessary to detect refunds.
			$query = new WC_Order_Query(
				array(
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'return'         => 'ids',
					// 'parent' => 0,
					'date_modified'  => '>' . ( time() - DAY_IN_SECONDS ),
					// See handle_payjoe_invoice_query_var
					'_payjoe_status' => array( 'compare' => 'EXISTS' ),
				)
			);

			$recentOrders = $query->get_orders();
			if ( $enable_log ) {
				echo count( $recentOrders ) . ' recent orders to preprocess.<br>';
			}
		}

		// Unprocessed orders where the extracted invoice number is not set.
		// Do that in batches to decrease the initial load. Because it has
		// the same sort order but a bigger limit than the actual upload query
		// it is a given, that there are enough processed orders.
		$args = array(
			'limit'          => $transfer_count * 2,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'return'         => 'ids',
			// 'parent' => 0,
			'exclude'        => $recentOrders,
			// See handle_payjoe_invoice_query_var
			'_payjoe_status' => array( 'compare' => 'NOT EXISTS' ),
		);
		if ( $start_order_date ) {
			$args['date_created'] = '>=' . $start_order_date;
		}

		$query = new WC_Order_Query( $args );

		$unprocessedOrders = $query->get_orders();
		echo( count( $unprocessedOrders ) . " unpreprocessed orders.\n" );

		$orders = array_merge( $unprocessedOrders, $recentOrders );

		return $orders;
	}

	/**
	 * @param int[] $order_ids
	 */
	private function mapWcpdfInvoiceNumbers( array $order_ids ) {
		$enable_log = get_option( 'payjoe_log' );

		if ( $enable_log ) {
			echo( 'Preprocessing ' . count( $order_ids ) . " orders\n" );
		}

		foreach ( $order_ids as $order_id ) {
			/**
			 * @var \WPO\WC\PDF_Invoices\Documents\Invoice
			 */
			$invoice = wcpdf_get_invoice( $order_id );
			/**
			 * @var \WC_Order
			 */
			$order = $invoice->order;

			if ( ! $invoice ) {
				update_post_meta( $order->get_id(), '_payjoe_status', PAYJOE_STATUS_PENDING );
				continue;
			}

			$number = $invoice->get_number();
			if ( ! $number ) {
				update_post_meta( $order->get_id(), '_payjoe_status', PAYJOE_STATUS_PENDING );
				continue;
			}

			echo esc_html( "Order $order_id:\n" );

			$invoiceData = array();

			$invoice_id        = $order->get_id();
			$invoice_number    = $number->number;
			$invoice_formatted = $number->formatted_number;
			$invoice_date      = date_format( $invoice->get_date(), 'c' );

			if ( $enable_log ) {
				echo esc_html( "    InvoiceDate: $invoice_date, InvoiceId $invoice_id, Number: $invoice_number, Formatted: $invoice_formatted \n" );
			}

			$invoiceData[] = array(
				'id'               => $invoice_id,
				'number'           => $invoice_number,
				'number_formatted' => $invoice_formatted,
				// 'refund_order_id' => $invoice->get_refund_parent_id($order),
				'date'             => $invoice_date,
				// Refund or not
				'type'             => $invoice->is_refund( $order ) ? 1 : 0,
			);

			$this->preprocessUpdatePostMeta( $order->get_id(), $invoiceData, $enable_log );
		}
	}

	/**
	 * @return int[]
	 */
	private function queryGermanizedOrders( $mapRecent = true ) {
		$enable_log       = get_option( 'payjoe_log' );
		$transfer_count   = (int) get_option( 'payjoe_transfer_count', 10 );
		$start_order_date = get_option( 'payjoe_start_order_date' );

		$recentOrders = array();
		if ( $mapRecent ) {
			// Process recently updated orders. Necessary to detect refunds.
			$query = new WC_Order_Query(
				array(
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'return'         => 'ids',
					'parent'         => 0,
					'date_modified'  => '>' . ( time() - DAY_IN_SECONDS ),
					// See handle_payjoe_invoice_query_var
					'_payjoe_status' => array( 'compare' => 'EXISTS' ),
				)
			);

			$recentOrders = $query->get_orders();
			if ( $enable_log ) {
				echo count( $recentOrders ) . " recent orders to preprocess.\n";
			}
		}

		// Unprocessed orders where the extracted invoice number is not set.
		// Do that in batches to decrease the initial load. Because it has
		// the same sort order but a bigger limit than the actual upload query
		// it is a given, that there are enough processed orders.
		$args = array(
			'limit'          => $transfer_count * 2,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'return'         => 'ids',
			'parent'         => 0, // We get all the invoices from the parent
			'exclude'        => $recentOrders,
			// See handle_payjoe_invoice_query_var
			'_payjoe_status' => array( 'compare' => 'NOT EXISTS' ),
		);
		if ( $start_order_date ) {
			$args['date_created'] = '>=' . $start_order_date;
		}

		$query = new WC_Order_Query( $args );

		$unprocessedOrders = $query->get_orders();
		echo( count( $unprocessedOrders ) . " unpreprocessed orders.\n" );

		$orders = array_merge( $unprocessedOrders, $recentOrders );

		return $orders;
	}

	/**
	 * Maps the Germanized Invoice numbers to _payjoe_invoice_number. That
	 * way the upload code can just use standardized fields for the different
	 * invoicing plugins.
	 *
	 * @param int[] $orders
	 */
	private function mapGermanizedInvoiceNumber( array $orders ) {
		$enable_log = get_option( 'payjoe_log' );
		if ( $enable_log ) {
			echo( 'Preprocessing ' . count( $orders ) . " orders\n" );
		}

		foreach ( $orders as $order ) {
			// Returns the documents only for the main order but will return nothing if
			// the refund is passed. Check the invoices refund_order_id to see if it belongs
			// to a refund.
			$invoices    = wc_gzdp_get_invoices_by_order( $order );
			$invoiceData = array();

			if ( $enable_log ) {
				echo esc_html( "Order $order:\n" );
			}

			foreach ( $invoices as $invoice ) {
				$invoice_formatted = $invoice->formatted_number;
				$invoice_date      = date_format( $invoice->get_document()->get_date_created(), 'c' );
				$invoice_id        = $invoice->id;
				$invoice_number    = $invoice->number;

				if ( $enable_log ) {
					echo esc_html( "    InvoiceDate: $invoice_date, InvoiceId $invoice_id, Number: $invoice_number, Formatted: $invoice_formatted \n" );
				}

				$invoiceData[] = array(
					'id'               => $invoice->id,
					'number'           => $invoice->number,
					'number_formatted' => $invoice->formatted_number,
					'refund_order_id'  => $invoice->refund_order_id,
					'date'             => $invoice_date,
					// Refund or not
					'type'             => $invoice->type === 'cancellation' ? 1 : 0,
				);
			}

			$this->preprocessUpdatePostMeta( $order, $invoiceData, $enable_log );
		}
	}

	/**
	 * @return int[]
	 */
	private function queryGermanMarketOrders( $mapRecent = true ) {
		$enable_log       = get_option( 'payjoe_log' );
		$transfer_count   = (int) get_option( 'payjoe_transfer_count', 10 );
		$start_order_date = get_option( 'payjoe_start_order_date' );

		$recentOrders = array();
		if ( $mapRecent ) {
			// Process recently updated orders. Necessary to detect refunds.
			$query = new WC_Order_Query(
				array(
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'return'         => 'ids',
					'date_modified'  => '>' . ( time() - DAY_IN_SECONDS ),
					// See handle_payjoe_invoice_query_var
					'_payjoe_status' => array( 'compare' => 'EXISTS' ),
				)
			);

			$recentOrders = $query->get_orders();
			if ( $enable_log ) {
				echo count( $recentOrders ) . " recent orders to preprocess.\n";
			}
		}

		// Unprocessed orders where the extracted invoice number is not set.
		// Do that in batches to decrease the initial load. Because it has
		// the same sort order but a bigger limit than the actual upload query
		// it is a given, that there are enough processed orders.
		$args = array(
			'limit'          => $transfer_count * 2,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'return'         => 'ids',
			'exclude'        => $recentOrders,
			// See handle_payjoe_invoice_query_var
			'_payjoe_status' => array( 'compare' => 'NOT EXISTS' ),
		);
		if ( $start_order_date ) {
			$args['date_created'] = '>=' . $start_order_date;
		}

		$query = new WC_Order_Query( $args );

		$unprocessedOrders = $query->get_orders();
		echo( count( $unprocessedOrders ) . " unpreprocessed orders.\n" );

		$orders = array_merge( $unprocessedOrders, $recentOrders );

		return $orders;
	}

	/**
	 * @param int[] $orders
	 */
	private function mapGermanMarketInvoiceNumber( array $orders ) {
		$enable_log = get_option( 'payjoe_log' );
		if ( $enable_log ) {
			echo( 'Preprocessing ' . count( $orders ) . " orders\n" );
		}

		if ( ! count( $orders ) ) {
			return;
		}

		// Change Placeholdes in German Market Invoice String
		$placeholder_date_time = new DateTime();
		$search                = array( '{{year}}', '{{year-2}}', '{{month}}', '{{day}}' );
		$replace               = array( $placeholder_date_time->format( 'Y' ), $placeholder_date_time->format( 'y' ), $placeholder_date_time->format( 'm' ), $placeholder_date_time->format( 'd' ) );
		$prefix_length         = strlen( str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_prefix', '' ) ) );
		$suffix_length         = strlen( str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_suffix', '' ) ) );

		$prefix_refund_length = strlen( str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_prefix_refund', '' ) ) );
		$suffix_refund_length = strlen( str_replace( $search, $replace, get_option( 'wp_wc_running_invoice_number_suffix_refund', '' ) ) );

		foreach ( $orders as $order ) {
			$invoiceData = array();

			if ( $enable_log ) {
				echo esc_html( "Order $order:\n" );
			}

			$german_market_invoice_number = get_post_meta( $order, '_wp_wc_running_invoice_number', true );
			$german_market_invoice_date   = get_post_meta( $order, '_wp_wc_running_invoice_number_date', true );
			$address_order_id             = $order;
			$is_refund                    = get_post_meta( $order, '_refund_amount', true );

			if ( $german_market_invoice_number ) {
				if ( $enable_log ) {
					echo esc_html( "    InvoiceDate: $german_market_invoice_date, InvoiceId $order, Formatted: $german_market_invoice_number \n" );
				}

				$pl = $prefix_length;
				$sl = $suffix_length;

				// Refunds need special handling because they can have different pre and suffixes and also
				// have no date metadata field.
				if ( $is_refund ) {
					$refund_details             = wc_get_order( $order );
					$parent_id                  = $refund_details->get_parent_id();
					$german_market_invoice_date = $refund_details->get_date_created()->getTimestamp();

					$parent_invoice_number = get_post_meta( $parent_id, '_wp_wc_running_invoice_number', true );

					// Use address from parent order
					$address_order_id = $parent_id;

					$pl = $prefix_refund_length;
					$sl = $suffix_refund_length;

					echo esc_html( "    Is a refund, parent order: $parent_id - $parent_invoice_number ($german_market_invoice_date)\n" );
				}

				// Extract the running number: INV-123-2020 -> 123
				$payjoe_invoice_number = $german_market_invoice_number;
				if ( $pl > 0 ) {
					$payjoe_invoice_number = substr( $payjoe_invoice_number, $pl );
				}
				if ( $sl > 0 ) {
					$payjoe_invoice_number = substr( $payjoe_invoice_number, 0, -$sl );
				}

				$payjoe_invoice_number = (int) $payjoe_invoice_number;

				if ( $enable_log ) {
					echo esc_html( "    Extracted running number: $payjoe_invoice_number\n" );
				}

				$invoiceData[] = array(
					'id'               => $order,
					'number'           => $payjoe_invoice_number,
					'number_formatted' => $german_market_invoice_number,
					'address_order_id' => $address_order_id,
					'date'             => gmdate( 'c', $german_market_invoice_date ),
					'type'             => $is_refund ? 1 : 0,
				);
			}

			$this->preprocessUpdatePostMeta( $order, $invoiceData, $enable_log );
		}
	}

	private function preprocessUpdatePostMeta( int $order, $invoiceData, $enable_log ) {
		$maxInvoiceId     = -1;
		$maxInvoiceDate   = null;
		$maxInvoiceNumber = null;

		foreach ( $invoiceData as $inv ) {
			if ( $inv['id'] > $maxInvoiceId ) {
				$maxInvoiceId     = $inv['id'];
				$maxInvoiceDate   = $inv['date'];
				$maxInvoiceNumber = $inv['number'];
			}
		}

		// Make a string with all invoice numbers and dates that allows
		// direct DB queries with LIKE.
		// WHERE _payjoe_invoice_string LIKE '%pjin_123_2020-10-12T14:43:18%'
		$func          = function( $value ) {
			return $this->getInvoiceString( $value['number'], $value['date'] );
		};
		$invoiceString = join( ';', array_map( $func, $invoiceData ) );

		if ( $enable_log ) {
			echo esc_html( "    -> Most recent InvoiceId: $maxInvoiceId \n" );
		}

		// If there are new invoices or cancellations the invoice string will change and
		// the state of the order will be resetted so that the upload code will pick up the
		// order again.
		$prevInvoiceString = get_post_meta( $order, '_payjoe_invoice_string', true );
		if ( update_post_meta( $order, '_payjoe_invoice_string', $invoiceString, $prevInvoiceString ) ) {
			if ( $enable_log ) {
				echo esc_html( "    -> '$prevInvoiceString' changed to '$invoiceString'\n" );
			}

			// Reset state on value change, otherwise it will not be picked up for upload
			update_post_meta( $order, '_payjoe_status', PAYJOE_STATUS_PENDING );

			// Array with available invoices for the order
			update_post_meta( $order, '_payjoe_invoices', $invoiceData );

			// Set these to the most recent invoice for filtering and backwards compatibility.
			// _payjoe_invoice_number is also used to check for unprocessed orders.
			update_post_meta( $order, '_payjoe_invoice_id', $maxInvoiceId );
			update_post_meta( $order, '_payjoe_invoice_date', $maxInvoiceDate );
			update_post_meta( $order, '_payjoe_invoice_number', $maxInvoiceNumber );
		}
	}

	private function echo_error( $message ) {
		$title = __( 'Error', 'woo-payjoe-beleg-schnittstelle' );
		echo '<span style="color:red">' . esc_html( $title ) . ': ' . esc_html( $message ) . "</span>\n";
	}

	private function echo_success( $message ) {
		echo '<span style="color:green">' . esc_html( $message ) . "</span>\n";
	}

	/**
	 * @param $result
	 * @param $order_positions
	 * @param bool            $log_json_data
	 */
	public function handleAPIResult( $result, $order_positions, $log_json_data = false ) {
		$result = trim( $result );

		if ( $result ) {
			$result = json_decode( $result, true );

			if ( ! $result ) {
				echo esc_html( json_last_error_msg() );
				return;
			}
		}

		// The same order might have different results for different invoices.
		// Assume success.
		$orderResults       = array();
		$orderPostIdMapping = array();
		foreach ( $order_positions as $aOrderPos ) {
			$postId                             = $aOrderPos['OPBeleg']['OPBelegReferenz1'];
			$orderNumber                        = $aOrderPos['OPBeleg']['OPBelegBestellNr'];
			$orderResults[ $orderNumber ]       = array();
			$orderPostIdMapping[ $orderNumber ] = $postId;
		}

		if ( isset( $result['Fehlerliste'] ) ) {
			foreach ( $result['Fehlerliste'] as $errorEntry ) {
				$opNumber     = $errorEntry['OPBelegNummer'];
				$opDate       = $errorEntry['OPBelegDatum'];
				$opDateObject = new DateTime( $opDate );

				$reasons = $errorEntry['OPBelegErrorReasons'];

				if ( ! $errorEntry['OPBelegNummer'] ) {
					$this->echo_error( wp_json_encode( $reasons ) );
					continue;
				}

				$orderNumber = null;

				// Find the order that belongs to the OP Beleg
				foreach ( $order_positions as $uploadEntry ) {
					$currentNumber      = $uploadEntry['OPBeleg']['OPBelegNummer'];
					$currentDate        = new DateTime( $uploadEntry['OPBeleg']['OPBelegdatum'] );
					$currentOrderNumber = $uploadEntry['OPBeleg']['OPBelegBestellNr'];

					if ( $opNumber !== $currentNumber ) {
						continue;
					}

					$diff = $opDateObject->diff( $currentDate );
					if ( $diff->h === 0 && $diff->m === 0 ) {
						$orderNumber = $currentOrderNumber;
						break;
					}
				}

				if ( ! $orderNumber ) {
					$this->echo_error( "Order for $opNumber ($opDate) not found." );
					continue;
				}

				$success = false;
				$msg     = "  Invoice $opNumber ($opDate):";
				if ( isset( $reasons ) && count( $reasons ) > 0 ) {
					foreach ( $reasons as $reason ) {
						// Reason == 1 means that it was uploaded before.
						// Don't treat as error
						if ( $reason['OPBelegErrorReason'] === 1 ) {
							$msg     = 'Duplicate';
							$success = true;
							break;
						}

						$msg .= "\n  - " . $reason['OPBelegErrorText'];
					}
				}

				$orderResults[ $orderNumber ][] = array(
					'success' => $success,
					'msg'     => $msg,
				);
			}
		}

		// Go through the collected results for each order and check if there is an error.
		foreach ( $orderResults as $orderNumber => $orderResultEntry ) {
			$result  = array( 'error' => null ); // Success
			$success = true;
			foreach ( $orderResultEntry as $orderResult ) {
				if ( ! $orderResult['success'] ) {
					$result['error'] .= $orderResult['msg'];
					$success          = false;
					break;
				}
			}

			$postId = $orderPostIdMapping[ $orderNumber ];

			if ( $success ) {
				$this->echo_success( "Order $orderNumber (Post ID $postId): all invoices transferred successfully." );
			} else {
				$this->echo_error( "Order $orderNumber (Post ID $postId):\n" . $result['error'] );
			}

			do_action( 'weslink-payjoe-opbeleg-post-upload', $result, $postId );
		}

		/*
		if ($maxInvoiceId) {
			//do_action('weslink-payjoe-opbeleg-update-last-processed', $msg, $order_number, $maxInvoiceId);
		}
		*/
	}

	/**
	 * @return boolean|array
	 */
	public function getOrderByBelegnummer( $belegnummer, $date ) {
		$order_without_error = get_posts(
			array(
				'post_type'   => 'shop_order',
				'post_status' => array_keys( wc_get_order_statuses() ),
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => '_payjoe_invoice_string',
						'compare' => 'LIKE',
						'value'   => '%;' . $belegnummer . '_' . $date . ';%',
					),
					array(
						array(
							'key'   => '_payjoe_invoice_number',
							'value' => $belegnummer,
						),
						array(
							'key'   => '_payjoe_invoice_date',
							'value' => $date,
						),
					),
				),
			)
		);

		if ( ! empty( $order_without_error ) ) {
			$order_number   = $order_without_error[0]->ID;
			$invoice_number = get_post_meta( $order_without_error[0]->ID, '_payjoe_invoice_number', true );

			return array( $order_number, $invoice_number );
		}

		return false;
	}

	/**
	 * @param $data
	 * @return bool|string
	 */
	public function uploadBelegtoPayJoe( $data ) {
		$url  = 'https://api.payjoe.de/api/opbelegupload';
		$args = array(
			'body'     => wp_json_encode( $data ),
			'timeout'  => '60',
			'blocking' => true,
			'headers'  => array(
				'Content-Type' => 'application/json; charset=utf-8',
				'User-Agent: ' . $this->user_agent,
				'X-PHP-Version: ' . $this->php_version,
				'X-WP-Version: ' . $this->wp_version,
				'X-WC-Version: ' . $this->wc_version,
			),
		);

		$response  = wp_remote_post( $url, $args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || $http_code !== 200 ) {
			switch ( $http_code ) {
				case 0:
					$msg = __( 'The PayJoe servers are not reachable.', 'woo-payjoe-beleg-schnittstelle' );
					break;
				case 401:
					$msg = __( 'Invalid PayJoe credentials.', 'woo-payjoe-beleg-schnittstelle' );
					break;
				default:
					$msg = sprintf(
						/* translators: %s: The HTTP status code */
						__( 'Unknown error occurred! HTTP status code: %s', 'woo-payjoe-beleg-schnittstelle' ),
						$http_code
					);
					break;
			}
			$this->echo_error( $msg . "\n\n" );
			return null;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			return $response_body;
		}
	}


	/**
	 * @param $message
	 */
	public function sendErrorNotificationToAdmin( $message ) {
		$message = "This is an automatic email from the PayJoe Plugin for WooCommerce. There has been an error with the PayJoe Upload: \n \n'.$message.'\n If you have enabled debugging, you can check the logfiles at uploads/payjoe/ to get more information.";
		$to      = get_bloginfo( 'admin_email' );
		$subject = 'PayJoe upload error at ' . get_home_url();
		wp_mail( $to, $subject, $message );
	}

	/**
	 * @throws Exception
	 */
	public function setResendStatus() {
		delete_post_meta_by_key( '_payjoe_status' );
		delete_post_meta_by_key( '_payjoe_error' );

		delete_post_meta_by_key( '_payjoe_invoice_date' );

		// Old field
		delete_post_meta_by_key( '_payjoe_invoice_number' );

		// new Fields
		delete_post_meta_by_key( '_payjoe_invoices' );
		delete_post_meta_by_key( '_payjoe_invoice_id' );
		delete_post_meta_by_key( '_payjoe_invoice_string' );
	}

	/**
	 * Decide which plugin is used for Invoicing
	 *  get option
	 *  '0'    =>    'WooCommerce PDF Invoices & Packing Slips',
	 *  '1'    =>    'WooCommerce Germanized',
	 *  '2'    =>    'GermanMarket'
	 *
	 * @throws Exception
	 */
	public function mapInvoiceNumbers( array $order_ids = null ) {
		$mapRecent = true;
		switch ( $this->plugin_type ) {
			case PAYJOE_PLUGIN_TYPE_WCPDF:
				if ( ! function_exists( 'wcpdf_get_invoice' ) ) {
					$msg = __( 'WooCommerce PDF Invoices & Packing Slips not installed or incompatible version!', 'woo-payjoe-beleg-schnittstelle' );
					$this->echo_error( $msg );
					throw new Error( $msg );
				}

				if ( $order_ids === null ) {
					$order_ids = $this->queryWcpdfOrders( $mapRecent );
				}
				$this->mapWcpdfInvoiceNumbers( $order_ids );
				break;

			case PAYJOE_PLUGIN_TYPE_GERMANIZED:
				if ( ! function_exists( 'wc_gzdp_get_invoices_by_order' ) ) {
					$msg = __( 'Germanized not installed or incompatible version!', 'woo-payjoe-beleg-schnittstelle' );
					$this->echo_error( $msg );
					throw new Error( $msg );
				}

				if ( $order_ids === null ) {
					$order_ids = $this->queryGermanizedOrders( $mapRecent );
				}
				$this->mapGermanizedInvoiceNumber( $order_ids );
				break;

			case PAYJOE_PLUGIN_TYPE_GERMAN_MARKET:
				if ( $order_ids === null ) {
					$order_ids = $this->queryGermanMarketOrders( $mapRecent );
				}
				$this->mapGermanMarketInvoiceNumber( $order_ids );
				break;
		}
	}
}
