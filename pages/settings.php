<?php

(defined( 'ABSPATH' ) && $this instanceof WPBananaSplash) or die( 'buawwhhaawwwhhhaa' );

$plugins_url = plugins_url('../', __FILE__);

?>

<div class="banana-splash-admin-wrapper">
  <h2 class="logo-wrapper"><img src="<?php echo $plugins_url; ?>/images/banana-logo.png" class="banana-logo" /></h2>

  <form action='' method='post'>
    <?php wp_nonce_field( 'banana-splash-settings', 'checking-ma-validicity' ); ?>
    <ul>
      <li><section>
        <img src="<?php echo $plugins_url; ?>/images/banana-juggling.png" class="banana-juggling"/>
        
        <h4><label for="banana_splash_settings[code]"><?php _e( 'Enter the implementation code right down here:', 'banana_splash' ); ?></label></h4>
        <textarea rows='4' name='banana_splash_settings[code]'<?php if ($error) { echo ' class="error"'; } ?>><?php echo $this->options['code']; ?></textarea>

      </section></li>

      <li><section>
        <h4><label for="banana_splash_settings[pages]"><?php _e( 'What do you want to splash?', 'banana_splash' ); ?></label></h4>

        <div class="radio-buttons">
          <input type="radio" name="banana_splash_settings[pages]" id="banana_splash_settings_pages_all" value="all" <?php if ($this->pages === 'all') echo 'checked="checked"'; ?>/>
              <label for="banana_splash_settings_pages_all"><?php _e( 'All Pages', 'banana_splash' ); ?></label>
          <input type="radio" name="banana_splash_settings[pages]" id="banana_splash_settings_pages_specific" value="specific" <?php if (is_array($this->pages)) echo 'checked="checked"'; ?>/>
            <label for="banana_splash_settings_pages_specific"><?php _e( 'Select specific pages', 'banana_splash' ); ?></label>
        </div>

        <div class="pages-selector-widget">
          <div class="pages-selector pages-selector-panel">
            <?php echo $this->pages_selector(); ?>
          </div>

          <div class="pages-selected pages-selector-panel"></div>
        </div>

      </section></li>
    </ul>
    <input type="submit" value="<?php _e( 'Activate!', 'banana_splash' ); ?>" />
  </form>
</div>