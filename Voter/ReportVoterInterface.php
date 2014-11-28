<?php

namespace Staffim\RollbarBundle\Voter;

interface ReportVoterInterface
{
    public function vote($exception);
}
