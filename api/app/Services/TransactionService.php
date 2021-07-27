<?php

namespace App\Services;
use App\Entities\Account;
use App\Entities\Transaction;
use App\Exceptions\AccountAlreadyExists;
use App\Exceptions\AccountNotFound;
use App\Exceptions\MultipleAccountsMatched;
use App\Exceptions\TransactionDuplicated;
use App\Exceptions\TransactionDuplicateFound;
use App\Exceptions\TransactionNotFound;
use App\Exceptions\TransactionServicePanic;
use App\Exceptions\UnhandledTransactionFileFormat;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Illuminate\Database\MultipleRecordsFoundException;

/**
 * Class TransactionService
 *
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
     * @param  string $format
     * @return TransactionFileParserInterface
     * @throws UnhandledTransactionFileFormat
     */
    public function getParser(string $format): TransactionFileParserInterface
    {
        switch ($format)
        {
        case 'qfx': 
            return new QfxTransactionFileParser();
        default:
            throw new UnhandledTransactionFileFormat($format);
        }
    }

    /**
     * @param  Account     $account
     * @param  Transaction $transaction
     * @param  bool        $flush
     * @throws AccountNotFound
     * @throws TransactionDuplicated
     */
    public function registerTransaction(Account $account, Transaction $transaction, $flush = true)
    {
        if (empty($account->getId())) {
            throw new AccountNotFound();
        }
        if (!$this->db->contains($account)) {
            throw new AccountNotFound('Entity is not synchronized with Entity Manager');
        }
        try {
            $this->fetchTransactionByValues($transaction);
            throw new TransactionDuplicated($transaction->getTransactionId());
        } catch (TransactionNotFound | TransactionDuplicateFound $e) {
        }
        $transaction->setAccount($account);
        $this->db->persist($transaction, $flush);
    }

    public function fetchAccountByIdOrName($accountIdOrName): Account
    {
        if (is_numeric($accountIdOrName)) {
            return $this->fetchAccountById($accountIdOrName);
        }

        return $this->fetchAccountByName($accountIdOrName);
    }

    /**
     * @param  int $accountId
     * @return Account
     * @throws AccountNotFound
     * @throws TransactionServicePanic
     */
    private function fetchAccountById(int $accountId): Account
    {
        try {
            return $this->db->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from('App\Entities\Account', 'a')
                ->where('a.id = :id')
                ->setParameter('id', $accountId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new AccountNotFound('id: ' . $accountId);
        } catch (NonUniqueResultException $e) {
            throw new TransactionServicePanic('More than one account found for id ' . $accountId);
        }
    }

    /**
     * @param  string $accountName
     * @return Account
     * @throws AccountNotFound
     * @throws MultipleAccountsMatched
     */
    private function fetchAccountByName(string $accountName): Account
    {
        try {
            return $this->db->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from('App\Entities\Account', 'a')
                ->where('a.name = :name')
                ->setParameter('name', $accountName)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new AccountNotFound($accountName);
        } catch (NonUniqueFieldNameException $e) {
            throw new MultipleAccountsMatched('More than one account matches ' . $accountName);
        }
    }

    public function flush()
    {
        $this->db->flush();
    }

    /**
     * @param  Transaction $transaction
     * @return Transaction
     * @throws TransactionNotFound
     * @throws TransactionDuplicateFound
     */
    private function fetchTransactionByValues(Transaction $transaction): Transaction
    {
        try {
            return $this->db->getEntityManager()->createQueryBuilder()
                ->select('t')
                ->from('App\Entities\Transaction', 't')
                ->where('t.transactionId = :id')
                ->andWhere('t.name = :name')
                ->andWhere('t.amount = :amount')
                ->andWhere('t.datePosted = :datePosted')
                ->andWhere('t.type = :type')
                ->setParameter('id', $transaction->getTransactionId())
                ->setParameter('name', $transaction->getName())
                ->setParameter('amount', $transaction->getAmount())
                ->setParameter('datePosted', $transaction->getDatePosted())
                ->setParameter('type', $transaction->getType())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new TransactionNotFound($transaction);
        } catch (NonUniqueResultException $e) {
            throw new TransactionDuplicateFound($transaction->toString());
        }
    }

    /**
     * For unit testing purposes only
     *
     * @internal
     * @param    Transaction $transaction
     * @throws   \Doctrine\ORM\ORMException
     */
    public function deleteTransaction(Transaction $transaction)
    {
        $this->db->getEntityManager()->remove($transaction);
        $this->db->flush();
    }

    /**
     * @param  string   $name
     * @param  int|null $id
     * @return Account
     * @throws AccountAlreadyExists
     * @throws TransactionServicePanic
     */
    public function registerAccount(string $name, int $id = null)
    {
        try {
            $this->fetchAccountByName($name);
            throw new AccountAlreadyExists($name);
        } catch (AccountNotFound $e) {
        } catch (MultipleAccountsMatched $e) {
        }
        try {
            if (!is_null($id)) {
                $this->fetchAccountById($id);
                throw new AccountAlreadyExists('id: ' . $id);
            }
        } catch (AccountNotFound $e) {
        }
        $acc = new Account();
        $acc->setId($id)
            ->setName($name);
        $this->db->persist($acc, true);

        return $acc;
    }

    /**
     * @param    Account $acc
     * @throws   AccountNotFound
     * @internal
     */
    public function deleteAccount(Account $acc)
    {
        if (!$this->db->contains($acc)) {
            throw new AccountNotFound('Entity is not synchronized.');
        }

        $this->db->remove($acc);
        $this->db->flush();
    }
}
