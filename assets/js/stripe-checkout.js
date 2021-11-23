(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

/* globals iban Stripe wcv_sc_params StripeCheckout */
jQuery(function ($) {
  'use strict';

  try {
    var stripe = Stripe(wcv_sc_params.key);
  } catch (error) {
    // eslint-disable-next-line
    console.log(error);
    return;
  }

  var stripe_elements_options = Object.keys(wcv_sc_params.elements_options).length ? wcv_sc_params.elements_options : {},
      elements = stripe.elements(stripe_elements_options),
      stripe_card,
      stripe_exp,
      stripe_cvc;
  /**
   * Object to handle Stripe elements payment form.
   */

  var wcv_stripe_connect_form = {
    /**
     * Initialize event handlers and UI state.
     */
    init: function init() {
      // if ( 'yes' === wcv_sc_params.is_change_payment_page || 'yes' === wcv_sc_params.is_pay_for_order_page ) {
      // 	$( document.body ).trigger( 'wc-credit-card-form-init' );
      // }
      // Stripe Checkout.
      this.stripe_checkout_submit = false; // checkout page

      if ($('form.woocommerce-checkout').length) {
        this.form = $('form.woocommerce-checkout');
      }

      $('form.woocommerce-checkout').on('checkout_place_order_stripe-connect', this.onSubmit); // // pay order page

      if ($('form#order_review').length) {
        this.form = $('form#order_review');
      }

      $('form#order_review, form#add_payment_method').on('submit', this.onSubmit); // // add payment method page

      if ($('form#add_payment_method').length) {
        this.form = $('form#add_payment_method');
      }

      $('form.woocommerce-checkout').on('change', this.reset);
      $(document).on('stripeConnectError', this.onError).on('checkout_error', this.reset);
      wcv_stripe_connect_form.createElements();
      window.addEventListener('hashchange', wcv_stripe_connect_form.onHashChange); // Stripe Checkout

      if ('yes' === wcv_sc_params.is_stripe_checkout) {
        $(document.body).on('click', '#place_order', function () {
          if (!wcv_stripe_connect_form.isStripeCardChosen()) {
            return;
          }

          wcv_stripe_connect_form.openModal();
          return false;
        });
      }
    },
    unmountElements: function unmountElements() {
      stripe_card.unmount('#stripe-connect-card-element');
      stripe_exp.unmount('#stripe-connect-exp-element');
      stripe_cvc.unmount('#stripe-connect-cvc-element');
    },
    mountElements: function mountElements() {
      if (!$('#stripe-connect-card-element').length) {
        return;
      }

      stripe_card.mount('#stripe-connect-card-element');
      stripe_exp.mount('#stripe-connect-exp-element');
      stripe_cvc.mount('#stripe-connect-cvc-element');
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
        $('input.stripe-connect-source').remove();

        if (event.error) {
          $(document.body).trigger('stripeConnectError', event);
        }
      });
      stripe_exp.addEventListener('change', function (event) {
        wcv_stripe_connect_form.onCCFormChange();
        $('input.stripe-connect-source').remove();

        if (event.error) {
          $(document.body).trigger('stripeConnectError', event);
        }
      });
      stripe_cvc.addEventListener('change', function (event) {
        wcv_stripe_connect_form.onCCFormChange();
        $('input.stripe-connect-source').remove();

        if (event.error) {
          $(document.body).trigger('stripeConnectError', event);
        }
      });
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

          wcv_stripe_connect_form.mountElements();

          if ($('#stripe-iban-element').length) {
            iban.mount('#stripe-iban-element');
          }
        });
      } else if ($('form#add_payment_method').length || $('form#order_review').length) {
        wcv_stripe_connect_form.mountElements();

        if ($('#stripe-iban-element').length) {
          iban.mount('#stripe-iban-element');
        }
      }
    },
    onCCFormChange: function onCCFormChange() {
      wcv_stripe_connect_form.reset();
    },
    updateCardBrand: function updateCardBrand(brand) {
      var brandClass = {
        visa: 'stripe-visa-brand',
        mastercard: 'stripe-mastercard-brand',
        amex: 'stripe-amex-brand',
        discover: 'stripe-discover-brand',
        diners: 'stripe-diners-brand',
        jcb: 'stripe-jcb-brand',
        unknown: 'stripe-credit-card-brand'
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
    // Stripe Checkout.
    openModal: function openModal() {
      // Capture submittal and open stripecheckout
      var $form = wcv_stripe_connect_form.form,
          $data = $('#stripe-connect-payment-data');
      wcv_stripe_connect_form.reset();

      var token_action = function token_action(res) {
        $form.find('input.stripe_source').remove();

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
    unblock: function unblock() {
      wcv_stripe_connect_form.form.unblock();
    },
    // rest
    reset: function reset() {
      $('.wcv-stripe-connect-error, .stripeConnectError, .stripe_connect_token').remove(); // Stripe Checkout.

      if ('yes' === wcv_sc_params.is_stripe_checkout) {
        wcv_stripe_connect_form.stripe_submit = false;
      }
    },
    // Check to see if Stripe in general is being used for checkout.
    isStripeConnectChosen: function isStripeConnectChosen() {
      return $('#payment_method_stripe-connect').is(':checked') || $('#payment_method_stripe-connect').is(':checked') && 'new' === $('input[name="wc-stripe-payment-token"]:checked').val();
    },
    // Currently only support saved cards via credit cards and SEPA. No other payment method.
    isStripeSaveCardChosen: function isStripeSaveCardChosen() {
      return $('#payment_method_stripe-connect').is(':checked') && $('input[name="wc-stripe-connect-payment-token"]').is(':checked') && 'new' !== $('input[name="wc-stripe-connect-payment-token"]:checked').val();
    },
    // Stripe credit card used.
    isStripeCardChosen: function isStripeCardChosen() {
      return $('#payment_method_stripe-connect').is(':checked');
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
    isStripeModalNeeded: function isStripeModalNeeded() {
      var token = wcv_stripe_connect_form.form.find('input.stripe_connect_token'); // If this is a stripe submission (after modal) and token exists, allow submit.

      if (wcv_stripe_connect_form.stripe_submit && token) {
        return false;
      } // Don't affect submission if modal is not needed.


      if (!wcv_stripe_connect_form.isStripeConnectChosen()) {
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
    // Get the customer details
    getCustomerDetails: function getCustomerDetails() {
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

      if ('undefined' === typeof extra_details.owner.phone || 0 >= extra_details.owner.phone.length) {
        delete extra_details.owner.phone;
      }

      if ('undefined' === typeof extra_details.owner.email || 0 >= extra_details.owner.email.length) {
        if ($('#stripe-connect-payment-data').data('email').length) {
          extra_details.owner.email = $('#stripe-connect-payment-data').data('email');
        } else {
          delete extra_details.owner.email;
        }
      }

      if ('undefined' === typeof extra_details.owner.name || 0 >= extra_details.owner.name.length) {
        delete extra_details.owner.name;
      }

      if (0 < $('#billing_address_1').length) {
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
    // Source Support
    createSource: function createSource() {
      var extra_details = wcv_stripe_connect_form.getCustomerDetails(); // Create the Stripe source

      stripe.createSource(stripe_card, extra_details).then(wcv_stripe_connect_form.sourceResponse);
    },
    sourceResponse: function sourceResponse(response) {
      if (response.error) {
        $(document.body).trigger('stripeConnectError', response);
      } else if ('no' === wcv_sc_params.allow_prepaid_card && 'card' === response.source.type && 'prepaid' === response.source.card.funding) {
        response.error = {
          message: wcv_sc_params.no_prepaid_card_msg
        };

        if ('yes' === wcv_sc_params.is_stripe_checkout) {
          wcv_stripe_connect_form.submitError('<ul class="woocommerce-error"><li>' + wcv_sc_params.no_prepaid_card_msg + '</li></ul>');
        } else {
          $(document.body).trigger('stripeConnectError', response);
        }
      } else {
        wcv_stripe_connect_form.processStripeResponse(response.source);
      }
    },
    processStripeResponse: function processStripeResponse(source) {
      wcv_stripe_connect_form.reset(); // Insert the Source into the form so it gets submitted to the server.

      wcv_stripe_connect_form.form.append("<input type='hidden' class='stripe-connect-source' name='stripe_connect_source' value='" + source.id + "'/>");

      if ($('form#add_payment_method').length) {
        $(wcv_stripe_connect_form.form).off('submit', wcv_stripe_connect_form.form.onSubmit);
      }

      wcv_stripe_connect_form.form.submit();
    },
    onSubmit: function onSubmit(e) {
      if (!wcv_stripe_connect_form.isStripeConnectChosen()) {
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

        wcv_stripe_connect_form.createSource(); // Prevent form submitting

        return false;
      }
    },
    getSelectedPaymentElement: function getSelectedPaymentElement() {
      return $('.payment_methods input[name="payment_method"]:checked');
    },
    onError: function onError(e, result) {
      var message = result.error.message;
      var selectedMethodElement = wcv_stripe_connect_form.getSelectedPaymentElement().closest('li');
      var savedTokens = selectedMethodElement.find('.woocommerce-SavedPaymentMethods-tokenInput');
      var errorContainer;

      if (savedTokens.length) {
        // In case there are saved cards too, display the message next to the correct one.
        var selectedToken = savedTokens.filter(':checked');

        if (selectedToken.closest('.woocommerce-SavedPaymentMethods-new').length) {
          // Display the error next to the CC fields if a new card is being entered.
          errorContainer = $('#wcv-stripe-connect-cc-form .stripe-connect-source-errors');
        } else {
          // Display the error next to the chosen saved card.
          errorContainer = selectedToken.closest('li').find('.stripe-connect-source-errors');
        }
      } else {
        // When no saved cards are available, display the error next to CC fields.
        errorContainer = selectedMethodElement.find('.stripe-connect-source-errors');
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
      $('.woocommerce-NoticeGroup-checkout').remove(); // eslint-disable-next-line

      console.log(result.error.message); // Leave for troubleshooting.

      errorContainer.html('<ul class="woocommerce_error woocommerce-error wcv-stripe-connect-error"><li>' + message + '</li></ul>');

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
    },

    /**
     * Handles changes in the hash in order to show a modal for PaymentIntent confirmations.
     *
     * Listens for `hashchange` events and checks for a hash in the following format:
     * #confirm-pi-<intentClientSecret>:<successRedirectURL>
     *
     * If such a hash appears, the partials will be used to call `stripe.handleCardAction`
     * in order to allow customers to confirm an 3DS/SCA authorization.
     *
     * Those redirects/hashes are generated in `WCV_SC_Payment_Gateway::generate_charges_transfers_payment`.
     */
    onHashChange: function onHashChange() {
      var partials = window.location.hash.match(/^#?confirm-pi-([^:]+):(.+)$/);

      if (!partials || 3 > partials.length) {
        return;
      }

      var intentClientSecret = partials[1];
      var redirectURL = decodeURIComponent(partials[2]); // Cleanup the URL

      window.location.hash = '';
      wcv_stripe_connect_form.openIntentModal(intentClientSecret, redirectURL);
    },

    /**
     * Opens the modal for PaymentIntent authorizations.
     *
     * @param {string}  intentClientSecret The client secret of the intent.
     * @param {string}  redirectURL        The URL to ping on fail or redirect to on success.
     * @param {boolean} alwaysRedirect     If set to true, an immediate redirect will happen no matter the result.
     *                                     If not, an error will be displayed on failure.
     */
    openIntentModal: function openIntentModal(intentClientSecret, redirectURL, alwaysRedirect) {
      stripe.handleCardAction(intentClientSecret).then(function (response) {
        if (response.error) {
          throw response.error;
        }

        if ('requires_confirmation' !== response.paymentIntent.status) {
          return;
        }

        window.location = redirectURL;
      }).catch(function (error) {
        if (alwaysRedirect) {
          return window.location = redirectURL;
        }

        $(document.body).trigger('stripeConnectError', {
          error: error
        });
        wcv_stripe_connect_form.form.removeClass('processing'); // Report back to the server.

        $.get(redirectURL + '&is_ajax');
      });
    }
  };
  wcv_stripe_connect_form.init();
});

},{}]},{},[1])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvc3JjL2pzL3N0cmlwZS1jaGVja291dC5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7O0FDQUE7QUFFQSxNQUFNLENBQUMsVUFBUyxDQUFULEVBQVk7QUFDbEI7O0FBRUEsTUFBSTtBQUNILFFBQUksTUFBTSxHQUFHLE1BQU0sQ0FBQyxhQUFhLENBQUMsR0FBZixDQUFuQjtBQUNBLEdBRkQsQ0FFRSxPQUFPLEtBQVAsRUFBYztBQUNmO0FBQ0EsSUFBQSxPQUFPLENBQUMsR0FBUixDQUFZLEtBQVo7QUFDQTtBQUNBOztBQUVELE1BQUksdUJBQXVCLEdBQUcsTUFBTSxDQUFDLElBQVAsQ0FBWSxhQUFhLENBQUMsZ0JBQTFCLEVBQzNCLE1BRDJCLEdBRTFCLGFBQWEsQ0FBQyxnQkFGWSxHQUcxQixFQUhKO0FBQUEsTUFJQyxRQUFRLEdBQUcsTUFBTSxDQUFDLFFBQVAsQ0FBZ0IsdUJBQWhCLENBSlo7QUFBQSxNQUtDLFdBTEQ7QUFBQSxNQU1DLFVBTkQ7QUFBQSxNQU9DLFVBUEQ7QUFTQTs7OztBQUdBLE1BQUksdUJBQXVCLEdBQUc7QUFDN0I7OztBQUdBLElBQUEsSUFBSSxFQUFFLGdCQUFXO0FBQ2hCO0FBQ0E7QUFDQTtBQUVBO0FBQ0EsV0FBSyxzQkFBTCxHQUE4QixLQUE5QixDQU5nQixDQVFoQjs7QUFDQSxVQUFJLENBQUMsQ0FBQywyQkFBRCxDQUFELENBQStCLE1BQW5DLEVBQTJDO0FBQzFDLGFBQUssSUFBTCxHQUFZLENBQUMsQ0FBQywyQkFBRCxDQUFiO0FBQ0E7O0FBRUQsTUFBQSxDQUFDLENBQUMsMkJBQUQsQ0FBRCxDQUErQixFQUEvQixDQUNDLHFDQURELEVBRUMsS0FBSyxRQUZOLEVBYmdCLENBa0JoQjs7QUFDQSxVQUFJLENBQUMsQ0FBQyxtQkFBRCxDQUFELENBQXVCLE1BQTNCLEVBQW1DO0FBQ2xDLGFBQUssSUFBTCxHQUFZLENBQUMsQ0FB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QixrQkFEbkIsRUFFRSxJQUZGLENBRU8sVUFBUyxRQUFULEVBQW1CO0FBQ3hCLFlBQUksUUFBUSxDQUFDLEtBQWIsRUFBb0I7QUFDbkIsZ0JBQU0sUUFBUSxDQUFDLEtBQWY7QUFDQTs7QUFFRCxZQUNDLDRCQUNBLFFBQVEsQ0FBQyxhQUFULENBQXVCLE1BRnhCLEVBR0U7QUFDRDtBQUNBOztBQUVELFFBQUEsTUFBTSxDQUFDLFFBQVAsR0FBa0IsV0FBbEI7QUFDQSxPQWZGLEVBZ0JFLEtBaEJGLENBZ0JRLFVBQVMsS0FBVCxFQUFnQjtBQUN0QixZQUFJLGNBQUosRUFBb0I7QUFDbkIsaUJBQVEsTUFBTSxDQUFDLFFBQVAsR0FBa0IsV0FBMUI7QUFDQTs7QUFFRCxRQUFBLENBQUMsQ0FBQyxRQUFRLENBQUMsSUFBVixDQUFELENBQWlCLE9BQWpCLENBQXlCLG9CQUF6QixFQUErQztBQUM5QyxVQUFBLEtBQUssRUFBRTtBQUR1QyxTQUEvQztBQUdBLFFBQUEsdUJBQXVCLENBQUMsSUFBeEIsQ0FBNkIsV0FBN0IsQ0FBeUMsWUFBekMsRUFSc0IsQ0FVdEI7O0FBQ0EsUUFBQSxDQUFDLENBQUMsR0FBRixDQUFNLFdBQVcsR0FBRyxVQUFwQjtBQUNBLE9BNUJGO0FBNkJBO0FBMXRCNEIsR0FBOUI7QUE2dEJBLEVBQUEsdUJBQXVCLENBQUMsSUFBeEI7QUFDQSxDQXJ2QkssQ0FBTiIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCl7ZnVuY3Rpb24gcihlLG4sdCl7ZnVuY3Rpb24gbyhpLGYpe2lmKCFuW2ldKXtpZighZVtpXSl7dmFyIGM9XCJmdW5jdGlvblwiPT10eXBlb2YgcmVxdWlyZSYmcmVxdWlyZTtpZighZiYmYylyZXR1cm4gYyhpLCEwKTtpZih1KXJldHVybiB1KGksITApO3ZhciBhPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIraStcIidcIik7dGhyb3cgYS5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGF9dmFyIHA9bltpXT17ZXhwb3J0czp7fX07ZVtpXVswXS5jYWxsKHAuZXhwb3J0cyxmdW5jdGlvbihyKXt2YXIgbj1lW2ldWzFdW3JdO3JldHVybiBvKG58fHIpfSxwLHAuZXhwb3J0cyxyLGUsbix0KX1yZXR1cm4gbltpXS5leHBvcnRzfWZvcih2YXIgdT1cImZ1bmN0aW9uXCI9PXR5cGVvZiByZXF1aXJlJiZyZXF1aXJlLGk9MDtpPHQubGVuZ3RoO2krKylvKHRbaV0pO3JldHVybiBvfXJldHVybiByfSkoKSIsIi8qIGdsb2JhbHMgaWJhbiBTdHJpcGUgd2N2X3NjX3BhcmFtcyBTdHJpcGVDaGVja291dCAqL1xuXG5qUXVlcnkoZnVuY3Rpb24oJCkge1xuXHQndXNlIHN0cmljdCc7XG5cblx0dHJ5IHtcblx0XHR2YXIgc3RyaXBlID0gU3RyaXBlKHdjdl9zY19wYXJhbXMua2V5KTtcblx0fSBjYXRjaCAoZXJyb3IpIHtcblx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcblx0XHRjb25zb2xlLmxvZyhlcnJvcik7XG5cdFx0cmV0dXJuO1xuXHR9XG5cblx0dmFyIHN0cmlwZV9lbGVtZW50c19vcHRpb25zID0gT2JqZWN0LmtleXMod2N2X3NjX3BhcmFtcy5lbGVtZW50c19vcHRpb25zKVxuXHRcdFx0Lmxlbmd0aFxuXHRcdFx0PyB3Y3Zfc2NfcGFyYW1zLmVsZW1lbnRzX29wdGlvbnNcblx0XHRcdDoge30sXG5cdFx0ZWxlbWVudHMgPSBzdHJpcGUuZWxlbWVudHMoc3RyaXBlX2VsZW1lbnRzX29wdGlvbnMpLFxuXHRcdHN0cmlwZV9jYXJkLFxuXHRcdHN0cmlwZV9leHAsXG5cdFx0c3RyaXBlX2N2YztcblxuXHQvKipcblx0ICogT2JqZWN0IHRvIGhhbmRsZSBTdHJpcGUgZWxlbWVudHMgcGF5bWVudCBmb3JtLlxuXHQgKi9cblx0dmFyIHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtID0ge1xuXHRcdC8qKlxuXHRcdCAqIEluaXRpYWxpemUgZXZlbnQgaGFuZGxlcnMgYW5kIFVJIHN0YXRlLlxuXHRcdCAqL1xuXHRcdGluaXQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0Ly8gaWYgKCAneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19jaGFuZ2VfcGF5bWVudF9wYWdlIHx8ICd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX3BheV9mb3Jfb3JkZXJfcGFnZSApIHtcblx0XHRcdC8vIFx0JCggZG9jdW1lbnQuYm9keSApLnRyaWdnZXIoICd3Yy1jcmVkaXQtY2FyZC1mb3JtLWluaXQnICk7XG5cdFx0XHQvLyB9XG5cblx0XHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRcdHRoaXMuc3RyaXBlX2NoZWNrb3V0X3N1Ym1pdCA9IGZhbHNlO1xuXG5cdFx0XHQvLyBjaGVja291dCBwYWdlXG5cdFx0XHRpZiAoJCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpLmxlbmd0aCkge1xuXHRcdFx0XHR0aGlzLmZvcm0gPSAkKCdmb3JtLndvb2NvbW1lcmNlLWNoZWNrb3V0Jyk7XG5cdFx0XHR9XG5cblx0XHRcdCQoJ2Zvcm0ud29vY29tbWVyY2UtY2hlY2tvdXQnKS5vbihcblx0XHRcdFx0J2NoZWNrb3V0X3BsYWNlX29yZGVyX3N0cmlwZS1jb25uZWN0Jyxcblx0XHRcdFx0dGhpcy5vblN1Ym1pdFxuXHRcdFx0KTtcblxuXHRcdFx0Ly8gLy8gcGF5IG9yZGVyIHBhZ2Vcblx0XHRcdGlmICgkKCdmb3JtI29yZGVyX3JldmlldycpLmxlbmd0aCkge1xuXHRcdFx0XHR0aGlzLmZvcm0gPSAkKCdmb3JtI29yZGVyX3JldmlldycpO1xuXHRcdFx0fVxuXG5cdFx0XHQkKCdmb3JtI29yZGVyX3JldmlldywgZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnKS5vbihcblx0XHRcdFx0J3N1Ym1pdCcsXG5cdFx0XHRcdHRoaXMub25TdWJtaXRcblx0XHRcdCk7XG5cblx0XHRcdC8vIC8vIGFkZCBwYXltZW50IG1ldGhvZCBwYWdlXG5cdFx0XHRpZiAoJCgnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnKS5sZW5ndGgpIHtcblx0XHRcdFx0dGhpcy5mb3JtID0gJCgnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnKTtcblx0XHRcdH1cblxuXHRcdFx0JCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpLm9uKCdjaGFuZ2UnLCB0aGlzLnJlc2V0KTtcblxuXHRcdFx0JChkb2N1bWVudClcblx0XHRcdFx0Lm9uKCdzdHJpcGVDb25uZWN0RXJyb3InLCB0aGlzLm9uRXJyb3IpXG5cdFx0XHRcdC5vbignY2hlY2tvdXRfZXJyb3InLCB0aGlzLnJlc2V0KTtcblxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uY3JlYXRlRWxlbWVudHMoKTtcblxuXHRcdFx0d2luZG93LmFkZEV2ZW50TGlzdGVuZXIoXG5cdFx0XHRcdCdoYXNoY2hhbmdlJyxcblx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ub25IYXNoQ2hhbmdlXG5cdFx0XHQpO1xuXG5cdFx0XHQvLyBTdHJpcGUgQ2hlY2tvdXRcblx0XHRcdGlmICgneWVzJyA9PT0gd2N2X3NjX3BhcmFtcy5pc19zdHJpcGVfY2hlY2tvdXQpIHtcblx0XHRcdFx0JChkb2N1bWVudC5ib2R5KS5vbignY2xpY2snLCAnI3BsYWNlX29yZGVyJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0aWYgKCF3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1N0cmlwZUNhcmRDaG9zZW4oKSkge1xuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLm9wZW5Nb2RhbCgpO1xuXHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0fSk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdHVubW91bnRFbGVtZW50czogZnVuY3Rpb24oKSB7XG5cdFx0XHRzdHJpcGVfY2FyZC51bm1vdW50KCcjc3RyaXBlLWNvbm5lY3QtY2FyZC1lbGVtZW50Jyk7XG5cdFx0XHRzdHJpcGVfZXhwLnVubW91bnQoJyNzdHJpcGUtY29ubmVjdC1leHAtZWxlbWVudCcpO1xuXHRcdFx0c3RyaXBlX2N2Yy51bm1vdW50KCcjc3RyaXBlLWNvbm5lY3QtY3ZjLWVsZW1lbnQnKTtcblx0XHR9LFxuXG5cdFx0bW91bnRFbGVtZW50czogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZiAoISQoJyNzdHJpcGUtY29ubmVjdC1jYXJkLWVsZW1lbnQnKS5sZW5ndGgpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRzdHJpcGVfY2FyZC5tb3VudCgnI3N0cmlwZS1jb25uZWN0LWNhcmQtZWxlbWVudCcpO1xuXHRcdFx0c3RyaXBlX2V4cC5tb3VudCgnI3N0cmlwZS1jb25uZWN0LWV4cC1lbGVtZW50Jyk7XG5cdFx0XHRzdHJpcGVfY3ZjLm1vdW50KCcjc3RyaXBlLWNvbm5lY3QtY3ZjLWVsZW1lbnQnKTtcblx0XHR9LFxuXG5cdFx0Y3JlYXRlRWxlbWVudHM6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIGVsZW1lbnRTdHlsZXMgPSB7XG5cdFx0XHRcdGJhc2U6IHtcblx0XHRcdFx0XHRpY29uQ29sb3I6ICcjNjY2RUU4Jyxcblx0XHRcdFx0XHRjb2xvcjogJyMzMTMyNUYnLFxuXHRcdFx0XHRcdGZvbnRTaXplOiAnMTVweCcsXG5cdFx0XHRcdFx0Jzo6cGxhY2Vob2xkZXInOiB7XG5cdFx0XHRcdFx0XHRjb2xvcjogJyNDRkQ3RTAnXG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9O1xuXG5cdFx0XHR2YXIgZWxlbWVudENsYXNzZXMgPSB7XG5cdFx0XHRcdGZvY3VzOiAnZm9jdXNlZCcsXG5cdFx0XHRcdGVtcHR5OiAnZW1wdHknLFxuXHRcdFx0XHRpbnZhbGlkOiAnaW52YWxpZCdcblx0XHRcdH07XG5cblx0XHRcdHN0cmlwZV9jYXJkID0gZWxlbWVudHMuY3JlYXRlKCdjYXJkTnVtYmVyJywge1xuXHRcdFx0XHRzdHlsZTogZWxlbWVudFN0eWxlcyxcblx0XHRcdFx0Y2xhc3NlczogZWxlbWVudENsYXNzZXNcblx0XHRcdH0pO1xuXHRcdFx0c3RyaXBlX2V4cCA9IGVsZW1lbnRzLmNyZWF0ZSgnY2FyZEV4cGlyeScsIHtcblx0XHRcdFx0c3R5bGU6IGVsZW1lbnRTdHlsZXMsXG5cdFx0XHRcdGNsYXNzZXM6IGVsZW1lbnRDbGFzc2VzXG5cdFx0XHR9KTtcblx0XHRcdHN0cmlwZV9jdmMgPSBlbGVtZW50cy5jcmVhdGUoJ2NhcmRDdmMnLCB7XG5cdFx0XHRcdHN0eWxlOiBlbGVtZW50U3R5bGVzLFxuXHRcdFx0XHRjbGFzc2VzOiBlbGVtZW50Q2xhc3Nlc1xuXHRcdFx0fSk7XG5cblx0XHRcdHN0cmlwZV9jYXJkLmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLm9uQ0NGb3JtQ2hhbmdlKCk7XG5cblx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0udXBkYXRlQ2FyZEJyYW5kKGV2ZW50LmJyYW5kKTtcblx0XHRcdFx0JCgnaW5wdXQuc3RyaXBlLWNvbm5lY3Qtc291cmNlJykucmVtb3ZlKCk7XG5cblx0XHRcdFx0aWYgKGV2ZW50LmVycm9yKSB7XG5cdFx0XHRcdFx0JChkb2N1bWVudC5ib2R5KS50cmlnZ2VyKCdzdHJpcGVDb25uZWN0RXJyb3InLCBldmVudCk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXG5cdFx0XHRzdHJpcGVfZXhwLmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLm9uQ0NGb3JtQ2hhbmdlKCk7XG5cdFx0XHRcdCQoJ2lucHV0LnN0cmlwZS1jb25uZWN0LXNvdXJjZScpLnJlbW92ZSgpO1xuXG5cdFx0XHRcdGlmIChldmVudC5lcnJvcikge1xuXHRcdFx0XHRcdCQoZG9jdW1lbnQuYm9keSkudHJpZ2dlcignc3RyaXBlQ29ubmVjdEVycm9yJywgZXZlbnQpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblxuXHRcdFx0c3RyaXBlX2N2Yy5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBmdW5jdGlvbihldmVudCkge1xuXHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vbkNDRm9ybUNoYW5nZSgpO1xuXHRcdFx0XHQkKCdpbnB1dC5zdHJpcGUtY29ubmVjdC1zb3VyY2UnKS5yZW1vdmUoKTtcblxuXHRcdFx0XHRpZiAoZXZlbnQuZXJyb3IpIHtcblx0XHRcdFx0XHQkKGRvY3VtZW50LmJvZHkpLnRyaWdnZXIoJ3N0cmlwZUNvbm5lY3RFcnJvcicsIGV2ZW50KTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogT25seSBpbiBjaGVja291dCBwYWdlIHdlIG5lZWQgdG8gZGVsYXkgdGhlIG1vdW50aW5nIG9mIHRoZVxuXHRcdFx0ICogY2FyZCBhcyBzb21lIEFKQVggcHJvY2VzcyBuZWVkcyB0byBoYXBwZW4gYmVmb3JlIHdlIGRvLlxuXHRcdFx0ICovXG5cdFx0XHRpZiAoJ3llcycgPT09IHdjdl9zY19wYXJhbXMuaXNfY2hlY2tvdXQpIHtcblx0XHRcdFx0JChkb2N1bWVudC5ib2R5KS5vbigndXBkYXRlZF9jaGVja291dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdC8vIERvbid0IG1vdW50IGVsZW1lbnRzIGEgc2Vjb25kIHRpbWUuXG5cdFx0XHRcdFx0aWYgKHN0cmlwZV9jYXJkKSB7XG5cdFx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS51bm1vdW50RWxlbWVudHMoKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5tb3VudEVsZW1lbnRzKCk7XG5cblx0XHRcdFx0XHRpZiAoJCgnI3N0cmlwZS1pYmFuLWVsZW1lbnQnKS5sZW5ndGgpIHtcblx0XHRcdFx0XHRcdGliYW4ubW91bnQoJyNzdHJpcGUtaWJhbi1lbGVtZW50Jyk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH0gZWxzZSBpZiAoXG5cdFx0XHRcdCQoJ2Zvcm0jYWRkX3BheW1lbnRfbWV0aG9kJykubGVuZ3RoIHx8XG5cdFx0XHRcdCQoJ2Zvcm0jb3JkZXJfcmV2aWV3JykubGVuZ3RoXG5cdFx0XHQpIHtcblx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ubW91bnRFbGVtZW50cygpO1xuXG5cdFx0XHRcdGlmICgkKCcjc3RyaXBlLWliYW4tZWxlbWVudCcpLmxlbmd0aCkge1xuXHRcdFx0XHRcdGliYW4ubW91bnQoJyNzdHJpcGUtaWJhbi1lbGVtZW50Jyk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0b25DQ0Zvcm1DaGFuZ2U6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ucmVzZXQoKTtcblx0XHR9LFxuXG5cdFx0dXBkYXRlQ2FyZEJyYW5kOiBmdW5jdGlvbihicmFuZCkge1xuXHRcdFx0dmFyIGJyYW5kQ2xhc3MgPSB7XG5cdFx0XHRcdHZpc2E6ICdzdHJpcGUtdmlzYS1icmFuZCcsXG5cdFx0XHRcdG1hc3RlcmNhcmQ6ICdzdHJpcGUtbWFzdGVyY2FyZC1icmFuZCcsXG5cdFx0XHRcdGFtZXg6ICdzdHJpcGUtYW1leC1icmFuZCcsXG5cdFx0XHRcdGRpc2NvdmVyOiAnc3RyaXBlLWRpc2NvdmVyLWJyYW5kJyxcblx0XHRcdFx0ZGluZXJzOiAnc3RyaXBlLWRpbmVycy1icmFuZCcsXG5cdFx0XHRcdGpjYjogJ3N0cmlwZS1qY2ItYnJhbmQnLFxuXHRcdFx0XHR1bmtub3duOiAnc3RyaXBlLWNyZWRpdC1jYXJkLWJyYW5kJ1xuXHRcdFx0fTtcblxuXHRcdFx0dmFyIGltYWdlRWxlbWVudCA9ICQoJy5zdHJpcGUtY2FyZC1icmFuZCcpLFxuXHRcdFx0XHRpbWFnZUNsYXNzID0gJ3N0cmlwZS1jcmVkaXQtY2FyZC1icmFuZCc7XG5cblx0XHRcdGlmIChicmFuZCBpbiBicmFuZENsYXNzKSB7XG5cdFx0XHRcdGltYWdlQ2xhc3MgPSBicmFuZENsYXNzW2JyYW5kXTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gUmVtb3ZlIGV4aXN0aW5nIGNhcmQgYnJhbmQgY2xhc3MuXG5cdFx0XHQkLmVhY2goYnJhbmRDbGFzcywgZnVuY3Rpb24oaW5kZXgsIGVsKSB7XG5cdFx0XHRcdGltYWdlRWxlbWVudC5yZW1vdmVDbGFzcyhlbCk7XG5cdFx0XHR9KTtcblxuXHRcdFx0aW1hZ2VFbGVtZW50LmFkZENsYXNzKGltYWdlQ2xhc3MpO1xuXHRcdH0sXG5cblx0XHQvLyBTdHJpcGUgQ2hlY2tvdXQuXG5cdFx0b3Blbk1vZGFsOiBmdW5jdGlvbigpIHtcblx0XHRcdC8vIENhcHR1cmUgc3VibWl0dGFsIGFuZCBvcGVuIHN0cmlwZWNoZWNrb3V0XG5cdFx0XHR2YXIgJGZvcm0gPSB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLFxuXHRcdFx0XHQkZGF0YSA9ICQoJyNzdHJpcGUtY29ubmVjdC1wYXltZW50LWRhdGEnKTtcblxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ucmVzZXQoKTtcblxuXHRcdFx0dmFyIHRva2VuX2FjdGlvbiA9IGZ1bmN0aW9uKHJlcykge1xuXHRcdFx0XHQkZm9ybS5maW5kKCdpbnB1dC5zdHJpcGVfc291cmNlJykucmVtb3ZlKCk7XG5cblx0XHRcdFx0aWYgKCd0b2tlbicgPT09IHJlcy5vYmplY3QpIHtcblx0XHRcdFx0XHRzdHJpcGVcblx0XHRcdFx0XHRcdC5jcmVhdGVTb3VyY2Uoe1xuXHRcdFx0XHRcdFx0XHR0eXBlOiAnY2FyZCcsXG5cdFx0XHRcdFx0XHRcdHRva2VuOiByZXMuaWRcblx0XHRcdFx0XHRcdH0pXG5cdFx0XHRcdFx0XHQudGhlbih3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zb3VyY2VSZXNwb25zZSk7XG5cdFx0XHRcdH0gZWxzZSBpZiAoJ3NvdXJjZScgPT09IHJlcy5vYmplY3QpIHtcblx0XHRcdFx0XHR2YXIgcmVzcG9uc2UgPSB7IHNvdXJjZTogcmVzIH07XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uc291cmNlUmVzcG9uc2UocmVzcG9uc2UpO1xuXHRcdFx0XHR9XG5cdFx0XHR9O1xuXG5cdFx0XHRTdHJpcGVDaGVja291dC5vcGVuKHtcblx0XHRcdFx0a2V5OiB3Y3Zfc2NfcGFyYW1zLmtleSxcblx0XHRcdFx0YmlsbGluZ0FkZHJlc3M6ICRkYXRhLmRhdGEoJ2JpbGxpbmctYWRkcmVzcycpLFxuXHRcdFx0XHR6aXBDb2RlOiAkZGF0YS5kYXRhKCd2ZXJpZnktemlwJyksXG5cdFx0XHRcdGFtb3VudDogJGRhdGEuZGF0YSgnYW1vdW50JyksXG5cdFx0XHRcdG5hbWU6ICRkYXRhLmRhdGEoJ25hbWUnKSxcblx0XHRcdFx0ZGVzY3JpcHRpb246ICRkYXRhLmRhdGEoJ2Rlc2NyaXB0aW9uJyksXG5cdFx0XHRcdGN1cnJlbmN5OiAkZGF0YS5kYXRhKCdjdXJyZW5jeScpLFxuXHRcdFx0XHRpbWFnZTogJGRhdGEuZGF0YSgnaW1hZ2UnKSxcblx0XHRcdFx0bG9jYWxlOiAkZGF0YS5kYXRhKCdsb2NhbGUnKSxcblx0XHRcdFx0ZW1haWw6ICQoJyNiaWxsaW5nX2VtYWlsJykudmFsKCkgfHwgJGRhdGEuZGF0YSgnZW1haWwnKSxcblx0XHRcdFx0cGFuZWxMYWJlbDogJGRhdGEuZGF0YSgncGFuZWwtbGFiZWwnKSxcblx0XHRcdFx0YWxsb3dSZW1lbWJlck1lOiAkZGF0YS5kYXRhKCdhbGxvdy1yZW1lbWJlci1tZScpLFxuXHRcdFx0XHR0b2tlbjogdG9rZW5fYWN0aW9uLFxuXHRcdFx0XHRjbG9zZWQ6IHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLm9uQ2xvc2UoKVxuXHRcdFx0fSk7XG5cdFx0fSxcblxuXHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRyZXNldE1vZGFsOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnJlc2V0KCk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zdHJpcGVfY2hlY2tvdXRfc3VibWl0ID0gZmFsc2U7XG5cdFx0fSxcblxuXHRcdC8vIFN0cmlwZSBDaGVja291dC5cblx0XHRvbkNsb3NlOiBmdW5jdGlvbigpIHtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnVuYmxvY2soKTtcblx0XHR9LFxuXG5cdFx0dW5ibG9jazogZnVuY3Rpb24oKSB7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLnVuYmxvY2soKTtcblx0XHR9LFxuXG5cdFx0Ly8gcmVzdFxuXHRcdHJlc2V0OiBmdW5jdGlvbigpIHtcblx0XHRcdCQoXG5cdFx0XHRcdCcud2N2LXN0cmlwZS1jb25uZWN0LWVycm9yLCAuc3RyaXBlQ29ubmVjdEVycm9yLCAuc3RyaXBlX2Nvbm5lY3RfdG9rZW4nXG5cdFx0XHQpLnJlbW92ZSgpO1xuXG5cdFx0XHQvLyBTdHJpcGUgQ2hlY2tvdXQuXG5cdFx0XHRpZiAoJ3llcycgPT09IHdjdl9zY19wYXJhbXMuaXNfc3RyaXBlX2NoZWNrb3V0KSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnN0cmlwZV9zdWJtaXQgPSBmYWxzZTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0Ly8gQ2hlY2sgdG8gc2VlIGlmIFN0cmlwZSBpbiBnZW5lcmFsIGlzIGJlaW5nIHVzZWQgZm9yIGNoZWNrb3V0LlxuXHRcdGlzU3RyaXBlQ29ubmVjdENob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQkKCcjcGF5bWVudF9tZXRob2Rfc3RyaXBlLWNvbm5lY3QnKS5pcygnOmNoZWNrZWQnKSB8fFxuXHRcdFx0XHQoJCgnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0JykuaXMoJzpjaGVja2VkJykgJiZcblx0XHRcdFx0XHQnbmV3JyA9PT1cblx0XHRcdFx0XHRcdCQoXG5cdFx0XHRcdFx0XHRcdCdpbnB1dFtuYW1lPVwid2Mtc3RyaXBlLXBheW1lbnQtdG9rZW5cIl06Y2hlY2tlZCdcblx0XHRcdFx0XHRcdCkudmFsKCkpXG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvLyBDdXJyZW50bHkgb25seSBzdXBwb3J0IHNhdmVkIGNhcmRzIHZpYSBjcmVkaXQgY2FyZHMgYW5kIFNFUEEuIE5vIG90aGVyIHBheW1lbnQgbWV0aG9kLlxuXHRcdGlzU3RyaXBlU2F2ZUNhcmRDaG9zZW46IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuIChcblx0XHRcdFx0JCgnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0JykuaXMoJzpjaGVja2VkJykgJiZcblx0XHRcdFx0JCgnaW5wdXRbbmFtZT1cIndjLXN0cmlwZS1jb25uZWN0LXBheW1lbnQtdG9rZW5cIl0nKS5pcyhcblx0XHRcdFx0XHQnOmNoZWNrZWQnXG5cdFx0XHRcdCkgJiZcblx0XHRcdFx0J25ldycgIT09XG5cdFx0XHRcdFx0JChcblx0XHRcdFx0XHRcdCdpbnB1dFtuYW1lPVwid2Mtc3RyaXBlLWNvbm5lY3QtcGF5bWVudC10b2tlblwiXTpjaGVja2VkJ1xuXHRcdFx0XHRcdCkudmFsKClcblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8vIFN0cmlwZSBjcmVkaXQgY2FyZCB1c2VkLlxuXHRcdGlzU3RyaXBlQ2FyZENob3NlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCgnI3BheW1lbnRfbWV0aG9kX3N0cmlwZS1jb25uZWN0JykuaXMoJzpjaGVja2VkJyk7XG5cdFx0fSxcblxuXHRcdGhhc1NvdXJjZTogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gMCA8ICQoJ2lucHV0LnN0cmlwZS1jb25uZWN0LXNvdXJjZScpLmxlbmd0aDtcblx0XHR9LFxuXG5cdFx0Ly8gTGVnYWN5XG5cdFx0aGFzVG9rZW46IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuIDAgPCAkKCdpbnB1dC5zdHJpcGVfY29ubmVjdF90b2tlbicpLmxlbmd0aDtcblx0XHR9LFxuXG5cdFx0aXNNb2JpbGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKFxuXHRcdFx0XHQvQW5kcm9pZHx3ZWJPU3xpUGhvbmV8aVBhZHxpUG9kfEJsYWNrQmVycnl8SUVNb2JpbGV8T3BlcmEgTWluaS9pLnRlc3QoXG5cdFx0XHRcdFx0bmF2aWdhdG9yLnVzZXJBZ2VudFxuXHRcdFx0XHQpXG5cdFx0XHQpIHtcblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9LFxuXG5cdFx0aXNTdHJpcGVNb2RhbE5lZWRlZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgdG9rZW4gPSB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5mb3JtLmZpbmQoXG5cdFx0XHRcdCdpbnB1dC5zdHJpcGVfY29ubmVjdF90b2tlbidcblx0XHRcdCk7XG5cblx0XHRcdC8vIElmIHRoaXMgaXMgYSBzdHJpcGUgc3VibWlzc2lvbiAoYWZ0ZXIgbW9kYWwpIGFuZCB0b2tlbiBleGlzdHMsIGFsbG93IHN1Ym1pdC5cblx0XHRcdGlmICh3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5zdHJpcGVfc3VibWl0ICYmIHRva2VuKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gRG9uJ3QgYWZmZWN0IHN1Ym1pc3Npb24gaWYgbW9kYWwgaXMgbm90IG5lZWRlZC5cblx0XHRcdGlmICghd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDb25uZWN0Q2hvc2VuKCkpIHtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHR9LFxuXG5cdFx0YmxvY2s6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCF3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc01vYmlsZSgpKSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0uYmxvY2soe1xuXHRcdFx0XHRcdG1lc3NhZ2U6IG51bGwsXG5cdFx0XHRcdFx0b3ZlcmxheUNTUzoge1xuXHRcdFx0XHRcdFx0YmFja2dyb3VuZDogJyNmZmYnLFxuXHRcdFx0XHRcdFx0b3BhY2l0eTogMC42XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0Ly8gR2V0IHRoZSBjdXN0b21lciBkZXRhaWxzXG5cdFx0Z2V0Q3VzdG9tZXJEZXRhaWxzOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBmaXJzdF9uYW1lID0gJCgnI2JpbGxpbmdfZmlyc3RfbmFtZScpLmxlbmd0aFxuXHRcdFx0XHRcdD8gJCgnI2JpbGxpbmdfZmlyc3RfbmFtZScpLnZhbCgpXG5cdFx0XHRcdFx0OiB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfZmlyc3RfbmFtZSxcblx0XHRcdFx0bGFzdF9uYW1lID0gJCgnI2JpbGxpbmdfbGFzdF9uYW1lJykubGVuZ3RoXG5cdFx0XHRcdFx0PyAkKCcjYmlsbGluZ19sYXN0X25hbWUnKS52YWwoKVxuXHRcdFx0XHRcdDogd2N2X3NjX3BhcmFtcy5iaWxsaW5nX2xhc3RfbmFtZSxcblx0XHRcdFx0ZXh0cmFfZGV0YWlscyA9IHtcblx0XHRcdFx0XHRvd25lcjogeyBuYW1lOiAnJywgYWRkcmVzczoge30sIGVtYWlsOiAnJywgcGhvbmU6ICcnIH1cblx0XHRcdFx0fTtcblxuXHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5uYW1lID0gZmlyc3RfbmFtZTtcblxuXHRcdFx0aWYgKGZpcnN0X25hbWUgJiYgbGFzdF9uYW1lKSB7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIubmFtZSA9IGZpcnN0X25hbWUgKyAnICcgKyBsYXN0X25hbWU7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLm5hbWUgPSAkKCcjc3RyaXBlLXBheW1lbnQtZGF0YScpLmRhdGEoXG5cdFx0XHRcdFx0J2Z1bGwtbmFtZSdcblx0XHRcdFx0KTtcblx0XHRcdH1cblxuXHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5lbWFpbCA9ICQoJyNiaWxsaW5nX2VtYWlsJykudmFsKCk7XG5cdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLnBob25lID0gJCgnI2JpbGxpbmdfcGhvbmUnKS52YWwoKTtcblxuXHRcdFx0LyogU3RyaXBlIGRvZXMgbm90IGxpa2UgZW1wdHkgc3RyaW5nIHZhbHVlcyBzb1xuXHRcdFx0ICogd2UgbmVlZCB0byByZW1vdmUgdGhlIHBhcmFtZXRlciBpZiB3ZSdyZSBub3Rcblx0XHRcdCAqIHBhc3NpbmcgYW55IHZhbHVlLlxuXHRcdFx0ICovXG5cdFx0XHRpZiAoXG5cdFx0XHRcdCd1bmRlZmluZWQnID09PSB0eXBlb2YgZXh0cmFfZGV0YWlscy5vd25lci5waG9uZSB8fFxuXHRcdFx0XHQwID49IGV4dHJhX2RldGFpbHMub3duZXIucGhvbmUubGVuZ3RoXG5cdFx0XHQpIHtcblx0XHRcdFx0ZGVsZXRlIGV4dHJhX2RldGFpbHMub3duZXIucGhvbmU7XG5cdFx0XHR9XG5cblx0XHRcdGlmIChcblx0XHRcdFx0J3VuZGVmaW5lZCcgPT09IHR5cGVvZiBleHRyYV9kZXRhaWxzLm93bmVyLmVtYWlsIHx8XG5cdFx0XHRcdDAgPj0gZXh0cmFfZGV0YWlscy5vd25lci5lbWFpbC5sZW5ndGhcblx0XHRcdCkge1xuXHRcdFx0XHRpZiAoJCgnI3N0cmlwZS1jb25uZWN0LXBheW1lbnQtZGF0YScpLmRhdGEoJ2VtYWlsJykubGVuZ3RoKSB7XG5cdFx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5lbWFpbCA9ICQoXG5cdFx0XHRcdFx0XHQnI3N0cmlwZS1jb25uZWN0LXBheW1lbnQtZGF0YSdcblx0XHRcdFx0XHQpLmRhdGEoJ2VtYWlsJyk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0ZGVsZXRlIGV4dHJhX2RldGFpbHMub3duZXIuZW1haWw7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0aWYgKFxuXHRcdFx0XHQndW5kZWZpbmVkJyA9PT0gdHlwZW9mIGV4dHJhX2RldGFpbHMub3duZXIubmFtZSB8fFxuXHRcdFx0XHQwID49IGV4dHJhX2RldGFpbHMub3duZXIubmFtZS5sZW5ndGhcblx0XHRcdCkge1xuXHRcdFx0XHRkZWxldGUgZXh0cmFfZGV0YWlscy5vd25lci5uYW1lO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoMCA8ICQoJyNiaWxsaW5nX2FkZHJlc3NfMScpLmxlbmd0aCkge1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MubGluZTEgPSAkKFxuXHRcdFx0XHRcdCcjYmlsbGluZ19hZGRyZXNzXzEnXG5cdFx0XHRcdCkudmFsKCk7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5saW5lMiA9ICQoXG5cdFx0XHRcdFx0JyNiaWxsaW5nX2FkZHJlc3NfMidcblx0XHRcdFx0KS52YWwoKTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLnN0YXRlID0gJCgnI2JpbGxpbmdfc3RhdGUnKS52YWwoKTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLmNpdHkgPSAkKCcjYmlsbGluZ19jaXR5JykudmFsKCk7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5wb3N0YWxfY29kZSA9ICQoXG5cdFx0XHRcdFx0JyNiaWxsaW5nX3Bvc3Rjb2RlJ1xuXHRcdFx0XHQpLnZhbCgpO1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MuY291bnRyeSA9ICQoXG5cdFx0XHRcdFx0JyNiaWxsaW5nX2NvdW50cnknXG5cdFx0XHRcdCkudmFsKCk7XG5cdFx0XHR9IGVsc2UgaWYgKHdjdl9zY19wYXJhbXMuYmlsbGluZ19hZGRyZXNzXzEpIHtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLmxpbmUxID1cblx0XHRcdFx0XHR3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfYWRkcmVzc18xO1xuXHRcdFx0XHRleHRyYV9kZXRhaWxzLm93bmVyLmFkZHJlc3MubGluZTIgPVxuXHRcdFx0XHRcdHdjdl9zY19wYXJhbXMuYmlsbGluZ19hZGRyZXNzXzI7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5zdGF0ZSA9IHdjdl9zY19wYXJhbXMuYmlsbGluZ19zdGF0ZTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLmNpdHkgPSB3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfY2l0eTtcblx0XHRcdFx0ZXh0cmFfZGV0YWlscy5vd25lci5hZGRyZXNzLnBvc3RhbF9jb2RlID1cblx0XHRcdFx0XHR3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfcG9zdGNvZGU7XG5cdFx0XHRcdGV4dHJhX2RldGFpbHMub3duZXIuYWRkcmVzcy5jb3VudHJ5ID1cblx0XHRcdFx0XHR3Y3Zfc2NfcGFyYW1zLmJpbGxpbmdfY291bnRyeTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGV4dHJhX2RldGFpbHM7XG5cdFx0fSxcblxuXHRcdC8vIFNvdXJjZSBTdXBwb3J0XG5cdFx0Y3JlYXRlU291cmNlOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBleHRyYV9kZXRhaWxzID0gd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZ2V0Q3VzdG9tZXJEZXRhaWxzKCk7XG5cblx0XHRcdC8vIENyZWF0ZSB0aGUgU3RyaXBlIHNvdXJjZVxuXHRcdFx0c3RyaXBlXG5cdFx0XHRcdC5jcmVhdGVTb3VyY2Uoc3RyaXBlX2NhcmQsIGV4dHJhX2RldGFpbHMpXG5cdFx0XHRcdC50aGVuKHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnNvdXJjZVJlc3BvbnNlKTtcblx0XHR9LFxuXG5cdFx0c291cmNlUmVzcG9uc2U6IGZ1bmN0aW9uKHJlc3BvbnNlKSB7XG5cdFx0XHRpZiAocmVzcG9uc2UuZXJyb3IpIHtcblx0XHRcdFx0JChkb2N1bWVudC5ib2R5KS50cmlnZ2VyKCdzdHJpcGVDb25uZWN0RXJyb3InLCByZXNwb25zZSk7XG5cdFx0XHR9IGVsc2UgaWYgKFxuXHRcdFx0XHQnbm8nID09PSB3Y3Zfc2NfcGFyYW1zLmFsbG93X3ByZXBhaWRfY2FyZCAmJlxuXHRcdFx0XHQnY2FyZCcgPT09IHJlc3BvbnNlLnNvdXJjZS50eXBlICYmXG5cdFx0XHRcdCdwcmVwYWlkJyA9PT0gcmVzcG9uc2Uuc291cmNlLmNhcmQuZnVuZGluZ1xuXHRcdFx0KSB7XG5cdFx0XHRcdHJlc3BvbnNlLmVycm9yID0geyBtZXNzYWdlOiB3Y3Zfc2NfcGFyYW1zLm5vX3ByZXBhaWRfY2FyZF9tc2cgfTtcblxuXHRcdFx0XHRpZiAoJ3llcycgPT09IHdjdl9zY19wYXJhbXMuaXNfc3RyaXBlX2NoZWNrb3V0KSB7XG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uc3VibWl0RXJyb3IoXG5cdFx0XHRcdFx0XHQnPHVsIGNsYXNzPVwid29vY29tbWVyY2UtZXJyb3JcIj48bGk+JyArXG5cdFx0XHRcdFx0XHRcdHdjdl9zY19wYXJhbXMubm9fcHJlcGFpZF9jYXJkX21zZyArXG5cdFx0XHRcdFx0XHRcdCc8L2xpPjwvdWw+J1xuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0JChkb2N1bWVudC5ib2R5KS50cmlnZ2VyKCdzdHJpcGVDb25uZWN0RXJyb3InLCByZXNwb25zZSk7XG5cdFx0XHRcdH1cblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnByb2Nlc3NTdHJpcGVSZXNwb25zZShyZXNwb25zZS5zb3VyY2UpO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHRwcm9jZXNzU3RyaXBlUmVzcG9uc2U6IGZ1bmN0aW9uKHNvdXJjZSkge1xuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ucmVzZXQoKTtcblxuXHRcdFx0Ly8gSW5zZXJ0IHRoZSBTb3VyY2UgaW50byB0aGUgZm9ybSBzbyBpdCBnZXRzIHN1Ym1pdHRlZCB0byB0aGUgc2VydmVyLlxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybS5hcHBlbmQoXG5cdFx0XHRcdFwiPGlucHV0IHR5cGU9J2hpZGRlbicgY2xhc3M9J3N0cmlwZS1jb25uZWN0LXNvdXJjZScgbmFtZT0nc3RyaXBlX2Nvbm5lY3Rfc291cmNlJyB2YWx1ZT0nXCIgK1xuXHRcdFx0XHRcdHNvdXJjZS5pZCArXG5cdFx0XHRcdFx0XCInLz5cIlxuXHRcdFx0KTtcblxuXHRcdFx0aWYgKCQoJ2Zvcm0jYWRkX3BheW1lbnRfbWV0aG9kJykubGVuZ3RoKSB7XG5cdFx0XHRcdCQod2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybSkub2ZmKFxuXHRcdFx0XHRcdCdzdWJtaXQnLFxuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0ub25TdWJtaXRcblx0XHRcdFx0KTtcblx0XHRcdH1cblxuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybS5zdWJtaXQoKTtcblx0XHR9LFxuXG5cdFx0b25TdWJtaXQ6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdGlmICghd2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDb25uZWN0Q2hvc2VuKCkpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoXG5cdFx0XHRcdCF3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5pc1N0cmlwZVNhdmVDYXJkQ2hvc2VuKCkgJiZcblx0XHRcdFx0IXdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmhhc1NvdXJjZSgpICYmXG5cdFx0XHRcdCF3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5oYXNUb2tlbigpXG5cdFx0XHQpIHtcblx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmJsb2NrKCk7XG5cblx0XHRcdFx0Ly8gU3RyaXBlIENoZWNrb3V0LlxuXHRcdFx0XHRpZiAoXG5cdFx0XHRcdFx0J3llcycgPT09IHdjdl9zY19wYXJhbXMuaXNfc3RyaXBlX2NoZWNrb3V0ICYmXG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVNb2RhbE5lZWRlZCgpICYmXG5cdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uaXNTdHJpcGVDYXJkQ2hvc2VuKClcblx0XHRcdFx0KSB7XG5cdFx0XHRcdFx0aWYgKCd5ZXMnID09PSB3Y3Zfc2NfcGFyYW1zLmlzX2NoZWNrb3V0KSB7XG5cdFx0XHRcdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0ub3Blbk1vZGFsKCk7XG5cdFx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cblx0XHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uY3JlYXRlU291cmNlKCk7XG5cblx0XHRcdFx0Ly8gUHJldmVudCBmb3JtIHN1Ym1pdHRpbmdcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHRnZXRTZWxlY3RlZFBheW1lbnRFbGVtZW50OiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiAkKCcucGF5bWVudF9tZXRob2RzIGlucHV0W25hbWU9XCJwYXltZW50X21ldGhvZFwiXTpjaGVja2VkJyk7XG5cdFx0fSxcblxuXHRcdG9uRXJyb3I6IGZ1bmN0aW9uKGUsIHJlc3VsdCkge1xuXHRcdFx0dmFyIG1lc3NhZ2UgPSByZXN1bHQuZXJyb3IubWVzc2FnZTtcblx0XHRcdHZhciBzZWxlY3RlZE1ldGhvZEVsZW1lbnQgPSB3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybVxuXHRcdFx0XHQuZ2V0U2VsZWN0ZWRQYXltZW50RWxlbWVudCgpXG5cdFx0XHRcdC5jbG9zZXN0KCdsaScpO1xuXHRcdFx0dmFyIHNhdmVkVG9rZW5zID0gc2VsZWN0ZWRNZXRob2RFbGVtZW50LmZpbmQoXG5cdFx0XHRcdCcud29vY29tbWVyY2UtU2F2ZWRQYXltZW50TWV0aG9kcy10b2tlbklucHV0J1xuXHRcdFx0KTtcblx0XHRcdHZhciBlcnJvckNvbnRhaW5lcjtcblxuXHRcdFx0aWYgKHNhdmVkVG9rZW5zLmxlbmd0aCkge1xuXHRcdFx0XHQvLyBJbiBjYXNlIHRoZXJlIGFyZSBzYXZlZCBjYXJkcyB0b28sIGRpc3BsYXkgdGhlIG1lc3NhZ2UgbmV4dCB0byB0aGUgY29ycmVjdCBvbmUuXG5cdFx0XHRcdHZhciBzZWxlY3RlZFRva2VuID0gc2F2ZWRUb2tlbnMuZmlsdGVyKCc6Y2hlY2tlZCcpO1xuXG5cdFx0XHRcdGlmIChcblx0XHRcdFx0XHRzZWxlY3RlZFRva2VuLmNsb3Nlc3QoXG5cdFx0XHRcdFx0XHQnLndvb2NvbW1lcmNlLVNhdmVkUGF5bWVudE1ldGhvZHMtbmV3J1xuXHRcdFx0XHRcdCkubGVuZ3RoXG5cdFx0XHRcdCkge1xuXHRcdFx0XHRcdC8vIERpc3BsYXkgdGhlIGVycm9yIG5leHQgdG8gdGhlIENDIGZpZWxkcyBpZiBhIG5ldyBjYXJkIGlzIGJlaW5nIGVudGVyZWQuXG5cdFx0XHRcdFx0ZXJyb3JDb250YWluZXIgPSAkKFxuXHRcdFx0XHRcdFx0JyN3Y3Ytc3RyaXBlLWNvbm5lY3QtY2MtZm9ybSAuc3RyaXBlLWNvbm5lY3Qtc291cmNlLWVycm9ycydcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdC8vIERpc3BsYXkgdGhlIGVycm9yIG5leHQgdG8gdGhlIGNob3NlbiBzYXZlZCBjYXJkLlxuXHRcdFx0XHRcdGVycm9yQ29udGFpbmVyID0gc2VsZWN0ZWRUb2tlblxuXHRcdFx0XHRcdFx0LmNsb3Nlc3QoJ2xpJylcblx0XHRcdFx0XHRcdC5maW5kKCcuc3RyaXBlLWNvbm5lY3Qtc291cmNlLWVycm9ycycpO1xuXHRcdFx0XHR9XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHQvLyBXaGVuIG5vIHNhdmVkIGNhcmRzIGFyZSBhdmFpbGFibGUsIGRpc3BsYXkgdGhlIGVycm9yIG5leHQgdG8gQ0MgZmllbGRzLlxuXHRcdFx0XHRlcnJvckNvbnRhaW5lciA9IHNlbGVjdGVkTWV0aG9kRWxlbWVudC5maW5kKFxuXHRcdFx0XHRcdCcuc3RyaXBlLWNvbm5lY3Qtc291cmNlLWVycm9ycydcblx0XHRcdFx0KTtcblx0XHRcdH1cblxuXHRcdFx0Lypcblx0XHRcdCAqIEN1c3RvbWVycyBkbyBub3QgbmVlZCB0byBrbm93IHRoZSBzcGVjaWZpY3Mgb2YgdGhlIGJlbG93IHR5cGUgb2YgZXJyb3JzXG5cdFx0XHQgKiB0aGVyZWZvcmUgcmV0dXJuIGEgZ2VuZXJpYyBsb2NhbGl6YWJsZSBlcnJvciBtZXNzYWdlLlxuXHRcdFx0ICovXG5cdFx0XHRpZiAoXG5cdFx0XHRcdCdpbnZhbGlkX3JlcXVlc3RfZXJyb3InID09PSByZXN1bHQuZXJyb3IudHlwZSB8fFxuXHRcdFx0XHQnYXBpX2Nvbm5lY3Rpb25fZXJyb3InID09PSByZXN1bHQuZXJyb3IudHlwZSB8fFxuXHRcdFx0XHQnYXBpX2Vycm9yJyA9PT0gcmVzdWx0LmVycm9yLnR5cGUgfHxcblx0XHRcdFx0J2F1dGhlbnRpY2F0aW9uX2Vycm9yJyA9PT0gcmVzdWx0LmVycm9yLnR5cGUgfHxcblx0XHRcdFx0J3JhdGVfbGltaXRfZXJyb3InID09PSByZXN1bHQuZXJyb3IudHlwZVxuXHRcdFx0KSB7XG5cdFx0XHRcdG1lc3NhZ2UgPSB3Y3Zfc2NfcGFyYW1zLmludmFsaWRfcmVxdWVzdF9lcnJvcjtcblx0XHRcdH1cblxuXHRcdFx0aWYgKFxuXHRcdFx0XHQnY2FyZF9lcnJvcicgPT09IHJlc3VsdC5lcnJvci50eXBlICYmXG5cdFx0XHRcdHdjdl9zY19wYXJhbXMuaGFzT3duUHJvcGVydHkocmVzdWx0LmVycm9yLmNvZGUpXG5cdFx0XHQpIHtcblx0XHRcdFx0bWVzc2FnZSA9IHdjdl9zY19wYXJhbXNbcmVzdWx0LmVycm9yLmNvZGVdO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoXG5cdFx0XHRcdCd2YWxpZGF0aW9uX2Vycm9yJyA9PT0gcmVzdWx0LmVycm9yLnR5cGUgJiZcblx0XHRcdFx0d2N2X3NjX3BhcmFtcy5oYXNPd25Qcm9wZXJ0eShyZXN1bHQuZXJyb3IuY29kZSlcblx0XHRcdCkge1xuXHRcdFx0XHRtZXNzYWdlID0gd2N2X3NjX3BhcmFtc1tyZXN1bHQuZXJyb3IuY29kZV07XG5cdFx0XHR9XG5cblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLnJlc2V0KCk7XG5cdFx0XHQkKCcud29vY29tbWVyY2UtTm90aWNlR3JvdXAtY2hlY2tvdXQnKS5yZW1vdmUoKTtcblx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZVxuXHRcdFx0Y29uc29sZS5sb2cocmVzdWx0LmVycm9yLm1lc3NhZ2UpOyAvLyBMZWF2ZSBmb3IgdHJvdWJsZXNob290aW5nLlxuXHRcdFx0ZXJyb3JDb250YWluZXIuaHRtbChcblx0XHRcdFx0Jzx1bCBjbGFzcz1cIndvb2NvbW1lcmNlX2Vycm9yIHdvb2NvbW1lcmNlLWVycm9yIHdjdi1zdHJpcGUtY29ubmVjdC1lcnJvclwiPjxsaT4nICtcblx0XHRcdFx0XHRtZXNzYWdlICtcblx0XHRcdFx0XHQnPC9saT48L3VsPidcblx0XHRcdCk7XG5cblx0XHRcdGlmICgkKCcud2N2LXN0cmlwZS1jb25uZWN0LWVycm9yJykubGVuZ3RoKSB7XG5cdFx0XHRcdCQoJ2h0bWwsIGJvZHknKS5hbmltYXRlKFxuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdHNjcm9sbFRvcDpcblx0XHRcdFx0XHRcdFx0JCgnLndjdi1zdHJpcGUtY29ubmVjdC1lcnJvcicpLm9mZnNldCgpLnRvcCAtIDIwMFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0MjAwXG5cdFx0XHRcdCk7XG5cdFx0XHR9XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS51bmJsb2NrKCk7XG5cdFx0fSxcblxuXHRcdHN1Ym1pdEVycm9yOiBmdW5jdGlvbihlcnJvcl9tZXNzYWdlKSB7XG5cdFx0XHQkKFxuXHRcdFx0XHQnLndvb2NvbW1lcmNlLU5vdGljZUdyb3VwLWNoZWNrb3V0LCAud29vY29tbWVyY2UtZXJyb3IsIC53b29jb21tZXJjZS1tZXNzYWdlJ1xuXHRcdFx0KS5yZW1vdmUoKTtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0ucHJlcGVuZChcblx0XHRcdFx0JzxkaXYgY2xhc3M9XCJ3b29jb21tZXJjZS1Ob3RpY2VHcm91cCB3b29jb21tZXJjZS1Ob3RpY2VHcm91cC1jaGVja291dFwiPicgK1xuXHRcdFx0XHRcdGVycm9yX21lc3NhZ2UgK1xuXHRcdFx0XHRcdCc8L2Rpdj4nXG5cdFx0XHQpO1xuXHRcdFx0d2N2X3N0cmlwZV9jb25uZWN0X2Zvcm0uZm9ybS5yZW1vdmVDbGFzcygncHJvY2Vzc2luZycpLnVuYmxvY2soKTtcblx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm1cblx0XHRcdFx0LmZpbmQoJy5pbnB1dC10ZXh0LCBzZWxlY3QsIGlucHV0OmNoZWNrYm94Jylcblx0XHRcdFx0LmJsdXIoKTtcblxuXHRcdFx0dmFyIHNlbGVjdG9yID0gJyc7XG5cblx0XHRcdGlmICgkKCcjYWRkX3BheW1lbnRfbWV0aG9kJykubGVuZ3RoKSB7XG5cdFx0XHRcdHNlbGVjdG9yID0gJCgnI2FkZF9wYXltZW50X21ldGhvZCcpO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoJCgnI29yZGVyX3JldmlldycpLmxlbmd0aCkge1xuXHRcdFx0XHRzZWxlY3RvciA9ICQoJyNvcmRlcl9yZXZpZXcnKTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCQoJ2Zvcm0uY2hlY2tvdXQnKS5sZW5ndGgpIHtcblx0XHRcdFx0c2VsZWN0b3IgPSAkKCdmb3JtLmNoZWNrb3V0Jyk7XG5cdFx0XHR9XG5cblx0XHRcdGlmIChzZWxlY3Rvci5sZW5ndGgpIHtcblx0XHRcdFx0JCgnaHRtbCwgYm9keScpLmFuaW1hdGUoXG5cdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0c2Nyb2xsVG9wOiBzZWxlY3Rvci5vZmZzZXQoKS50b3AgLSAxMDBcblx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdDUwMFxuXHRcdFx0XHQpO1xuXHRcdFx0fVxuXG5cdFx0XHQkKGRvY3VtZW50LmJvZHkpLnRyaWdnZXIoJ2NoZWNrb3V0X2Vycm9yJyk7XG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS51bmJsb2NrKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEhhbmRsZXMgY2hhbmdlcyBpbiB0aGUgaGFzaCBpbiBvcmRlciB0byBzaG93IGEgbW9kYWwgZm9yIFBheW1lbnRJbnRlbnQgY29uZmlybWF0aW9ucy5cblx0XHQgKlxuXHRcdCAqIExpc3RlbnMgZm9yIGBoYXNoY2hhbmdlYCBldmVudHMgYW5kIGNoZWNrcyBmb3IgYSBoYXNoIGluIHRoZSBmb2xsb3dpbmcgZm9ybWF0OlxuXHRcdCAqICNjb25maXJtLXBpLTxpbnRlbnRDbGllbnRTZWNyZXQ+OjxzdWNjZXNzUmVkaXJlY3RVUkw+XG5cdFx0ICpcblx0XHQgKiBJZiBzdWNoIGEgaGFzaCBhcHBlYXJzLCB0aGUgcGFydGlhbHMgd2lsbCBiZSB1c2VkIHRvIGNhbGwgYHN0cmlwZS5oYW5kbGVDYXJkQWN0aW9uYFxuXHRcdCAqIGluIG9yZGVyIHRvIGFsbG93IGN1c3RvbWVycyB0byBjb25maXJtIGFuIDNEUy9TQ0EgYXV0aG9yaXphdGlvbi5cblx0XHQgKlxuXHRcdCAqIFRob3NlIHJlZGlyZWN0cy9oYXNoZXMgYXJlIGdlbmVyYXRlZCBpbiBgV0NWX1NDX1BheW1lbnRfR2F0ZXdheTo6Z2VuZXJhdGVfY2hhcmdlc190cmFuc2ZlcnNfcGF5bWVudGAuXG5cdFx0ICovXG5cdFx0b25IYXNoQ2hhbmdlOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBwYXJ0aWFscyA9IHdpbmRvdy5sb2NhdGlvbi5oYXNoLm1hdGNoKFxuXHRcdFx0XHQvXiM/Y29uZmlybS1waS0oW146XSspOiguKykkL1xuXHRcdFx0KTtcblxuXHRcdFx0aWYgKCFwYXJ0aWFscyB8fCAzID4gcGFydGlhbHMubGVuZ3RoKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0dmFyIGludGVudENsaWVudFNlY3JldCA9IHBhcnRpYWxzWzFdO1xuXHRcdFx0dmFyIHJlZGlyZWN0VVJMID0gZGVjb2RlVVJJQ29tcG9uZW50KHBhcnRpYWxzWzJdKTtcblxuXHRcdFx0Ly8gQ2xlYW51cCB0aGUgVVJMXG5cdFx0XHR3aW5kb3cubG9jYXRpb24uaGFzaCA9ICcnO1xuXG5cdFx0XHR3Y3Zfc3RyaXBlX2Nvbm5lY3RfZm9ybS5vcGVuSW50ZW50TW9kYWwoXG5cdFx0XHRcdGludGVudENsaWVudFNlY3JldCxcblx0XHRcdFx0cmVkaXJlY3RVUkxcblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE9wZW5zIHRoZSBtb2RhbCBmb3IgUGF5bWVudEludGVudCBhdXRob3JpemF0aW9ucy5cblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSAgaW50ZW50Q2xpZW50U2VjcmV0IFRoZSBjbGllbnQgc2VjcmV0IG9mIHRoZSBpbnRlbnQuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9ICByZWRpcmVjdFVSTCAgICAgICAgVGhlIFVSTCB0byBwaW5nIG9uIGZhaWwgb3IgcmVkaXJlY3QgdG8gb24gc3VjY2Vzcy5cblx0XHQgKiBAcGFyYW0ge2Jvb2xlYW59IGFsd2F5c1JlZGlyZWN0ICAgICBJZiBzZXQgdG8gdHJ1ZSwgYW4gaW1tZWRpYXRlIHJlZGlyZWN0IHdpbGwgaGFwcGVuIG5vIG1hdHRlciB0aGUgcmVzdWx0LlxuXHRcdCAqICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIElmIG5vdCwgYW4gZXJyb3Igd2lsbCBiZSBkaXNwbGF5ZWQgb24gZmFpbHVyZS5cblx0XHQgKi9cblx0XHRvcGVuSW50ZW50TW9kYWw6IGZ1bmN0aW9uKFxuXHRcdFx0aW50ZW50Q2xpZW50U2VjcmV0LFxuXHRcdFx0cmVkaXJlY3RVUkwsXG5cdFx0XHRhbHdheXNSZWRpcmVjdFxuXHRcdCkge1xuXHRcdFx0c3RyaXBlXG5cdFx0XHRcdC5oYW5kbGVDYXJkQWN0aW9uKGludGVudENsaWVudFNlY3JldClcblx0XHRcdFx0LnRoZW4oZnVuY3Rpb24ocmVzcG9uc2UpIHtcblx0XHRcdFx0XHRpZiAocmVzcG9uc2UuZXJyb3IpIHtcblx0XHRcdFx0XHRcdHRocm93IHJlc3BvbnNlLmVycm9yO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGlmIChcblx0XHRcdFx0XHRcdCdyZXF1aXJlc19jb25maXJtYXRpb24nICE9PVxuXHRcdFx0XHRcdFx0cmVzcG9uc2UucGF5bWVudEludGVudC5zdGF0dXNcblx0XHRcdFx0XHQpIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHR3aW5kb3cubG9jYXRpb24gPSByZWRpcmVjdFVSTDtcblx0XHRcdFx0fSlcblx0XHRcdFx0LmNhdGNoKGZ1bmN0aW9uKGVycm9yKSB7XG5cdFx0XHRcdFx0aWYgKGFsd2F5c1JlZGlyZWN0KSB7XG5cdFx0XHRcdFx0XHRyZXR1cm4gKHdpbmRvdy5sb2NhdGlvbiA9IHJlZGlyZWN0VVJMKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQkKGRvY3VtZW50LmJvZHkpLnRyaWdnZXIoJ3N0cmlwZUNvbm5lY3RFcnJvcicsIHtcblx0XHRcdFx0XHRcdGVycm9yOiBlcnJvclxuXHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmZvcm0ucmVtb3ZlQ2xhc3MoJ3Byb2Nlc3NpbmcnKTtcblxuXHRcdFx0XHRcdC8vIFJlcG9ydCBiYWNrIHRvIHRoZSBzZXJ2ZXIuXG5cdFx0XHRcdFx0JC5nZXQocmVkaXJlY3RVUkwgKyAnJmlzX2FqYXgnKTtcblx0XHRcdFx0fSk7XG5cdFx0fVxuXHR9O1xuXG5cdHdjdl9zdHJpcGVfY29ubmVjdF9mb3JtLmluaXQoKTtcbn0pO1xuIl19

//# sourceMappingURL=stripe-checkout.js.map
