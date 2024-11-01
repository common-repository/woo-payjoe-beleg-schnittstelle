<?php

class PayJoe_Invoice {

	/**
	 * @var WC_Abstract_Order
	 */
	public $order;

	/**
	 * @var number
	 */
	public $type;

	/**
	 * @var string
	 */
	public $number;

	/**
	 * @var string
	 */
	public $date;

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var number
	 */
	public $refund_order_id;

	/**
	 * @var number
	 */
	public $address_order_id;

	/**
	 * @var string
	 */
	public $number_formatted;

	/**
	 * @var float
	 */
	public $total_net = null;

	/**
	 * @var float
	 */
	public $total_gross = null;

	public function __construct( WC_Abstract_Order $order, int $type, string $number, string $date ) {
		$this->order  = $order;
		$this->type   = $type;
		$this->number = $number;
		$this->date   = $date;
	}

	public function __toString() {
		return $this->number . ' (type ' . $this->type . ', ' . $this->date . ')';
	}
}
