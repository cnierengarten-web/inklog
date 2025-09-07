<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_array($env = @include dirname(__DIR__) . '/.env.local.php')) {
    $_ENV += $env; $_SERVER += $env;
} elseif (!class_exists(Dotenv::class)) {
    throw new LogicException('You need to install symfony/dotenv to load the ".env" files.');
} else {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}
