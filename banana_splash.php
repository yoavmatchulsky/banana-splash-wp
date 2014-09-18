<?php
/**
 * Plugin Name: Banana Splash
 * Plugin URI: http://banana-splash.com/wordpress
 * Description: Add a Banana Splash popup for your site
 * Version: 0.1
 * Author: Yoav Matchulsky
 * Author URI: http://matchulsky.com/
 * License: WTFPL
 */

defined( 'ABSPATH' ) or die( 'buawwhhaawwwhhhaa' );

class WPBananaSplash {
  const SCRIPT_REGEX = '/^<script[^>]+><\/script>$/';

  private $options, $pages;

  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_plugins_page' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );

    add_action( 'wp_footer', array( $this, 'wp_footer' ) );

    $this->get_options();
  }

  public function check($code = false) {
    if ( ! $code ) {
      $code = $this->options['code'];
    }

    if ( ! preg_match( self::SCRIPT_REGEX, $code) ) {
      return false;
    }

    foreach ( array( 'data-banana-key=', 'data-v=', '/code.js' ) as $key ) {
      if ( FALSE === strpos( $code, $key ) ) {
        return false;
      }
    }

    return true;
  }

  public function add_plugins_page() {
    $icon_url = plugins_url( 'images/icon.gif', __FILE__ );
    $page = add_menu_page( 'Banana Splash Settings', 'Banana Splash', 'manage_options', 'banana_splash_settings', array( $this, 'plugins_page' ), $icon_url );
    add_action( 'admin_print_styles-' . $page, array( $this, 'admin_styles' ) );
  }

  public function admin_init() {
    wp_register_style( 'banana-splash-admin', plugins_url( 'css/settings.css', __FILE__ ) );
  }

  public function admin_styles() {
    global $wp_scripts;
    $core_ver = $wp_scripts->registered['jquery-ui-core']->ver;
    $settings_deps = array(
      'jquery-ui-button', 'jquery-ui-tabs',
      'jquery-ui-droppable', 'jquery-ui-draggable'
    );

    wp_enqueue_style( 'banana-splash-admin' );

    wp_enqueue_style( 'jquery-ui-smoothness', "//ajax.googleapis.com/ajax/libs/jqueryui/{$core_ver}/themes/smoothness/jquery-ui.min.css" );
    wp_enqueue_script( 'banana-splash-settings-script', plugins_url('js/settings.js', __FILE__), $settings_deps );
  }

  public function wp_footer() {
    if ( $this->check() ) {
      echo $this->options['code'];
    }
  }

  public function plugins_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $error = false;
    $code = '';

    if ( isset($_POST) and isset($_POST['banana_splash_settings']) ) {
      $error = $this->save_settings();
    }

    require ( plugin_dir_path( __FILE__ ) . 'pages/settings.php' );
  }

  protected function error_msg($msg) {
    echo '<div class="error"><p>' . $msg . '</p></div>';
  }

  protected function updated_msg($msg) {
    echo '<div class="updated"><p>' . $msg . '</p></div>';
  }

  protected function save_settings() {
    $error = false;

    if ( !isset( $_POST['checking-ma-validicity'] ) or !wp_verify_nonce( $_POST['checking-ma-validicity'], 'banana-splash-settings' ) ) {
      $this->error_msg("WHAT ARE YOU TRYING TO DO????");
    }
    else {
      if ( isset($_POST['banana_splash_settings']['code']) ) {
        $code = stripslashes( $_POST['banana_splash_settings']['code'] );
        if ( $this->check( $code ) ) {
          $this->set_options(array('code' => $code));
          $this->updated_msg("Your <strong>Banana Splash</strong> code is pretty good, and I'll allow you to use it here.");
        }
        else {
          $error = true;
          $this->error_msg("Are you sure you copied it correctly? <strong>don't make me recheck your code!</strong>");
        }
      }
      else {
        $this->error_msg('You must enter your implementation code');
      }
    }

    return $error;
  }

  private function get_options() {
    $this->options = get_option( 'banana_splash_settings' );
    $this->pages = $this->options['pages'] === 'specific' ? $this->options['selected_pages'] : 'all';
  }

  private function set_options($update = array()) {
    $this->options = array_merge( $this->options, $update );
    update_option( 'banana_splash_settings', $this->options );
  }

  private function pages_selector() {
    $post_types = get_post_types( array('_builtin' => false, 'public' => true), 'objects' );
    $titles = array();
    $posts_items = array();

    array_unshift($post_types, 'post', 'page');

    foreach ($post_types as $post_type) {
      if (! $post_type instanceof stdClass) {
        $post_type = get_post_type_object($post_type);
      }

      $titles[] = $this->format_selector_title($post_type);
      $posts_items[] = $this->format_selected_items_for_post_type($post_type);
    }

    $output = '<ul><li>' . implode('</li><li>', $titles) . '</li></ul>';
    $output .= implode($posts_items);

    return $output;
  }

  private function format_selector_title($post_type) {

    $label = $post_type->labels->name or $post_type->label or $post_type->name;
    $id = 'banana_splash_' . $post_type->name;

    return '<a href="#' . $id . '">' . __( $label, 'banana_splash' ) . '</a>';
  }

  private function format_selected_items_for_post_type($post_type) {
    $posts = $this->get_posts_for_post_type($post_type);

    $output = '<div id="banana_splash_' . $post_type->name . '">';

    if (is_array($posts) && !empty($posts)) {
      foreach ($posts as $post) {
        $output .= $this->format_selected_item($post);
      }
    }

    return $output . '</div>';
  }

  private function format_selected_item($post) {
    $post_id = $post->ID;
    $field_id = 'banana_splash_settings_pages_' . $post_id;

    return '
      <li class="page-item">
        <input type="checkbox" name="banana_splash_settings[pages][' . $post_id . ']"
          id="' . $field_id . '"
          data-post-id="' . $post_id . '"
          data-orig-field-id="' . $field_id . '" />
        <label for="' . $field_id . '">' . $post->post_title . '</label>
      </li>';
  }

  private function get_posts_for_post_type($post_type) {
    $args = array( 'post_type' => $post_type->name );
    if ($post_type->hierarchical) {
      return get_pages($args);
    }
    else {
      return get_posts($args);
    }
  }
}

$wp_banana_splash = new WPBananaSplash();