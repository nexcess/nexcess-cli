<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\Cli\ {
  Command\Command,
  Command\TakesUserInput
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Base class for "create" commands.
 */
abstract class Create extends Command {
  use TakesUserInput;

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $app = $this->getApplication();
    $endpoint = $this->_getEndpoint();
    $model = $endpoint->create($this->_data);

    if ($app->shouldWait()) {
      try {
        $app->say('waiting');
        $endpoint->wait();
        $app->say('created');
      } catch (ApiException $e) {
        switch ($e->getCode()) {
          case ApiException::CREATE_FAILED:
            // @todo Open a support ticket?
            $app->say('create_failed');
            break;
          default:
            throw $e;
        }
      } catch (SdkException $e) {
        switch ($e->getCode()) {
          case SdkException::WAIT_TIMEOUT_EXCEEDED:
            $app->say('create_timed_out');
            break;
          default:
            throw $e;
        }
      }
    } else {
      $app->say('creating');
    }

    $app->say(
      $this->_getPhrase('create_summary', ['data' => $model->toArray()])
    );
  }
}
