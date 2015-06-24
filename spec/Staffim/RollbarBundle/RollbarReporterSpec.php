<?php

namespace spec\Staffim\RollbarBundle;

use PhpSpec\ObjectBehavior;
use RollbarNotifier;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Staffim\RollbarBundle\ReportDecisionManager;

class RollbarReporterSpec extends ObjectBehavior
{
    /**
     * @param \RollbarNotifier $rollbarNotifier
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param \Staffim\RollbarBundle\ReportDecisionManager $reportDecisionManager
     */
    function let(
        RollbarNotifier $rollbarNotifier,
        SecurityContextInterface $securityContext,
        ReportDecisionManager $reportDecisionManager
    ) {
        $rollbarNotifier->flush()->willReturn(null);
        $this->beConstructedWith($rollbarNotifier, $securityContext, $reportDecisionManager);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Staffim\RollbarBundle\RollbarReporter');
    }

    function it_should_report_exception_when_decision_true(\Exception $e, $reportDecisionManager, $rollbarNotifier)
    {
        $reportDecisionManager->decide($e)->willReturn(true);
        $rollbarNotifier->report_exception($e)->shouldBeCalled();
        $this->report($e);
    }

    function it_should_not_report_exception_when_decision_false(\Exception $e, $reportDecisionManager, $rollbarNotifier)
    {
        $reportDecisionManager->decide($e)->willReturn(false);
        $rollbarNotifier->report_exception($e)->shouldNotBeCalled();
        $this->report($e);
    }
}
