<?php
/**
* Plugin Name: WooCommerce Auto Add to Cart
* Description: Add a specified product automatically to the cart when any product in a particular category is purchased
* Version: 1.0
* Author: Abundant Designs
* Author URI: https://abundantdesigns.com/
* License: GPL2
* Text Domain: woocommerce-auto-add-to-cart
*/
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check if WooCommerce is active (https://docs.woothemes.com/document/create-a-plugin/)
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	class WooCommerce_Auto_Add {

		public function __construct() {
			// Add product to cart (Use priority 20 to avoid being overridden by Subscriptions plugin)
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'handle_add_to_cart' ), 20, 3 );

			// Admin functions
			add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'plugin_add_settings_link' ) );  
			add_filter( 'woocommerce_get_sections_products', array( $this, 'woocommerce_auto_add_section' ) );
			add_filter( 'woocommerce_get_settings_products', array( $this, 'woocommerce_auto_add_settings' ), 10, 2 );
		}

		/**
		 * Add to Cart action
		 * https://docs.woocommerce.com/document/automatically-add-product-to-cart-on-visit/
		 */
		public function handle_add_to_cart( $passed, $product_id, $quantity ) {
			$trigger_category_id = get_option( 'woocommerce_auto_add_category_id' );
			$auto_add_product_id = get_option( 'woocommerce_auto_add_product_id' );

			if ( has_term( $trigger_category_id, 'product_cat', $product_id ) ) {
				WC()->cart->add_to_cart( $auto_add_product_id );

				// Check if product to auto add is already in the cart
				$found = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product->get_id() == $auto_add_product_id )
						$found = true;
				}

				// If the product is not already in the cart, add it
				if ( !$found )
					WC()->cart->add_to_cart( $auto_add_product_id );
			}

			return $passed;
		}


		/** 
		 * Plugin Settings Link
		 * https://hugh.blog/2012/07/27/wordpress-add-plugin-settings-link-to-plugins-page/
		 */          
		public function plugin_add_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=wc-settings&tab=products&section=auto_add">' . __( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}


		/** 
		 * Admin WooCommerce settings
		 * https://docs.woocommerce.com/document/adding-a-section-to-a-settings-tab/
		 */
		public function woocommerce_auto_add_section( $sections ) {
			$sections['auto_add'] = __( 'Auto Add to Cart', 'woocommerce-auto-add-to-cart' );
			return $sections;
		}
		public function woocommerce_auto_add_settings( $settings, $current_section ) {
			if ( $current_section == 'auto_add' ) {
				$settings_auto_add = array();

				// Add Title to the Settings
				$settings_auto_add[] = array( 'name' => __( 'Auto Add to Cart', 'woocommerce-auto-add-to-cart' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure WC Slider', 'woocommerce-auto-add-to-cart' ), 'id' => 'wcslider' );

				// Add first dropdown option -- WooCommerce categories
				$settings_auto_add[] = array(
					'name'     => __( 'Auto add product', 'woocommerce-auto-add-to-cart' ),
					'id'       => 'woocommerce_auto_add_product_id',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select-nostd',
					'options'  => self::woocommerce_auto_add_get_products(),
					'desc_tip'     => __( 'This product will be automatically added to the shopping cart when a product from the trigger category is added to the cart', 'woocommerce-auto-add-to-cart' ),
				);

				// Add second dropdown option -- WooCommerce categories
				$settings_auto_add[] = array(
					'name'     => __( 'Trigger category', 'woocommerce-auto-add-to-cart' ),
					'id'       => 'woocommerce_auto_add_category_id',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select-nostd',
					'options'  => self::woocommerce_auto_add_get_categories(),
					'desc_tip' => __( 'When a product from this category is added to the shopping cart, the auto add product will also be added to the cart.', 'woocommerce-auto-add-to-cart' ),
				);

				$settings_auto_add[] = array( 'type' => 'sectionend', 'id' => 'wcslider' );
				return $settings_auto_add;

			// If not, return the standard settings
			} else {
				return $settings;
			}
		}


		/*
		* Admin settings helper functions
		*/
		private function woocommerce_auto_add_get_products() {
			$products = array();

			$args = array(
				'status' => 'publish'
			);
			$all_products = wc_get_products( $args );
			foreach ( $all_products as $product ) {
				$products[ $product->get_id() ] = $product->get_name();
			}

			return $products;
		}

		private function woocommerce_auto_add_get_categories() {
			$categories = array();

			$args = array(
				'taxonomy'     => 'product_cat',
				'orderby'      => 'name',
				'show_count'   => 0,
				'pad_counts'   => 0,
				'hierarchical' => 1,
				'title_li'     => '',
				'hide_empty'   => 1
			);
			$all_categories = get_categories( $args );
			foreach ($all_categories as $cat) {
				$categories[ $cat->term_id ] = $cat->name;
			}

			return $categories;
		}
	}
	new WooCommerce_Auto_Add;
}
