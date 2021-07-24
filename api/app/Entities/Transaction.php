<?php

namespace App\Entities;
/**
 * Class Transaction
 * @package App\Entities
 * @Entity
 * @Table(name="transactions")
 */
class Transaction
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string", length=12)
     */
    protected $type;

    /**
     * @var \DateTime
     * @Column(type="datetime")
     */
    protected $datePosted;

    /**
     * @var double
     * @Column(type="decimal")
     */
    protected $amount;

    /**
     * @var integer
     * @Column(type="integer")
     */
    protected $referenceNumber = 0;

    /**
     * @var string
     * @Column(type="string", length=200)
     */
    protected $name;

    /**
     * @var string|null
     * @Column(type="string", length=200, nullable=true)
     */
    protected $memo;

    /**
     * @var double
     * @Column(type="decimal", nullable=true)
     */
    protected $balance;

    /**
     * @var string
     * @Column(type="string", length=120)
     */
    protected $transactionId;

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
     * @return Transaction
     */
    public function setId(int $id): Transaction
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Transaction
     */
    public function setType(string $type): Transaction
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatePosted(): \DateTime
    {
        return $this->datePosted;
    }

    /**
     * @param \DateTime $datePosted
     * @return Transaction
     */
    public function setDatePosted(\DateTime $datePosted): Transaction
    {
        $this->datePosted = $datePosted;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return Transaction
     */
    public function setAmount(float $amount): Transaction
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getReferenceNumber(): int
    {
        return $this->referenceNumber;
    }

    /**
     * @param int $referenceNumber
     * @return Transaction
     */
    public function setReferenceNumber(int $referenceNumber): Transaction
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Transaction
     */
    public function setName(string $name): Transaction
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @param string $memo
     * @return Transaction
     */
    public function setMemo(?string $memo): Transaction
    {
        $this->memo = $memo;
        return $this;
    }

    /**
     * @return float
     */
    public function getBalance(): ?float
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     * @return Transaction
     */
    public function setBalance(?float $balance): Transaction
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
     * @return Transaction
     */
    public function setAccount(Account $account): Transaction
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return Transaction
     */
    public function setTransactionId(string $transactionId): Transaction
    {
        $this->transactionId = $transactionId;
        return $this;
    }
}
