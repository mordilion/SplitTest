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

namespace Mordilion\SplitTest;

use Mordilion\SplitTest\Facade\Test as TestFacade;
use Mordilion\SplitTest\Model\Test;
use Mordilion\SplitTest\Model\Test\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
class Container
{
    /**
     * @var int
     */
    private $seed;

    /**
     * @var Test[]
     */
    private $tests = [];


    /**
     * Container constructor.
     *
     * @param int $seed
     */
    public function __construct(int $seed = 0)
    {
        $this->setSeed($seed);
    }

    /**
     * @param string $string
     * @param int    $seed
     *
     * @return Container
     */
    public static function fromString(string $string, int $seed = 0): Container
    {
        $string = trim($string);
        $testStrings = explode('|', $string);

        $instance = new self($seed);

        foreach ($testStrings as $testString) {
            $test = Test::fromString($testString);
            $instance->addTest($test);
        }

        return $instance;
    }

    /**
     * @param string $cookie
     * @param string $header
     */
    public function deliver(string $cookie = 'SplitTests', string $header = 'X-Split-Tests'): void
    {
        $deliveryString = $this->toString();

        if (!empty($cookie)) {
            setcookie($cookie, $deliveryString);
        }

        if (!empty($header)) {
            header($header . ': ' . $deliveryString);
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

        foreach ($this->tests as $test) {
            $testFacade = new TestFacade($test);
            $testSeed = $testFacade->generateSeed($this->seed);

            $test->setSeed($testSeed);
        }
    }

    /**
     * @param Test $test
     */
    public function addTest(Test $test): void
    {
        if (empty($test->getVariations())) {
            throw new \InvalidArgumentException('The provided $test has no Variations.');
        }

        if (array_key_exists($test->getName(), $this->tests)) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" already exists.', $test->getName()));
        }

        $testFacade = new TestFacade($test);
        $testSeed = $testFacade->generateSeed($this->seed);
        $test->setSeed($testSeed);

        $this->tests[$test->getName()] = $test;
    }

    /**
     * @param string $name
     *
     * @return Test|null
     */
    public function getTest(string $name): ?Test
    {
        return $this->tests[$name] ?? null;
    }

    /**
     * @param string      $testName
     * @param string|null $variationName
     *
     * @return Variation|null
     */
    public function getTestVariation(string $testName, ?string $variationName = null): ?Variation
    {
        $test = $this->getTest($testName);

        if ($test === null) {
            return null;
        }

        $testFacade = new TestFacade($test);

        return $testFacade->selectVariation(false, $variationName);
    }

    /**
     * @return Test[]
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @param Test[] $tests
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $tests = [];

        /** @var Test $test */
        foreach ($this->getTests() as $test) {
            $testFacade = new TestFacade($test);
            /** @var Variation $variation */
            $variation = $testFacade->selectVariation();

            $testName = urlencode($test->getName()) ;
            $variationName = urlencode($variation->getName());

            $tests[] = $testName . ':' . $test->getSeed() . ':' . (int) $test->isEnabled()
                . '=' . $variationName . ':' . $variation->getDistribution();
        }

        return implode('|', $tests);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
