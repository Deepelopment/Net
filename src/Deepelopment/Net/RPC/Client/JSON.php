<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Client;


use BadFunctionCallException;
use InvalidArgumentException;
use RuntimeException;
use Deepelopment\Net\Request;
use Deepelopment\Net\RPC\Client_Interface;
use Deepelopment\Net\RPC\Client_Layer_Net;

/**
 * Remote Procedure Call JSON client layer.
 *
 * Based on https://github.com/fguillot/JsonRPC.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper (https://github.com/deepeloper)
 */
class JSON extends Client_Layer_Net implements Client_Interface
{
    const JSON_RPC_VERSION = '2.0';

    public function execute(
        $method,
        array $params = NULL,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        $data = array(
            'jsonrpc' => self::JSON_RPC_VERSION,
            'method'  => $method,
            'id'      => mt_rand()
        );
        if (is_array($params)) {
            $data['params'] = $params;
        }
        $options['method'] = Request::METHOD_OTHER;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);

        $response = parent::execute($method, array(), $options, $resetOptions, $url);

        $response = json_decode($response, TRUE);
        $this->validateResponse($response);
        if (isset($response['error'])) {
            $this->validateResponse($response['error']);
        }
        $result =
            isset($response['result'])
            ? $response['result']
            : NULL;

        return $result;
    }

    protected function getDefaultOptions()
    {
        return
            array(
                CURLOPT_CUSTOMREQUEST => 'POST'
            );
    }

    protected function patchResponse(&$response)
    {
    }

    protected function validateResponse($response)
    {
        if (
            isset($response['code']) &&
            isset($response['message'])
        ) {
            $this->handleError($response);
        }
    }

    /**
     * Throw an exception according the RPC error.
     *
     * @param  array $error
     * @return void
     * @throws BadFunctionCallException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function handleError(array $error)
    {
        $data = isset($error['data']) ? ". " . $error['data'] : '';
        switch ($error['code']) {
            case -32601:
                throw new BadFunctionCallException(
                    $error['message'] . $data
                );
            case -32602:
                throw new InvalidArgumentException(
                    $error['message'] . $data
                );
            default:
                throw new RuntimeException(
                    $error['message'] . $data,
                    $error['code']
                );
        }
    }
}
