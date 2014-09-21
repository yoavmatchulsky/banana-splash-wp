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
    }
  });
  $selected.tabs({
    activate: function(event, ui) {
      $selector.tabs({ active: $(ui.newTab).index() });
    }
  });

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
        $cloned, new_id, $panel;

    if ($item.is(':animated')) {
      return false;
    }

    $cloned = $item.clone().hide();
    $item.fadeOut('slow', function() {
      $checkbox.prop('checked', false);
      if ( $item.siblings('.page-item:visible').length === 0 ) {
        $item.siblings('.pages-selector-empty').fadeIn();
      }
    });

    new_id = $checkbox.attr('id') + '_selected';
    $cloned.find('input:checkbox').attr('id', new_id);
    $cloned.find('label').attr('for', new_id);

    $panel = $selected.find('#banana_splash_' + post_type + '_selected');
    $panel.find('.pages-selector-empty').fadeOut();

    $cloned.appendTo( $panel ).fadeIn('slow');
  });

  // Move items back to selector (unselected)
  $selected.on('click', 'input:checkbox', function() {
    var $checkbox = $(this),
        $item = $checkbox.closest('.page-item'),
        post_id = $checkbox.data('postId'),
        post_type = $checkbox.data('postType');
    
    if ($item.is(':animated')) {
      return false;
    }

    $selector.find('.page-item').has('[data-post-id="' + post_id + '"]').fadeIn('fast', function() {
      $panel = $selector.find('#banana_splash_' + post_type);
      $panel.find('.pages-selector-empty').fadeOut('fast');
    });

    $item.fadeOut('slow', function() {
      if ( $item.siblings('.page-item:visible').length === 0 ) {
        $item.siblings('.pages-selector-empty').fadeIn('fast');
      }
      $item.remove();
    });
  });

  $selector.find('input:checkbox').each(function() {
    $(this).prop('checked', false);
  });
});