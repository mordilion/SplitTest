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

use Mordilion\SplitTest\Model\Experiment\Group;
use Mordilion\SplitTest\Model\Experiment\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class Experiment
{
    private const FROM_STRING_PATTERN = '/([\w\s\_\-]+)\:(-?\d+)\:(1|0)(\:([\w\s\_\-\,\(\)\:]+))?\=((([\w\s\_\-]+)(\:\d+),?)(?7)*)/';


    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Group[]
     */
    private $groups = [];

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
    private $variations = [];

    /**
     * @var Variation[]
     */
    public $selectedVariations = [];


    /**
     * Test constructor.
     *
     * @param string        $name
     * @param bool          $enabled
     * @param Group[]       $groups
     * @param int           $seed
     * @param Variation[]   $variations
     * @param callable|null $callback
     */
    public function __construct(string $name, bool $enabled = false, array $groups = [], int $seed = 0, array $variations = [], ?callable $callback = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->seed = $seed;
        $this->callback = $callback;

        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        foreach ($variations as $variation) {
            $this->addVariation($variation);
        }
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
        $groups = Group::collectionFromString($matches[5] ?? '');
        $variations = Variation::collectionFromString($matches[6]);

        return new self($name, $enabled, $groups, $seed, $variations);
    }

    /**
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
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
     * @param Group $group
     */
    public function addGroup(Group $group): void
    {
        $this->groups[$group->getName()] = $group;
    }

    /**
     * @param string $name
     *
     * @return Group|null
     */
    public function getGroup(string $name): ?Group
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * @param array $names
     *
     * @return Group[]
     */
    public function getGroups(array $names = []): array
    {
        if (empty($names)) {
            return $this->groups;
        }

        return array_filter($this->groups, static function (string $key) use ($names) {
            return in_array($key, $names, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasGroup(string $name): bool
    {
        return array_key_exists($name, $this->groups);
    }

    /**
     * @param string $name
     */
    public function removeGroup(string $name): void
    {
        unset($this->groups[$name]);
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

        $this->selectedVariations = [];
    }

    /**
     * @param string $name
     *
     * @return Variation|null
     */
    public function getSelectedVariation(string $name = ''): ?Variation
    {
        $name = empty($name) ? 'default' : $name;

        return $this->selectedVariations[$name] ?? null;
    }

    /**
     * @param Variation|null $selectedVariation
     * @param string         $name
     */
    public function setSelectedVariation(?Variation $selectedVariation, string $name = ''): void
    {
        $name = empty($name) ? 'default' : $name;

        if (empty($selectedVariation)) {
            unset($this->selectedVariations[$name]);

            return;
        }

        $this->selectedVariations[$name] = $selectedVariation;
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
     * @param string $groupName
     *
     * @return Variation[]
     */
    public function getVariations(string $groupName = ''): array
    {
        $variations = $this->variations;
        $group = $this->getGroup($groupName);

        if ($group !== null) {
            $variations = array_replace($variations, $group->getVariations());
        }

        return $variations;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = $this->getName() . ':' . $this->getSeed() . ':' . (int) $this->isEnabled();
        $groups = $this->getGroups();

        if (count($groups) > 0) {
            $result .= ':' . implode(',', $groups);
        }

        $result .= '=' . implode(',', $this->getVariations());

        return $result;
    }
}
