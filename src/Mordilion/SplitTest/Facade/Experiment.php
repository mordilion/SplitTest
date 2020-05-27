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

use Mordilion\SplitTest\Model\Experiment as ExperimentModel;
use Mordilion\SplitTest\Model\Experiment\Variation as VariationModel;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
final class Experiment
{
    /**
     * @var ExperimentModel
     */
    private $experiment;


    /**
     * Test constructor.
     *
     * @param ExperimentModel $experiment
     */
    public function __construct(ExperimentModel $experiment)
    {
        $this->experiment = $experiment;
    }

    /**
     * @param VariationModel $variation
     */
    public function callCallback(VariationModel $variation): void
    {
        if (!$this->experiment->isEnabled()) {
            return;
        }

        $callback = $this->experiment->getCallback();

        if (is_callable($callback)) {
            $callback($this->experiment, $variation);
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

        $seed = (int)hexdec(substr(md5($this->experiment->getName()), 0, 7));

        return $baseSeed - $seed;
    }

    /**
     * @param string $name
     *
     * @return VariationModel|null
     */
    public function getVariationByName(string $name): ?VariationModel
    {
        $variations = $this->experiment->getVariations();

        foreach ($variations as $variation) {
            if ($variation->getName() === $name) {
                return $variation;
            }
        }

        return null;
    }

    /**
     * @param bool        $force
     * @param string|null $variationName
     *
     * @return VariationModel
     */
    public function selectVariation(bool $force = false, string $variationName = null): VariationModel
    {
        $selectedVariation = $this->experiment->getSelectedVariation();

        if ($selectedVariation === null || $force) {
            $variations = $this->experiment->getVariations();
            $selectedVariation = $variationName !== null ? $this->getVariationByName($variationName) : reset($variations);

            if ((count($variations) > 1 && $variationName === null) || $selectedVariation === null) {
                $random = $this->getRandomBySeed($variations);
                $distribution = 0;

                foreach ($variations as $variation) {
                    $distribution += $variation->getDistribution();

                    if ($random <= $distribution) {
                        $selectedVariation = $variation;

                        break;
                    }
                }
            }

            if ($selectedVariation === null) {
                throw new \RuntimeException('Cannot select a Variation.');
            }

            $this->callCallback($selectedVariation);
            $this->experiment->setSelectedVariation($selectedVariation);
        }

        return $selectedVariation;
    }

    /**
     * @param VariationModel[] $variations
     *
     * @return int
     */
    private function getRandomBySeed(array $variations): int
    {
        if ($this->experiment->getSeed() !== 0) {
            mt_srand($this->experiment->getSeed());
        }

        $distributions = array_map(static function ($variation) {
            /** @var VariationModel $variation */
            return $variation->getDistribution();
        }, $variations);

        return mt_rand(1, (int) array_sum($distributions));
    }
}
