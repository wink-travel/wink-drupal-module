<?php

declare(strict_types=1);

namespace Drupal\Tests\wink\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wink\Form\WinkSettingsForm;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\wink\Form\WinkSettingsForm
 * @group wink
 */
final class WinkSettingsFormTest extends UnitTestCase {

  use ProphecyTrait;

  private $configFactory;
  private $config;
  private $messenger;
  private $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config        = $this->prophesize(Config::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->messenger     = $this->prophesize(MessengerInterface::class);
    $this->translation   = $this->prophesize(TranslationInterface::class);

    $this->configFactory->get('wink.settings')
      ->willReturn($this->config->reveal());

    $this->configFactory->getEditable('wink.settings')
      ->willReturn($this->config->reveal());

    // Make string translation return the input string unchanged.
    $this->translation->translateString(Argument::any())->will(function ($args) {
      return (string) $args[0];
    });
  }

  /**
   * Creates the form under test.
   */
  private function createForm(): WinkSettingsForm {
    $form = new WinkSettingsForm($this->configFactory->reveal());
    $form->setStringTranslation($this->translation->reveal());
    $form->setMessenger($this->messenger->reveal());
    return $form;
  }

  /**
   * @covers ::getFormId
   */
  public function testGetFormId(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $this->assertSame('wink_settings_form', $this->createForm()->getFormId());
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormAcceptsValidInput(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $form_state = new FormState();
    $form_state->setValue('client_id', 'valid-client-id');
    $form_state->setValue('environment', 'production');

    $form       = [];
    $this->createForm()->validateForm($form, $form_state);

    $this->assertFalse($form_state->hasAnyErrors());
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormRejectsInvalidEnvironment(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $form_state = new FormState();
    $form_state->setValue('client_id', '');
    $form_state->setValue('environment', 'invalid_env');

    $form = [];
    $this->createForm()->validateForm($form, $form_state);

    $this->assertTrue($form_state->hasAnyErrors());
    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('environment', $errors);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormRejectsOverlongClientId(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $form_state = new FormState();
    $form_state->setValue('client_id', str_repeat('x', 300));
    $form_state->setValue('environment', 'production');

    $form = [];
    $this->createForm()->validateForm($form, $form_state);

    $this->assertTrue($form_state->hasAnyErrors());
    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('client_id', $errors);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormAllowsAllThreeEnvironments(): void {
    $this->config->get(Argument::any())->willReturn(NULL);

    foreach (['production', 'staging', 'development'] as $env) {
      $form_state = new FormState();
      $form_state->setValue('client_id', '');
      $form_state->setValue('environment', $env);

      $form = [];
      $this->createForm()->validateForm($form, $form_state);

      $this->assertFalse(
        $form_state->hasAnyErrors(),
        "Expected no errors for environment: {$env}"
      );
    }
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormSavesValues(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $this->config->set('client_id', 'my-client')->shouldBeCalled()->willReturn($this->config->reveal());
    $this->config->set('environment', 'staging')->shouldBeCalled()->willReturn($this->config->reveal());
    $this->config->set('oauth2_client_id', 'oauth-id')->shouldBeCalled()->willReturn($this->config->reveal());
    $this->config->set('oauth2_client_secret', 'new-secret')->shouldBeCalled()->willReturn($this->config->reveal());
    $this->config->save()->shouldBeCalled();

    $this->messenger->addStatus(Argument::any())->willReturn(NULL);

    $form_state = new FormState();
    $form_state->setValue('client_id', 'my-client');
    $form_state->setValue('environment', 'staging');
    $form_state->setValue('oauth2_client_id', 'oauth-id');
    $form_state->setValue('oauth2_client_secret', 'new-secret');

    $form = ['#parents' => []];
    $this->createForm()->submitForm($form, $form_state);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormDoesNotOverwriteSecretWhenBlank(): void {
    $this->config->get(Argument::any())->willReturn(NULL);
    $this->config->set('client_id', Argument::any())->willReturn($this->config->reveal());
    $this->config->set('environment', Argument::any())->willReturn($this->config->reveal());
    $this->config->set('oauth2_client_id', Argument::any())->willReturn($this->config->reveal());
    // oauth2_client_secret should NOT be set when the submitted value is blank.
    $this->config->set('oauth2_client_secret', Argument::any())->shouldNotBeCalled();
    $this->config->save()->shouldBeCalled();
    $this->messenger->addStatus(Argument::any())->willReturn(NULL);

    $form_state = new FormState();
    $form_state->setValue('client_id', 'id');
    $form_state->setValue('environment', 'production');
    $form_state->setValue('oauth2_client_id', '');
    $form_state->setValue('oauth2_client_secret', '');

    $form = ['#parents' => []];
    $this->createForm()->submitForm($form, $form_state);
  }

}
