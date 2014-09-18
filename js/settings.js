jQuery(function($) {
  var $wrapper = $('.banana-splash-admin-wrapper'),
      $radio_buttons = $wrapper.find('.radio-buttons'),
      $pages_selector = $wrapper.find('.pages-selector-widget'),
      $selector_tree = $pages_selector.find('.pages-selector'),
      $selected = $pages_selector.find('.pages-selected'),
      $items = $selector_tree.find('.page-item');

  $radio_buttons.buttonset();
  $selector_tree.tabs();

  $selector_tree.find('.page-item').on('click', 'input:checkbox', function() {
    var $checkbox = $(this),
        $item = $checkbox.closest('.page-item'),
        post_id = $checkbox.data('postId'),
        $cloned, new_id;

    if ($item.is(':animated')) {
      return false;
    }

    $cloned = $item.clone().hide();
    $item.fadeOut('slow');

    new_id = $checkbox.attr('id') + '_selected';
    $cloned.find('input:checkbox').attr('id', new_id)
    $cloned.find('label').attr('for', new_id);

    $cloned.appendTo( $selected ).fadeIn('slow');
  });

  $selected.on('click', 'input:checkbox', function() {
    var $checkbox = $(this),
        $item = $checkbox.closest('.page-item'),
        post_id = $checkbox.data('postId');
    
    if ($item.is(':animated')) {
      return false;
    }

    $selector_tree.find('.page-item').has('[data-post-id="' + post_id + '"]').fadeIn('slow');
    $item.fadeOut('slow', function() { $(this).remove(); });
  });

});