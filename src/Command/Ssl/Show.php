<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use DateTimeImmutable as DateTime;

use Nexcess\Sdk\ {
  Resource\Ssl\Endpoint,
  Util\Config
};

use Nexcess\Sdk\Cli\ {
  Command\Show as ShowCommand
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Show an existing SSL Certificate
 */
class Show extends ShowCommand {
  use GetsSslChoices;

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'ssl:show';

  /** {@inheritDoc} */
  const OPTS = [
    'id' => [OPT::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /** {@inheritDoc} */
  const SUMMARY_KEYS = [
    'id',
    'common_name',
    'valid_from_date',
    'valid_to_date'
  ];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    parent::initialize($input, $output);

    if ($this->_input['id'] === null) {
      $lookup = $input->getOption('id');
      if (! empty($lookup)) {
        $this->_lookupChoice('id', $lookup);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'id':
        return $this->_getSslChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    $details = parent::_getSummary($details);

    if (!is_null($details['valid_from_date'])) {
      $details['valid_from_date'] = (new \DateTimeImmutable($details['valid_from_date']))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_from_date']);
    }
    
    if (!is_null($details['valid_to_date'])) {
      $details['valid_to_date'] = (new \DateTimeImmutable($details['valid_to_date']))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_to_date']);
    }
    
    return $details;
  }
}
