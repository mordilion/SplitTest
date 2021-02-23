<?php

declare(strict_types=1);

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Chooser\BalancedChooser;
use Mordilion\SplitTest\Chooser\RandomChooser;
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
        $string = 'First-Test:1478982179:1:g1,g2,g3=Version+C:1|Second-Test:1344290232:0:g2=Version+B:1';

        $container = Container::fromString(urldecode($string));

        $this->assertCount(2, $container->getExperiments());
        $this->assertCount(2, $container->getExperiments(['g2']));

        $this->assertCount(1, $container->getExperiments(['g1']));
        $this->assertEquals('First-Test', array_values($container->getExperiments(['g1']))[0]->getName());

        $this->assertCount(1, $container->getExperiments(['g3']));
        $this->assertEquals('First-Test', array_values($container->getExperiments(['g1']))[0]->getName());

        foreach ($container->getExperiments() as $test) {
            $this->assertCount(1, $test->getVariations());
        }

        $experiments = $container->getExperiments(['g1', 'g2'], false);
        $this->assertCount(2, $experiments);

        $experiments = $container->getExperiments(['g1', 'g2'], true);
        $this->assertCount(1, $experiments);
        $this->assertEquals('First-Test', array_values($experiments)[0]->getName());
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

    public function testContainerDistribution()
    {
        $counts = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
        ];

        for ($i = 0; $i < 1000; $i++) {
            $string = '007:0:1=A:60,B:5,C:35';

            $container = Container::fromString(urldecode($string), $i);
            $variation = $container->getExperimentVariation('007');

            $counts[$variation->getName()]++;
        }

        $valueA = (int) round($counts['A'] / (array_sum($counts) / 100));
        $valueB = (int) round($counts['B'] / (array_sum($counts) / 100));
        $valueC = (int) round($counts['C'] / (array_sum($counts) / 100));

        $this->assertEquals(60, $valueA);
        $this->assertEquals(5, $valueB);
        $this->assertEquals(35, $valueC);
    }

    public function testContainerDistributionWithSeedsFile()
    {
        $counts = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
        ];

        if ($fh = fopen(__DIR__ . '/seeds.txt', 'rb')) {
            while (!feof($fh)) {
                $seed = (int) fgets($fh, 25);
                $string = '007:0:1=A:60,B:5,C:35';

                $container = Container::fromString(urldecode($string), $seed);
                $variation = $container->getExperimentVariation('007');

                $counts[$variation->getName()]++;
            }
        }

        $valueA = (int) round($counts['A'] / (array_sum($counts) / 100));
        $valueB = (int) round($counts['B'] / (array_sum($counts) / 100));
        $valueC = (int) round($counts['C'] / (array_sum($counts) / 100));

        $this->assertEquals(60, $valueA);
        $this->assertEquals(5, $valueB);
        $this->assertEquals(35, $valueC);
    }
}
