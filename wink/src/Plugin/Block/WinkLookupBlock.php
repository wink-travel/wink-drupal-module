<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the "Wink Lookup" block.
 *
 * Renders <wink-lookup> — an interactive property lookup / autocomplete widget.
 *
 * @Block(
 *   id = "wink_lookup_block",
 *   admin_label = @Translation("Wink Lookup"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkLookupBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<wink-lookup></wink-lookup>',
      '#cache'  => [
        'contexts' => [],
        'tags'     => ['config:wink.settings'],
        'max-age'  => Cache::PERMANENT,
      ],
    ];
  }

}
