<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Line_Item_Manager {

	/**
	 * @var WC_XR_Settings
	 */
	private $settings;

	private $eu_countries_code = array('BE', 'BG', 'CZ', 'DK', 'DE', 'EE', 'IE', 'EL', 'ES', 'FR', 'HR', 'IT', 'CY', 'LV',
                                       'LT', 'LU', 'HU', 'MT', 'NL', 'AT', 'PL', 'PT', 'RO', 'SI', 'SK', 'FI', 'SE');

    private $eu_countries = array('Austria', 'Belgium', 'Bulgaria', 'Croatia', 'Cyprus', 'Czech Republic', 'Denmark',
                                  'Estonia', 'Finland', 'France', 'Germany', 'Greece', 'Hungary', 'Ireland', 'Italy',
                                  'Latvia', 'Lithuania', 'Luxembourg', 'Malta', 'Netherlands', 'Poland', 'Portugal',
                                  'Romania', 'Slovakia', 'Slovenia', 'Spain', 'Sweden');

	/**
	 * WC_XR_Line_Item_Manager constructor.
	 *
	 * @param WC_XR_Settings $settings
	 */
	public function __construct( WC_XR_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Build product line items
	 *
	 * @param WC_Order $order
	 *
	 * @return array<WC_XR_Line_Item>
	 */
	public function build_products( $order ) {
		$items = $order->get_items();
		$this->order = $order;

		// The line items
		$line_items = array();

		// Check if there are any order items
		if ( count( $items ) > 0 ) {

			// Get the sales account
			if (in_array($order->billing_country, $this->eu_countries_code) ||
			    in_array($order->billing_country, $this->eu_countries)) {
                $sales_account = $this->settings->get_option( 'sales_account_EU' );
            } elseif (($order->billing_country === 'UK') ||
                      ($order->billing_country === 'GB') ||
                      ($order->billing_country === 'United Kingdom') ||
                      ($order->billing_country === 'United Kingdom (UK)')) {
                $sales_account = $this->settings->get_option( 'sales_account_UK' );
            } else {
                $sales_account = $this->settings->get_option( 'sales_account_world' );
            }

			// Check we need to send sku's
			$send_inventory = ( ( 'on' === $this->settings->get_option( 'send_inventory' ) ) ? true : false );

			// Add order items as line items
			foreach ( $items as $item ) {

				// Get the product
				$product = $order->get_product_from_item( $item );

				// Create Line Item object
				$line_item = new WC_XR_Line_Item( $this->settings );

				// Set description
				$line_item->set_description( str_replace( array( '&#8220;', '&#8221;' ), '""', $item['name'] ) );

				// Set account code
				$line_item->set_account_code( $sales_account );

				// Send SKU?
				if ( $send_inventory ) {
					$line_item->set_item_code( $product->sku );
				}


//				if ( true === $send_inventory && $product->is_on_sale() ) {} // Set the unit price if we send inventory and the product is on sale
				// Set the Unit Amount with 4DP
				$line_item->set_unit_amount( ( floatval( $item['line_subtotal'] ) / intval( $item['qty'] ) ) );

				// Quantity
				$line_item->set_quantity( $item['qty'] );

				// Line Amount
				$line_item->set_line_amount( $item['line_subtotal'] );

				// Tax Amount
				$line_item->set_tax_amount( $item['line_tax'] );

				// Tax Rate
				$item_tax_status   = $product ? $product->get_tax_status() : 'taxable';
				if ( 'taxable' === $item_tax_status ) {
					add_filter( 'woocommerce_get_tax_location', array( $this, 'set_tax_location' ), 10, 2 );
					$rates = WC_Tax::get_rates( $product->get_tax_class() );
					remove_filter( 'woocommerce_get_tax_location', array( $this, 'set_tax_location' ) );
					reset( $rates );
					$line_item->set_tax_rate( $rates[ key( $rates ) ] );
				}

				// Add Line Item to array
				$line_items[] = $line_item;
			}
		}

		return $line_items;
	}

	/**
	 * Sets the tax location (without needing a session) so we can calculate
	 * the correct rates for our items.
	 */
	public function set_tax_location( $location, $tax_class ) {
		if ( sizeof( $location ) === 4 ) {
			return $location;
		}

		$shipping_methods = array();
		foreach ( $this->order->get_shipping_methods() as $method ) {
			$shipping_methods[] = $method['method_id'];
		}

		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		if ( true == apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( $shipping_methods, apply_filters( 'woocommerce_local_pickup_methods', array( 'local_pickup' ) ) ) ) > 0 ) {
			$tax_based_on = 'base';
		}

		if ( 'base' === $tax_based_on ) {
			$country  = WC()->countries->get_base_country();
			$state    = WC()->countries->get_base_state();
			$postcode = WC()->countries->get_base_postcode();
			$city     = WC()->countries->get_base_city();
		} elseif ( 'billing' === $tax_based_on ) {
			$country  = $this->order->billing_country;
			$state    = $this->order->billing_state;
			$postcode = $this->order->billing_postcode;
			$city     = $this->order->billing_city;
		} else {
			$country  = $this->order->shipping_country;
			$state    = $this->order->shipping_state;
			$postcode = $this->order->shipping_postcode;
			$city     = $this->order->shipping_city;
		}

		return array( $country, $state, $postcode, $city );
	}


	/**
	 * Build shipping line item
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Line_Item
	 */
	public function build_shipping( $order ) {
		if ( $order->order_shipping > 0 ) {

			// Create Line Item object
			$line_item = new WC_XR_Line_Item( $this->settings );

			// Shipping Description
			$line_item->set_description( 'Shipping: ' . $order->get_shipping_method() . '  ' .
			                             'SHIPPING TO:' . '               ' .
                                         $order->shipping_first_name . ' ' . $order->shipping_last_name .  '   ' .
                                         $order->shipping_address_1 .  '    ' .
                                         $order->shipping_address_2 .  '    ' .
                                         $order->shipping_postcode . ' ' . $order->shipping_city .  '    ' .
                                         $order->shipping_state .  '        ' .
                                         $order->shipping_country);

			// Shipping Quantity
			$line_item->set_quantity( 1 );

			// Shipping account code
			$line_item->set_account_code( $this->settings->get_option( 'shipping_account' ) );

			// Shipping cost
			$line_item->set_unit_amount( $order->order_shipping );

			// Shipping tax
			$line_item->set_tax_amount( $order->order_shipping_tax );
			$line_item->set_tax_rate( array(
				'rate'     => ( ( $order->order_shipping_tax / $order->order_shipping ) * 100 ),
				'label'    => 'Shipping',
				'shipping' => true,
				'compound' => false,
			) );

			return $line_item;
		}
	}

	/**
	 * Build discount line item
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_XR_Line_Item
	 */
	public function build_discount( $order ) {

		if ( $order->get_total_discount() > 0 ) {

			// Create Line Item object
			$line_item = new WC_XR_Line_Item( $this->settings );

			// Shipping Description
			$line_item->set_description( 'Order Discount' );

			// Shipping Quantity
			$line_item->set_quantity( 1 );

			// Shipping account code
			$line_item->set_account_code( $this->settings->get_option( 'discount_account' ) );

			// Shipping cost
			$line_item->set_unit_amount( - $order->get_total_discount() );

			return $line_item;
		}

	}

	/**
	 * Build a correction line if needed
	 *
	 * @param WC_Order $order
	 * @param WC_XR_Line_Item[] $line_items
	 *
	 * @return WC_XR_Line_Item
	 */
	public function build_correction( $order, $line_items ) {

		// Line Item
		$correction_line = null;

		// The line item total in cents
		$line_total = 0;

		// Get a sum of the amount and tax of all line items
		if ( count( $line_items ) > 0 ) {

			foreach ( $line_items as $line_item ) {

				$val = round( $line_item->get_unit_amount(), 2 ) * $line_item->get_quantity();
				$line_total += round( $val, 2 ) + round( $line_item->get_tax_amount(), 2 );
			}
		}

		// Order total in cents
		$order_total = round( $order->get_total(), 2 );

		// Check if there's a difference
		if ( $order_total !== $line_total ) {

			// Calculate difference
			$diff = $order_total - $line_total;

			// Get rounding account code
			$account_code = $this->settings->get_option( 'rounding_account' );

			// Check rounding account code
			if ( '' !== $account_code ) {

				// Create correction line item
				$correction_line = new WC_XR_Line_Item( $this->settings );

				// Correction description
				$correction_line->set_description( 'Rounding adjustment' );

				// Correction quantity
				$correction_line->set_quantity( 1 );

				// Correction amount
				$correction_line->set_unit_amount( $diff );

				$correction_line->set_account_code( $account_code );
			} else {

				// There's a rounding difference but no rounding account
				$logger = new WC_XR_Logger( $this->settings );
				$logger->write( "There's a rounding difference but no rounding account set in XERO settings." );
			}
		}

		return $correction_line;
	}

	/**
	 * Build line items
	 *
	 * @param WC_Order $order
	 *
	 * @return array<WC_XR_Line_Item>
	 */
	public function build_line_items( $order ) {

		// Fill line items array with products
		$line_items = $this->build_products( $order );

		// Add shipping line item if there's shipping
		if ( $order->order_shipping > 0 ) {
			$line_items[] = $this->build_shipping( $order );
		}

		// Add discount line item if there's discount
		if ( $order->get_total_discount() > 0 ) {
			$line_items[] = $this->build_discount( $order );
		}

		// Build correction
		$correction = $this->build_correction( $order, $line_items );
		if ( null !== $correction ) {
			$line_items[] = $correction;
		}

		// Return line items
		return $line_items;
	}

}