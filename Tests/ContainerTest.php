<?php

namespace Staffim\DTOBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Staffim\DTOBundle\Serializer\SerializationContext;

use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;
use Staffim\DTOBundle\DTO\Model\DTOInterface;

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
