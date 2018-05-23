<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\ {
  Model\Modelable as Model,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\InputCommand,
  Exception\Handler
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Base class for "create" commands.
 */
abstract class Create extends InputCommand {

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $app = $this->getApplication();
    $endpoint = $this->_getEndpoint();

    $app->say($this->getPhrase('creating'));

    try {
      //$model = $endpoint->create($this->getInput());
      // @todo mock-a-doodle-doo
      $model = $endpoint->retrieve(76492);
    } catch (ApiException $e) {
      switch ($e->getCode()) {
        case ApiException::CREATE_FAILED:
          // @todo Open a support ticket?
          $app->say($this->getPhrase('failed', ['id' => $model->getId()]));
          return Handler::EXIT_API_ERROR;
        default:
          throw $e;
      }
    } catch (SdkException $e) {
      switch ($e->getCode()) {
        case SdkException::WAIT_TIMEOUT_EXCEEDED:
          $app->say($this->getPhrase('timed_out', ['id' => $model->getId()]));
          return Handler::EXIT_SDK_ERROR;
        default:
          throw $e;
      }
    }

    $app->say($this->getPhrase('created', ['id' => $model->getId()]));
    $this->_saySummary($model->toArray(), $input->getOption('json'));
    return Handler::EXIT_SUCCESS;
  }
}
