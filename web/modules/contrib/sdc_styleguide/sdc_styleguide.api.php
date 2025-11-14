<?php

/**
 * @file
 * Documentation for sdc_styleguide API.
 */

/**
 * This module provides support for basic data types defined by the SDC Schemas.
 *
 * It is not our intention to support more complex types, even though people
 * might use them. Examples that we've seen in the wild:
 *
 * - The Olivero theme supports Drupal\Core\Template\Attribute as part of their
 *   teaser component.
 * - The Bootstrap Barrio theme supports Drupal\Core\Menu\MenuLinkInterface as
 *   part of their menu_main component.
 *
 * Since there is no limitation regarding the classes that could be used, we
 * provide an API that allows for extension by providing 4 important hooks:
 *
 * 1 - One to allow setting up form elements when building the demo form.
 * 2 - One to allow converting the submitted values into the corresponding
 *     complex type.
 * 3 - One to allow converting from the complex type into the final type to be
 *     stored in the YAML definition.
 * 4 - One to read from the YAML definition into the complex type.
 *
 * As long as those 4 elements are implemented, you should be able to add
 * support for more complex types.
 *
 * Now, even though our intention is to not support those complex types, we did
 * build the implementation to support Drupal\Core\Template\Attribute (because
 * someone had logged an issue) and you can find the implementation in the
 * module file, specifically in order these functions:
 *
 * 1 - sdc_styleguide_styleguide_demo_form_element_complex_type_alter
 * 2 - sdc_styleguide_styleguide_demo_form_submitted_complex_type_value_alter
 * 3 - sdc_styleguide_styleguide_demo_form_export_complex_type_value_alter
 * 4 - sdc_styleguide_styleguide_demo_convert_stored_value_to_complex_type_alter
 */

/**
 * Builds the form element type for the complex type.
 *
 * For an implementation example,
 * @see sdc_styleguide_styleguide_demo_form_element_complex_type_alter()
 *
 * Do note that you need to include any validation functions under #validate.
 *
 * @param array &$form_element
 *   The form element you are building.
 *
 * @param array $sdc_property
 *   The SDC property definition from the component.yml file. It is a map that
 *   matches the exact definitions, but it could also be extended depending
 *   on the values of your SDC. As an example, it contains:
 *   - title: The property title or name.
 *   - type: The property complex type (class name).
 *   - description: The description associated with the property.
 *   - enum: A list of values when an enumeration is used.
 *   - required: This is not a standard for SDC, but we added it when the
 *   property is marked as required in the SDC definition.
 */
function hook_styleguide_demo_form_element_complex_type_alter(array &$form_element, array $sdc_property) {
}

/**
 * Builds the form element type for the complex type.
 *
 * For an implementation example,
 * @see sdc_styleguide_styleguide_demo_form_submitted_complex_type_value_alter()
 *
 * @param array &$value
 *   An object of the complex type you are trying to add support to. This is the
 *   one that will be altered.
 *
 * @param array &$submitted_value
 *   The submitted value based on the form element that was setup during
 *   hook_styleguide_demo_form_element_complex_type_alter.
 *
 * @param array $sdc_property
 *   The SDC property definition from the component.yml file. It is a map that
 *   matches the exact definitions, but it could also be extended depending
 *   on the values of your SDC. As an example, it contains:
 *   - title: The property title or name.
 *   - type: The property complex type (class name).
 *   - description: The description associated with the property.
 *   - enum: A list of values when an enumeration is used.
 *
 */
function hook_styleguide_demo_form_submitted_complex_type_value_alter(&$value, $submitted_value, array $sdc_property) {
}

/**
 * Transforms the complex type into a structure that can be written into a YAML.
 *
 * For an implementation example,
 * @see sdc_styleguide_styleguide_demo_form_export_complex_type_value_alter()
 *
 * @param array &$value
 *   An element that is easily convertible into YAML.
 *
 * @param $original_value
 *   The complex type containing the value used by the SDC.
 *
 * @param array $sdc_property
 *   The SDC property definition from the component.yml file. It is a map that
 *   matches the exact definitions, but it could also be extended depending
 *   on the values of your SDC. As an example, it contains:
 *   - title: The property title or name.
 *   - type: The property complex type (class name).
 *   - description: The description associated with the property.
 *   - enum: A list of values when an enumeration is used.
 *
 */
function hook_styleguide_demo_form_export_complex_type_value_alter(&$value, $original_value, array $sdc_property) {
}

/**
 * Transforms the complex type into a structure that can be written into a YAML.
 *
 * For an implementation example,
 * @see sdc_styleguide_styleguide_demo_convert_stored_value_to_complex_type_alter()
 *
 * @param array &&$value
 *   The complex type to be used by the SDC demo builder.
 *
 * @param $original_value
 *   The value stored in the YAML file.
 *
 * @param array $sdc_property
 *   The SDC property definition from the component.yml file. It is a map that
 *   matches the exact definitions, but it could also be extended depending
 *   on the values of your SDC. As an example, it contains:
 *   - title: The property title or name.
 *   - type: The property complex type (class name).
 *   - description: The description associated with the property.
 *   - enum: A list of values when an enumeration is used.
 *
 */
function hook_styleguide_demo_convert_stored_value_to_complex_type_alter(&$value, $original_value, array $sdc_property) {
}
