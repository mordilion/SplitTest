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

use Mordilion\SplitTest\Model\Test\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
final class Test
{
    private const FROM_STRING_PATTERN = '/([\w\s_-]+)\:(\d+)\:(1|0)\=((([\w\s_-]+)(\:\d+),?)(?5)*)/';


    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

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
     * @param int           $seed
     * @param array         $variations
     * @param callable|null $callback
     */
    public function __construct(string $name, bool $enabled = false, int $seed = 0, array $variations = [], ?callable $callback = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->seed = $seed;
        $this->variations = $variations;
        $this->callback = $callback;
    }

    /**
     * @param string $string Format: "test_1:423:1=var_a:1,var_b:1,..." > "name:seed=variation:weight,variation:weight,..."
     *
     * @return Test
     */
    public static function fromString(string $string): Test
    {
        $string = trim($string);

        if (!preg_match(self::FROM_STRING_PATTERN, $string, $matches, 0, 0)) {
            throw new \InvalidArgumentException('Could not match string.');
        }

        $name = $matches[1];
        $seed = (int) $matches[2];
        $enabled = filter_var($matches[3], FILTER_VALIDATE_BOOLEAN);
        $variations = Variation::collectionFromString($matches[4]);

        return new self($name, $enabled, $seed, $variations);
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
