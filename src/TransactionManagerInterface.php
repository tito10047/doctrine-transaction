<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 23. 9. 2024
 * Time: 8:40
 */

namespace Tito10047\DoctrineTransaction;

interface TransactionManagerInterface
{
    public function beginTransaction(string ...$connection): Transaction;
}