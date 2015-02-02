<?php

namespace Staffim\RollbarBundle\Voter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SameRefererVoter implements ReportVoterInterface
{
    private $requestStack;

    public function __construct($requestStack)
    {
        $this->requestStack = $requestStack;
    }

    private function support($exception)
    {
        return $exception instanceof NotFoundHttpException &&
            null !== $this->requestStack->getCurrentRequest()
        ;
    }

    public function vote($exception)
    {
        if (!$this->support($exception)) {
            return true;
        }

        return $this->sameRefererRequest();
    }

    public function sameRefererRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        $referer = parse_url($request->server->get('HTTP_REFERER'), PHP_URL_HOST);

        return $referer === $request->getHost();
    }
}
