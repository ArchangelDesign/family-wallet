<?php

namespace App\Services;

use App\Entities\LedgerBalance;
use App\Entities\Transaction;
use App\Exceptions\FileNotFound;
use App\Exceptions\FileParsingError;

/**
 * Class QfxTransactionFileParser
 *
 * @package App\Services
 * @see     https://www.ofx.net/downloads/OFX%202.2.pdf
 *
 * based on my example file from the bank (my actual transaction history)
 * the file is not in correct XML format as documentation shows,
 * for some reason the file is partially a text file.
 * Regular expressions are used here instead of tokenizer.
 * @TODO:   probably rename this one and create an actual parser that will also fix the file
 */
class QfxTransactionFileParser implements TransactionFileParserInterface
{
    const PATTERN_TRANSACTIONS_OUTER_BLOCK = '/<BANKTRANLIST>[.\s\w<>\[\]\/:,\-#\$]*<\/BANKTRANLIST>/';
    const PATTERN_LEDGER_BALANCE_OUTER_BLOCK = '/<LEDGERBAL>[.\s\w<>\[\]\/:,]*<\/LEDGERBAL>/';
    const PATTERN_TRANSACTION = '/<STMTTRN>[.\w\s<>,:\-\$]*<\/STMTTRN>/';
    private $filePath;

    private $contents;

    private $transactionOuterBlock;

    private $transactions = [];

    private $ledgerBalanceBlock;

    /**
     * @var LedgerBalance
     */
    private $ledgerBalance;

    /**
     * @param  string $filePath
     * @throws FileNotFound
     * @throws FileParsingError
     */
    public function loadFile(string $filePath)
    {
        $this->filePath = $filePath;
        if (!file_exists($filePath)) {
            throw new FileNotFound($filePath);
        }
        // @TODO: support large file
        $this->contents = file_get_contents($filePath);
        $this->contents = str_replace("\r\n", "\n", $this->contents);
        $this->parseFile();
    }

    /**
     * @param  string $buffer
     * @throws FileParsingError
     */
    public function loadFromBuffer(string $buffer)
    {
        $this->contents = $buffer;
        $this->contents = str_replace("\r\n", "\n", $this->contents);
        $this->parseFile();
    }

    /**
     * @throws FileParsingError
     */
    private function parseFile()
    {
        $this->transactions = [];
        $transactionOuterBlock = $this->parseTransactionOuterBlock();
        $transactionBlocks = $this->parseTransactionBlocks($transactionOuterBlock);
        foreach ($transactionBlocks as $block) {
            $this->transactions[] = $this->parseTransaction($block);
        }
        $this->ledgerBalanceBlock = $this->parseLedgerBalanceBlock();
        $this->ledgerBalance = new LedgerBalance();
        $this->ledgerBalance->setBalance($this->parseLedgerBalanceAmount())
            ->setDateOf($this->parseLedgerBalanceDateOf());
    }

    /**
     * @throws FileParsingError
     */
    private function parseTransactionOuterBlock(): string
    {
        $matches = [];
        if (!preg_match(self::PATTERN_TRANSACTIONS_OUTER_BLOCK, $this->contents, $matches)) {
            throw new FileParsingError('Cannot find BANKTRANLIST node in the file.');
        }

        return $matches[0];
    }

    /**
     * @throws FileParsingError
     */
    private function parseLedgerBalanceBlock(): string
    {
        $matches = [];
        if (!preg_match(self::PATTERN_LEDGER_BALANCE_OUTER_BLOCK, $this->contents, $matches)) {
            throw new FileParsingError('Cannot find ledger balance block in the file');
        }

        return $matches[0];
    }

    /**
     * @return mixed
     */
    public function getTransactionOuterBlock()
    {
        return $this->transactionOuterBlock;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function getLedgerBalance(): LedgerBalance
    {
        return $this->ledgerBalance;
    }

    /**
     * Returns an array of transaction block strings
     * for further parsing into Transaction entity
     *
     * @param  string $transactionOuterBlock
     * @return array
     */
    private function parseTransactionBlocks(string $transactionOuterBlock): array
    {
        $matches = [];
        preg_match_all(self::PATTERN_TRANSACTION, $transactionOuterBlock, $matches);

        return $matches[0];
    }

    /**
     * @param  string $block
     * @return Transaction
     * @throws FileParsingError
     */
    private function parseTransaction(string $block): Transaction
    {
        $t = new Transaction();
        $t->setType($this->parseTransactionType($block))
            ->setBalance(null)
            ->setName($this->parseTransactionName($block))
            ->setAmount($this->parseTransactionAmount($block))
            ->setTransactionId($this->parseTransactionId($block))
            ->setDatePosted($this->parseTransactionDate($block));

        return $t;
    }

    /**
     * @param  string $block
     * @return string
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function parseTransactionType(string $block): string
    {
        $pattern = '/<TRNTYPE>\w*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction type');
        }
        return trim(str_replace('<TRNTYPE>', '', $matches[0]));
    }

    /**
     * @param  string $block
     * @return string
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function parseTransactionName(string $block): string
    {
        $pattern = '/<NAME>[\s\b\w\$#\.\-]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction name');
        }
        return trim(str_replace(['<NAME>', "\n"], '', $matches[0]));
    }

    /**
     * @param  string $block
     * @return float
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function parseTransactionAmount(string $block): float
    {
        $pattern = '/<TRNAMT>-?[\s\b\w\.]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction amount');
        }
        return (float)trim(str_replace(['<TRNAMT>', "\n"], '', $matches[0]));
    }

    /**
     * @param  string $block
     * @return string
     * @throws FileParsingError
     * @TODO:  DRY
     */
    private function parseTransactionId(string $block): string
    {
        $pattern = '/<FITID>[\s\b\w\.\-]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction ID');
        }
        return trim(str_replace(['<FITID>', "\n"], '', $matches[0]));
    }

    private function parseTransactionDate(string $block): \DateTime
    {
        $pattern = '/<DTPOSTED>[\s\b\w]*/';
        $matches = [];
        if (!preg_match($pattern, $block, $matches)) {
            throw new FileParsingError('cannot parse transaction ID');
        }
        return $this->qfxDateToDateTime(trim(str_replace(['<DTPOSTED>', "\n"], '', $matches[0])));
    }

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
     * @return float
     * @throws FileParsingError
     */
    private function parseLedgerBalanceAmount(): float
    {
        $pattern = '/<BALAMT>[\s\b\w\.]*/';
        $matches = [];
        if (!preg_match($pattern, $this->ledgerBalanceBlock, $matches)) {
            throw new FileParsingError('cannot parse ledger balance amount');
        }
        return (float)trim(str_replace(['<BALAMT>', "\n"], '', $matches[0]));
    }

    /**
     * @return \DateTime
     * @throws FileParsingError
     */
    private function parseLedgerBalanceDateOf(): \DateTime
    {
        $pattern = '/<DTASOF>[\s\b\w]*/';
        $matches = [];
        if (!preg_match($pattern, $this->ledgerBalanceBlock, $matches)) {
            throw new FileParsingError('cannot parse ledger balance date');
        }
        return $this->qfxDateToDateTime(trim(str_replace(['<DTASOF>', "\n"], '', $matches[0])));
    }


}
