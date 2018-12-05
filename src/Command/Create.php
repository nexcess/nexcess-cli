<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\ {
  ApiException,
  Resource\Creatable
};

use Nexcess\Sdk\Cli\ {
  Command\InputCommand,
  Console
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
    $console = $this->getConsole();
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Creatable);

    $console->say($this->getPhrase('creating'));

    try {
      // @phan-suppress-next-line PhanUndeclaredMethod
      $model = $endpoint->create($this->getInput());
    } catch (ApiException $e) {
      switch ($e->getCode()) {
        case ApiException::CREATE_FAILED:
          // @todo Open a support ticket?
          $console->say($this->getPhrase('failed'));
          return Console::EXIT_API_ERROR;
        default:
          throw $e;
      }
    }

    $console->say($this->getPhrase('created', ['id' => $model->getId()]));
    $this->_saySummary($model->toArray(), $input->getOption('json'));
    return Console::EXIT_SUCCESS;
  }
}
