<?php

/**
 * The public-facing functionality of Visidea.
 *
 * @link       https://visidea.ai
 * @since      1.0.0
 *
 * @package    Visidea
 * @subpackage Visidea/public
 */

class Visidea_Public {

  /**
   * The ID of Visidea.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of Visidea.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * The database of Visidea.
   *
   * @since    2.0.0
   * @access   private
   * @var      string    $database    The database.
   */
  private $database;

  /**
   * The public_token of Visidea.
   *
   * @since    2.0.0
   * @access   private
   * @var      string    $public_token    The public_token.
   */
  private $public_token;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

  	$this->plugin_name = $plugin_name;
  	$this->version = $version;

    // Get options
    $options = get_option('visidea_plugin_options');
    $this->database = str_replace('.','_',$options['database']);
    $this->public_token = $options['public_token'];

  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_styles() {

  }

  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_scripts() {

    // Load library from the CDN
    wp_register_script('visidea_plugin', 'https://cdn.visidea.ai/js-visidea/js-visidea-3.2.0.min.js', array(), $this->version, array('in_footer' => true, 'strategy' => 'defer'));
    wp_enqueue_script('visidea_plugin');

    // Initialize visidea
    wp_add_inline_script('visidea_plugin', "
      document.addEventListener('DOMContentLoaded', function() {
        window.visidea('".esc_js($this->database)."','".esc_js($this->public_token)."');
      });
    ");

  }

  /**
   * Display the tags in the HTML.
   *
   * @since 1.0.0
   */
  public function callback_the_content($content) {
    if (isset($GLOBALS['visidea_fired']))
      return $content;
    $GLOBALS['visidea_fired'] = true;
    $user_id = get_current_user_id();
    if (is_front_page()) {
      $return = '<div class="visidea-recommendations" language="'.substr(get_locale(),0,2).'" page="home" user_id="'.esc_attr($user_id).'"></div>';
    }
    if (is_product()) {
      global $product;
      $product_id = $product->get_id();
      $return = '<div class="visidea-recommendations" language="'.substr(get_locale(),0,2).'" page="product" user_id="'.esc_attr($user_id).'" item_id="'.esc_attr($product_id).'"></div>
                 <meta class="visidea-interaction" type="item" user_id="'.esc_attr($user_id).'" item_id="'.esc_attr($product_id).'" />';
    }
    if (is_cart()) {
      $return = '<div class="visidea-recommendations" language="'.substr(get_locale(),0,2).'" page="cart" user_id="'.esc_attr($user_id).'"></div>';
    }
    if (is_order_received_page()) {
      $return = '<div class="visidea-recommendations" language="'.substr(get_locale(),0,2).'" page="order" user_id="'.esc_attr($user_id).'"></div>';
    }
    return $content.$return;
  }

  /**
   * Insert the search tag in the HTML.
   *
   * @since 2.0.0
   */
  public function insert_search_tag() {
    $user_id = get_current_user_id();
    echo '<div class="visidea-search-bar" language="'.substr(get_locale(),0,2).'" user_id="'.$user_id.'"></div>';
  }

}
