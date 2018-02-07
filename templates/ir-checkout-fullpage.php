<?php
ob_start();
the_widget("IdealRentCheckoutWidget", array("layout" => 1), array("widget_id" => "ir_checkout_fullpage_widget"));
$widget = ob_get_clean();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
  <meta charset="<?php bloginfo("charset"); ?>">
  <meta name="viewport" content="width=device-width">
  <link rel="profile" href="http://gmpg.org/xfn/11">
  <link rel="pingback" href="<?php bloginfo("pingback_url"); ?>">
  <link rel="stylesheet" href="<?php echo plugins_url()."/idealrent_checkout/assets/idealrent_checkout_fullpage.css"; ?>">
  <?php
  if (function_exists("monsterinsights_tracking_script")) {
    monsterinsights_tracking_script();
  }
  ?>
</head>

<body>

<?php
echo $widget;
wp_footer();
?>

</body>
</html>
