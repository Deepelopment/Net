<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

/**
 * Remote Procedure Call layer interface.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper (https://github.com/deepeloper)
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
     * Constructor.
     *
     * @param array  $options  Layer options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->getDefaultOptions();
    }

    /**
     * Returns default options.
     *
     * @return array
     */
    abstract protected function getDefaultOptions();
}
