<?php

namespace Staffim\RollbarBundle;

use Staffim\RollbarBundle\DependencyInjection\CompilerPass\RegisterDecisionVoterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Vyacheslav Salakhutdinov <megazoll@gmail.com>
 */
class StaffimRollbarBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterDecisionVoterPass());
    }
}
