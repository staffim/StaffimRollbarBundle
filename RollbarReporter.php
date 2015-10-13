<?php

namespace Staffim\RollbarBundle;

use Exception;
use ErrorException;
use RollbarNotifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class RollbarReporter
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
     * @var \Staffim\RollbarBundle\ReportDecisionManager
     */
    private $reportDecisionManager;

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * @var array
     */
    private $scrubExceptions;

    /**
     * @var array
     */
    private $scrubParameters;

    /**
     * Constructor.
     *
     * @param \RollbarNotifier $rollbarNotifier
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param \Staffim\RollbarBundle\ReportDecisionManager $reportDecisionManager
     * @param type $errorLevel
     * @param array $scrubExceptions
     * @param array $scrubParameters
     */
    public function __construct(
        RollbarNotifier $rollbarNotifier,
        SecurityContextInterface $securityContext,
        ReportDecisionManager $reportDecisionManager,
        $errorLevel = -1,
        array $scrubExceptions = array(),
        array $scrubParameters = array()
    ) {
        $this->rollbarNotifier = $rollbarNotifier;
        $this->securityContext = $securityContext;
        $this->reportDecisionManager = $reportDecisionManager;
        $this->scrubExceptions = $scrubExceptions;
        $this->scrubParameters = $scrubParameters;
        $this->errorLevel = $errorLevel;

        $this->rollbarNotifier->person_fn = array($this, 'getUserData');
    }

    /**
     * Report an exception to the rollbar.
     *
     * @param \Exception $exception
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $extraData
     * @return string UUID of rollbar item
     */
    public function report(Exception $exception, Request $request = null, $extraData = null)
    {
        if (false === $this->reportDecisionManager->decide($exception)) {
            return;
        }

        if (in_array(get_class($exception), $this->scrubExceptions)) {
            $exception = $this->scrubException($exception);
        }

        $this->prepareGlobalServer($request);
        $result = $this->rollbarNotifier->report_exception($exception, $extraData);
        $this->cleanGlobalServer();

        return $result;
    }

    /**
     * Report an error to the rollbar.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     */
    public function reportError($level, $message, $file, $line, Request $request = null)
    {
        if (error_reporting() & $level &&
            $this->errorLevel & $level &&
            true === $this->reportDecisionManager->decide(new ErrorException($message, 0, $level, $file, $line))
        ) {
            $this->prepareGlobalServer($request);
            $this->rollbarNotifier->report_php_error($level, $message, $file, $line);
            $this->cleanGlobalServer();
        }
    }

    /**
     * Flush the rollbar Notifier.
     */
    public function flush()
    {
        $this->rollbarNotifier->flush();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     */
    private function prepareGlobalServer(Request $request = null)
    {
        if ($request) {
            $_SERVER['HTTP_REQUEST_CONTENT'] = $request->getContent();
        }
    }

    private function cleanGlobalServer()
    {
        unset($_SERVER['HTTP_REQUEST_CONTENT']);
    }

    /**
     * Scrub Exception.
     *
     * @return Exception
     */
    private function scrubException($originalException)
    {
        $exception = FlattenException::create($originalException);

        $trace = $exception->getTrace();
        foreach ($trace as $key => $item) {
            array_walk_recursive($item['args'], function (&$value, $key, $params) {
                if (is_string($value) && $key = array_search($value, $params)) {
                    $value = '%' . $key . '%';
                }
            }, $this->scrubParameters);

            $trace[$key] = $item;
        }

        $exception->setTrace($trace, $exception->getFile(), $exception->getLine());

        return $exception;
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
            if (!$user) {
                return null;
            }
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
}
