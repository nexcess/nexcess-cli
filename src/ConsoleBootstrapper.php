<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli;

use SplFileInfo as FileInfo;

use Symfony\Component\Console\ {
  Input\ArgvInput,
  Input\InputDefinition as Definition,
  Input\InputInterface as Input,
  Input\InputOption as Option
};

use Nexcess\Sdk\ {
  Util\Config,
  Util\NexcessConfig,
  Util\ThermoConfig,
  Util\Util
};

use Nexcess\Sdk\Cli\Exception\ConsoleException;

/**
 * Builds SDK configuration, etc. from console inputs and environment.
 */
class ConsoleBootstrapper {

  /** @var string[] Map of name: config classname pairs. */
  const CONFIG_TYPE_FQCN = [
    'nexcess' => NexcessConfig::class,
    'thermo' => ThermoConfig::class
  ];

  /** @var string Default user profile name. */
  const DEFAULT_PROFILE = 'nexcess';

  /** @var string Env var name for user profile. */
  const ENV_PROFILE = 'NEXCESS_PROFILE';

  /** @var string Env var name for api token. */
  const ENV_API_TOKEN = 'NEXCESS_API_TOKEN';

  /** @var callable[] Supported profile type: reader function map. */
  const SUPPORTED_PROFILE_TYPES = ['json' => [Util::class, 'readJsonFile']];

  /** var Input Console input object. */
  protected $_input;

  public function __construct() {
    $this->_configureInput();
  }

  /**
   * Gets an SDK configuration instance based on cli input.
   *
   * @return Config
   */
  public function getConfig() : Config {
    $profile = $this->_loadProfile();
    $config = isset($profile['config']) ?
      (self::CONFIG_TYPE_FQCN[$profile['config']] ?? $profile['config']) :
      self::CONFIG_TYPE_FQCN[self::DEFAULT_PROFILE];

    if (! is_a($config, Config::class, true)) {
      throw new ConsoleException(
        ConsoleException::INVALID_CONFIG_TYPE,
        ['type' => $config]
      );
    }

    return new $config([
      'api_token' => $this->_input->getOption('api-token') ??
        $this->_getEnv(self::ENV_API_TOKEN) ??
        '',
      'debug' => $this->_input->hasParameterOption('debug', true) ?? false
    ]);
  }

  /**
   * Sets up the input options needed for bootstrapping.
   */
  protected function _configureInput() {
    $this->_input = new ArgvInput();
    $this->_input->bind(
      new Definition([
        new Option('api-token', null, Option::VALUE_REQUIRED),
        new Option('debug', 'vvv', Option::VALUE_NONE),
        new Option('profile', null, Option::VALUE_REQUIRED)
      ])
    );
  }

  /**
   * Gets an environment variable.
   *
   * @param string $name Var name to get
   * @return string|null Value on success; null otherwise
   */
  protected function _getEnv(string $name) : ?string {
    $env = getenv($name);
    return ($env !== false) ? $env : null;
  }

  /**
   * Determines which user profile to load and returns its values.
   *
   * @return array Map of profile options
   */
  protected function _loadProfile() : array {
    $file = $this->_input->getOption('profile') ??
      $this->_getEnv(self::ENV_PROFILE) ??
      self::DEFAULT_PROFILE;

    // unqualified paths are relative to cwd
    if (strpos($file, '.') !== 0 && strpos($file, '/') !== 0) {
      $file = "./{$file}";
    }

    $type = (new FileInfo($file))->getExtension();
    if (! isset(self::SUPPORTED_PROFILE_TYPES[$type])) {
      throw new ConsoleException(
        ConsoleException::UNSUPPORTED_PROFILE_TYPE,
        [
          'type' => $type,
          'supported' => implode('|', self::SUPPORTED_PROFILE_TYPES)
        ]
      );
    }

    return self::SUPPORTED_PROFILE_TYPES[$type]($file);
  }
}
