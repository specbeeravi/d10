<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * SDC Styleguide story property type handler.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SDCStyleguideStoryPropertyType extends AttributeBase {

  /**
   * Constructs a new SDCStyleguideStoryPropertyTypeHandler instance.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $handleType,
    public readonly ?TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
  ) {}

}
