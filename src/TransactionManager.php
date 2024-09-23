<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 23. 9. 2024
 * Time: 8:36
 */

namespace Tito10047\DoctrineTransaction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class TransactionManager implements TransactionManagerInterface
{

    public function __construct(
        private readonly ManagerRegistry $mr,
        private readonly string          $defaultConnectionName = "default"
    ) {}

    /**
     * Transactions are started in the order in which they are passed to the parameter. if no parameter is sent, the default em is used
     * @param string ...$connection database connection names as they are listed in doctrine.yaml. If no parameter is sent, the default em is used
     * @return Transaction
     */
    public function begin(string ...$connection): Transaction {
        $transaction = new Transaction($this->mr, $this->defaultConnectionName);
        $transaction->begin(...$connection);
        return $transaction;
    }
}