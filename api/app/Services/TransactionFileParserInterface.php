<?php

namespace App\Services;
use App\Entities\LedgerBalance;
use App\Entities\Transaction;
use App\Exceptions\FileNotFound;

/**
 * Interface TransactionFileParserInterface
 * @package App\Services
 */
interface TransactionFileParserInterface
{
    /**
     * @return Transaction[]
     */
    public function getTransactions(): array;

    /**
     * @return LedgerBalance
     */
    public function getLedgerBalance(): LedgerBalance;

    /**
     * @param string $filePath
     * @return void
     * @throws FileNotFound
     */
    public function loadFile(string $filePath);
}
