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
  SdkException
};

use Nexcess\Sdk\Cli\ {
  Command\Command,
  Command\CommandException,
  Console
};

use Symfony\Component\Console\ {
  Input\InputArgument as Arg,
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Base class for "list" commands.
 */
abstract class Find extends Command {

  /** {@inheritDoc} */
  const ARGS = ['filter' => [Arg::OPTIONAL | Arg::IS_ARRAY]];

  /** @var array List filter parsed from args. */
  protected $_filter = [];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    // collect list filter params
    if ($input->hasArgument('filter')) {
      foreach ($input->getArgument('filter') as $filter) {
        if (substr_count($filter, ':') !== 1) {
          throw new CommandException(
            CommandException::INVALID_LIST_FILTER,
            ['filter' => $filter]
          );
        }

        [$key, $value] = explode(':', $filter);
        $this->_filter[$key] = $value;
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $this->getApplication()->say($this->getPhrase('summary_title'));

    $collection = $this->_getEndpoint()->list($this->_filter);
    $json = $input->getOption('format') === 'json';
    foreach ($collection as $token) {
      $this->_saySummary($token->toArray(true), $json);
    }

    return Console::EXIT_SUCCESS;
  }
}
