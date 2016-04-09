<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Payment_Manager {

	/**
	 * @var WC_XR_Settings
	 */
	private $settings;

	/**
	 * WC_XR_Payment_Manager constructor.
	 *
	 * @param WC_XR_Settings $settings
	 */
	public function __construct( WC_XR_Settings $settings ){
		$this->settings = $settings;
	}

	public function setup_hooks() {
		// Check if we need to send payments when they're completed automatically
		if ( 'on' === $this->settings->get_option( 'send_payments' ) ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'send_payment' ) );
		}

		add_filter( 'woocommerce_xero_order_payment_date', array( $this, 'cod_payment_set_payment_date_as_current_date' ), 10, 2 );
	}

	/**
	 * Send the payment to the XERO API
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function send_payment( $order_id ) {

		// Get the order
		$order = wc_get_order( $order_id );

		if ( ! get_post_meta( $order->id, '_xero_invoice_id', true ) ) {
			$order->add_order_note( __( 'Xero Payment not created: Invoice has not been sent.', 'wc-xero' ) );
			return false;
		}

		// Payment Request
		$payment_request = new WC_XR_Request_Payment( $this->settings, $this->get_payment_by_order( $order ) );

		// Write exception message to log
		$logger = new WC_XR_Logger( $this->settings );

		// Logging start
		$logger->write( 'START XERO NEW PAYMENT. order_id=' . $order->id );

		// Try to do the request
		try {
			// Do the request
			$payment_request->do_request();

			// Parse XML Response
			$xml_response = $payment_request->get_response_body_xml();

			// Check response status
			if ( 'OK' == $xml_response->Status ) {

				// Add post meta
				update_post_meta( $order->id, '_xero_payment_id', (string) $xml_response->Payments->Payment[0]->PaymentID );

				// Write logger
				$logger->write( 'XERO RESPONSE:' . "\n" . $payment_request->get_response_body() );

				// Add order note
				$order->add_order_note( __( 'Xero Payment created.  ', 'wc-xero' ) .
				                        ' Payment ID: ' . (string) $xml_response->Payments->Payment[0]->PaymentID );

			} else { // XML reponse is not OK

				// Logger write
				$logger->write( 'XERO ERROR RESPONSE:' . "\n" . $payment_request->get_response_body() );

				// Error order note
				$error_num = (string) $xml_response->ErrorNumber;
				$error_msg = (string) $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message;
				$order->add_order_note( __( 'ERROR creating Xero payment. ErrorNumber:' . $error_num . '| Error Message:' . $error_msg, 'wc-xero' ) );
			}

		} catch ( Exception $e ) {
			// Add Exception as order note
			$order->add_order_note( $e->getMessage() );

			$logger->write( $e->getMessage() );

			return false;
		}

		// Logging end
		$logger->write( 'END XERO NEW PAYMENT' );

		return true;
	}

	/**
	 * Get payment by order
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Payment
	 */
	public function get_payment_by_order( $order ) {

		// Get the XERO invoice ID
		$invoice_id = get_post_meta( $order->id, '_xero_invoice_id', true );

		// Get the XERO currency rate
		$currency_rate = get_post_meta( $order->id, '_xero_currencyrate', true );

		// Date time object of order data
		$order_dt = new DateTime( $order->order_date );

		// The Payment object
		$payment = new WC_XR_Payment();

		$payment->set_order( $order );

		// Set the invoice ID
		$payment->set_invoice_id( $invoice_id );

		// Set the Payment Account code
		$payment->set_code( $this->settings->get_option( 'payment_account' ) );

		// Set the payment date
		$payment->set_date( apply_filters( 'woocommerce_xero_order_payment_date', $order_dt->format( 'Y-m-d' ), $order ) );

		// Set the currency rate
		$payment->set_currency_rate( $currency_rate );

		// Set the amount
		$payment->set_amount( $order->order_total );

		return $payment;
	}

	/**
	 * If the payment gateway is set to COD, set the payment date as the current date instead of the order date.
	 */
	public function cod_payment_set_payment_date_as_current_date( $order_date, $order ) {
		$payment_method = ! empty( $order->payment_method ) ? $order->payment_method : '';
		if ( 'cod' !== $payment_method ) {
			return $order_date;
		}
		return date( 'Y-m-d', time() );
	}

}
