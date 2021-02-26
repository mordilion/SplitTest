<?php

declare(strict_types=1);

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Chooser\RandomChooser;
use Mordilion\SplitTest\Chooser\StaticChooser;
use Mordilion\SplitTest\Facade\Experiment as ExperimentFacade;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;
use PHPUnit\Framework\TestCase;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
class ExperimentTest extends TestCase
{
    public function testExperimentHasVariationWithZeorDistribution()
    {
        $test = new Experiment('test-test', true);
        $test->addVariation(new Variation('A', 0));
        $test->addVariation(new Variation('B'));

        $facade = new ExperimentFacade($test, new StaticChooser('B'));

        for ($i = 1; $i <= 100; $i++) {
            $test->setSeed($i);
            $selectedVariation = $facade->selectVariation('', '', true);
            $this->assertEquals('B', $selectedVariation->getName());
        }
    }

    public function testTestSelectsEveryTimeTheSameVariationBasedOnProvidedSeed()
    {
        $seed1 = 123456789;
        $seed2 = 987654321;
        $seed3 = time();

        $test = new Experiment('test-test', true);
        $experimentFacade = new ExperimentFacade($test, new RandomChooser());

        for ($i = 1; $i < 1000; $i++) { // make it hard!
            $test->addVariation(new Variation((string) $i));
        }

        $test->setSeed($seed1);
        $seedVariation1 = $experimentFacade->selectVariation('', '',true);
        $seedVariation2 = $experimentFacade->selectVariation('', '',true);

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());

        $test->setSeed($seed2);
        $seedVariation1 = $experimentFacade->selectVariation();
        $seedVariation2 = $experimentFacade->selectVariation('', '',true);

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());

        $test->setSeed($seed3);
        $seedVariation1 = $experimentFacade->selectVariation('', '',true);
        $seedVariation2 = $experimentFacade->selectVariation();

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());
    }

    public function testTestSelectsEachTimeTheSameVariationIfThereIsOnlyOneVariation()
    {
        $test = new Experiment('test-test', true);
        $experimentFacade = new ExperimentFacade($test, new RandomChooser());

        $variationA = new Variation('A');
        $test->addVariation($variationA);

        $variationB = new Variation('B');
        $test->addVariation($variationB);

        for ($i = 1; $i <= 1000; $i++) {
            $this->assertSame($variationB, $experimentFacade->selectVariation('', 'B',true));
        }
    }

    public function testExperimentWithAZeroDsitributionVariation()
    {
        $test = new Experiment('test-test', true);
        $experimentFacade = new ExperimentFacade($test, new RandomChooser());

        $variationA = new Variation('A', 0);
        $test->addVariation($variationA);

        $variationB = new Variation('B', 100);
        $test->addVariation($variationB);

        for ($i = 0; $i <= 1000; $i++) {
            $test->setSeed($i);
            $this->assertSame($variationB, $experimentFacade->selectVariation('', 'B',true));
        }
    }

    public function testExperimentDistribution()
    {
        $test = new Experiment('test-test', true);
        $test->addVariation(new Variation('A', 80));
        $test->addVariation(new Variation('B', 20));

        $facade = new ExperimentFacade($test, new RandomChooser());
        $counts = [
            'A' => 0,
            'B' => 0,
        ];

        $start = 987654;

        for ($i = $start; $i < $start + 10000; $i++) {
            $test->setSeed($i);
            $selectedVariation = $facade->selectVariation('', '',true);

            $counts[$selectedVariation->getName()]++;
        }

        $this->assertEquals(80, round($counts['A'] / (($counts['B'] + $counts['A']) / 100)));
        $this->assertEquals(20, round($counts['B'] / (($counts['B'] + $counts['A']) / 100)));
    }
}
