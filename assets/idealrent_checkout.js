(function($) {
  $(function() {

    //Stripe

    //Stripe Elements

    if ($(".idealrent_checkout_form.fullpage_layout").length) {

      var stripe = Stripe(IR_Checkout_settings.stripekey);
      var elements = stripe.elements();
      var style = {
        base: {
          color: "#32325d",
          lineHeight: "18px",
          fontFamily: "\"Helvetica Neue\", Helvetica, sans-serif",
          fontSmoothing: "antialiased",
          fontSize: "16px",
          "::placeholder": {
            color: "#aab7c4"
          }
        },
        invalid: {
          color: "#fa755a",
          iconColor: "#fa755a"
        }
      };
      var card = elements.create("card", {style: style});
      card.mount("#card-element");

      $("#submit_booking").click(function (ev) {
        $("body").append("<div class='idealrent_checkout_overlay'></div>");
        ev.preventDefault();

        stripe.createToken(card).then(function (result) {
          $(".idealrent_checkout_overlay").remove();
          if (result.error) {
            $("#card-errors").text(result.error.message);
          } else {
            $("#card-errors").text("");
            $("#stripe_token").val(result.token.id);
            $("#secret_submit").click();
          }
        });
      });

      $(".idealrent_checkout_form").submit(function () {
        $("body").append("<div class='idealrent_checkout_overlay'></div>");
      });
    }

    // Stripe Checkout
    /*
    var handler = StripeCheckout.configure({
      key: IR_Checkout_settings.stripekey,
      image: "https://stripe.com/img/documentation/checkout/marketplace.png",
      locale: "auto",
      token: function(token) {
        $("#stripe_token").val(token.id);
        $("#stripe_email").val(token.email);
        $("form.idealrent_checkout_form").submit();
      }
    });

    $("#stripebutton").click(function(ev) {
      handler.open({
        name: "Idealrent IVS",
        description: "Rengøring",
        zipCode: true,
        currency: "dkk",
        amount: $(".priceholder").text()*100
      });
      ev.preventDefault();
    });

    window.addEventListener("popstate", function() {
      handler.close();
    });
    */
    //End Stripe

    $("#idealrent_checkout_form_progress_mobile_drop").click(function() {
      if ($(this).hasClass("rolled")) {
        $(".idealrent_checkout_form_progress").animate({height: "84px"}, 200);
      } else {
        var cheight = $($(".idealrent_checkout_form_progress")).height();
        $(".idealrent_checkout_form_progress").css("height", "auto");
        var theight = $($(".idealrent_checkout_form_progress")).height();
        $(".idealrent_checkout_form_progress").css("height", cheight).animate({height: theight}, 200);
      }
      $(this).toggleClass("rolled");
    });

    if ($(".idealrent_checkout_form.fullpage_layout").length) {
      $("body").addClass("checkoutfull");
    }

    $("body").click(function(ev) {
      try {
        var $orig = $(ev.srcElement || ev.originalTarget || ev.originalEvent.originalTarget);
        if (!$orig.is(".idealrent_checkout_form_dropdown")) {
          $(".idealrent_checkout_form_dropdown.active").toggleClass("active");
        }
        if (!$orig.is("#idealrent_checkout_form_progress_mobile_drop") && !$orig.parent().is("#idealrent_checkout_form_progress_mobile_drop")) {
          $("#idealrent_checkout_form_progress_mobile_drop.rolled").click();
        }
      } catch (err) {
        return;
      }
    });

    var pricetimer = false;
    $(".idealrent_checkout_form input").change(function(ev) {
      ev.preventDefault();
      var price = $(".priceholder:first").data("price");
      $(".idealrent_checkout_form input:checked").each(function() {
        if ($(this).data("price")) {
          price += $(this).data("price");
        }
      });
      $(".idealrent_checkout_form input:checked").each(function() {
        if ($(this).data("discount")) {
          price *= (1-$(this).data("discount")/100);
        }
      });
      price = Math.floor(price);
      if ($(".priceholder").data("checkout-price") != price) {
        $(".priceholder").data("checkout-price", price);
        clearTimeout(pricetimer)
        $(".idealrent_price_bubble").removeClass("active");
        $(".idealrent_price_bubble").addClass("active");
        pricetimer = setTimeout(function () {
          $(".idealrent_price_bubble").removeClass("active");
        }, 300);
        $(".priceholder").text(price);
      }
    });

    $(".idealrent_checkout_form_dropdown").click(function(ev) {
      ev.preventDefault();
      $(".idealrent_checkout_form_dropdown.active").not(this).toggleClass("active");
      $(this).toggleClass("active");
    });

    $(".idealrent_checkout_form_dropdown + div input").click(function() {
      var $button = $(this).closest(".idealrent_checkout_form_element").children(".idealrent_checkout_form_dropdown");
      $button.html($(this).parent().find("span").html());
    });

    $(".idealrent_checkout_form_progress button").click(function (ev) {
      ev.preventDefault();
      if (!$(this).parent().is(".waiting, .complete")) return;
      var section = $(this).data("section");
      if (!section) return;

      $(".idealrent_checkout_form_section:not(.section_"+section+")").parent().removeClass("active");
      $(".idealrent_checkout_form_section.section_"+section).parent().addClass("active");
      $(".idealrent_checkout_form_progress button[data-section="+section+"]").parent().addClass("active");

      while(true) {
        section++;
        var $targs = $(".idealrent_checkout_form_progress button[data-section="+section+"]").parent();
        if ($targs.length) {
          $targs.removeClass("active complete");
        } else {
          break;
        }
      }
    });

    $("#idealrent_checkout_address").focus(function() {
      $(this).select();
    }).keydown(function(ev) {
      if (ev.which == 13) {
        ev.preventDefault();
        return false;
      }
    });

    $("#idealrent_checkout_address").dawaautocomplete({
      select: function(event, data) {
        if (data.data.postnr < 3000) {
          $("#idealrent_checkout_address_complete").val(data.tekst);
          $(".idealrent_checkout_form_progress button[data-section=4] .value").html(data.tekst);
          if ($("[name=idealrent_checkout_entrance]:checked").length) {
            $(this).closest(".idealrent_checkout_form_section").find(".continue").addClass("active");
          }
        } else {
          $("body").append("<div class='idealrent_checkout_overlay'></div>");
          $("body").append("<div class='idealrent_checkout_zip_error'><div>Vi kan desværre kun servicere området omkring København</div><button>OK</button></div>");
          $(".idealrent_checkout_zip_error button").focus();
          $("#idealrent_checkout_address").val("");
          $("#idealrent_checkout_address_complete").val("");
          $(".idealrent_checkout_form_progress button[data-section=4] .value").html("");
          $(this).closest(".idealrent_checkout_form_section").find(".continue").removeClass("active");
          $(".idealrent_checkout_zip_error button").click(function() {
            $("#idealrent_checkout_address").focus();
            $(".idealrent_checkout_overlay, .idealrent_checkout_zip_error").remove();
          });
        }
      }
    });

    $(".idealrent_checkout_form.fullpage_layout input").click(function() {
      if ($(this).attr("name") == "idealrent_checkout_pets") {
        if ($(this).val() == "yes" && $(this).is(":checked")) {
          $(this).closest(".idealrent_checkout_form_element").next().show();
        } else if ($(this).val() == "no" && $(this).is(":checked")) {
          $(this).closest(".idealrent_checkout_form_element").next().hide();
        }
      }

      if ($(this).attr("name") == "idealrent_checkout_entrance") {
        if ($("#idealrent_checkout_address_complete").val().length) {
          $(this).closest(".idealrent_checkout_form_section").find(".continue").addClass("active");
        }
        if (($(this).val() == "home" || $(this).val() == "doorman") && $(this).is(":checked")) {
          $(this).closest(".idealrent_checkout_form_element").next().hide();
        } else if (($(this).val() == "key" || $(this).val() == "other") && $(this).is(":checked")) {
          $(this).closest(".idealrent_checkout_form_element").next().show();
        }
      }

      if ($(this).is(":radio")) {
        var $el = $(this).closest(".idealrent_checkout_form_element");
        $el.find("label.active").removeClass("active");
        $(this).parent().addClass("active");
        $("#idealrent_checkout_form_progress_" + $el.data("element")).find("label.value").html($(this).data("label"));
      } else {
        $(this).parent().toggleClass("active");
      }
    });

    $(".idealrent_checkout_form.fullpage_layout").find(".section_2, .section_3").find("input").click(function() {
      $(this).closest(".idealrent_checkout_form_section").find(".continue").addClass("active");
    });

    $(".idealrent_checkout_form.fullpage_layout button.continue:not(.finish)").click(function(ev) {

      var section = $(this).data("section");
      $(".idealrent_checkout_form_progress button[data-section="+section+"]").parent().addClass("complete").removeClass("active");
      $(".idealrent_checkout_form_progress button[data-section="+(section + 1)+"]").parent().addClass("active");

      ev.preventDefault();
      var $targ = $(this).closest(".idealrent_checkout_form_block");
      $targ.removeClass("active");
      $targ.next("div").addClass("active");

      if ($targ.next("div").find(".main-carousel").length) {
        $(".main-carousel").flickity({
          cellAlign: "left",
          contain: true
        });
      }
    });

  });
})(jQuery);