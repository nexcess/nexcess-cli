<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\ {
  Resource\Readable as Endpoint,
  Resource\Modelable as Model,
  Util\Util
};
use Nexcess\Sdk\Cli\ {
  Console,
  ConsoleException
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

  /** @var array[] Map of name:[mode, default] argument definitions. */
  const ARGS = [];

  /** @var string Classname of Sdk Endpoint to use. */
  const ENDPOINT = '';

  /** @var string Command name. */
  const NAME = '';

  /** @var array[] Map of name|n:[mode, default] option definitions. */
  const OPTS = [];

  /** @var string[] Api types to restrict this command to. */
  const RESTRICT_TO = [];

  /** @var string Whitelist of properties to include in summary. */
  const SUMMARY_KEYS = [];

  /** @var string Base part for translation keys for this command. */
  protected $_base_tr_key = '';

  public function __construct(Console $console) {
    $this->setApplication($console);
    parent::__construct();
  }

  /**
   * {@inheritDoc}
   */
  public function configure() {
    $this->_base_tr_key = 'console.' .
      strtr(static::NAME, [':' => '.', '-' => '_']);

    $this->setName(static::NAME);
    $this->setDescription($this->getPhrase('desc'));
    $this->setHelp($this->getPhrase('help'));
    $this->addUsage($this->getPhrase('usage'));
    $this->setProcessTitle(
      Console::NAME . ' (' . Console::VERSION . ') ' . static::NAME
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
    $console = $this->getApplication();
    if ($console === null) {
      return $key;
    }

    $tr_key = $this->_trKey($key);
    $tr = $console->translate($tr_key, $context);
    return ($tr === $tr_key) ? $key : $tr;
  }

  /**
   * {@inheritDoc}
   * Some commands are restricted to one company or another.
   */
  public function isEnabled() {
    return empty(static::RESTRICT_TO) ||
      in_array(
        $this->getApplication()->getConfig()::COMPANY,
        static::RESTRICT_TO
      );
  }

  /**
   * {@inheritDoc}
   * Ensure application has most recent I/O.
   */
  public function run(Input $input, Output $output) {
    $this->getApplication()->setIO($input, $output);
    return parent::run($input, $output);
  }

  /**
   * Sets up this command's arguments.
   */
  protected function _bootstrapArguments() {
    $args = static::ARGS;
    // sort by required, optional, array
    uasort(
      $args,
      function ($a, $b) {
        $a = $a[0] ?? Arg::OPTIONAL;
        $b = $b[0] ?? Arg::OPTIONAL;

        if (($a & Arg::IS_ARRAY) === Arg::IS_ARRAY) {
          return 1;
        }
        if (($b & Arg::IS_ARRAY) === Arg::IS_ARRAY) {
          return -1;
        }

        if (($a & Arg::OPTIONAL) === Arg::OPTIONAL) {
          return (($b & Arg::IS_ARRAY) === Arg::IS_ARRAY) ? -1 : 1;
        }
        if (($b & Arg::OPTIONAL) === Arg::OPTIONAL) {
          return (($a & Arg::IS_ARRAY) === Arg::IS_ARRAY) ? 1 : -1;
        }

        return 0;
      }
    );

    foreach ($args as $name => $arg) {
      $mode = array_shift($arg) ?? Arg::OPTIONAL;
      $default = array_shift($arg);
      $desc = $this->getPhrase("arg_{$name}");

      $this->addArgument($name, $mode, $desc, $default);
    }
  }

  /**
   * Sets up this command's options.
   */
  protected function _bootstrapOptions() {
    foreach (static::OPTS as $name => $opt) {
      $name = explode('|', $name);
      $long = array_shift($name);
      $short = array_shift($name);
      $mode = array_shift($opt) ?? Opt::VALUE_OPTIONAL;
      $desc = $this->getPhrase("opt_{$long}");
      $default = array_shift($opt);

      $this->addOption($long, $short, $mode, $desc, $default);
    }
  }

  /**
   * Default human-readable formatting for key:value pairs in a summary.
   *
   * Starts with the command's `summary_title` phrase, if exists.
   * Looks for a `summary_key.{key}` for each item and falls back on plain key.
   * Recurses on values and nested values are indented.
   *
   * @param array $summary Details
   * @param int $depth Starting indent depth
   * @return string Formatted details
   */
  protected function _formatSummary(array $summary, int $depth = 0) : string {
    $depth += 1;
    $details = [];
    foreach ($summary as $key => $value) {
      $translated_key = $this->getPhrase("summary_key.{$key}");
      if ($translated_key !== "summary_key.{$key}") {
        $translated_key = $key;
      }
      $details[$translated_key] = $value;
    }
    $indent = str_repeat('  ', $depth);

    $formatted = '';
    if ($depth === 1) {
      $title = $this->getPhrase('summary_title');
      if ($title !== 'summary_title') {
        $formatted = $title;
      }
    }

    foreach ($details as $key => $value) {
      $formatted .= "\n<info>{$indent}{$key}</info>: ";

      if (method_exists($value, 'toArray')) {
        $value = $value->toArray();
      }
      if (is_array($value)) {
        $value = $this->_formatSummary($value, $depth);
      }
      if (is_string($value) || is_numeric($value)) {
        $formatted .= $value;
      } else {
        $formatted .= Util::jsonEncode($value, Util::JSON_ENCODE_PRETTY);
      }
    }

    return $formatted;
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
   * Gets a summary of the command results.
   *
   * Override to provide custom summary details.
   * The returned array must be suitable for use as $context with getPhrase()).
   *
   * @param array $details Details of command results
   * @return array Summary data
   */
  protected function _getSummary(array $details) : array {
    if (! empty(static::SUMMARY_KEYS)) {
      $details = array_intersect_key(
        $details,
        array_flip(static::SUMMARY_KEYS)
      );
    }
    return $this->_orderDetails($details);
  }

  /**
   * Order the output array in the same order as the SUMMARY_KEYS
   *
   * @param array $details the details to be output.
   * @return array
   */
  protected function _orderDetails(array $details) : array {
    $return_value = [];
    if (! empty(static::SUMMARY_KEYS)) {
      foreach (static::SUMMARY_KEYS as $key) {
        $return_value[$key] = $details[$key];
      }
      return $return_value;
    }

    return $details;
  }

  /**
   * Outputs a summary of the command results.
   *
   * @param array $details Details of command results
   * @param bool $json Output as json?
   */
  protected function _saySummary(array $details, bool $json = false) {
    $console = $this->getApplication();
    $summary = $this->_getSummary($details);

    if ($json) {
      $console->sayJson($summary);
      return;
    }

    $summary_phrase = $this->getPhrase('summary', $summary);
    $console->say(
      ($summary_phrase === 'summary') ?
        "{$this->_formatSummary($summary)}\n" :
        $summary_phrase
    );
  }

  /**
   * Builds a translation key from given key.
   *
   * @param string $key Key
   * @return string Command-namespaced key
   */
  protected function _trKey(string $key) : string {
    return (strpos($key, 'console.') === 0) ?
      $key :
      "{$this->_base_tr_key}.{$key}";
  }
}
