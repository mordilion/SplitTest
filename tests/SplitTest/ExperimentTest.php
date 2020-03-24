<?php

declare(strict_types=1);

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Facade\Experiment as ExperimentFacade;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;
use PHPUnit\Framework\TestCase;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
class ExperimentTest extends TestCase
{
    public function testTestSelectsEveryTimeTheSameVariationBasedOnProvidedSeed()
    {
        $seed1 = 123456789;
        $seed2 = 987654321;
        $seed3 = time();

        $test = new Experiment('test-test', true);
        $experimentFacade = new ExperimentFacade($test);

        for ($i = 1; $i < 1000; $i++) { // make it hard!
            $test->addVariation(new Variation((string) $i));
        }

        $test->setSeed($seed1);
        $seedVariation1 = $experimentFacade->selectVariation();
        $seedVariation2 = $experimentFacade->selectVariation();

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());

        $test->setSeed($seed2);
        $seedVariation1 = $experimentFacade->selectVariation();
        $seedVariation2 = $experimentFacade->selectVariation();

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());

        $test->setSeed($seed3);
        $seedVariation1 = $experimentFacade->selectVariation();
        $seedVariation2 = $experimentFacade->selectVariation();

        $this->assertSame($seedVariation1->getName(), $seedVariation2->getName());
    }

    public function testTestSelectsEachTimeTheSameVariationIfThereIsOnlyOneVariation()
    {
        $test = new Experiment('test-test', true);
        $experimentFacade = new ExperimentFacade($test);

        $variationA = new Variation('A');
        $test->addVariation($variationA);

        $variationB = new Variation('B');
        $test->addVariation($variationB);

        for ($i = 1; $i <= 1000; $i++) {
            $this->assertSame($variationB, $experimentFacade->selectVariation(false, 'B'));
        }
    }
}
