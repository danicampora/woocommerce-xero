<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Contact_Manager {

	/**
	 * @var WC_XR_Settings
	 */
	private $settings;

	/**
	 * WC_XR_Contact_Manager constructor.
	 *
	 * @param WC_XR_Settings $settings
	 */
	public function __construct( WC_XR_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Address
	 */
	public function get_address_by_order( $order ) {

		// Setup address object
		$address = new WC_XR_Address();

		// Set line 1
		$address->set_line_1( $order->billing_address_1 );

		// Set city
		$address->set_city( $order->billing_city );

		// Set region
		$address->set_region( $order->billing_state );

		// Set postal code
		$address->set_postal_code( $order->billing_postcode );

		// Set country
		$address->set_country( $order->billing_country );

		// Set line 2
		if ( strlen( $order->billing_address_2 ) > 0 ) {
			$address->set_line_2( $order->billing_address_2 );
		}

		// Return address object
		return $address;
	}

	/**
	 * Returns a xero contact ID based on an email address if one is found
	 * null otherwise
	 * @param  string $email
	 * @return string|null
	 */
	public function get_id_by_email( $email ) {

		if ( ! $email ) {
			return null;
		}

		$contact_request = new WC_XR_Request_Contact( $this->settings, $email );
		$contact_request->do_request();
		$xml_response = $contact_request->get_response_body_xml();

		if ( 'OK' == $xml_response->Status ) {
			if ( ! empty( $xml_response->Contacts ) && $xml_response->Contacts->Contact->ContactID->__toString() ) {
				return $xml_response->Contacts->Contact->ContactID->__toString();
			}
		}

		return null;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Contact
	 */
	public function get_contact_by_order( $order ) {
		// Setup Contact object
		$contact = new WC_XR_Contact();

		// Set Invoice name
		if ( strlen( $order->billing_company ) > 0 ) {
			$invoice_name = $order->billing_company;
		} else {
			$invoice_name = $order->billing_first_name . ' ' . $order->billing_last_name;
		}

		$contact_id = $this->get_id_by_email( $order->billing_email );
		$contact_id_only = null;

		// See if a previous contact exists
		if ( ! empty ( $contact_id ) ) {
			$contact->set_id( $contact_id );
			$contact_id_only = $contact;
		}

		// Set name
		$contact->set_name( $invoice_name );

		// Set first name
		$contact->set_first_name( $order->billing_first_name );

		// Set last name
		$contact->set_last_name( $order->billing_last_name );

		// Set email address
		$contact->set_email_address( $order->billing_email );

		// Set address
		$contact->set_addresses( array( $this->get_address_by_order( $order ) ) );

		// Set phone
		$contact->set_phones( array( new WC_XR_Phone( $order->billing_phone ) ) );

		// Return contact

		if ( ! is_null( $contact_id_only ) ) {
			// Update a contact if we pulled info from a previous thing
			$contact_request_update = new WC_XR_Request_Update_Contact( $this->settings, $contact_id, $contact );
			$contact_request_update->do_request();

			return $contact_id_only;
		}

		return $contact;
	}

}
