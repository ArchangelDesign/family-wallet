<?php

namespace App\Services;
use App\Entities\Account;
use App\Entities\Transaction;
use App\Exceptions\UnhandledTransactionFileFormat;

/**
 * Class TransactionService
 * @package App\Services
 */
class TransactionService
{
    const SUPPORTED_FORMATS = ['qfx', 'csv'];
    /**
     * @var DatabaseService
     */
    private $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * @param string $format
     * @return TransactionFileParserInterface
     * @throws UnhandledTransactionFileFormat
     */
    public function getParser(string $format): TransactionFileParserInterface
    {
        switch ($format)
        {
            case 'qfx': return new QfxTransactionFileParser();
            default:
                throw new UnhandledTransactionFileFormat($format);
        }
    }

    public function registerTransaction(Account $account, Transaction $transaction)
    {

    }

    public function fetchAccountByIdOrName($accountIdOrName): Account
    {
        if (is_numeric($accountIdOrName))
            return $this->fetchAccountById($accountIdOrName);

        return $this->fetchAccountByName($accountIdOrName);
    }

    private function fetchAccountById(int $accountId): Account
    {
        return new Account();
    }

    private function fetchAccountByName($accountIdOrName): Account
    {
        return new Account();
    }
}
