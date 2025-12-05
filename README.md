# Prometheus metrics for PHP

Prometheus metrics collector and exporter for PHP. Storage is abstracted to allow better integration with your infrastructure.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-build-status]][link-build-status]

## Installation

Install it as [Composer](https://getcomposer.org) dependency:

```sh
composer require erickskrauch/prometheus
```

## Usage

```php
use ErickSkrauch\Prometheus\CollectorRegistry;
use ErickSkrauch\Prometheus\NamespacedRegistry;
use ErickSkrauch\Prometheus\Storage\Redis;
use ErickSkrauch\Prometheus\Storage\Redis\PHPRedis;

$redisClientImplementation = PHPRedis::create(['host' => 'localhost', 'port' => 6379]);
$storage = new Redis($redisClientImplementation);
$registry = new CollectorRegistry($storage);
// Might be useful to prefix all metrics in your app
// $registry = new NamespacedRegistry('github', $registry);

$registry->counter('readme_readers_total')->inc()
$registry->gauge('stars_count', 'How many people read this code example and helped promote the project')->set(1_000);
$registry->histogram('delay_before_decision_to_install_seconds', [1, 5, 10, 20, 60] ['trafficSource'])->observe(5.78, ['google']);
```

In addition to the [PHPRedis](https://github.com/phpredis/phpredis) extension, it also offers an implementation for the [Predis](https://github.com/predis/predis) library. You can easily write your own adapter for any Redis client by implementing the `\ErickSkrauch\Prometheus\Storage\Redis\RedisClient` interface.

An in-memory storage driver is also available via `\ErickSkrauch\Prometheus\Storage\InMemory`.

To expose metrics, register a route in your framework, such as this example for Symfony:

```php
use ErickSkrauch\Prometheus\RegistryInterface;
use ErickSkrauch\Prometheus\Renderer\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController
{
    #[Route('/metrics', name: 'prometheus_metrics')]
    public function expose(RegistryInterface $registry): Response
    {
        $renderer = new RenderTextFormat();

        return new Response(
            $renderer->render($registry->collectMetrics()),
            headers: ['Content-Type' => RenderTextFormat::MIME_TYPE],
        );
    }
}
```

[ico-version]: https://img.shields.io/packagist/v/erickskrauch/prometheus-php.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-green.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/erickskrauch/prometheus-php.svg?style=flat-square
[ico-build-status]: https://img.shields.io/github/actions/workflow/status/erickskrauch/prometheus-php/ci.yml?branch=master&style=flat-square

[link-packagist]: https://packagist.org/packages/erickskrauch/prometheus-php
[link-downloads]: https://packagist.org/packages/erickskrauch/prometheus-php/stats
[link-build-status]: https://github.com/erickskrauch/prometheus-php/actions
