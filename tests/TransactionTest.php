<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 22. 9. 2024
 * Time: 20:45
 */

namespace Tito10047\DoctrineTransaction\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tito10047\DoctrineTransaction\Transaction;

class TransactionTest extends TestCase
{
    public function testBeginDefault()
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('beginTransaction');

        $transaction->begin();

    }
    public function testBeginMultipleNames()
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->exactly(2))
            ->method('getManager')
            ->with($this->logicalOr('first','second'))
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('beginTransaction');

        $transaction->begin("first","second");
    }
    public function testBeginCorrectOrder()
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->exactly(2))
            ->method('getManager')
            ->withConsecutive(['first'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('beginTransaction');

        $transaction->begin("first","second");
    }

    public function testCommitCorrectOrder(){
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");


        $mr->expects($this->exactly(4))
            ->method('getManager')
            ->withConsecutive(['first'],['second'],['first'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('commit');
        $transaction->begin("first","second");

        $transaction->commit();
    }

    public function testAddCommitHandlers():void
    {
        $mr = $this->createMock(ManagerRegistry::class);

        $transaction = new Transaction($mr,"default");
        $count = 0;
        $transaction->addCommitHandler(function () use (&$count) {
            $count++;
        });
        $transaction->commit();
        $transaction->commit();
        $this->assertEquals(2, $count);
    }

    public function testFlush(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->exactly(4))
            ->method('getManager')
            ->withConsecutive(['first'],['second'],['first'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('flush');

        $transaction->begin("first","second");
        $transaction->flush();
    }
    
    public function testRollback(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->exactly(4))
            ->method('getManager')
            ->withConsecutive(['first'],['second'],['first'],['second'])
            ->willReturn($em);
        $mr->expects($this->exactly(2))
            ->method('resetManager')
            ->withConsecutive(['first'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('rollback');

        $transaction->begin("first","second");
        $transaction->rollback();
    }

    public function testAddRollbackHandler(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $transaction = new Transaction($mr,"default");

        $count = 0;
        $transaction->addRollbackHandler(function () use (&$count) {
            $count++;
        });

        $transaction->rollback();
        $transaction->rollback();
        $this->assertEquals(2, $count);
    }

    public function testClearAll(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $transaction = new Transaction($mr,"default");

        $mr->expects($this->exactly(6))
            ->method('getManager')
            ->withConsecutive(['first'],['second'],['first'],['first'],['second'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('clear');

        $transaction->begin("first","second");
        $transaction->clearAll();
    }

    public function testClearOneAllEntities(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $mr->expects($this->any())
            ->method('getManager')
            ->with('first')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('clear');


        $transaction = new Transaction($mr,"default");
        $transaction->begin("first");
        $transaction->clear("first");
    }
    public function testClearOneTwoEntities(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $mr->expects($this->any())
            ->method('getManager')
            ->with('first')
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('clear')
            ->withConsecutive(['entity1'],['entity2']);


        $transaction = new Transaction($mr,"default");
        $transaction->begin("first");
        $transaction->clear("first","entity1","entity2");
    }

    public function testBatchCommit(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $mr->expects($this->any())
            ->method('getManager')
            ->with('first')
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('commit');
        $em->expects($this->exactly(3))
            ->method('beginTransaction');

        $count = 0;
        $commited = 0;
        $transaction = new Transaction($mr,"default");
        $transaction->addCommitHandler(function () use (&$count) {
            $count++;
        });
        $transaction->begin("first");
        for($i=0;$i<15;$i++){
            $commited +=$transaction->batchCommit($i,5);
        }
        $this->assertEquals(2,$count);
        $this->assertEquals(2, $commited);
    }

    public function testReset(): void
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $mr->expects($this->any())
            ->method('getManager')
            ->withConsecutive(['first'],['second'])
            ->willReturn($em);
        $mr->expects($this->exactly(2))
            ->method('resetManager')
            ->withConsecutive(['first'],['second']);


        $transaction = new Transaction($mr,"default");
        $transaction->begin("first","second");
        $transaction->reset();
    }
}