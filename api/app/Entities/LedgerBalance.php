<?php


namespace App\Entities;
/**
 * Class LedgerBalance
 * @package App\Entities
 * @Entity
 * @Table(name="ledger_balance")
 */
class LedgerBalance
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var \DateTime
     * @Column(type="datetime")
     */
    protected $dateOf;

    /**
     * @var double
     * @Column(type="decimal")
     */
    protected $balance;

    /**
     * @var Account
     * @ManyToOne(targetEntity="App\Entities\Account")
     * @JoinColumn(name="account_id", referencedColumnName="id")
     */
    protected $account;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return LedgerBalance
     */
    public function setId(int $id): LedgerBalance
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateOf(): \DateTime
    {
        return $this->dateOf;
    }

    /**
     * @param \DateTime $dateOf
     * @return LedgerBalance
     */
    public function setDateOf(\DateTime $dateOf): LedgerBalance
    {
        $this->dateOf = $dateOf;
        return $this;
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     * @return LedgerBalance
     */
    public function setBalance(float $balance): LedgerBalance
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return LedgerBalance
     */
    public function setAccount(Account $account): LedgerBalance
    {
        $this->account = $account;
        return $this;
    }
}
