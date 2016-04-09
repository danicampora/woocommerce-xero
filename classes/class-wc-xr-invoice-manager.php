<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Invoice_Manager {

	/**
	 * @var WC_XR_Settings
	 */
	private $settings;

	/**
	 * WC_XR_Invoice_Manager constructor.
	 *
	 * @param WC_XR_Settings $settings
	 */
	public function __construct( WC_XR_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Method to setup the hooks
	 */
	public function setup_hooks() {

		// Check if we need to send invoices when they're completed automatically
		$option = $this->settings->get_option( 'send_invoices' );
		if ( 'creation' === $option ) {
			add_action( 'woocommerce_order_status_processing', array( $this, 'send_invoice' ) );
		} elseif ( 'completion' === $option || 'on' === $option ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'send_invoice' ) );
		}

	}

	/**
	 * Send invoice to XERO API
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function send_invoice( $order_id ) {

		// Get the order
		$order = wc_get_order( $order_id );

		// Get the invoice
		$invoice = $this->get_invoice_by_order( $order );

		// Write exception message to log
		$logger = new WC_XR_Logger( $this->settings );

		// Check if the order total is 0 and if we need to send 0 total invoices to Xero
		if ( 0 == $invoice->get_total() && 'on' !== $this->settings->get_option( 'export_zero_amount' ) ) {

			$logger->write( 'INVOICE HAS TOTAL OF 0, NOT SENDING ORDER WITH ID ' . $order->id );

			$order->add_order_note( __( "XERO: Didn't create invoice because total is 0 and send order with zero total is set to off.", 'wc-xero' ) );

			return false;
		}

		// Invoice Request
		$invoice_request = new WC_XR_Request_Invoice( $this->settings, $invoice );

		// Logging
		$logger->write( 'START XERO NEW INVOICE. order_id=' . $order->id );

		// Try to do the request
		try {
			// Do the request
			$invoice_request->do_request();

			// Parse XML Response
			$xml_response = $invoice_request->get_response_body_xml();

			// Check response status
			if ( 'OK' == $xml_response->Status ) {

				// Add order meta data
				update_post_meta( $order->id, '_xero_invoice_id', (string) $xml_response->Invoices->Invoice[0]->InvoiceID );
				update_post_meta( $order->id, '_xero_currencyrate', (string) $xml_response->Invoices->Invoice[0]->CurrencyRate );

				// Log response
				$logger->write( 'XERO RESPONSE:' . "\n" . $invoice_request->get_response_body() );

				// Add Order Note
				$order->add_order_note( __( 'Xero Invoice created.  ', 'wc-xero' ) . ' Invoice ID: ' . (string) $xml_response->Invoices->Invoice[0]->InvoiceID );

			} else { // XML reponse is not OK

				// Log reponse
				$logger->write( 'XERO ERROR RESPONSE:' . "\n" . $invoice_request->get_response_body() );

				// Format error message
				$error_message = $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message ? $xml_response->Elements->DataContractBase->ValidationErrors->ValidationError->Message : __( 'None', 'wc-xero' );

				// Add order note
				$order->add_order_note( __( 'ERROR creating Xero invoice: ', 'wc-xero' ) .
				                        __( ' ErrorNumber: ', 'wc-xero' ) . $xml_response->ErrorNumber .
				                        __( ' ErrorType: ', 'wc-xero' ) . $xml_response->Type .
				                        __( ' Message: ', 'wc-xero' ) . $xml_response->Message .
				                        __( ' Detail: ', 'wc-xero' ) . $error_message );
			}

		} catch ( Exception $e ) {
			// Add Exception as order note
			$order->add_order_note( $e->getMessage() );

			$logger->write( $e->getMessage() );

			return false;
		}

		$logger->write( 'END XERO NEW INVOICE' );

		return true;
	}

	/**
	 * Get invoice by order
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Invoice
	 */
	public function get_invoice_by_order( $order ) {

		// Date time object of order data
		$order_dt = new DateTime( $order->order_date );

		// Line Item manager
		$line_item_manager = new WC_XR_Line_Item_Manager( $this->settings );

		// Contact Manager
		$contact_manager = new WC_XR_Contact_Manager( $this->settings );

		// Create invoice
		$invoice = new WC_XR_Invoice(
			$this->settings,
			$contact_manager->get_contact_by_order( $order ),
			$order_dt->format( 'Y-m-d' ),
			$order_dt->format( 'Y-m-d' ),
			ltrim( $order->get_order_number(), '#' ),
			$line_item_manager->build_line_items( $order ),
			$order->get_order_currency(),
			round( ( floatval( $order->order_tax ) + floatval( $order->order_shipping_tax ) ), 2 ),
			$order->order_total
		);

		$invoice->set_order( $order );

		// Return invoice
		return $invoice;
	}

}