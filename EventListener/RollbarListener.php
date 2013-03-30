<?php
namespace Staffim\RollbarBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;

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

    public function __construct(SecurityContextInterface $securityContext, $accessToken, $environment, $errorLevel = null) {
        $this->rollbarNotifier = new \RollbarNotifier([
            'access_token' => $accessToken,
            'environment'  => $environment,
            'host'         => php_uname('n'),
            'max_errno'    => -1,
            'person_fn'    => [$this, 'getUserData']
        ]);

        $this->securityContext = $securityContext;
        $this->setErrorLevel($errorLevel);
        register_shutdown_function([$this, 'flush']);
    }

    public function setErrorLevel($errorLevel)
    {
        $this->errorLevel = is_null($errorLevel) ? error_reporting() : $errorLevel;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        set_error_handler([$this, 'handleError']);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof HttpException) {
            return;
        }

        $this->exception = $event->getException();
    }

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

    public function handleError($level, $message, $file, $line, $context)
    {
        if ($this->errorLevel & $level) {
            $this->rollbarNotifier->report_php_error($level, $message, $file, $line);
        }

        return false;
    }

    public function getUserData()
    {
        if ($this->securityContext->getToken() && $this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $userData = [];
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
