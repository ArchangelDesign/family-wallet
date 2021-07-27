<?php

/**
 * Import transactions from a given file(s)
 * php version 7.4
 *
 * @category Command
 * @package  App\Console\Commands
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @version  GIT: @1.0.0@
 * @link     https://github.com/ArchangelDesign/family-wallet
 */

namespace App\Console\Commands;

use App\Entities\Transaction;
use App\Exceptions\AccountNotFound;
use App\Exceptions\FileNotFound;
use App\Exceptions\TransactionDuplicated;
use App\Exceptions\UnhandledTransactionFileFormat;
use App\Services\TransactionService;
use Illuminate\Console\Command;

/**
 * Class ImportTransactions
 *
 * @category Command
 * @package  App\Console\Commands
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     https://github.com/ArchangelDesign/family-wallet
 */
class ImportTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import transactions from a given file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param TransactionService $transactionService transaction service
     *
     * @return int
     */
    public function handle(TransactionService $transactionService)
    {
        $formats = $transactionService->getSupportedFormats();
        $selectedFormat = $this->output->choice(
            'Which format do you want to use?',
            $formats,
            'csv'
        );
        try {
            $parser = $transactionService->getParser($selectedFormat);
        } catch (UnhandledTransactionFileFormat $e) {
            $this->error($e);
            return 128;
        }
        $file = $this->output->ask('provide file path');
        try {
            $parser->loadFile($file);
        } catch (FileNotFound $e) {
            $this->error('File not found');
            return 128;
        }
        $accountIdOrName = $this->output->ask('Account name or ID: ');
        $account = $transactionService->fetchAccountByIdOrName($accountIdOrName);
        $transactions = $parser->getTransactions();
        $ledgerBalance = $parser->getLedgerBalance();
        $this->info('number of transactions: ' . count($transactions));
        $this->info(
            'ledger balance: ' . $ledgerBalance->getBalance()
            . ' as of ' . $ledgerBalance->getDateOf()->format('Y-m-d H:i:s')
        );
        $this->output->table(
            ['type', 'amount'],
            $this->_transactionTable($transactions)
        );

        if (!$this->output->confirm('Import transactions?')) {
            return 0;
        }
        $imported = 0;
        foreach ($transactions as $transaction) {
            try {
                $transactionService->registerTransaction(
                    $account,
                    $transaction,
                    true
                );
                $imported++;
            } catch (AccountNotFound $e) {
                $this->error('Invalid account.');
                return 128;
            } catch (TransactionDuplicated $e) {
                $this->warn('DUPLICATE: ' . $transaction);
            }
        }
        // we are flushing after each insert to filter out duplicates
        //$transactionService->flush();

        $this->output->success($imported . ' transactions imported.');
        return 0;
    }

    /**
     * Creates a table to be displayed in the console
     *
     * @param array $transactions array of transactions
     *
     * @return array
     */
    private function _transactionTable(array $transactions): array
    {
        $result = [];
        foreach ($transactions as $t) {
            $result[] = [
                'type' => $t->getType(),
                'amount' => $t->getAmount()
            ];
        }

        return $result;
    }
}
