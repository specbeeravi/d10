(function ($, Drupal) {
  Drupal.behaviors.WebformBookingPayPal = {
    attach: function (context, settings) {
      once('webform-booking-paypal', '.webform-submission-form', context).forEach(function () {
        document.addEventListener('webformBookingTotalPriceUpdated', handleTotalPriceUpdate, { passive: true });
      });

      let paypalScriptLoaded = false;
      let paypalButtonsInstance = null;
      let currentTotalPrice = 0;
      let isUpdating = false;

      const localeMap = {
        'AL': 'en_US', 'DZ': 'ar_EG', 'AD': 'en_US', 'AO': 'en_US', 'AI': 'en_US',
        'AG': 'en_US', 'AR': 'es_XC', 'AM': 'en_US', 'AW': 'en_US', 'AU': 'en_AU',
        'AT': 'de_DE', 'AZ': 'en_US', 'BS': 'en_US', 'BH': 'ar_EG', 'BB': 'en_US',
        'BY': 'en_US', 'BE': 'en_US', 'BZ': 'es_XC', 'BJ': 'fr_XC', 'BM': 'en_US',
        'BT': 'en_US', 'BO': 'es_XC', 'BA': 'en_US', 'BW': 'en_US', 'BR': 'pt_BR',
        'VG': 'en_US', 'BN': 'en_US', 'BG': 'en_US', 'BF': 'fr_XC', 'BI': 'fr_XC',
        'KH': 'en_US', 'CM': 'fr_XC', 'CA': 'en_US', 'CV': 'en_US', 'KY': 'en_US',
        'TD': 'fr_XC', 'CL': 'es_XC', 'CN': 'zh_CN', 'XC': 'zh_XC', 'CO': 'es_XC',
        'KM': 'fr_XC', 'CG': 'en_US', 'CD': 'fr_XC', 'CK': 'en_US', 'CR': 'es_XC',
        'CI': 'fr_XC', 'HR': 'en_US', 'CY': 'en_US', 'CZ': 'cs_CZ', 'DK': 'da_DK',
        'DJ': 'fr_XC', 'DM': 'en_US', 'DO': 'es_XC', 'EC': 'es_XC', 'EG': 'ar_EG',
        'SV': 'es_XC', 'ER': 'en_US', 'EE': 'en_US', 'ET': 'en_US', 'FK': 'en_US',
        'FO': 'da_DK', 'FJ': 'en_US', 'FI': 'fi_FI', 'FR': 'fr_FR', 'GF': 'en_US',
        'PF': 'en_US', 'GA': 'fr_XC', 'GM': 'en_US', 'GE': 'en_US', 'DE': 'de_DE',
        'GI': 'en_US', 'GR': 'el_GR', 'GL': 'da_DK', 'GD': 'en_US', 'GP': 'en_US',
        'GT': 'es_XC', 'GN': 'fr_XC', 'GW': 'en_US', 'GY': 'en_US', 'HN': 'es_XC',
        'HK': 'en_GB', 'HU': 'hu_HU', 'IS': 'en_US', 'IN': 'en_IN', 'ID': 'id_ID',
        'IE': 'en_US', 'IL': 'he_IL', 'IT': 'it_IT', 'JM': 'es_XC', 'JP': 'ja_JP',
        'JO': 'ar_EG', 'KZ': 'en_US', 'KE': 'en_US', 'KI': 'en_US', 'KW': 'ar_EG',
        'KG': 'en_US', 'LA': 'en_US', 'LV': 'en_US', 'LS': 'en_US', 'LI': 'en_US',
        'LT': 'en_US', 'LU': 'en_US', 'MK': 'en_US', 'MG': 'en_US', 'MW': 'en_US',
        'MY': 'en_US', 'MV': 'en_US', 'ML': 'fr_XC', 'MT': 'en_US', 'MH': 'en_US',
        'MQ': 'en_US', 'MR': 'en_US', 'MU': 'en_US', 'YT': 'en_US', 'MX': 'es_XC',
        'FM': 'en_US', 'MD': 'en_US', 'MC': 'fr_XC', 'MN': 'en_US', 'ME': 'en_US',
        'MS': 'en_US', 'MA': 'ar_EG', 'MZ': 'en_US', 'NA': 'en_US', 'NR': 'en_US',
        'NP': 'en_US', 'NL': 'nl_NL', 'NC': 'en_US', 'NZ': 'en_US', 'NI': 'es_XC',
        'NE': 'fr_XC', 'NG': 'en_US', 'NU': 'en_US', 'NF': 'en_US', 'NO': 'no_NO',
        'OM': 'ar_EG', 'PW': 'en_US', 'PA': 'es_XC', 'PG': 'en_US', 'PY': 'es_XC',
        'PE': 'es_XC', 'PH': 'en_US', 'PN': 'en_US', 'PL': 'pl_PL', 'PT': 'pt_PT',
        'QA': 'en_US', 'RE': 'en_US', 'RO': 'en_US', 'RU': 'ru_RU', 'RW': 'fr_XC',
        'WS': 'en_US', 'SM': 'en_US', 'ST': 'en_US', 'SA': 'ar_EG', 'SN': 'fr_XC',
        'RS': 'en_US', 'SC': 'fr_XC', 'SL': 'en_US', 'SG': 'en_GB', 'SK': 'sk_SK',
        'SI': 'en_US', 'SB': 'en_US', 'SO': 'en_US', 'ZA': 'en_US', 'KR': 'ko_KR',
        'ES': 'es_ES', 'LK': 'en_US', 'SH': 'en_US', 'KN': 'en_US', 'LC': 'en_US',
        'PM': 'en_US', 'VC': 'en_US', 'SR': 'en_US', 'SJ': 'en_US', 'SZ': 'en_US',
        'SE': 'sv_SE', 'CH': 'de_DE', 'TW': 'zh_TW', 'TJ': 'en_US', 'TZ': 'en_US',
        'TH': 'th_TH', 'TG': 'fr_XC', 'TO': 'en_US', 'TT': 'en_US', 'TN': 'ar_EG',
        'TM': 'en_US', 'TC': 'en_US', 'TV': 'en_US', 'UG': 'en_US', 'UA': 'en_US',
        'AE': 'en_US', 'GB': 'en_GB', 'US': 'en_US', 'UY': 'es_XC', 'VU': 'en_US',
        'VA': 'en_US', 'VE': 'es_XC', 'VN': 'en_US', 'WF': 'en_US', 'YE': 'ar_EG',
        'ZM': 'en_US', 'ZW': 'en_US'
      };

      function handleTotalPriceUpdate(event) {
        if (isUpdating) return;
        isUpdating = true;

        const totalPrice = event.detail.totalPrice;
        currentTotalPrice = totalPrice;
        const submitButton = document.querySelector('.webform-button--submit');
        let paypalButtonsContainer = document.getElementById('paypal-button-container');

        if (parseFloat(totalPrice) > 0) {
          if (submitButton) {
            submitButton.style.display = 'none';
          }
          if (!paypalButtonsContainer) {
            paypalButtonsContainer = document.createElement('div');
            paypalButtonsContainer.id = 'paypal-button-container';
            const form = document.querySelector('form.webform-submission-form');
            if (form) {
              form.appendChild(paypalButtonsContainer);
            }
          }
          paypalButtonsContainer.style.display = 'block';

          if (!paypalScriptLoaded) {
            loadPayPalScript().then(() => {
              paypalScriptLoaded = true;
              initPayPalButtons();
              isUpdating = false;
            }).catch(error => {
              console.error('Failed to load PayPal script:', error);
              if (submitButton) {
                submitButton.style.display = 'block';
              }
              isUpdating = false;
            });
          } else if (paypalButtonsInstance) {
            updatePayPalButtons();
            isUpdating = false;
          } else {
            initPayPalButtons();
            isUpdating = false;
          }
        } else {
          if (submitButton) {
            submitButton.style.display = 'block';
          }
          if (paypalButtonsContainer) {
            paypalButtonsContainer.style.display = 'none';
          }
          isUpdating = false;
        }
      }

      function loadPayPalScript() {
        return new Promise((resolve, reject) => {
          if (typeof paypal !== 'undefined') {
            resolve();
            return;
          }

          const clientId = drupalSettings.webform_booking.paypalClientId;
          const currency = drupalSettings.webform_booking.currency;
          const defaultCountry = drupalSettings.webform_booking.defaultCountry;
          if (!clientId) {
            console.error('PayPal client ID is not set');
            reject(new Error('PayPal client ID is not set'));
            return;
          }

          const locale = (localeMap[defaultCountry] || 'en_US');

          const script = document.createElement('script');
          script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=${currency}&locale=${locale}`;
          script.async = true;
          script.onload = resolve;
          script.onerror = reject;
          document.body.appendChild(script);
        });
      }

      function initPayPalButtons() {
        const paypalButtonsContainer = document.getElementById('paypal-button-container');
        if (!paypalButtonsContainer) return;

        if (typeof paypal !== 'undefined') {
          paypalButtonsInstance = paypal.Buttons({
            createOrder: function (data, actions) {
              // Get the correct locale from the localeMap
              const countryCode = drupalSettings.webform_booking.defaultCountry;
              const locale = localeMap[countryCode] || 'en_US';

              return actions.order.create({
                purchase_units: [{
                  amount: {
                    value: currentTotalPrice,
                    currency_code: drupalSettings.webform_booking.currency
                  }
                }],
                application_context: {
                  shipping_preference: 'NO_SHIPPING',
                  user_action: 'PAY_NOW',
                  locale: locale.split('_')[0]
                }
              });
            },
            onApprove: function (data, actions) {
              // Hide the PayPal buttons immediately after approval
              paypalButtonsContainer.style.display = 'none';

              return actions.order.capture().then(function (details) {
                // Store transaction details in the hidden field
                const transactionInput = document.querySelector('input[name="paypal_transaction"]');
                if (transactionInput) {
                  transactionInput.value = JSON.stringify(details);
                }

                updateBookingElements();

                document.querySelector('form.webform-submission-form').submit();
              });
            }
          });

          paypalButtonsInstance.render('#paypal-button-container').catch(error => {
            console.error('PayPal Buttons failed to render:', error);
            const submitButton = document.querySelector('.webform-button--submit');
            if (submitButton) {
              submitButton.style.display = 'block';
            }
            // Display error message on the page
            const errorMessage = document.createElement('div');
            errorMessage.className = 'paypal-error-message';
            errorMessage.textContent = `PayPal Buttons failed to render: ${error.message}`;
            paypalButtonsContainer.appendChild(errorMessage);
          });
        } else {
          console.error('PayPal script is not loaded');
          const submitButton = document.querySelector('.webform-button--submit');
          if (submitButton) {
            submitButton.style.display = 'block';
          }
          // Display error message on the page
          const errorMessage = document.createElement('div');
          errorMessage.className = 'paypal-error-message';
          errorMessage.textContent = 'PayPal script is not loaded';
          paypalButtonsContainer.appendChild(errorMessage);
        }
      }

      function updatePayPalButtons() {
        if (paypalButtonsInstance) {
          paypalButtonsInstance.updateProps({
            createOrder: function (data, actions) {
              // Get the correct locale from the localeMap
              const countryCode = drupalSettings.webform_booking.defaultCountry;
              const locale = localeMap[countryCode] || 'en_US';

              return actions.order.create({
                purchase_units: [{
                  amount: {
                    value: currentTotalPrice,
                    currency_code: drupalSettings.webform_booking.currency
                  }
                }],
                application_context: {
                  shipping_preference: 'NO_SHIPPING',
                  user_action: 'PAY_NOW',
                  locale: locale.split('_')[0]
                }
              });
            }
          });
        }
      }

      function updateBookingElements() {
        document.querySelectorAll('[data-drupal-selector^="edit-webform-booking"]').forEach(function(element) {
          const elementId = element.getAttribute('data-drupal-selector').split('edit-')[1];
          const priceDisplay = document.getElementById('price-display-' + elementId);
          const seatsDropdown = document.getElementById('seats-' + elementId);
          if (priceDisplay && seatsDropdown) {
            const price = priceDisplay.textContent.split(':')[1].trim();
            const seats = seatsDropdown.value;
            const hiddenInput = element.querySelector('input[type="hidden"]');
            if (hiddenInput) {
              const currentValue = hiddenInput.value;
              const [slot] = currentValue.split('|');
              hiddenInput.value = `${slot}|${seats}|${price}`;
            }
          }
        });
      }
    }
  };
})(jQuery, Drupal);
