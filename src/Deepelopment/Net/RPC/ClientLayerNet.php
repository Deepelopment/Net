<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use RuntimeException;
use Deepelopment\Net\Request;
use Deepelopment\Net\Utility;

/**
 * Remote Procedure Call client layer
 * using \Deepelopment\Net\Request abstract class.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class ClientLayerNet extends ClientLayer
{
    /**
     * Remote service transport object
     *
     * @var \Deepelopment\Net\Request
     */
    protected $transport;

    /**
     * @var string
     */
    protected $url;

    /**
     * Opens connection to remote service.
     *
     * @param  string $url
     * @return void
     */
    public function open($url)
    {
        $this->transport = new Request($this->options);
        $this->url       = $url;
    }

    /**
     * Sends request to remote server method.
     *
     * @param  mixed   $request
     * @param  array   $options       {@see
     *                                \Deepelopment\Net\Request::__construct()}
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @param  string  $url           Cusrom URL if differs from initialized
     * @return mixed
     * @throws RuntimeException  In case of remote service returns not '200 OK' or
     *                           transport was not initialized
     */
    protected function send(
        $request,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        if (!is_object($this->transport)) {
            throw new RuntimeException("Call open() method first");
        }
        if ('' === $url) {
            $url = $this->url;
        }
        $auth = '';
        $parsedURL = parse_url($url);
        if (isset($parsedURL['user'])) {
            $auth = $parsedURL['user'] . ':';
            if (isset($parsedURL['pass'])) {
                $auth .= $parsedURL['pass'];
            }
            unset($parsedURL['user'], $parsedURL['pass']);
            $url = Utility::buildURL($parsedURL);
        }
        if ('' !== $auth) {
            $options[CURLOPT_USERPWD] = $auth;
        }
        unset($parsedURL, $auth);
        $this->transport->setOptions($options, $resetOptions);
        $response = $this->transport->send($url, $request, $options['method']);
        $code = $this->transport->getInfo(CURLINFO_HTTP_CODE);
        switch ($code) {
            case 200:
                break;
            default:
                if (
                    '' == $response ||
                    '{' != mb_substr($response, 0, 1, 'ASCII') &&
                    '}' != mb_substr($response, -1, NULL, 'ASCII')
                ) {
                    $error = $this->transport->getError();
                    throw new RuntimeException(
                        sprintf(
                            "URL: %s, HTTP code: %d, %s (%s)",
                            $url,
                            $code,
                            $error['message'],
                            $response
                        ),
                        $error['code'] ? $error['code'] : $code
                    );
                }
        }
        $this->patchResponse($response);

        return $response;
    }

    public function close()
    {
        if ($this->transport) {
            $this->transport = NULL;
        }
    }
}
