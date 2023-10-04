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

namespace Mordilion\SplitTest\Model\Experiment;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class Group
{
    private const FROM_STRING_PATTERN = '/([\w\_\-]+)(\(([^\)]*)\))?/';

    /**
     * @var string
     */
    private $name;

    /**
     * @var Variation[]
     */
    private $variations = [];


    /**
     * Group constructor.
     *
     * @param string      $name
     * @param Variation[] $variations
     */
    public function __construct(string $name, array $variations)
    {
        $this->name = $name;

        foreach ($variations as $variation) {
            $this->addVariation($variation);
        }
    }

    public static function fromString(string $string): Group
    {
        $string = trim($string);

        if (!preg_match(self::FROM_STRING_PATTERN, $string, $matches, 0, 0)) {
            throw new \InvalidArgumentException('Could not match string.');
        }

        $variations = Variation::collectionFromString($matches[3] ?? '');

        return new self($matches[1], $variations);
    }

    /**
     * @param string $string
     *
     * @return Group[]
     */
    public static function collectionFromString(string $string): array
    {
        if (empty($string)) {
            return [];
        }

        if (!preg_match_all(self::FROM_STRING_PATTERN, $string, $matches, PREG_SET_ORDER)) {
            throw new \InvalidArgumentException('Could not match string.');
        }

        return array_filter(array_map(static function (array $match) {
            $item = $match[0];

            return !empty($item) ? self::fromString($item) : null;
        }, $matches));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Variation $variation
     */
    public function addVariation(Variation $variation): void
    {
        $this->variations[$variation->getName()] = $variation;
    }

    /**
     * @return Variation[]
     */
    public function getVariations(): array
    {
        return $this->variations;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = $this->getName();
        $variations = $this->getVariations();

        if (count($variations) > 0) {
            $result .= '(' . implode(',', $variations) . ')';
        }

        return $result;
    }
}
