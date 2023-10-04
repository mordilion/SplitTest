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
final class RandomChooser implements ChooserInterface
{
    /**
     * @param Experiment  $experiment
     * @param Variation[] $variations
     *
     * @return Variation|null
     */
    public function choose(Experiment $experiment, array $variations): ?Variation
    {
        $random = $this->getRandomBySeed($experiment, $variations);
        $distribution = 0;

        foreach ($variations as $variation) {
            if ($variation->getDistribution() === 0) {
                continue;
            }

            $distribution += $variation->getDistribution();

            if ($random <= $distribution) {
                return $variation;
            }
        }

        return null;
    }

    /**
     * @param Experiment  $experiment
     * @param Variation[] $variations
     *
     * @return int
     */
    private function getRandomBySeed(Experiment $experiment, array $variations): int
    {
        if ($experiment->getSeed() !== 0) {
            mt_srand($experiment->getSeed());
        }

        $distributions = array_map(static function (Variation $variation) {
            return $variation->getDistribution();
        }, $variations);

        return mt_rand(1, array_sum($distributions));
    }
}
