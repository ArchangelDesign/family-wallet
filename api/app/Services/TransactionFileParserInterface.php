<?php

/**
 * Contract of file parsers that import transactions
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
use App\Entities\LedgerBalance;
use App\Entities\Transaction;
use App\Exceptions\FileNotFound;

/**
 * Class DatabaseService
 *
 * @category Contract
 * @package  App\Services
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     https://github.com/ArchangelDesign/family-wallet
 */
interface TransactionFileParserInterface
{
    /**
     * Returns the list of transactions imported from the file
     *
     * @return Transaction[]
     */
    public function getTransactions(): array;

    /**
     * Returns the ledger balance imported from the file (if applicable)
     *
     * @return LedgerBalance
     */
    public function getLedgerBalance(): LedgerBalance;

    /**
     * Performs the parsing process
     *
     * @param string $filePath path to the file
     *
     * @return void
     *
     * @throws FileNotFound
     */
    public function loadFile(string $filePath);
}
