<?php
namespace Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\PDOConnection;

class Connection extends PDOConnection
{
    /** @var int */
    private $previousAutocommitValue;

    /**
     * {@inheritdoc}
     *
     * Apparently, pdo_firebird transactions fail unless we explicitly change PDO::ATTR_AUTOCOMMIT ourselves.
     * @see https://stackoverflow.com/a/41749323/25804
     */
    public function beginTransaction()
    {
        $this->previousAutocommitValue = $this->getAttribute(\PDO::ATTR_AUTOCOMMIT);
        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        return parent::beginTransaction();
    }

    /**
     * {@inheritdoc}
     *
     * Apparently, pdo_firebird transactions fail unless we explicitly change PDO::ATTR_AUTOCOMMIT ourselves.
     * @see https://stackoverflow.com/a/41749323/25804
     */
    public function commit()
    {
        $result = parent::commit();
        $this->resetAutocommitValue();
        return $result;
    }

    /**
     * {@inheritdoc)
     *
     * Apparently, pdo_firebird transactions fail unless we explicitly change PDO::ATTR_AUTOCOMMIT ourselves.
     * @see https://stackoverflow.com/a/41749323/25804
     */
    public function rollBack()
    {
        $result = parent::rollBack();
        $this->resetAutocommitValue();
        return $result;
    }

    private function resetAutocommitValue()
    {
        if(isset($this->previousAutocommitValue)) {
            $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, $this->previousAutocommitValue);
            unset($this->previousAutocommitValue);
        }
    }
}
