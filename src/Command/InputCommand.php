<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\Util\Util;

use Nexcess\Sdk\Cli\ {
  Command\Command,
  Command\CommandException
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Collects user input from console options and asks for missing values.
 *
 * Which values are collected depend on the INPUTS array:
 *  - each name in INPUTS will be initialized as null
 *  - if an argument with the same name exists, its value will be assigned
 *  - if an option with the same name exists, its value will be assigned
 *  - if a value is null, the user will be asked to choose a value:
 *    - if _getChoices(name) returns a non-empty array, those choices are used
 *    - otherwise the user will be asked to choose a value freely
 */
abstract class InputCommand extends Command {

  /** @var array User input name:filter map. */
  const INPUTS = [];

  /** @var array Map of option:choices pairs. */
  protected $_choices = [];

  /** @var array Map of param:value pairs. */
  protected $_input = [];

  /**
   * Gets user input and applies defined filters.
   *
   * Omit input name to get all non-empty inputs.
   *
   * @param string|null $name The input name to retrieve
   * @param bool $optional Is value allowed to be empty?
   * @return mixed The input value
   * @throws CommandException If input does not exist,
   *  or required value is missing
   */
  public function getInput(string $name = null, bool $optional = true) {
    if ($name === null) {
      $inputs = [];
      foreach ($this->_input as $name => $value) {
        $inputs[$name] = $this->getInput($name, $optional);
      }
      return array_filter(
        $inputs,
        function ($value) {
          return $value !== null;
        }
      );
    }

    if (! array_key_exists($name, $this->_input)) {
      throw new CommandException(
        CommandException::NO_SUCH_INPUT,
        ['command' => static::NAME, 'name' => $name]
      );
    }

    if (! $optional && $this->_input[$name] === null) {
      throw new CommandException(
        CommandException::MISSING_INPUT,
        ['command' => static::NAME, 'name' => 'id']
      );
    }

    $filter = static::INPUTS[$name];
    return ($filter === null) ?
      $this->_input[$name] :
      Util::filter($this->_input[$name], $filter);
  }

  /**
   * {@inheritDoc}
   * Collects user input from options.
   */
  public function initialize(Input $input, Output $output) {
    // initialize empty inputs
    $this->_input = array_fill_keys(array_keys(static::INPUTS), null);

    // grab input from any matching args/options
    foreach ($this->_input as $name => $value) {
      $cli_name = strtr($name, ['_' => '-']);
      if ($input->hasArgument($cli_name)) {
        $this->_input[$name] = $input->getArgument($cli_name);
      } elseif ($input->hasOption($cli_name)) {
        $this->_input[$name] = $input->getOption($cli_name);
      }
    }

    parent::initialize($input, $output);
  }

  /**
   * {@inheritDoc}
   * Asks user to fill in missing inputs.
   */
  public function interact(Input $input, Output $output) {
    $app = $this->getConsole();

    foreach ($this->_input as $name => $value) {
      if ($value === null) {
        $choices = $this->_getChoices($name);
        if (! empty($choices)) {
          $answer = $app->choose(
            $this->getPhrase("choose_{$name}"),
            array_values($choices),
            0
          );
          $this->_input[$name] = array_search($answer, $choices);
          continue;
        }

        $this->_input[$name] = $app
          ->ask("{$this->getPhrase("ask_{$name}")}\n > ");
      }
    }

    parent::interact($input, $output);
  }

  /**
   * Gets available choices for a given option.
   *
   * Set choices in configure(), or override _getChoices() to lazy-load.
   *
   * @param string $name Name of option to get choices for
   * @param bool $format Format the choices for output?
   * @return array Map of choice:description pairs if available;
   *  empty array otherwise
   */
  protected function _getChoices(string $name, bool $format = false) : array {
    return $this->_choices[$name] ?? [];
  }

  /**
   * Looks up an input option by search value.
   *
   * @param string $input Name of the input to lookup
   * @param string $lookup The lookup value
   */
  protected function _lookupChoice(string $input, string $lookup) {
    $choices = $this->_getChoices($input, false);
    $matches = preg_grep("({$lookup})iu", $choices);
    switch (count($matches)) {
      case 1:
        $this->_input[$input] = array_search(reset($matches), $choices);
        return;
      case 0:
        throw new CommandException(
          CommandException::NO_LOOKUP_MATCH,
          [
            'input' => $input,
            'lookup' => $lookup,
            'choices' => implode('|', $choices)
          ]
        );
      default:
        throw new CommandException(
          CommandException::LOOKUP_MATCH_AMBIGUOUS,
          [
            'input' => $input,
            'lookup' => $lookup,
            'matches' => implode(', ', $matches)
          ]
        );
    }
  }

  /**
   * Choice formatter: pads column(s) of an array to equal length.
   *
   * @param array $details The array to pad
   * @param array|null $columns Column (key) name(s) to pad; defaults to all
   * @return array Padded details
   */
  protected function _padColumns(
    array $details,
    array $columns = null
  ) : array {
    $columns = $columns ?? array_keys($details);
    foreach ($columns as $column) {
      $values = array_column($details, $column);
      if (empty($values)) {
        continue;
      }
      $max = max(array_map('mb_strlen', $values));
      foreach ($details as $i => $row) {
        $value = Util::filter($row[$column] ?? '', Util::FILTER_STRING);
        $details[$i][$column] = $value .
          str_repeat(' ', $max - mb_strlen($value));
      }
    }
    return $details;
  }
}
