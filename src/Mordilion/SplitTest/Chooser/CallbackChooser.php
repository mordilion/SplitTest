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
final class CallbackChooser implements ChooserInterface
{
    /**
     * @var callable
     */
    private $callback;


    /**
     * CallbackChooser constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Experiment $experiment
     *
     * @return Variation|null
     */
    public function choose(Experiment $experiment): ?Variation
    {
        $variation = call_user_func($this->callback, $experiment);

        if (!$variation instanceof Variation && $variation !== null) {
            throw new \RuntimeException('The returned value needs to be null or an instance of ' . Variation::class);
        }

        return $variation;
    }
}
