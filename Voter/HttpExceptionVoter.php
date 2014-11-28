<?php

namespace Staffim\RollbarBundle\Voter;

use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpExceptionVoter implements ReportVoterInterface
{
    private function support($exception)
    {
        return $exception instanceof HttpException;
    }

    public function vote($exception)
    {
        return !$this->support($exception);
    }
}
