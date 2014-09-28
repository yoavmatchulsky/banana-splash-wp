<?php

class PagesSelector {
  private $options;
  private $posts, $post_types;
  private $show_on_front;

  private $all = true;
  private $post_ids = array();

  function __construct($options = array()) {
    $this->options = array_deep_merge(array(
      'prefix_id' => 'pages_selector',
      'language_domain' => 'pages_selector',
      'field_prefix' => 'pages_selector[pages]',
      'toggle_prefix' => 'pages_selector[all]',
      'show_on_front_prefix' => 'pages_selector[show_on_front]',

      'labels' => array(
        'widget' => array(
          'selector' => 'Choose content:',
          'selected' => 'Appears on:',
        ),
        'buttons' => array(
          'all_pages' => 'All Pages',
          'specific_pages' => 'Specific Pages',
        ),
        'empty' => 'No Posts selected yet.',
        'show_on_front' => 'Show on front page? ',
        'check_all' => 'Check All'
      ),
    ), $options);

    $this->get_post_types();
    $this->posts = array();
  }

  public function widget() {
    return '
      <div class="pages-selector-widget">
        <div class="pages-selector pages-selector-panel">
          <h4>' . $this->label('widget.selector') . '</h4>
          <div class="pages-selector-tree">
            ' . $this->pages_selector() . '
          </div>
        </div>

        <div class="pages-selected pages-selector-panel">
          <h4>' . $this->label('widget.selected') . '</h4>
          <div class="pages-selector-tree">
            ' . $this->pages_selected() . '
          </div>
        </div>

        <div class="pages-selector-front">
          ' . $this->show_on_front_checkbox() . '
        </div>
      </div>';
  }

  public function buttons() {
    $all_id = $prefix_id . '_all_all';
    $specific_id = $prefix_id . '_all_specific';

    return '
      <div class="pages-selector-radio-buttons">
        <input type="radio" name="' . $this->options[ 'toggle_prefix' ] . '" id="' . $all_id . '" value="all"
          ' . ($this->all ? 'checked="checked"' : '') . '/>
          <label for="' . $all_id . '">' . $this->label('buttons.all_pages') . '</label>
        <input type="radio" name="' . $this->options[ 'toggle_prefix' ] . '" id="' . $specific_id . '" value="specific"
          ' . ($this->all ? '' : 'checked="checked"') . '/>
          <label for="' . $specific_id . '">' . $this->label('buttons.specific_pages') . '</label>
      </div>';
  }

  public function show_on_front_checkbox() {
    $field_id = $prefix_id . '_show_on_front';
    $checked = $this->show_on_front ? 'checked="checked"' : '';

    return '
      <p><div class="pages-selector-show-on-front">
        <input type="checkbox" name="' . $this->options[ 'show_on_front_prefix' ] . '" id="' . $field_id . '" ' . $checked . ' />
        <label for="' . $field_id . '">' . $this->label('show_on_front') . '</label>
      </div></p>
    ';
  }

  // selector and its methods
  private function pages_selector() {
    $titles = array();
    $posts_items = array();

    foreach ( $this->post_types as $post_type ) {
      $titles[] = $this->selector_format_title($post_type);
      $posts_items[] = $this->selector_format_items_for_post_type($post_type);
    }

    $output = '<ul><li>' . implode('</li><li>', $titles) . '</li></ul>';
    $output .= implode($posts_items);

    return $output;
  }

  private function selector_format_title($post_type) {
    $label = $post_type->labels->name or $post_type->label or $post_type->name;
    $id = $this->options['prefix_id'] . '_' . $post_type->name;

    return '<a href="#' . $id . '">' . __( $label, $this->options['language_domain'] ) . '</a>';
  }

  private function selector_format_items_for_post_type($post_type) {
    $posts = $this->get_posts_for_post_type($post_type);

    $output = '<div id="' . $this->options['prefix_id'] . '_' . $post_type->name . '">';

    if ( is_array($posts) ) {
      $output .= $this->empty_posts( !$this->has_unselected_post( $posts ) );

      $output .= '<ul>';
      foreach ($posts as $post) {
        $output .= $this->selector_format_item($post);
      }
      $output .= '</ul>';
    }

    $output .= $this->check_all( $post_type->name );
    return $output . '</div>';
  }

  private function selector_format_item($post) {
    $post_id = $post->ID;
    $field_id = $this->options['prefix_id'] . '_' . $post_id;
    $selected = 

    $classes = array('page-item');
    if ( $this->post_is_selected( $post ) ) {
      $classes[] = 'pages-selector-hidden';
    }

    return '
      <li class="' . implode( ' ', $classes ) . '">
        <input type="checkbox" name="' . $this->options['field_prefix'] . '[' . $post_id . ']"
          id="' . $field_id . '"
          data-post-id="' . $post_id . '"
          data-post-type="' . $post->post_type . '" />
        <label for="' . $field_id . '">' . $post->post_title . '</label>
      </li>';
  }

  // selected pages and all of their functions
  private function pages_selected() {
    $titles = array();
    $posts_items = array();

    foreach ( $this->post_types as $post_type ) {
      $titles[] = $this->selected_format_title($post_type);
      $posts_items[] = $this->selected_format_items_for_post_type($post_type);
    }

    $output = '<ul><li>' . implode('</li><li>', $titles) . '</li></ul>';
    $output .= implode($posts_items);

    return $output;
  }

  private function selected_format_title($post_type) {
    $label = $post_type->labels->name or $post_type->label or $post_type->name;
    $id = $this->options['prefix_id'] . '_' . $post_type->name . '_selected';

    return '<a href="#' . $id . '">' . __( $label, $this->options['language_domain'] ) . '</a>';
  }

  private function selected_format_items_for_post_type($post_type) {
    $posts = $this->get_posts_for_post_type($post_type);

    $output = '<div id="' . $this->options['prefix_id'] . '_' . $post_type->name . '_selected">';

    if ( is_array($posts) && !empty($posts) ) {
      $selected_posts = array_filter( $posts, array( $this, 'post_is_selected' ));
      $output .= $this->empty_posts( !empty($selected_posts) );

      $output .= '<ul>';
      foreach ($selected_posts as $post) {
        $output .= $this->selected_format_item($post);
      }
      $output .= '</ul>';
    }

    $output .= $this->check_all( $post_type->name, true );
    return $output . '</div>';
  }

  private function selected_format_item($post) {
    $post_id = $post->ID;
    $field_id = $this->options['prefix_id'] . '_' . $post_id . '_selected';

    return '
      <li class="page-item">
        <input type="checkbox" name="' . $this->options['field_prefix'] . '[' . $post_id . ']"
          id="' . $field_id . '"
          data-post-id="' . $post_id . '"
          data-post-type="' . $post->post_type . '"
          checked="checked" />
        <label for="' . $field_id . '">' . $post->post_title . '</label>
      </li>';
  }

  private function check_all( $type = 'post', $selected = false ) {
    $field_id = 'banana-splash-check-all_' . ($selected ? 'selected' : 'selector') . '_' . $type;

    return '
      <div class="banana-splash-check-all-wrapper">
        <input type="checkbox" id="' . $field_id . '" />
        <label for="' . $field_id . '">' . $this->label('check_all') . '</label>
      </div>
    ';
  }

  public function set_all() {
    $this->all = true;
  }

  public function set_specific_posts($post_ids) {
    $this->all = false;
    $this->post_ids = $post_ids;
  }

  public function set_show_on_front( $value ) {
    $this->show_on_front = $value;
  }

  public function post_is_selected( $post ) {
    return in_array( $post->ID, $this->post_ids );
  }

  public function has_unselected_post( $posts = array() ) {
    $posts_count = count($posts);
    $i = 0;

    while ( $i < $posts_count ) {
      if ( !$this->post_is_selected( $posts[$i] ) ) {
        return false;
      }
      $i += 1;
    }

    return true;
  }

  private function empty_posts( $empty = true ) {
    $classes = array( 'pages-selector-empty' );
    if ( $empty ) {
      $classes[] = 'pages-selector-hidden';
    }

    return '<div class="' . implode(' ', $classes) . '">' . $this->label('empty') . '</div>';    
  }

  private function get_posts_for_post_type($post_type) {
    $name = $post_type->name;

    if ( ! isset($this->posts[ $name ]) ) {
      $args = array(
        'post_type' => $post_type->name,
        'posts_per_page' => -1,
      );

      if ( $post_type->hierarchical ) {
        $this->posts[ $name ] = get_pages( $args );
      }
      else {
        $this->posts[ $name ] = get_posts( $args );
      }
    }

    return $this->posts[ $name ];
  }

  private function get_post_types() {
    $post_types = get_post_types( array('_builtin' => false, 'public' => true), 'objects' );

    foreach (array( 'post', 'page' ) as $post_type) {
      array_unshift( $post_types, get_post_type_object( $post_type ) );
    }

    $this->post_types = $post_types;
    return $post_types;
  }

  private function label($key) {
    $labels = $this->options[ 'labels' ];
    $tree = explode( '.', $key );

    while (count($tree) > 1) {
      $labels = $labels[ array_shift($tree) ];
    }

    return $labels[ array_shift($tree) ];
  }  
}

if ( ! function_exists( 'array_deep_merge' ) ) {
  function array_deep_merge() {
    switch( func_num_args() ) {
      case 0 : return false; break;
      case 1 : return func_get_arg(0); break;
      case 2 :
        $args = func_get_args();
        $result = array();
        if( is_array($args[0]) and is_array($args[1]) ) {
          $all_keys = array_unique( array_merge( array_keys( $args[0] ), array_keys( $args[1] ) ) );

          foreach( $all_keys as $key ) {
            if ( is_array( $args[0][$key] ) && is_array( $args[1][$key] ) ) {
              $result[$key] = array_deep_merge( $args[0][$key], $args[1][$key] );
            }
            elseif ( isset( $args[0][$key]) && isset( $args[1][$key] ) ) {
              $result[$key] = $args[1][$key];
            }
            elseif ( ! isset( $args[1][$key] ) ) {
              $result[$key] = $args[0][$key];
            }
            elseif ( ! isset( $args[0][$key] ) ) {
              $result[$key] = $args[1][$key];
            }
          }

          return $result;
        }
        else {
          return $args[1];
        }

      default :
        $args = func_get_args();
        $args[1] = array_deep_merge( $args[0], $args[1] );
        array_shift( $args );
        return call_user_func_array( 'array_deep_merge', $args );
        break;
    }
  }
}