<?php

namespace Tests\Unit;

use App\Entities\Transaction;
use App\Services\QfxTransactionFileParser;
use App\Services\TransactionService;
use Tests\TestCase;

class QfxTransactionParserTest extends TestCase
{
    public function testParsingTransactionOuterBlock()
    {
        /** @var TransactionService $ts */
        $ts = $this->app->make(TransactionService::class);

        $parser = $ts->getParser('qfx');
        $this->assertInstanceOf(QfxTransactionFileParser::class, $parser);
        $parser->loadFile('transaction_history.qbo');
        $this->assertEquals(1, count($parser->getTransactions()));
        $transaction = $parser->getTransactions()[0];
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertStringContainsString('TRANSFER FROM CHK 2926 CONFIRM', $transaction->getName());
        $this->assertStringContainsString('CREDIT', $transaction->getType());
        $this->assertStringContainsString('20210722160000100.000', $transaction->getTransactionId());
        $this->assertEqualsWithDelta(100.0, $transaction->getAmount(), 0.01);
        $this->assertEqualsWithDelta(100.0, $parser->getLedgerBalance()->getBalance(), 0.01);
        $this->assertEquals('2021-07-23 14:42:32', $parser->getLedgerBalance()->getDateOf()->format('Y-m-d H:i:s'));
        $this->assertEquals('2021-07-22 16:00:00', $transaction->getDatePosted()->format('Y-m-d H:i:s'));
    }
}
