<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Server;

use InvalidArgumentException;

/**
 * Remote Procedure Call JSON server layer supporting metadata.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class JSONMeta extends JSON
{
    /**
     * @var array
     */
    protected $aMeta;

    /**
     * Returns request metadata.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->aMeta;
    }

    /**
     * Returns metadata parameters.
     *
     * @return array
     */
    protected abstract function getMetaParams();

    /**
     * Validates request.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateRequest()
    {
        parent::validateRequest();

        $this->aMeta = array();
        foreach ($this->getMetaParams() as $param){
            if (
                !isset($this->request[$param]) ||
                '' === $this->request[$param]
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Missing obligatory '%s' parameter",
                        $param
                    )
                );
            }
            $this->aMeta[$param] = $this->request[$param];
        }
    }
}
