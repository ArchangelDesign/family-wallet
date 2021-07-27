<?php

/**
 * Wrapper around the Unit Of Work
 * php version 7.4
 *
 * @category Command
 * @package  App\Services
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @version  GIT: @1.0.0@
 * @link     https://github.com/ArchangelDesign/family-wallet
 */

namespace App\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Class DatabaseService
 *
 * @category Command
 * @package  App\Services
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     https://github.com/ArchangelDesign/family-wallet
 */
class DatabaseService
{
    const ENTITY_DIRECTORY = 'Entities';
    /**
     * Doctrine EM
     *
     * @var EntityManager
     */
    private $_entityManager;

    /**
     * DatabaseService constructor.
     *
     * @param string $host       db host
     * @param string $username   username
     * @param string $password   password
     * @param int    $port       db port
     * @param string $database   database name or file path
     * @param string $connection db engine to use
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function __construct(
        string $host,
        string $username,
        string $password,
        int $port,
        string $database,
        string $connection = 'pdo_mysql'
    ) {
        $paths = [dirname(__FILE__, 2) . '/' . self::ENTITY_DIRECTORY];
        $params = [
            'driver' => $connection,
            'host' => $host,
            'user' => $username,
            'password' => $password,
            'port' => $port,
            'dbname' => $database
        ];
        if ($connection == 'pdo_sqlite') {
            $params['path'] = $database;
        }
        $config = Setup::createAnnotationMetadataConfiguration(
            $paths,
            $this->_isDevMode()
        );
        $this->_entityManager = EntityManager::create($params, $config);
    }

    /**
     * Returns true if we're not in production
     *
     * @return bool
     */
    private function _isDevMode(): bool
    {
        return env('APP_ENV') != 'prod';
    }

    /**
     * Returns EntityManager
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->_entityManager;
    }

    /**
     * Commits current transaction(s)
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @return void
     */
    public function flush(): void
    {
        $this->_entityManager->flush();
    }

    /**
     * Stores new entity
     *
     * @param mixed $entity given entity
     * @param bool  $flush  commit transaction right away
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist($entity, bool $flush): void
    {
        $this->_entityManager->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    /**
     * Returns true if entity is synchronized
     *
     * @param mixed $entity the entity
     *
     * @return bool
     */
    public function contains($entity)
    {
        return $this->_entityManager->contains($entity);
    }

    /**
     * Removes synchronized entity
     *
     * @param mixed $entity the entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove($entity): void
    {
        $this->_entityManager->remove($entity);
    }
}
