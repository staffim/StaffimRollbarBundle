<?php

namespace Staffim\RollbarBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterDecisionVoterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('staffim_rollbar.report_decision_manager')) {
            return;
        }

        $voters = array();
        foreach ($container->findTaggedServiceIds('staffim_rollbar.report_voter') as $id => $tags) {
            foreach ($tags as $tag) {
                $voters[] = new Reference($id);
            }
        }

        $container
            ->getDefinition('staffim_rollbar.report_decision_manager')
            ->replaceArgument(0, $voters)
        ;

        if (!$container->hasDefinition('request_stack')) {
            $container->removeDefinition('staffim_rollbar.same_referer_voter');
        }
    }
}
