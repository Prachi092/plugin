(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

/* eslint-disable no-console */
jQuery(function ($) {
  'use strict';
  /**
   * Object to handle Stripe hosted checkout.
   */

  var stripe_connect_hosted_checkout = {
    init: function init() {
      this.form = $('form.checkout, form#order_review, form#add_payment_method'); // checkout page

      if ($('form.woocommerce-checkout').length) {
        this.form = $('form.woocommerce-checkout');
      } // Pay order page


      if ($('form#order_review').length) {
        this.form = $('form#order_review');
      } // Add payment method page


      if ($('form#add_payment_method').length) {
        this.form = $('form#add_payment_method');
      } // Add event handlers.


      $('form.woocommerce-checkout').on('change', this.reset);
      $(document.body).on('click', '#place_order', function (e) {
        if (stripe_connect_hosted_checkout.isCheckoutFormValid()) {
          stripe_connect_hosted_checkout.startCheckout(e);
        }
      }); // TODO: Possibly remove.

      $('form.checkout').on('checkout_place_order_stripe-connect', function (e) {
        if (stripe_connect_hosted_checkout.isCheckoutFormValid()) {
          stripe_connect_hosted_checkout.startCheckout(e);
        }
      });
    },
    isHostedCheckout: function isHostedCheckout() {
      if ('hosted' === window.wcv_sc_params.checkout_type) {
        return true;
      }

      return false;
    },
    isSelectedGateway: function isSelectedGateway() {
      if ($('#payment_method_stripe-connect').is(':checked')) {
        return true;
      }

      return false;
    },
    isCheckoutFormValid: function isCheckoutFormValid() {
      var formValid = true;
      var $form = stripe_connect_hosted_checkout.form;
      $form.find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
      var $required_inputs = $form.find('.validate-required:not(.woocommerce-validated)').find('.input-text, select, input:checkbox');

      if ($required_inputs.length) {
        $required_inputs.each(function () {
          if ($(this).val() === '') {
            formValid = false;
          }
        });
      }

      if (!formValid) {
        stripe_connect_hosted_checkout.submitError('<ul class="woocommerce-error"><li>' + window.wcv_sc_params.required_fields_message + '</li></ul>');
        return false;
      }

      stripe_connect_hosted_checkout.triggerUpdateCheckout();
      return true;
    },
    triggerUpdateCheckout: function triggerUpdateCheckout() {
      $(document.body).trigger('update_checkout');
      stripe_connect_hosted_checkout.unblock();
    },
    startCheckout: function startCheckout(event) {
      event.preventDefault();
      var $form = stripe_connect_hosted_checkout.form;

      if ($form.is('.processing')) {
        return false;
      }

      $form.addClass('processing').block();

      if (!stripe_connect_hosted_checkout.isSelectedGateway()) {
        $form.unblock();
        return;
      }
      /**
       * Create checkout session and redirect to Stripe Checkout
       */


      var data = {
        action: 'create_checkout_session',
        nonce: window.wcv_sc_params.create_session_nonce,
        checkout_data: $form.serialize()
      };
      var ajaxUrl = window.wcv_sc_params.wcv_sc_ajax_url.toString();

      try {
        var response = $.ajax({
          url: ajaxUrl.replace('%%endpoint%%', 'create_checkout_session'),
          data: data,
          type: 'POST'
        }).done(function (responseData, statusText, jqXHR) {
          if (null == responseData) {
            $form.unblock();
            return false;
          }

          var hasError = responseData.error || true == responseData.data.error || false == responseData.success || 'success' != statusText || 200 != jqXHR.status ? true : false;

          if (hasError) {
            stripe_connect_hosted_checkout.submitError(responseData.data.message);
            return false;
          }

          if (responseData.success && !hasError) {
            if (responseData.data.id || responseData.data.line_items) {
              var checkout = stripe_connect_hosted_checkout.handleCheckoutForm(responseData.data);
              $form.unblock();
              return checkout;
            }
          }

          return responseData.data;
        }).error(function (error) {
          stripe_connect_hosted_checkout.submitError(window.wcv_sc_params.error_message_prefix + ' ' + error.responseText);
          return false;
        });
        return response;
      } catch (e) {
        return false;
      }
    },
    handleCheckoutForm: function handleCheckoutForm(session) {
      try {
        // eslint-disable-next-line no-undef
        var stripe = Stripe(window.wcv_sc_params.key);
      } catch (error) {
        console.log(error);
      }

      var sessionId = session.id;
      stripe.redirectToCheckout({
        sessionId: sessionId
      }).then(function (result) {
        if (result.error) {
          stripe_connect_hosted_checkout.submitError(window.wcv_sc_params.error_message_prefix + ' ' + result.message);
        }
      }).error(function (error) {
        stripe_connect_hosted_checkout.submitError(window.wcv_sc_params.error_message_prefix + ' ' + error.responseText);
        return false;
      });
      return false;
    },
    block: function block() {
      if (!stripe_connect_hosted_checkout.isMobile()) {
        stripe_connect_hosted_checkout.form.addClass('processing').block();
      }
    },
    unblock: function unblock() {
      stripe_connect_hosted_checkout.form.unblock();
    },
    reset: function reset() {
      $('.wcv-stripe-connect-error, .stripeConnectError, .stripe_connect_token').remove();
    },
    isMobile: function isMobile() {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return true;
      }

      return false;
    },
    submitError: function submitError(error_message) {
      $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
      stripe_connect_hosted_checkout.form.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout woocommerce-error">' + error_message + '</div>');
      stripe_connect_hosted_checkout.form.removeClass('processing').unblock();
      stripe_connect_hosted_checkout.form.find('.input-text, select, input:checkbox').blur();
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
      stripe_connect_hosted_checkout.unblock();
    }
  };
  stripe_connect_hosted_checkout.init();
});

},{}]},{},[1])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvc3JjL2pzL3N0cmlwZS1ob3N0ZWQtY2hlY2tvdXQuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7OztBQ0FBO0FBQ0EsTUFBTSxDQUFDLFVBQVMsQ0FBVCxFQUFZO0FBQ2xCO0FBRUE7Ozs7QUFHQSxNQUFJLDhCQUE4QixHQUFHO0FBQ3BDLElBQUEsSUFBSSxFQUFFLGdCQUFXO0FBQ2hCLFdBQUssSUFBTCxHQUFZLENBQUMsQ0FDWiwyREFEWSxDQUFiLENBRGdCLENBSWhCOztBQUNBLFVBQUksQ0FBQyxDQUFDLDJCQUFELENBQUQsQ0FBK0IsTUFBbkMsRUFBMkM7QUFDMUMsYUFBSyxJQUFMLEdBQVksQ0FBQyxDQUFDLDJCQUFELENBQWI7QUFDQSxPQVBlLENBU2hCOzs7QUFDQSxVQUFJLENBQUMsQ0FBQyxtQkFBRCxDQUFELENBQXVCLE1BQTNCLEVBQW1DO0FBQ2xDLGFBQUssSUFBTCxHQUFZLENBQUMsQ0FBQyxtQkFBRCxDQUFiO0FBQ0EsT0FaZSxDQWNoQjs7O0FBQ0EsVUFBSSxDQUFDLENBQUMseUJBQUQsQ0FBRCxDQUE2QixNQUFqQyxFQUF5QztBQUN4QyxhQUFLLElBQUwsR0FBWSxDQUFDLENBQUMseUJBQUQsQ0FBYjtBQUNBLE9BakJlLENBbUJoQjs7O0FBQ0EsTUFBQSxDQUFDLENBQUMsMkJBQUQsQ0FBRCxDQUErQixFQUEvQixDQUFrQyxRQUFsQyxFQUE0QyxLQUFLLEtBQWpEO0FBRUEsTUFBQSxDQUFDLENBQUMsUUFBUSxDQUFDLElBQVYsQ0FBRCxDQUFpQixFQUFqQixDQUFvQixPQUFwQixFQUE2QixjQUE3QixFQUE2QyxVQUFTLENBQVQsRUFBWTtBQUN4RCxZQUFJLDhCQUE4QixDQUFDLG1CQUEvQixFQUFKLEVBQTBEO0FBQ3pELFVBQUEsOEJBQThCLENBQUMsYUFBL0IsQ0FBNkMsQ0FBN0M7QUFDQTtBQUNELE9BSkQsRUF0QmdCLENBNEJoQjs7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QUFDLENBQUMsWUFBRCxDQUFELENBQWdCLE9BQWhCLENBQ0M7QUFDQyxVQUFBLFNBQVMsRUFBRSxRQUFRLENBQUMsTUFBVCxHQUFrQixHQUFsQixHQUF3QjtBQURwQyxTQURELEVBSUMsR0FKRDtBQU1BOztBQUVELE1BQUEsQ0FBQyxDQUFDLFFBQVEsQ0FBQyxJQUFWLENBQUQsQ0FBaUIsT0FBakIsQ0FBeUIsZ0JBQXpCO0FBQ0EsTUFBQSw4QkFBOEIsQ0FBQyxPQUEvQjtBQUNBO0FBbFNtQyxHQUFyQztBQXFTQSxFQUFBLDhCQUE4QixDQUFDLElBQS9CO0FBQ0EsQ0E1U0ssQ0FBTiIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCl7ZnVuY3Rpb24gcihlLG4sdCl7ZnVuY3Rpb24gbyhpLGYpe2lmKCFuW2ldKXtpZighZVtpXSl7dmFyIGM9XCJmdW5jdGlvblwiPT10eXBlb2YgcmVxdWlyZSYmcmVxdWlyZTtpZighZiYmYylyZXR1cm4gYyhpLCEwKTtpZih1KXJldHVybiB1KGksITApO3ZhciBhPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIraStcIidcIik7dGhyb3cgYS5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGF9dmFyIHA9bltpXT17ZXhwb3J0czp7fX07ZVtpXVswXS5jYWxsKHAuZXhwb3J0cyxmdW5jdGlvbihyKXt2YXIgbj1lW2ldWzFdW3JdO3JldHVybiBvKG58fHIpfSxwLHAuZXhwb3J0cyxyLGUsbix0KX1yZXR1cm4gbltpXS5leHBvcnRzfWZvcih2YXIgdT1cImZ1bmN0aW9uXCI9PXR5cGVvZiByZXF1aXJlJiZyZXF1aXJlLGk9MDtpPHQubGVuZ3RoO2krKylvKHRbaV0pO3JldHVybiBvfXJldHVybiByfSkoKSIsIi8qIGVzbGludC1kaXNhYmxlIG5vLWNvbnNvbGUgKi9cbmpRdWVyeShmdW5jdGlvbigkKSB7XG5cdCd1c2Ugc3RyaWN0JztcblxuXHQvKipcblx0ICogT2JqZWN0IHRvIGhhbmRsZSBTdHJpcGUgaG9zdGVkIGNoZWNrb3V0LlxuXHQgKi9cblx0dmFyIHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dCA9IHtcblx0XHRpbml0OiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuZm9ybSA9ICQoXG5cdFx0XHRcdCdmb3JtLmNoZWNrb3V0LCBmb3JtI29yZGVyX3JldmlldywgZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnXG5cdFx0XHQpO1xuXHRcdFx0Ly8gY2hlY2tvdXQgcGFnZVxuXHRcdFx0aWYgKCQoJ2Zvcm0ud29vY29tbWVyY2UtY2hlY2tvdXQnKS5sZW5ndGgpIHtcblx0XHRcdFx0dGhpcy5mb3JtID0gJCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBQYXkgb3JkZXIgcGFnZVxuXHRcdFx0aWYgKCQoJ2Zvcm0jb3JkZXJfcmV2aWV3JykubGVuZ3RoKSB7XG5cdFx0XHRcdHRoaXMuZm9ybSA9ICQoJ2Zvcm0jb3JkZXJfcmV2aWV3Jyk7XG5cdFx0XHR9XG5cblx0XHRcdC8vIEFkZCBwYXltZW50IG1ldGhvZCBwYWdlXG5cdFx0XHRpZiAoJCgnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnKS5sZW5ndGgpIHtcblx0XHRcdFx0dGhpcy5mb3JtID0gJCgnZm9ybSNhZGRfcGF5bWVudF9tZXRob2QnKTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gQWRkIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0JCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpLm9uKCdjaGFuZ2UnLCB0aGlzLnJlc2V0KTtcblxuXHRcdFx0JChkb2N1bWVudC5ib2R5KS5vbignY2xpY2snLCAnI3BsYWNlX29yZGVyJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRpZiAoc3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LmlzQ2hlY2tvdXRGb3JtVmFsaWQoKSkge1xuXHRcdFx0XHRcdHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5zdGFydENoZWNrb3V0KGUpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblxuXHRcdFx0Ly8gVE9ETzogUG9zc2libHkgcmVtb3ZlLlxuXHRcdFx0JCgnZm9ybS5jaGVja291dCcpLm9uKFxuXHRcdFx0XHQnY2hlY2tvdXRfcGxhY2Vfb3JkZXJfc3RyaXBlLWNvbm5lY3QnLFxuXHRcdFx0XHRmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0aWYgKHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5pc0NoZWNrb3V0Rm9ybVZhbGlkKCkpIHtcblx0XHRcdFx0XHRcdHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5zdGFydENoZWNrb3V0KGUpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0KTtcblx0XHR9LFxuXG5cdFx0aXNIb3N0ZWRDaGVja291dDogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZiAoJ2hvc3RlZCcgPT09IHdpbmRvdy53Y3Zfc2NfcGFyYW1zLmNoZWNrb3V0X3R5cGUpIHtcblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fSxcblxuXHRcdGlzU2VsZWN0ZWRHYXRld2F5OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmICgkKCcjcGF5bWVudF9tZXRob2Rfc3RyaXBlLWNvbm5lY3QnKS5pcygnOmNoZWNrZWQnKSkge1xuXHRcdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHRcdH1cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9LFxuXG5cdFx0aXNDaGVja291dEZvcm1WYWxpZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgZm9ybVZhbGlkID0gdHJ1ZTtcblx0XHRcdHZhciAkZm9ybSA9IHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5mb3JtO1xuXG5cdFx0XHQkZm9ybVxuXHRcdFx0XHQuZmluZCgnLmlucHV0LXRleHQsIHNlbGVjdCwgaW5wdXQ6Y2hlY2tib3gnKVxuXHRcdFx0XHQudHJpZ2dlcigndmFsaWRhdGUnKVxuXHRcdFx0XHQudHJpZ2dlcignYmx1cicpO1xuXG5cdFx0XHR2YXIgJHJlcXVpcmVkX2lucHV0cyA9ICRmb3JtXG5cdFx0XHRcdC5maW5kKCcudmFsaWRhdGUtcmVxdWlyZWQ6bm90KC53b29jb21tZXJjZS12YWxpZGF0ZWQpJylcblx0XHRcdFx0LmZpbmQoJy5pbnB1dC10ZXh0LCBzZWxlY3QsIGlucHV0OmNoZWNrYm94Jyk7XG5cblx0XHRcdGlmICgkcmVxdWlyZWRfaW5wdXRzLmxlbmd0aCkge1xuXHRcdFx0XHQkcmVxdWlyZWRfaW5wdXRzLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0aWYgKCQodGhpcykudmFsKCkgPT09ICcnKSB7XG5cdFx0XHRcdFx0XHRmb3JtVmFsaWQgPSBmYWxzZTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIWZvcm1WYWxpZCkge1xuXHRcdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuc3VibWl0RXJyb3IoXG5cdFx0XHRcdFx0Jzx1bCBjbGFzcz1cIndvb2NvbW1lcmNlLWVycm9yXCI+PGxpPicgK1xuXHRcdFx0XHRcdFx0d2luZG93Lndjdl9zY19wYXJhbXMucmVxdWlyZWRfZmllbGRzX21lc3NhZ2UgK1xuXHRcdFx0XHRcdFx0JzwvbGk+PC91bD4nXG5cdFx0XHRcdCk7XG5cblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQudHJpZ2dlclVwZGF0ZUNoZWNrb3V0KCk7XG5cdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHR9LFxuXG5cdFx0dHJpZ2dlclVwZGF0ZUNoZWNrb3V0OiBmdW5jdGlvbigpIHtcblx0XHRcdCQoZG9jdW1lbnQuYm9keSkudHJpZ2dlcigndXBkYXRlX2NoZWNrb3V0Jyk7XG5cdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQudW5ibG9jaygpO1xuXHRcdH0sXG5cblx0XHRzdGFydENoZWNrb3V0OiBmdW5jdGlvbihldmVudCkge1xuXHRcdFx0ZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuXHRcdFx0dmFyICRmb3JtID0gc3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LmZvcm07XG5cblx0XHRcdGlmICgkZm9ybS5pcygnLnByb2Nlc3NpbmcnKSkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdCRmb3JtLmFkZENsYXNzKCdwcm9jZXNzaW5nJykuYmxvY2soKTtcblx0XHRcdGlmICghc3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LmlzU2VsZWN0ZWRHYXRld2F5KCkpIHtcblx0XHRcdFx0JGZvcm0udW5ibG9jaygpO1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogQ3JlYXRlIGNoZWNrb3V0IHNlc3Npb24gYW5kIHJlZGlyZWN0IHRvIFN0cmlwZSBDaGVja291dFxuXHRcdFx0ICovXG5cdFx0XHR2YXIgZGF0YSA9IHtcblx0XHRcdFx0YWN0aW9uOiAnY3JlYXRlX2NoZWNrb3V0X3Nlc3Npb24nLFxuXHRcdFx0XHRub25jZTogd2luZG93Lndjdl9zY19wYXJhbXMuY3JlYXRlX3Nlc3Npb25fbm9uY2UsXG5cdFx0XHRcdGNoZWNrb3V0X2RhdGE6ICRmb3JtLnNlcmlhbGl6ZSgpXG5cdFx0XHR9O1xuXG5cdFx0XHR2YXIgYWpheFVybCA9IHdpbmRvdy53Y3Zfc2NfcGFyYW1zLndjdl9zY19hamF4X3VybC50b1N0cmluZygpO1xuXG5cdFx0XHR0cnkge1xuXHRcdFx0XHRjb25zdCByZXNwb25zZSA9ICQuYWpheCh7XG5cdFx0XHRcdFx0dXJsOiBhamF4VXJsLnJlcGxhY2UoXG5cdFx0XHRcdFx0XHQnJSVlbmRwb2ludCUlJyxcblx0XHRcdFx0XHRcdCdjcmVhdGVfY2hlY2tvdXRfc2Vzc2lvbidcblx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdGRhdGE6IGRhdGEsXG5cdFx0XHRcdFx0dHlwZTogJ1BPU1QnXG5cdFx0XHRcdH0pXG5cdFx0XHRcdFx0LmRvbmUoZnVuY3Rpb24ocmVzcG9uc2VEYXRhLCBzdGF0dXNUZXh0LCBqcVhIUikge1xuXHRcdFx0XHRcdFx0aWYgKG51bGwgPT0gcmVzcG9uc2VEYXRhKSB7XG5cdFx0XHRcdFx0XHRcdCRmb3JtLnVuYmxvY2soKTtcblx0XHRcdFx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHR2YXIgaGFzRXJyb3IgPVxuXHRcdFx0XHRcdFx0XHRyZXNwb25zZURhdGEuZXJyb3IgfHxcblx0XHRcdFx0XHRcdFx0dHJ1ZSA9PSByZXNwb25zZURhdGEuZGF0YS5lcnJvciB8fFxuXHRcdFx0XHRcdFx0XHRmYWxzZSA9PSByZXNwb25zZURhdGEuc3VjY2VzcyB8fFxuXHRcdFx0XHRcdFx0XHQnc3VjY2VzcycgIT0gc3RhdHVzVGV4dCB8fFxuXHRcdFx0XHRcdFx0XHQyMDAgIT0ganFYSFIuc3RhdHVzXG5cdFx0XHRcdFx0XHRcdFx0PyB0cnVlXG5cdFx0XHRcdFx0XHRcdFx0OiBmYWxzZTtcblxuXHRcdFx0XHRcdFx0aWYgKGhhc0Vycm9yKSB7XG5cdFx0XHRcdFx0XHRcdHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5zdWJtaXRFcnJvcihcblx0XHRcdFx0XHRcdFx0XHRyZXNwb25zZURhdGEuZGF0YS5tZXNzYWdlXG5cdFx0XHRcdFx0XHRcdCk7XG5cdFx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdFx0aWYgKHJlc3BvbnNlRGF0YS5zdWNjZXNzICYmICFoYXNFcnJvcikge1xuXHRcdFx0XHRcdFx0XHRpZiAoXG5cdFx0XHRcdFx0XHRcdFx0cmVzcG9uc2VEYXRhLmRhdGEuaWQgfHxcblx0XHRcdFx0XHRcdFx0XHRyZXNwb25zZURhdGEuZGF0YS5saW5lX2l0ZW1zXG5cdFx0XHRcdFx0XHRcdCkge1xuXHRcdFx0XHRcdFx0XHRcdHZhciBjaGVja291dCA9IHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5oYW5kbGVDaGVja291dEZvcm0oXG5cdFx0XHRcdFx0XHRcdFx0XHRyZXNwb25zZURhdGEuZGF0YVxuXHRcdFx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdFx0XHQkZm9ybS51bmJsb2NrKCk7XG5cblx0XHRcdFx0XHRcdFx0XHRyZXR1cm4gY2hlY2tvdXQ7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdFx0cmV0dXJuIHJlc3BvbnNlRGF0YS5kYXRhO1xuXHRcdFx0XHRcdH0pXG5cdFx0XHRcdFx0LmVycm9yKGZ1bmN0aW9uKGVycm9yKSB7XG5cdFx0XHRcdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuc3VibWl0RXJyb3IoXG5cdFx0XHRcdFx0XHRcdHdpbmRvdy53Y3Zfc2NfcGFyYW1zLmVycm9yX21lc3NhZ2VfcHJlZml4ICtcblx0XHRcdFx0XHRcdFx0XHQnICcgK1xuXHRcdFx0XHRcdFx0XHRcdGVycm9yLnJlc3BvbnNlVGV4dFxuXHRcdFx0XHRcdFx0KTtcblx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHR9KTtcblxuXHRcdFx0XHRyZXR1cm4gcmVzcG9uc2U7XG5cdFx0XHR9IGNhdGNoIChlKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0aGFuZGxlQ2hlY2tvdXRGb3JtOiBmdW5jdGlvbihzZXNzaW9uKSB7XG5cdFx0XHR0cnkge1xuXHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tdW5kZWZcblx0XHRcdFx0dmFyIHN0cmlwZSA9IFN0cmlwZSh3aW5kb3cud2N2X3NjX3BhcmFtcy5rZXkpO1xuXHRcdFx0fSBjYXRjaCAoZXJyb3IpIHtcblx0XHRcdFx0Y29uc29sZS5sb2coZXJyb3IpO1xuXHRcdFx0fVxuXG5cdFx0XHR2YXIgc2Vzc2lvbklkID0gc2Vzc2lvbi5pZDtcblxuXHRcdFx0c3RyaXBlXG5cdFx0XHRcdC5yZWRpcmVjdFRvQ2hlY2tvdXQoeyBzZXNzaW9uSWQ6IHNlc3Npb25JZCB9KVxuXHRcdFx0XHQudGhlbihmdW5jdGlvbihyZXN1bHQpIHtcblx0XHRcdFx0XHRpZiAocmVzdWx0LmVycm9yKSB7XG5cdFx0XHRcdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuc3VibWl0RXJyb3IoXG5cdFx0XHRcdFx0XHRcdHdpbmRvdy53Y3Zfc2NfcGFyYW1zLmVycm9yX21lc3NhZ2VfcHJlZml4ICtcblx0XHRcdFx0XHRcdFx0XHQnICcgK1xuXHRcdFx0XHRcdFx0XHRcdHJlc3VsdC5tZXNzYWdlXG5cdFx0XHRcdFx0XHQpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSlcblx0XHRcdFx0LmVycm9yKGZ1bmN0aW9uKGVycm9yKSB7XG5cdFx0XHRcdFx0c3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LnN1Ym1pdEVycm9yKFxuXHRcdFx0XHRcdFx0d2luZG93Lndjdl9zY19wYXJhbXMuZXJyb3JfbWVzc2FnZV9wcmVmaXggK1xuXHRcdFx0XHRcdFx0XHQnICcgK1xuXHRcdFx0XHRcdFx0XHRlcnJvci5yZXNwb25zZVRleHRcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0fSk7XG5cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9LFxuXG5cdFx0YmxvY2s6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCFzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuaXNNb2JpbGUoKSkge1xuXHRcdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuZm9ybVxuXHRcdFx0XHRcdC5hZGRDbGFzcygncHJvY2Vzc2luZycpXG5cdFx0XHRcdFx0LmJsb2NrKCk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdHVuYmxvY2s6IGZ1bmN0aW9uKCkge1xuXHRcdFx0c3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LmZvcm0udW5ibG9jaygpO1xuXHRcdH0sXG5cblx0XHRyZXNldDogZnVuY3Rpb24oKSB7XG5cdFx0XHQkKFxuXHRcdFx0XHQnLndjdi1zdHJpcGUtY29ubmVjdC1lcnJvciwgLnN0cmlwZUNvbm5lY3RFcnJvciwgLnN0cmlwZV9jb25uZWN0X3Rva2VuJ1xuXHRcdFx0KS5yZW1vdmUoKTtcblx0XHR9LFxuXG5cdFx0aXNNb2JpbGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKFxuXHRcdFx0XHQvQW5kcm9pZHx3ZWJPU3xpUGhvbmV8aVBhZHxpUG9kfEJsYWNrQmVycnl8SUVNb2JpbGV8T3BlcmEgTWluaS9pLnRlc3QoXG5cdFx0XHRcdFx0bmF2aWdhdG9yLnVzZXJBZ2VudFxuXHRcdFx0XHQpXG5cdFx0XHQpIHtcblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9LFxuXG5cdFx0c3VibWl0RXJyb3I6IGZ1bmN0aW9uKGVycm9yX21lc3NhZ2UpIHtcblx0XHRcdCQoXG5cdFx0XHRcdCcud29vY29tbWVyY2UtTm90aWNlR3JvdXAtY2hlY2tvdXQsIC53b29jb21tZXJjZS1lcnJvciwgLndvb2NvbW1lcmNlLW1lc3NhZ2UnXG5cdFx0XHQpLnJlbW92ZSgpO1xuXG5cdFx0XHRzdHJpcGVfY29ubmVjdF9ob3N0ZWRfY2hlY2tvdXQuZm9ybS5wcmVwZW5kKFxuXHRcdFx0XHQnPGRpdiBjbGFzcz1cIndvb2NvbW1lcmNlLU5vdGljZUdyb3VwIHdvb2NvbW1lcmNlLU5vdGljZUdyb3VwLWNoZWNrb3V0IHdvb2NvbW1lcmNlLWVycm9yXCI+JyArXG5cdFx0XHRcdFx0ZXJyb3JfbWVzc2FnZSArXG5cdFx0XHRcdFx0JzwvZGl2Pidcblx0XHRcdCk7XG5cblx0XHRcdHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5mb3JtXG5cdFx0XHRcdC5yZW1vdmVDbGFzcygncHJvY2Vzc2luZycpXG5cdFx0XHRcdC51bmJsb2NrKCk7XG5cblx0XHRcdHN0cmlwZV9jb25uZWN0X2hvc3RlZF9jaGVja291dC5mb3JtXG5cdFx0XHRcdC5maW5kKCcuaW5wdXQtdGV4dCwgc2VsZWN0LCBpbnB1dDpjaGVja2JveCcpXG5cdFx0XHRcdC5ibHVyKCk7XG5cblx0XHRcdHZhciBzZWxlY3RvciA9ICcnO1xuXG5cdFx0XHRpZiAoJCgnI2FkZF9wYXltZW50X21ldGhvZCcpLmxlbmd0aCkge1xuXHRcdFx0XHRzZWxlY3RvciA9ICQoJyNhZGRfcGF5bWVudF9tZXRob2QnKTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCQoJyNvcmRlcl9yZXZpZXcnKS5sZW5ndGgpIHtcblx0XHRcdFx0c2VsZWN0b3IgPSAkKCcjb3JkZXJfcmV2aWV3Jyk7XG5cdFx0XHR9XG5cblx0XHRcdGlmICgkKCdmb3JtLmNoZWNrb3V0JykubGVuZ3RoKSB7XG5cdFx0XHRcdHNlbGVjdG9yID0gJCgnZm9ybS5jaGVja291dCcpO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoc2VsZWN0b3IubGVuZ3RoKSB7XG5cdFx0XHRcdCQoJ2h0bWwsIGJvZHknKS5hbmltYXRlKFxuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdHNjcm9sbFRvcDogc2VsZWN0b3Iub2Zmc2V0KCkudG9wIC0gMTAwXG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0XHQ1MDBcblx0XHRcdFx0KTtcblx0XHRcdH1cblxuXHRcdFx0JChkb2N1bWVudC5ib2R5KS50cmlnZ2VyKCdjaGVja291dF9lcnJvcicpO1xuXHRcdFx0c3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LnVuYmxvY2soKTtcblx0XHR9XG5cdH07XG5cblx0c3RyaXBlX2Nvbm5lY3RfaG9zdGVkX2NoZWNrb3V0LmluaXQoKTtcbn0pO1xuIl19

//# sourceMappingURL=stripe-hosted-checkout.js.map
