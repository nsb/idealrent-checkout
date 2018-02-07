<?php

class IdealRentServiceListWidget extends WP_Widget {

  public function __construct() {
    parent::__construct(
      "idealrent-servicelist-widget",
      "IdealRent ServiceList Widget"
    );
  }

  public function widget($args, $instance) {
    IdealRentCheckout::enqueue_scripts();

    $types = array_map(function($s) { return trim(mb_convert_case($s, MB_CASE_TITLE)); }, explode("|", $instance["types"]));
    $services = array();
    foreach(explode("\n", $instance["services"]) as $service) {
      $split = explode("|", $service);
      $typelist = array_map("trim", explode(",", $split[0]));
      $services[trim($split[1])] = $typelist;
    }

    $action = get_option("ir-checkout-stripe-booking-form-page", "");
    if (empty($action)) {
      $action = trim(trim($instance["action"]), "/");
    }
    if (!empty($action)) {
      $action = "/{$action}";
    }

    ?>
    <div id="service_headers">
    <?php $first = true; foreach($types as $index => $type): $id = $index + 1; ?>
      <h4<?php if ($first) { $first = false; echo " class='active'"; } ?>><span data-target="#service_block_<?php echo $id; ?>"><?php echo $type; ?></span>
        <form method="post" action="<?php echo $action; ?>">
          <button name="idealrent_checkout_service" value="<?php echo $id; ?>">
            Bestil nu
          </button>
        </form>
      </h4>
    <?php endforeach; ?>
    </div>
    <div id="service_blocks">
      <?php $first = true;  foreach($types as $index => $type): $id = $index + 1; ?>
        <div<?php if ($first) { $first = false; echo " class='active'"; } ?> id="service_block_<?php echo $id; ?>">
          <?php foreach($services as $name => $typelist): ?>
            <div class="<?php if (in_array($id, $typelist)) echo "provided"; ?>">
              <?php echo $name; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
  }

  public function form($instance) {
    $defaults = array(
      "types" => "",
      "services" => "",
    );
    $instance = wp_parse_args((array)$instance, $defaults);
    ?>
    <p>
      <label for="<?php echo $this->get_field_id("types"); ?>">Service kategorier</label>
      <input class="widefat" id="<?php echo $this->get_field_id("types"); ?>" name="<?php echo $this->get_field_name("types"); ?>" value="<?php echo $instance["types"]; ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("services"); ?>">Services</label><br/>
      <textarea rows="20" style="width: 100%" id="<?php echo $this->get_field_id("services"); ?>" name="<?php echo $this->get_field_name("services"); ?>"><?php echo $instance["services"]; ?></textarea>
    </p>
    <?php
  }
}

