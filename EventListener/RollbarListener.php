<?php
namespace Staffim\RollbarBundle\EventListener;

use Exception;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Vyacheslav Salakhutdinov <megazoll@gmail.com>
 */
class RollbarListener implements EventSubscriberInterface
{
    private $exceptionReporter;

    public function __construct($exceptionReporter)
    {
        $this->exceptionReporter = $exceptionReporter;
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

    /**
     * Report kernel exception.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        return $this->exceptionReporter->report($exception);
    }

    /**
     * Report console exception.
     *
     * @param \Symfony\Component\Console\Event\ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $exception = $event->getException();

        return $this->exceptionReporter->report($exception);
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
