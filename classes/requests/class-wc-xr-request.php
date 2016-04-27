<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

abstract class WC_XR_Request {

	/**
	 * @var String API URL
	 */
	const API_URL = 'https://api.xero.com/api.xro/2.0/';

	/**
	 * @var String Signature Method
	 */
	const signature_method = 'RSA-SHA1';

	/**
	 * @var String
	 */
	private $signed_url = null;

	/**
	 * The request endpoint
	 *
	 * @var String
	 */
	private $endpoint = '';

	/**
	 * The request method
	 *
	 * @var string
	 */
	private $method = 'PUT';

	/**
	 * The request body
	 *
	 * @var string
	 */
	private $body = '';

	/**
	 * The query string
	 *
	 * @var string
	 */
	private $query = array();

	/**
	 * The request response
	 * @var array
	 */
	private $response = null;

	/**
	 * @var WC_XR_Settings
	 */
	private $settings;

	/**
	 * WC_XR_Request constructor.
	 *
	 * @param WC_XR_Settings $settings
	 */
	public function __construct( WC_XR_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Method to set endpoint
	 *
	 * @param $endpoint
	 */
	protected function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Get the endpoint
	 *
	 * @return String
	 */
	protected function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * @return string
	 */
	protected function get_method() {
		return $this->method;
	}

	/**
	 * @param string $method
	 */
	protected function set_method( $method ) {
		$this->method = $method;
	}

	/**
	 * @return string
	 */
	protected function get_body() {
		return $this->body;
	}

	/**
	 * @param string $body
	 */
	protected function set_body( $body ) {
		$this->body = $body;
	}

	/**
	 * @return string
	 */
	protected function get_query() {
		return $this->query;
	}


	/**
	 * @param string $query
	 */
	protected function set_query( $query ) {
		$this->query = $query;
	}

	/**
	 * Get response
	 *
	 * @return array
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Return the response body in XML object
	 *
	 * @return SimpleXMLElement|null
	 */
	public function get_response_body_xml() {
		if ( ! is_null( $this->response ) ) {
			return new SimpleXMLElement( $this->response['body'] );
		}

		return null;
	}

	/**
	 * Return the response body
	 *
	 * @return string
	 */
	public function get_response_body() {
		if ( ! is_null( $this->response ) ) {
			return $this->response['body'];
		}

		return '';
	}

	/**
	 * Clear the response
	 *
	 * @return bool
	 */
	private function clear_response() {
		$this->response = null;

		return true;
	}

	/**
	 * Check local settings required for request
	 *
	 * @return bool
	 */
	private function are_settings_set() {

		// Check required settings
		if ( ( '' === $this->settings->get_option( 'consumer_key' ) ) ||
		     ( '' === $this->settings->get_option( 'consumer_secret' ) ) ||
		     ( '' === $this->settings->get_option( 'private_key' ) ) ||
		     ( '' === $this->settings->get_option( 'public_key' ) ) ||
		     ( '' === $this->settings->get_option( 'sales_account_UK' ) ) ||
		     ( '' === $this->settings->get_option( 'sales_account_EU' ) ) ||
		     ( '' === $this->settings->get_option( 'sales_account_world' ) ) ||
		     ( '' === $this->settings->get_option( 'discount_account' ) ) ||
		     ( '' === $this->settings->get_option( 'shipping_account' ) ) ||
		     ( '' === $this->settings->get_option( 'payment_account' ) )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if key files exist
	 *
	 * @return bool
	 */
	private function do_keys_exist() {

		// Check keys
		if ( file_exists( $this->settings->get_option( 'private_key' ) ) && file_exists( $this->settings->get_option( 'public_key' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the signed URL.
	 * The signed URL is fetched by doing an OAuth request.
	 *
	 * @throws Exception
	 *
	 * @return String
	 */
	private function get_signed_url() {
		if ( null === $this->signed_url ) {

			// Setup OAuth object
			$oauthObject = new WC_XR_OAuth_Simple();

			// Reset, start clean
			$oauthObject->reset();

			$parameters = array( 'oauth_signature_method' => 'RSA-SHA1' );
			$query      = $this->get_query();
			if ( ! empty( $query ) ) {
				$parameters = array_merge( $query, $parameters );
			}

			// Do the OAuth sign request
			$oauth_result = $oauthObject->sign( array(
				'path'       => self::API_URL . $this->get_endpoint() . '/',
				'action'     => $this->get_method(),
				'parameters' => $parameters,
				'signatures' => array(
					'consumer_key'    => $this->settings->get_option( 'consumer_key' ),
					'shared_secret'   => $this->settings->get_option( 'consumer_secret' ),
					'rsa_private_key' => $this->settings->get_option( 'private_key' ),
					'rsa_public_key'  => $this->settings->get_option( 'public_key' ),
					'oauth_secret'    => $this->settings->get_option( 'consumer_secret' ),
					'oauth_token'     => $this->settings->get_option( 'consumer_key' )
				)
			) );

			// Set the signed URL
			$this->signed_url = $oauth_result['signed_url'];
		}

		return $this->signed_url;
	}

	/**
	 * Do the request
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function do_request() {

		// Check if required settings are set
		if ( false === $this->are_settings_set() ) {
			throw new Exception( "Can't do XERO API request because not all required settings are entered." );
		}

		// Check if key files exist
		if ( false === $this->do_keys_exist() ) {
			throw new Exception( 'Xero key files are set but file does not exist.' );
		}

		// Do the request
		$this->response = wp_remote_request( $this->get_signed_url(), array(
				'method'     => $this->get_method(),
				'headers'    => array(
					'Accept'         => 'application/xml',
					'Content-Type'   => 'application/binary',
					'Content-Length' => strlen( $this->get_body() ),
				),
				'timeout'    => 70,
				'body'       => $this->get_body(),
				'user-agent' => 'WooCommerce ' . WC()->version
			)
		);

		// Check if request is an error
		if ( is_wp_error( $this->response ) ) {
			$this->clear_response();
			throw new Exception( 'There was a problem connecting to the API.' );
		}

		// Check for OAuth error
		if ( isset( $this->response['body'] ) && 0 === strpos( $this->response['body'], 'oauth_problem=' ) ) {

			// Parse error string
			parse_str( $this->response['body'], $oauth_error );

			// Find OAuth advisse
			$oauth_advise = ( ( isset( $oauth_error['oauth_problem_advice'] ) ) ? $oauth_error['oauth_problem_advice'] : '' );

			// Throw new exception
			throw new Exception( sprintf( 'Request failed due OAuth error: %s | %s', $oauth_error['oauth_problem'], $oauth_advise ) );
		}

		return true;

	}

}
