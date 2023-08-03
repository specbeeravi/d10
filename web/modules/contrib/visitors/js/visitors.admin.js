/**
 * @file
 * Visitors admin behaviors.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the tracking settings vertical tabs.
   */
  Drupal.behaviors.trackingSettingsSummary = {
    attach: function () {
      // Make sure this behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('#edit-domain-tracking').drupalSetSummary(function (context) {
        var $radio = $('input[name="visitors_domain_mode"]:checked', context);
        if ($radio.val() === '0') {
          return Drupal.t('A single domain');
        }
        else if ($radio.val() === '1') {
          return Drupal.t('One domain with multiple subdomains');
        }
      });

      $('#edit-page-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="visitors_visibility_request_path_mode"]:checked', context);
        if ($radio.val() === '0') {
          if (!$('textarea[name="visitors_visibility_request_path_pages"]', context).val()) {
            return Drupal.t('Not restricted');
          }
          else {
            return Drupal.t('All pages with exceptions');
          }
        }
        else {
          return Drupal.t('Restricted to certain pages');
        }
      });

      $('#edit-role-visibility-settings').drupalSetSummary(function (context) {
        var vals = [];
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($(this).next('label').text());
        });
        if (!vals.length) {
          return Drupal.t('Not restricted');
        }
        else if ($('input[name="visitors_visibility_user_role_mode"]:checked', context).val() === '1') {
          return Drupal.t('Excepted: @roles', {'@roles': vals.join(', ')});
        }
        else {
          return vals.join(', ');
        }
      });

      $('#edit-user-visibility-settings').drupalSetSummary(function (context) {
        var $radio = $('input[name="visitors_visibility_user_account_mode"]:checked', context);
        if ($radio.val() === '0') {
          return Drupal.t('Not customizable');
        }
        else if ($radio.val() === '1') {
          return Drupal.t('On by default with opt out');
        }
        else {
          return Drupal.t('Off by default with opt in');
        }
      });

      $('#edit-privacy').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-visitors-privacy-disablecookies', context).is(':checked')) {
          vals.push(Drupal.t('Cookies disabled'));
        }
        if (!vals.length) {
          return Drupal.t('No privacy');
        }
        return Drupal.t('@items', {'@items': vals.join(', ')});
      });

      $('#edit-charts').drupalSetSummary(function (context) {

        var width = $('input#edit-chart-width', context).val();
        var height = $('input#edit-chart-height', context).val();

        return Drupal.t('@width x @height', {
          '@width': width,
          '@height': height
        });
      });

      $('#edit-status-codes').drupalSetSummary(function (context) {
        var vals = [];
        if ($('input#edit-status-codes-disabled-404', context).is(':checked')) {
          vals.push(Drupal.t('404'));
        }
        if ($('input#edit-status-codes-disabled-403', context).is(':checked')) {
          vals.push(Drupal.t('403'));
        }
        if (!vals.length) {
          return Drupal.t('Default');
        }
        return Drupal.t('@items disabled', {'@items': vals.join(', ')});
      });

    }
  };

})(jQuery);
