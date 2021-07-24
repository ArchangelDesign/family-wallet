<?php

namespace Tests\Unit;

use App\Entities\Transaction;
use App\Services\QfxTransactionFileParser;
use App\Services\TransactionService;
use Tests\TestCase;

class QfxTransactionParserTest extends TestCase
{
    public function testParsingSingleTransactionAndLedgerBalance()
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
        $this->assertEquals('20210722160000-100.000', $transaction->getTransactionId());
        $this->assertEqualsWithDelta(100.0, $transaction->getAmount(), 0.01);
        $this->assertEqualsWithDelta(100.0, $parser->getLedgerBalance()->getBalance(), 0.01);
        $this->assertEquals('2021-07-23 14:42:32', $parser->getLedgerBalance()->getDateOf()->format('Y-m-d H:i:s'));
        $this->assertEquals('2021-07-22 16:00:00', $transaction->getDatePosted()->format('Y-m-d H:i:s'));
    }

    public function testParsingMultipleTransactions()
    {
        /** @var TransactionService $ts */
        $ts = $this->app->make(TransactionService::class);

        $parser = $ts->getParser('qfx');
        $this->assertInstanceOf(QfxTransactionFileParser::class, $parser);
        $parser->loadFile('C:\Users\archa\Downloads\test.qfx');
        $this->assertEquals(107, count($parser->getTransactions()));
        $transaction = $parser->getTransactions()[0];
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testDateTimeConversion()
    {
        $qfxDate = '20210723235959.000[0:GMT]';
        /** @var QfxTransactionFileParser $parser */
        $parser = $this->app->make(QfxTransactionFileParser::class);
        $d = $parser->qfxDateToDateTime($qfxDate);
        $this->assertEquals('2021-07-23 23:59:59', $d->format('Y-m-d H:i:s'));
    }
}
