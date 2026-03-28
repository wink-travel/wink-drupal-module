<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the "Wink Account" block.
 *
 * Renders <wink-account-button> — a user account button that opens the Wink
 * account management panel (sign-in, profile, bookings).
 *
 * @Block(
 *   id = "wink_account_block",
 *   admin_label = @Translation("Wink Account Button"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkAccountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<wink-account-button></wink-account-button>',
      '#cache'  => [
        'contexts' => ['user'],
        'tags'     => ['config:wink.settings'],
        'max-age'  => Cache::PERMANENT,
      ],
    ];
  }

}
