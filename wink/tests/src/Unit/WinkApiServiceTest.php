<?php

declare(strict_types=1);

namespace Drupal\Tests\wink\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wink\Service\WinkApiService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\wink\Service\WinkApiService
 * @group wink
 */
final class WinkApiServiceTest extends UnitTestCase {

  use ProphecyTrait;

  private $httpClient;
  private $configFactory;
  private $cache;
  private $loggerFactory;
  private $logger;
  private $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient    = $this->prophesize(ClientInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->cache         = $this->prophesize(CacheBackendInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger        = $this->prophesize(LoggerChannelInterface::class);
    $this->config        = $this->prophesize(Config::class);

    $this->loggerFactory->get('wink')->willReturn($this->logger->reveal());
    $this->configFactory->get('wink.settings')->willReturn($this->config->reveal());
  }

  /**
   * Returns a service instance wired with the current prophecy doubles.
   */
  private function createService(): WinkApiService {
    return new WinkApiService(
      $this->httpClient->reveal(),
      $this->configFactory->reveal(),
      $this->cache->reveal(),
      $this->loggerFactory->reveal(),
    );
  }

  // ---------------------------------------------------------------------------
  // getAccessToken()
  // ---------------------------------------------------------------------------

  /**
   * @covers ::getAccessToken
   */
  public function testGetAccessTokenReturnsCachedToken(): void {
    $cached_item       = new \stdClass();
    $cached_item->data = 'cached-token-value';

    $this->cache->get('wink:oauth2_token')->willReturn($cached_item);

    $service = $this->createService();
    $this->assertSame('cached-token-value', $service->getAccessToken());

    // The HTTP client must not be called when a valid cache entry exists.
    $this->httpClient->post(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
  }

  /**
   * @covers ::getAccessToken
   */
  public function testGetAccessTokenReturnsNullWhenCredentialsMissing(): void {
    $this->cache->get('wink:oauth2_token')->willReturn(FALSE);
    $this->config->get('oauth2_client_id')->willReturn('');
    $this->config->get('oauth2_client_secret')->willReturn('');

    $service = $this->createService();
    $this->assertNull($service->getAccessToken());
  }

  /**
   * @covers ::getAccessToken
   */
  public function testGetAccessTokenFetchesAndCachesToken(): void {
    $this->cache->get('wink:oauth2_token')->willReturn(FALSE);
    $this->config->get('oauth2_client_id')->willReturn('test-client');
    $this->config->get('oauth2_client_secret')->willReturn('test-secret');
    $this->config->get('environment')->willReturn('production');

    $response_body = json_encode([
      'access_token' => 'fresh-token',
      'expires_in'   => 3600,
    ]);

    $this->httpClient->post(
      'https://iam.wink.travel/oauth2/token',
      Argument::type('array')
    )->willReturn(new Response(200, [], $response_body));

    $this->cache->set(
      'wink:oauth2_token',
      'fresh-token',
      Argument::type('int')
    )->shouldBeCalled();

    $service = $this->createService();
    $this->assertSame('fresh-token', $service->getAccessToken());
  }

  /**
   * @covers ::getAccessToken
   */
  public function testGetAccessTokenReturnsNullOnHttpError(): void {
    $this->cache->get('wink:oauth2_token')->willReturn(FALSE);
    $this->config->get('oauth2_client_id')->willReturn('test-client');
    $this->config->get('oauth2_client_secret')->willReturn('test-secret');
    $this->config->get('environment')->willReturn('production');

    $this->httpClient->post(Argument::any(), Argument::any())
      ->willThrow(new RequestException('Connection refused', new Request('POST', '/')));

    $this->logger->error(Argument::containingString('Failed to obtain'), Argument::any())
      ->shouldBeCalled();

    $service = $this->createService();
    $this->assertNull($service->getAccessToken());
  }

  /**
   * @covers ::getAccessToken
   */
  public function testGetAccessTokenUsesCorrectIamUrlForStaging(): void {
    $this->cache->get('wink:oauth2_token')->willReturn(FALSE);
    $this->config->get('oauth2_client_id')->willReturn('client');
    $this->config->get('oauth2_client_secret')->willReturn('secret');
    $this->config->get('environment')->willReturn('staging');

    $response_body = json_encode(['access_token' => 'staging-token', 'expires_in' => 3600]);

    $this->httpClient->post(
      'https://staging-iam.wink.travel/oauth2/token',
      Argument::any()
    )->willReturn(new Response(200, [], $response_body));

    $this->cache->set(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();

    $service = $this->createService();
    $this->assertSame('staging-token', $service->getAccessToken());
  }

  // ---------------------------------------------------------------------------
  // getLayouts()
  // ---------------------------------------------------------------------------

  /**
   * @covers ::getLayouts
   */
  public function testGetLayoutsReturnsCachedLayouts(): void {
    $cached_item       = new \stdClass();
    $cached_item->data = ['layout-1' => 'My Layout'];

    $this->cache->get('wink:layouts')->willReturn($cached_item);

    $service = $this->createService();
    $this->assertSame(['layout-1' => 'My Layout'], $service->getLayouts());
  }

  /**
   * @covers ::getLayouts
   */
  public function testGetLayoutsReturnsEmptyArrayWhenNoToken(): void {
    // No layout cache entry.
    $this->cache->get('wink:layouts')->willReturn(FALSE);
    // No token cache entry either.
    $this->cache->get('wink:oauth2_token')->willReturn(FALSE);
    // No credentials.
    $this->config->get('oauth2_client_id')->willReturn('');
    $this->config->get('oauth2_client_secret')->willReturn('');

    $service = $this->createService();
    $this->assertSame([], $service->getLayouts());
  }

  /**
   * @covers ::getLayouts
   */
  public function testGetLayoutsFetchesAndNormalisesLayouts(): void {
    // No layout cache.
    $this->cache->get('wink:layouts')->willReturn(FALSE);

    // Warm token cache.
    $token_item       = new \stdClass();
    $token_item->data = 'valid-token';
    $this->cache->get('wink:oauth2_token')->willReturn($token_item);

    $this->config->get('environment')->willReturn('production');

    $api_body = json_encode([
      ['engineConfigurationIdentifier' => 'layout-abc', 'name' => 'Grid Layout'],
      ['engineConfigurationIdentifier' => 'layout-xyz', 'name' => 'Card Layout'],
    ]);

    $this->httpClient->get(
      'https://api.wink.travel/api/property/engine/list',
      Argument::type('array')
    )->willReturn(new Response(200, [], $api_body));

    $this->cache->set('wink:layouts', Argument::type('array'), Argument::type('int'))
      ->shouldBeCalled();

    $service  = $this->createService();
    $layouts  = $service->getLayouts();

    $this->assertArrayHasKey('layout-abc', $layouts);
    $this->assertSame('Grid Layout', $layouts['layout-abc']);
    $this->assertArrayHasKey('layout-xyz', $layouts);
  }

  // ---------------------------------------------------------------------------
  // invalidateCache()
  // ---------------------------------------------------------------------------

  /**
   * @covers ::invalidateCache
   */
  public function testInvalidateCacheDeletesBothEntries(): void {
    $this->cache->delete('wink:oauth2_token')->shouldBeCalled();
    $this->cache->delete('wink:layouts')->shouldBeCalled();

    $service = $this->createService();
    $service->invalidateCache();
  }

}
