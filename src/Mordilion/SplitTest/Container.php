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

use Mordilion\SplitTest\Facade\Experiment as ExperimentFacade;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;

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
     * @var Experiment[]
     */
    private $experiments = [];


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
            if (empty($testString)) {
                continue;
            }

            $test = Experiment::fromString($testString);
            $instance->addExperiment($test);
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

        foreach ($this->experiments as $test) {
            $experimentFacade = new ExperimentFacade($test);
            $testSeed = $experimentFacade->generateSeed($this->seed);

            $test->setSeed($testSeed);
        }
    }

    /**
     * @param Experiment $experiment
     */
    public function addExperiment(Experiment $experiment): void
    {
        if (empty($experiment->getVariations())) {
            throw new \InvalidArgumentException('The provided $test has no Variations.');
        }

        if (array_key_exists($experiment->getName(), $this->experiments)) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" already exists.', $experiment->getName()));
        }

        $experimentFacade = new ExperimentFacade($experiment);
        $testSeed = $experimentFacade->generateSeed($this->seed);
        $experiment->setSeed($testSeed);

        $this->experiments[$experiment->getName()] = $experiment;
    }

    /**
     * @param string $name
     *
     * @return Experiment|null
     */
    public function getExperiment(string $name): ?Experiment
    {
        return $this->experiments[$name] ?? null;
    }

    /**
     * @param string      $experimentName
     * @param string|null $variationName
     *
     * @return Variation|null
     */
    public function getExperimentVariation(string $experimentName, ?string $variationName = null): ?Variation
    {
        $test = $this->getExperiment($experimentName);

        if ($test === null) {
            return null;
        }

        $experimentFacade = new ExperimentFacade($test);

        return $experimentFacade->selectVariation(false, $variationName);
    }

    /**
     * @param mixed[] $groups
     * @param bool    $mustMatchAll
     *
     * @return Experiment[]
     */
    public function getExperiments(array $groups = [], bool $mustMatchAll = false): array
    {
        if (empty($groups)) {
            return $this->experiments;
        }

        $experiments = [];

        foreach ($this->experiments as $key => $experiment) {
            $intersect = array_intersect($groups, $experiment->getGroups());

            if ((!$mustMatchAll && count($intersect) > 0) || ($mustMatchAll && count($intersect) === count($groups))) {
                $experiments[$key] = $experiment;
            }
        }

        return $experiments;
    }

    /**
     * @param Experiment[] $experiments
     */
    public function setExperiments(array $experiments): void
    {
        $this->experiments = $experiments;
    }

    /**
     * @param mixed[] $groups
     * @param bool    $mustMatchAll
     *
     * @return string
     */
    public function toString(array $groups = [], bool $mustMatchAll = false): string
    {
        $experiments = [];

        foreach ($this->getExperiments($groups, $mustMatchAll) as $experiment) {
            $experimentFacade = new ExperimentFacade($experiment);
            $variation = $experimentFacade->selectVariation();

            $experimentName = urlencode($experiment->getName()) ;
            $variationName = urlencode($variation->getName());
            $groupsAsString = count($experiment->getGroups()) > 0 ? ':' . implode(',', $experiment->getGroups()) : '';

            $experiments[] = $experimentName . ':' . $experiment->getSeed() . ':' . (int) $experiment->isEnabled()
                . $groupsAsString . '=' . $variationName . ':' . $variation->getDistribution();
        }

        return implode('|', $experiments);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
