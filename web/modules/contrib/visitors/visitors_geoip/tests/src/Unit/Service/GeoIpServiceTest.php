<?php

namespace Drupal\Tests\visitors_geoip\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\visitors_geoip\Service\GeoIpService;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;

/**
 * Tests the GeoIpService.
 *
 * @group visitors_geoip
 */
class GeoIpServiceTest extends UnitTestCase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The GeoIP reader service.
   *
   * @var \GeoIp2\Database\Reader|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $reader;

  /**
   * The GeoIpService instance being tested.
   *
   * @var \Drupal\visitors_geoip\Service\GeoIpService
   */
  protected $geoIpService;

  /**
   * The settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->settings = $this->createMock('\Drupal\Core\Config\ImmutableConfig');
    $this->settings->expects($this->once())
      ->method('get')
      ->with('geoip_path')
      ->willReturn('test_path');
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('visitors_geoip.settings')
      ->willReturn($this->settings);
    $this->reader = $this->createMock(Reader::class);

    $this->geoIpService = new GeoIpService($this->configFactory);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->geoIpService = NULL;
    $this->reader = NULL;
    $this->configFactory = NULL;

    parent::tearDown();
  }

  /**
   * Tests the metadata() method.
   */
  public function testMetadata() {
    // Mock the reader service.
    $metadata = $this->createMock('\MaxMind\Db\Reader\Metadata');
    $this->reader->expects($this->once())
      ->method('metadata')
      ->willReturn($metadata);
    $this->geoIpService->setReader($this->reader);
    // Call the metadata() method.
    $result = $this->geoIpService->metadata();

    // Assert the result.
    $this->assertEquals($metadata, $result);
  }

  /**
   * Tests the city() method.
   */
  public function testCity() {
    $ipAddress = '127.0.0.1';

    // Mock the reader service.
    $city = $this->createMock(City::class);
    $this->reader->expects($this->once())
      ->method('city')
      ->with($ipAddress)
      ->willReturn($city);
    $this->geoIpService->setReader($this->reader);
    // Call the city() method.
    $result = $this->geoIpService->city($ipAddress);

    // Assert the result.
    $this->assertSame($city, $result);
  }

}
