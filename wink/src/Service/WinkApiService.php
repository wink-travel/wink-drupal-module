<?php

declare(strict_types=1);

namespace Drupal\wink\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service that communicates with the Wink Travel REST API.
 *
 * Responsibilities:
 *  - Obtain a short-lived OAuth2 access token via the client-credentials flow.
 *  - Fetch the list of available content layouts from the Wink inventory API.
 *  - Cache both the token and the layout list to avoid redundant HTTP calls.
 */
final class WinkApiService {

  /**
   * Cache TTL for the OAuth2 token (seconds).  Conservative — real expiry is 1 h.
   */
  private const TOKEN_CACHE_TTL = 3300;

  /**
   * Cache TTL for the layout list (seconds).
   */
  private const LAYOUT_CACHE_TTL = 300;

  /**
   * Cache ID prefix for the access token.
   */
  private const CACHE_TOKEN_CID = 'wink:oauth2_token';

  /**
   * Cache ID prefix for the layout list.
   */
  private const CACHE_LAYOUTS_CID = 'wink:layouts';

  /**
   * IAM base URLs keyed by environment.
   */
  private const IAM_URLS = [
    'production'  => 'https://iam.wink.travel',
    'staging'     => 'https://staging-iam.wink.travel',
    'development' => 'https://staging-iam.wink.travel',
  ];

  /**
   * API base URLs keyed by environment.
   */
  private const API_URLS = [
    'production'  => 'https://api.wink.travel',
    'staging'     => 'https://staging-api.wink.travel',
    'development' => 'https://staging-api.wink.travel',
  ];

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly CacheBackendInterface $cache,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns a valid OAuth2 access token, fetching a new one when needed.
   *
   * @return string|null
   *   The Bearer token string, or NULL when the token cannot be obtained.
   */
  public function getAccessToken(): ?string {
    $cached = $this->cache->get(self::CACHE_TOKEN_CID);
    if ($cached !== FALSE) {
      return (string) $cached->data;
    }

    $config = $this->configFactory->get('wink.settings');
    $client_id     = $config->get('oauth2_client_id') ?? '';
    $client_secret = $config->get('oauth2_client_secret') ?? '';

    if ($client_id === '' || $client_secret === '') {
      return NULL;
    }

    $environment = $config->get('environment') ?: 'production';
    $iam_base    = self::IAM_URLS[$environment] ?? self::IAM_URLS['production'];

    try {
      $response = $this->httpClient->post($iam_base . '/oauth2/token', [
        'form_params' => [
          'grant_type'    => 'client_credentials',
          'client_id'     => $client_id,
          'client_secret' => $client_secret,
        ],
        'timeout' => 10,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $token = $body['access_token'] ?? NULL;

      if (!is_string($token) || $token === '') {
        throw new \UnexpectedValueException('Empty access_token in response.');
      }

      $expires_in = (int) ($body['expires_in'] ?? self::TOKEN_CACHE_TTL);
      $ttl        = min($expires_in - 60, self::TOKEN_CACHE_TTL);

      $this->cache->set(self::CACHE_TOKEN_CID, $token, \Drupal::time()->getRequestTime() + $ttl);

      return $token;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('wink')->error(
        'Failed to obtain Wink OAuth2 token: @message',
        ['@message' => $e->getMessage()]
      );
      return NULL;
    }
    catch (\JsonException $e) {
      $this->loggerFactory->get('wink')->error(
        'Invalid JSON in Wink OAuth2 response: @message',
        ['@message' => $e->getMessage()]
      );
      return NULL;
    }
    catch (\UnexpectedValueException $e) {
      $this->loggerFactory->get('wink')->error(
        'Unexpected Wink OAuth2 response: @message',
        ['@message' => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * Returns the available content layouts from the Wink API.
   *
   * @return array<string, string>
   *   An associative array of layout_id => label suitable for a select widget,
   *   or an empty array when the API is unavailable or credentials are missing.
   */
  public function getLayouts(): array {
    $cached = $this->cache->get(self::CACHE_LAYOUTS_CID);
    if ($cached !== FALSE) {
      return (array) $cached->data;
    }

    $token = $this->getAccessToken();
    if ($token === NULL) {
      return [];
    }

    $config      = $this->configFactory->get('wink.settings');
    $environment = $config->get('environment') ?: 'production';
    $api_base    = self::API_URLS[$environment] ?? self::API_URLS['production'];

    try {
      $response = $this->httpClient->get($api_base . '/api/property/engine/list', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept'        => 'application/json',
        ],
        'timeout' => 15,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);

      if (!is_array($body)) {
        throw new \UnexpectedValueException('Expected JSON array from layout endpoint.');
      }

      $layouts = $this->parseLayouts($body);

      $this->cache->set(
        self::CACHE_LAYOUTS_CID,
        $layouts,
        \Drupal::time()->getRequestTime() + self::LAYOUT_CACHE_TTL
      );

      return $layouts;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('wink')->error(
        'Failed to fetch Wink layouts: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
    catch (\JsonException $e) {
      $this->loggerFactory->get('wink')->error(
        'Invalid JSON in Wink layouts response: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
    catch (\UnexpectedValueException $e) {
      $this->loggerFactory->get('wink')->error(
        'Unexpected Wink layouts response: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
  }

  /**
   * Invalidates all locally cached Wink data.
   *
   * Call this after changing environment or credentials.
   */
  public function invalidateCache(): void {
    $this->cache->delete(self::CACHE_TOKEN_CID);
    $this->cache->delete(self::CACHE_LAYOUTS_CID);
  }

  /**
   * Normalises a raw API response into a layout_id => label map.
   *
   * The Wink API may evolve; this method insulates the rest of the module from
   * shape changes by mapping known fields with safe defaults.
   *
   * @param array<int|string, mixed> $raw
   *   Raw decoded JSON array from the layouts endpoint.
   *
   * @return array<string, string>
   *   Sanitised associative array suitable for a Form API select element.
   */
  private function parseLayouts(array $raw): array {
    $layouts = [];

    foreach ($raw as $item) {
      if (!is_array($item)) {
        continue;
      }

      $id    = (string) ($item['engineConfigurationIdentifier'] ?? $item['id'] ?? '');
      $label = (string) ($item['name'] ?? $item['label'] ?? $id);

      if ($id === '') {
        continue;
      }

      $layouts[htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8')] =
        htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return $layouts;
  }

}
