<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 23. 9. 2024
 * Time: 8:43
 */

namespace Tito10047\DoctrineTransaction;

interface TransactionInterface
{
    /**
     * Transactions are started in the order in which they are passed to the parameter.
     *
     * @param string ...$connection entity manager names as they are listed in doctrine.yaml. If no parameter is sent, the default em is used
     */
    public function begin(string ...$connection): void ;

    public function commit(): void;

    /**
     * Commit transaction after a certain number of steps
     * @param int $current current step number
     * @param int $batchSize number of steps after which the transaction is committed
     * @return bool
     */
    public function batchCommit(int $current, int $batchSize): bool;

    public function flush(): void ;

    public function rollback(): void;

    public function clearAll(string ...$entityNames): void;

    /**
     * Clear the entity manager
     * @param string $connection entity manager name as it is listed in doctrine.yaml
     * @param string ...$entityFQN entity fully qualified class name of the entity to clear. if no parameter is sent, all entities are cleared
     */
    public function clear(string $connection, string ...$entityFQN): void;

    /**
     * Add a handler that is called when the transaction is rolled back
     * @param callable $handler
     */
    public function addRollbackHandler(callable $handler): self;

    /**
     * Add a handler that is called when the transaction is committed
     * @param callable $handler
     */
    public function addCommitHandler(callable $handler): self;

    public function reset():void;

}