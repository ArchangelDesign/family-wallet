<?php

/**
 * Domain service. Central point where all financial operations are performed
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
 * @category Service
 * @package  App\Services
 * @author   Raff <email@domain.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     https://github.com/ArchangelDesign/family-wallet
 */
class TransactionService
{
    const SUPPORTED_FORMATS = ['qfx', 'csv'];
    /**
     * Gate to persistence
     *
     * @var DatabaseService
     */
    private $_db;

    /**
     * TransactionService constructor.
     *
     * @param DatabaseService $db database service
     */
    public function __construct(DatabaseService $db)
    {
        $this->_db = $db;
    }

    /**
     * Returns an array of strings representing compatible file formats
     *
     * @return string[]
     */
    public function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * Returns an instance of the parser for given file format
     *
     * @param string $format format
     *
     * @return TransactionFileParserInterface
     *
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
     * Stores transaction in the persistence. Transactions are validated.
     *
     * @param Account     $account     synchronized Account entity
     * @param Transaction $transaction raw (detached) Transaction entity.
     * @param bool        $flush       commit transaction
     *
     * @return void
     *
     * @throws AccountNotFound
     * @throws TransactionDuplicated
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerTransaction(
        Account $account,
        Transaction $transaction,
        $flush = true
    ) {
        if (empty($account->getId())) {
            throw new AccountNotFound();
        }
        if (!$this->_db->contains($account)) {
            throw new AccountNotFound(
                'Entity is not synchronized with Entity Manager'
            );
        }
        try {
            $this->_fetchTransactionByValues($transaction);
            throw new TransactionDuplicated($transaction->getTransactionId());
        } catch (TransactionNotFound | TransactionDuplicateFound $e) {
        }
        $transaction->setAccount($account);
        $this->_db->persist($transaction, $flush);
    }

    /**
     * Returns Account entity by given name or ID (primary key from `accounts` table)
     *
     * @param int|string $accountIdOrName either ID or name
     *
     * @return Account
     *
     * @throws AccountNotFound
     * @throws MultipleAccountsMatched
     * @throws TransactionServicePanic
     */
    public function fetchAccountByIdOrName($accountIdOrName): Account
    {
        if (is_numeric($accountIdOrName)) {
            return $this->_fetchAccountById($accountIdOrName);
        }

        return $this->_fetchAccountByName($accountIdOrName);
    }

    /**
     * Returns Account entity by primary key
     *
     * @param int $accountId primary key from `transactions` table
     *
     * @return Account
     *
     * @throws AccountNotFound
     * @throws TransactionServicePanic
     */
    private function _fetchAccountById(int $accountId): Account
    {
        try {
            return $this->_db->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from('App\Entities\Account', 'a')
                ->where('a.id = :id')
                ->setParameter('id', $accountId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new AccountNotFound('id: ' . $accountId);
        } catch (NonUniqueResultException $e) {
            throw new TransactionServicePanic(
                'More than one account found for id ' . $accountId
            );
        }
    }

    /**
     * Returns Account entity by given name
     *
     * @param string $accountName exact account name
     *
     * @return Account
     *
     * @throws AccountNotFound
     * @throws MultipleAccountsMatched
     */
    private function _fetchAccountByName(string $accountName): Account
    {
        try {
            return $this->_db->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from('App\Entities\Account', 'a')
                ->where('a.name = :name')
                ->setParameter('name', $accountName)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new AccountNotFound($accountName);
        } catch (NonUniqueResultException $e) {
            throw new MultipleAccountsMatched(
                'More than one account matches ' . $accountName
            );
        }
    }

    /**
     * Commits uncommitted transactions
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function flush(): void
    {
        $this->_db->flush();
    }

    /**
     * Returns transaction that supposedly is the same one as given, a duplicate.
     * To achieve that, this method will try to match values of the transaction
     * to any existing one however this violates the assumption that we're getting
     * a valid file from the bank. In testing it became known that some transactions
     * are marked as duplicates which should not be the case.
     *
     * @param Transaction $transaction either attached or detached Transaction entity
     *
     * @return Transaction
     * @throws TransactionNotFound
     * @throws TransactionDuplicateFound
     */
    private function _fetchTransactionByValues(Transaction $transaction): Transaction
    {
        try {
            return $this->_db->getEntityManager()->createQueryBuilder()
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
     * @param Transaction $transaction attached entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteTransaction(Transaction $transaction)
    {
        $this->_db->getEntityManager()->remove($transaction);
        $this->_db->flush();
    }

    /**
     * Creates an account
     *
     * @param string   $name account name
     * @param int|null $id   optional internal account ID
     *
     * @return Account
     *
     * @throws AccountAlreadyExists
     * @throws TransactionServicePanic
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerAccount(string $name, int $id = null)
    {
        try {
            $this->_fetchAccountByName($name);
            throw new AccountAlreadyExists($name);
        } catch (AccountNotFound $e) {
        } catch (MultipleAccountsMatched $e) {
        }
        try {
            if (!is_null($id)) {
                $this->_fetchAccountById($id);
                throw new AccountAlreadyExists('id: ' . $id);
            }
        } catch (AccountNotFound $e) {
        }
        $acc = new Account();
        $acc->setId($id)
            ->setName($name);
        $this->_db->persist($acc, true);

        return $acc;
    }

    /**
     * Deletes account from persistence layer. For internal use only.
     *
     * @param Account $acc synchronized entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws AccountNotFound
     *
     * @internal
     */
    public function deleteAccount(Account $acc)
    {
        if (!$this->_db->contains($acc)) {
            throw new AccountNotFound('Entity is not synchronized.');
        }

        $this->_db->remove($acc);
        $this->_db->flush();
    }
}
