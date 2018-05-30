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
abstract class Show extends InputCommand {

  /** {@inheritDoc} */
  const ARGS = ['id' => [Arg::OPTIONAL]];

  /** {@inheritDoc} */
  const INPUTS = ['id' => Util::FILTER_INT];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $this->_saySummary(
      $this->_getEndpoint()->retrieve($this->getInput('id', false))->toArray(),
      ($input->getOption('format') === 'json')
    );
    return Handler::EXIT_SUCCESS;
  }
}
