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
use PHPUnit\Framework\TestCase;
use Tito10047\DoctrineTransaction\Transaction;
use Tito10047\DoctrineTransaction\TransactionManager;

class TransactionManagerTest extends TestCase
{
    public function testBeginDefault()
    {
        $mr = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $tm = new TransactionManager($mr,"first");

        $mr->expects($this->exactly(4))
            ->method('getManager')
            ->withConsecutive(['first'],['first'],['second'],['second'])
            ->willReturn($em);
        $em->expects($this->exactly(2))
            ->method('beginTransaction');
        $em->expects($this->exactly(2))
            ->method('commit');

        $count=0;
        $tm
            ->begin()
            ->addCommitHandler(function () use (&$count){
                $count++;
            })
            ->commit()
        ;
        $tm = new TransactionManager($mr,"second");
        $tm->begin()->commit();
        $this->assertEquals(1, $count);

    }
}