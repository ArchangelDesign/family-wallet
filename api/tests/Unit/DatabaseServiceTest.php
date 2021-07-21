<?php

namespace Tests\Unit;

use App\Entities\Transaction;
use App\Services\DatabaseService;
use Tests\TestCase;

class DatabaseServiceTest extends TestCase
{
    public function testConnectionWithLocalTestInstance()
    {
        $db = new DatabaseService(
            '127.0.0.1',
            'local_test',
            '',
            3308,
            'local_test'
        );

        $em = $db->getEntityManager();
        $t = new Transaction();
        $em->persist($t);
        $em->flush();
        $this->assertIsNumeric($t->getId());
        $this->assertTrue($t->getId() > 0);
    }
}
