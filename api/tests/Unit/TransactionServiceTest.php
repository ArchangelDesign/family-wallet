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
    public function testDuplicatedTransactions()
    {
        /** @var TransactionService $xService */
        $xService = $this->app->make(TransactionService::class);
var_dump(DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;"));
        $xService->registerAccount('unit test', 1);
        $account = $xService->fetchAccountByIdOrName(1);
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
        }
    }
}
