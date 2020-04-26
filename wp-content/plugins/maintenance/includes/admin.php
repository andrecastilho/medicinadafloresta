<?php
add_action('admin_menu', 'mtnc_admin_setup');

function mtnc_admin_setup()
{
  global  $mtnc_variable;
  $mtnc_variable->options_page = add_menu_page(__('Maintenance', 'maintenance'), __('Maintenance', 'maintenance'), 'manage_options', 'maintenance', 'mtnc_manage_options', MTNC_URI . '/images/icon-small.png');

  add_action('admin_init', 'mtnc_register_settings');
  add_action("admin_head-{$mtnc_variable->options_page}", 'mtnc_metaboxes_scripts');
  add_action("admin_print_styles-{$mtnc_variable->options_page}", 'mtnc_admin_print_custom_styles');
  add_action("load-{$mtnc_variable->options_page}", 'mtnc_page_add_meta_boxes');
  add_action('admin_enqueue_scripts', 'mtnc_load_later_scripts', 1);
  add_action('admin_enqueue_scripts', 'mtnc_codemirror_enqueue_scripts');
}

function mtnc_page_add_meta_boxes()
{
  global  $mtnc_variable;
  do_action('add_mt_meta_boxes', $mtnc_variable->options_page);
}

function mtnc_register_settings()
{
  if (!empty($_POST['lib_options']) && check_admin_referer('mtnc_edit_post', 'mtnc_nonce')) {
    if (!isset($_POST['lib_options']['state'])) {
      $_POST['lib_options']['state'] = 0;
    } else {
      $_POST['lib_options']['state'] = 1;
    }

    if (isset($_POST['lib_options']['htmlcss'])) {
      $_POST['lib_options']['htmlcss'] = wp_kses_stripslashes($_POST['lib_options']['htmlcss']);  // Allowed all tags as for WYSIWYG post content
    }

    if (isset($_POST['lib_options'])) {
      $lib_options = sanitize_post(wp_unslash($_POST['lib_options']), 'db');
      $lib_options['default_settings'] = false;
      update_option('maintenance_options', $lib_options);
      MTNC::mtnc_clear_cache();
    }
  }
}

function mtnc_admin_print_custom_styles()
{
  if (function_exists('wp_enqueue_media')) {
    wp_enqueue_media();
  } else {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
  }

  wp_enqueue_script('common');
  wp_enqueue_script('wp-lists');
  wp_enqueue_script('postbox');

  wp_enqueue_style('arvo', '//fonts.googleapis.com/css?family=Open+Sans:400,300,600,700|Arvo:400,400italic,700,700italic');
  wp_enqueue_style('wp-color-picker');

  wp_enqueue_script('uploads_', MTNC_URI . 'js/uploads_.min.js', 'jquery', filemtime(MTNC_DIR . 'js/uploads_.min.js'), '');
  wp_register_script('mtnc', MTNC_URI . 'js/init.js', array('wp-color-picker'), filemtime(MTNC_DIR . 'js/init.js'), true);
  wp_localize_script('mtnc', 'mtnc', array('path' => MTNC_URI,
    'weglot_install_url' => add_query_arg(array('action' => 'mtnc_install_weglot', 'rnd' => rand()), admin_url('admin.php')),
    'weglot_dialog_upsell_title' => '<img alt="Weglot" title="Weglot" src="' . MTNC_URI . 'images/weglot-logo-white.png' . '">',
    'amelia_install_url' => add_query_arg(array('action' => 'mtnc_install_amelia', 'rnd' => rand()), admin_url('admin.php')),
    'amelia_dialog_upsell_title' => '<img alt="Amelia Booking" title="Amelia Booking" src="' . MTNC_URI . 'images/amelia-logo-white.png' . '">',
    'mailoptin_dialog_upsell_title' => '<img alt="MailOptin" title="MailOptin" src="' . MTNC_URI . 'images/mailoptin-logo-white.png' . '">',
    'mailoptin_install_url' => add_query_arg(array('action' => 'mtnc_install_mailoptin'), admin_url('admin.php'))));
  wp_enqueue_script('mtnc');
  wp_enqueue_style('mtnc', MTNC_URI . 'css/admin.css', '', filemtime(MTNC_DIR . 'css/admin.css'));

  wp_enqueue_style('wp-jquery-ui-dialog');
  wp_enqueue_script('jquery-ui-dialog');
}

function mtnc_codemirror_enqueue_scripts($hook)
{
  if ('toplevel_page_maintenance' !== $hook) {
    return;
  }

  $cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/css'));
  wp_localize_script('jquery', 'cm_settings', $cm_settings);

  wp_enqueue_script('wp-theme-plugin-editor');
  wp_enqueue_style('wp-codemirror');
}

function mtnc_load_later_scripts($hook)
{
  if ($hook !== 'toplevel_page_maintenance') {
    return;
  }

  // fix a bug with WooCommerce 3.2.2 .
  wp_deregister_script('select2');
  wp_deregister_style('select2');
  wp_dequeue_script('select2');
  wp_dequeue_style('select2');
  wp_enqueue_script('select2', MTNC_URI . 'js/select2/select2.min.js', 'jquery', filemtime(MTNC_DIR . 'js/select2/select2.min.js'), '');
  wp_enqueue_style('select2', MTNC_URI . 'js/select2/select2.css', '', filemtime(MTNC_DIR . 'js/select2/select2.css'));

  // fix for aggressive plugins
  wp_dequeue_style('uiStyleSheet');
  wp_dequeue_style('wpcufpnAdmin');
  wp_dequeue_style('unifStyleSheet');
  wp_dequeue_style('wpcufpn_codemirror');
  wp_dequeue_style('wpcufpn_codemirrorTheme');
  wp_dequeue_style('collapse-admin-css');
  wp_dequeue_style('jquery-ui-css');
  wp_dequeue_style('tribe-common-admin');
  wp_dequeue_style('file-manager__jquery-ui-css');
  wp_dequeue_style('file-manager__jquery-ui-css-theme');
  wp_dequeue_style('wpmegmaps-jqueryui');
  wp_dequeue_style('wp-botwatch-css');
}

function mtnc_manage_options()
{
  mtnc_generate_plugin_page();
}

function mtnc_generate_plugin_page()
{
  global  $mtnc_variable;
  $mt_option = mtnc_get_plugin_options(true);
  ?>
  <div id="maintenance-options" class="wrap">
    <form method="post" action="" enctype="multipart/form-data" name="options-form">
      <?php wp_nonce_field('mtnc_edit_post', 'mtnc_nonce'); ?>
      <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
      <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
      <div class="postbox-container header-container column-1 normal">
        <h1><?php esc_html_e('Maintenance', 'maintenance'); ?><input type="checkbox" id="state" name="lib_options[state]" <?php checked($mt_option['state'], 1); ?> /> <p class="submit"><a href="<?php echo home_url( '?maintenance-preview'); ?>" target="_blank" class="button">Preview</a> &nbsp;&nbsp; <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p></h1>

      </div>
      <div class="clear"></div>
      <div id="poststuff">
        <div class="metabox-holder">
          <div id="all-fileds" class="postbox-container column-1 normal">

            <?php do_meta_boxes($mtnc_variable->options_page, 'normal', null); ?>
            <?php do_meta_boxes($mtnc_variable->options_page, 'advanced', null); ?>

          </div>

          <div id="promo" class="postbox-container column-2 normal">
            <?php do_meta_boxes($mtnc_variable->options_page, 'side', null); ?>
          </div>

        </div>
      </div>
    </form>
  </div>
<?php
// mailoptin install dialog
echo '<div id="mailoptin-upsell-dialog" style="display: none;" title="MailOptin"><span class="ui-helper-hidden-accessible"><input type="text"/></span>';
echo '<div style="padding: 20px; font-size: 14px;">';
echo '<ul class="mtnc-list">';
echo '<li>completely free plugin that integrates with Maintenance</li>';
echo '<li>instantly start collecting leads &amp; subscribers</li>';
echo '<li>use an optin form on the bottom of Maintenance content</li>';
echo '<li>or try a popup/lightbox optin</li>';
echo '<li>easily connect with Mailchimp and other leading autoresponder services</li>';
echo '<li>completely customize the look &amp; feel of the optin form</li>';
echo '</ul>';
echo '<p class="upsell-footer"><a class="button button-primary" id="install-mailoptin">Install &amp; activate MailOptin to start collecting leads</a></p>';
echo '</div>';
echo '</div>';
// mailoptin install dialog

// weglot install dialog
echo '<div id="weglot-upsell-dialog" style="display: none;" title="Weglot"><span class="ui-helper-hidden-accessible"><input type="text"/></span>';
echo '<div style="padding: 20px; font-size: 15px;">';
echo '<ul class="mtnc-list">';
echo '<li>Best-rated WordPress multilingual plugin</li>';
echo '<li>Simple 5-minute set-up. No coding required</li>';
echo '<li>Accelerated translation management: Machine & human translations with access to professional translators</li>';
echo '<li>Compatible with any WordPress theme or plugin</li>';
echo '<li>Optimized for multilingual SEO</li>';
echo '<li>10-day Free trial and free plan available</li>';
echo '</ul>';
echo '<p class="upsell-footer"><a class="button button-primary" id="install-weglot">Install &amp; activate Weglot to make your site multilingual</a></p>';
echo '</div>';
echo '</div>';
// weglot install dialog

// amelia install dialog
echo '<div id="amelia-upsell-dialog" style="display: none;" title="Amelia Booking"><span class="ui-helper-hidden-accessible"><input type="text"/></span>';
echo '<div style="padding: 0 20px 20px 20px; font-size: 15px;">';
echo '<ul class="mtnc-list">';
echo '<li>Start booking events and appointements for free</li>';
echo '<li>A convenient Calendar view on the back-end, giving a full overview on all the appointments and the statuses</li>';
echo '<li>Flexible Appointment Management page, with an option to add appointments by your staff from the WordPress admin dashboard</li>';
echo '<li>All key numbers are available in the Dashboard</li>';
echo '<li>Email notifications to the customer and yourself when the appointment status changes</li>';
echo '<li>Free and used by +10,000 sites</li>';
echo '</ul>';
echo '<p class="upsell-footer textcenter"><br><a class="button button-primary" id="install-amelia">Install &amp; activate Amelia Booking to enable bookings calendar</a></p>';
echo '</div>';
echo '</div>';
// amelia install dialog
}
