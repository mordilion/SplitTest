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
     * @param string $prefix
     * @param string $deliveryName
     */
    public function deliver(string $prefix = 'SPLIT_TEST_', string $deliveryName = 'X-Split-Tests'): void
    {
        if (empty($deliveryName)) {
            throw new \RuntimeException('The delivery name must be set.');
        }

        $tests = [];

        /** @var Test $test */
        foreach ($this->getTests() as $test) {
            $testFacade = new TestFacade($test);
            /** @var Test\Variation $variation */
            $variation = $testFacade->selectVariation();

            $testName = urlencode($prefix . $test->getName());
            $variationName = $variation->getName();

            define($testName, $variationName);
            $tests[] = $testName . '=' . $variationName;
        }

        setcookie($deliveryName, implode(';', $tests));
        header($deliveryName . ': ' . implode(';', $tests));
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
}
