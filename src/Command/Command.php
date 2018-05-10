<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\Cli\ {
  Console,
  Exception\ConsoleException
};

use Symfony\Component\Console\ {
  Command\Command as SymfonyCommand,
  Helper\QuestionHelper,
  Input\InputArgument as Arg,
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Base Console Command class for the Nexcess SDK.
 */
abstract class Command extends SymfonyCommand {

  /** @var array[] List of [name, mode] argument definitions. */
  const ARGS = [];

  /** @var string Classname of Sdk Endpoint to use. */
  const ENDPOINT = '';

  /** @var string Command name. */
  const NAME = '';

  /** @var array[] List of [name, mode, default] option definitions. */
  const OPTS = [];

  /** @var string Base part for translation keys for this command. */
  protected $_base_tr_key = '';

  /**
   * {@inheritDoc}
   */
  public function configure() {
    $this->_base_tr_key = 'console.' . strtr(static::NAME, [':' => '.']);

    $this->setName(static::NAME);
    $this->setDescription($this->getPhrase('desc'));
    $this->setHelp($this->getPhrase('help'));
    $this->setUsage($this->getPhrase('usage'));
    $this->setProcessTitle(
      Console::NAME . ' (' . Console::VERSION . ') > ' . static::NAME
    );

    $this->_bootstrapArguments();
    $this->_bootstrapOptions();

    parent::configure();
  }

  /**
   * Gets a translated phrase for this command.
   *
   * @param string $key Translation key (without base part)
   * @param array $context Map of parameter:replacement pairs
   * @return string Translated phrase on success; untranslated key otherwise
   */
  public function getPhrase(string $key, array $context = []) : string {
    return $this
      ->getApplication()
      ->translate("{$this->_trKey($key)}", $context);
  }

  /**
   * Sets up this command's arguments.
   */
  protected function _bootstrapArguments() {
    $app = $this->getApplication();

    foreach (static::ARGS as $arg) {
      $name = array_shift($arg);
      $mode = array_shift($arg) ?? Arg::OPTIONAL;
      $desc = $this->getPhrase(".arg_{$name}");

      $this->addArgument($name, $mode, $desc);
    }
  }

  /**
   * Sets up this command's options.
   */
  protected function _bootstrapOptions() {
    $app = $this->getApplication();

    foreach (static::OPTS as $opt) {
      $name = explode('|', array_shift($opt));
      $long = array_shift($name);
      $short = array_shift($name);
      $mode = array_shift($opt) ?? Arg::VALUE_OPTIONAL;
      $desc = $this->getPhrase("opt_{$long}");
      $default = array_shift($opt);

      $this->addOption($long, $short, $mode, $desc, $default);
    }
  }

  /**
   * Gets an API Endpoint instance.
   *
   * @param string|null $endpoint Name of desired endpoint; omit for default
   * @return Endpoint Requested endpoint on success
   * @throw ConsoleException On failure
   */
  protected function _getEndpoint(string $endpoint = null) : Endpoint {
    $endpoint = $endpoint ?? static::ENDPOINT;
    return $this->getApplication()->getClient()->getEndpoint($endpoint);
  }

  /**
   * Builds a translation key from given key.
   *
   * @param string $key Key
   * @return string Command-namespaced key
   */
  protected function _trKey(string $key) : string {
    return "{$this->_base_tr_key}.{$key}";
  }
}
