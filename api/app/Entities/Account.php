<?php

namespace App\Entities;
/**
 * Class Account
 * @package App\Entities
 * @Entity
 * @Table(name="accounts")
 */
class Account
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
     * @Column(type="string")
     */
    protected $name;
}
