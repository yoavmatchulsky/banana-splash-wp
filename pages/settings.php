<?php

(defined( 'ABSPATH' ) && $this instanceof WPBananaSplash) or die( 'buawwhhaawwwhhhaa' );

?>

<div class="banana-splash-admin-wrapper">
  <h2 class="logo-wrapper"><img src="<?php echo $this->plugins_url; ?>/images/banana-logo.png" class="banana-logo" /></h2>

  <form action='' method='post'>
    <?php wp_nonce_field( 'banana-splash-settings', 'checking-ma-validicity' ); ?>
    <ul>
      <li><section>
        <img src="<?php echo $this->plugins_url; ?>/images/banana-juggling.png" class="banana-juggling"/>
        
        <h4><label for="banana_splash_settings[code]"><?php _e( 'Enter the implementation code right down here:', 'banana_splash' ); ?></label></h4>
        <textarea rows='4' name='banana_splash_settings[code]'<?php if ($error) { echo ' class="error"'; } ?>><?php echo $this->options['code']; ?></textarea>

      </section></li>

      <li><section>
        <h4><?php _e( 'What do you want to splash?', 'banana_splash' ); ?></h4>

        <?php echo $this->pages_selector->buttons(); ?>

        <?php echo $this->pages_selector->widget(); ?>

        <?php echo $this->pages_selector->show_on_front_checkbox(); ?>

      </section></li>
    </ul>
    <input type="submit" value="<?php _e( 'Save!!!', 'banana_splash' ); ?>" />
  </form>
</div>