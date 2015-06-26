<?php
namespace Staffim\RollbarBundle\EventListener;

use Exception;
use Staffim\RollbarBundle\RollbarReporter;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Vyacheslav Salakhutdinov <megazoll@gmail.com>
 */
class RollbarListener implements EventSubscriberInterface
{
    /**
     * @var \Staffim\RollbarBundle\RollbarReporter
     */
    private $exceptionReporter;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var callable
     */
    private $previousErrorHandler;

    public function __construct(RollbarReporter $exceptionReporter, RequestStack $requestStack)
    {
        $this->exceptionReporter = $exceptionReporter;
        $this->requestStack = $requestStack;

        register_shutdown_function(array($this->exceptionReporter, 'flush'));
        $this->previousErrorHandler = set_error_handler(array($this, 'handleError'));
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', -100),
            KernelEvents::TERMINATE => array('onKernelTerminate', -100),
            ConsoleEvents::EXCEPTION => array('onConsoleException', -100),
            ConsoleEvents::TERMINATE => array('onConsoleTerminate', -100),
        );
    }

    public function handleError($level, $message, $file, $line, $context)
    {
        $this->exceptionReporter->reportError($level, $message, $file, $line, $this->requestStack->getCurrentRequest());

        if ($this->previousErrorHandler) {
            return call_user_func($this->previousErrorHandler, $level, $message, $file, $line, $context);
        } else {
            return false;
        }
    }

    /**
     * Report kernel exception.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        return $this->reportException($event->getException());
    }

    /**
     * Report console exception.
     *
     * @param \Symfony\Component\Console\Event\ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        return $this->reportException($event->getException());
    }

    /**
     * @param \Exception $exception
     * @return string
     */
    private function reportException(Exception $exception)
    {
        return $this->exceptionReporter->report($exception, $this->requestStack->getCurrentRequest());
    }

    /**
     * Flush exception stack to Rollbar
     */
    public function onKernelTerminate()
    {
        $this->exceptionReporter->flush();
    }

    /**
     * Flush exception stack to Rollbar
     */
    public function onConsoleTerminate()
    {
        $this->exceptionReporter->flush();
    }
}
