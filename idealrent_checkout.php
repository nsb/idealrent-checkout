<?php
/**
 * Plugin Name: IdealRent Checkout
 * Description: IdealRent Responsive Checkout Form
 * Author: Redcape IVS
 * Version: 1.1
 */

if (!defined("ABSPATH")) {
  die("Not to be accessed directly");
}

class IdealRentCheckout {
  function __construct() {
    add_action("widgets_init", array($this, "widgets_init"));
    add_action("template_include", array($this, "template_include"));
    add_shortcode("widget", array($this, "widget"));
    add_shortcode("sidebar", array($this, "sidebar"));

    if (is_admin()) {
      add_action("admin_menu", array($this, "admin_menu"));
      add_action("admin_init", array($this, "admin_init"));
    }
  }

  public function sidebar($args) {
    ob_start();
    dynamic_sidebar($args["name"]);
    return ob_get_clean();
  }

  public function widget($args) {
    global $wp_widget_factory;

    if (!is_a($wp_widget_factory->widgets[$args["name"]], "WP_Widget")) return false;
    if (isset($args["cap"]) && $args["cap"] == "anonymous" && is_user_logged_in()) return false;
    if (isset($args["cap"]) && $args["cap"] != "anonymous" && !current_user_can($args["cap"])) return false;

    $args += array(
      "widget_id" => "arbitrary-instance-widget",
      "before_widget" => "",
      "after_widget" => "",
      "before_title" => "",
      "after_title" => ""
    );

    ob_start();
    the_widget($args["name"], array(), $args);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }

  public function template_include($template) {
    global $post;
    if ($post) {
      $url = trim(trim(wp_make_link_relative(get_permalink($post))), "/");
      $booking = trim(trim(get_option("ir-checkout-stripe-booking-form-page")), "/");
      if ($url == $booking && !empty($booking)) {
        $template = dirname(__FILE__)."/templates/ir-checkout-fullpage.php";
      }
    }
    return $template;
  }

  public function admin_menu() {
    add_options_page("IdealRent checkout indstillinger", "IR checkout", "manage_options", "ir-checkout-settings", array($this, "options_page"));
  }

  public function admin_init() {
    add_settings_section("ir-checkout-settings-section", "Basis indstillinger", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-base-price");
    register_setting("ir-checkout-settings", "ir-checkout-plus-price");
    register_setting("ir-checkout-settings", "ir-checkout-short-term-days");
    register_setting("ir-checkout-settings", "ir-checkout-short-term-price");
    register_setting("ir-checkout-settings", "ir-checkout-stripe-key");
    register_setting("ir-checkout-settings", "ir-checkout-stripe-secret-key");
    register_setting("ir-checkout-settings", "ir-checkout-stripe-mail");
    register_setting("ir-checkout-settings", "ir-checkout-stripe-booking-page");
    register_setting("ir-checkout-settings", "ir-checkout-stripe-booking-form-page");

    add_settings_field("ir-checkout-base-price", "Angiv basis pris", array($this, "settings_base_price"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-plus-price", "Hovedrengøring pris", array($this, "settings_plus_price"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-short-term-days", "Kort varsel grænse (dage)", array($this, "settings_short_term_days"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-short-term-price", "Kort varsel pris", array($this, "settings_short_term_price"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-stripe-key", "Stripe key", array($this, "settings_stripe_key"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-stripe-secret-key", "Stripe secret key", array($this, "settings_stripe_secret_key"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-stripe-mail", "Mailboks", array($this, "settings_stripe_mail"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-stripe-booking-page", "Bekræftelsesside", array($this, "settings_stripe_booking_page"), "ir-checkout-settings", "ir-checkout-settings-section");
    add_settings_field("ir-checkout-stripe-booking-form-page", "Bookingside", array($this, "settings_stripe_booking_form_page"), "ir-checkout-settings", "ir-checkout-settings-section");

    add_settings_section("ir-checkout-settings-section-frequency", "Aftale rabatter (i procent)", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-frequency-weekly");
    register_setting("ir-checkout-settings", "ir-checkout-frequency-biweekly");
    register_setting("ir-checkout-settings", "ir-checkout-frequency-monthly");

    add_settings_field("ir-checkout-frequency-weekly", "Ugentlig", array($this, "settings_frequency_weekly"), "ir-checkout-settings", "ir-checkout-settings-section-frequency");
    add_settings_field("ir-checkout-frequency-biweekly", "Hver anden uge", array($this, "settings_frequency_biweekly"), "ir-checkout-settings", "ir-checkout-settings-section-frequency");
    add_settings_field("ir-checkout-frequency-monthly", "Månedlig", array($this, "settings_frequency_monthly"), "ir-checkout-settings", "ir-checkout-settings-section-frequency");

    add_settings_section("ir-checkout-settings-section-extra", "Extra services", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-extra-oven");
    register_setting("ir-checkout-settings", "ir-checkout-extra-fridge");
    register_setting("ir-checkout-settings", "ir-checkout-extra-shirts");
    register_setting("ir-checkout-settings", "ir-checkout-extra-linen");

    add_settings_field("ir-checkout-extra-oven", "Ovn pris", array($this, "settings_extra_oven"), "ir-checkout-settings", "ir-checkout-settings-section-extra");
    add_settings_field("ir-checkout-extra-fridge", "Køleskab pris", array($this, "settings_extra_fridge"), "ir-checkout-settings", "ir-checkout-settings-section-extra");
    add_settings_field("ir-checkout-extra-shirts", "Strygning pris", array($this, "settings_extra_shirts"), "ir-checkout-settings", "ir-checkout-settings-section-extra");
    add_settings_field("ir-checkout-extra-linen", "Sengetøj pris", array($this, "settings_extra_linen"), "ir-checkout-settings", "ir-checkout-settings-section-extra");

    add_settings_section("ir-checkout-settings-section-bathroom", "Badeværelse priser", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-bathroom-2-price");
    register_setting("ir-checkout-settings", "ir-checkout-bathroom-3-price");
    register_setting("ir-checkout-settings", "ir-checkout-bathroom-4-price");
    register_setting("ir-checkout-settings", "ir-checkout-bathroom-5-price");

    add_settings_field("ir-checkout-bathroom-2-price", "Pris 2 badeværelser", array($this, "settings_bathroom_2_price"), "ir-checkout-settings", "ir-checkout-settings-section-bathroom");
    add_settings_field("ir-checkout-bathroom-3-price", "Pris 3 badeværelser", array($this, "settings_bathroom_3_price"), "ir-checkout-settings", "ir-checkout-settings-section-bathroom");
    add_settings_field("ir-checkout-bathroom-4-price", "Pris 4 badeværelser", array($this, "settings_bathroom_4_price"), "ir-checkout-settings", "ir-checkout-settings-section-bathroom");
    add_settings_field("ir-checkout-bathroom-5-price", "Pris 5 badeværelser", array($this, "settings_bathroom_5_price"), "ir-checkout-settings", "ir-checkout-settings-section-bathroom");

    add_settings_section("ir-checkout-settings-section-size", "Kvm priser", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-size-2-price");
    register_setting("ir-checkout-settings", "ir-checkout-size-3-price");
    register_setting("ir-checkout-settings", "ir-checkout-size-4-price");
    register_setting("ir-checkout-settings", "ir-checkout-size-5-price");

    add_settings_field("ir-checkout-size-2-price", "Pris 70-100kvm", array($this, "settings_size_2_price"), "ir-checkout-settings", "ir-checkout-settings-section-size");
    add_settings_field("ir-checkout-size-3-price", "Pris 100-130kvm", array($this, "settings_size_3_price"), "ir-checkout-settings", "ir-checkout-settings-section-size");
    add_settings_field("ir-checkout-size-4-price", "Pris 130-160kvm", array($this, "settings_size_4_price"), "ir-checkout-settings", "ir-checkout-settings-section-size");
    add_settings_field("ir-checkout-size-5-price", "Pris over 160kvm", array($this, "settings_size_5_price"), "ir-checkout-settings", "ir-checkout-settings-section-size");

    add_settings_section("ir-checkout-settings-section-weekdays", "Ugedage priser", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-monday-price");
    register_setting("ir-checkout-settings", "ir-checkout-tuesday-price");
    register_setting("ir-checkout-settings", "ir-checkout-wednesday-price");
    register_setting("ir-checkout-settings", "ir-checkout-thursday-price");
    register_setting("ir-checkout-settings", "ir-checkout-friday-price");
    register_setting("ir-checkout-settings", "ir-checkout-saturday-price");
    register_setting("ir-checkout-settings", "ir-checkout-sunday-price");

    add_settings_field("ir-checkout-monday-price", "Angiv mandag pris", array($this, "settings_monday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-tuesday-price", "Angiv tirsdag pris", array($this, "settings_tuesday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-wednesday-price", "Angiv onsdag pris", array($this, "settings_wednesday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-thursday-price", "Angiv torsdag pris", array($this, "settings_thursday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-friday-price", "Angiv fredag pris", array($this, "settings_friday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-saturday-price", "Angiv lørdag pris", array($this, "settings_saturday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");
    add_settings_field("ir-checkout-sunday-price", "Angiv søndag pris", array($this, "settings_sunday_price"), "ir-checkout-settings", "ir-checkout-settings-section-weekdays");

    add_settings_section("ir-checkout-settings-section-hours", "Tidspunkt priser", array($this, "settings_section"), "ir-checkout-settings");

    register_setting("ir-checkout-settings", "ir-checkout-6-price");
    register_setting("ir-checkout-settings", "ir-checkout-7-price");
    register_setting("ir-checkout-settings", "ir-checkout-8-price");
    register_setting("ir-checkout-settings", "ir-checkout-9-price");
    register_setting("ir-checkout-settings", "ir-checkout-10-price");
    register_setting("ir-checkout-settings", "ir-checkout-11-price");
    register_setting("ir-checkout-settings", "ir-checkout-12-price");
    register_setting("ir-checkout-settings", "ir-checkout-13-price");
    register_setting("ir-checkout-settings", "ir-checkout-14-price");
    register_setting("ir-checkout-settings", "ir-checkout-15-price");
    register_setting("ir-checkout-settings", "ir-checkout-16-price");
    register_setting("ir-checkout-settings", "ir-checkout-17-price");
    register_setting("ir-checkout-settings", "ir-checkout-18-price");
    register_setting("ir-checkout-settings", "ir-checkout-19-price");

    add_settings_field("ir-checkout-6-price", "Angiv kl 6 pris", array($this, "settings_6_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-7-price", "Angiv kl 7 pris", array($this, "settings_7_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-8-price", "Angiv kl 8 pris", array($this, "settings_8_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-9-price", "Angiv kl 9 pris", array($this, "settings_9_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-10-price", "Angiv kl 10 pris", array($this, "settings_10_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-11-price", "Angiv kl 11 pris", array($this, "settings_11_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-12-price", "Angiv kl 12 pris", array($this, "settings_12_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-13-price", "Angiv kl 13 pris", array($this, "settings_13_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-14-price", "Angiv kl 14 pris", array($this, "settings_14_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-15-price", "Angiv kl 15 pris", array($this, "settings_15_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-16-price", "Angiv kl 16 pris", array($this, "settings_16_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-17-price", "Angiv kl 17 pris", array($this, "settings_17_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-18-price", "Angiv kl 18 pris", array($this, "settings_18_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
    add_settings_field("ir-checkout-19-price", "Angiv kl 19 pris", array($this, "settings_19_price"), "ir-checkout-settings", "ir-checkout-settings-section-hours");
  }

  public function settings_frequency_weekly($args) {
    $val = get_option("ir-checkout-frequency-weekly");
    echo "<input value='{$val}' type='number' name='ir-checkout-frequency-weekly' id='ir-checkout-frequency-weekly' />";
  }
  public function settings_frequency_biweekly($args) {
    $val = get_option("ir-checkout-frequency-biweekly");
    echo "<input value='{$val}' type='number' name='ir-checkout-frequency-biweekly' id='ir-checkout-frequency-biweekly' />";
  }
  public function settings_frequency_monthly($args) {
    $val = get_option("ir-checkout-frequency-monthly");
    echo "<input value='{$val}' type='number' name='ir-checkout-frequency-monthly' id='ir-checkout-frequency-monthly' />";
  }

  public function settings_extra_oven($args) {
    $val = get_option("ir-checkout-extra-oven");
    echo "<input value='{$val}' type='number' name='ir-checkout-extra-oven' id='ir-checkout-extra-oven' />";
  }
  public function settings_extra_fridge($args) {
    $val = get_option("ir-checkout-extra-fridge");
    echo "<input value='{$val}' type='number' name='ir-checkout-extra-fridge' id='ir-checkout-extra-fridge' />";
  }
  public function settings_extra_shirts($args) {
    $val = get_option("ir-checkout-extra-shirts");
    echo "<input value='{$val}' type='number' name='ir-checkout-extra-shirts' id='ir-checkout-extra-shirts' />";
  }
  public function settings_extra_linen($args) {
    $val = get_option("ir-checkout-extra-linen");
    echo "<input value='{$val}' type='number' name='ir-checkout-extra-linen' id='ir-checkout-extra-linen' />";
  }

  public function settings_size_2_price($args) {
    $val = get_option("ir-checkout-size-2-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-size-2-price' id='ir-checkout-size-2-price' />";
  }
  public function settings_size_3_price($args) {
    $val = get_option("ir-checkout-size-3-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-size-3-price' id='ir-checkout-size-3-price' />";
  }
  public function settings_size_4_price($args) {
    $val = get_option("ir-checkout-size-4-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-size-4-price' id='ir-checkout-size-4-price' />";
  }
  public function settings_size_5_price($args) {
    $val = get_option("ir-checkout-size-5-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-size-5-price' id='ir-checkout-size-5-price' />";
  }

  public function settings_bathroom_2_price($args) {
    $val = get_option("ir-checkout-bathroom-2-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-bathroom-2-price' id='ir-checkout-bathroom-2-price' />";
  }
  public function settings_bathroom_3_price($args) {
    $val = get_option("ir-checkout-bathroom-3-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-bathroom-3-price' id='ir-checkout-bathroom-3-price' />";
  }
  public function settings_bathroom_4_price($args) {
    $val = get_option("ir-checkout-bathroom-4-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-bathroom-4-price' id='ir-checkout-bathroom-4-price' />";
  }
  public function settings_bathroom_5_price($args) {
    $val = get_option("ir-checkout-bathroom-5-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-bathroom-5-price' id='ir-checkout-bathroom-5-price' />";
  }

  public function settings_6_price($args) {
    $val = get_option("ir-checkout-6-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-6-price' id='ir-checkout-6-price' />";
  }
  public function settings_7_price($args) {
    $val = get_option("ir-checkout-7-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-7-price' id='ir-checkout-7-price' />";
  }
  public function settings_8_price($args) {
    $val = get_option("ir-checkout-8-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-8-price' id='ir-checkout-8-price' />";
  }
  public function settings_9_price($args) {
    $val = get_option("ir-checkout-9-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-9-price' id='ir-checkout-9-price' />";
  }
  public function settings_10_price($args) {
    $val = get_option("ir-checkout-10-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-10-price' id='ir-checkout-10-price' />";
  }
  public function settings_11_price($args) {
    $val = get_option("ir-checkout-11-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-11-price' id='ir-checkout-11-price' />";
  }
  public function settings_12_price($args) {
    $val = get_option("ir-checkout-12-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-12-price' id='ir-checkout-12-price' />";
  }
  public function settings_13_price($args) {
    $val = get_option("ir-checkout-13-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-13-price' id='ir-checkout-13-price' />";
  }
  public function settings_14_price($args) {
    $val = get_option("ir-checkout-14-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-14-price' id='ir-checkout-14-price' />";
  }
  public function settings_15_price($args) {
    $val = get_option("ir-checkout-15-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-15-price' id='ir-checkout-15-price' />";
  }
  public function settings_16_price($args) {
    $val = get_option("ir-checkout-16-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-16-price' id='ir-checkout-16-price' />";
  }
  public function settings_17_price($args) {
    $val = get_option("ir-checkout-17-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-17-price' id='ir-checkout-17-price' />";
  }
  public function settings_18_price($args) {
    $val = get_option("ir-checkout-18-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-18-price' id='ir-checkout-18-price' />";
  }
  public function settings_19_price($args) {
    $val = get_option("ir-checkout-19-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-19-price' id='ir-checkout-19-price' />";
  }

  public function settings_monday_price($args) {
    $val = get_option("ir-checkout-monday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-monday-price' id='ir-checkout-monday-price' />";
  }
  public function settings_tuesday_price($args) {
    $val = get_option("ir-checkout-tuesday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-tuesday-price' id='ir-checkout-tuesday-price' />";
  }
  public function settings_wednesday_price($args) {
    $val = get_option("ir-checkout-wednesday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-wednesday-price' id='ir-checkout-wednesday-price' />";
  }
  public function settings_thursday_price($args) {
    $val = get_option("ir-checkout-thursday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-thursday-price' id='ir-checkout-thursday-price' />";
  }
  public function settings_friday_price($args) {
    $val = get_option("ir-checkout-friday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-friday-price' id='ir-checkout-friday-price' />";
  }
  public function settings_saturday_price($args) {
    $val = get_option("ir-checkout-saturday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-saturday-price' id='ir-checkout-saturday-price' />";
  }
  public function settings_sunday_price($args) {
    $val = get_option("ir-checkout-sunday-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-sunday-price' id='ir-checkout-sunday-price' />";
  }

  public function settings_section() {
  }

  public function settings_base_price($args) {
    $val = get_option("ir-checkout-base-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-base-price' id='ir-checkout-base-price' />";
  }
  public function settings_plus_price($args) {
    $val = get_option("ir-checkout-plus-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-plus-price' id='ir-checkout-plus-price' />";
  }
  public function settings_short_term_days($args) {
    $val = get_option("ir-checkout-short-term-days");
    echo "<input value='{$val}' type='number' name='ir-checkout-short-term-days' id='ir-checkout-short-term-days' />";
  }
  public function settings_short_term_price($args) {
    $val = get_option("ir-checkout-short-term-price");
    echo "<input value='{$val}' type='number' name='ir-checkout-short-term-price' id='ir-checkout-short-term-price' />";
  }
  public function settings_stripe_key($args) {
    $val = get_option("ir-checkout-stripe-key");
    echo "<input value='{$val}' type='text' name='ir-checkout-stripe-key' id='ir-checkout-stripe-key' />";
  }
  public function settings_stripe_secret_key($args) {
    $val = get_option("ir-checkout-stripe-secret-key");
    echo "<input value='{$val}' type='text' name='ir-checkout-stripe-secret-key' id='ir-checkout-stripe-secret-key' />";
  }
  public function settings_stripe_mail($args) {
    $val = get_option("ir-checkout-stripe-mail");
    echo "<input value='{$val}' type='text' name='ir-checkout-stripe-mail' id='ir-checkout-stripe-mail' />";
  }
  public function settings_stripe_booking_page($args) {
    $val = get_option("ir-checkout-stripe-booking-page");
    echo "<input value='{$val}' type='text' name='ir-checkout-stripe-booking-page' id='ir-checkout-stripe-booking-page' />";
  }

  public function settings_stripe_booking_form_page($args) {
    $val = get_option("ir-checkout-stripe-booking-form-page");
    echo "<input value='{$val}' type='text' name='ir-checkout-stripe-booking-form-page' id='ir-checkout-stripe-booking-form-page' />";
  }

  public function options_page() {
    if (!current_user_can("manage_options")) {
      wp_die(__("You do not have sufficient permissions to access this page."));
    }

    ?>
    <h2>IdealRent checkout indstillinger</h2>
    <form method="post" action="options.php">
      <?php
      settings_fields("ir-checkout-settings");
      do_settings_sections("ir-checkout-settings");
      submit_button();
      ?>
    </form>
    <?php
  }

  public function widgets_init() {
    register_sidebar(array(
      "name" => "Services sidebar",
      "id" => "Services sidebar",
      "description" => "Services sidebar",
      "before_widget" => "<section id='%1\$s' class='widget %2\$s'>",
      "after_widget" => "</section>",
      "before_title" => "<h2>",
      "after_title" => "</h2>",
    ));

    require_once dirname(__FILE__)."/widgets/widget.idealrent_checkout.php";
    register_widget("IdealRentCheckoutWidget");
    require_once dirname(__FILE__)."/widgets/widget.idealrent_servicelist.php";
    register_widget("IdealRentServiceListWidget");
  }

  public static function enqueue_scripts() {
    wp_enqueue_style("idealrent_checkout", plugins_url("assets/idealrent_checkout.css", __FILE__));
    wp_enqueue_style("flickity", plugins_url("assets/flickity.css", __FILE__));
    wp_enqueue_style("jqueryui", plugins_url("assets/jquery-ui.css", __FILE__));

    wp_enqueue_script("stripe", "https://js.stripe.com/v3/", array("jquery"));
    wp_enqueue_script("flickity", plugins_url("assets/flickity.pkgd.js", __FILE__), array("jquery"));
    wp_enqueue_script("jqueryui", plugins_url("assets/jquery-ui.js", __FILE__), array("jquery"));
    wp_enqueue_script("dawa", plugins_url("assets/dawa-autocomplete.js", __FILE__), array("jquery", "jqueryui"));
    wp_register_script("idealrent_checkout", plugins_url("assets/idealrent_checkout.js", __FILE__), array("jquery", "flickity", "dawa", "jqueryui", "stripe"));
    $values = array("stripekey" => get_option("ir-checkout-stripe-key"));
    wp_localize_script("idealrent_checkout", "IR_Checkout_settings", $values);
    wp_enqueue_script("idealrent_checkout");
  }
}

new IdealRentCheckout();