<?php

declare(strict_types=1);

namespace Drupal\Tests\wink\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the Wink Travel block plugins.
 *
 * These tests boot a minimal Drupal kernel (no full install) and verify:
 *  - All six block plugins are discoverable via the Block plugin manager.
 *  - The simple blocks (no dependencies) produce the expected markup.
 *  - WinkContentBlock returns an empty build when layout/id are unconfigured.
 *  - The module config schema is valid.
 *
 * @group wink
 */
final class WinkBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'block',
    'wink',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['wink']);
  }

  // ---------------------------------------------------------------------------
  // Block plugin discovery
  // ---------------------------------------------------------------------------

  /**
   * @dataProvider blockPluginIdProvider
   */
  public function testBlockPluginIsDiscoverable(string $plugin_id): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $manager */
    $manager     = $this->container->get('plugin.manager.block');
    $definitions = $manager->getDefinitions();

    $this->assertArrayHasKey(
      $plugin_id,
      $definitions,
      "Block plugin '{$plugin_id}' was not discovered."
    );
  }

  /**
   * @dataProvider blockPluginIdProvider
   */
  public function testBlockPluginCategoryIsWinkTravel(string $plugin_id): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $manager */
    $manager    = $this->container->get('plugin.manager.block');
    $definition = $manager->getDefinition($plugin_id);

    $this->assertSame(
      'Wink Travel',
      (string) $definition['category'],
      "Block plugin '{$plugin_id}' has wrong category."
    );
  }

  /**
   * Data provider: all six Wink block plugin IDs.
   *
   * @return array<string, array<string>>
   */
  public static function blockPluginIdProvider(): array {
    return [
      'content'       => ['wink_content_block'],
      'lookup'        => ['wink_lookup_block'],
      'search'        => ['wink_search_block'],
      'account'       => ['wink_account_block'],
      'itinerary'     => ['wink_itinerary_block'],
      'shopping_cart' => ['wink_shopping_cart_block'],
    ];
  }

  // ---------------------------------------------------------------------------
  // Simple block builds
  // ---------------------------------------------------------------------------

  /**
   * @dataProvider simpleBlockMarkupProvider
   */
  public function testSimpleBlockReturnsExpectedMarkup(
    string $plugin_id,
    string $expected_markup,
  ): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockBase $block */
    $block = $manager->createInstance($plugin_id, ['label' => 'Test']);
    $build = $block->build();

    $this->assertArrayHasKey('#markup', $build);
    $this->assertStringContainsString(
      $expected_markup,
      (string) $build['#markup']
    );
  }

  /**
   * Data provider: simple blocks (no configuration required).
   *
   * @return array<string, array<string>>
   */
  public static function simpleBlockMarkupProvider(): array {
    return [
      'lookup'        => ['wink_lookup_block',        '<wink-lookup>'],
      'search'        => ['wink_search_block',         '<wink-search-button>'],
      'account'       => ['wink_account_block',        '<wink-account-button>'],
      'itinerary'     => ['wink_itinerary_block',      '<wink-itinerary-button>'],
      'shopping_cart' => ['wink_shopping_cart_block',  '<wink-shopping-cart-button>'],
    ];
  }

  // ---------------------------------------------------------------------------
  // WinkContentBlock
  // ---------------------------------------------------------------------------

  /**
   * An unconfigured WinkContentBlock should return an empty build array.
   */
  public function testContentBlockReturnsEmptyBuildWhenNotConfigured(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockBase $block */
    $block = $manager->createInstance('wink_content_block', []);
    $build = $block->build();

    $this->assertEmpty($build);
  }

  /**
   * A fully configured WinkContentBlock should return a render array.
   */
  public function testContentBlockReturnsBuildWithValidConfig(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockBase $block */
    $block = $manager->createInstance('wink_content_block', [
      'layout' => 'test-layout-id',
      'id'     => 'test-content-id',
    ]);
    $build = $block->build();

    $this->assertArrayHasKey('#theme', $build);
    $this->assertSame('wink_content_block', $build['#theme']);
    $this->assertSame('test-layout-id', $build['#layout']);
    $this->assertSame('test-content-id', $build['#id']);
  }

  // ---------------------------------------------------------------------------
  // Configuration
  // ---------------------------------------------------------------------------

  /**
   * Default configuration should load correctly after module installation.
   */
  public function testDefaultConfigLoads(): void {
    $config = $this->config('wink.settings');

    $this->assertSame('', $config->get('client_id'));
    $this->assertSame('production', $config->get('environment'));
    $this->assertSame('', $config->get('oauth2_client_id'));
    $this->assertSame('', $config->get('oauth2_client_secret'));
  }

}
