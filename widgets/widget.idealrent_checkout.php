<?php

class IdealRentCheckoutWidget extends WP_Widget {

  public function __construct() {
    parent::__construct(
      "idealrent-checkout-widget",
      "IdealRent Checkout Widget"
    );
  }

  public function widget($args, $instance) {
    IdealRentCheckout::enqueue_scripts();

    $action = get_option("ir-checkout-stripe-booking-form-page", "");
    if (empty($action)) {
      $action = trim(trim($instance["action"]), "/");
    }
    if (!empty($action)) {
      $action = "/{$action}";
    }
    if ($instance["layout"] == 0) {
      $this->layout_frontpage($action);
    } else if ($instance["layout"] == 1) {
      if ($this->check_post()) {
        $this->layout_receipt();
      } else {
        $this->layout_full($action);
      }
    }
  }

  private function sizes($layout) {
    $sizes = array(
      1 => "Op til 70m<sup>2</sup>",
      2 => "70 til 100m<sup>2</sup>",
      3 => "100 til 130m<sup>2</sup>",
      4 => "130 til 160m<sup>2</sup>",
      5 => "Over 160m<sup>2</sup>",
    );
    $default = isset($_REQUEST["idealrent_checkout_size"]) ? $_REQUEST["idealrent_checkout_size"] : 1;
    if ($layout == "frontpage"):
      ?>
      <button class="idealrent_checkout_form_dropdown"><?php echo $sizes[$default]; ?></button>
      <div>
      <?php $i = 0; foreach($sizes as $val => $label):
        $i++; $price = get_option("ir-checkout-size-{$i}-price", 0);
        ?>
        <label for="idealrent_checkout_size_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_size_<?php echo $val; ?>" name="idealrent_checkout_size" /><span><?php echo $label; ?></span></label>
      <?php endforeach; ?>
      </div>
      <?php
    elseif ($layout == "progress"):
      ?>
      <button data-section="1">
        <label class="value"><?php echo $sizes[$default]; ?></label>
        <label class="label">Størrelse</label>
      </button>
      <?php
    elseif ($layout == "full"):
      $i = 0; foreach($sizes as $val => $label):
      $i++; $price = get_option("ir-checkout-size-{$i}-price", 0);
      ?>
        <label<?php if ($val == $default) echo " class='active' "; ?> for="idealrent_checkout_size_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>"  data-label="<?php echo $label; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_size_<?php echo $val; ?>" name="idealrent_checkout_size" /><span><?php echo $label; ?></span></label>
      <?php
      endforeach;
    endif;

    return get_option("ir-checkout-size-{$default}-price", 0);
  }

  private function bathrooms($layout) {
    $bathrooms = array(
      1 => "1 badeværelse",
      2 => "2 badeværelser",
      3 => "3 badeværelser",
      4 => "4 badeværelser",
      5 => "5 badeværelser",
    );
    $default = isset($_REQUEST["idealrent_checkout_bathrooms"]) ? $_REQUEST["idealrent_checkout_bathrooms"] : 1;
    if ($layout == "frontpage"):
      ?>
      <button class="idealrent_checkout_form_dropdown"><?php echo $bathrooms[$default]; ?></button>
      <div>
      <?php $i = 0; foreach($bathrooms as $val => $label):
        $i++; $price = get_option("ir-checkout-bathroom-{$i}-price", 0);
        ?>
        <label for="idealrent_checkout_bathrooms_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_bathrooms_<?php echo $val; ?>" name="idealrent_checkout_bathrooms" /><span><?php echo $label; ?></span></label>
      <?php endforeach; ?>
      </div>
      <?php
    elseif ($layout == "progress"):
      ?>
      <button data-section="1">
        <label class="value"><?php echo $default; ?></label>
        <label class="label">Badeværelser</label>
      </button>
      <?php
    elseif ($layout == "full"):
      $i = 0; foreach($bathrooms as $val => $label):
      $i++; $price = get_option("ir-checkout-bathroom-{$i}-price", 0);
      ?>
        <label<?php if ($val == $default) echo " class='active' "; ?> for="idealrent_checkout_bathrooms_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>" data-label="<?php echo $val; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_bathrooms_<?php echo $val; ?>" name="idealrent_checkout_bathrooms" /><span><?php echo $val; ?></span></label>
      <?php
      endforeach;
    endif;

    return get_option("ir-checkout-bathroom-{$default}-price", 0);
  }

  private function service($layout) {
    $services = array(
      1 => "Standard",
      2 => "Hovedrengøring",
    );
    $default = isset($_REQUEST["idealrent_checkout_service"]) ? $_REQUEST["idealrent_checkout_service"] : 1;
    if ($layout == "frontpage"):
      ?>
      <button class="idealrent_checkout_form_dropdown" style="border-right: 0;"><?php echo $services[$default]; ?></button>
      <div>
        <?php foreach($services as $val => $label):
          $price = $val == 1 ? 0 : get_option("ir-checkout-plus-price", 0);
          ?>
          <label for="idealrent_checkout_service_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_service_<?php echo $val; ?>" name="idealrent_checkout_service" /><span><?php echo $label; ?></span></label>
        <?php endforeach; ?>
      </div>
      <?php
    elseif ($layout == "progress"):
      ?>
      <button data-section="1">
        <label class="value"><?php echo $services[$default]; ?></label>
        <label class="label">Service</label>
      </button>
      <?php
    elseif ($layout == "full"):
      foreach($services as $val => $label):
        $price = $val == 1 ? 0 : get_option("ir-checkout-plus-price", 0);
        ?>
        <label<?php if ($val == $default) echo " class='active' "; ?> for="idealrent_checkout_service_<?php echo $val; ?>"><input data-price="<?php echo $price; ?>" data-label="<?php echo $label; ?>" type="radio" value="<?php echo $val; ?>" <?php if ($val == $default) echo "checked='checked' "; ?>id="idealrent_checkout_service_<?php echo $val; ?>" name="idealrent_checkout_service" /><span><?php echo $label; ?></span></label>
      <?php
      endforeach;
    endif;

    return $default == 1 ? 0 : get_option("ir-checkout-plus-price", 0);
  }

  private function layout_frontpage($action) {
    ?>
    <form action="<?php echo $action; ?>" class="idealrent_checkout_form frontpage_layout" method="post">
      <div class="idealrent_checkout_form_section section_1">
        <div class="idealrent_checkout_form_element">
          <?php $this->sizes("frontpage"); ?>
        </div>
        <div class="idealrent_checkout_form_element">
          <?php $this->bathrooms("frontpage"); ?>
        </div>
        <div class="idealrent_checkout_form_element">
          <?php $this->service("frontpage"); ?>
        </div>
        <div class="idealrent_checkout_form_element">
          <button class="blue">Bestil fra <span data-price="<?php echo get_option("ir-checkout-base-price"); ?>" class="priceholder"><?php echo get_option("ir-checkout-base-price"); ?></span> kr.</button>
        </div>
      </div>
    </form>
    <?php
  }

  private function date($layout) {
    $shortd = get_option("ir-checkout-short-term-days", 0);
    $shortp = get_option("ir-checkout-short-term-price", 0);

    $dayindex = array(
      1 => get_option("ir-checkout-monday-price", 0),
      2 => get_option("ir-checkout-tuesday-price", 0),
      3 => get_option("ir-checkout-wednesday-price", 0),
      4 => get_option("ir-checkout-thursday-price", 0),
      5 => get_option("ir-checkout-friday-price", 0),
      6 => get_option("ir-checkout-saturday-price", 0),
      7 => get_option("ir-checkout-sunday-price", 0),
    );

    if ($layout == "full"): ?>
      <div class="idealrent_checkout_form_element" data-element="date">
        <div class="main-carousel">
          <?php
          $today = date("j");
          $nowstamp = strtotime(date("Y-m-d"));
          for ($i = 0; $i <= 11; $i++):
            $names = array(0, "Januar", "Februar", "Marts", "April", "Maj", "Juni", "Juli", "August", "September", "Oktober", "November", "December");
            $m = date("m", strtotime("+$i month"));
            $mname = $names[$m*1];
            $y = date("Y", strtotime("+$i month"));
            $daysinm = cal_days_in_month(CAL_GREGORIAN, $m, $y);
            ?>
            <div class="carousel-cell">
              <h4><?php echo "$mname - $y"; ?></h4>
              <div class="week header">
                <div><label class="notmobile">Mandag</label><label class="mobile">Man</label></div>
                <div><label class="notmobile">Tirsdag</label><label class="mobile">Tir</label></div>
                <div><label class="notmobile">Onsdag</label><label class="mobile">Ons</label></div>
                <div><label class="notmobile">Torsdag</label><label class="mobile">Tor</label></div>
                <div><label class="notmobile">Fredag</label><label class="mobile">Fre</label></div>
                <div><label class="notmobile">Lørdag</label><label class="mobile">Lør</label></div>
                <div><label class="notmobile">Søndag</label><label class="mobile">Søn</label></div>
              </div>
              <div class="week right">
                <?php
                for ($j = 1; $j <= $daysinm; $j ++):
                  $stamp = strtotime("$y-$m-$j");
                  if (date("N", $stamp) == 1 && $j > 1) {
                    echo "</div><div class='week'>";
                  }
                  $inactive = ($i == 0 && date("j", $stamp) <= $today) || date("N", $stamp) > 5;
                  $price = 0;
                  if (!$inactive) {
                    $price += $dayindex[date("N", $stamp)];
                    if ($shortd > 0) $price += $shortp;
                    $shortd--;
                  }
                  $disabled = $inactive ? " disabled" : "";
                  $labelclass = array();
                  if ($inactive) $labelclass[] = "greyed";
                  if ($price) $labelclass[] = "hasprice";
                  $labelclass = $labelclass ? " class='".implode(" ", $labelclass)."'" : "";
                  $value = $inactive ? "" : " value='".date("Y-m-d", $stamp)."'";
                  $label = date("j", $stamp).". ".$mname;
                  $pricespan = $price ? "<span class='dateprice'>+{$price},-</span>" : "";
                  $input = $inactive ? "" : "<input{$disabled}{$value} data-price='{$price}' data-label='{$label}' type='radio' name='idealrent_checkout_date' />";
                  echo "<div><label{$labelclass}>{$input}<span>".date("j", $stamp)."</span>{$pricespan}</label></div>";
                endfor;
                ?>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    <?php
    elseif ($layout == "progress"):
    ?>
      <button data-section="2">
        <label class="value"></label>
        <label class="label">Dato</label>
      </button>
    <?php
    endif;
  }

  private function hour($layout) {
    if ($layout == "full"): ?>
      <div class="idealrent_checkout_form_element" data-element="hour">
        <div class="scroller">
          <ul>
            <li>
              <label>Fleksibel<input data-label="Fleksibel" type="radio" name="idealrent_checkout_hour" /></label>
            </li>
            <?php
            for($i = 6; $i < 20; $i++) {
              $price = get_option("ir-checkout-{$i}-price", 0);
              $labelclass = $price ? " class='hasprice'" : "";
              echo "<li><label{$labelclass}><span>{$i}:00</span><span class='hourprice'>+{$price},-</span><input value='{$i}' data-price='{$price}' data-label='{$i}:00' type='radio' name='idealrent_checkout_hour' /></label></li>";
            }
            ?>
          </ul>
        </div>
      </div>
      <?php
    elseif ($layout == "progress"):
      ?>
      <button data-section="3">
        <label class="value"></label>
        <label class="label">Tidspunkt</label>
      </button>
      <?php
    endif;
  }

  private function address($layout) {
    if ($layout == "full"): ?>
      <div class="idealrent_checkout_form_element" data-element="address">
        <input type="hidden" name="idealrent_checkout_address_complete" id="idealrent_checkout_address_complete" value="" />
        <input placeholder="Angiv din fulde adresse" type="text" name="idealrent_checkout_address" id="idealrent_checkout_address" />
      </div>
      <?php
    elseif ($layout == "progress"):
      ?>
      <button data-section="4">
        <label class="value address"></label>
        <label class="label">Adresse</label>
      </button>
      <?php
    endif;
  }

  private function layout_progress() {
    $price = get_option("ir-checkout-base-price", 0);
    $base = $price;
    ?>
    <div class="idealrent_checkout_form_progress">
      <div class="complete" id="idealrent_checkout_form_progress_logo">
        <a href="/" rel="home"><span>IdealRent</span></a>
      </div>
      <div class="complete" id="idealrent_checkout_form_progress_size">
        <?php $price += $this->sizes("progress"); ?>
      </div>
      <div class="complete" id="idealrent_checkout_form_progress_bathrooms">
        <?php $price += $this->bathrooms("progress"); ?>
      </div>
      <div class="complete" id="idealrent_checkout_form_progress_service">
        <?php $price += $this->service("progress"); ?>
      </div>
      <div class="" id="idealrent_checkout_form_progress_date">
        <?php $this->date("progress"); ?>
      </div>
      <div class="" id="idealrent_checkout_form_progress_hour">
        <?php $this->hour("progress"); ?>
      </div>
      <div class="" id="idealrent_checkout_form_progress_address">
        <?php $this->address("progress"); ?>
      </div>
      <div class="active" id="idealrent_checkout_form_progress_price">
        <button>
          <span class="idealrent_price_bubble"></span>
          <label class="value"><span data-checkout-price="<?php echo $base; ?>" data-price="<?php echo $base; ?>" class="priceholder"><?php echo $price; ?></span>,-</label>
        </button>
      </div>
      <div class="active" id="idealrent_checkout_form_progress_mobile_drop">
        <span>›</span>
      </div>
    </div>
    <?php
  }

  private function layout_full($action) {
    ?>
    <form action="<?php echo $action; ?>" class="idealrent_checkout_form fullpage_layout" method="post">
      <?php $this->layout_progress(); ?>
      <div class="idealrent_checkout_form_block active">
        <div class="idealrent_checkout_form_section section_1">
          <h2>Bestil rengøring i dag</h2>
          <h4>Størrelse</h4>
          <div class="idealrent_checkout_form_element" data-element="size">
            <?php $this->sizes("full"); ?>
          </div>
          <h4>Antal badeværelser</h4>
          <div class="idealrent_checkout_form_element" data-element="bathrooms">
            <?php $this->bathrooms("full"); ?>
          </div>
          <h4>Service</h4>
          <div class="idealrent_checkout_form_element" data-element="service">
            <?php $this->service("full"); ?>
          </div>
          <div class="idealrent_checkout_form_element" style="justify-content: space-around">
            <button data-section="1" class="continue active">Fortsæt</button>
          </div>
        </div>
      </div>
      <div class="idealrent_checkout_form_block">
        <div class="idealrent_checkout_form_section section_2">
          <h2>Hvilket tidspunkt passer dig bedst?</h2>
          <?php $this->date("full"); ?>
          <div class="idealrent_checkout_form_element" style="justify-content: space-around">
            <button data-section="2" class="continue">Fortsæt</button>
          </div>
        </div>
      </div>
      <div class="idealrent_checkout_form_block">
        <div class="idealrent_checkout_form_section section_3">
          <h2>Hvilket tidspunkt passer dig bedst?</h2>
          <?php $this->hour("full"); ?>
          <div class="idealrent_checkout_form_element" style="justify-content: space-around">
            <button data-section="3" class="continue">Fortsæt</button>
          </div>
        </div>
      </div>
      <div class="idealrent_checkout_form_block">
        <div class="idealrent_checkout_form_section section_4">
          <h2>Hvor skal vi gøre rent?</h2>
          <?php $this->address("full"); ?>
          <h2>Hvor tit skal vi komme forbi?</h2>
          <?php $this->frequency(); ?>
          <h2>Ønsker du nogle extra ydelser?</h2>
          <?php $this->services(); ?>
          <h2>Har du kæledyr?</h2>
          <?php $this->pets(); ?>
          <h2>Hvordan kommer vi ind?</h2>
          <?php $this->entrance(); ?>
          <h2>Har du nogen specielle ønsker?</h2>
          <?php $this->info(); ?>
          <div class="idealrent_checkout_form_element" style="justify-content: space-around">
            <button data-section="4" class="continue">Fortsæt</button>
          </div>
        </div>
      </div>
      <div class="idealrent_checkout_form_block">
        <div class="idealrent_checkout_form_section section_5">
          <div class="payblock">
            <label for="card-element">Kort oplysninger</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>

            <label for="idealrent_checkout_name">Dit navn</label>
            <input name="idealrent_checkout_name" id="idealrent_checkout_name" required type="text" placeholder="Dit navn" />

            <label for="idealrent_checkout_phone">Telefonnummer</label>
            <input name="idealrent_checkout_phone" id="idealrent_checkout_phone" required type="text" placeholder="Dit telefonnummer" />

            <label for="idealrent_checkout_email">Email</label>
            <input name="idealrent_checkout_email" id="idealrent_checkout_email" required type="email" placeholder="Din email adresse" />

            <button class="continue finish" id="submit_booking">Godkend bestilling</button>
            <input type="submit" id="secret_submit" style="display: none" name="idealrent_checkout_complete_submit" />
          </div>
        </div>
        <div>

        </div>
      </div>

      <input type="hidden" name="idealrent_checkout_stripe_token" id="stripe_token" />

    </form>
    <?php
  }

  public function info() {
    ?>
    <div class="idealrent_checkout_form_element" data-element="info">
      <textarea name="idealrent_checkout_wishes" rows="4" placeholder="Har du nogen specielle ønsker?"></textarea>
    </div>
    <?php
  }

  public function pets() {
    ?>
    <div class="idealrent_checkout_form_element" data-element="pets">
      <label for="idealrent_checkout_pets_yes"><input value="yes" type="radio" id="idealrent_checkout_pets_yes" name="idealrent_checkout_pets" /><span>Ja</span></label>
      <label for="idealrent_checkout_pets_no"><input value="no" type="radio" id="idealrent_checkout_pets_no" name="idealrent_checkout_pets" /><span>Nej</span></label>
    </div>
    <div style="display: none;" class="idealrent_checkout_form_element" data-element="pets-details">
      <textarea name="idealrent_checkout_pets_info" placeholder="Hvilke kæledyr har du?"></textarea>
    </div>
    <?php
  }

  public function entrance() {
    ?>
    <div class="idealrent_checkout_form_element" data-element="entrance">
      <label for="idealrent_checkout_entrance_home"><input value="home" type="radio" id="idealrent_checkout_entrance_home" name="idealrent_checkout_entrance" /><span>Vi er hjemme</span></label>
      <label for="idealrent_checkout_entrance_doorman"><input value="doorman" type="radio" id="idealrent_checkout_entrance_doorman" name="idealrent_checkout_entrance" /><span>Dørmand</span></label>
      <label for="idealrent_checkout_entrance_key"><input value="key" type="radio" id="idealrent_checkout_entrance_key" name="idealrent_checkout_entrance" /><span>Skjult nøgle</span></label>
      <label for="idealrent_checkout_entrance_other"><input value="other" type="radio" id="idealrent_checkout_entrance_other" name="idealrent_checkout_entrance" /><span>Andet</span></label>
    </div>
    <div style="display: none;" class="idealrent_checkout_form_element" data-element="entrance-details">
      <textarea name="idealrent_checkout_entrance_info" placeholder="Angiv venligst"></textarea>
    </div>
    <?php
  }

  private function check_post() {
    return !empty($_POST["idealrent_checkout_stripe_token"]);
  }

  private function layout_receipt() {
    $target = get_option("ir-checkout-stripe-mail");

    $mail = $_POST["idealrent_checkout_email"];
    $phone = $_POST["idealrent_checkout_phone"];
    $name = $_POST["idealrent_checkout_name"];
    $token = $_POST["idealrent_checkout_stripe_token"];

    switch($_POST["idealrent_checkout_size"]) {
      case 2:
        $size = "70 til 100kvm";
        break;
      case 3:
        $size = "100 til 130kvm";
        break;
      case 4:
        $size = "130 til 160kvm";
        break;
      case 5:
        $size = "Over 160kvm";
        break;
      default:
        $size = "Op til 70kvm";
        break;
    }

    switch($_POST["idealrent_checkout_bathrooms"]) {
      case 2:
        $baths = "2 badeværelser";
        break;
      case 3:
        $baths = "3 badeværelser";
        break;
      case 4:
        $baths = "4 badeværelser";
        break;
      case 5:
        $baths = "5 badeværelser";
        break;
      default:
        $baths = "1 badeværelse";
        break;
    }

    switch($_POST["idealrent_checkout_frequency"]) {
      case 2:
        $frequency = "Ugentligt";
        break;
      case 3:
        $frequency = "Hver anden uge";
        break;
      case 4:
        $frequency = "Månedligt";
        break;
      default:
        $frequency = "Enkelt";
        break;
    }


    switch($_POST["idealrent_checkout_service"]) {
      case 2:
        $service = "Hovedrengøring";
        break;
      default:
        $service = "Standard";
        break;
    }

    $date = date("d/m/Y", strtotime($_POST["idealrent_checkout_date"]));
    $time = isset($_POST["idealrent_checkout_hour"]) && is_numeric($_POST["idealrent_checkout_hour"]) ? $_POST["idealrent_checkout_hour"].":00" : "Fleksibel";
    $address = $_POST["idealrent_checkout_address_complete"];

    $extra = array();
    if (isset($_POST["idealrent_checkout_extra_1"])) $extra[] = "Ovn";
    if (isset($_POST["idealrent_checkout_extra_2"])) $extra[] = "Køleskab";
    if (isset($_POST["idealrent_checkout_extra_3"])) $extra[] = "Strygning";
    if (isset($_POST["idealrent_checkout_extra_4"])) $extra[] = "Sengetøj";

    $extra = empty($extra) ? "Ingen" : implode(" ", $extra);

    if (isset($_POST["idealrent_checkout_pets"]) && $_POST["idealrent_checkout_pets"] == "yes") {
      $pets = trim("Ja - ".$_POST["idealrent_checkout_pets_info"], " \t\n\r\0\x0B-");
    } else {
      $pets = "Nej";
    }

    if (isset($_POST["idealrent_checkout_entrance"]) && in_array($_POST["idealrent_checkout_entrance"], array("key", "other"))) {
      $entrance = isset($_POST["idealrent_checkout_entrance"]) && $_POST["idealrent_checkout_entrance"] == "key" ? "Adgang: Skjult nøgle" : "Adgang: Andet";
      if (!empty($_POST["idealrent_checkout_entrance_info"])) $entrance .= "<br/>".$_POST["idealrent_checkout_entrance_info"];
    } else {
      $entrance = isset($_POST["idealrent_checkout_entrance"]) && $_POST["idealrent_checkout_entrance"] == "home" ? "Adgang: Vi er hjemme" : "Adgang: Dørmand";
    }

    $info = empty($_POST["idealrent_checkout_wishes"]) ? "" : $_POST["idealrent_checkout_wishes"]."<br/>";

    $body = "<html><body><h3>Information om booking</h3>
<br/>
Størrelse: {$size}<br/>
Badeværelser: {$baths}<br/>
Service: {$service}<br/>
<br/>
Tid: {$date} kl. {$time}<br/>
Sted: {$address}<br/>
<br/>
Gentagelse: {$frequency}<br/>
Ekstra services: {$extra}<br/>
Husdyr: {$pets}<br/>
Adgang: {$entrance}<br/>
<br/>
Ekstra information:<br/>
{$info}<br/>
Navn: {$name}<br/>
Email: {$mail}<br/>
Telefon: {$phone}<br/>
<br/>
Stripe token: {$token}
</body></html>";

    $headers = array(
      "Content-Type: text/html; charset=UTF-8",
      "From: IdealRent <booking@{$_SERVER["SERVER_NAME"]}>",
    );

    wp_mail($target, "Ny booking modtaget", $body, $headers);

    $redir = get_option("ir-checkout-stripe-booking-page");
    if (strpos($redir, "http://") !== 0) {
      $redir = "/" . trim($redir, " \t\n\r\0\x0B/");
    }
    ob_clean();
    header("Location: ".$redir);
    die;
  }

  public function services() {
    $freqs = array(
      "Ovn" => get_option("ir-checkout-extra-oven", 0),
      "Køleskab" => get_option("ir-checkout-extra-fridge", 0),
      "Strygning" => get_option("ir-checkout-extra-shirts", 0),
      "Sengetøj" => get_option("ir-checkout-extra-linen", 0),
    )
    ?>
    <div class="idealrent_checkout_form_element" data-element="services">
      <?php
      $i = 0; foreach($freqs as $label => $price):
        $i++;
        $class = ($price > 0) ? " hasprice" : "";
        $dspan = ($price > 0) ? "<span class='pricespan'>{$price},-</span>" : "";
        ?>
        <label class="checker <?php echo $class; ?>" for="idealrent_checkout_extra_<?php echo $i; ?>"><input data-price="<?php echo $price; ?>" value="1" type="checkbox" id="idealrent_checkout_extra_<?php echo $i; ?>" name="idealrent_checkout_extra_<?php echo $i; ?>" /><span><?php echo $label; ?></span><?php echo $dspan; ?></label>
        <?php
      endforeach;
      ?>
    </div>
    <?php
  }

  public function frequency() {
    $freqs = array(
      "Enkelt" => 0,
      "Ugentlig" => get_option("ir-checkout-frequency-weekly", 0),
      "Hver 2. uge" => get_option("ir-checkout-frequency-biweekly", 0),
      "Månedlig" => get_option("ir-checkout-frequency-monthly", 0),
    )
    ?>
    <div class="idealrent_checkout_form_element" data-element="frequency">
      <?php
      $i = 0; foreach($freqs as $label => $discount):
        $i++;
        $classes = array();
        if ($discount > 0) $classes[] = "hasdiscount";
        if ($i == 1) $classes[] = "active";
        $dspan = ($discount > 0) ? "<span class='discountspan'>spar {$discount}%</span>" : "";
        $classes = empty($classes) ? "" : " class='".implode(" ", $classes)."'";
        $checked = $i == 1 ? " checked='checked'" : "";
        ?>
        <label<?php echo $classes; ?> for="idealrent_checkout_frequency_<?php echo $i; ?>"><input<?php echo $checked; ?> data-discount="<?php echo $discount; ?>" value="<?php echo $i; ?>" type="radio" id="idealrent_checkout_frequency_<?php echo $i; ?>" name="idealrent_checkout_frequency" /><span><?php echo $label; ?></span><?php echo $dspan; ?></label>
        <?php
      endforeach;
      ?>
    </div>
    <?php
  }

  public function form($instance) {
    $defaults = array(
      "layout" => 0,
      "action" => ""
    );
    $instance = wp_parse_args((array)$instance, $defaults);
    ?>
    <p>
      <label for="<?php echo $this->get_field_id("title"); ?>">Layout</label>
      <select class="widefat" id="<?php echo $this->get_field_id("layout"); ?>" name="<?php echo $this->get_field_name("layout"); ?>">
        <option value="0" <?php if ($instance["layout"] == 0) echo "selected='selected'"; ?>>Forside</option>
        <option value="1" <?php if ($instance["layout"] == 1) echo "selected='selected'"; ?>>Fuld</option>
      </select>
    </p>
    <p>
    <label for="<?php echo $this->get_field_id("action"); ?>">Form action</label>
    <input class="widefat" id="<?php echo $this->get_field_id("action"); ?>" name="<?php echo $this->get_field_name("action"); ?>" value="<?php echo $instance["action"]; ?>" />
    </p>
    <?php
  }
}