<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the "Wink Shopping Cart" block.
 *
 * Renders <wink-shopping-cart-button> — a shopping-cart icon button that
 * opens the Wink cart / checkout panel.
 *
 * @Block(
 *   id = "wink_shopping_cart_block",
 *   admin_label = @Translation("Wink Shopping Cart Button"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkShoppingCartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<wink-shopping-cart-button></wink-shopping-cart-button>',
      '#cache'  => [
        'contexts' => ['user'],
        'tags'     => ['config:wink.settings'],
        'max-age'  => Cache::PERMANENT,
      ],
    ];
  }

}
