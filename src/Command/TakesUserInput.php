<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\Cli\Exception\CommandException;

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Collects user input from console options and asks for missing values.
 *
 * Which values are collected depend on the keys of the $_data array:
 *  - if an option with the same name exists, its value will be assigned
 *  - if a value is null, the user will be asked to choose a value
 */
trait TakesUserInput {

  /** @var array Map of option:choices pairs. */
  protected $_choices = [];

  /** @var array Map of param:value pairs. */
  protected $_input = [];

  /**
   * {@inheritDoc}
   * Collects user input from options.
   */
  public function initialize(Input $input, Output $output) {
    // grab input from any matching args/options
    foreach (array_keys($this->_input) as $name) {
      if ($input->hasArgument($name)) {
        $this->_input[$name] = $input->getArgument($name);
      } elseif ($input->hasOption($name)) {
        $this->_input[$name] = $input->getOption($name);
      }
    }

    parent::initialize($input, $output);
  }

  /**
   * {@inheritDoc}
   * Asks user to fill in missing inputs.
   */
  public function interact(Input $input, Output $output) {
    $app = $this->getApplication();

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

        $this->_input[$name] = $app->ask(
          "{$this->getPhrase("ask_{$name}")}\n > "
        );
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
   * @return array Map of choice:description pairs if available;
   *  empty array otherwise
   */
  protected function _getChoices(string $name) : array {
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
}
