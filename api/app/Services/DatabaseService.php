<?php

namespace App\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Class DatabaseService
 * @package App\Services
 */
class DatabaseService
{
    const ENTITY_DIRECTORY = 'Entities';
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        string $host,
        string $username,
        string $password,
        int $port,
        string $database,
        string $connection = 'pdo_mysql'
    )
    {
        $paths = [dirname(__FILE__, 2) . '/' . self::ENTITY_DIRECTORY];
        $params = [
            'driver' => $connection,
            'host' => $host,
            'user' => $username,
            'password' => $password,
            'port' => $port,
            'dbname' => $database
        ];
        $config = Setup::createAnnotationMetadataConfiguration($paths, $this->isDevMode());
        $this->entityManager = EntityManager::create($params, $config);
    }

    private function isDevMode(): bool
    {
        return env('APP_ENV') != 'prod';
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function flush()
    {
        $this->entityManager->flush();
    }

    public function persist($entity, bool $flush)
    {
        $this->entityManager->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function contains($entity)
    {
        return $this->entityManager->contains($entity);
    }
}
