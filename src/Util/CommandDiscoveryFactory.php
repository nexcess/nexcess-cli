<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use RecursiveDirectoryIterator,
  RecursiveIteratorIterator,
  ReflectionClass;

use Symfony\Component\Console\ {
  Command\Command as SymfonyCommand,
  CommandLoader\FactoryCommandLoader as Factory
};

/**
 * Auto-discovers and lazy-loads console command classes.
 */
class CommandDiscoveryFactory extends Factory {

  /** @var string[] Map of available command:fqcn pairs. */
  protected $_commands = [];

  /** @var string Directory to load php files from. */
  protected $_dir = '';

  /** @var string Namespace to search for Commands under. */
  protected $_ns = '';

  /**
   * @param string $dir Directory to load php files from
   * @param string $ns Root namespace to search for Commands under
   */
  public function __construct(string $dir, string $ns) {
    $this->_dir = $dir;
    $this->_ns = $ns;

    $this->_discoverCommands();
    parent::_construct($this->_commands);
  }

  /**
   * Looks for Command classes belonging to this application
   * and adds them to the $_commands map.
   */
  protected function _discoverCommands() {
    // make sure all php files below us are loaded
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->_dir)
    );
    foreach ($iterator as $file => $info) {
      if ($info->getExtension() === 'php') {
        include_once $file;
      }
    }

    $commands = array_filter(
      get_declared_classes(),
      function (string $fqcn) {
        $rc = new ReflectionClass($fqcn);
        return (
          $rc->isInstantiable() &&
          $rc->isSubclassOf(SymfonyCommand::class) &&
          strpos($rc->getNamespaceName(), $this->_ns) === 0
        ) ?
          $fqcn :
          false;
      }
    );

    if (! empty($commands)) {
      foreach ($commands as $command) {
        $name = defined("{$command}::NAME") ?
          $command::NAME :
          (new $command())->getName();

        $this->_commands[$name] = function () {
          return new $command();
        };
      }
    }
  }
}
