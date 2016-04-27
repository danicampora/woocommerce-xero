<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_XR_Settings {

	const OPTION_PREFIX = 'wc_xero_';

	// Settings defaults
	private $settings = array();
	private $override = array();

	public function __construct( $override = null ) {

		if ( $override !== null ) {
			$this->override = $override;
		}

		// Set the settings
		$this->settings = array(

			// API keys
			'consumer_key'       => array(
				'title'       => __( 'Consumer Key', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'OAuth Credential retrieved from <a href="http://api.xero.com" target="_blank">Xero Developer Centre</a>.', 'wc-xero' ),
			),
			'consumer_secret'    => array(
				'title'       => __( 'Consumer Secret', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'OAuth Credential retrieved from <a href="http://api.xero.com" target="_blank">Xero Developer Centre</a>.', 'wc-xero' ),
			),
			// SSH key files
			'private_key'        => array(
				'title'       => __( 'Private Key', 'wc-xero' ),
				'default'     => '',
				'type'        => 'file',
				'description' => __(  'Path to the private key file created to authenticate this site with Xero.', 'wc-xero' ),
			),
			'public_key'         => array(
				'title'       => __( 'Public Key', 'wc-xero' ),
				'default'     => '',
				'type'        => 'file',
				'description' => __(  'Path to the public key file created to authenticate this site with Xero.', 'wc-xero' ),
			),
			// Invoice Prefix
			'invoice_prefix'     => array(
				'title'       => __( 'Invoice Prefix', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Allow you to prefix all your invoices.', 'wc-xero' ),
			),
			// Accounts
			'sales_account_UK'    => array(
				'title'       => __( 'Sales Account UK', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track sales in the UK.', 'wc-xero' ),
			),
    	   	'sales_account_EU'    => array(
				'title'       => __( 'Sales Account EU', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track sales in Europe.', 'wc-xero' ),
			),
            		'sales_account_world' => array(
				'title'       => __( 'Sales Account rest of the world', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track sales in the rest of the world.', 'wc-xero' ),
			),
			'discount_account'   => array(
				'title'       => __( 'Discount Account', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track customer discounts.', 'wc-xero' ),
			),
			'shipping_account'   => array(
				'title'       => __( 'Shipping Account', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track shipping charges.', 'wc-xero' ),
			),
			'payment_account'    => array(
				'title'       => __( 'Payment Account', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to track payments received.', 'wc-xero' ),
			),
			'rounding_account'   => array(
				'title'       => __( 'Rounding Account', 'wc-xero' ),
				'default'     => '',
				'type'        => 'text',
				'description' => __(  'Code for Xero account to allow an adjustment entry for rounding', 'wc-xero' ),
			),
			// Misc settings
			'send_invoices'      => array(
				'title'       => __( 'Send Invoices', 'wc-xero' ),
				'default'     => 'manual',
				'type'        => 'select',
				'description' => __(  'Send Invoices manually (from the order\'s action menu), on creation (when the order is created), or on completion (when order status is changed to completed).', 'wc-xero' ),
				'options'     => array(
					'manual'   => __( 'Manually', 'wc-xero' ),
					'creation' => __( 'On Order Creation', 'wc-xero' ),
					'on'       => __( 'On Order Completion', 'wc-xero' ),
				),
			),
			'send_payments'      => array(
				'title'       => __( 'Send Payments', 'wc-xero' ),
				'default'     => 'off',
				'type'        => 'select',
				'description' => __(  'Send Payments manually or automatically when order is completed. This may need to be turned off if you sync via a separate integration such as PayPal.', 'wc-xero' ),
				'options'     => array(
					'off' => __( 'Manually', 'wc-xero' ),
					'on'  => __( 'On Order Completion', 'wc-xero' ),
				),
			),
			'four_decimals'     => array(
				'title'       => __( 'Four Decimal Places', 'wc-xero' ),
				'default'     => 'off',
				'type'        => 'checkbox',
				'description' => __(  'Use four decimal places for unit prices instead of two.', 'wc-xero' ),
			),
			'export_zero_amount' => array(
				'title'       => __( 'Orders with zero total', 'wc-xero' ),
				'default'     => 'off',
				'type'        => 'checkbox',
				'description' => __(  'Export orders with zero total.', 'wc-xero' ),
			),
			'send_inventory'     => array(
				'title'       => __( 'Send Inventory Items', 'wc-xero' ),
				'default'     => 'off',
				'type'        => 'checkbox',
				'description' => __(  'Send Item Code field with invoices. If this is enabled then each product must have a SKU defined and be setup as an <a href="https://help.xero.com/us/#Settings_PriceList" target="_blank">inventory item</a> in Xero.', 'wc-xero' ),
			),
			'debug'              => array(
				'title'       => __( 'Debug', 'wc-xero' ),
				'default'     => 'off',
				'type'        => 'checkbox',
				'description' => __(  'Enable logging.  Log file is located at: /wc-logs/', 'wc-xero' ),
			),
		);
	}

	/**
	 * Setup the required settings hooks
	 */
	public function setup_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}

	/**
	 * Get an option
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get_option( $key ) {

		if ( isset( $this->override[ $key ] ) ) {
			return $this->override[ $key ];
		}

		return get_option( self::OPTION_PREFIX . $key, $this->settings[ $key ]['default'] );
	}

	/**
	 * settings_init()
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {

		// Add section
		add_settings_section( 'wc_xero_settings', __( 'Xero Settings', 'wc-xero' ), array(
			$this,
			'settings_intro'
		), 'woocommerce_xero' );

		// Add setting fields
		foreach ( $this->settings as $key => $option ) {

			// Add setting fields
			add_settings_field( self::OPTION_PREFIX . $key, $option['title'], array(
				$this,
				'input_' . $option['type']
			), 'woocommerce_xero', 'wc_xero_settings', array( 'key' => $key, 'option' => $option ) );

			// Register setting
			register_setting( 'woocommerce_xero', self::OPTION_PREFIX . $key );

		}

	}

	/**
	 * Add menu item
	 *
	 * @return void
	 */
	public function add_menu_item() {
		$sub_menu_page = add_submenu_page( 'woocommerce', __( 'Xero', 'wc-xero' ), __( 'Xero', 'wc-xero' ), 'manage_woocommerce', 'woocommerce_xero', array(
			$this,
			'options_page'
		) );

		add_action( 'load-' . $sub_menu_page, array( $this, 'enqueue_style' ) );
	}

	public function enqueue_style() {
		global $woocommerce;
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
	}

	/**
	 * The options page
	 */
	public function options_page() {
		?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="options.php">
				<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br/></div>
				<h2><?php _e( 'Xero for WooCommerce', 'wc-xero' ); ?></h2>

				<?php
				if ( isset( $_GET['settings-updated'] ) && ( $_GET['settings-updated'] == 'true' ) ) {
					echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.', 'wc-xero' ) . '</strong></p></div>';

				} else if ( isset( $_GET['settings-updated'] ) && ( $_GET['settings-updated'] == 'false' ) ) {
					echo '<div id="message" class="error fade"><p><strong>' . __( 'There was an error saving your settings.', 'wc-xero' ) . '</strong></p></div>';
				}
				?>

				<?php settings_fields( 'woocommerce_xero' ); ?>
				<?php do_settings_sections( 'woocommerce_xero' ); ?>
				<p class="submit"><input type="submit" class="button-primary" value="Save"/></p>
			</form>
		</div>
	<?php
	}

	/**
	 * Settings intro
	 */
	public function settings_intro() {
		echo '<p>' . __( 'Settings for your Xero account including security keys and default account numbers.<br/> <strong>All</strong> text fields are required for the integration to work properly.', 'wc-xero' ) . '</p>';
	}

	/**
	 * File setting field
	 *
	 * @param $args
	 */
	public function input_file( $args ) {

		// Default text field
		$this->input_text( $args );

		if ( is_file( $this->get_option( $args['key'] ) ) ) {
			echo '<p style="margin-top:15px;"><span style="padding: .5em; background-color: #4AB915; color: #fff; font-weight: bold;">' . __( 'Key file found.', 'wc-xero' ) . '</span></p>';
		} else {
			echo '<p style="margin-top:15px;"><span style="padding: .5em; background-color: #bc0b0b; color: #fff; font-weight: bold;">' . __( 'Key file not found.', 'wc-xero' ) . '</span></p>';
			$working_dir = str_replace( 'wp-admin', '', getcwd() );
			echo '<p>' . __( '  This setting should include the absolute path to the file which might include working directory: ', 'wc-xero' ) . '<span class="code" style="background: #efefef;">' . $working_dir . '</span></p>';
		}
	}

	/**
	 * Text setting field
	 *
	 * @param array $args
	 */
	public function input_text( $args ) {
		echo '<input type="text" name="' . self::OPTION_PREFIX . $args['key'] . '" id="' . self::OPTION_PREFIX . $args['key'] . '" value="' . $this->get_option( $args['key'] ) . '" />';
		echo '<p class="description">' . $args['option']['description'] . '</p>';
	}

	/**
	 * Checkbox setting field
	 *
	 * @param array $args
	 */
	public function input_checkbox( $args ) {
		echo '<input type="checkbox" name="' . self::OPTION_PREFIX . $args['key'] . '" id="' . self::OPTION_PREFIX . $args['key'] . '" ' . checked( 'on', $this->get_option( $args['key'] ), false ) . ' /> ';
		echo '<p class="description">' . $args['option']['description'] . '</p>';
	}

	/**
	 * Drop down setting field
	 *
	 * @param array $args
	 */
	public function input_select( $args ) {
		$option = $this->get_option( $args['key'] );

		$name = esc_attr( self::OPTION_PREFIX . $args['key'] );
		$id = esc_attr( self::OPTION_PREFIX . $args['key'] );
		echo "<select name='$name' id='$id'>";

		foreach( $args['option']['options'] as $key => $value ) {
			$selected = selected( $option, $key, false );
			$text = esc_html( $value );
			$val = esc_attr( $key );
			echo "<option value='$val' $selected>$text</option>";
		}

		echo '</select>';
		echo '<p class="description">' . esc_html( $args['option']['description'] ) . '</p>';
	}

}
