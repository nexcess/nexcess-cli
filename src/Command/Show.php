<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\ {
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\Command,
  Command\TakesUserInput,
  Exception\CommandException,
  Exception\Handler
};

use Symfony\Component\Console\ {
  Input\InputArgument as Arg,
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Base class for "show" commands.
 */
abstract class Show extends Command {
  use TakesUserInput {
    initialize as _initialize;
  }

  /** {@inheritDoc} */
  const ARGS = [['id', Arg::OPTIONAL]];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    $this->_input['id'] = null;

    $this->_initialize($input, $output);
  }

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $id = Util::filter($this->_input['id'], Util::FILTER_INT);
    if ($id === null) {
      throw new CommandException(
        CommandException::MISSING_INPUT,
        ['command' => static::NAME, 'name' => 'id']
      );
    }

    $model = $this->_getEndpoint()->retrieve($id);

    $this->_saySummary($model->toArray(), $input->getOption('json'));
    return Handler::EXIT_SUCCESS;
  }
}
