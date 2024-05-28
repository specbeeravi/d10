<?php

namespace Drupal\Tests\visitors_geoip\Unit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors_geoip\Form\SettingsForm;

/**
 * Tests the Visitors Settings Form.
 *
 * @group visitors_geoip
 */
class SettingsFormTest extends UnitTestCase {

  /**
   * Tests the buildForm() method.
   */
  public function testBuildForm() {
    // Create a mock config object.
    $config = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $config->expects($this->once())
      ->method('get')
      ->willReturn('path/to/geoip/database');

    // Create a mock config factory object.
    $configFactory = $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactory->expects($this->once())
      ->method('get')
      ->with('visitors_geoip.settings')
      ->willReturn($config);

    // Create a mock form state object.
    $formState = $this->createMock(FormStateInterface::class);

    $messenger = $this->createMock('\Drupal\Core\Messenger\MessengerInterface');

    // Create the form object.
    $form = new SettingsForm($configFactory, $messenger);
    $form->setStringTranslation($this->getStringTranslationStub());

    // Build the form.
    $formArray = $form->buildForm([], $formState);

    // Assert that the form array contains the expected elements.
    $this->assertArrayHasKey('geoip_path', $formArray);
    $this->assertEquals('textfield', $formArray['geoip_path']['#type']);
    $this->assertEquals('GeoIP Database path', $formArray['geoip_path']['#title']);
    $this->assertEquals('path/to/geoip/database', $formArray['geoip_path']['#default_value']);
  }

  /**
   * Tests the submitForm() method.
   */
  public function testSubmitForm() {
    // Create a mock editable config object.
    $editableConfig = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $editableConfig->expects($this->once())
      ->method('set')
      ->with('geoip_path', 'new/path/to/geoip/database')
      ->willReturnSelf();
    $editableConfig->expects($this->once())
      ->method('save');

    // Create a mock config factory object.
    $configFactory = $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactory->expects($this->once())
      ->method('getEditable')
      ->with('visitors_geoip.settings')
      ->willReturn($editableConfig);

    $messenger = $this->createMock('\Drupal\Core\Messenger\MessengerInterface');

    // Create a mock form state object.
    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->once())
      ->method('getValues')
      ->willReturn(['geoip_path' => 'new/path/to/geoip/database']);

    // Create the form object.
    $form = new SettingsForm($configFactory, $messenger);
    $form->setStringTranslation($this->getStringTranslationStub());

    // Submit the form.
    $form_array = [
      'geoip_path' => [
        '#value' => 'new/path/to/geoip/database',
      ],
    ];
    $form->submitForm($form_array, $formState);

    // Assert that the config was updated with the new value.
    $this->assertEquals('new/path/to/geoip/database', $form_array['geoip_path']['#value']);
  }

}
