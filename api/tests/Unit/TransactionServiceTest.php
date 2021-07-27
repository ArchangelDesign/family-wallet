<?php

namespace Tests\Unit;

use App\Entities\Transaction;
use App\Exceptions\TransactionDuplicated;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Class TransactionServiceTest
 * @package Tests\Unit
 */
class TransactionServiceTest extends TestCase
{
    private function getService(): TransactionService
    {
        return $this->app->make(TransactionService::class);
    }

    public function testDuplicatedTransactions()
    {
        $xService = $this->getService();
        $account = $xService->registerAccount('unit test', 1);
        $transaction = new Transaction();
        $d = new \DateTime();
        $transaction->setDatePosted($d)
            ->setType('DEBIT')
            ->setName('unit test')
            ->setAmount(0.1)
            ->setTransactionId('UNIT.TEST-1');
        $xService->registerTransaction($account, $transaction);
        try {
            $xService->registerTransaction($account, $transaction);
            $this->assertTrue(false, 'registerTransaction method did not throw for duplicated transaction');
        } catch (TransactionDuplicated $e) {
            $this->assertTrue(true);
        } finally {
            $xService->deleteTransaction($transaction);
            $xService->deleteAccount($account);
        }
    }

    public function testRegisteringAccounts()
    {
        $service = $this->getService();
        $accountName = 'unit-test-' . date('His');
        try {
           $acc = $service->registerAccount($accountName);
           $this->assertIsNumeric($acc->getId());
           $this->assertTrue($acc->getId() > 0);
        } finally {
            if (isset($acc))
                $service->deleteAccount($acc);
        }
    }
}
