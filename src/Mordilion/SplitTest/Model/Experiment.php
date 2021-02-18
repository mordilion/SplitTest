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

namespace Mordilion\SplitTest\Model;

use Mordilion\SplitTest\Model\Experiment\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class Experiment
{
    private const FROM_STRING_PATTERN = '/([\w\s\_\-]+)\:(-?\d+)\:(1|0)(\:([\w\s\_\-\,]+))?\=((([\w\s\_\-]+)(\:\d+),?)(?7)*)/';


    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var int
     */
    private $seed;

    /**
     * @var callable|null
     */
    private $callback;

    /**
     * @var Variation[]
     */
    private $variations;

    /**
     * @var Variation|null
     */
    private $selectedVariation;


    /**
     * Test constructor.
     *
     * @param string        $name
     * @param bool          $enabled
     * @param array         $groups
     * @param int           $seed
     * @param array         $variations
     * @param callable|null $callback
     */
    public function __construct(string $name, bool $enabled = false, array $groups = [], int $seed = 0, array $variations = [], ?callable $callback = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->groups = $groups;
        $this->seed = $seed;
        $this->variations = $variations;
        $this->callback = $callback;
    }

    /**
     * @param string $string Format: "test_1:423:1:g1,g2,g3=var_a:1,var_b:1,..." > "name:seed:enabled[:groups]=variation:weight,variation:weight,..."
     *
     * @return Experiment
     */
    public static function fromString(string $string): Experiment
    {
        $string = trim($string);

        if (!preg_match(self::FROM_STRING_PATTERN, $string, $matches, 0, 0)) {
            throw new \InvalidArgumentException('Could not match string.');
        }

        $name = $matches[1];
        $seed = (int) $matches[2];
        $enabled = filter_var($matches[3], FILTER_VALIDATE_BOOLEAN);
        $groups = explode(',', $matches[5] ?? '') ?: [];
        $variations = Variation::collectionFromString($matches[6]);

        return new self($name, $enabled, $groups, $seed, $variations);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param mixed $group
     */
    public function addGroup($group): void
    {
        $this->groups[] = $group;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param mixed $group
     *
     * @return bool
     */
    public function hasGroup($group): bool
    {
        return in_array($group, $this->groups, true);
    }

    /**
     * @param mixed $group
     */
    public function removeGroup($group): void
    {
        $key = array_search($group, $this->groups, true);

        if ($key !== false) {
            unset($this->groups[$key]);
        }
    }

    /**
     * @return int
     */
    public function getSeed(): int
    {
        return $this->seed;
    }

    /**
     * @param int $seed
     */
    public function setSeed(int $seed): void
    {
        $this->seed = $seed;

        $this->setSelectedVariation(null);
    }

    /**
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @param Variation $variation
     */
    public function addVariation(Variation $variation): void
    {
        if (array_key_exists($variation->getName(), $this->variations)) {
            throw new \InvalidArgumentException(sprintf('Variation with name "%s" already exists.', $variation->getName()));
        }

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
     * @return Variation|null
     */
    public function getSelectedVariation(): ?Variation
    {
        return $this->selectedVariation;
    }

    /**
     * @param Variation|null $selectedVariation
     */
    public function setSelectedVariation(?Variation $selectedVariation): void
    {
        $this->selectedVariation = $selectedVariation;
    }
}
