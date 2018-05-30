<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli;

use Exception,
  SplFileInfo as FileInfo;

use Nexcess\Sdk\ {
  Client,
  Sandbox\Sandbox,
  Util\Config,
  Util\Language,
  Util\NexcessConfig,
  Util\ThermoConfig,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Exception\ConsoleException,
  Exception\Handler,
  Util\CommandDiscoveryFactory as DiscoveryFactory
};

use Symfony\Component\Console\ {
  Application as SymfonyApplication,
  Command\Command as SymfonyCommand,
  Helper\QuestionHelper,
  Input\ArgvInput,
  Input\InputInterface as Input,
  Input\InputOption as Option,
  Output\OutputInterface as Output,
  Output\ConsoleOutput,
  Question\ChoiceQuestion,
  Question\ConfirmationQuestion,
  Question\Question
};

/**
 * Cli API client for nexcess.net / thermo.io.
 */
class Console extends SymfonyApplication {

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

  /** @var string Name of application. */
  const NAME = 'Nexcess-CLI';

  /** @var int Key for newline option {@see say() $options}. */
  const SAY_OPT_NEWLINE = 0;

  /** @var int Key for options bitmask {@see say() $options}. */
  const SAY_OPT_OPTIONS = 1;

  /** @var callable[] Supported profile type: reader function map. */
  const SUPPORTED_PROFILE_TYPES = ['json' => [Util::class, 'readJsonFile']];

  /** @var string Version of application. */
  const VERSION = '0.1-alpha';

  /** @var Guzzle The guzzle http client. */
  protected $_client;

  /** @var Config The SDK configuration object. */
  protected $_config;

  /** @var Handler Application error/exception handler. */
  protected $_error_handler;

  /** @var Input Application input object. */
  protected $_input;

  /** @var Language Language object. */
  protected $_language;

  /** @var Output Application output object. */
  protected $_output;

  /** @var Sandbox Sdk sandbox object. */
  protected $_sandbox;

  /**
   * @param array $options Console options (overrides any inputs)
   */
  public function __construct(array $options = []) {
    // hide args from top/ps (ignore if fails)
    @cli_set_process_title(static::NAME . ' (' . static::VERSION . ')');
    parent::__construct(static::NAME, static::VERSION);
    $this->setCommandLoader(
      new DiscoveryFactory($this, __DIR__, __NAMESPACE__)
    );

    $this->_input = new ArgvInput();
    $this->_output = new ConsoleOutput();

    $this->_setErrorHandler();
    $this->_setLanguageHandler();

    $this->_buildConfig($options);
    $this->_initializeClient();
  }

  /**
   * Queries the user and gets response.
   *
   * @param string $message The question to ask
   * @param string $default A default answer
   * @return string|null The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function ask(
    string $message,
    string $default = null
  ) : ?string {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new Question($message, $default)
    );
  }

  /**
   * Asks the user to choose an option.
   *
   * @param string $message The question to ask
   * @param array $choices Options for user to choose from
   * @param int|string|null $default A default response
   * @return string|null The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function choose(
    string $message,
    array $choices,
    $default = null
  ) : ?string {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new ChoiceQuestion($message, $choices, $default)
    );
  }

  /**
   * Asks the user for a confirmation.
   *
   * @param string $message The question to ask
   * @param bool $default A default response
   * @return bool The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function confirm(
    string $message,
    bool $default = false
  ) : bool {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new ConfirmationQuestion($message, $default, '(^y)i')
    );
  }

  /**
   * {@inheritDoc}
   * Override to set application (console) early.
   */
  public function doRunCommand(
    SymfonyCommand $command,
    Input $input,
    Output $output
  ) {
    $command->setApplication($this);
    return parent::doRunCommand($command, $input, $output);
  }

  /**
   * Gets the API Client.
   *
   * @return Client
   */
  public function getClient() : Client {
    return $this->_client;
  }

  /**
   * Gets the API configuration.
   *
   * @return Config
   */
  public function getConfig() : Config {
    return $this->_config;
  }

  /**
   * {@inheritDoc}
   * Overridden to set up the input options needed for bootstrapping.
   */
  public function getDefaultInputDefinition() {
    $definition = parent::getDefaultInputDefinition();
    $definition->addOptions([
      new Option(
        'api-token',
        null,
        Option::VALUE_REQUIRED,
        $this->translate('console.opt_api_token')
      ),
      new Option(
        'format',
        'f',
        Option::VALUE_REQUIRED,
        $this->translate('console.opt_format'),
        'text'
      ),
      new Option(
        'profile',
        null,
        Option::VALUE_REQUIRED,
        $this->translate('console.opt_profile')
      ),
      new Option(
        'sandboxed',
        null,
        Option::VALUE_NONE,
        $this->translate('console.opt_sandboxed')
      ),
      new Option(
        'wait',
        null,
        Option::VALUE_NONE,
        $this->translate('console.opt_wait')
      )
    ]);

    return $definition;
  }

  /**
   * Gets the sandbox (if it exists).
   *
   * This method exists mainly for use in the test suite.
   *
   * @return Sandbox The Sdk sandbox object
   * @throws ConsoleException If not sandboxed
   */
  public function getSandbox() : Sandbox {
    if (empty($this->_sandbox)) {
      throw new ConsoleException(ConsoleException::NOT_SANDBOXED);
    }

    return $this->_sandbox;
  }

  /**
   * Is the console running in "debug" mode?
   *
   * @return bool True if debug; false otherwise
   */
  public function isDebug() : bool {
    return $this->_output->isDebug();
  }

  /**
   * {@inheritDoc}
   * @see SymfonyApplication::run
   * Use our own i/o objects instead of letting run() create them.
   */
  public function run(Input $input = null, Output $output = null) {
    return parent::run($input ?? $this->_input, $output ?? $this->_output);
  }

  /**
   * Writes a message out to the console.
   *
   * If symfony's Output object is not yet available,
   * this method will attempt to simply echo to stdout.
   *
   * This method only outputs when plain text output is expected.
   * {@see Console::sayJson()}
   *
   * @param string $message The message or message key to output
   * @param array $opts Map of output options:
   *  - bool Console::SAY_OPT_NEWLINE Add a newline at the end?
   *  - int Console::SAY_OPT_OPTIONS {@see OutputInterface::write $options}
   * @return Console $this
   */
  public function say(string $message, array $options = []) : Console {
    if (
      $this->_input->getParameterOption('--format', 'text', true) !== 'text'
    ) {
      return $this;
    }

    $newline = $options[self::SAY_OPT_NEWLINE] ?? true;
    $opts = $options[self::SAY_OPT_OPTIONS] ?? 0;

    if ($this->_output) {
      $this->_output->write($message, $newline, $opts);
    } else {
      echo $newline ? "{$message}\n" : $message;
    }

    return $this;
  }

  /**
   * Json-encodes data and writes it out to the console.
   *
   * If symfony's Output object is not yet available,
   * this method will attempt to simply echo to stdout.
   *
   * @param mixed $data The data to output
   * @param array $opts Map of output options:
   *  - bool Console::SAY_OPT_NEWLINE Add a newline at the end?
   *  - int Console::SAY_OPT_OPTIONS {@see OutputInterface::write $options}
   * @return Console $this
   */
  public function sayJson($data, array $options = []) : Console {
    $message = Util::jsonEncode($data, Util::JSON_ENCODE_PRETTY);
    $newline = $options[self::SAY_OPT_NEWLINE] ?? true;
    $opts = $options[self::SAY_OPT_OPTIONS] ?? 0;

    if ($this->_output) {
      $this->_output->write($message, $newline, $opts);
    } else {
      echo $newline ? "{$message}\n" : $message;
    }

    return $this;
  }

  /**
   * Translates and makes placeholder → context replacements in given message.
   *
   * @param string $message The string to make replacements on
   * @param array $context Map of message placeholder:replacement pairs
   * @return string Replaced message on success; original message otherwise
   */
  public function translate(string $message, array $context = []) : string {
    $message = $this->_language->getTranslation($message) ?? $message;

    if (empty($context)) {
      return $message;
    }

    preg_match_all('(\{(\w+)\})', $message, $matches);
    $placeholders = $matches[1];
    $replacements = [];

    foreach ($placeholders as $placeholder) {
      if (! isset($context[$placeholder])) {
        return $message;
      }

      $replacements["{{$placeholder}}"] = is_scalar($context[$placeholder]) ?
        $context[$placeholder] :
        json_encode(
          $context[$placeholder],
          JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    return strtr($message, $replacements);
  }

  /**
   * Does the console wait for long-running tasks to complete?
   *
   * @return bool True if waits; false otherwise
   */
  public function waits() : bool {
    return $this->_config->get('wait.always') ?? false;
  }

  /**
   * Builds an Sdk configuration object based on provided options.
   *
   * @param array $overrides Map of config options to forcibly override
   */
  protected function _buildConfig(array $overrides = []) {
    $input = $this->_input;
    $profile = $this->_loadProfile(
      $input->getParameterOption('--profile', null, true) ??
        $this->_getEnv(self::ENV_PROFILE) ??
        self::DEFAULT_PROFILE
    );

    $profile['api_token'] =
      $input->getParameterOption('--api-token', null, true) ??
      $this->_getEnv(self::ENV_API_TOKEN) ??
      $profile['api_token'] ??
      '';
    $profile['debug'] =
      ($this->isDebug() ? true : ($profile['debug'] ?? false));
    $profile['sandboxed'] =
      $input->getParameterOption('--sandboxed', null, true) ??
      $profile['sandboxed'] ??
      false;
    $profile['wait']['always'] =
      $this->_input->getParameterOption('--wait', null, true) ??
      $profile['wait']['always'] ??
      false;

    // apply overrides
    $profile = $overrides + $profile;

    if (! is_a($profile['fqcn'], Config::class, true)) {
      throw new ConsoleException(
        ConsoleException::INVALID_CONFIG_TYPE,
        ['type' => $profile['fqcn']]
      );
    }

    $this->_config = new $profile['fqcn']($profile);
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
   * Sets up the API Client for console use.
   *
   * @param Config $config Sdk configuration object
   */
  protected function _initializeClient() {
    if ($this->_config->get('sandboxed')) {
      $this->_sandbox = new Sandbox($this->_config);
      $this->_client = $this->_sandbox->newClient();
      return;
    }

    $this->_client = new Client($this->_config);
  }

  /**
   * Are we debugging?
   *
   * @return bool True if debugging; false otherwise
   */
  protected function _isDebug() : bool {
    return $this->_config->get('debug');
  }

  /**
   * Looks for and loads a user profile.
   *
   * @param string $name Name of profile to load
   * @return array Profile on success
   * @throws ConsoleException If profile cannot be found/loaded
   */
  protected function _loadProfile(string $name) : array {
    $type = (new FileInfo($name))->getExtension();
    if (empty($type)) {
      $profile = [];
    } elseif (! isset(self::SUPPORTED_PROFILE_TYPES[$type])) {
      throw new ConsoleException(
        ConsoleException::UNSUPPORTED_PROFILE_TYPE,
        [
          'type' => $type,
          'supported' => implode(
            '|',
            array_keys(self::SUPPORTED_PROFILE_TYPES)
          )
        ]
      );
    } elseif (is_readable($name)) {
      $profile = self::SUPPORTED_PROFILE_TYPES[$type]($name);
    } else {
      $dir = getcwd();
      while (! is_readable("{$dir}/{$name}")) {
        $dir = rtrim(dirname($dir), '/');
        if (empty($dir)) {
          throw new ConsoleException(
            ConsoleException::PROFILE_NOT_FOUND,
            ['profile' => $name]
          );
        }
      }
      $profile = self::SUPPORTED_PROFILE_TYPES[$type]("{$dir}/{$name}");
    }

    $profile['fqcn'] = $profile['fqcn'] ??
      self::CONFIG_FQCN[$name] ??
      self::CONFIG_FQCN[$profile['type'] ?? self::DEFAULT_CONFIG_TYPE];

    return $profile;
  }

  /**
   * Symfony/Console hides a lot of errors for some reason.
   */
  protected function _setErrorHandler() {
    $this->_error_handler = new Handler($this, $this->_input, $this->_output);
    $this->_error_handler->register();
  }

  /**
   * Sets the Language object for the application.
   */
  protected function _setLanguageHandler() {
    $this->_language = Language::getInstance();
    $this->_language->addPaths(__DIR__ . '/Util/lang');
  }
}
