<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use Deepelopment\Core\Logger;

/**
 * Remote Procedure Call layer abstract class,
 * see {@see Deepelopment\Net\RPC}, {@see Deepelopment\Net\RPC\Client\JSON}.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class Layer
{
    /**
     * Layer options
     *
     * @var array
     */
    protected $options;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param array  $options  Layer options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->getDefaultOptions();
        $this->logger = new Logger(
            isset($this->options['Deepelopment\\Logger'])
                ? $this->options['Deepelopment\\Logger']
                : array()
        );
        unset($this->options['Deepelopment\\Logger']);
    }

    /**
     * Returns default options.
     *
     * @return array
     */
    abstract protected function getDefaultOptions();
}
