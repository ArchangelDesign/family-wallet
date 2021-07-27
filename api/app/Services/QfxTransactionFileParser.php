<?php

/**
 * Qfx file parser
 * php version 7.4
 *
 * @category Service
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
use App\Exceptions\FileParsingError;

/**
 * Class QfxTransactionFileParser
 *
 * @category Command
 * @package  App\Console\Commands
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     https://github.com/ArchangelDesign/family-wallet
 * @see      https://www.ofx.net/downloads/OFX%202.2.pdf
 *
 * based on my example file from the bank (my actual transaction history)
 * the file is not in correct XML format as documentation shows,
 * for some reason the file is partially a text file.
 * Regular expressions are used here instead of tokenizer.
 * @TODO:    probably rename this one and create
 * an actual parser that will also fix the file
 */
class QfxTransactionFileParser implements TransactionFileParserInterface
{
    const PATTERN_TRANSACTIONS_OUTER_BLOCK
        = '/<BANKTRANLIST>[.\s\w<>\[\]\/:,\-#\$]*<\/BANKTRANLIST>/';
    const PATTERN_LEDGER_BALANCE_OUTER_BLOCK
        = '/<LEDGERBAL>[.\s\w<>\[\]\/:,]*<\/LEDGERBAL>/';
    const PATTERN_TRANSACTION = '/<STMTTRN>[.\w\s<>,:\-\$]*<\/STMTTRN>/';
    /**
     * Path to the source file
     *
     * @var string|null
     */
    private $_filePath;

    /**
     * File contents
     *
     * @var string|null
     */
    private $_contents;

    /**
     * Raw XML of transaction block
     *
     * @var string
     */
    private $_transactionOuterBlock;

    /**
     * Parsed transactions
     *
     * @var array
     */
    private $_transactions = [];

    /**
     * Raw XML of ledger balance
     *
     * @var string
     */
    private $_ledgerBalanceBlock;

    /**
     * Parsed ledger balance entity
     *
     * @var LedgerBalance
     */
    private $_ledgerBalance;

    /**
     * Performs file parsing
     *
     * @param string $filePath path to the file
     *
     * @return void
     *
     * @throws FileNotFound
     * @throws FileParsingError
     */
    public function loadFile(string $filePath)
    {
        $this->_filePath = $filePath;
        if (!file_exists($filePath)) {
            throw new FileNotFound($filePath);
        }
        // @TODO: support large file
        $this->_contents = file_get_contents($filePath);
        $this->_contents = str_replace("\r\n", "\n", $this->_contents);
        $this->_parseFile();
    }

    /**
     * Bypasses file loading and uses given buffer as contents
     *
     * @param string $buffer file contents
     *
     * @return void
     *
     * @throws FileParsingError
     */
    public function loadFromBuffer(string $buffer)
    {
        $this->_contents = $buffer;
        $this->_contents = str_replace("\r\n", "\n", $this->_contents);
        $this->_parseFile();
    }

    /**
     * Performs actual parsing
     *
     * @return void
     *
     * @throws FileParsingError
     */
    private function _parseFile()
    {
        $this->_transactions = [];
        $transactionOuterBlock = $this->_parseTransactionOuterBlock();
        $transactionBlocks = $this->_parseTransactionBlocks($transactionOuterBlock);
        foreach ($transactionBlocks as $block) {
            $this->_transactions[] = $this->_parseTransaction($block);
        }
        $this->_ledgerBalanceBlock = $this->_parseLedgerBalanceBlock();
        $this->_ledgerBalance = new LedgerBalance();
        $this->_ledgerBalance->setBalance($this->_parseLedgerBalanceAmount())
            ->setDateOf($this->_parseLedgerBalanceDateOf());
    }

    /**
     * Returns the transaction block
     *
     * @return string
     *
     * @throws FileParsingError
     */
    private function _parseTransactionOuterBlock(): string
    {
        $matches = [];
        if (!preg_match(
            self::PATTERN_TRANSACTIONS_OUTER_BLOCK,
            $this->_contents,
            $matches
        )
        ) {
            throw new FileParsingError('Cannot find BANKTRANLIST node in the file.');
        }

        return $matches[0];
    }

    /**
     * Returns ledger balance block
     *
     * @return string
     *
     * @throws FileParsingError
     */
    private function _parseLedgerBalanceBlock(): string
    {
        $matches = [];
        if (!preg_match(
            self::PATTERN_LEDGER_BALANCE_OUTER_BLOCK,
            $this->_contents,
            $matches
        )
        ) {
            throw new FileParsingError(
                'Cannot find ledger balance block in the file'
            );
        }

        return $matches[0];
    }

    /**
     * Returns transaction outer block
     *
     * @return string
     */
    public function getTransactionOuterBlock()
    {
        return $this->_transactionOuterBlock;
    }

    /**
     * Returns the list of parsed transactions
     *
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->_transactions;
    }

    /**
     * Returns parsed ledger balance
     *
     * @return LedgerBalance
     */
    public function getLedgerBalance(): LedgerBalance
    {
        return $this->_ledgerBalance;
    }

    /**
     * Returns an array of transaction block strings
     * for further parsing into Transaction entity
     *
     * @param string $transactionOuterBlock raw transaction block
     *
     * @return array
     */
    private function _parseTransactionBlocks(string $transactionOuterBlock): array
    {
        $matches = [];
        preg_match_all(self::PATTERN_TRANSACTION, $transactionOuterBlock, $matches);

        return $matches[0];
    }

    /**
     * Converts raw data into Transaction entity
     *
     * @param string $block raw block
     *
     * @return Transaction
     *
     * @throws FileParsingError
     */
    private function _parseTransaction(string $block): Transaction
    {
        $t = new Transaction();
        $t->setType($this->_parseTransactionType($block))
            ->setBalance(null)
            ->setName($this->_parseTransactionName($block))
            ->setAmount($this->_parseTransactionAmount($block))
            ->setTransactionId($this->_parseTransactionId($block))
            ->setDatePosted($this->_parseTransactionDate($block));

        return $t;
    }

    /**
     * Returns transaction type
     *
     * @param string $block raw block
     *
     * @return string
     *
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function _parseTransactionType(string $block): string
    {
        $pattern = '/<TRNTYPE>\w*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction type');
        }
        return trim(str_replace('<TRNTYPE>', '', $matches[0]));
    }

    /**
     * Returns transaction name
     *
     * @param string $block raw block
     *
     * @return string
     *
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function _parseTransactionName(string $block): string
    {
        $pattern = '/<NAME>[\s\b\w\$#\.\-]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction name');
        }
        return trim(str_replace(['<NAME>', "\n"], '', $matches[0]));
    }

    /**
     * Returns the amount of transaction
     *
     * @param string $block raw block
     *
     * @return float
     *
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function _parseTransactionAmount(string $block): float
    {
        $pattern = '/<TRNAMT>-?[\s\b\w\.]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction amount');
        }
        return (float)trim(str_replace(['<TRNAMT>', "\n"], '', $matches[0]));
    }

    /**
     * Returns the transaction ID
     *
     * @param string $block raw block
     *
     * @return string
     *
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function _parseTransactionId(string $block): string
    {
        $pattern = '/<FITID>[\s\b\w\.\-]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction ID');
        }
        return trim(str_replace(['<FITID>', "\n"], '', $matches[0]));
    }

    /**
     * REturns transaction date
     *
     * @param string $block raw block
     *
     * @return \DateTime
     *
     * @throws FileParsingError
     */
    private function _parseTransactionDate(string $block): \DateTime
    {
        $pattern = '/<DTPOSTED>[\s\b\w]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction ID');
        }
        return $this->qfxDateToDateTime(
            trim(str_replace(['<DTPOSTED>', "\n"], '', $matches[0]))
        );
    }

    /**
     * Converts QFX date to DateTime
     *
     * @param string $qfxDate date in qfx format
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    public function qfxDateToDateTime(string $qfxDate): \DateTime
    {
        $year = substr($qfxDate, 0, 4);
        $month = substr($qfxDate, 4, 2);
        $day = substr($qfxDate, 6, 2);
        $hour = substr($qfxDate, 8, 2);
        $minute = substr($qfxDate, 10, 2);
        $second = substr($qfxDate, 12, 2);

        return new \DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}");
    }

    /**
     * Returns the amount from ledger balance block
     *
     * @return float
     *
     * @throws FileParsingError
     */
    private function _parseLedgerBalanceAmount(): float
    {
        $pattern = '/<BALAMT>[\s\b\w\.]*/';
        $matches = [];
        if (!preg_match($pattern, $this->_ledgerBalanceBlock, $matches)) {
            throw new FileParsingError('cannot parse ledger balance amount');
        }
        return (float)trim(str_replace(['<BALAMT>', "\n"], '', $matches[0]));
    }

    /**
     * Returns the date from ledger balance block
     *
     * @return \DateTime
     *
     * @throws FileParsingError
     */
    private function _parseLedgerBalanceDateOf(): \DateTime
    {
        $pattern = '/<DTASOF>[\s\b\w]*/';
        $matches = [];
        if (!preg_match($pattern, $this->_ledgerBalanceBlock, $matches)) {
            throw new FileParsingError('cannot parse ledger balance date');
        }
        return $this->qfxDateToDateTime(
            trim(str_replace(['<DTASOF>', "\n"], '', $matches[0]))
        );
    }


}
