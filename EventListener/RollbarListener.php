<?php
namespace Staffim\RollbarBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * @author Vyacheslav Salakhutdinov <megazoll@gmail.com>
 */
class RollbarListener
{
    /**
     * @var \RollbarNotifier
     */
    private $rollbarNotifier;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * Constructor.
     *
     * @param \RollbarNotifier $rollbarNotifier
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param int $errorLevel
     */
    public function __construct(\RollbarNotifier $rollbarNotifier, SecurityContextInterface $securityContext, $errorLevel = null) {
        $this->rollbarNotifier = $rollbarNotifier;
        $this->securityContext = $securityContext;
        $this->setErrorLevel($errorLevel);

        $this->rollbarNotifier->person_fn = array($this, 'getUserData');
        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Return RollbarNotifier instance.
     *
     * @return \RollbarNotifier
     */
    public function getRollbarNotifier()
    {
        return $this->rollbarNotifier;
    }

    /**
     * Set error level.
     *
     * @param type $errorLevel
     */
    public function setErrorLevel($errorLevel)
    {
        $this->errorLevel = is_null($errorLevel) ? error_reporting() : $errorLevel;
    }

    /**
     * Register error handler.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->request = $event->getRequest();
        set_error_handler(array($this, 'handleError'));
    }

    /**
     * Log exception.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof HttpException) {
            return;
        }

        $this->exception = $event->getException();
    }

    /**
     * Wrap exception with additional info.
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->exception) {
            $debugToken = $event->getResponse()->headers->get('X-Debug-Token');
            $_SERVER['HTTP_DEBUG_TOKEN'] = $debugToken;
            $_SERVER['HTTP_REQUEST_CONTENT'] = $event->getRequest()->getContent();
            $this->rollbarNotifier->report_exception($this->exception);
            unset($_SERVER['HTTP_DEBUG_TOKEN']);
            unset($_SERVER['HTTP_REQUEST_CONTENT']);
            $this->exception = null;
        }
    }

    /**
     * Handle php error.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param string $context
     * @return bool
     */
    public function handleError($level, $message, $file, $line, $context)
    {
        if (error_reporting() & $level && $this->errorLevel & $level) {
            if ($this->request) {
                $_SERVER['HTTP_REQUEST_CONTENT'] = $this->request->getContent();
            }
            $this->rollbarNotifier->report_php_error($level, $message, $file, $line);
            unset($_SERVER['HTTP_REQUEST_CONTENT']);
        }

        return false;
    }

    /**
     * Get current user info.
     *
     * @return null|array
     */
    public function getUserData()
    {
        if ($this->securityContext->getToken() && $this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $userData = array();
            $user = $this->securityContext->getToken()->getUser();
            if (method_exists($user, 'getId')) {
                $userData['id'] = $user->getId();
            } else {
                // id is required
                $userData['id'] = $user->getUsername();
            }
            $userData['username'] = $user->getUsername();
            if (method_exists($user, 'getEmail')) {
                $userData['email'] = $user->getEmail();
            }

            return $userData;
        }

        return null;
    }

    /**
     * Flush errors on halt.
     */
    public function flush()
    {
        $error = error_get_last();
        if (!is_null($error)) {
            switch($error['type']) {
                case E_ERROR:
                    $this->rollbarNotifier->report_php_error($error['type'], $error['message'], $error['file'], $error['line']);
                    break;
            }
        }

        $this->rollbarNotifier->flush();
    }
}
