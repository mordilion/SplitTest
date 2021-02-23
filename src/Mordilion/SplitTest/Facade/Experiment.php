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

use Mordilion\SplitTest\Chooser\ChooserInterface;
use Mordilion\SplitTest\Chooser\RandomChooser;
use Mordilion\SplitTest\Model\Experiment as ExperimentModel;
use Mordilion\SplitTest\Model\Experiment\Variation as VariationModel;

/**
 * @author Henning Huncke <henning.huncke@check24.de>
 */
final class Experiment
{
    /**
     * @var ChooserInterface
     */
    private $chooser;

    /**
     * @var ExperimentModel
     */
    private $experiment;


    /**
     * Test constructor.
     *
     * @param ExperimentModel  $experiment
     * @param ChooserInterface $chooser
     */
    public function __construct(ExperimentModel $experiment, ChooserInterface $chooser)
    {
        $this->experiment = $experiment;
        $this->chooser = $chooser;
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

        $seed = (int) hexdec(substr(md5($this->experiment->getName()), 0, 10));

        return max($baseSeed, $seed) - min($baseSeed, $seed);
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

            if ($selectedVariation === null || (count($variations) > 1 && $variationName === null)) {
                $selectedVariation = $this->chooser->choose($this->experiment);
            }

            if ($selectedVariation === null) {
                throw new \RuntimeException('Cannot select a Variation.');
            }

            $this->callCallback($selectedVariation);
            $this->experiment->setSelectedVariation($selectedVariation);
        }

        return $selectedVariation;
    }
}
