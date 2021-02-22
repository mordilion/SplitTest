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

namespace Mordilion\SplitTest\Chooser;

use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class BalancedChooser implements ChooserInterface
{
    /**
     * @param Experiment $experiment
     *
     * @return Variation|null
     */
    public function choose(Experiment $experiment): ?Variation
    {
        $index = $this->getIndexBySeed($experiment);

        return $experiment->getVariations()[$index] ?? null;
    }

    /**
     * @param Experiment $experiment
     *
     * @return int
     */
    private function getIndexBySeed(Experiment $experiment): int
    {
        $distributions = array_map(static function (Variation $variation) {
            return $variation->getDistribution();
        }, $experiment->getVariations());

        asort($distributions);
        $total = (int) array_sum($distributions);
        $seedPercentage = ($experiment->getSeed() % 100) + 1;
        $percentage = 0;

        foreach ($distributions as $index => $distribution) {
            $percentage += $distribution / $total * 100;

            if ($seedPercentage <= $percentage) {
                return $index;
            }
        }

        return 0;
    }
}
