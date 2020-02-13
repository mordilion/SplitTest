<?php

declare(strict_types=1);

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Model\Test;
use Mordilion\SplitTest\Model\Test\Variation;
use PHPUnit\Framework\TestCase;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
class ContainerTest extends TestCase
{
    public function testContainerAcceptTestObject()
    {
        $container = new Container();

        $test = new Test('test-test', true);
        $test->addVariation(new Variation('A'));
        $test->addVariation(new Variation('B'));

        $container->addTest($test);

        $this->assertContains($test, $container->getTests());
        $this->assertCount(1, $container->getTests());
    }

    public function testContainerThrowsInvalidArgumentExceptionIfTestHasNoVariations()
    {
        $this->expectException(\InvalidArgumentException::class);

        $container = new Container();

        $test = new Test('test-test', true);

        $container->addTest($test);
    }

    public function testContainerThrowsInvalidArgumentExceptionIfTestWithSameNameAlreadyExists()
    {
        $this->expectException(\InvalidArgumentException::class);

        $container = new Container();

        $test1 = new Test('test-test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Test('test-test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addTest($test1);
        $container->addTest($test2);
    }

    public function testContainerSetsSeedToTestObjects()
    {
        $container = new Container(time());

        $test1 = new Test('First Test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Test('Second Test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addTest($test1);
        $container->addTest($test2);

        $this->assertContains($test1, $container->getTests());
        $this->assertContains($test2, $container->getTests());
        $this->assertCount(2, $container->getTests());
        $this->assertNotEquals(0, $test1->getSeed());
        $this->assertNotEquals(0, $test2->getSeed());
        $this->assertNotSame($test1->getSeed(), $test2->getSeed());
    }

    public function testContainerDoesNotSetSeedToTestObjectsIfNoSeedIsGiven()
    {
        $container = new Container();

        $test1 = new Test('First Test', true);
        $test1->addVariation(new Variation('A'));
        $test1->addVariation(new Variation('B'));

        $test2 = new Test('Second Test', true);
        $test2->addVariation(new Variation('A'));
        $test2->addVariation(new Variation('B'));

        $container->addTest($test1);
        $container->addTest($test2);

        $this->assertContains($test1, $container->getTests());
        $this->assertContains($test2, $container->getTests());
        $this->assertCount(2, $container->getTests());
        $this->assertEmpty($test1->getSeed());
        $this->assertEmpty($test2->getSeed());
    }

    public function testContainerCanBeCreatedFromString()
    {
        $string = 'First+Test:1478982179:1=Version+C:1;Second+Test:1344290232:0=Version+B:1';

        $container = Container::fromString(urldecode($string));

        $this->assertCount(2, $container->getTests());

        foreach ($container->getTests() as $test) {
            $this->assertCount(1, $test->getVariations());
        }
    }

    public function testContainerProvidesRightTestVariation()
    {
        $string = 'Test:1478179:1=A:1,B:1,C:1';

        $container = Container::fromString(urldecode($string));
        $variation = $container->getTestVariation('Test');

        for ($i = 1; $i <= 1000; $i++) {
            $this->assertSame($variation, $container->getTestVariation('Test'));
        }
    }
}
