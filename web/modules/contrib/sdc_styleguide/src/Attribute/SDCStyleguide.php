<?php

declare(strict_types=1);

namespace Drupal\sdc_styleguide\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The sdc_styleguide attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SDCStyleguide extends AttributeBase {

  /**
   * Constructs a new SdcStyleguide instance.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $type,
    public readonly ?TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
  ) {}

}
