<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the "Wink Itinerary" block.
 *
 * Renders <wink-itinerary-button> — a button that opens the Wink itinerary
 * side-panel where travellers can manage their saved itineraries.
 *
 * @Block(
 *   id = "wink_itinerary_block",
 *   admin_label = @Translation("Wink Itinerary Button"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkItineraryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => '<wink-itinerary-button></wink-itinerary-button>',
      '#cache'  => [
        'contexts' => ['user'],
        'tags'     => ['config:wink.settings'],
        'max-age'  => Cache::PERMANENT,
      ],
    ];
  }

}
