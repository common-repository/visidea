<?php

/**
 * The admin-specific functionality of Visidea.
 *
 * @link       https://visidea.ai
 * @since      1.0.0
 *
 * @package    Visidea
 * @subpackage Visidea/admin
 */

ini_set('max_execution_time', 3600);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE  & ~E_DEPRECATED);

class Visidea_Admin {

  /**
   * The ID of Visidea.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct($plugin_name, $version) {
  	$this->plugin_name = $plugin_name;
  	$this->version = $version;
  }

  private function init_multilanguage() {
    if ( class_exists( 'TRP_Translate_Press' ) ) {
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_settings = $trp->get_component( 'settings' );

        $default_language = $trp_settings->get_settings()['default-language'];
        $language_codes_array = $trp_settings->get_settings()['publish-languages'];

        return ['enabled'=>true, 'type'=>'translatepress', 'default'=>$default_language, 'languages'=>$language_codes_array];
    }
    if ( class_exists( 'SitePress' ) ) {
      $default_language = apply_filters('wpml_default_language', NULL);
      $languages = apply_filters('wpml_active_languages', NULL, 'skip_missing=0');

      $language_codes_array = array_map(function($lang) {
          return $lang['language_code'];
      }, $languages);

      return ['enabled' => true, 'type' => 'wpml', 'default' => $default_language, 'languages' => $language_codes_array];
    }
    return ['enabled'=>false];
  }

  private function fetch_translation($string, $lang, $orig_lang) {
    global $wpdb;

    // Check if TRP_Translate_Press plugin is active
    if ( class_exists( 'TRP_Translate_Press' ) ) {
      $translation = $wpdb->get_var($wpdb->prepare("
          SELECT translated
          FROM {$wpdb->prefix}trp_dictionary_{$orig_lang}_{$lang}
          WHERE original = %s
      ", $string));
      
      if ($translation) {
        return $translation;
      }
    }

    // Check if SitePress (WPML) plugin is active
    if ( class_exists( 'SitePress' ) ) {
      $translation = apply_filters('wpml_translate_single_string', $string, 'Your text domain', $string, $lang);
      
      if ($translation && $translation !== $string) {
        return $translation;
      }
    }

    // Return original string if no translation is found
    return $string;
  }

  private function fetch_url_translation($url, $lang, $orig_lang) {
    // This function fetches the translated URL slug
    global $wpdb;

    // Extract the post ID from the URL
    $post_id = url_to_postid($url);

    $product_string = explode('/', str_replace(get_home_url() . '/', '', $url))[0];

    // Get the translated slug for the post
    $translated_slug = $wpdb->get_var($wpdb->prepare("
      SELECT translated
      FROM {$wpdb->prefix}trp_gettext_$lang
      WHERE original = %s
      ", basename(get_permalink($post_id))));

    if ($translated_slug) {
      // Construct the translated URL
      $translated_url = get_home_url() . '/' . explode('_',$lang)[0] . '/' . $this->fetch_translation($product_string, $lang, $orig_lang) . '/' . $translated_slug . '/';
      return $translated_url;
    }

    return get_home_url() . '/' . explode('_',$lang)[0] . '/' . $this->fetch_translation($product_string, $lang, $orig_lang) . '/' . basename(get_permalink($post_id)) . '/'; // Return original URL if no translation is found
  }

  public function cron_items_dump() {
    // Init multilanguage
    $multilanguage = $this->init_multilanguage();

    // Re-initialize items dump file
    $options = get_option('visidea_plugin_options');
    $option_filename = 'items_' . $options['private_token'] . '.tmp';
    $file = plugin_dir_path(__FILE__) . $option_filename;
    $fp = fopen($file, 'w');

    // Write header
    $items_columns = "item_id;code;mpn;ean;name;brand_id;brand_name;price;market_price;discount;page_ids;page_names;url;images;stock;gender;description";

    if ($multilanguage['enabled']) {
      foreach ($multilanguage['languages'] as $lang) {
        if ($multilanguage['default'] != $lang) {
          $lang_code = substr($lang, 0, 2);
          $items_columns .= ";name_{$lang_code};description_{$lang_code};page_names_{$lang_code};url_{$lang_code}";
        }
      }
    }

    $items_columns .= "\n";
    fwrite($fp, $items_columns);

    // Set the pagination chunk size
    $chunkSize = 1000;
    // Set the initial page number
    $page = 1;

    // Start the loop
    while (true) {
      // Retrieve products using wc_get_products() with pagination
      $products = wc_get_products(array(
        'status' => 'publish',
        'type' => ['simple', 'variable', 'external'],
        'limit' => $chunkSize,
        'page' => $page,
        'return' => 'ids', // Fetch only IDs to reduce memory usage
      ));

      // Check if there are no more products
      if (empty($products)) {
        break;
      }

      // Process the retrieved product IDs
      foreach ($products as $product_id) {
        $item = wc_get_product($product_id);

        // Initialize buffer for each product
        $buffer = $this->prepare_product_buffer($item, $multilanguage);

        // Write product data to the file
        fwrite($fp, $buffer);
        
        // Clear the item object to free memory
        unset($item);
      }

      // Increment the page number for the next iteration
      $page++;
    }

    // Finalize the file handling
    $old_filename = 'items_' . $options['private_token'] . '.csv';
    $hash_filename = 'items_' . $options['private_token'] . '.hash';
    unlink(plugin_dir_path(__FILE__) . $old_filename);
    rename(plugin_dir_path(__FILE__) . $option_filename, plugin_dir_path(__FILE__) . $old_filename);
    file_put_contents(plugin_dir_path(__FILE__) . $hash_filename, hash_file('md5', plugin_dir_path(__FILE__) . $old_filename));

    // Close the file pointer
    fclose($fp);
  }

  private function prepare_product_buffer($item, $multilanguage) {
    // Fetch and prepare product details
    $mpn = implode('|', get_post_meta($item->get_id(), 'woo_feed_mpn'));
    $ean = implode('|', get_post_meta($item->get_id(), 'woo_feed_ean'));

    $brand_names = '';
    $brand_ids = '';
    $brands = wp_get_post_terms($item->get_id(), 'pwb-brand');
    if (!is_wp_error($brands) && !empty($brands)) {
      foreach ($brands as $brand) {
        $brand_names .= $brand->name.'|';
        $brand_ids .= $brand->term_id.'|';
      }
      $brand_names = substr($brand_names, 0, -1);
      $brand_ids = substr($brand_ids, 0, -1);
    }

    $buffer = $item->get_id() . ';' .
              $item->get_sku() . ';' .
              $mpn . ';' .
              $ean . ';' .
              Visidea::sanitizeString($item->get_name()) . ';' .
              '"'.$brand_ids.'";' .
              Visidea::sanitizeString($brand_names).';';

    if ($item->is_type('variable')) {
      $discount = 0;
      $reg = $item->get_variation_regular_price('min');
      if ($reg > 0) {
        $discount = (($reg - $item->get_variation_price('min')) / $reg) * 100;
      }

      if (is_nan($discount) || $discount == 100) {
        $discount = 0;
      }

      $buffer .= wc_get_price_including_tax($item, array('price' => $item->get_variation_regular_price('min'))) . ';' .
        wc_get_price_including_tax($item, array('price' => $item->get_variation_price('min'))) . ';' .
        $discount . ';';
    } else {
      $discount = (($item->get_regular_price() - $item->get_price()) / $item->get_regular_price()) * 100;

      if (is_nan($discount) || $discount == 100) {
        $discount = 0;
      }

      $buffer .=  wc_get_price_including_tax($item, array('price' => $item->get_regular_price())) . ';' .
        wc_get_price_including_tax($item) . ';' .
        $discount . ';';
    }

    // Create Category ids + names fields
    $category_ids = "";
    $category_names = "";
    foreach ($item->get_category_ids() as $category_id) {
      $category_ids .= $category_id . '|';
      $category_name = get_term_by('id', $category_id, 'product_cat')->name;
      $category_names .= $category_name . '|';
    }
    if (strlen($category_ids) > 0) {
      $category_ids = substr($category_ids, 0, -1);
    }
    if (strlen($category_names) > 0) {
      $category_names = substr($category_names, 0, -1);
    }

    $stock = $item->get_stock_status() == 'instock' ? 1 : 0;

    // Prepare the images string
    $images = wp_get_attachment_image_src(get_post_thumbnail_id($item->get_id()), 'full')[0] . '|';
    $imagesarr = $item->get_gallery_image_ids();
    foreach ($imagesarr as $image) {
      $images .= wp_get_attachment_url($image) . '|';
    }
    if (strlen($images) > 0) {
      $images = substr($images, 0, -1);
    }

    $description = $item->get_description();

    $buffer .= Visidea::sanitizeString($category_ids) . ';' .
               Visidea::sanitizeString($category_names) . ';' .
               Visidea::sanitizeString(get_permalink($item->get_id())) . ';' .
               Visidea::sanitizeString($images) . ';' .
               $stock . ';;' .
               Visidea::sanitizeString($description);

    if ($multilanguage['enabled']) {
      foreach ($multilanguage['languages'] as $lang) {
        if ($multilanguage['default'] != $lang) {
          if ($multilanguage['type'] == 'translatepress') {
            $name_translated = $this->fetch_translation($item->get_name(), strtolower($lang), strtolower($multilanguage['default']));
            $description_translated = $this->fetch_translation($item->get_description(), strtolower($lang), strtolower($multilanguage['default']));
            $page_names_translated = $this->fetch_translation($category_names, strtolower($lang), strtolower($multilanguage['default']));
            $url_translated = $this->fetch_url_translation(get_permalink($item->get_id()), strtolower($lang), strtolower($multilanguage['default']));
          } elseif ($multilanguage['type'] == 'wpml') {
            $name_translated = apply_filters('wpml_translate_single_string', $item->get_name(), 'woocommerce', $item->get_id(), $lang);
            $description_translated = apply_filters('wpml_translate_single_string', $item->get_description(), 'woocommerce', $item->get_id(), $lang);
            $page_names_translated = apply_filters('wpml_translate_single_string', $category_names, 'woocommerce', $item->get_id(), $lang);
            $url_translated = apply_filters('wpml_permalink', get_permalink($item->get_id()), $lang);
          }
          $buffer .= ';' .
            Visidea::sanitizeString($name_translated) . ';' .
            Visidea::sanitizeString($description_translated) . ';' .
            Visidea::sanitizeString($page_names_translated) . ';' .
            Visidea::sanitizeString($url_translated);
        }
      }
    }

    $buffer .= "\n";
    return $buffer;
  }

  public function cron_users_dump() {
    // error_log( 'fired cron_users_dump at ' . date( DATE_RFC2822 ) );

    // Re-initialize items dump file
    $options = get_option('visidea_plugin_options');
    $option_filename = 'users_'.$options['private_token'].'.tmp';
    $file = plugin_dir_path(__FILE__) . $option_filename;
    $fp = fopen($file, 'w');

    // Write header
    $users_columns = "user_id;email;name;surname;address;city;zip;state;country;locale;lifetimeDuration;ordersCount;totalSpent;createdAt\n";
    fwrite($fp, $users_columns);

    // Obtain customers dump
    $args = array(
      'fields' => 'all_with_meta',
      'role' => 'customer',
    );
    $customers = get_users($args);

    // Write dump
    foreach ($customers as $user) {
        $buffer = $user->ID.";".
        Visidea::sanitizeString($user->user_email).';'.
        Visidea::sanitizeString($user->first_name).';'.
        Visidea::sanitizeString($user->last_name).';'.
        Visidea::sanitizeString($user->billing_address_1).';'.
        Visidea::sanitizeString($user->billing_city).';'.
        Visidea::sanitizeString($user->billing_postcode).';'.
        Visidea::sanitizeString($user->billing_state).';'.
        Visidea::sanitizeString($user->billing_country).';'.
        Visidea::sanitizeString(get_user_locale($user->ID)).';'.
        ';'.
        wc_get_customer_order_count($user->ID).';'.
        wc_get_customer_total_spent($user->ID).';'.
        Visidea::sanitizeString($user->user_registered) . "\n";

        fwrite($fp, $buffer);

    }

    fclose($fp);

    $old_filename = 'users_'.$options['private_token'].'.csv';
    $hash_filename = 'users_'.$options['private_token'].'.hash';
    unlink(plugin_dir_path(__FILE__) . $old_filename);
    $file = plugin_dir_path(__FILE__) . $option_filename;
    rename(plugin_dir_path(__FILE__) . $option_filename, plugin_dir_path(__FILE__) . $old_filename);
    file_put_contents(plugin_dir_path(__FILE__) . $hash_filename, hash_file('md5', plugin_dir_path(__FILE__) . $old_filename));

  }

  function vs_get_customer_total_spent($email) {
    global $wpdb;
    $sql = "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_billing_email' AND meta_value = '$email'";
    $orders = $wpdb->get_results($sql, OBJECT);
    $total = 0;
    foreach ($orders as $order) {
      $sql = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_total' AND post_id = ".$order->post_id;
      $value = $wpdb->get_results($sql, OBJECT);
      $total += $value[0]->meta_value;
    }
    return $total;
  }

  function vs_get_customer_order_count($email) {
    global $wpdb;
    $sql = "SELECT COUNT(DISTINCT(post_id)) AS n FROM {$wpdb->prefix}postmeta WHERE meta_value = '$email'";
    $orders = $wpdb->get_results($sql, OBJECT);
    return $orders[0]->n;
  }

  public function cron_interactions_month() {
  }

  public function cron_interactions_dump() {
      // error_log( 'fired cron_interactions_dump at ' . date( DATE_RFC2822 ) );

      // Initialize interactions file
      $options = get_option('visidea_plugin_options');
      $option_filename = 'interactions_'.$options['private_token'].'.tmp';
      $file = plugin_dir_path(__FILE__) . $option_filename;
      $fp = fopen($file, 'w');

      // $logfile = plugin_dir_path(__FILE__) . 'log.txt';
      // $fp2 = fopen($logfile, 'w');

      // Write header
      $interactions_columns = "item_id;action;user_id;timestamp\n";
      fwrite($fp, $interactions_columns);

      for ($i=0; $i<356; $i++) {
        // Get all previous orders
        $start   = 356 - $i;
        $one_day = 24 * 60 * 60;
        $today   = strtotime( date('Y-m-d') );

        // fwrite($fp2, json_encode(array(
        //   'limit' => -1,
        //   'orderby' => 'date',
        //   'order' => 'DESC',
        //   'date_created' => ( $today - ( $days_delay * $one_day ) ).'...'.( $today - ( $days_delay * $one_day ) + $one_day ),
        // ))."\n");
        // fwrite($fp2, 'start:'.gmdate("Y-m-d\TH:i:s\Z",( $today - ( $start * $one_day ) ))."\n");
        // fwrite($fp2, 'end:'.gmdate("Y-m-d\TH:i:s\Z",( $today - ( $start * $one_day ) + $one_day ))."\n");

        $query = new WC_Order_Query( array(
          'limit' => -1,
          'date_created' => ( $today - ( $start * $one_day ) ).'...'.( $today - ( $start * $one_day ) + $one_day ),
        ) );
        $orders = $query->get_orders();
  
        foreach ($orders as $order) {
          // fwrite($fp2, 'order:'.$order->get_id()."\n");

          // Get customer ID
          // $user_id = $order->get_billing_email();
          $user_id = $order->get_user_id();
          // if (empty($user_id)) {
          //   $user_id = $this->vs_get_customer_id($order->get_billing_email());
          //   if (empty($user_id)) {
          //     $user_id = $order->get_billing_email();
          //   } else {
          //     $user_id = 'c_'.$user_id;
          //   }
          // }
  
          // Loop through order items and write them in the opened file
          foreach ($order->get_items() as $item_id => $item) {
  
            // Get product handle
            $product = $item->get_product();
  
            // Get the product ID
            if (is_object($product))
              $product_id = $product->get_id();
            else
              $product_id = 0;
  
            // fwrite($fp2, 'order:'.$order->get_id()."\n");
            // fwrite($fp2, 'mail:'.$order->get_billing_email()."\n");
            // fwrite($fp2, 'customer_id:'.$user_id."\n");
            // fwrite($fp2, 'product_id:'.$product_id."\n");
      
      
            // If all is set create the buffer
            if ($product_id > 0 && !empty($user_id)) {
              // fwrite($fp2, 'write to file'."\n");
  
              $buffer = $product_id . ';"purchase";' . $user_id . ';"'.$order->get_date_created()->format(DateTime::ATOM) .'"'. "\n";
  
              // Write to file
              fwrite($fp, $buffer);
            }
  
          }
  
        }
  
      }

      fclose($fp);

      $old_filename = 'interactions_'.$options['private_token'].'.csv';
      $hash_filename = 'interactions_'.$options['private_token'].'.hash';
      unlink(plugin_dir_path(__FILE__) . $old_filename);
      $file = plugin_dir_path(__FILE__) . $option_filename;
      rename(plugin_dir_path(__FILE__) . $option_filename, plugin_dir_path(__FILE__) . $old_filename);
      file_put_contents(plugin_dir_path(__FILE__) . $hash_filename, hash_file('md5', plugin_dir_path(__FILE__) . $old_filename));

    }

  function check_crons() {
    
    if ( true === as_has_scheduled_action( 'visidea_export_data_job' ) ) {
      as_unschedule_action('visidea_export_data_job');
    }

    if ( false === as_has_scheduled_action( 'visidea_export_items_job' ) ) {
      as_schedule_recurring_action( strtotime( '+1 minutes' ), 3600, 'visidea_export_items_job' );
    }

    if ( false === as_has_scheduled_action( 'visidea_export_users_job' ) ) {
      as_schedule_recurring_action( strtotime( '+6 minutes' ), 3600, 'visidea_export_users_job' );
    }

    if ( false === as_has_scheduled_action( 'visidea_export_interactions_job' ) ) {
      // as_unschedule_action('visidea_interactions_data_job');
      as_schedule_recurring_action( strtotime( '+11 minutes' ), 3600, 'visidea_export_interactions_job' );
    }

  }

  function vs_get_customer_id($email) {
    global $wpdb;
    $sql = "SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup WHERE email = '$email'";
    $customer = $wpdb->get_results($sql, OBJECT);
    return $customer[0]->customer_id;
  }

  function register_settings() {
    register_setting('visidea_plugin_options', 'visidea_plugin_options', 'visidea_plugin_options_validate');
    add_settings_section('api_settings', __( 'API Settings', 'visidea' ), 'visidea_plugin_section_text', 'visidea_plugin');
    add_settings_field('visidea_plugin_setting_public_token', __( 'Public token', 'visidea' ), 'visidea_plugin_setting_public_token', 'visidea_plugin', 'api_settings');
    add_settings_field('visidea_plugin_setting_private_token', __( 'Private token', 'visidea' ), 'visidea_plugin_setting_private_token', 'visidea_plugin', 'api_settings');
    add_settings_field('visidea_plugin_setting_database', __( 'Database', 'visidea' ), 'visidea_plugin_setting_database', 'visidea_plugin', 'api_settings');
    add_settings_section('api_labels', __( 'File URLs', 'visidea' ), 'visidea_plugin_section_labels', 'visidea_plugin');
  }

  function create_settings() {
    $options = get_option('visidea_plugin_options');
    // var_dump($options);
    if (!$options) {
      $public_token = $this->generate_random_string();
      $private_token = $this->generate_random_string();
      $site_url = parse_url(get_site_url());
      // var_dump($site_url);
      $database = $site_url['host'];
      $options_array = array('public_token'=>$public_token,'private_token'=>$private_token,'database'=>$database);
      // var_dump($options_array);
      add_option('visidea_plugin_options',$options_array);
    }

  }

  function generate_random_string($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
  
  function add_settings_page() {
    $this->create_settings();
    add_menu_page( "Visidea", "Visidea", "manage_options", "visidea", "visidea_render_plugin_index_page", plugins_url('images/visidea-icon.png', __FILE__) , 8);
    add_submenu_page("visidea", __( 'Settings', 'visidea' ), __( 'Settings', 'visidea' ), "manage_options", "visidea_general_setting","visidea_render_plugin_settings_page");
  }
  
}


function visidea_get_main_language() {
  $current_language = get_bloginfo('language');
  if (defined('ICL_LANGUAGE_CODE')) {
    $current_language = ICL_LANGUAGE_CODE;
  }
  if (function_exists('pll_current_language')) {
    $current_language = pll_current_language();
  }
  return substr($current_language, 0, 2);
}

function visidea_render_plugin_index_page() {
  $options = get_option('visidea_plugin_options');
  // $f = plugins_url('items_'.esc_attr($options['private_token']));
  // var_dump($f);
  $subscribe_url = 'https://app.visidea.ai/?platform=wordpress&website='.$options['database'].
    '&public_token='.$options['public_token'].'&private_token='.$options['private_token'].
    '&items_file='.urlencode(plugins_url('items_'.esc_attr($options['private_token']).'.csv', __FILE__)).
    '&users_file='.urlencode(plugins_url('users_'.esc_attr($options['private_token']).'.csv', __FILE__)).
    '&interactions_file='.urlencode(plugins_url('interactions_'.esc_attr($options['private_token']).'.csv', __FILE__)).
    '&language='.visidea_get_main_language();
  // var_dump($subscribe_url);
?>
    <img  src="<?php echo plugins_url('images/visidea-logo.png', __FILE__); ?>" style="width:350px">
    <hr>
    <h2><?php _e( 'Welcome to Visidea', 'visidea' ); ?></h2>
    <div style="margin-bottom:2rem;">
      <span><?php _e( 'Visidea is the Visual Search and Product Recommendation plugin for WordPress & WooCommerce.', 'visidea' ); ?></span>
    </div>
    <div style="margin-bottom:2rem;">
      <iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/YUfZQmBE7Uc" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
    <div style="margin-bottom:2rem;">
      <span><?php _e( 'To integrate your website with Visidea, click the link below, login/register and wait for the confirmation message', 'visidea' ); ?>.</span>
    </div>
    <div style="margin-bottom:2rem;">
     <button type="button" class="button button-primary" onclick="document.location='<?php echo $subscribe_url; ?>'"><?php _e( 'Integrate with Visidea', 'visidea' ); ?></button>
    </div>
    <div style="margin-bottom:2rem;">
      <span>
        <?php _e( 'Any problems?', 'visidea' ); ?>
        <?php _e( 'You can take a look at our', 'visidea' ); ?>
        <strong><a href="https://docs.visidea.ai/docs/plugins/wordpress" target="_blank"><?php _e( 'documentation', 'visidea' ); ?></a></strong>
        <?php _e( 'or you can contact us: ', 'visidea' ); ?>
        <strong><a href="mailto:support@visidea.ai"><?php _e( 'support@visidea.ai', 'visidea' ); ?></a></strong>.
      </span><br><br>
      <span><?php _e( 'Discover more about our service on our ', 'visidea' ); ?><strong><a href="https://visidea.ai/" target="_blank"><?php _e( 'website', 'visidea' ); ?></a></strong>.</span>
    </div>
<?php
}

function visidea_render_plugin_settings_page() {
    ?>
    <img  src="<?php echo plugins_url('images/visidea-logo.png', __FILE__); ?>" style="width:350px">
    <hr>
    <h2><?php _e( 'Visidea configurations', 'visidea' ); ?></h2>
    <form action="options.php" method="post">
        <?php
        settings_fields('visidea_plugin_options');
        do_settings_sections('visidea_plugin'); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php _e( 'Save', 'visidea' ); ?>" />
    </form>
<?php
}

function visidea_plugin_section_text() {
    echo '<p>'.__( 'Here you can set all the options for using our API:', 'visidea' ).'</p>';
}

function visidea_plugin_setting_public_token() {
    $options = get_option('visidea_plugin_options');
    $public_token = isset($options['public_token'])?$options['public_token']:'';
    echo "<input id='visidea_setting_public_token' name='visidea_plugin_options[public_token]' type='text' value='".esc_attr($public_token)."' />";
}

function visidea_plugin_setting_private_token() {
    $options = get_option('visidea_plugin_options');
    $private_token = isset($options['private_token'])?$options['private_token']:'';
    echo "<input id='visidea_setting_private_token' name='visidea_plugin_options[private_token]' type='text' value='".esc_attr($private_token)."' />";
}

function visidea_plugin_setting_database() {
    $options = get_option('visidea_plugin_options');
    $database = isset($options['database'])?$options['database']:'';
    echo "<input id='visidea_setting_database' name='visidea_plugin_options[database]' type='text' value='".esc_attr($database)."' />";
}

function visidea_plugin_section_labels() {
    $options = get_option('visidea_plugin_options');
    $private_token = isset($options['private_token'])?$options['private_token']:'';
    if (empty($private_token)) {
        echo '<p>'.__( 'You have to setup tokens to get file urls.', 'visidea' ).'</p>';
    } else {
        echo '<p>'.__( 'Items file:', 'visidea' ).'</p>';
        echo '<p><pre>'.plugins_url('items_'.esc_attr($private_token).'.csv', __FILE__).'</pre></p>';
        echo '<p>'.__( 'Users file:', 'visidea' ).'</p>';
        echo '<p><pre>'.plugins_url('users_'.esc_attr($private_token).'.csv', __FILE__).'</pre></p>';
        echo '<p>'.__( 'Interactions file:', 'visidea' ).'</p>';
        echo '<p><pre>'.plugins_url('interactions_'.esc_attr($private_token).'.csv', __FILE__).'</pre></p>';
    }
}

function visidea_plugin_options_validate($input) {
    // var_dump($input);exit;
    // $newinput['api_key'] = trim($input['api_key']);
    $newinput['public_token'] = trim($input['public_token']);
    $newinput['private_token'] = trim($input['private_token']);
    $newinput['database'] = trim($input['database']);
    // if (!preg_match('/^[a-z0-9]{32}$/i', $newinput['api_key'])) {
    //     $newinput['api_key'] = '';
    // }
    // var_dump($newinput);exit;
    return $newinput;
}
