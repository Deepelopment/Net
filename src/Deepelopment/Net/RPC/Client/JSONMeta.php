<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Client;

use InvalidArgumentException;

/**
 * Remote Procedure Call JSON client layer supporting metadata.
 *
 * Example:
 * <code>
 * use Deepelopment\Net\RPC;
 * use Deepelopment\Net\RPC\Client\JSONMeta;
 *
 * class MySJON extends JSONMeta
 * {
 *     protected function getMetaParams()
 *     {
 *         return array('param_name1', 'param_name2', ...);
 *     }
 * }
 *
 * $client = RPC::getLayer('MyJSON', RPC::TYPE_CLIENT, $options);
 * $client->open('someURL');
 * $response = $oRPC->execute(
 *     'command1',
 *      array(
 *          'param1' => '...',
 *          // ...
 *          'param_name1' => '...',
 *          'param_name1' => '...',
 *          // ...
 *     )
 * );
 * </code>
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class JSONMeta extends JSON
{
    /**
     * Returns metadata parameters.
     *
     * @return array
     */
    protected abstract function getMetaParams();

    /**
     * Prepares request data using calling RPC method and parameters.
     *
     * @param  string $method
     * @param  array  $params
     * @return array
     * @throws InvalidArgumentException
     */
    protected function prepareRequest($method, array $params = NULL)
    {
        $aParams = array();
        foreach ($this->getMetaParams() as $param){
            if(
                !is_array($params) ||
                !isset($params[$param]) ||
                '' === $params[$param]
            ){
                throw new InvalidArgumentException(
                    sprintf(
                        "Missing obligatory '%s' parameter",
                        $param
                    )
                );
            }
            $aParams[$param] = $params[$param];
            unset($params[$param]);
        }

        $request = parent::prepareRequest($method, $params);

        $request += $aParams;

        return $request;
    }
}
