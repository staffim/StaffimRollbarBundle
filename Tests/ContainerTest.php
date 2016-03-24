<?php

namespace Staffim\DTOBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContainerTest extends KernelTestCase
{
    public function testContainerHasRollbar()
    {
        $this->assertTrue($this->getContainer()->has('staffim_rollbar.rollbar_listener'));
    }

    private function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function setUp()
    {
        $this->bootKernel();
    }
}
