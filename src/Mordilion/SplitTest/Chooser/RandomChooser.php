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
     * @param Experiment $experiment
     *
     * @return Variation|null
     */
    public function choose(Experiment $experiment): ?Variation
    {
        $random = $this->getRandomBySeed($experiment);
        $distribution = 0;

        foreach ($experiment->getVariations() as $variation) {
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
     * @param Experiment $experiment
     *
     * @return int
     */
    private function getRandomBySeed(Experiment $experiment): int
    {
        if ($experiment->getSeed() !== 0) {
            mt_srand($experiment->getSeed());
        }

        $distributions = array_map(static function (Variation $variation) {
            return $variation->getDistribution();
        }, $experiment->getVariations());

        return mt_rand(1, (int) array_sum($distributions));
    }
}
