<?php
/**
 * Plugin Name: Banana Splash
 * Plugin URI: http://banana-splash.com/wordpress
 * Description: Add a Banana Splash splashing splasher for your site!
 * Version: 0.6
 * Author: Yoav Matchulsky
 * Author URI: http://matchulsky.com/
 * License: WTFPL
 */

defined( 'ABSPATH' ) or die( 'buawwhhaawwwhhhaa' );

require_once 'inc/pages_selector.php';

class WPBananaSplash {
  const SCRIPT_REGEX = '/^<script[^>]+><\/script>$/';

  public $pages_selector, $plugins_url;
  private $options;

  public function __construct() {
    $this->get_options();

    if (is_admin()) {
      add_action( 'admin_menu', array( $this, 'add_plugins_page' ) );
      add_action( 'admin_init', array( $this, 'admin_init' ) );

      $this->set_pages_selector();
    }
    else {
      add_action( 'wp_footer', array( $this, 'inject_splasher' ) );
    }

    register_activation_hook( __FILE__, array( $this, 'activate' ));
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

  public function show_splasher_in_page() {
    if ( $this->options['all'] ) {
      return true;
    }

    if ( is_front_page() && $this->show_on_front()) {
      return true;
    }

    if ( is_single() or is_page() ) {
      global $post;

      if ( isset( $this->options[ 'selected_post_ids' ] ) ) {
        return in_array( $post->ID, $this->options[ 'selected_post_ids' ] );
      }
    }

    return false;
  }

  private function show_on_front() {
    if ( get_option( 'show_on_front' ) === 'posts' ) {
      return $this->options[ 'show_on_front' ];
    }

    return false;
  }

  public function add_plugins_page() {
    $icon_url = plugins_url( 'images/icon.gif', __FILE__ );
    $page = add_menu_page( 'Banana Splash Settings', 'Banana Splash', 'manage_options', 'banana_splash_settings', array( $this, 'plugins_page' ), $icon_url );
    add_action( 'admin_print_styles-' . $page, array( $this, 'admin_styles' ) );
  }

  public function admin_init() {
    if ( get_option( 'banana_splash_activation', false ) ) {
      delete_option( 'banana_splash_activation' );
      if ( !isset( $_GET[ 'activate-multi' ] )) {
        wp_redirect( admin_url( 'admin.php?page=banana_splash_settings' ) );
      }
    }

    $this->plugins_url = plugins_url( '', __FILE__ );
    wp_register_style( 'banana-splash-admin', plugins_url( 'css/settings.css', __FILE__ ) );
  }

  public function admin_styles() {
    global $wp_scripts;
    $core_ver = $wp_scripts->registered['jquery-ui-core']->ver;
    $settings_deps = array( 'jquery-ui-tabs' );
    $protocol = is_ssl() ? 'https' : 'http';

    list($major, $minor, $tiny) = explode( '.', $core_ver );
    $has_minified = (($major == 1 && (($minor == 10 && $tiny >= 1) || $minor > 10)) || $major > 1);
    $minified = ($has_minified ? '.min' : '');

    wp_enqueue_style( 'banana-splash-admin' );
    wp_enqueue_style( 'jquery-ui-smoothness', "{$protocol}://ajax.googleapis.com/ajax/libs/jqueryui/{$core_ver}/themes/smoothness/jquery-ui{$minified}.css" );
    wp_enqueue_script( 'banana-splash-settings-script', plugins_url('js/settings.js', __FILE__), $settings_deps );
  }

  public function inject_splasher() {
    if ( $this->show_splasher_in_page() and $this->check() ) {
      echo $this->options['code'] . "\n";
    }
  }

  public function plugins_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( isset($_POST) and isset($_POST['banana_splash_settings']) ) {
      if ( !isset( $_POST['checking-ma-validicity'] ) or !wp_verify_nonce( $_POST['checking-ma-validicity'], 'banana-splash-settings' ) ) {
        $this->error_msg("WHAT ARE YOU TRYING TO DO????");
      }
      else {
        $this->save_code( $_POST[ 'banana_splash_settings' ][ 'code' ]);
        $this->save_pages( $_POST[ 'banana_splash_settings' ] );
        $this->save_show_on_front( $_POST[ 'banana_splash_settings' ][ 'show_on_front'] );
      }
    }

    require ( plugin_dir_path( __FILE__ ) . 'pages/settings.php' );
  }

  protected function error_msg($msg) {
    echo '<div class="error"><p>' . $msg . '</p></div>';
  }

  protected function updated_msg($msg) {
    echo '<div class="updated"><p>' . $msg . '</p></div>';
  }

  protected function save_code( $code = '' ) {
    $error = false;

    $code = stripslashes( $code );
    if ( $this->check( $code ) ) {
      $this->set_options( array( 'code' => $code ));
      $this->updated_msg( __( "Your <strong>Banana Splash</strong> code is pretty good, and I'll allow you to use it here.", 'banana_splash' ) );
    }
    else {
      $error = true;
      $this->error_msg( __( "Are you sure you copied it correctly? <strong>don't make me recheck your code!</strong>", 'banana_splash' ) );
    }

    return $error;
  }

  protected function save_pages( $posted ) {
    $options = array(
      'all' => 'all' === $posted['all'],
    );

    if ( isset( $posted[ 'selected_post_ids' ] ) && is_array( $posted[ 'selected_post_ids' ] ) ) {
      $options[ 'selected_post_ids' ] = array_keys( $posted[ 'selected_post_ids' ] );
    }
    else {
      $options[ 'selected_post_ids' ] = array();
    }

    $this->set_options( $options );
  }

  private function save_show_on_front( $show_on_front = true) {
    $this->set_options( array( 'show_on_front' => ($show_on_front ? true : false) ));
  }

  private function get_options() {
    $this->options = get_option( 'banana_splash_settings' );
  }

  private function set_options($update = array()) {
    $this->options = array_merge( $this->options, $update );
    $this->set_pages_selector_posts();
    $this->pages_selector->set_show_on_front( $this->options[ 'show_on_front' ] );

    update_option( 'banana_splash_settings', $this->options );
  }

  public function set_pages_selector() {
    $options = array(
      'prefix_id' => 'banana_splash',
      'language_domain' => 'banana_splash',
      'field_prefix' => 'banana_splash_settings[selected_post_ids]',
      'toggle_prefix' => 'banana_splash_settings[all]',
      'show_on_front_prefix' => 'banana_splash_settings[show_on_front]',

      'labels' => array(
        'widget' => array(
          'selected' => __( 'Banana-Splash appears on:', 'banana_splash' ),
        ),
        'buttons' => array(
          'all_pages'      => __( 'All Pages', 'banana_splash'),
          'specific_pages' => __( 'Select specific pages', 'banana_splash'),
        ),
      )
    );

    $this->pages_selector = new PagesSelector( $options );
    $this->pages_selector->set_show_on_front( $this->options[ 'show_on_front' ] );
    $this->set_pages_selector_posts();

    return $this->pages_selector;
  }

  public function set_pages_selector_posts() {
    if ($this->options[ 'all' ]) {
      $this->pages_selector->set_all();
    }
    else {
      $this->pages_selector->set_specific_posts( $this->options[ 'selected_post_ids' ] );
    }
  }

  public function activate() {
    $options = get_option( 'banana_splash_settings' );

    if ( !isset( $options[ 'selected_post_ids' ] ) ) {
      $options[ 'selected_post_ids' ] = array();
      $options[ 'all' ] = true;
    }

    if ( !isset( $options[ 'show_on_front' ] ) ) {
      $options[ 'show_on_front' ] = get_option( 'show_on_front', false ) === 'posts';
    }

    update_option( 'banana_splash_settings', $options );
    update_option( 'banana_splash_activation', 'true' );
  }
}

$wp_banana_splash = new WPBananaSplash();