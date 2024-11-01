<?php

/**
 * The file that defines the core Visidea class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://visidea.ai
 * @since      1.0.0
 *
 * @package    Visidea
 * @subpackage Visidea/includes
 */

class Visidea {

  /**
   * The loader that's responsible for maintaining and registering all hooks
   * that power Visidea.
   *
   * @since    1.0.0
   * @access   protected
   * @var      Visidea_Loader    $loader
   *           Maintains and registers all hooks for the plugin.
   */
  protected $loader;

  /**
   * The unique identifier of Visidea.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $plugin_name
   *           The string used to uniquely identify this plugin.
   */
  protected $plugin_name;

  /**
   * The current version of Visidea.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $version
   *           The current version of Visidea.
   */
  protected $version;

  /*
   * Define the core functionality of Visidea.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the admin area and
   * the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function __construct() {
    if ( defined( 'VISIDEA_VERSION' ) ) {
    	$this->version = VISIDEA_VERSION;
    } else {
    	$this->version = '1.0.0';
    }

    $this->plugin_name = 'visidea';
    $this->load_dependencies();
    $this->set_locale();
    $this->define_public_hooks();
    $this->define_admin_hooks();
  }

  /**
   * Load the required dependencies for Visidea
   *
   * Include the following files that make up the plugin:
   *
   * - Visidea_Loader. Orchestrates the hooks of the plugin.
   * - Visidea_Admin. Defines all hooks for the admin area.
   * - Visidea_Public. Defines all hooks for the public side of the site.
   *
   * Create an instance of the loader which will be used to register the hooks
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies() {

    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-visidea-loader.php';

    /**
     * The class responsible for defining all translatopm.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-visidea-i18n.php';

    /**
     * The class responsible for defining all actions that occur in the admin area.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-visidea-admin.php';

    /**
     * The class responsible for defining all actions that occur in the public-facing
     * side of the site.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-visidea-public.php';

    $this->loader = new Visidea_Loader();
  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Visidea_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function set_locale() {

      $plugin_i18n = new Visidea_i18n();

      $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of Visidea.
   *
   * In particular, add hooks for product_viewed, product_added_to_cart,
   * product_ordered and product_favourite (@DEPR) events. It also calls dump actions
   * in response to consumer registration and product update events.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_public_hooks() {

    $plugin_public = new Visidea_Public( $this->get_plugin_name(), $this->get_version() );

    // $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    $this->loader->add_filter('the_content', $plugin_public, 'callback_the_content', 20);

    $this->loader->add_action('wp_body_open', $plugin_public, 'insert_search_tag', 20, 1);

  }


  /**
   * Register all of the hooks related to the admin-facing functionality
   * of Visidea.
   *
   * In particular, add hooks for dump cronjobs.
   *
   * @since 1.0.0
   * @access private
   */
  private function define_admin_hooks() {

    // Add filter to create new cronjob time interval
    $plugin_admin = new Visidea_Admin($this->get_plugin_name(), $this->get_version());
    // $this->loader->add_action('woocommerce_update_product', $plugin_admin, 'callback_new_item', 10);
    // $this->loader->add_action('user_register', $plugin_admin, 'callback_new_user');
    // $this->loader->add_action('woocommerce_new_order', $plugin_admin, 'callback_new_interaction');
    $this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page');
    $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

    $this->loader->add_action('init', $plugin_admin, 'check_crons');

   }

  /**
   * Run the loader to execute all of the hooks with WordPress.
   *
   * @since    1.0.0
   */
  public function run() {
    $this->loader->run();
  }


  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @since     1.0.0
   * @return    string    The name of the plugin.
   */
  public function get_plugin_name() {
    return $this->plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @since     1.0.0
   * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
   */
  public function get_loader() {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @since     1.0.0
   * @return    string    The version number of the plugin.
   */
  public function get_version() {
    return $this->version;
  }

  /**
   * Sanitize a String.
   *
   * Enclose a String in '"' and escape all '"' inside.
   *
   * @since 1.0.0
   * @param string      string to sanitize
   * @return string     sanitized and escaped string.
   *
   */
  public static function sanitizeString($string) {
    $sanitized = preg_replace('#<[^>]+>#', ' ', $string);
    if (strpos($string, 'fildoux.com') !== false) {
      $u = parse_url($string);
      $sanitized = str_replace($u['host'], 'www.fildoux.com', $sanitized);      
    }
    $sanitized = str_replace('"', '\"', $sanitized);
    return '"'.$sanitized.'"';
  }

}
