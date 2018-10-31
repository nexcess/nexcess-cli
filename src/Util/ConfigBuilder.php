<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Util;

class ConfigBuilder {

  /** @var string[] Map of name: config classname pairs. */
  const CONFIG_FQCN = [
    'nexcess' => NexcessConfig::class,
    'thermo' => ThermoConfig::class
  ];

  /** @var string Default config type. */
  const DEFAULT_CONFIG_TYPE = 'nexcess';

  /** @var string Default user profile name. */
  const DEFAULT_PROFILE = 'nexcess';

  /** @var string Env var name for user profile. */
  const ENV_PROFILE = 'NEXCESS_PROFILE';

  /** @var string Env var name for api token. */
  const ENV_API_TOKEN = 'NEXCESS_API_TOKEN';

  /** @var callable[] Supported profile type: reader function map. */
  const SUPPORTED_TYPES = ['json' => [Util::class, 'readJsonFile']];

  public function __construct(Input $input) {}

  public function build(array $options = []) {}

  protected function _findProfile(string $name) : array {
    // find extension if included
    $ext = (new FileInfo($name))->getExtension() ?: null;

    // check support for profile format
    $type = $ext ?? 'json';
    if (! isset(self::SUPPORTED_TYPES[$type])) {
      throw new ConsoleException(
        ConsoleException::UNSUPPORTED_PROFILE_TYPE,
        [
          'type' => $type,
          'supported' => implode('|', array_keys(self::SUPPORTED_TYPES))
        ]
      );
    }

    // literal filepath?

    // search up to root  @todo what's needed for windows support?
    // if $ext is null, try $name first and then $name.$type
    // throw if nothing found
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
}
