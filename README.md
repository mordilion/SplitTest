# SplitTest
It's a small library to get the ability of adding your own split testing (A/B testing) logic to your project.

## Simple usage
```php
<?php

use Mordilion\SplitTest\Container;
use Mordilion\SplitTest\Model\Experiment;

// receive seed cookie if available
$seed = $_COOKIE['seed'] ?? time();

$experimentName = 'an-experiment';

// set up the container with tests
$container = new Container();
$experiment = new Experiment($experimentName, true);
$experiment->addVariation(new Experiment\Variation('a', 50)); // 50%
$experiment->addVariation(new Experiment\Variation('b', 50)); // 50%
$container->addExperiment($experiment);

// select the variation based on the provided seed
$variation = $container->getExperimentVariation($experimentName);

if ($variation === null) {
    // happens only, if the experiment wasn't defined
    throw new \RuntimeException(sprintf('Experiment "%s" does not exist!', $experimentName));
}

if ($variation->getName() === 'b') {
  echo 'Variation B';
} else {
  echo 'Variation A';
}

// set seed cookie for next visit
setcookie('seed', $seed, time() + (3600 * 24 * 30)); // 30 days valid - after 30 days the user is unknown!
```
