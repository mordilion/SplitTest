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

namespace Mordilion\SplitTest\Model\Test;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class Variation
{
    private const FROM_STRING_PATTERN = '/([\w\s_-]+)\:(\d+),?/';


    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $distribution;


    /**
     * Variation constructor.
     *
     * @param string $name
     * @param int    $distribution
     */
    public function __construct(string $name, int $distribution = 1)
    {
        if ($distribution < 1 || $distribution > 100) {
            throw new \InvalidArgumentException('The provided $distribution must be a value between 1 and 100.');
        }

        $this->name = $name;
        $this->distribution = $distribution;
    }

    /**
     * @param string $string
     *
     * @return Variation
     */
    public static function fromString(string $string): Variation
    {
        $string = trim($string);

        if (!preg_match(self::FROM_STRING_PATTERN, $string, $matches, 0, 0)) {
            throw new \InvalidArgumentException('Could not match string.');
        }

        $name = $matches[1];
        $distribution = (int) $matches[2];

        return new self($name, $distribution);
    }

    /**
     * @param string $string
     *
     * @return Variation[]
     */
    public static function collectionFromString(string $string): array
    {
        $collection = [];
        $items = explode(',', $string);

        foreach ($items as $item) {
            $collection[] = self::fromString($item);
        }

        return $collection;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getDistribution(): int
    {
        return $this->distribution;
    }
}
