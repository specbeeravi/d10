# Webform Booking Module

The Webform Booking module seamlessly integrates a simple booking system into any webform.

## Table of Contents

1. Features
2. Requirements
3. Installation
4. Configuration
5. PayPal Integration
6. Troubleshooting
7. Similar Projects
8. Maintainers

## Features

1. **Flexible Booking Slots:** Easily define time intervals and duration for each booking slot, allowing custom
   schedules and appointments.
2. **Weekday Management:** Disable bookings on specific weekdays (e.g., Saturdays and Sundays) and set different time
   periods for specific days of the week.
3. **Date and Time Exclusions:** Exclude specific dates or time periods for booking, accommodating holidays, events, or
   personal time off.
4. **Real-Time Availability Checks:** Automatically check for booked slots, preventing double bookings for a smooth and
   efficient user experience.
5. **Customizable for Any Webform:** Versatile design to be added to any webform within your Drupal site, providing a
   tailored booking solution.
6. **PayPal Integration:** Optional PayPal integration for seamless payment processing.
7. **Placeholder Variables:** Supports placeholder variables (tokens) for dynamically personalizing confirmation pages and email notifications.

## Requirements

- Webform

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Once the module is activated, navigate to the configuration page.
2. Add a 'Webform Booking' element to your desired webform.
3. Pay close attention to the provided formats and examples within the tooltips to ensure optimal setup and
   functionality.
4. If you are from the US, make sure to change the default country to US in the [settings page](admin/config/services/webform_booking).


### Placeholder Variables

The Webform Booking module supports placeholder variables (tokens) for dynamically personalizing confirmation pages, email notifications, and similar outputs.

#### Supported Tokens

`[webform_submission:booking:element_key]`

Replace 'element_key' with the machine name of your booking form element. For example, if your booking element's machine name is 'room', use `[webform_submission:booking:room]`.

Available token formats:

1. **[webform_submission:booking:element_key]**
   Default format. Displays the raw date and time (e.g., "2024-12-28 22:22")

2. **[webform_submission:booking:element_key:date]**
   Displays the date using the default html_date format

3. **[webform_submission:booking:element_key:date:format_name]**
   Displays the date using a specific format defined in your site's Date and Time formats configuration (/admin/config/regional/date-time). Replace 'format_name' with formats like 'short', 'medium', 'long', etc.

4. **[webform_submission:booking:element_key:time]**
   Displays just the time in 24-hour format (e.g., "22:22")

5. **[webform_submission:booking:element_key:slots]**
   Displays the number of slots booked.

6. **[webform_submission:booking:element_key:cancel_link]**
   Displays the cancel link for the booking.

#### Example

To personalize a confirmation page (/admin/structure/webform/manage/contact/settings/confirmation) or an email notification, include the following sample text:

```
Thank you for your reservation. We booked [webform_submission:booking:element_key:slots] slot(s) on [webform_submission:booking:element_key:date:long] for you. Please arrive 5 minutes before your scheduled time slot at [webform_submission:booking:element_key:time].
```

**This will output:**

```
Thank you for your reservation. We booked 1 slot(s) on Saturday, December 28, 2024 - 22:22 for you. Please arrive 5 minutes before your scheduled time slot at 22:22.
```

## PayPal Integration

The Webform Booking module supports PayPal integration for processing payments. To enable and configure PayPal:

1. In the Webform Booking element settings, check the "Enable PayPal integration" box.
2. Enter your PayPal Client ID.
3. Choose between Sandbox (for testing) or Live environment.
4. Set the default price per seat and select the currency.

### Obtaining PayPal API Credentials

To use PayPal integration, you need to obtain API credentials from PayPal. Follow these steps:

1. Go to the [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/).
2. Log in or create a PayPal account if you don't have one.
3. Click on "Apps & Credentials" in the left menu.
4. Click "Create App" to create a new application.
5. Choose a name for your app and select "Merchant" as the app type.
6. Once created, you'll see the Client ID and Secret. Use the Client ID in your Webform Booking settings.
7. For testing, use the Sandbox environment. For real transactions, switch to the Live environment and create a live app to get production credentials.

Remember to keep your API credentials secure and never share them publicly.

## Troubleshooting

If you encounter any issues with the Webform Booking module, consider the following steps:

- Ensure that all module dependencies are met.
- Check for conflicts with other modules.
- Review the Drupal logs for any error messages.
- For PayPal-related issues, verify your API credentials and ensure you're using the correct environment (Sandbox or Live).

## Similar Projects

- The BEE and BAT (Booking and Availability Management Tools) may offer similar functionality with increased complexity
  and dependencies.

## Maintainers

- Ricardo Marcelino [rfmarcelino](https://www.drupal.org/u/rfmarcelino)

#### Supporting organizations:

- Initial development [Universidade do Minho](https://www.drupal.org/universidade-do-minho)
- Paypal integration [My Local Trades](https://www.drupal.org/u/mylocaltrades)
- Ongoing support [Omibee](https://www.drupal.org/omibee)
