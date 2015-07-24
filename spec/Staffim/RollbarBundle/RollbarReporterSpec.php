<?php

namespace spec\Staffim\RollbarBundle;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
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
        $rollbarNotifier->report_exception($e, null)->shouldBeCalled();
        $this->report($e);
    }

    function it_should_not_report_exception_when_decision_false(\Exception $e, $reportDecisionManager, $rollbarNotifier)
    {
        $reportDecisionManager->decide($e)->willReturn(false);
        $rollbarNotifier->report_exception($e, null)->shouldNotBeCalled();
        $this->report($e);
    }

    function it_should_report_exception_with_extra_data(\Exception $e, $reportDecisionManager, $rollbarNotifier)
    {
        $reportDecisionManager->decide($e)->willReturn(true);
        $extra = array('foo' => 'bar');
        $rollbarNotifier->report_exception($e, $extra)->shouldBeCalled();
        $this->report($e, null, $extra);
    }

    function it_should_report_error($reportDecisionManager, $rollbarNotifier)
    {
        $reportDecisionManager->decide(Argument::type('ErrorException'))->willReturn(true);
        $file = __FILE__;
        $line = __LINE__;
        $rollbarNotifier->report_php_error(E_USER_NOTICE, 'Error', $file, $line)->shouldBeCalled();
        $this->reportError(E_USER_NOTICE, 'Error', $file, $line);
    }
}
