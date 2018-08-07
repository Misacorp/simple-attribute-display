<?php
/*
 * Plugin Name: Simple Attribute Display
 * Version: 1.0
 * Plugin URI: http://toriverkosto.fi
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Misa Jokisalo
 * Author URI: http://toriverkosto.fi
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: simple-attribute-display
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Misa Jokisalo
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-simple-attribute-display.php' );
require_once( 'includes/class-simple-attribute-display-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-simple-attribute-display-admin-api.php' );
require_once( 'includes/lib/class-simple-attribute-display-post-type.php' );
require_once( 'includes/lib/class-simple-attribute-display-taxonomy.php' );

/**
 * Returns the main instance of Simple_Attribute_Display to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Simple_Attribute_Display
 */
function Simple_Attribute_Display () {
	$instance = Simple_Attribute_Display::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Simple_Attribute_Display_Settings::instance( $instance );
  }

	return $instance;
}

if(class_exists('Simple_Attribute_Display')) {
  $sad = new Simple_Attribute_Display();


  // Get a product's top level category name by product ID.
  // Returns category name (string).
  function get_product_top_level_category ( $product_id ) {
    $product_top_category='';
    $prod_terms = get_the_terms( $product_id, 'product_cat' );
    foreach ($prod_terms as $prod_term) {
      $product_cat_id = $prod_term->term_id;
      $product_parent_categories_all_hierachy = get_ancestors( $product_cat_id, 'product_cat' );  
      $last_parent_cat = array_slice($product_parent_categories_all_hierachy, -1, 1, true);
      foreach($last_parent_cat as $last_parent_cat_value){
        $product_top_category =  $last_parent_cat_value;
      }
    }

    // Translate ID to category name
    if( $term = get_term_by( 'id', $product_top_category, 'product_cat' ) ){
      return $term->name;
    }
    return "";
  }

  // Add brand to product title.
  // Returns new product title.
  function add_brand_to_product_title($data) {
    // $data will be string(#) "current title"
    global $product;

    // Get plugin options
    $options = get_option('my_plugin_options', array() );
    print_r($options);

    $brand = "";
    if(null !== $product->get_attribute('valmistaja')) {
      // Add brand to title if it is set
      $brand = $product->get_attribute('valmistaja') .  "<br/>";
    }

    $brand = get_product_top_level_category($product->get_id()) . " / " . $brand;

    return $brand . $data;
  }

  // Adds brand names 
  function add_brand_names() {
    add_filter('the_title', 'add_brand_to_product_title');
  }

  // Hook into shop loop and add brands
  add_filter('woocommerce_shop_loop_item_title', 'add_brand_names', 10);

  // Hook into single product page and add brands
  add_filter('woocommerce_before_single_product_summary', 'add_brand_names');
}