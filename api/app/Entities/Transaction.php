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


}
