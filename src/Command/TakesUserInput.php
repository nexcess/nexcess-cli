<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

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
    $this->_initializeInput();
    // grab data from any matching options
    foreach ($this->_input as $param => $value) {
      if ($input->hasOption($param)) {
        $this->_input[$param] = $input->getOption($param) ?? $value;
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
          $this->_input[$name] = $app->choose(
            $this->_getPhrase("choose_{$name}"),
            $choices,
            key($choices)
          );
          continue;
        }

        $this->_input[$name] = $app->ask($this->_getPhrase("ask_{$name}"));
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
   * Override this method to define inputs for the command.
   */
  protected function _initializeInput() {
    // no inputs defined by default
  }
}
