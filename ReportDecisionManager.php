<?php

namespace Staffim\RollbarBundle;

use Exception;

class ReportDecisionManager
{
    private $voters;

    public function __construct(array $voters)
    {
        $this->voters = $voters;
    }

    public function decide($exception, array $context = array())
    {
        foreach ($this->voters as $voter) {
            if (!$voter->vote($exception, $context)) {
                return false;
            }
        }

        return true;
    }
}
