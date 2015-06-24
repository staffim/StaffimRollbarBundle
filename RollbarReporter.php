<?php

namespace Staffim\RollbarBundle;

use Exception;
use RollbarNotifier;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class RollbarReporter
{
    protected $rollbarNotifier;
    protected $securityContext;
    protected $reportDecisionManager;
    protected $reportVoter;
    protected $errorLevel;

    public function __construct(
        RollbarNotifier $rollbarNotifier,
        SecurityContextInterface $securityContext,
        ReportDecisionManager $reportDecisionManager,
        $errorLevel = null,
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
        register_shutdown_function(array($this, 'flush'));
        $this->previousErrorHandler = set_error_handler(array($this, 'reportError'));
    }

    /**
     * Add an exception to the stack
     *
     * @param  Exception $exception
     */
    public function report(Exception $exception)
    {
        if (false === $this->reportDecisionManager->decide($exception)) {
            return;
        }

        if (in_array(get_class($exception), $this->scrubExceptions)) {
            $exception = $this->scrubException($exception);
        }

        $this->rollbarNotifier->report_exception($exception);
    }

    /**
     * Flush the rollbar Notifier.
     */
    public function flush()
    {
        $this->rollbarNotifier->flush();
    }

    /**
     * Scrub Exception
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
    private function getUserData()
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
    public function reportError($level, $message, $file, $line, $context)
    {
        if (error_reporting() & $level &&
            $this->errorLevel & $level &&
            true === $this->reportDecisionManager->decide(new ErrorException($message, 0, $level, $file, $line))
        ) {
            if ($this->request) {
                $_SERVER['HTTP_REQUEST_CONTENT'] = $this->request->getContent();
            }
            $this->rollbarNotifier->report_php_error($level, $message, $file, $line);
            unset($_SERVER['HTTP_REQUEST_CONTENT']);
        }
        if ($this->previousErrorHandler) {
            return call_user_func($this->previousErrorHandler, $level, $message, $file, $line, $context);
        } else {
            return false;
        }
    }
}
