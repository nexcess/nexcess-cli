<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command;

use Throwable;

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\CloudAccountException,
  Command\InputCommand,
  Tests\TestCase
};

/**
 * Base class for testing Get..Choices traits.
 *
 * This is for unit tests;
 * integration tests belong with tests for concrete implementation.
 */
abstract class GetsCloudAccountChoicesTestCase extends TestCase {

  const RESOURCE_PATH = __DIR__ . '/resources';

  const RESOURCE_CHOICE_PAYLOAD = self::RESOURCE_PATH . '/';

  /**
   *
   */
  public function testGetCloudAccountChoices() {
    $this->_getSandbox()->play(function ($api, $sandbox) {
      $chooser = new class extends InputCommand {
        use GetsCloudAccountChoices {
          _getCloudAccountChoices as public;
        }
      };

      $choice_data = [
        ['id' => 1, 'domain' => 'test-1.example.com', 'ip' => '203.0.113.1'],
        ['id' => 2, 'domain' => 'test-2.example.com', 'ip' => '203.0.113.2'],
        ['id' => 3, 'domain' => 'test-3.example.com', 'ip' => '203.0.113.3']
      ];

      // unformatted
      $api->makeResponse('GET /cloud-account', 200, $choice_data);
      $unformatted_choices = $chooser->_getCloudAccountChoices(false);
      foreach ($choice_data as $cloudaccount) {
        $this->assertArrayHasKey(
          $cloudaccount['id'],
          $unformatted_choices,
          "id {$cloudaccount['id']} exists as choice index"
        );
        $this->assertEquals(
          $cloudaccount['domain'],
          $unformatted_choices[$cloudaccount['id']],
          "domain {$cloudaccount['domain']} exists as choice value"
        );
      }

      // formatted
      $api->queueResponse(
        '*',
        function () {
          $this->fail('api response was not cached');
        }
      );
      $formatted_choices = $chooser->_getCloudAccountChoices(true);
      foreach ($choice_data as $cloudaccount) {
        $this->assertArrayHasKey(
          $cloudaccount['id'],
          $formatted_choices,
          "id {$cloudaccount['id']} exists as choice index"
        );
        $this->assertContains(
          $cloudaccount['id'],
          $formatted_choices[$cloudaccount['id']],
          "id {$cloudaccount['id']} exists in choice description"
        );
        $this->assertContains(
          $cloudaccount['domain'],
          $formatted_choices[$cloudaccount['id']],
          "domain {$cloudaccount['domain']} exists in choice description"
        );
        $this->assertContains(
          $cloudaccount['ip'],
          $formatted_choices[$cloudaccount['id']],
          "ip {$cloudaccount['ip']} exists in choice description"
        );
      }

      // no results
      $this->_setNonpublicProperty($chooser, '_choices', []);
      $api->makeResponse('GET /cloud-account', 200, []);
      $this->setExpectedException(
        new CloudAccountException(
          CloudAccountException::NO_CLOUD_ACCOUNT_CHOICES
        )
      );
      $chooser->_getCloudAccountChoices();
    });
  }
}
