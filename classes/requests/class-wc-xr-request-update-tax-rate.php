<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Request_Update_Tax_Rate extends WC_XR_Request {

	public function __construct( WC_XR_Settings $settings, $rate ) {
		parent::__construct( $settings );

		$this->set_method( 'POST' );
		$this->set_endpoint( 'TaxRates' );
		$this->set_body( $this->get_xml( $rate ) );
	}

	public function get_xml( $rate ) {
		$xml = '<TaxRate>';
		$xml .= '<Name>' . $rate['label'] . '</Name>';
		$xml .= '<TaxComponents>';
		$xml .= '<TaxComponent>';
		$xml .= '<Name>' . $rate['label'] . '</Name>';
		$xml .= '<Rate>' . $rate['rate'] . '</Rate>';
		$xml .= '<IsCompound>' . ( ( 'no' === $rate['compound'] ) ? 'false' : 'true' ) . '</IsCompound>';
		$xml .= '</TaxComponent>';
		$xml .= '</TaxComponents>';
		$xml .= '</TaxRate>';
		return $xml;
	}

}
