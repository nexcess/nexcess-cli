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
  Output\OutputInterface as Output,
  Helper\TableStyle,
  Helper\Table
};

/**
 * Base class for "list" commands.
 */
abstract class ShowList extends Command {

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
    $this->_saySummary(
      $this->_getEndpoint()->list($this->_filter)->toArray(true),
      $input->getOption('json')
    );

    return Console::EXIT_SUCCESS;
  }

  /**
   * {@inheritDoc}
   */
  protected function _formatSummary(array $summary, int $depth = 0) : string {
    $formatted = $this->getPhrase('summary_title');
    foreach ($summary as $item) {
      $formatted .= "\n{$this->getPhrase('summary_item', $item)}";
    }

    return $formatted;
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    foreach ($details as $key => $item) {
      $details[$key] = parent::_getSummary($item);
    }

    return $details;
  }

  protected function _saySummary(array $details, bool $json = false) {
    $console = $this->getApplication();
    $details = $this->_getSummary($details);
    
    if ($json) {
      $console->sayJson($details);
      return;
    }

    $console->say($this->getPhrase('summary_title'));

    $this->_sayTable($details);
  }

  /**
   * Output a table
   * @@param array $details Items to be displayed
   */
  protected function _sayTable(array $details) {
    $console = $this->getApplication();

    $table = new Table($console->getIo()[Console::GET_IO_OUTPUT]);
    $table->setStyle($this->_setupTableStyle());
    $table->setHeaders($this->_getTableHeader($details[0]));
    $table->setRows($details);
    $table->render();
  }

  /**
   * Return the header of the table to be output based on SUMMARY_KEYS
   */
  protected function _getTableHeader($details) : array {
    $returnValue = [];
    $keys = (count($details) > 0 ? array_keys($details) : static::SUMMARY_KEYS);

    foreach($keys as $header) {
      $returnValue[] = $this->getPhrase($header);
    }

    return $returnValue;
  }

  protected function _setupTableStyle() : TableStyle {
    $tableStyle = new TableStyle();
    $tableStyle
      ->setCellRowFormat('<fg=white;options=bold>%s</>')
      ->setCellHeaderFormat('<fg=yellow;options=bold>%s</>');
      return $tableStyle;
  }
}
