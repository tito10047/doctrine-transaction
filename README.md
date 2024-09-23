![Tests](https://github.com/tito10047/doctrine-transaction/actions/workflows/unit-test.yml/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/tito10047/doctrine-transaction/badge.svg?branch=main)](https://coveralls.io/github/tito10047/doctrine-transaction?branch=main)

# Doctrine Transaction

When you use Repository classes in your application, and don't use EntityManager directly, 
you can't use the transaction methods on Repository classes. 
This package allows you to use transactions anywhere in your application.

This package is also useful when you need to use multiple connections in the same transaction.

## Setup

```
composer require tito10047/doctrine-transaction
```

## Try it

```yaml
#service.yaml
services:
    Tito10047\DoctrineTransaction\TransactionManager:
```

```php
use Tito10047\DoctrineTransaction\TransactionManager;

class MyService
{

    public function __construct(private readonly TransactionManager $tm)
    {
    }

    public function myMethod()
    {
        $transaction = $this->tm->begin();
        try {
            // Your code
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
    
    public function myBatchMethod() {
        $transaction = $this->tm->begin();
        try {
            for($i = 0; $i < 100; $i++) {
                $myEntity = new MyEntity();
                if ($transaction->batchCommit($i,10)){
                    $transaction->clear(MyEntity::class);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }    
    }
    
    public function myCallbacksMethod() {
        $transaction = $this->tm
            ->begin()
            ->addCommitHandler(function() {
                // Your code
            })
            ->addRollbackHandler(function() {
                // Your code
            });
        try {
            for($i = 0; $i < 100; $i++) {
                $myEntity = new MyEntity();
                if ($transaction->batchCommit($i,10)){
                    $transaction->clear(MyEntity::class);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
    
    public function multipleConnections() {
        $transaction = $this->tm->begin('connection1','connection2');
        try {
            // Your code
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
}

```
