<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the "Wink Search" block.
 *
 * Renders <wink-search-button> — a floating or inline search-trigger button
 * that opens the Wink search overlay.
 *
 * @Block(
 *   id = "wink_search_block",
 *   admin_label = @Translation("Wink Search Button"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<wink-search-button></wink-search-button>',
      '#cache'  => [
        'contexts' => [],
        'tags'     => ['config:wink.settings'],
        'max-age'  => Cache::PERMANENT,
      ],
    ];
  }

}
