<?php

use App\Services\DatabaseService;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Illuminate\Contracts\Console\Kernel;

require_once 'vendor/autoload.php';

$app = require __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

$app->make(Kernel::class)->bootstrap();
/** @var DatabaseService $dbService */
$dbService = $app->make(DatabaseService::class);

return ConsoleRunner::createHelperSet($dbService->getEntityManager());
