<?php
/**
 * PHP Deepelopment Framework
 *
 * Some overview...
 *
 * @package Deepelopment/Net
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net;

use InvalidArgumentException;
use RuntimeException;

/**
 * Remote Procedure Call client/server implementation.
 *
 * Example:
 * <code>
 * use Deepelopment\Net\RPC;
 *
 * $client = new RPC(
 *     'JSON',
 *     // or class implementing Deepelopment\Net\RPC\ClientInterface interface:
 *     // '\\My\\Namespace\\JSON',
 *     RPC::TYPE_CLIENT,
 *     array(
 *         CURLOPT_SSL_VERIFYPEER => FALSE,
 *         CURLOPT_SSL_VERIFYHOST => FALSE
 *     )
 * );
 * $layer = $client->getLayer();
 * $layer->open('https://user:password@domain:port/');
 * $response = $layer->execute(
 *     'methodName',
 *     array(
 *         'param1' => 'value1',
 *         'param2' => 'value2',
 *         // ...
 *     )
 * );
 * </code>
 *
 * @package Deepelopment/Net
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @throws  InvalidArgumentException
 * @throws  RuntimeException
 * @todo    Logging
 */
class RPC
{
    const TYPE_CLIENT = 1;
    const TYPE_SERVER = 2;

    /**
     * @var
     *      \Deepelopment\Net\RPC\ClientInterface |
     *      \Deepelopment\Net\RPC\ServerInterface
     */
    protected $layer;

    /**
     * @param  string $layer    RPC layer, for exaple 'JSON'
     * @param  int    $type     self::TYPE_CLIENT | self::TYPE_SERVER
     * @param  array  $options  Options passing to the layer
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($layer, $type, array $options = array())
    {
        switch ($type) {
            case self::TYPE_CLIENT:
                $type = 'Client';
                break;
            case self::TYPE_SERVER:
                $type = 'Server';
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('Invalid layer type %s', $type)
                );
        }
        if (FALSE === mb_strpos($layer, '\\', NULL, 'ASCII')) {
            $class = "Deepelopment\\Net\\RPC\\{$type}\\{$layer}";
        } else {
            $class = $layer;
        }
        $this->layer = new $class($options);
        $interface = "Deepelopment\\Net\\RPC\\{$type}Layer";
        if (!($this->layer instanceof $interface)) {
            throw new RuntimeException(
                sprintf('Class %s does not implement %s interface', $class, $interface)
            );
        }
    }

    /**
     * Returns layer object.
     *
     * @return \Deepelopment\Net\RPC\Layer
     */
    public function getLayer()
    {
        return $this->layer;
    }
}
