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
final class StaticChooser implements ChooserInterface
{
    /**
     * @var int|string
     */
    private $choice;


    /**
     * StaticChooser constructor.
     *
     * @param int|string $choice if it's int then it's the index of variation otherwise the name of a variation
     */
    public function __construct($choice)
    {
        if (!is_int($choice) && !is_string($choice)) {
            throw new \InvalidArgumentException('The provided $choice needs to be a string or an int.');
        }

        $this->choice = $choice;
    }

    /**
     * @param Experiment $experiment
     *
     * @return Variation|null
     */
    public function choose(Experiment $experiment): ?Variation
    {
        $variations = $experiment->getVariations();
        $variationsNames = array_map(static function (Variation $variation) {
            return $variation->getName();
        }, $variations);

        return $variations[$this->choice] ?? ($variations[array_search($this->choice, $variationsNames, true)] ?? null);
    }
}
