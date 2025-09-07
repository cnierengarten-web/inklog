<?php declare(strict_types=1);

use App\Kernel;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

/**
 * Charge la config d'env comme le fait Symfony :
 * - si config/bootstrap.php existe, on l'utilise
 * - sinon, on charge .env via Dotenv->bootEnv()
 */
if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Boot kernel de test
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? 1));
$kernel->boot();

// (Re)création du schéma Doctrine pour la DB de test
$em = $kernel->getContainer()->get('doctrine')->getManager();
$tool = new SchemaTool($em);
$metadata = $em->getMetadataFactory()->getAllMetadata();

$tool->dropDatabase();
if ($metadata) {
    $tool->createSchema($metadata);
}

$kernel->shutdown();
