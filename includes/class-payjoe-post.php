<?php

class PayJoe_Post {

	/**
	 * @param int|WP_Post|WC_Abstract_Order $post
	 */
	public static function reset( $post ) {
		if ( $post instanceof WP_Post ) {
			$id = $post->ID;
		} elseif ( $post instanceof WC_Abstract_Order ) {
			$id = $post->get_id();
		} elseif ( is_numeric( $post ) ) {
			$id = $post;
		} else {
			return;
		}

		delete_post_meta( $id, '_payjoe_status' );
		delete_post_meta( $id, '_payjoe_invoices' );
		delete_post_meta( $id, '_payjoe_invoice_id' );
		delete_post_meta( $id, '_payjoe_invoice_date' );
		delete_post_meta( $id, '_payjoe_invoice_number' );
		delete_post_meta( $id, '_payjoe_invoice_string' );
	}
}
