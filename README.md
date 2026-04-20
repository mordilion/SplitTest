# SplitTest

A lightweight PHP library for deterministic A/B and multivariate testing. Assign visitors to experiment variations using seed-based bucketing, keep their choice consistent across requests via cookies or headers, and plug in your own selection strategies whenever the defaults are not enough.

## Features

- **Deterministic bucketing** — the same seed always produces the same variation, so visitors see a consistent experience across sessions and requests.
- **Weighted distributions** — split traffic across any number of variations with arbitrary weights.
- **Pluggable chooser strategies** — ship with `Random`, `Balanced`, `Static` and `Callback` choosers, or implement your own via `ChooserInterface`.
- **Groups** — override the default variations for specific visitor cohorts (logged-in users, regions, feature flags, …).
- **String serialization** — round-trip experiments via cookies or headers with `fromString()` / `__toString()`.
- **Persistence helper** — `Container::deliver()` writes the assignment cookie and header in one call.
- **Selection callbacks** — hook variation assignment into your analytics or tracking pipeline.
- **Small surface area** — four model classes, four choosers, one container.

## Requirements

- PHP **8.1+**
- `ext-iconv`

## Installation

```bash
composer require mordilion/split-test
```

## Quick Start

```php
<?php

use Mordilion\SplitTest\Container;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;

// Reuse the visitor's seed if we have already seen them.
$seed = (int) ($_COOKIE['seed'] ?? time());

$container = new Container($seed);

$experiment = new Experiment('homepage-hero', true);
$experiment->addVariation(new Variation('a', 50));
$experiment->addVariation(new Variation('b', 50));
$container->addExperiment($experiment);

$variation = $container->getExperimentVariation('homepage-hero');

echo $variation->getName() === 'b' ? 'Variation B' : 'Variation A';

// Remember the seed so this visitor keeps their assignment.
setcookie('seed', (string) $seed, time() + 30 * 86400);
```

## Core Concepts

### Container

The `Container` owns the seed and a collection of experiments. The seed is the single source of randomness — combined with each experiment's name it yields a per-experiment seed, so bucket decisions stay reproducible and independent between experiments.

```php
$container = new Container($seed);                 // default RandomChooser
$container = new Container($seed, $customChooser); // or bring your own

$container->addExperiment($experiment);
$variation = $container->getExperimentVariation('homepage-hero');
```

### Experiment

An experiment has a name, an enabled flag, one or more variations, optional groups, and an optional callback that fires on selection.

```php
$experiment = new Experiment(
    name:     'checkout-button',
    enabled:  true,
    groups:   [],         // optional Group[]
    seed:     0,          // optional, normally managed by the Container
    variations: [],       // optional, can also be added via addVariation()
    callback: null,       // optional, called on each selection when enabled
);

$experiment->addVariation(new Variation('control', 80));
$experiment->addVariation(new Variation('variant', 20));
```

### Variation

A named bucket with a weight between `0` and `100`. Weights do not have to sum to 100 — the library normalises them internally — but picking values that sum to 100 makes the intended percentages easy to read in config and logs.

```php
new Variation('control', 75);
new Variation('variant', 25);
```

### Group (optional)

Groups let you serve a different variation set to a specific cohort. When a visitor matches a group, its variations override the experiment's defaults *for that group only*.

```php
use Mordilion\SplitTest\Model\Experiment\Group;

$experiment = new Experiment('checkout-button', true);
$experiment->addVariation(new Variation('control', 50));
$experiment->addVariation(new Variation('variant', 50));

// B2B users always see the enterprise flow.
$experiment->addGroup(new Group('b2b', [
    new Variation('enterprise-flow', 100),
]));

$variation = $container->getExperimentVariation(
    experimentName: 'checkout-button',
    groupName:      'b2b',
);
```

## Chooser Strategies

All choosers implement `Mordilion\SplitTest\Chooser\ChooserInterface`. Pass an instance to `Container` (the default is `RandomChooser`).

### RandomChooser (default)

Seeded `mt_rand` weighted by variation distribution — deterministic for a given seed.

```php
use Mordilion\SplitTest\Chooser\RandomChooser;

$container = new Container($seed, new RandomChooser());
```

### BalancedChooser

Deterministic percentage-based bucketing. Splits the `1..100` range according to the cumulative share of each variation — useful when you want predictable bucket sizes instead of a random draw.

```php
use Mordilion\SplitTest\Chooser\BalancedChooser;

$container = new Container($seed, new BalancedChooser());
```

### StaticChooser

Always picks the same variation — handy for tests, `?forceVariation=` query flags, or admin overrides.

```php
use Mordilion\SplitTest\Chooser\StaticChooser;

// By name …
$container = new Container($seed, new StaticChooser('variant'));

// … or by positional index.
$container = new Container($seed, new StaticChooser(1));
```

### CallbackChooser

Delegates selection to your own callable — connect a feature-flag service, ML model, or any custom logic.

```php
use Mordilion\SplitTest\Chooser\CallbackChooser;
use Mordilion\SplitTest\Model\Experiment;

$chooser = new CallbackChooser(function (Experiment $experiment, array $variations) use ($user) {
    return $user->isBeta() ? $variations['variant'] : $variations['control'];
});

$container = new Container($seed, $chooser);
```

### Writing a custom chooser

```php
use Mordilion\SplitTest\Chooser\ChooserInterface;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Variation;

final class TimeOfDayChooser implements ChooserInterface
{
    public function choose(Experiment $experiment, array $variations): ?Variation
    {
        $key = ((int) date('G')) < 12 ? 'morning' : 'evening';

        return $variations[$key] ?? null;
    }
}
```

## Selection Callbacks

Attach a callback to an experiment to fire analytics or tracking when a visitor is assigned. Callbacks only run while the experiment is enabled.

```php
$experiment = new Experiment(
    name:     'pricing-page',
    enabled:  true,
    callback: static function (Experiment $experiment, Variation $variation): void {
        track('experiment.assigned', [
            'experiment' => $experiment->getName(),
            'variation'  => $variation->getName(),
        ]);
    },
);
```

## Persistence: cookies and headers

`Container::deliver()` serialises the current assignments and writes them as both a cookie and a response header, so downstream services (CDN, logs, analytics) can see which variations the visitor was assigned to.

```php
$container->deliver(
    cookie: 'SplitTests',
    header: 'X-Split-Tests',
);
```

On the next request, restore the container from the cookie:

```php
$container = Container::fromString($_COOKIE['SplitTests'] ?? '', $seed);
```

### Serialization format

Each experiment serializes to:

```
name:seed:enabled[:groups]=variation:weight[,variation:weight]...
```

Multiple experiments are pipe-separated:

```
homepage-hero:4711:1=a:50,b:50|checkout-button:815:1:b2b=enterprise-flow:100
```

Pass such a string back to `Container::fromString()` or `Experiment::fromString()` to reconstruct the objects.

## Filtering experiments by group

```php
// Experiments matching at least one of the given groups.
$experiments = $container->getExperiments(['b2b']);

// Experiments that match *all* of the given groups.
$experiments = $container->getExperiments(['b2b', 'europe'], mustMatchAll: true);
```

## Putting it all together

```php
use Mordilion\SplitTest\Chooser\BalancedChooser;
use Mordilion\SplitTest\Container;
use Mordilion\SplitTest\Model\Experiment;
use Mordilion\SplitTest\Model\Experiment\Group;
use Mordilion\SplitTest\Model\Experiment\Variation;

$seed = (int) ($_COOKIE['seed'] ?? random_int(1, PHP_INT_MAX));

$container = new Container($seed, new BalancedChooser());

$hero = new Experiment('homepage-hero', true);
$hero->addVariation(new Variation('a', 50));
$hero->addVariation(new Variation('b', 50));
$container->addExperiment($hero);

$cta = new Experiment('checkout-cta', true);
$cta->addVariation(new Variation('control', 50));
$cta->addVariation(new Variation('variant', 50));
$cta->addGroup(new Group('b2b', [new Variation('enterprise', 100)]));
$container->addExperiment($cta);

$heroVariation = $container->getExperimentVariation('homepage-hero');
$ctaVariation  = $container->getExperimentVariation('checkout-cta', groupName: $user->segment());

$container->deliver();
setcookie('seed', (string) $seed, time() + 30 * 86400);
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

Released under the [MIT License](LICENSE).
