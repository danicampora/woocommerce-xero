<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Request_Tax_Rate extends WC_XR_Request {

	public function __construct( WC_XR_Settings $settings, $rate, $name ) {
		parent::__construct( $settings );

		$this->set_method( 'GET' );
		$this->set_endpoint( 'TaxRates' );
		$this->set_query( array(
			'where' => 'EffectiveRate==' . $rate . '&&Name==' . $name . '&&TaxType.StartsWith("TAX")'
		) );
	}

}
