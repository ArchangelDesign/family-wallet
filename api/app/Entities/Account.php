<?php

namespace App\Entities;
/**
 * Class Account
 *
 * @package                App\Entities
 * @Entity
 * @Table(name="accounts")
 */
class Account
{
    /**
     * @var                    int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var                   string
     * @Column(type="string")
     */
    protected $name;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param  int $id
     * @return Account
     */
    public function setId(?int $id): Account
    {
        $this->id = $id;
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
     * @param  string $name
     * @return Account
     */
    public function setName(string $name): Account
    {
        $this->name = $name;
        return $this;
    }
}
