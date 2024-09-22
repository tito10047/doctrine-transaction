<?php

namespace Tito10047\DoctrineTransaction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class Transaction {



	/** @var callable[] */
	private array $rollbackHandlers = [];
	/** @var callable[] */
	private array $commitHandlers = [];
	/** @var string[] */
	private array $currentConnections = [];

	public function __construct(
		private readonly ManagerRegistry $mr,
		private readonly string          $defaultConnectionName = "default"
	) {}

	/**
     * Transactions are started in the order in which they are passed to the parameter.
	 *
     * @param string $names entity manager names as they are listed in doctrine.yaml. If no parameter is sent, the default em is used
	 */
	public function begin(string ...$names): void {
		if (count($names) === 0) {
			$names = [$this->defaultConnectionName];
		}
		$this->currentConnections = $names;
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

	public function clear(string $connectionName, string ...$entityNames): void {
		$em = $this->mr->getManager($connectionName);
		if (count($entityNames)) {
			foreach ($entityNames as $name) {
				$em->clear($name);
			}
		} else {
			$em->clear();
		}
	}

	public function addRollbackHandler(callable $handler): void {
		$this->rollbackHandlers[] = $handler;
	}

	public function addCommitHandler(callable $handler): void {
		$this->commitHandlers[] = $handler;
	}

	public function reset():void {
		foreach ($this->currentConnections as $connection) {
			$this->mr->resetManager($connection);
		}
	}

}