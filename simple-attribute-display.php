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


/**
 * Get a product's topmost category name
 * @param string product_id string (?)
 * @return string Topmost category name for this product
 */
function get_product_top_level_category( $product_id ) {
  try {
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
  } catch (Exception $e) {
    // Return empty string if unable to find category name.
    return "";
  }
}


/**
 * Add a prefix to product title.
 * Prefix can be one of the following, set in plugin settings:
 *   - Topmost category name
 *   - Value of a specific attribute (specify in plugin settings)
 * @param string data Current title.
 * @return string New product title
 */
function add_brand_to_product_title($data) {
  global $product;
  $prefix = "";

  // Read plugin options for where to get prefix string value.
  $data_source = get_option('sad_data_source');

  $separator = "Default separator";
  // Read plugin options for separator
  $separator_type = get_option('sad_separator_type');
  if ($separator_type === 'line_break') {
    // Use line break as separator
    $separator = '<br>';
  } else if ($separator_type === 'custom') {
    // Use custom string as separator
    $custom_string = get_option('sad_separator');
    $separator = htmlspecialchars($custom_string);
  }

  if ($data_source === 'attribute') {
    // Add attribute to title, if it is set
    $attribute = get_option('sad_attribute_name');
    $attribute = htmlspecialchars($attribute);
    if(null !== $product->get_attribute($attribute)) {
      $prefix = $product->get_attribute($attribute) .  $separator;
    }
  } else if ($data_source === 'top_category') {
    // Add top level category name to title
    $top_category_name = get_product_top_level_category($product->get_id());

    // Check if top category name exists
    if (strlen($top_category_name) > 0) {
      $prefix = $top_category_name . $separator;
    }
  }

  // If plugin settings want prefix in uppercase:
  if (get_option('sad_uppercase') === 'on') {
    $prefix = strtoupper($prefix);
  }

  return $prefix . $data;
}


// Adds brand names 
function add_brand_names() {
  add_filter('the_title', 'add_brand_to_product_title');
}

/**
 * Check if Woocommerce exists
 */
function check_for_woocommerce() {
    if (!defined('WC_VERSION')) {
        // no woocommerce :(
    } else {
        run_plugin();
    }
}


/**
 * Run the plugin code
 */
function run_plugin() {
  Simple_Attribute_Display();

  // Hook into shop loop and add brands
  add_filter('woocommerce_shop_loop_item_title', 'add_brand_names', 10);

  // Hook into single product page and add brands
  add_filter('woocommerce_before_single_product_summary', 'add_brand_names');
}
  

/* Plugin logic */


if(class_exists('Simple_Attribute_Display')) {
  add_action('plugins_loaded', 'check_for_woocommerce');
}