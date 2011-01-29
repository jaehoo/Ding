<?php
/**
 * Aspect dispatcher implementation.
 *
 * PHP Version 5
 *
 * @category   Ding
 * @package    Aspect
 * @subpackage Interceptor
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://www.noneyet.ar/ Apache License 2.0
 * @version    SVN: $Id$
 * @link       http://www.noneyet.ar/
 */
namespace Ding\Aspect\Interceptor;
use Ding\Aspect\MethodInvocation;

/**
 * Aspect dispatcher implementation.
 *
 * PHP Version 5
 *
 * @category   Ding
 * @package    Aspect
 * @subpackage Interceptor
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://www.noneyet.ar/ Apache License 2.0
 * @link       http://www.noneyet.ar/
 */
class DispatcherImpl implements IDispatcher
{
    /**
     * Associated array for methods intercepted.
     * @var IMethodInterceptor[]
     */
    private $_methodsIntercepted;

    /**
     * Associated array for methods intercepted for exceptions.
     * @var IExceptionInterceptor[]
     */
    private $_methodsExceptionIntercepted;

    /**
     * Interceptor classes.
     * @var string[]
     */
    private $_interceptorClasses;

    /**
     * Adds a method interceptor to the chain of a given method name.
     *
     * @param string             $method      Method name.
     * @param IMethodInterceptor $interceptor Interceptor to call.
     *
     * @return void
     */
    public function addMethodInterceptor(
        $method, IMethodInterceptor $interceptor
    ) {
        $this->_methodsIntercepted[$method][] = $interceptor;
    }

    /**
     * Adds a method interceptor to the chain of the exception interceptors
     * for a given method name.
     *
     * @param string                $method      Method name.
     * @param IExceptionInterceptor $interceptor Interceptor to call.
     *
     * @return void
     */
    public function addExceptionInterceptor(
        $method, IExceptionInterceptor $interceptor
    ) {
        $this->_methodsExceptionIntercepted[$method][] = $interceptor;
    }

    /**
     * Clone this object.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_methodsIntercepted as $method => $interceptor)
        {
            $this->_methodsIntercepted[$method] = clone $interceptor;
        }
        foreach ($this->_methodsExceptionIntercepted as $method => $interceptor)
        {
            $this->_methodsExceptionIntercepted[$method] = clone $interceptor;
        }
    }

    /**
     * Returns interceptors for a given method name or false if none was set.
     *
     * @param string $method Method to check for.
     *
     * @return IMethodInterceptor[]
     */
    public function getInterceptors($method)
    {
        if (!isset($this->_methodsIntercepted[$method])) {
            return false;
        }
        return $this->_methodsIntercepted[$method];
    }

    /**
     * Returns exception interceptors for a given method name or
     * false if none was set.
     *
     * @param string $method Method to check for.
     *
     * @return IExceptionInterceptor[]
     */
    public function getExceptionInterceptors($method)
    {
        if (!isset($this->_methodsExceptionIntercepted[$method])) {
            return false;
        }
        return $this->_methodsExceptionIntercepted[$method];
    }

    /**
     * Will chain and return the result of the chained call of interceptors.
     *
     * @param MethodInvocation $invocation   Original invocation to preserve.
     * @param IInterceptor[]   $interceptors Array of interceptors.
     *
     * @return mixed
     */
    private function _callInterceptors(
        MethodInvocation $invocation, array $interceptors
    ) {
        $total = count($interceptors) - 1;
        $invocationChain = $invocation;
        for ($i = $total; $i >= 0; $i--) {
            $newInvocation = new MethodInvocation(
                get_class($interceptors[$i]), 'invoke',
                array($invocationChain),
                $interceptors[$i], $invocation
            );
            $invocationChain = $newInvocation;
        }
        return $invocationChain->proceed();
    }

    /**
     * The proxy will call this method when an aspected method throws an
     * exception.
     *
     * @param MethodInvocation $invocation Method invocation "descriptor".
     *
     * @return void
     */
    public function invokeException(MethodInvocation $invocation)
    {
        $method = $invocation->getMethod();
        $interceptors = $this->_methodsExceptionIntercepted[$method];
        if ($interceptors != false) {
            return $this->_callInterceptors($invocation, $interceptors);
        } else {
            return $invocation->proceed();
        }
    }

    /**
     * The proxy will call this method when an aspected method is called.
     *
     * @param MethodInvocation $invocation Method invocation "descriptor".
     *
     * @return void
     */
    public function invoke(MethodInvocation $invocation)
    {
        $method = $invocation->getMethod();
        $interceptors = $this->_methodsIntercepted[$method];
        if ($interceptors != false) {
            return $this->_callInterceptors($invocation, $interceptors);
        } else {
            return $invocation->proceed();
        }
    }

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_methodsIntercepted = array();
        $this->_methodsExceptionIntercepted = array();
    }
}