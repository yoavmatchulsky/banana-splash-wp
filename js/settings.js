jQuery(function($) {
  var $wrapper = $('.banana-splash-admin-wrapper'),
      $radio_buttons = $wrapper.find('.pages-selector-radio-buttons'),
      $widget = $wrapper.find('.pages-selector-widget'),
      $selector = $widget.find('.pages-selector'),
      $selected = $widget.find('.pages-selected'),
      $items = $selector.find('.page-item');

  $selector.tabs({
    activate: function(event, ui) {
      $selected.tabs({ active: $(ui.newTab).index() });
      handle_empty_items( ui.newPanel );
      handle_empty_items( $selected.find('.ui-tabs-active') );
    }
  });
  $selected.tabs({
    activate: function(event, ui) {
      $selector.tabs({ active: $(ui.newTab).index() });
      handle_empty_items( ui.newPanel );
      handle_empty_items( $selector.find('.ui-tabs-active') );
    }
  });

  var handle_empty_items = function( $panel ) {
    var panel_is_empty = $panel.find('.page-item:visible').length === 0,
        empty = $panel.find('.pages-selector-empty'),
        check_all = $panel.find('.banana-splash-check-all-wrapper');

    if (panel_is_empty) {
      empty.fadeIn('slow');
      check_all.fadeOut('slow');
    }
    else {
      empty.fadeOut('slow');
      check_all.fadeIn('slow');
    }
  };

  $radio_buttons.on('change', function(e, data) {
    var show_selected_pages_widget = 'specific' === $(this).find(':checked').val();
    if (show_selected_pages_widget) {
      $widget.fadeIn('fast');
    }
    else {
      if (data != null && data['initial'] != null && data['initial']) {
        $widget.hide(0);
      }
      else {
        $widget.fadeOut('fast');
      }
    }
  }).trigger('change', { initial: true });

  // Move items from selector to selected
  $selector.find('.page-item').on('click', 'input:checkbox', function() {
    var $checkbox = $(this),
        $item = $checkbox.closest('.page-item'),
        post_id = $checkbox.data('postId'),
        post_type = $checkbox.data('postType'),
        $cloned, new_id,
        $panel = $selected.find('#banana_splash_' + post_type + '_selected');

    if ($item.is(':animated')) {
      return false;
    }

    $cloned = $item.clone().hide();
    $item.fadeOut('slow', function() {
      $checkbox.prop('checked', false);
      handle_empty_items( $item.closest('.ui-tabs-panel') );
    });

    new_id = $checkbox.attr('id') + '_selected';
    $cloned.find('input:checkbox').attr('id', new_id);
    $cloned.find('label').attr('for', new_id);
    $cloned.appendTo( $panel.find('ul') ).fadeIn('slow', function() {
      handle_empty_items($panel);
    });
  });

  // Move items back to selector (unselected)
  $selected.on('click', 'ul input:checkbox', function() {
    var $checkbox = $(this),
        $item = $checkbox.closest('.page-item'),
        post_id = $checkbox.data('postId'),
        post_type = $checkbox.data('postType');
    
    if ($item.is(':animated')) {
      return false;
    }

    $selector.find('.page-item').has('[data-post-id="' + post_id + '"]').fadeIn('fast', function() {
      $panel = $selector.find('#banana_splash_' + post_type);
      handle_empty_items( $panel );
    });

    $item.fadeOut('slow', function() {
      handle_empty_items( $item.closest('.ui-tabs-panel') );
      $item.remove();
    });
  });

  $selector.find('input:checkbox').each(function() {
    $(this).prop('checked', false);
  });

  $widget.on('click', '.banana-splash-check-all-wrapper input', function() {
    var $check_all = $(this),
        $wrapper = $check_all.closest('.banana-splash-check-all-wrapper'),
        $page_items = $wrapper.siblings('ul').find('.page-item input'),
        i = 0,
        trigger_next_item = function() {
          if (i < $page_items.length) {
            $page_items.eq(i).trigger('click');
            i += 1;
          }
          else {
            $check_all.prop('checked', false);
          }
          setTimeout(trigger_next_item, 30);
        };

    setTimeout(trigger_next_item, 10);
  });
});