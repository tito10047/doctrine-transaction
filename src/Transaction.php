<?php

namespace Tito10047\DoctrineTransaction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class Transaction implements TransactionInterface{



	/** @var callable[] */
	private array $rollbackHandlers = [];
	/** @var callable[] */
	private array $commitHandlers = [];
	/** @var string[] */
	private array $currentConnections = [];

	public function __construct(
		private readonly ManagerRegistry $mr,
		private readonly string          $defaultConnection
	) {}

	public function begin(string ...$connection): void {
		if (count($connection) === 0) {
			$connection = [$this->defaultConnection];
		}
		$this->currentConnections = $connection;
		foreach ($this->currentConnections as $connection) {
			/** @var EntityManagerInterface $em */
			$em = $this->mr->getManager($connection);
			$em->beginTransaction();
		}
	}

	public function commit(): void {
		foreach ($this->currentConnections as $connection) {
			/** @var EntityManagerInterface $em */
			$em = $this->mr->getManager($connection);
			$em->commit();
		}
		foreach ($this->commitHandlers as $handler) {
			$handler();
		}
	}

	public function batchCommit(int $current, int $batchSize): bool {
		if ($current > 0 && ($current % $batchSize) === 0) {
			foreach ($this->currentConnections as $connection) {
				/** @var EntityManagerInterface $em */
				$em = $this->mr->getManager($connection);
				$em->commit();
			}
			foreach ($this->commitHandlers as $handler) {
				$handler();
			}
            $this->begin(...$this->currentConnections);

			return true;
		}

		return false;
	}

	public function flush(): void {
		foreach ($this->currentConnections as $connection) {
			/** @var EntityManagerInterface $em */
			$em = $this->mr->getManager($connection);
			$em->flush();
		}
	}

	public function rollback(): void {
		foreach ($this->currentConnections as $connection) {
			/** @var EntityManagerInterface $em */
			$em = $this->mr->getManager($connection);
			$em->rollback();
			$this->mr->resetManager($connection);
		}
		foreach ($this->rollbackHandlers as $handler) {
			$handler();
		}
	}

	public function clearAll(string ...$entityNames): void {
		foreach ($this->currentConnections as $connection) {
			/** @var EntityManagerInterface $em */
			$em = $this->mr->getManager($connection);
            $this->clear($connection, ...$entityNames);
		}
	}

	public function clear(string $connection, string ...$entityFCN): void {
		$em = $this->mr->getManager($connection);
		if (count($entityFCN)) {
			foreach ($entityFCN as $name) {
				$em->clear($name);
			}
		} else {
			$em->clear();
		}
	}

	public function addRollbackHandler(callable $handler): self {
		$this->rollbackHandlers[] = $handler;
        return $this;
	}

	public function addCommitHandler(callable $handler): self {
		$this->commitHandlers[] = $handler;
        return $this;
	}

	public function reset():void {
		foreach ($this->currentConnections as $connection) {
			$this->mr->resetManager($connection);
		}
	}

}