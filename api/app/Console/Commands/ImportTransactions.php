<?php

namespace App\Console\Commands;

use App\Entities\Transaction;
use App\Exceptions\AccountNotFound;
use App\Exceptions\FileNotFound;
use App\Exceptions\TransactionDuplicated;
use App\Exceptions\UnhandledTransactionFileFormat;
use App\Services\TransactionService;
use Illuminate\Console\Command;

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
     * @param TransactionService $transactionService
     * @return int
     * @throws \App\Exceptions\FileNotFound
     */
    public function handle(TransactionService $transactionService)
    {
        $formats = $transactionService->getSupportedFormats();
        $selectedFormat = $this->output->choice('Which format do you want to use?', $formats, 'csv');
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
        $this->info('ledger balance: ' . $ledgerBalance->getBalance() . ' as of ' . $ledgerBalance->getDateOf()->format('Y-m-d H:i:s'));
        $this->output->table(['type', 'amount'], $this->transactionTable($transactions));

        if (!$this->output->confirm('Import transactions?'))
            return 0;

        foreach ($transactions as $transaction) {
            try {
                $transactionService->registerTransaction($account, $transaction, false);
            } catch (AccountNotFound $e) {
                $this->error('Invalid account.');
                return 128;
            } catch (TransactionDuplicated $e) {
                $this->warn('DUPLICATE: ' . $transaction);
            }
        }

        $transactionService->flush();

        $this->output->success('Transactions imported.');
        return 0;
    }

    private function transactionTable(array $transactions): array
    {
        $result = [];
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $result[] = [
                'type' => $t->getType(),
                'amount' => $t->getAmount()
            ];
        }

        return $result;
    }
}
