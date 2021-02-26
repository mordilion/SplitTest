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

use Mordilion\SplitTest\Chooser\BalancedChooser;
use Mordilion\SplitTest\Chooser\ChooserInterface;
use Mordilion\SplitTest\Facade\Experiment as ExperimentFacade;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Group;
use Mordilion\SplitTest\Model\Experiment\Variation;

/**
 * @author Henning Huncke <mordilion@gmx.de>
 */
class Container
{
    /**
     * @var ChooserInterface
     */
    private $chooser;

    /**
     * @var Experiment[]
     */
    private $experiments = [];

    /**
     * @var int
     */
    private $seed;


    /**
     * Container constructor.
     *
     * @param int                   $seed
     * @param ChooserInterface|null $chooser
     */
    public function __construct(int $seed = 0, ?ChooserInterface $chooser = null)
    {
        $this->chooser = $chooser ?? new BalancedChooser();

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
        $deliveryString = $this->getSelectionString();

        if (!empty($cookie)) {
            setcookie($cookie, $deliveryString);
        }

        if (!empty($header)) {
            header($header . ': ' . $deliveryString);
        }
    }

    /**
     * @return ChooserInterface
     */
    public function getChooser(): ChooserInterface
    {
        return $this->chooser;
    }

    /**
     * @param ChooserInterface $chooser
     */
    public function setChooser(ChooserInterface $chooser): void
    {
        $this->chooser = $chooser;
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

        $experimentFacade = new ExperimentFacade($experiment, $this->getChooser());

        if ($this->seed !== 0) {
            $testSeed = $experimentFacade->generateSeed($this->seed);
            $experiment->setSeed($testSeed);
        }

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
     * @param string $experimentName
     * @param bool   $force
     * @param string $variationName
     * @param string $groupName
     *
     * @return Variation|null
     */
    public function getExperimentVariation(string $experimentName, bool $force = false, string $variationName = '', string $groupName = ''): ?Variation
    {
        $experiment = $this->getExperiment($experimentName);

        if ($experiment === null) {
            return null;
        }

        $experimentFacade = new ExperimentFacade($experiment, $this->getChooser());

        return $experimentFacade->selectVariation($groupName, $variationName, $force);
    }

    /**
     * @param string[] $groups
     * @param bool     $mustMatchAll
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
            $experimentGroups = $experiment->getGroups($groups);

            if ((!$mustMatchAll && count($experimentGroups) > 0) || ($mustMatchAll && count($experimentGroups) === count($groups))) {
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
            $experimentFacade = new ExperimentFacade($test, $this->getChooser());
            $testSeed = $experimentFacade->generateSeed($this->seed);

            $test->setSeed($testSeed);
        }
    }

    /**
     * @param mixed[] $groups
     * @param bool    $mustMatchAll
     *
     * @return string
     */
    public function getSelectionString(array $groups = [], bool $mustMatchAll = false): string
    {
        $experiments = [];
        $groupName = count($groups) > 0 ? (string) reset($groups) : '';

        foreach ($this->getExperiments($groups, $mustMatchAll) as $experiment) {
            $experimentFacade = new ExperimentFacade($experiment, $this->getChooser());
            $variation = $experimentFacade->selectVariation($groupName);

            $experimentName = urlencode($experiment->getName()) ;
            $variationName = urlencode($variation->getName());
            $groupsAsString = array_map(static function (Group $group) {
                return $group->getName();
            }, $experiment->getGroups());

            $groupsAsString = count($groupsAsString) > 0 ? ':' . implode(',', $groupsAsString) : '';

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
        return implode('|', $this->getExperiments());
    }
}
