<?php

/**
 * This file is part of the Mordilion\SplitTest package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @copyright (c) Henning Huncke - <mordilion@gmx.de>
 */

declare(strict_types=1);

namespace Mordilion\SplitTest\Facade;

use Mordilion\SplitTest\Model\Test as TestModel;
use Mordilion\SplitTest\Model\Test\Variation as VariationModel;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
final class Test
{
    /**
     * @var TestModel
     */
    private $test;


    /**
     * Test constructor.
     *
     * @param TestModel $test
     */
    public function __construct(TestModel $test)
    {
        $this->test = $test;
    }

    /**
     * @param VariationModel $variation
     */
    public function callCallback(VariationModel $variation): void
    {
        $callback = $this->test->getCallback();

        if (is_callable($callback)) {
            $callback($this->test, $variation);
        }
    }

    /**
     * @param int $baseSeed
     *
     * @return int
     */
    public function generateSeed(int $baseSeed): int
    {
        if ($baseSeed === 0) {
            return 0;
        }

        $seed = (int)hexdec(substr(md5($this->test->getName()), 0, 7));

        return $baseSeed - $seed;
    }

    /**
     * @return VariationModel
     */
    public function selectVariation(): VariationModel
    {
        $variations = $this->test->getVariations();
        $fallbackVariation = reset($variations);

        if (count($variations) === 1) {
            $this->callCallback($fallbackVariation);

            return $fallbackVariation;
        }

        $selectedVariation = $fallbackVariation;
        $random = $this->getRandomBySeed($variations);
        $distribution = 0;

        foreach ($variations as $variation) {
            $distribution += $variation->getDistribution();

            if ($random <= $distribution) {
                $selectedVariation = $variation;

                break;
            }
        }

        $this->callCallback($selectedVariation);

        return $selectedVariation;
    }

    /**
     * @param VariationModel[] $variations
     *
     * @return int
     */
    private function getRandomBySeed(array $variations): int
    {
        if ($this->test->getSeed() !== 0) {
            mt_srand($this->test->getSeed());
        }

        return mt_rand(1, array_sum(array_map(static function ($variation) {
            /** @var VariationModel $variation */
            return $variation->getDistribution();
        }, $variations)));
    }
}