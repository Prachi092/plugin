(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

/* global wcv_sc_params */
jQuery(function ($) {
  'use strict';

  try {
    var stripe = Stripe(wcv_sc_params.key);
  } catch (error) {
    console.log(error);
    return;
  }

  var stripe_elements_options = Object.keys(wcv_sc_params.elements_options).length ? wcv_sc_params.elements_options : {},
      sepa_elements_options = Object.keys(wcv_sc_params.sepa_elements_options).length ? wcv_sc_params.sepa_elements_options : {},
      elements = stripe.elements(stripe_elements_options),
      stripe_card,
      stripe_exp,
      stripe_cvc;
  /**
   * Object to handle Stripe elements payment form.
   */

  var wcv_stripe_connect_form = {
    /**
     * Get WC AJAX endpoint URL.
     *
     * @param  {String} endpoint Endpoint.
     * @return {String}
     */
    getAjaxURL: function getAjaxURL(endpoint) {
      return wcv_sc_params.ajaxurl.toString().replace('%%endpoint%%', 'wcv_sc_' + endpoint);
    },
    unmountElements: function unmountElements() {
      if ('yes' === wcv_sc_params.inline_cc_form) {
        stripe_card.unmount('#stripe-connect-card-element');
      } else {
        stripe_card.unmount('#stripe-connect-card-element');
        stripe_exp.unmount('#stripe-connect-exp-element');
        stripe_cvc.unmount('#stripe-connect-cvc-element');
      }
    },
    mountElements: function mountElements() {
      if (!$('#stripe-connect-card-element').length) {
        return;
      }

      if ('yes' === wcv_sc_params.inline_cc_form) {
        stripe_card.mount('#stripe-connect-card-element');
      } else {
        stripe_card.mount('#stripe-connect-card-element');
        stripe_exp.mount('#stripe-connect-exp-element');
        stripe_cvc.mount('#stripe-connect-cvc-element');
      }
    },
    createElements: function createElements() {
      var elementStyles = {
        base: {
          iconColor: '#666EE8',
          color: '#31325F',
          fontSize: '15px',
          '::placeholder': {
            color: '#CFD7E0'
          }
        }
      };
      var elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid'
      };
      elementStyles = wcv_sc_params.elements_styling ? wcv_sc_params.elements_styling : elementStyles;
      elementClasses = wcv_sc_params.elements_classes ? wcv_sc_params.elements_classes : elementClasses;

      if ('yes' === wcv_sc_params.inline_cc_form) {
        stripe_card = elements.create('card', {
          style: elementStyles,
          hidePostalCode: true
        });
        stripe_card.addEventListener('change', function (event) {
          wcv_stripe_connect_form.onCCFormChange();

          if (event.error) {
            $(document.body).trigger('stripeError', event);
          }
        });
      } else {
        stripe_card = elements.create('cardNumber', {
          style: elementStyles,
          classes: elementClasses
        });
        stripe_exp = elements.create('cardExpiry', {
          style: elementStyles,
          classes: elementClasses
        });
        stripe_cvc = elements.create('cardCvc', {
          style: elementStyles,
          classes: elementClasses
        });
        stripe_card.addEventListener('change', function (event) {
          wcv_stripe_connect_form.onCCFormChange();
          wcv_stripe_connect_form.updateCardBrand(event.brand);

          if (event.error) {
            $(document.body).trigger('stripeError', event);
          }
        });
        stripe_exp.addEventListener('change', function (event) {
          wcv_stripe_connect_form.onCCFormChange();

          if (event.error) {
            $(document.body).trigger('stripeError', event);
          }
        });
        stripe_cvc.addEventListener('change', function (event) {
          wcv_stripe_connect_form.onCCFormChange();

          if (event.error) {
            $(document.body).trigger('stripeError', event);
          }
        });
      }
      /**
       * Only in checkout page we need to delay the mounting of the
       * card as some AJAX process needs to happen before we do.
       */


      if ('yes' === wcv_sc_params.is_checkout) {
        $(document.body).on('updated_checkout', function () {
          // Don't mount elements a second time.
          if (stripe_card) {
            wcv_stripe_connect_form.unmountElements();
          }

          wcv_stripe_connect_form.mountElements(); // if ( $( '#stripe-iban-element' ).length ) {
          // 	iban.mount( '#stripe-iban-element' );
          // }
        });
      } else if ($('form#add_payment_method').length || $('form#order_review').length) {
        wcv_stripe_connect_form.mountElements(); // if ( $( '#stripe-iban-element' ).length ) {
        // 	iban.mount( '#stripe-iban-element' );
        // }
      }
    },
    updateCardBrand: function updateCardBrand(brand) {
      var brandClass = {
        'visa': 'stripe-visa-brand',
        'mastercard': 'stripe-mastercard-brand',
        'amex': 'stripe-amex-brand',
        'discover': 'stripe-discover-brand',
        'diners': 'stripe-diners-brand',
        'jcb': 'stripe-jcb-brand',
        'unknown': 'stripe-credit-card-brand'
      };
      var imageElement = $('.stripe-card-brand'),
          imageClass = 'stripe-credit-card-brand';

      if (brand in brandClass) {
        imageClass = brandClass[brand];
      } // Remove existing card brand class.


      $.each(brandClass, function (index, el) {
        imageElement.removeClass(el);
      });
      imageElement.addClass(imageClass);
    },

    /**
     * Initialize event handlers and UI state.
     */
    init: function init() {
      // Initialize tokenization script if on change payment method page and pay for order page.
      if ('yes' === wcv_sc_params.is_change_payment_page || 'yes' === wcv_sc_params.is_pay_for_order_page) {
        $(document.body).trigger('wc-credit-card-form-init');
      } // Stripe Checkout.


      this.stripe_checkout_submit = false; // checkout page

      if ($('form.woocommerce-checkout').length) {
        this.form = $('form.woocommerce-checkout');
      }

      $('form.woocommerce-checkout').on('checkout_place_order_stripe checkout_place_order_stripe_bancontact checkout_place_order_stripe_sofort checkout_place_order_stripe_giropay checkout_place_order_stripe_ideal checkout_place_order_stripe_alipay checkout_place_order_stripe_sepa', this.onSubmit); // pay order page

      if ($('form#order_review').length) {
        this.form = $('form#order_review');
      }

      $('form#order_review, form#add_payment_method').on('submit', this.onSubmit); // add payment method page

      if ($('form#add_payment_method').length) {
        this.form = $('form#add_payment_method');
      }

      $('form.woocommerce-checkout').on('change', this.reset);
      $(document).on('stripeError', this.onError).on('checkout_error', this.reset); // // SEPA IBAN.
      // iban.on( 'change',
      // 	this.onSepaError
      // );

      wcv_stripe_connect_form.createElements();

      if ('yes' === wcv_sc_params.is_stripe_checkout) {
        $(document.body).on('click', '.wc-stripe-checkout-button', function () {
          wcv_stripe_connect_form.openModal();
          return false;
        });
      }
    },
    // Check to see if Stripe in general is being used for checkout.
    isStripeChosen: function isStripeChosen() {
      return $('#payment_method_stripe-connect, #payment_method_stripe-connect_bancontact, #payment_method_stripe-connect_sofort, #payment_method_stripe-connect_giropay, #payment_method_stripe-connect_ideal, #payment_method_stripe-connect_alipay, #payment_method_stripe-connect_sepa, #payment_method_stripe-connect_eps, #payment_method_stripe-connect_multibanco').is(':checked') || $('#payment_method_stripe-connect').is(':checked') && 'new' === $('input[name="wc-stripe-payment-token"]:checked').val() || $('#payment_method_stripe-connect_sepa').is(':checked') && 'new' === $('input[name="wc-stripe-payment-token"]:checked').val();
    },
    // Currently only support saved cards via credit cards and SEPA. No other payment method.
    isStripeSaveCardChosen: function isStripeSaveCardChosen() {
      return $('#payment_method_stripe-connect').is(':checked') && $('input[name="wc-stripe-payment-token"]').is(':checked') && 'new' !== $('input[name="wc-stripe-payment-token"]:checked').val() || $('#payment_method_stripe-connect_sepa').is(':checked') && $('input[name="wc-stripe_sepa-payment-token"]').is(':checked') && 'new' !== $('input[name="wc-stripe_sepa-payment-token"]:checked').val();
    },
    // Stripe credit card used.
    isStripeCardChosen: function isStripeCardChosen() {
      return $('#payment_method_stripe-connect').is(':checked');
    },
    isBancontactChosen: function isBancontactChosen() {
      return $('#payment_method_stripe-connect_bancontact').is(':checked');
    },
    isGiropayChosen: function isGiropayChosen() {
      return $('#payment_method_stripe-connect_giropay').is(':checked');
    },
    isIdealChosen: function isIdealChosen() {
      return $('#payment_method_stripe-connect_ideal').is(':checked');
    },
    isSofortChosen: function isSofortChosen() {
      return $('#payment_method_stripe-connect_sofort').is(':checked');
    },
    isAlipayChosen: function isAlipayChosen() {
      return $('#payment_method_stripe-connect_alipay').is(':checked');
    },
    isSepaChosen: function isSepaChosen() {
      return $('#payment_method_stripe-connect_sepa').is(':checked');
    },
    isP24Chosen: function isP24Chosen() {
      return $('#payment_method_stripe-connect_p24').is(':checked');
    },
    isEpsChosen: function isEpsChosen() {
      return $('#payment_method_stripe-connect_eps').is(':checked');
    },
    isMultibancoChosen: function isMultibancoChosen() {
      return $('#payment_method_stripe-connect_multibanco').is(':checked');
    },
    hasSource: function hasSource() {
      return 0 < $('input.stripe-connect-source').length;
    },
    // Legacy
    hasToken: function hasToken() {
      return 0 < $('input.stripe_connect_token').length;
    },
    isMobile: function isMobile() {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return true;
      }

      return false;
    },
    isStripeModalNeeded: function isStripeModalNeeded(e) {
      var token = wcv_stripe_connect_form.form.find('input.stripe_connect_token'),
          $required_inputs; // If this is a stripe submission (after modal) and token exists, allow submit.

      if (wcv_stripe_connect_form.stripe_submit && token) {
        return false;
      } // Don't affect submission if modal is not needed.


      if (!wcv_stripe_connect_form.isStripeChosen()) {
        return false;
      }

      return true;
    },
    block: function block() {
      if (!wcv_stripe_connect_form.isMobile()) {
        wcv_stripe_connect_form.form.block({
          message: null,
          overlayCSS: {
            background: '#fff',
            opacity: 0.6
          }
        });
      }
    },
    unblock: function unblock() {
      wcv_stripe_connect_form.form.unblock();
    },
    getSelectedPaymentElement: function getSelectedPaymentElement() {
      return $('.payment_methods input[name="payment_method"]:checked');
    },
    // Stripe Checkout.
    openModal: function openModal() {
      // Capture submittal and open stripecheckout
      var $form = wcv_stripe_connect_form.form,
          $data = $('#stripe-connect-payment-data');
      wcv_stripe_connect_form.reset();

      var token_action = function token_action(res) {
        $form.find('input.stripe_connect_source').remove();
        /* Since source was introduced in 4.0. We need to
         * convert the token into a source.
         */

        if ('token' === res.object) {
          stripe.createSource({
            type: 'card',
            token: res.id
          }).then(wcv_stripe_connect_form.sourceResponse);
        } else if ('source' === res.object) {
          var response = {
            source: res
          };
          wcv_stripe_connect_form.sourceResponse(response);
        }
      };

      StripeCheckout.open({
        key: wcv_sc_params.key,
        billingAddress: $data.data('billing-address'),
        zipCode: $data.data('verify-zip'),
        amount: $data.data('amount'),
        name: $data.data('name'),
        description: $data.data('description'),
        currency: $data.data('currency'),
        image: $data.data('image'),
        locale: $data.data('locale'),
        email: $('#billing_email').val() || $data.data('email'),
        panelLabel: $data.data('panel-label'),
        allowRememberMe: $data.data('allow-remember-me'),
        token: token_action,
        closed: wcv_stripe_connect_form.onClose()
      });
    },
    // Stripe Checkout.
    resetModal: function resetModal() {
      wcv_stripe_connect_form.reset();
      wcv_stripe_connect_form.stripe_checkout_submit = false;
    },
    // Stripe Checkout.
    onClose: function onClose() {
      wcv_stripe_connect_form.unblock();
    },
    getOwnerDetails: function getOwnerDetails() {
      var first_name = $('#billing_first_name').length ? $('#billing_first_name').val() : wcv_sc_params.billing_first_name,
          last_name = $('#billing_last_name').length ? $('#billing_last_name').val() : wcv_sc_params.billing_last_name,
          extra_details = {
        owner: {
          name: '',
          address: {},
          email: '',
          phone: ''
        }
      };
      extra_details.owner.name = first_name;

      if (first_name && last_name) {
        extra_details.owner.name = first_name + ' ' + last_name;
      } else {
        extra_details.owner.name = $('#stripe-payment-data').data('full-name');
      }

      extra_details.owner.email = $('#billing_email').val();
      extra_details.owner.phone = $('#billing_phone').val();
      /* Stripe does not like empty string values so
       * we need to remove the parameter if we're not
       * passing any value.
       */

      if (typeof extra_details.owner.phone === 'undefined' || 0 >= extra_details.owner.phone.length) {
        delete extra_details.owner.phone;
      }

      if (typeof extra_details.owner.email === 'undefined' || 0 >= extra_details.owner.email.length) {
        if ($('#stripe-connect-payment-data').data('email').length) {
          extra_details.owner.email = $('#stripe-connect-payment-data').data('email');
        } else {
          delete extra_details.owner.email;
        }
      }

      if (typeof extra_details.owner.name === 'undefined' || 0 >= extra_details.owner.name.length) {
        delete extra_details.owner.name;
      }

      if ($('#billing_address_1').length > 0) {
        extra_details.owner.address.line1 = $('#billing_address_1').val();
        extra_details.owner.address.line2 = $('#billing_address_2').val();
        extra_details.owner.address.state = $('#billing_state').val();
        extra_details.owner.address.city = $('#billing_city').val();
        extra_details.owner.address.postal_code = $('#billing_postcode').val();
        extra_details.owner.address.country = $('#billing_country').val();
      } else if (wcv_sc_params.billing_address_1) {
        extra_details.owner.address.line1 = wcv_sc_params.billing_address_1;
        extra_details.owner.address.line2 = wcv_sc_params.billing_address_2;
        extra_details.owner.address.state = wcv_sc_params.billing_state;
        extra_details.owner.address.city = wcv_sc_params.billing_city;
        extra_details.owner.address.postal_code = wcv_sc_params.billing_postcode;
        extra_details.owner.address.country = wcv_sc_params.billing_country;
      }

      return extra_details;
    },
    createSource: function createSource() {
      var extra_details = wcv_stripe_connect_form.getOwnerDetails(),
          source_type = 'card';

      if (wcv_stripe_connect_form.isBancontactChosen()) {
        source_type = 'bancontact';
      }

      if (wcv_stripe_connect_form.isSepaChosen()) {
        source_type = 'sepa_debit';
      }

      if (wcv_stripe_connect_form.isIdealChosen()) {
        source_type = 'ideal';
      }

      if (wcv_stripe_connect_form.isSofortChosen()) {
        source_type = 'sofort';
      }

      if (wcv_stripe_connect_form.isGiropayChosen()) {
        source_type = 'giropay';
      }

      if (wcv_stripe_connect_form.isAlipayChosen()) {
        source_type = 'alipay';
      }

      if ('card' === source_type) {
        stripe.createSource(stripe_card, extra_details).then(wcv_stripe_connect_form.sourceResponse);
      } else {
        switch (source_type) {
          case 'bancontact':
          case 'giropay':
          case 'ideal':
          case 'sofort':
          case 'alipay':
            // These redirect flow payment methods need this information to be set at source creation.
            extra_details.amount = $('#stripe-' + source_type + '-payment-data').data('amount');
            extra_details.currency = $('#stripe-' + source_type + '-payment-data').data('currency');
            extra_details.redirect = {
              return_url: wcv_sc_params.return_url
            };

            if (wcv_sc_params.statement_descriptor) {
              extra_details.statement_descriptor = wcv_sc_params.statement_descriptor;
            }

            break;
        } // Handle special inputs that are unique to a payment method.


        switch (source_type) {
          case 'sepa_debit':
            extra_details.currency = $('#stripe-' + source_type + '-payment-data').data('currency');
            extra_details.mandate = {
              notification_method: wcv_sc_params.sepa_mandate_notification
            };
            break;

          case 'ideal':
            extra_details.ideal = {
              bank: $('#stripe-ideal-bank').val()
            };
            break;

          case 'alipay':
            extra_details.currency = $('#stripe-' + source_type + '-payment-data').data('currency');
            extra_details.amount = $('#stripe-' + source_type + '-payment-data').data('amount');
            break;

          case 'sofort':
            extra_details.sofort = {
              country: $('#billing_country').val()
            };
            break;
        }

        extra_details.type = source_type; // if ( 'sepa_debit' === source_type ) {
        // 	stripe.createSource( iban, extra_details ).then( wcv_stripe_connect_form.sourceResponse );
        // } else {

        stripe.createSource(extra_details).then(wcv_stripe_connect_form.sourceResponse); // }
      }
    },
    sourceResponse: function sourceResponse(response) {
      if (response.error) {
        $(document.body).trigger('stripeError', response);
      } else if ('no' === wcv_sc_params.allow_prepaid_card && 'card' === response.source.type && 'prepaid' === response.source.card.funding) {
        response.error = {
          message: wcv_sc_params.no_prepaid_card_msg
        };

        if ('yes' === wcv_sc_params.is_stripe_checkout) {
          wcv_stripe_connect_form.submitError('<ul class="woocommerce-error"><li>' + wcv_sc_params.no_prepaid_card_msg + '</li></ul>');
        } else {
          $(document.body).trigger('stripeError', response);
        }
      } else {
        wcv_stripe_connect_form.processStripeResponse(response.source);
      }
    },
    processStripeResponse: function processStripeResponse(source) {
      wcv_stripe_connect_form.reset(); // Insert the Source into the form so it gets submitted to the server.

      wcv_stripe_connect_form.form.append("<input type='hidden' class='stripe-source' name='stripe_source' value='" + source.id + "'/>");

      if ($('form#add_payment_method').length) {
        $(wcv_stripe_connect_form.form).off('submit', wcv_stripe_connect_form.form.onSubmit);
      }

      wcv_stripe_connect_form.form.submit();
    },
    onSubmit: function onSubmit(e) {
      if (!wcv_stripe_connect_form.isStripeChosen()) {
        return;
      }

      if (!wcv_stripe_connect_form.isStripeSaveCardChosen() && !wcv_stripe_connect_form.hasSource() && !wcv_stripe_connect_form.hasToken()) {
        e.preventDefault();
        wcv_stripe_connect_form.block(); // Stripe Checkout.

        if ('yes' === wcv_sc_params.is_stripe_checkout && wcv_stripe_connect_form.isStripeModalNeeded() && wcv_stripe_connect_form.isStripeCardChosen()) {
          if ('yes' === wcv_sc_params.is_checkout) {
            return true;
          } else {
            wcv_stripe_connect_form.openModal();
            return false;
          }
        }
        /*
         * For methods that needs redirect, we will create the
         * source server side so we can obtain the order ID.
         */


        if (wcv_stripe_connect_form.isBancontactChosen() || wcv_stripe_connect_form.isGiropayChosen() || wcv_stripe_connect_form.isIdealChosen() || wcv_stripe_connect_form.isAlipayChosen() || wcv_stripe_connect_form.isSofortChosen() || wcv_stripe_connect_form.isP24Chosen() || wcv_stripe_connect_form.isEpsChosen() || wcv_stripe_connect_form.isMultibancoChosen()) {
          if ($('form#order_review').length) {
            $('form#order_review').off('submit', this.onSubmit);
            wcv_stripe_connect_form.form.submit();
            return false;
          }

          if ($('form.woocommerce-checkout').length) {
            return true;
          }

          if ($('form#add_payment_method').length) {
            $('form#add_payment_method').off('submit', this.onSubmit);
            wcv_stripe_connect_form.form.submit();
            return false;
          }
        }

        wcv_stripe_connect_form.createSource(); // Prevent form submitting

        return false;
      } else if ($('form#add_payment_method').length) {
        e.preventDefault(); // Stripe Checkout.

        if ('yes' === wcv_sc_params.is_stripe_checkout && wcv_stripe_connect_form.isStripeModalNeeded() && wcv_stripe_connect_form.isStripeCardChosen()) {
          wcv_stripe_connect_form.openModal();
          return false;
        }

        wcv_stripe_connect_form.block();
        wcv_stripe_connect_form.createSource();
        return false;
      }
    },
    onCCFormChange: function onCCFormChange() {
      wcv_stripe_connect_form.reset();
    },
    reset: function reset() {
      $('.wcv-stripe-connect-error, .stripe-source, .stripe_token').remove(); // Stripe Checkout.

      if ('yes' === wcv_sc_params.is_stripe_checkout) {
        wcv_stripe_connect_form.stripe_submit = false;
      }
    },
    onSepaError: function onSepaError(e) {
      var errorContainer = wcv_stripe_connect_form.getSelectedPaymentElement().parents('li').eq(0).find('.stripe-connect-source-errors');

      if (e.error) {
        console.log(e.error.message); // Leave for troubleshooting.

        $(errorContainer).html('<ul class="woocommerce_error woocommerce-error wcv-stripe-connect-error"><li>' + e.error.message + '</li></ul>');
      } else {
        $(errorContainer).html('');
      }
    },
    onError: function onError(e, result) {
      var message = result.error.message,
          errorContainer = wcv_stripe_connect_form.getSelectedPaymentElement().parents('li').eq(0).find('.stripe-connect-source-errors');
      /*
       * If payment method is SEPA and owner name is not completed,
       * source cannot be created. So we need to show the normal
       * Billing name is required error message on top of form instead
       * of inline.
       */

      if (wcv_stripe_connect_form.isSepaChosen()) {
        if ('invalid_owner_name' === result.error.code && wcv_sc_params.hasOwnProperty(result.error.code)) {
          var error = '<ul class="woocommerce-error"><li>' + wcv_sc_params[result.error.code] + '</li></ul>';
          return wcv_stripe_connect_form.submitError(error);
        }
      }
      /*
       * Customers do not need to know the specifics of the below type of errors
       * therefore return a generic localizable error message.
       */


      if ('invalid_request_error' === result.error.type || 'api_connection_error' === result.error.type || 'api_error' === result.error.type || 'authentication_error' === result.error.type || 'rate_limit_error' === result.error.type) {
        message = wcv_sc_params.invalid_request_error;
      }

      if ('card_error' === result.error.type && wcv_sc_params.hasOwnProperty(result.error.code)) {
        message = wcv_sc_params[result.error.code];
      }

      if ('validation_error' === result.error.type && wcv_sc_params.hasOwnProperty(result.error.code)) {
        message = wcv_sc_params[result.error.code];
      }

      wcv_stripe_connect_form.reset();
      $('.woocommerce-NoticeGroup-checkout').remove();
      console.log(result.error.message); // Leave for troubleshooting.

      $(errorContainer).html('<ul class="woocommerce_error woocommerce-error wcv-stripe-connect-error"><li>' + message + '</li></ul>');

      if ($('.wcv-stripe-connect-error').length) {
        $('html, body').animate({
          scrollTop: $('.wcv-stripe-connect-error').offset().top - 200
        }, 200);
      }

      wcv_stripe_connect_form.unblock();
    },
    submitError: function submitError(error_message) {
      $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
      wcv_stripe_connect_form.form.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
      wcv_stripe_connect_form.form.removeClass('processing').unblock();
      wcv_stripe_connect_form.form.find('.input-text, select, input:checkbox').blur();
      var selector = '';

      if ($('#add_payment_method').length) {
        selector = $('#add_payment_method');
      }

      if ($('#order_review').length) {
        selector = $('#order_review');
      }

      if ($('form.checkout').length) {
        selector = $('form.checkout');
      }

      if (selector.length) {
        $('html, body').animate({
          scrollTop: selector.offset().top - 100
        }, 500);
      }

      $(document.body).trigger('checkout_error');
      wcv_stripe_connect_form.unblock();
    }
  };
  wcv_stripe_connect_form.init();
});

},{}]},{},[1])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvc3JjL2pzL3N0cmlwZS1jb25uZWN0LmpzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBOzs7QUNBQTtBQUVBLE1BQU0sQ0FBRSxVQUFVLENBQVYsRUFBYztBQUNyQjs7QUFFQSxNQUFJO0FBQ0gsUUFBSSxNQUFNLEdBQUcsTUFBTSxDQUFFLGFBQWEsQ0FBQyxHQUFoQixDQUFuQjtBQUNBLEdBRkQsQ0FFRSxPQUFPLEtBQVAsRUFBZTtBQUNoQixJQUFBLE9BQU8sQ0FBQyxHQUFSLENBQWEsS0FBYjtBQUNBO0FBQ0E7O0FBRUQsTUFBSSx1QkFBdUIsR0FBRyxNQUFNLENBQUMsSUFBUCxDQUFhLGFBQWEsQ0FBQyxnQkFBM0IsRUFBOEMsTUFBOUMsR0FBdUQsYUFBYSxDQUFDLGdCQUFyRSxHQUF3RixFQUF0SDtBQUFBLE1BQ0MscUJBQXFCLEdBQUssTUFBTSxDQUFDLElBQVAsQ0FBYSxhQUFhLENBQUMscUJBQTNCLEVBQW1ELE1BQW5ELEdBQTRELGFBQWEsQ0FBQyxxQkFBMUUsR0FBa0csRUFEN0g7QUFBQSxNQUVDLFFBQVEsR0FBa0IsTUFBTSxDQUFDLFFBQVAsQ0FBaUIsdUJBQWpCLENBRjNCO0FBQUEsTUFHQyxXQUhEO0FBQUEsTUFJQyxVQUpEO0FBQUEsTUFLQyxVQUxEO0FBT0E7Ozs7QUFHQSxNQUFJLHVCQUF1QixHQUFHO0FBQzdCOzs7Ozs7QUFNQSxJQUFBLFVBQVUsRUFBRSxvQkFBVSxRQUFWLEVBQXFCO0FBQ2hDLGFBQU8sYUFBYSxDQUFDLE9BQWQsQ0FDTCxRQURLLEdBRUwsT0FGSyxDQUVJLGNBRkosRUFFb0IsWUFBWSxRQUZoQyxDQUFQO0FBR0EsS0FYNEI7QUFhN0IsSUFBQSxlQUFlLEVBQUUsMkJBQVc7QUFDM0IsVUFBSyxVQUFVLGFBQWEsQ0FBQyxjQUE3QixFQUE4QztBQUM3QyxRQUFBLFdBQVcsQ0FBQyxPQUFaLENBQXFCLDhCQUFyQjtBQUNBLE9B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QjtBQUNBLE1BQUEsdUJBQXVCLENBQUMsT0FBeEI7QUFDQTtBQTV0QjRCLEdBQTlCO0FBK3RCQSxFQUFBLHVCQUF1QixDQUFDLElBQXhCO0FBQ0EsQ0FwdkJLLENBQU4iLCJmaWxlIjoiZ2VuZXJhdGVkLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXNDb250ZW50IjpbIihmdW5jdGlvbigpe2Z1bmN0aW9uIHIoZSxuLHQpe2Z1bmN0aW9uIG8oaSxmKXtpZighbltpXSl7aWYoIWVbaV0pe3ZhciBjPVwiZnVuY3Rpb25cIj09dHlwZW9mIHJlcXVpcmUmJnJlcXVpcmU7aWYoIWYmJmMpcmV0dXJuIGMoaSwhMCk7aWYodSlyZXR1cm4gdShpLCEwKTt2YXIgYT1uZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiK2krXCInXCIpO3Rocm93IGEuY29kZT1cIk1PRFVMRV9OT1RfRk9VTkRcIixhfXZhciBwPW5baV09e2V4cG9ydHM6e319O2VbaV1bMF0uY2FsbChwLmV4cG9ydHMsZnVuY3Rpb24ocil7dmFyIG49ZVtpXVsxXVtyXTtyZXR1cm4gbyhufHxyKX0scCxwLmV4cG9ydHMscixlLG4sdCl9cmV0dXJuIG5baV0uZXhwb3J0c31mb3IodmFyIHU9XCJmdW5jdGlvblwiPT10eXBlb2YgcmVxdWlyZSYmcmVxdWlyZSxpPTA7aTx0Lmxlbmd0aDtpKyspbyh0W2ldKTtyZXR1cm4gb31yZXR1cm4gcn0pKCkiLCIvKiBnbG9iYWwgd2N2X3NjX3BhcmFtcyAqL1xuXG5qUXVlcnkoIGZ1bmN0aW9uKCAkICkge1xuXHQndXNlIHN0cmljdCc7XG5cblx0dHJ5IHtcblx0XHR2YXIgc3RyaXBlID0gU3RyaXBlKCB3Y3Zfc2NfcGFyYW1zLmtleSApO1xuXHR9IGNhdGNoKCBlcnJvciApIHtcblx0XHRjb25zb2xlLmxvZyggZXJyb3IgKTtcblx0XHRyZXR1cm47XG5cdH1cblxuXHR2YXIgc3RyaXBlX2VsZW1lbnRzX29wdGlvbnMgPSBPYmplY3Qua2V5cyggd2N2X3NjX3BhcmFtcy5lbGVtZW50c19vcHRpb25zICkubGVuZ3RoID8gd2N2X3NjX3BhcmFtcy5lbGVtZW50c19vcHRpb25zIDoge30sXG5cdFx0c2VwYV9lbGVtZW50c19vcHRpb25zICAgPSBPYmplY3Qua2V5cyggd2N2X3NjX3BhcmFtcy5zZXBhX2VsZW1lbnRzX29wdGlvbnMgKS5sZW5ndGggPyB3Y3Zfc2NfcGFyYW1zLnNlcGFfZWxlbWVudHNfb3B0aW9ucyA6IHt9LFxuXHRcdGVsZW1lbnRzICAgICAgICAgICAgICAgID0gc3RyaXBlLmVsZW1lbnRzKCBzdHJpcGVfZWxlbWVudHNfb3B0aW9ucyApLFxuXHRcdHN0cmlwZV9jYXJkLFxuXHRcdHN0cmlwZV9leHAsXG5cdFx0c3RyaXBlX2N2YztcblxuXHQvKipcblx0ICogT2JqZWN0IHRvIGhhbmRsZSBTdHJpcGUgZWxlbWVudHMgcGF5bWVudCBmb3JtLlxuXHQgKi9cblx0dmFyIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtID0ge1xuXHRcdC8qKlxuXHRcdCAqIEdldCBXQyBBSkFYIGVuZHBvaW50IFVSTC5cblx0XHQgKlxuXHRcdCAqIEBwYXJhbSAge1N0cmluZ30gZW5kcG9pbnQgRW5kcG9pbnQuXG5cdFx0ICogQHJldHVybiB7U3RyaW5nfVxuXHRcdCAqL1xuXHRcdGdldEFqYXhVUkw6IGZ1bmN0aW9uKCBlbmRwb2ludCApIHtcblx0XHRcdHJldHVybiB3Y3Zfc2NfcGFyYW1zLmFqYXh1cmxcblx0XHRcdFx0LnRvU3RyaW5nKClcblx0XHRcdFx0LnJlcGxhY2UoICclJWVuZHBvaW50JSUnLCAnd2N2X3NjXycgKyBlbmRwb2ludCApO1xuXHRcdH0sXG5cblx0XHR1bm1vdW50RWxlbWVudHM6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pbmxpbmVfY2NfZm9ybSApIHtcblx0XHRcdFx0c3RyaXBlX2NhcmQudW5tb3VudCggJyNzdHJpcGUtY29ubmVjdC1jYXJkLWVsZW1lbnQnICk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRzdHJpcGVfY2FyZC51bm1vdW50KCAnI3N0cmlwZS1jb25uZWN0LWNhcmQtZWxlbWVudCcgKTtcblx0XHRcdFx0c3RyaXBlX2V4cC51bm1vdW50KCAnI3N0cmlwZS1jb25uZWN0LWV4cC1lbGVtZW50JyApO1xuXHRcdFx0XHRzdHJpcGVfY3ZjLnVubW91bnQoICcjc3RyaXBlLWNvbm5lY3QtY3ZjLWVsZW1lbnQnICk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdG1vdW50RWxlbWVudHM6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCAhICQoICcjc3RyaXBlLWNvbm5lY3QtY2FyZC1lbGVtZW50JyApLmxlbmd0aCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlubGluZV9jY19mb3JtICkge1xuXHRcdFx0XHRzdHJpcGVfY2FyZC5tb3VudCggJyNzdHJpcGUtY29ubmVjdC1jYXJkLWVsZW1lbnQnICk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRzdHJpcGVfY2FyZC5tb3VudCggJyNzdHJpcGUtY29ubmVjdC1jYXJkLWVsZW1lbnQnICk7XG5cdFx0XHRcdHN0cmlwZV9leHAubW91bnQoICcjc3RyaXBlLWNvbm5lY3QtZXhwLWVsZW1lbnQnICk7XG5cdFx0XHRcdHN0cmlwZV9jdmMubW91bnQoICcjc3RyaXBlLWNvbm5lY3QtY3ZjLWVsZW1lbnQnICk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdGNyZWF0ZUVsZW1lbnRzOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBlbGVtZW50U3R5bGVzID0ge1xuXHRcdFx0XHRiYXNlOiB7XG5cdFx0XHRcdFx0aWNvbkNvbG9yOiAnIzY2NkVFOCcsXG5cdFx0XHRcdFx0Y29sb3I6ICcjMzEzMjVGJyxcblx0XHRcdFx0XHRmb250U2l6ZTogJzE1cHgnLFxuXHRcdFx0XHRcdCc6OnBsYWNlaG9sZGVyJzoge1xuXHRcdFx0XHQgIFx0XHRjb2xvcjogJyNDRkQ3RTAnLFxuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fTtcblxuXHRcdFx0dmFyIGVsZW1lbnRDbGFzc2VzID0ge1xuXHRcdFx0XHRmb2N1czogJ2ZvY3VzZWQnLFxuXHRcdFx0XHRlbXB0eTogJ2VtcHR5Jyxcblx0XHRcdFx0aW52YWxpZDogJ2ludmFsaWQnLFxuXHRcdFx0fTtcblxuXHRcdFx0ZWxlbWVudFN0eWxlcyAgPSB3Y3Zfc2NfcGFyYW1zLmVsZW1lbnRzX3N0eWxpbmcgPyB3Y3Zfc2NfcGFyYW1zLmVsZW1lbnRzX3N0eWxpbmcgOiBlbGVtZW50U3R5bGVzO1xuXHRcdFx0ZWxlbWVudENsYXNzZXMgPSB3Y3Zfc2NfcGFyYW1zLmVsZW1lbnRzX2NsYXNzZXMgPyB3Y3Zfc2NfcGFyYW1zLmVsZW1lbnRzX2NsYXNzZXMgOiBlbGVtZW50Q2xhc3NlcztcblxuXHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pbmxpbmVfY2NfZm9ybSApIHtcblx0XHRcdFx0c3RyaXBlX2NhcmQgPSBlbGVtZW50cy5jcmVhdGUoICdjYXJkJywgeyBzdHlsZTogZWxlbWVudFN0eWxlcywgaGlkZVBvc3RhbENvZGU6IHRydWUgfSApO1xuXG5cdFx0XHRcdHN0cmlwZV9jYXJkLmFkZEV2ZW50TGlzdGVuZXIoICdjaGFuZ2UnLCBmdW5jdGlvbiggZXZlbnQgKSB7XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ub25DQ0Zvcm1DaGFuZ2UoKTtcblxuXHRcdFx0XHRcdGlmICggZXZlbnQuZXJyb3IgKSB7XG5cdFx0XHRcdFx0XHQkKCBkb2N1bWVudC5ib2R5ICkudHJpZ2dlciggJ3N0cmlwZUVycm9yJywgZXZlbnQgKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0gKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHN0cmlwZV9jYXJkID0gZWxlbWVudHMuY3JlYXRlKCAnY2FyZE51bWJlcicsIHsgc3R5bGU6IGVsZW1lbnRTdHlsZXMsIGNsYXNzZXM6IGVsZW1lbnRDbGFzc2VzIH0gKTtcblx0XHRcdFx0c3RyaXBlX2V4cCAgPSBlbGVtZW50cy5jcmVhdGUoICdjYXJkRXhwaXJ5JywgeyBzdHlsZTogZWxlbWVudFN0eWxlcywgY2xhc3NlczogZWxlbWVudENsYXNzZXMgfSApO1xuXHRcdFx0XHRzdHJpcGVfY3ZjICA9IGVsZW1lbnRzLmNyZWF0ZSggJ2NhcmRDdmMnLCB7IHN0eWxlOiBlbGVtZW50U3R5bGVzLCBjbGFzc2VzOiBlbGVtZW50Q2xhc3NlcyB9ICk7XG5cblx0XHRcdFx0c3RyaXBlX2NhcmQuYWRkRXZlbnRMaXN0ZW5lciggJ2NoYW5nZScsIGZ1bmN0aW9uKCBldmVudCApIHtcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vbkNDRm9ybUNoYW5nZSgpO1xuXG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0udXBkYXRlQ2FyZEJyYW5kKCBldmVudC5icmFuZCApO1xuXG5cdFx0XHRcdFx0aWYgKCBldmVudC5lcnJvciApIHtcblx0XHRcdFx0XHRcdCQoIGRvY3VtZW50LmJvZHkgKS50cmlnZ2VyKCAnc3RyaXBlRXJyb3InLCBldmVudCApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdHN0cmlwZV9leHAuYWRkRXZlbnRMaXN0ZW5lciggJ2NoYW5nZScsIGZ1bmN0aW9uKCBldmVudCApIHtcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vbkNDRm9ybUNoYW5nZSgpO1xuXG5cdFx0XHRcdFx0aWYgKCBldmVudC5lcnJvciApIHtcblx0XHRcdFx0XHRcdCQoIGRvY3VtZW50LmJvZHkgKS50cmlnZ2VyKCAnc3RyaXBlRXJyb3InLCBldmVudCApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdHN0cmlwZV9jdmMuYWRkRXZlbnRMaXN0ZW5lciggJ2NoYW5nZScsIGZ1bmN0aW9uKCBldmVudCApIHtcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vbkNDRm9ybUNoYW5nZSgpO1xuXG5cdFx0XHRcdFx0aWYgKCBldmVudC5lcnJvciApIHtcblx0XHRcdFx0XHRcdCQoIGRvY3VtZW50LmJvZHkgKS50cmlnZ2VyKCAnc3RyaXBlRXJyb3InLCBldmVudCApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSApO1xuXHRcdFx0fVxuXG5cdFx0XHQvKipcblx0XHRcdCAqIE9ubHkgaW4gY2hlY2tvdXQgcGFnZSB3ZSBuZWVkIHRvIGRlbGF5IHRoZSBtb3VudGluZyBvZiB0aGVcblx0XHRcdCAqIGNhcmQgYXMgc29tZSBBSkFYIHByb2Nlc3MgbmVlZHMgdG8gaGFwcGVuIGJlZm9yZSB3ZSBkby5cblx0XHRcdCAqL1xuXHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19jaGVja291dCApIHtcblx0XHRcdFx0JCggZG9jdW1lbnQuYm9keSApLm9uKCAndXBkYXRlZF9jaGVja291dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdC8vIERvbid0IG1vdW50IGVsZW1lbnRzIGEgc2Vjb25kIHRpbWUuXG5cdFx0XHRcdFx0aWYgKCBzdHJpcGVfY2FyZCApIHtcblx0XHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnVubW91bnRFbGVtZW50cygpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLm1vdW50RWxlbWVudHMoKTtcblxuXHRcdFx0XHRcdC8vIGlmICggJCggJyNzdHJpcGUtaWJhbi1lbGVtZW50JyApLmxlbmd0aCApIHtcblx0XHRcdFx0XHQvLyBcdGliYW4ubW91bnQoICcjc3RyaXBlLWliYW4tZWxlbWVudCcgKTtcblx0XHRcdFx0XHQvLyB9XG5cdFx0XHRcdH0gKTtcblx0XHRcdH0gZWxzZSBpZiAoICQoICdmb3JtI2FkZF9wYXltZW50X21ldGhvZCcgKS5sZW5ndGggfHwgJCggJ2Zvcm0jb3JkZXJfcmV2aWV3JyApLmxlbmd0aCApIHtcblx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ubW91bnRFbGVtZW50cygpO1xuXG5cdFx0XHRcdC8vIGlmICggJCggJyNzdHJpcGUtaWJhbi1lbGVtZW50JyApLmxlbmd0aCApIHtcblx0XHRcdFx0Ly8gXHRpYmFuLm1vdW50KCAnI3N0cmlwZS1pYmFuLWVsZW1lbnQnICk7XG5cdFx0XHRcdC8vIH1cblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0dXBkYXRlQ2FyZEJyYW5kOiBmdW5jdGlvbiggYnJhbmQgKSB7XG5cdFx0XHR2YXIgYnJhbmRDbGFzcyA9IHtcblx0XHRcdFx0J3Zpc2EnOiAnc3RyaXBlLXZpc2EtYnJhbmQnLFxuXHRcdFx0XHQnbWFzdGVyY2FyZCc6ICdzdHJpcGUtbWFzdGVyY2FyZC1icmFuZCcsXG5cdFx0XHRcdCdhbWV4JzogJ3N0cmlwZS1hbWV4LWJyYW5kJyxcblx0XHRcdFx0J2Rpc2NvdmVyJzogJ3N0cmlwZS1kaXNjb3Zlci1icmFuZCcsXG5cdFx0XHRcdCdkaW5lcnMnOiAnc3RyaXBlLWRpbmVycy1icmFuZCcsXG5cdFx0XHRcdCdqY2InOiAnc3RyaXBlLWpjYi1icmFuZCcsXG5cdFx0XHRcdCd1bmtub3duJzogJ3N0cmlwZS1jcmVkaXQtY2FyZC1icmFuZCdcblx0XHRcdH07XG5cblx0XHRcdHZhciBpbWFnZUVsZW1lbnQgPSAkKCAnLnN0cmlwZS1jYXJkLWJyYW5kJyApLFxuXHRcdFx0XHRpbWFnZUNsYXNzID0gJ3N0cmlwZS1jcmVkaXQtY2FyZC1icmFuZCc7XG5cblx0XHRcdGlmICggYnJhbmQgaW4gYnJhbmRDbGFzcyApIHtcblx0XHRcdFx0aW1hZ2VDbGFzcyA9IGJyYW5kQ2xhc3NbIGJyYW5kIF07XG5cdFx0XHR9XG5cblx0XHRcdC8vIFJlbW92ZSBleGlzdGluZyBjYXJkIGJyYW5kIGNsYXNzLlxuXHRcdFx0JC5lYWNoKCBicmFuZENsYXNzLCBmdW5jdGlvbiggaW5kZXgsIGVsICkge1xuXHRcdFx0XHRpbWFnZUVsZW1lbnQucmVtb3ZlQ2xhc3MoIGVsICk7XG5cdFx0XHR9ICk7XG5cblx0XHRcdGltYWdlRWxlbWVudC5hZGRDbGFzcyggaW1hZ2VDbGFzcyApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBJbml0aWFsaXplIGV2ZW50IGhhbmRsZXJzIGFuZCBVSSBzdGF0ZS5cblx0XHQgKi9cblx0XHRpbml0OiBmdW5jdGlvbigpIHtcblx0XHRcdC8vIEluaXRpYWxpemUgdG9rZW5pemF0aW9uIHNjcmlwdCBpZiBvbiBjaGFuZ2UgcGF5bWVudCBtZXRob2QgcGFnZSBhbmQgcGF5IGZvciBvcmRlciBwYWdlLlxuXHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19jaGFuZ2VfcGF5bWVudF9wYWdlIHx8ICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX3BheV9mb3Jfb3JkZXJfcGFnZSApIHtcblx0XHRcdFx0JCggZG9jdW1lbnQuYm9keSApLnRyaWdnZXIoICd3Yy1jcmVkaXQtY2FyZC1mb3JtLWluaXQnICk7XG5cdFx0XHR9XG5cblx0XHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRcdHRoaXMuc3RyaXBlX2NoZWNrb3V0X3N1Ym1pdCA9IGZhbHNlO1xuXG5cdFx0XHQvLyBjaGVja291dCBwYWdlXG5cdFx0XHRpZiAoICQoICdmb3JtLndvb2NvbW1lcmNlLWNoZWNrb3V0JyApLmxlbmd0aCApIHtcblx0XHRcdFx0dGhpcy5mb3JtID0gJCggJ2Zvcm0ud29vY29tbWVyY2UtY2hlY2tvdXQnICk7XG5cdFx0XHR9XG5cblx0XHRcdCQoICdmb3JtLndvb2NvbW1lcmNlLWNoZWNrb3V0JyApXG5cdFx0XHRcdC5vbihcblx0XHRcdFx0XHQnY2hlY2tvdXRfcGxhY2Vfb3JkZXJfc3RyaXBlIGNoZWNrb3V0X3BsYWNlX29yZGVyX3N0cmlwZV9iYW5jb250YWN0IGNoZWNrb3V0X3BsYWNlX29yZGVyX3N0cmlwZV9zb2ZvcnQgY2hlY2tvdXRfcGxhY2Vfb3JkZXJfc3RyaXBlX2dpcm9wYXkgY2hlY2tvdXRfcGxhY2Vfb3JkZXJfc3RyaXBlX2lkZWFsIGNoZWNrb3V0X3BsYWNlX29yZGVyX3N0cmlwZV9hbGlwYXkgY2hlY2tvdXRfcGxhY2Vfb3JkZXJfc3RyaXBlX3NlcGEnLFxuXHRcdFx0XHRcdHRoaXMub25TdWJtaXRcblx0XHRcdFx0KTtcblxuXHRcdFx0Ly8gcGF5IG9yZGVyIHBhZ2Vcblx0XHRcdGlmICggJCggJ2Zvcm0jb3JkZXJfcmV2aWV3JyApLmxlbmd0aCApIHtcblx0XHRcdFx0dGhpcy5mb3JtID0gJCggJ2Zvcm0jb3JkZXJfcmV2aWV3JyApO1xuXHRcdFx0fVxuXG5cdFx0XHQkKCAnZm9ybSNvcmRlcl9yZXZpZXcsIGZvcm0jYWRkX3BheW1lbnRfbWV0aG9kJyApXG5cdFx0XHRcdC5vbihcblx0XHRcdFx0XHQnc3VibWl0Jyxcblx0XHRcdFx0XHR0aGlzLm9uU3VibWl0XG5cdFx0XHRcdCk7XG5cblx0XHRcdC8vIGFkZCBwYXltZW50IG1ldGhvZCBwYWdlXG5cdFx0XHRpZiAoICQoICdmb3JtI2FkZF9wYXltZW50X21ldGhvZCcgKS5sZW5ndGggKSB7XG5cdFx0XHRcdHRoaXMuZm9ybSA9ICQoICdmb3JtI2FkZF9wYXltZW50X21ldGhvZCcgKTtcblx0XHRcdH1cblxuXHRcdFx0JCggJ2Zvcm0ud29vY29tbWVyY2UtY2hlY2tvdXQnIClcblx0XHRcdFx0Lm9uKFxuXHRcdFx0XHRcdCdjaGFuZ2UnLFxuXHRcdFx0XHRcdHRoaXMucmVzZXRcblx0XHRcdFx0KTtcblxuXHRcdFx0JCggZG9jdW1lbnQgKVxuXHRcdFx0XHQub24oXG5cdFx0XHRcdFx0J3N0cmlwZUVycm9yJyxcblx0XHRcdFx0XHR0aGlzLm9uRXJyb3Jcblx0XHRcdFx0KVxuXHRcdFx0XHQub24oXG5cdFx0XHRcdFx0J2NoZWNrb3V0X2Vycm9yJyxcblx0XHRcdFx0XHR0aGlzLnJlc2V0XG5cdFx0XHRcdCk7XG5cblx0XHRcdC8vIC8vIFNFUEEgSUJBTi5cblx0XHRcdC8vIGliYW4ub24oICdjaGFuZ2UnLFxuXHRcdFx0Ly8gXHR0aGlzLm9uU2VwYUVycm9yXG5cdFx0XHQvLyApO1xuXG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5jcmVhdGVFbGVtZW50cygpO1xuXG5cdFx0XHRpZiAoICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX3N0cmlwZV9jaGVja291dCApIHtcblx0XHRcdFx0JCggZG9jdW1lbnQuYm9keSApLm9uKCAnY2xpY2snLCAnLndjLXN0cmlwZS1jaGVja291dC1idXR0b24nLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vcGVuTW9kYWwoKTtcblx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdH0gKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0Ly8gQ2hlY2sgdG8gc2VlIGlmIFN0cmlwZSBpbiBnZW5lcmFsIGlzIGJlaW5nIHVzZWQgZm9yIGNoZWNrb3V0LlxuXHRcdGlzU3RyaXBlQ2hvc2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0LCAjcGF5bWVudF9tZXRob2Rfc3RyaXBlLWNvbm5lY3RfYmFuY29udGFjdCwgI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X3NvZm9ydCwgI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X2dpcm9wYXksICNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9pZGVhbCwgI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X2FsaXBheSwgI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X3NlcGEsICNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9lcHMsICNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9tdWx0aWJhbmNvJyApLmlzKCAnOmNoZWNrZWQnICkgfHwgKCAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0JyApLmlzKCAnOmNoZWNrZWQnICkgJiYgJ25ldycgPT09ICQoICdpbnB1dFtuYW1lPVwid2Mtc3RyaXBlLXBheW1lbnQtdG9rZW5cIl06Y2hlY2tlZCcgKS52YWwoKSApIHx8ICggJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9zZXBhJyApLmlzKCAnOmNoZWNrZWQnICkgJiYgJ25ldycgPT09ICQoICdpbnB1dFtuYW1lPVwid2Mtc3RyaXBlLXBheW1lbnQtdG9rZW5cIl06Y2hlY2tlZCcgKS52YWwoKSApO1xuXHRcdH0sXG5cblx0XHQvLyBDdXJyZW50bHkgb25seSBzdXBwb3J0IHNhdmVkIGNhcmRzIHZpYSBjcmVkaXQgY2FyZHMgYW5kIFNFUEEuIE5vIG90aGVyIHBheW1lbnQgbWV0aG9kLlxuXHRcdGlzU3RyaXBlU2F2ZUNhcmRDaG9zZW46IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuICggJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdCcgKS5pcyggJzpjaGVja2VkJyApICYmICggJCggJ2lucHV0W25hbWU9XCJ3Yy1zdHJpcGUtcGF5bWVudC10b2tlblwiXScgKS5pcyggJzpjaGVja2VkJyApICYmICduZXcnICE9PSAkKCAnaW5wdXRbbmFtZT1cIndjLXN0cmlwZS1wYXltZW50LXRva2VuXCJdOmNoZWNrZWQnICkudmFsKCkgKSApIHx8XG5cdFx0XHRcdCggJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9zZXBhJyApLmlzKCAnOmNoZWNrZWQnICkgJiYgKCAkKCAnaW5wdXRbbmFtZT1cIndjLXN0cmlwZV9zZXBhLXBheW1lbnQtdG9rZW5cIl0nICkuaXMoICc6Y2hlY2tlZCcgKSAmJiAnbmV3JyAhPT0gJCggJ2lucHV0W25hbWU9XCJ3Yy1zdHJpcGVfc2VwYS1wYXltZW50LXRva2VuXCJdOmNoZWNrZWQnICkudmFsKCkgKSApO1xuXHRcdH0sXG5cblx0XHQvLyBTdHJpcGUgY3JlZGl0IGNhcmQgdXNlZC5cblx0XHRpc1N0cmlwZUNhcmRDaG9zZW46IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuICQoICcjcGF5bWVudF9tZXRob2Rfc3RyaXBlLWNvbm5lY3QnICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aXNCYW5jb250YWN0Q2hvc2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X2JhbmNvbnRhY3QnICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aXNHaXJvcGF5Q2hvc2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X2dpcm9wYXknICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aXNJZGVhbENob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9pZGVhbCcgKS5pcyggJzpjaGVja2VkJyApO1xuXHRcdH0sXG5cblx0XHRpc1NvZm9ydENob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9zb2ZvcnQnICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aXNBbGlwYXlDaG9zZW46IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuICQoICcjcGF5bWVudF9tZXRob2Rfc3RyaXBlLWNvbm5lY3RfYWxpcGF5JyApLmlzKCAnOmNoZWNrZWQnICk7XG5cdFx0fSxcblxuXHRcdGlzU2VwYUNob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9zZXBhJyApLmlzKCAnOmNoZWNrZWQnICk7XG5cdFx0fSxcblxuXHRcdGlzUDI0Q2hvc2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X3AyNCcgKS5pcyggJzpjaGVja2VkJyApO1xuXHRcdH0sXG5cblx0XHRpc0Vwc0Nob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCggJyNwYXltZW50X21ldGhvZF9zdHJpcGUtY29ubmVjdF9lcHMnICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aXNNdWx0aWJhbmNvQ2hvc2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0X211bHRpYmFuY28nICkuaXMoICc6Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0aGFzU291cmNlOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAwIDwgJCggJ2lucHV0LnN0cmlwZS1jb25uZWN0LXNvdXJjZScgKS5sZW5ndGg7XG5cdFx0fSxcblxuXHRcdC8vIExlZ2FjeVxuXHRcdGhhc1Rva2VuOiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAwIDwgJCggJ2lucHV0LnN0cmlwZV9jb25uZWN0X3Rva2VuJyApLmxlbmd0aDtcblx0XHR9LFxuXG5cdFx0aXNNb2JpbGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYoIC9BbmRyb2lkfHdlYk9TfGlQaG9uZXxpUGFkfGlQb2R8QmxhY2tCZXJyeXxJRU1vYmlsZXxPcGVyYSBNaW5pL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50KSApIHtcblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9LFxuXG5cdFx0aXNTdHJpcGVNb2RhbE5lZWRlZDogZnVuY3Rpb24oIGUgKSB7XG5cdFx0XHR2YXIgdG9rZW4gPSB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLmZpbmQoICdpbnB1dC5zdHJpcGVfY29ubmVjdF90b2tlbicgKSxcblx0XHRcdFx0JHJlcXVpcmVkX2lucHV0cztcblxuXHRcdFx0Ly8gSWYgdGhpcyBpcyBhIHN0cmlwZSBzdWJtaXNzaW9uIChhZnRlciBtb2RhbCkgYW5kIHRva2VuIGV4aXN0cywgYWxsb3cgc3VibWl0LlxuXHRcdFx0aWYgKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zdHJpcGVfc3VibWl0ICYmIHRva2VuICkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdC8vIERvbid0IGFmZmVjdCBzdWJtaXNzaW9uIGlmIG1vZGFsIGlzIG5vdCBuZWVkZWQuXG5cdFx0XHRpZiAoICEgd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDaG9zZW4oKSApIHtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHR9LFxuXG5cdFx0YmxvY2s6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCAhIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzTW9iaWxlKCkgKSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0uYmxvY2soIHtcblx0XHRcdFx0XHRtZXNzYWdlOiBudWxsLFxuXHRcdFx0XHRcdG92ZXJsYXlDU1M6IHtcblx0XHRcdFx0XHRcdGJhY2tncm91bmQ6ICcjZmZmJyxcblx0XHRcdFx0XHRcdG9wYWNpdHk6IDAuNlxuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSApO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHR1bmJsb2NrOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0udW5ibG9jaygpO1xuXHRcdH0sXG5cblx0XHRnZXRTZWxlY3RlZFBheW1lbnRFbGVtZW50OiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCAnLnBheW1lbnRfbWV0aG9kcyBpbnB1dFtuYW1lPVwicGF5bWVudF9tZXRob2RcIl06Y2hlY2tlZCcgKTtcblx0XHR9LFxuXG5cdFx0Ly8gU3RyaXBlIENoZWNrb3V0LlxuXHRcdG9wZW5Nb2RhbDogZnVuY3Rpb24oKSB7XG5cdFx0XHQvLyBDYXB0dXJlIHN1Ym1pdHRhbCBhbmQgb3BlbiBzdHJpcGVjaGVja291dFxuXHRcdFx0dmFyICRmb3JtID0gd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybSxcblx0XHRcdFx0JGRhdGEgPSAkKCAnI3N0cmlwZS1jb25uZWN0LXBheW1lbnQtZGF0YScgKTtcblxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ucmVzZXQoKTtcblxuXHRcdFx0dmFyIHRva2VuX2FjdGlvbiA9IGZ1bmN0aW9uKCByZXMgKSB7XG5cdFx0XHRcdCRmb3JtLmZpbmQoICdpbnB1dC5zdHJpcGVfY29ubmVjdF9zb3VyY2UnICkucmVtb3ZlKCk7XG5cblx0XHRcdFx0LyogU2luY2Ugc291cmNlIHdhcyBpbnRyb2R1Y2VkIGluIDQuMC4gV2UgbmVlZCB0b1xuXHRcdFx0XHQgKiBjb252ZXJ0IHRoZSB0b2tlbiBpbnRvIGEgc291cmNlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0aWYgKCAndG9rZW4nID09PSByZXMub2JqZWN0ICkge1xuXHRcdFx0XHRcdHN0cmlwZS5jcmVhdGVTb3VyY2UoIHtcblx0XHRcdFx0XHRcdHR5cGU6ICdjYXJkJyxcblx0XHRcdFx0XHRcdHRva2VuOiByZXMuaWQsXG5cdFx0XHRcdFx0fSApLnRoZW4oIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnNvdXJjZVJlc3BvbnNlICk7XG5cdFx0XHRcdH0gZWxzZSBpZiAoICdzb3VyY2UnID09PSByZXMub2JqZWN0ICkge1xuXHRcdFx0XHRcdHZhciByZXNwb25zZSA9IHsgc291cmNlOiByZXMgfTtcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zb3VyY2VSZXNwb25zZSggcmVzcG9uc2UgKTtcblx0XHRcdFx0fVxuXHRcdFx0fTtcblxuXHRcdFx0U3RyaXBlQ2hlY2tvdXQub3Blbigge1xuXHRcdFx0XHRrZXkgICAgICAgICAgICAgICA6IHdjdl9zY19wYXJhbXMua2V5LFxuXHRcdFx0XHRiaWxsaW5nQWRkcmVzcyAgICA6ICRkYXRhLmRhdGEoICdiaWxsaW5nLWFkZHJlc3MnICksXG5cdFx0XHRcdHppcENvZGUgICAgICAgICAgIDogJGRhdGEuZGF0YSggJ3ZlcmlmeS16aXAnICksXG5cdFx0XHRcdGFtb3VudCAgICAgICAgICAgIDogJGRhdGEuZGF0YSggJ2Ftb3VudCcgKSxcblx0XHRcdFx0bmFtZSAgICAgICAgICAgICAgOiAkZGF0YS5kYXRhKCAnbmFtZScgKSxcblx0XHRcdFx0ZGVzY3JpcHRpb24gICAgICAgOiAkZGF0YS5kYXRhKCAnZGVzY3JpcHRpb24nICksXG5cdFx0XHRcdGN1cnJlbmN5ICAgICAgICAgIDogJGRhdGEuZGF0YSggJ2N1cnJlbmN5JyApLFxuXHRcdFx0XHRpbWFnZSAgICAgICAgICAgICA6ICRkYXRhLmRhdGEoICdpbWFnZScgKSxcblx0XHRcdFx0bG9jYWxlICAgICAgICAgICAgOiAkZGF0YS5kYXRhKCAnbG9jYWxlJyApLFxuXHRcdFx0XHRlbWFpbCAgICAgICAgICAgICA6ICQoICcjYmlsbGluZ19lbWFpbCcgKS52YWwoKSB8fCAkZGF0YS5kYXRhKCAnZW1haWwnICksXG5cdFx0XHRcdHBhbmVsTGFiZWwgICAgICAgIDogJGRhdGEuZGF0YSggJ3BhbmVsLWxhYmVsJyApLFxuXHRcdFx0XHRhbGxvd1JlbWVtYmVyTWUgICA6ICRkYXRhLmRhdGEoICdhbGxvdy1yZW1lbWJlci1tZScgKSxcblx0XHRcdFx0dG9rZW4gICAgICAgICAgICAgOiB0b2tlbl9hY3Rpb24sXG5cdFx0XHRcdGNsb3NlZCAgICAgICAgICAgIDogd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ub25DbG9zZSgpXG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRyZXNldE1vZGFsOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnJlc2V0KCk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zdHJpcGVfY2hlY2tvdXRfc3VibWl0ID0gZmFsc2U7XG5cdFx0fSxcblxuXHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRvbkNsb3NlOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnVuYmxvY2soKTtcblx0XHR9LFxuXG5cdFx0Z2V0T3duZXJEZXRhaWxzOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBmaXJzdF9uYW1lID0gJCggJyNiaWxsaW5nX2ZpcnN0X25hbWUnICkubGVuZ3RoID8gJCggJyNiaWxsaW5nX2ZpcnN0X25hbWUnICkudmFsKCkgOiB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfZmlyc3RfbmFtZSxcblx0XHRcdFx0bGFzdF9uYW1lICA9ICQoICcjYmlsbGluZ19sYXN0X25hbWUnICkubGVuZ3RoID8gJCggJyNiaWxsaW5nX2xhc3RfbmFtZScgKS52YWwoKSA6IHdjdl9zY19wYXJhbXMuYmlsbGluZ19sYXN0X25hbWUsXG5cdFx0XHRcdGV4dHJhX2RldGFpbHMgPSB7IG93bmVyOiB7IG5hbWU6ICcnLCBhZGRyZXNzOiB7fSwgZW1haWw6ICcnLCBwaG9uZTogJycgfSB9O1xuXG5cdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLm5hbWUgPSBmaXJzdF9uYW1lO1xuXG5cdFx0XHRpZiAoIGZpcnN0X25hbWUgJiYgbGFzdF9uYW1lICkge1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLm5hbWUgPSBmaXJzdF9uYW1lICsgJyAnICsgbGFzdF9uYW1lO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5uYW1lID0gJCggJyNzdHJpcGUtcGF5bWVudC1kYXRhJyApLmRhdGEoICdmdWxsLW5hbWUnICk7XG5cdFx0XHR9XG5cblx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuZW1haWwgPSAkKCAnI2JpbGxpbmdfZW1haWwnICkudmFsKCk7XG5cdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLnBob25lID0gJCggJyNiaWxsaW5nX3Bob25lJyApLnZhbCgpO1xuXG5cdFx0XHQvKiBTdHJpcGUgZG9lcyBub3QgbGlrZSBlbXB0eSBzdHJpbmcgdmFsdWVzIHNvXG5cdFx0XHQgKiB3ZSBuZWVkIHRvIHJlbW92ZSB0aGUgcGFyYW1ldGVyIGlmIHdlJ3JlIG5vdFxuXHRcdFx0ICogcGFzc2luZyBhbnkgdmFsdWUuXG5cdFx0XHQgKi9cblx0XHRcdGlmICggdHlwZW9mIGV4dHJhX2RldGFpbHMub3duZXIucGhvbmUgPT09ICd1bmRlZmluZWQnIHx8IDAgPj0gZXh0cmFfZGV0YWlscy5vd25lci5waG9uZS5sZW5ndGggKSB7XG5cdFx0XHRcdGRlbGV0ZSBleHRyYV9kZXRhaWxzLm93bmVyLnBob25lO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHR5cGVvZiBleHRyYV9kZXRhaWxzLm93bmVyLmVtYWlsID09PSAndW5kZWZpbmVkJyB8fCAwID49IGV4dHJhX2RldGFpbHMub3duZXIuZW1haWwubGVuZ3RoICkge1xuXHRcdFx0XHRpZiAoICQoICcjc3RyaXBlLWNvbm5lY3QtcGF5bWVudC1kYXRhJyApLmRhdGEoICdlbWFpbCcgKS5sZW5ndGggKSB7XG5cdFx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5lbWFpbCA9ICQoICcjc3RyaXBlLWNvbm5lY3QtcGF5bWVudC1kYXRhJyApLmRhdGEoICdlbWFpbCcgKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRkZWxldGUgZXh0cmFfZGV0YWlscy5vd25lci5lbWFpbDtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHR5cGVvZiBleHRyYV9kZXRhaWxzLm93bmVyLm5hbWUgPT09ICd1bmRlZmluZWQnIHx8IDAgPj0gZXh0cmFfZGV0YWlscy5vd25lci5uYW1lLmxlbmd0aCApIHtcblx0XHRcdFx0ZGVsZXRlIGV4dHJhX2RldGFpbHMub3duZXIubmFtZTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAkKCAnI2JpbGxpbmdfYWRkcmVzc18xJyApLmxlbmd0aCA+IDAgKSB7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5saW5lMSAgICAgICA9ICQoICcjYmlsbGluZ19hZGRyZXNzXzEnICkudmFsKCk7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5saW5lMiAgICAgICA9ICQoICcjYmlsbGluZ19hZGRyZXNzXzInICkudmFsKCk7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5zdGF0ZSAgICAgICA9ICQoICcjYmlsbGluZ19zdGF0ZScgKS52YWwoKTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLmNpdHkgICAgICAgID0gJCggJyNiaWxsaW5nX2NpdHknICkudmFsKCk7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5wb3N0YWxfY29kZSA9ICQoICcjYmlsbGluZ19wb3N0Y29kZScgKS52YWwoKTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLmNvdW50cnkgICAgID0gJCggJyNiaWxsaW5nX2NvdW50cnknICkudmFsKCk7XG5cdFx0XHR9IGVsc2UgaWYgKCB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfYWRkcmVzc18xICkge1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MubGluZTEgICAgICAgPSB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfYWRkcmVzc18xO1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MubGluZTIgICAgICAgPSB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfYWRkcmVzc18yO1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3Muc3RhdGUgICAgICAgPSB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfc3RhdGU7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5jaXR5ICAgICAgICA9IHdjdl9zY19wYXJhbXMuYmlsbGluZ19jaXR5O1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MucG9zdGFsX2NvZGUgPSB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfcG9zdGNvZGU7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5jb3VudHJ5ICAgICA9IHdjdl9zY19wYXJhbXMuYmlsbGluZ19jb3VudHJ5O1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gZXh0cmFfZGV0YWlscztcblx0XHR9LFxuXG5cdFx0Y3JlYXRlU291cmNlOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBleHRyYV9kZXRhaWxzID0gd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZ2V0T3duZXJEZXRhaWxzKCksXG5cdFx0XHRcdHNvdXJjZV90eXBlICAgPSAnY2FyZCc7XG5cblx0XHRcdGlmICggd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNCYW5jb250YWN0Q2hvc2VuKCkgKSB7XG5cdFx0XHRcdHNvdXJjZV90eXBlID0gJ2JhbmNvbnRhY3QnO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzU2VwYUNob3NlbigpICkge1xuXHRcdFx0XHRzb3VyY2VfdHlwZSA9ICdzZXBhX2RlYml0Jztcblx0XHRcdH1cblxuXHRcdFx0aWYgKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc0lkZWFsQ2hvc2VuKCkgKSB7XG5cdFx0XHRcdHNvdXJjZV90eXBlID0gJ2lkZWFsJztcblx0XHRcdH1cblxuXHRcdFx0aWYgKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1NvZm9ydENob3NlbigpICkge1xuXHRcdFx0XHRzb3VyY2VfdHlwZSA9ICdzb2ZvcnQnO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzR2lyb3BheUNob3NlbigpICkge1xuXHRcdFx0XHRzb3VyY2VfdHlwZSA9ICdnaXJvcGF5Jztcblx0XHRcdH1cblxuXHRcdFx0aWYgKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc0FsaXBheUNob3NlbigpICkge1xuXHRcdFx0XHRzb3VyY2VfdHlwZSA9ICdhbGlwYXknO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoICdjYXJkJyA9PT0gc291cmNlX3R5cGUgKSB7XG5cdFx0XHRcdHN0cmlwZS5jcmVhdGVTb3VyY2UoIHN0cmlwZV9jYXJkLCBleHRyYV9kZXRhaWxzICkudGhlbiggd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uc291cmNlUmVzcG9uc2UgKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHN3aXRjaCAoIHNvdXJjZV90eXBlICkge1xuXHRcdFx0XHRcdGNhc2UgJ2JhbmNvbnRhY3QnOlxuXHRcdFx0XHRcdGNhc2UgJ2dpcm9wYXknOlxuXHRcdFx0XHRcdGNhc2UgJ2lkZWFsJzpcblx0XHRcdFx0XHRjYXNlICdzb2ZvcnQnOlxuXHRcdFx0XHRcdGNhc2UgJ2FsaXBheSc6XG5cdFx0XHRcdFx0XHQvLyBUaGVzZSByZWRpcmVjdCBmbG93IHBheW1lbnQgbWV0aG9kcyBuZWVkIHRoaXMgaW5mb3JtYXRpb24gdG8gYmUgc2V0IGF0IHNvdXJjZSBjcmVhdGlvbi5cblx0XHRcdFx0XHRcdGV4dHJhX2RldGFpbHMuYW1vdW50ICAgPSAkKCAnI3N0cmlwZS0nICsgc291cmNlX3R5cGUgKyAnLXBheW1lbnQtZGF0YScgKS5kYXRhKCAnYW1vdW50JyApO1xuXHRcdFx0XHRcdFx0ZXh0cmFfZGV0YWlscy5jdXJyZW5jeSA9ICQoICcjc3RyaXBlLScgKyBzb3VyY2VfdHlwZSArICctcGF5bWVudC1kYXRhJyApLmRhdGEoICdjdXJyZW5jeScgKTtcblx0XHRcdFx0XHRcdGV4dHJhX2RldGFpbHMucmVkaXJlY3QgPSB7IHJldHVybl91cmw6IHdjdl9zY19wYXJhbXMucmV0dXJuX3VybCB9O1xuXG5cdFx0XHRcdFx0XHRpZiAoIHdjdl9zY19wYXJhbXMuc3RhdGVtZW50X2Rlc2NyaXB0b3IgKSB7XG5cdFx0XHRcdFx0XHRcdGV4dHJhX2RldGFpbHMuc3RhdGVtZW50X2Rlc2NyaXB0b3IgPSB3Y3Zfc2NfcGFyYW1zLnN0YXRlbWVudF9kZXNjcmlwdG9yO1xuXHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0fVxuXG5cdFx0XHRcdC8vIEhhbmRsZSBzcGVjaWFsIGlucHV0cyB0aGF0IGFyZSB1bmlxdWUgdG8gYSBwYXltZW50IG1ldGhvZC5cblx0XHRcdFx0c3dpdGNoICggc291cmNlX3R5cGUgKSB7XG5cdFx0XHRcdFx0Y2FzZSAnc2VwYV9kZWJpdCc6XG5cdFx0XHRcdFx0XHRleHRyYV9kZXRhaWxzLmN1cnJlbmN5ID0gJCggJyNzdHJpcGUtJyArIHNvdXJjZV90eXBlICsgJy1wYXltZW50LWRhdGEnICkuZGF0YSggJ2N1cnJlbmN5JyApO1xuXHRcdFx0XHRcdFx0ZXh0cmFfZGV0YWlscy5tYW5kYXRlICA9IHsgbm90aWZpY2F0aW9uX21ldGhvZDogd2N2X3NjX3BhcmFtcy5zZXBhX21hbmRhdGVfbm90aWZpY2F0aW9uIH07XG5cdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRjYXNlICdpZGVhbCc6XG5cdFx0XHRcdFx0XHRleHRyYV9kZXRhaWxzLmlkZWFsID0geyBiYW5rOiAkKCAnI3N0cmlwZS1pZGVhbC1iYW5rJyApLnZhbCgpIH07XG5cdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRjYXNlICdhbGlwYXknOlxuXHRcdFx0XHRcdFx0ZXh0cmFfZGV0YWlscy5jdXJyZW5jeSA9ICQoICcjc3RyaXBlLScgKyBzb3VyY2VfdHlwZSArICctcGF5bWVudC1kYXRhJyApLmRhdGEoICdjdXJyZW5jeScgKTtcblx0XHRcdFx0XHRcdGV4dHJhX2RldGFpbHMuYW1vdW50ID0gJCggJyNzdHJpcGUtJyArIHNvdXJjZV90eXBlICsgJy1wYXltZW50LWRhdGEnICkuZGF0YSggJ2Ftb3VudCcgKTtcblx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdGNhc2UgJ3NvZm9ydCc6XG5cdFx0XHRcdFx0XHRleHRyYV9kZXRhaWxzLnNvZm9ydCA9IHsgY291bnRyeTogJCggJyNiaWxsaW5nX2NvdW50cnknICkudmFsKCkgfTtcblx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0ZXh0cmFfZGV0YWlscy50eXBlID0gc291cmNlX3R5cGU7XG5cblx0XHRcdFx0Ly8gaWYgKCAnc2VwYV9kZWJpdCcgPT09IHNvdXJjZV90eXBlICkge1xuXHRcdFx0XHQvLyBcdHN0cmlwZS5jcmVhdGVTb3VyY2UoIGliYW4sIGV4dHJhX2RldGFpbHMgKS50aGVuKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zb3VyY2VSZXNwb25zZSApO1xuXHRcdFx0XHQvLyB9IGVsc2Uge1xuXHRcdFx0XHRcdHN0cmlwZS5jcmVhdGVTb3VyY2UoIGV4dHJhX2RldGFpbHMgKS50aGVuKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zb3VyY2VSZXNwb25zZSApO1xuXHRcdFx0XHQvLyB9XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdHNvdXJjZVJlc3BvbnNlOiBmdW5jdGlvbiggcmVzcG9uc2UgKSB7XG5cdFx0XHRpZiAoIHJlc3BvbnNlLmVycm9yICkge1xuXHRcdFx0XHQkKCBkb2N1bWVudC5ib2R5ICkudHJpZ2dlciggJ3N0cmlwZUVycm9yJywgcmVzcG9uc2UgKTtcblx0XHRcdH0gZWxzZSBpZiAoICdubycgPT09IHdjdl9zY19wYXJhbXMuYWxsb3dfcHJlcGFpZF9jYXJkICYmICdjYXJkJyA9PT0gcmVzcG9uc2Uuc291cmNlLnR5cGUgJiYgJ3ByZXBhaWQnID09PSByZXNwb25zZS5zb3VyY2UuY2FyZC5mdW5kaW5nICkge1xuXHRcdFx0XHRyZXNwb25zZS5lcnJvciA9IHsgbWVzc2FnZTogd2N2X3NjX3BhcmFtcy5ub19wcmVwYWlkX2NhcmRfbXNnIH07XG5cblx0XHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19zdHJpcGVfY2hlY2tvdXQgKSB7XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uc3VibWl0RXJyb3IoICc8dWwgY2xhc3M9XCJ3b29jb21tZXJjZS1lcnJvclwiPjxsaT4nICsgd2N2X3NjX3BhcmFtcy5ub19wcmVwYWlkX2NhcmRfbXNnICsgJzwvbGk+PC91bD4nICk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0JCggZG9jdW1lbnQuYm9keSApLnRyaWdnZXIoICdzdHJpcGVFcnJvcicsIHJlc3BvbnNlICk7XG5cdFx0XHRcdH1cblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnByb2Nlc3NTdHJpcGVSZXNwb25zZSggcmVzcG9uc2Uuc291cmNlICk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdHByb2Nlc3NTdHJpcGVSZXNwb25zZTogZnVuY3Rpb24oIHNvdXJjZSApIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnJlc2V0KCk7XG5cblx0XHRcdC8vIEluc2VydCB0aGUgU291cmNlIGludG8gdGhlIGZvcm0gc28gaXQgZ2V0cyBzdWJtaXR0ZWQgdG8gdGhlIHNlcnZlci5cblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0uYXBwZW5kKCBcIjxpbnB1dCB0eXBlPSdoaWRkZW4nIGNsYXNzPSdzdHJpcGUtc291cmNlJyBuYW1lPSdzdHJpcGVfc291cmNlJyB2YWx1ZT0nXCIgKyBzb3VyY2UuaWQgKyBcIicvPlwiICk7XG5cblx0XHRcdGlmICggJCggJ2Zvcm0jYWRkX3BheW1lbnRfbWV0aG9kJyApLmxlbmd0aCApIHtcblx0XHRcdFx0JCggd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybSApLm9mZiggJ3N1Ym1pdCcsIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0ub25TdWJtaXQgKTtcblx0XHRcdH1cblxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybS5zdWJtaXQoKTtcblx0XHR9LFxuXG5cdFx0b25TdWJtaXQ6IGZ1bmN0aW9uKCBlICkge1xuXHRcdFx0aWYgKCAhIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzU3RyaXBlQ2hvc2VuKCkgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAhIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzU3RyaXBlU2F2ZUNhcmRDaG9zZW4oKSAmJiAhIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmhhc1NvdXJjZSgpICYmICEgd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaGFzVG9rZW4oKSApIHtcblx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmJsb2NrKCk7XG5cblx0XHRcdFx0Ly8gU3RyaXBlIENoZWNrb3V0LlxuXHRcdFx0XHRpZiAoICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX3N0cmlwZV9jaGVja291dCAmJiB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1N0cmlwZU1vZGFsTmVlZGVkKCkgJiYgd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDYXJkQ2hvc2VuKCkgKSB7XG5cdFx0XHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19jaGVja291dCApIHtcblx0XHRcdFx0XHRcdHJldHVybiB0cnVlO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vcGVuTW9kYWwoKTtcblx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHQvKlxuXHRcdFx0XHQgKiBGb3IgbWV0aG9kcyB0aGF0IG5lZWRzIHJlZGlyZWN0LCB3ZSB3aWxsIGNyZWF0ZSB0aGVcblx0XHRcdFx0ICogc291cmNlIHNlcnZlciBzaWRlIHNvIHdlIGNhbiBvYnRhaW4gdGhlIG9yZGVyIElELlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0aWYgKFxuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzQmFuY29udGFjdENob3NlbigpIHx8XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNHaXJvcGF5Q2hvc2VuKCkgfHxcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc0lkZWFsQ2hvc2VuKCkgfHxcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc0FsaXBheUNob3NlbigpIHx8XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTb2ZvcnRDaG9zZW4oKSB8fFxuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmlzUDI0Q2hvc2VuKCkgfHxcblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc0Vwc0Nob3NlbigpIHx8XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNNdWx0aWJhbmNvQ2hvc2VuKClcblx0XHRcdFx0KSB7XG5cdFx0XHRcdFx0aWYgKCAkKCAnZm9ybSNvcmRlcl9yZXZpZXcnICkubGVuZ3RoICkge1xuXHRcdFx0XHRcdFx0JCggJ2Zvcm0jb3JkZXJfcmV2aWV3JyApXG5cdFx0XHRcdFx0XHRcdC5vZmYoXG5cdFx0XHRcdFx0XHRcdFx0J3N1Ym1pdCcsXG5cdFx0XHRcdFx0XHRcdFx0dGhpcy5vblN1Ym1pdFxuXHRcdFx0XHRcdFx0XHQpO1xuXG5cdFx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLnN1Ym1pdCgpO1xuXG5cdFx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0aWYgKCAkKCAnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcgKS5sZW5ndGggKSB7XG5cdFx0XHRcdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRpZiAoICQoICdmb3JtI2FkZF9wYXltZW50X21ldGhvZCcgKS5sZW5ndGggKSB7XG5cdFx0XHRcdFx0XHQkKCAnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnIClcblx0XHRcdFx0XHRcdFx0Lm9mZihcblx0XHRcdFx0XHRcdFx0XHQnc3VibWl0Jyxcblx0XHRcdFx0XHRcdFx0XHR0aGlzLm9uU3VibWl0XG5cdFx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0uc3VibWl0KCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5jcmVhdGVTb3VyY2UoKTtcblxuXHRcdFx0XHQvLyBQcmV2ZW50IGZvcm0gc3VibWl0dGluZ1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9IGVsc2UgaWYgKCAkKCAnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnICkubGVuZ3RoICkge1xuXHRcdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0XHRcdFx0Ly8gU3RyaXBlIENoZWNrb3V0LlxuXHRcdFx0XHRpZiAoICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX3N0cmlwZV9jaGVja291dCAmJiB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1N0cmlwZU1vZGFsTmVlZGVkKCkgJiYgd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDYXJkQ2hvc2VuKCkgKSB7XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ub3Blbk1vZGFsKCk7XG5cblx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5ibG9jaygpO1xuXG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmNyZWF0ZVNvdXJjZSgpO1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdG9uQ0NGb3JtQ2hhbmdlOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnJlc2V0KCk7XG5cdFx0fSxcblxuXHRcdHJlc2V0OiBmdW5jdGlvbigpIHtcblx0XHRcdCQoICcud2N2LXN0cmlwZS1jb25uZWN0LWVycm9yLCAuc3RyaXBlLXNvdXJjZSwgLnN0cmlwZV90b2tlbicgKS5yZW1vdmUoKTtcblxuXHRcdFx0Ly8gU3RyaXBlIENoZWNrb3V0LlxuXHRcdFx0aWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19zdHJpcGVfY2hlY2tvdXQgKSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnN0cmlwZV9zdWJtaXQgPSBmYWxzZTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0b25TZXBhRXJyb3I6IGZ1bmN0aW9uKCBlICkge1xuXHRcdFx0dmFyIGVycm9yQ29udGFpbmVyID0gd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZ2V0U2VsZWN0ZWRQYXltZW50RWxlbWVudCgpLnBhcmVudHMoICdsaScgKS5lcSgwKS5maW5kKCAnLnN0cmlwZS1jb25uZWN0LXNvdXJjZS1lcnJvcnMnICk7XG5cblx0XHRcdGlmICggZS5lcnJvciApIHtcblx0XHRcdFx0Y29uc29sZS5sb2coIGUuZXJyb3IubWVzc2FnZSApOyAvLyBMZWF2ZSBmb3IgdHJvdWJsZXNob290aW5nLlxuXHRcdFx0XHQkKCBlcnJvckNvbnRhaW5lciApLmh0bWwoICc8dWwgY2xhc3M9XCJ3b29jb21tZXJjZV9lcnJvciB3b29jb21tZXJjZS1lcnJvciB3Y3Ytc3RyaXBlLWNvbm5lY3QtZXJyb3JcIj48bGk+JyArIGUuZXJyb3IubWVzc2FnZSArICc8L2xpPjwvdWw+JyApO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0JCggZXJyb3JDb250YWluZXIgKS5odG1sKCAnJyApO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHRvbkVycm9yOiBmdW5jdGlvbiggZSwgcmVzdWx0ICkge1xuXHRcdFx0dmFyIG1lc3NhZ2UgPSByZXN1bHQuZXJyb3IubWVzc2FnZSxcblx0XHRcdFx0ZXJyb3JDb250YWluZXIgPSB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5nZXRTZWxlY3RlZFBheW1lbnRFbGVtZW50KCkucGFyZW50cyggJ2xpJyApLmVxKDApLmZpbmQoICcuc3RyaXBlLWNvbm5lY3Qtc291cmNlLWVycm9ycycgKTtcblxuXHRcdFx0Lypcblx0XHRcdCAqIElmIHBheW1lbnQgbWV0aG9kIGlzIFNFUEEgYW5kIG93bmVyIG5hbWUgaXMgbm90IGNvbXBsZXRlZCxcblx0XHRcdCAqIHNvdXJjZSBjYW5ub3QgYmUgY3JlYXRlZC4gU28gd2UgbmVlZCB0byBzaG93IHRoZSBub3JtYWxcblx0XHRcdCAqIEJpbGxpbmcgbmFtZSBpcyByZXF1aXJlZCBlcnJvciBtZXNzYWdlIG9uIHRvcCBvZiBmb3JtIGluc3RlYWRcblx0XHRcdCAqIG9mIGlubGluZS5cblx0XHRcdCAqL1xuXHRcdFx0aWYgKCB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1NlcGFDaG9zZW4oKSApIHtcblx0XHRcdFx0aWYgKCAnaW52YWxpZF9vd25lcl9uYW1lJyA9PT0gcmVzdWx0LmVycm9yLmNvZGUgJiYgd2N2X3NjX3BhcmFtcy5oYXNPd25Qcm9wZXJ0eSggcmVzdWx0LmVycm9yLmNvZGUgKSApIHtcblx0XHRcdFx0XHR2YXIgZXJyb3IgPSAnPHVsIGNsYXNzPVwid29vY29tbWVyY2UtZXJyb3JcIj48bGk+JyArIHdjdl9zY19wYXJhbXNbIHJlc3VsdC5lcnJvci5jb2RlIF0gKyAnPC9saT48L3VsPic7XG5cblx0XHRcdFx0XHRyZXR1cm4gd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uc3VibWl0RXJyb3IoIGVycm9yICk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0Lypcblx0XHRcdCAqIEN1c3RvbWVycyBkbyBub3QgbmVlZCB0byBrbm93IHRoZSBzcGVjaWZpY3Mgb2YgdGhlIGJlbG93IHR5cGUgb2YgZXJyb3JzXG5cdFx0XHQgKiB0aGVyZWZvcmUgcmV0dXJuIGEgZ2VuZXJpYyBsb2NhbGl6YWJsZSBlcnJvciBtZXNzYWdlLlxuXHRcdFx0ICovXG5cdFx0XHRpZiAoXG5cdFx0XHRcdCdpbnZhbGlkX3JlcXVlc3RfZXJyb3InID09PSByZXN1bHQuZXJyb3IudHlwZSB8fFxuXHRcdFx0XHQnYXBpX2Nvbm5lY3Rpb25fZXJyb3InICA9PT0gcmVzdWx0LmVycm9yLnR5cGUgfHxcblx0XHRcdFx0J2FwaV9lcnJvcicgICAgICAgICAgICAgPT09IHJlc3VsdC5lcnJvci50eXBlIHx8XG5cdFx0XHRcdCdhdXRoZW50aWNhdGlvbl9lcnJvcicgID09PSByZXN1bHQuZXJyb3IudHlwZSB8fFxuXHRcdFx0XHQncmF0ZV9saW1pdF9lcnJvcicgICAgICA9PT0gcmVzdWx0LmVycm9yLnR5cGVcblx0XHRcdCkge1xuXHRcdFx0XHRtZXNzYWdlID0gd2N2X3NjX3BhcmFtcy5pbnZhbGlkX3JlcXVlc3RfZXJyb3I7XG5cdFx0XHR9XG5cblx0XHRcdGlmICggJ2NhcmRfZXJyb3InID09PSByZXN1bHQuZXJyb3IudHlwZSAmJiB3Y3Zfc2NfcGFyYW1zLmhhc093blByb3BlcnR5KCByZXN1bHQuZXJyb3IuY29kZSApICkge1xuXHRcdFx0XHRtZXNzYWdlID0gd2N2X3NjX3BhcmFtc1sgcmVzdWx0LmVycm9yLmNvZGUgXTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAndmFsaWRhdGlvbl9lcnJvcicgPT09IHJlc3VsdC5lcnJvci50eXBlICYmIHdjdl9zY19wYXJhbXMuaGFzT3duUHJvcGVydHkoIHJlc3VsdC5lcnJvci5jb2RlICkgKSB7XG5cdFx0XHRcdG1lc3NhZ2UgPSB3Y3Zfc2NfcGFyYW1zWyByZXN1bHQuZXJyb3IuY29kZSBdO1xuXHRcdFx0fVxuXG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5yZXNldCgpO1xuXHRcdFx0JCggJy53b29jb21tZXJjZS1Ob3RpY2VHcm91cC1jaGVja291dCcgKS5yZW1vdmUoKTtcblx0XHRcdGNvbnNvbGUubG9nKCByZXN1bHQuZXJyb3IubWVzc2FnZSApOyAvLyBMZWF2ZSBmb3IgdHJvdWJsZXNob290aW5nLlxuXHRcdFx0JCggZXJyb3JDb250YWluZXIgKS5odG1sKCAnPHVsIGNsYXNzPVwid29vY29tbWVyY2VfZXJyb3Igd29vY29tbWVyY2UtZXJyb3Igd2N2LXN0cmlwZS1jb25uZWN0LWVycm9yXCI+PGxpPicgKyBtZXNzYWdlICsgJzwvbGk+PC91bD4nICk7XG5cblx0XHRcdGlmICggJCggJy53Y3Ytc3RyaXBlLWNvbm5lY3QtZXJyb3InICkubGVuZ3RoICkge1xuXHRcdFx0XHQkKCAnaHRtbCwgYm9keScgKS5hbmltYXRlKHtcblx0XHRcdFx0XHRzY3JvbGxUb3A6ICggJCggJy53Y3Ytc3RyaXBlLWNvbm5lY3QtZXJyb3InICkub2Zmc2V0KCkudG9wIC0gMjAwIClcblx0XHRcdFx0fSwgMjAwICk7XG5cdFx0XHR9XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS51bmJsb2NrKCk7XG5cdFx0fSxcblxuXHRcdHN1Ym1pdEVycm9yOiBmdW5jdGlvbiggZXJyb3JfbWVzc2FnZSApIHtcblx0XHRcdCQoICcud29vY29tbWVyY2UtTm90aWNlR3JvdXAtY2hlY2tvdXQsIC53b29jb21tZXJjZS1lcnJvciwgLndvb2NvbW1lcmNlLW1lc3NhZ2UnICkucmVtb3ZlKCk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLnByZXBlbmQoICc8ZGl2IGNsYXNzPVwid29vY29tbWVyY2UtTm90aWNlR3JvdXAgd29vY29tbWVyY2UtTm90aWNlR3JvdXAtY2hlY2tvdXRcIj4nICsgZXJyb3JfbWVzc2FnZSArICc8L2Rpdj4nICk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLnJlbW92ZUNsYXNzKCAncHJvY2Vzc2luZycgKS51bmJsb2NrKCk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLmZpbmQoICcuaW5wdXQtdGV4dCwgc2VsZWN0LCBpbnB1dDpjaGVja2JveCcgKS5ibHVyKCk7XG5cblx0XHRcdHZhciBzZWxlY3RvciA9ICcnO1xuXG5cdFx0XHRpZiAoICQoICcjYWRkX3BheW1lbnRfbWV0aG9kJyApLmxlbmd0aCApIHtcblx0XHRcdFx0c2VsZWN0b3IgPSAkKCAnI2FkZF9wYXltZW50X21ldGhvZCcgKTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAkKCAnI29yZGVyX3JldmlldycgKS5sZW5ndGggKSB7XG5cdFx0XHRcdHNlbGVjdG9yID0gJCggJyNvcmRlcl9yZXZpZXcnICk7XG5cdFx0XHR9XG5cblx0XHRcdGlmICggJCggJ2Zvcm0uY2hlY2tvdXQnICkubGVuZ3RoICkge1xuXHRcdFx0XHRzZWxlY3RvciA9ICQoICdmb3JtLmNoZWNrb3V0JyApO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHNlbGVjdG9yLmxlbmd0aCApIHtcblx0XHRcdFx0JCggJ2h0bWwsIGJvZHknICkuYW5pbWF0ZSh7XG5cdFx0XHRcdFx0c2Nyb2xsVG9wOiAoIHNlbGVjdG9yLm9mZnNldCgpLnRvcCAtIDEwMCApXG5cdFx0XHRcdH0sIDUwMCApO1xuXHRcdFx0fVxuXG5cdFx0XHQkKCBkb2N1bWVudC5ib2R5ICkudHJpZ2dlciggJ2NoZWNrb3V0X2Vycm9yJyApO1xuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0udW5ibG9jaygpO1xuXHRcdH1cblx0fTtcblxuXHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pbml0KCk7XG59ICk7XG4iXX0=

//# sourceMappingURL=stripe-connect.js.map
