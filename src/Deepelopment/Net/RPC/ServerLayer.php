<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use RuntimeException;
use BadMethodCallException;
use Deepelopment\Core\Logger;
use Deepelopment\Net\UnauthorizedAccessException;
use Deepelopment\Net\IPRestrictionException;

/**
 * Remote Procedure Call server layer abstract class.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class ServerLayer extends Layer implements ServerInterface
{
    /**
     * Server environment variables
     *
     * @var array
     */
    protected $environment;

    /**
     * Array containig methods as keys and callbacks as values
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Request data
     *
     * @var mixed
     */
    protected $request;

    /**
     * @var string
     * @see self::setMethodExecutionExceptionData()
     * @see self::executeMethod()
     * @see self::onExceptionDuringExec()
     */
    protected $exceptionMessage;

    /**
     * @var int
     * @see self::setMethodExecutionExceptionData()
     * @see self::executeMethod()
     * @see self::onExceptionDuringExec()
     */
    protected $exceptionCode;

    /**
     * @var mixed
     * @see self::setMethodExecutionExceptionData()
     * @see self::executeMethod()
     * @see self::onExceptionDuringExec()
     */
    protected $exceptionData;

    /**
     * @param array  $options  Layer options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->environment =
            isset($this->options['environment'])
            ? $this->options['environment']
            : array();
    }

    /**
     * Method and callback binding.
     *
     * @param  string   $method
     * @param  callback $callback
     * @return void
     * @throws RuntimeException
     */
    public function bind($method, $callback)
    {
        if (is_callable($callback)) {
            $this->methods[$method] = $callback;
        } else {
            throw new RuntimeException('Invalid callback passed');
        }
    }

    /**
     * Authenticates users by login/password pairs.
     *
     * @param  array $users
     * @return void
     * @throws UnauthorizedAccessException
     */
    public function authenticateUsers(array $users)
    {
        if (
            !isset($this->environment['PHP_AUTH_USER']) ||
            !isset($users[$this->environment['PHP_AUTH_USER']]) ||
            $users[$this->environment['PHP_AUTH_USER']] !== $this->environment['PHP_AUTH_PW']
        ) {
            $this->logger->write(
                'Unauthorized access',
                Logger::WARNING
            );
            throw new UnauthorizedAccessException;
        }
    }

    /**
     * IP based client restrictions.
     *
     * @param  array $hosts  Array of host IPs
     * @throws IPRestrictionException
     */
    public function restrictByIPs(array $hosts)
    {
        if (!in_array($this->environment['REMOTE_ADDR'], $hosts)) {
            $this->logger->write(
                sprintf(
                    'IP restriction for %s',
                    $this->environment['REMOTE_ADDR']
                ),
                Logger::WARNING
            );
            throw new IPRestrictionException;
        }
    }

    /**
     * Returns passed request.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets method execution exception message/code.
     *
     * @param  string $message
     * @param  int    $code
     * @return void
     */
    public function setMethodExecutionExceptionMessageAndCode($message, $code = 0)
    {
        $this->exceptionMessage = $message;
        $this->exceptionCode    = $code;
    }

    /**
     * Sets method execution exception data.
     *
     * @param  mixed $data
     * @return void
     */
    public function setMethodExecutionExceptionData($data)
    {
        $this->exceptionData = $data;
    }

    /**
     * Validates obligatory parameters presense and types.
     *
     * @param  array  $aParams
     * @param  array  $aObligatory
     * @param  bool   $checkEmptyness
     * @param  string $type
     * @return void
     */
    public function validateObligatoryParams(
        array $aParams,
        array $aObligatory,
        $checkEmptyness = FALSE,
        $type = ''
    ){
        $this->logger->write(
            sprintf("Validatting obligatory params: %s ...", implode(', ', $aObligatory))
        );
        if(!is_array($aParams)){
            $this->sendError("No parameters passed");
        }
        foreach($aObligatory as $param){
            $this->validateObligatoryParam($aParams, $param, $checkEmptyness, $type);
        }
    }

    /**
     * Validates obligatory parameter presense and type.
     *
     * @param  array  $aParams         Parameters passed to method
     * @param  string $param
     * @param  bool   $checkEmptyness
     * @param  string $type
     * @return void
     */
    public function validateObligatoryParam(
        array $aParams,
        $param,
        $checkEmptyness = FALSE,
        $type = ''
    ){
        if(
            !isset($aParams[$param]) ||
            ($checkEmptyness ? empty($aParams[$param]) : FALSE)
        ){
            $this->sendError(
                sprintf("Missing or empty '%s' obligatory parameter", $param)
            );
        }
        if(
            '' !== $type &&
            $type !== gettype($aParams[$param])
        ){
            $this->sendError(
                sprintf(
                    "Parameter '%s' not a(n) %s\n%s",
                    $param,
                    $type,
                    var_export($aParams[$param], TRUE)
                )
            );
        }
    }

    /**
     * Send error data.
     *
     * @param  string $message
     * @return void
     * @throws MethodExecutionException
     */
    public function sendError($message){
        $this->setMethodExecutionExceptionData($message);
        $this->logger->write($message, Logger::WARNING);
        throw new MethodExecutionException(
            $this->exceptionMessage,
            $this->exceptionCode
        );
    }

    /**
     * Executes method
     *
     * @param  mixed $method
     * @param  mixed $params
     * @return mixed
     * @throws BadMethodCallException
     */
    protected function executeMethod($method, array $params = NULL)
    {
        if (!isset($this->methods[$method])) {
            $message =
                sprintf(
                    "Method '%s' not found",
                    $method
                );
            $this->logger->write($message, Logger::WARNING);
            throw new BadMethodCallException($message);
        }
        $this->exceptionData = NULL;
        try {
            $response = call_user_func($this->methods[$method], $params);
        } catch (MethodExecutionException $exception) {
            $response = $this->onExceptionDuringExec($exception);
        }
        $this->logger->write(
            sprintf(
                "%s sending response:\n%s",
                get_class($this),
                var_export($response, TRUE)
            )
        );

        return $response;
    }

    /**
     * Returns response according to exception thrown during method execution.
     *
     * @param  MethodExecutionException $exception
     * @return mixed
     */
    protected abstract function onExceptionDuringExec(MethodExecutionException $exception);
}
