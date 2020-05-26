<?php

declare(strict_types=1);

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;
use PHPUnit\Framework\TestCase;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
class ContainerTest extends TestCase
{
    public function testContainerAcceptTestObject()
    {
        $container = new Container();

        $test = new Experiment('test-test', true);
        $test->addVariation(new Variation('A'));
        $test->addVariation(new Variation('B'));

        $container->addExperiment($test);

        $this->assertContains($test, $container->getExperiments());
        $this->assertCount(1, $container->getExperiments());
    }

    public function testContainerThrowsInvalidArgumentExceptionIfTestHasNoVariations()
    {
        $this->expectException(\InvalidArgumentException::class);

        $container = new Container();

        $test = new Experiment('test-test', true);

        $container->addExperiment($test);
    }

    public function testContainerThrowsInvalidArgumentExceptionIfTestWithSameNameAlreadyExists()
    {
        $this->expectException(\InvalidArgumentException::class);

        $container = new Container();

        $test1 = new Experiment('test-test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Experiment('test-test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addExperiment($test1);
        $container->addExperiment($test2);
    }

    public function testContainerSetsSeedToTestObjects()
    {
        $container = new Container(time());

        $test1 = new Experiment('First Test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Experiment('Second Test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addExperiment($test1);
        $container->addExperiment($test2);

        $this->assertContains($test1, $container->getExperiments());
        $this->assertContains($test2, $container->getExperiments());
        $this->assertCount(2, $container->getExperiments());
        $this->assertNotEquals(0, $test1->getSeed());
        $this->assertNotEquals(0, $test2->getSeed());
        $this->assertNotSame($test1->getSeed(), $test2->getSeed());
    }

    public function testContainerDoesNotSetSeedToTestObjectsIfNoSeedIsGiven()
    {
        $container = new Container();

        $test1 = new Experiment('First Test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Experiment('Second Test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addExperiment($test1);
        $container->addExperiment($test2);

        $this->assertContains($test1, $container->getExperiments());
        $this->assertContains($test2, $container->getExperiments());
        $this->assertCount(2, $container->getExperiments());
        $this->assertEmpty($test1->getSeed());
        $this->assertEmpty($test2->getSeed());
    }

    public function testContainerCanBeCreatedFromString()
    {
        $string = 'First+Test:1478982179:1:g1,g2,g3=Version+C:1|Second+Test:1344290232:0:g2=Version+B:1';

        $container = Container::fromString(urldecode($string));

        $this->assertCount(2, $container->getExperiments());
        $this->assertCount(2, $container->getExperiments(['g2']));
        $this->assertCount(1, $container->getExperiments(['g1']));
        $this->assertCount(1, $container->getExperiments(['g3']));

        foreach ($container->getExperiments() as $test) {
            $this->assertCount(1, $test->getVariations());
        }
    }

    public function testContainerProvidesRightTestVariation()
    {
        $string = 'Test:1478179:1=A:1,B:1,C:1';

        $container = Container::fromString(urldecode($string));
        $variation = $container->getExperimentVariation('Test');

        for ($i = 1; $i <= 1000; $i++) {
            $this->assertSame($variation, $container->getExperimentVariation('Test'));
        }
    }
}
