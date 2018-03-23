<?php
namespace IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use IST\DoctrineFirebirdDriver\Driver\ConfigurationInterface;

/**
 * Based on https://github.com/helicon-os/doctrine-dbal
 */
class Connection implements ConnectionInterface, ServerInfoAwareConnection
{
    const TRANSACTIONS_MAXIMUM_LEVEL = 20;

    /**
     * @var Configuration
     */
    private $_configuration;

    /**
     * @var resource (ibase_pconnect or ibase_connect)
     */
    private $_ibaseConnectionRc;

    /**
     * @var int
     */
    private $_ibaseTransactionLevel = 0;

    /**
     * @var resource
     */
    private $_ibaseActiveTransaction = null;

    /**
     * Isolation level used when a transaction is started.
     * @var int
     */
    protected $attrDcTransIsolationLevel = \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED;

    /**
     * Wait timeout used in transactions
     *
     * @var integer  Number of seconds to wait.
     */
    protected $attrDcTransWait = 5;

    /**
     * True if auto-commit is enabled
     * @var boolean
     */
    protected $attrAutoCommit = true;

    /**
     * @throws Exception
     */
    public function __construct(Configuration $configuration)
    {
        $this->_configuration = $configuration;
        foreach ($this->_configuration->getDriverOptions() as $k => $v) {
            $this->setAttribute($k, $v);
        }
        $this->connect();
    }

    public function __destruct()
    {
        $this->autoCommit();
        $success = @ibase_close($this->_ibaseConnectionRc);
        if (false == $success) {
            $this->checkLastApiCall();
        }
    }

    public function connect()
    {
        if (!$this->_ibaseConnectionRc || !is_resource($this->_ibaseConnectionRc)) {
            if ($this->_configuration->isPersistent()) {
                $this->_ibaseConnectionRc = @ibase_pconnect(
                    $this->_configuration->getFullHostString(),
                    $this->_configuration->getUsername(),
                    $this->_configuration->getPassword(),
                    $this->_configuration->getCharset(),
                    $this->_configuration->getBuffers(),
                    $this->_configuration->getDialect()
                );
            } else {
                $this->_ibaseConnectionRc = @ibase_connect(
                    $this->_configuration->getFullHostString(),
                    $this->_configuration->getUsername(),
                    $this->_configuration->getPassword(),
                    $this->_configuration->getCharset(),
                    $this->_configuration->getBuffers(),
                    $this->_configuration->getDialect()
                );
            }
            if (!is_resource($this->_ibaseConnectionRc)) {
                $this->checkLastApiCall();
            }
            if (!is_resource($this->_ibaseConnectionRc)) {
                throw Exception::fromErrorInfo($this->errorInfo());
            }
            $this->_ibaseActiveTransaction = $this->createTransaction(true);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Additionally to the standard driver attributes, the attribute
     * {@link Configuration::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL} can be used to control
     * the isolation level used for transactions
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case Configuration::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL:
                $this->attrDcTransIsolationLevel = $value;
                break;
            case Configuration::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT:
                $this->attrDcTransWait = $value;
                break;
            case \PDO::ATTR_AUTOCOMMIT:
                $this->attrAutoCommit = $value;
                break;
        }
    }
    /**
     * {@inheritDoc}
     *
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        switch ($attribute) {
            case Configuration::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL:
                return $this->attrDcTransIsolationLevel;
            case Configuration::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT:
                return $this->attrDcTransWait;
            case \PDO::ATTR_AUTOCOMMIT:
                return $this->attrAutoCommit;
        }
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * @return resource (ibase_pconnect or ibase_connect)
     */
    public function getInterbaseConnectionResource()
    {
        return $this->_ibaseConnectionRc;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        return ibase_server_info($this->_ibaseConnectionRc, IBASE_SVC_SERVER_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresQueryForServerVersion()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return new Statement($this, $prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type=\PDO::PARAM_STR)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $value = str_replace("'", "''", $value);
        return "'" . addcslashes($value, "\000\n\r\\\032") . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return false;
        }
        $sql = "SELECT GEN_ID('{$name}', 0) LAST_VAL FROM RDB\$DATABASE";
        $stmt = $this->query($sql);
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    /**
     * @param int $isolationLevel
     * @param int $timeout
     * @return string
     */
    public function getStartTransactionSql($isolationLevel, $timeout = 5)
    {
        $result = "";
        switch ($isolationLevel) {
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED: {
                    $result .= 'SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION';
                    break;
                }
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED: {
                    $result .= 'SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION';
                    break;
                }
            case \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ: {
                    $result .= 'SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT ';
                    break;
                }
            case \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE: {
                    $result .= 'SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT TABLE STABILITY';
                    break;
                }
        }
        if (($this->attrDcTransWait > 0)) {
            $result .= ' WAIT LOCK TIMEOUT ' . $this->attrDcTransWait;
        } elseif  (($this->attrDcTransWait === -1)) {
            $result .= ' WAIT';
        } else {
            $result .= ' NO WAIT';
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        if ($this->_ibaseTransactionLevel < 1) {
            $this->_ibaseActiveTransaction = $this->createTransaction(true);
            $this->_ibaseTransactionLevel++;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if ($this->_ibaseTransactionLevel > 0) {
            if (!$this->_ibaseActiveTransaction || false == is_resource($this->_ibaseActiveTransaction)) {
                throw new \RuntimeException(sprintf(
                    "No active transaction. \$this->_ibaseTransactionLevel = %d",
                    $this->_ibaseTransactionLevel
                ));
            }
            $success = @ibase_commit($this->_ibaseActiveTransaction);
            if (false == $success) {
                $this->checkLastApiCall();
            }
            $this->_ibaseTransactionLevel--;
        }
        if (0 == $this->_ibaseTransactionLevel) {
            $this->_ibaseActiveTransaction = $this->createTransaction(true);
        }
        return true;
    }

    /**
     * Commits the transaction if autocommit is enabled no explicte transaction has been started.
     * @throws \RuntimeException
     * @return null|bool
     */
    public function autoCommit()
    {
        if ($this->attrAutoCommit && $this->_ibaseTransactionLevel < 1) {
            if (!$this->_ibaseActiveTransaction || false == is_resource($this->_ibaseActiveTransaction)) {
                throw new \RuntimeException(sprintf(
                    "No active transaction. \$this->_ibaseTransactionLevel = %d",
                    $this->_ibaseTransactionLevel
                ));
            }
            $success = @ibase_commit_ret($this->getActiveTransaction());
            if (false == $success) {
                $this->checkLastApiCall();
            }
            return true;
        }
        return null;
    }

    /**
     * {@inheritdoc)
     * @throws \RuntimeException
     */
    public function rollBack()
    {
        if ($this->_ibaseTransactionLevel > 0) {
            if (!$this->_ibaseActiveTransaction || false == is_resource($this->_ibaseActiveTransaction)) {
                throw new \RuntimeException(sprintf(
                    "No active transaction. \$this->_ibaseTransactionLevel = %d",
                    $this->_ibaseTransactionLevel
                ));
            }
            $success = @ibase_rollback($this->_ibaseActiveTransaction);
            if (false == $success) {
                $this->checkLastApiCall();
            }
            $this->_ibaseTransactionLevel--;
        }
        $this->_ibaseActiveTransaction = $this->createTransaction(true);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return ibase_errcode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        $errorCode = $this->errorCode();
        if ($errorCode) {
            return [
                'code' => $errorCode,
                'message' => ibase_errmsg(),
            ];
        }
        return [
            'code' => 0,
            'message' => null,
        ];
    }

    /**
     * @throws \RuntimeException
     * @return resource
     */
    public function getActiveTransaction()
    {
        $this->connect();
        if (!$this->_ibaseActiveTransaction || false == is_resource($this->_ibaseActiveTransaction)) {
            throw new \RuntimeException(sprintf(
                "No active transaction. \$this->_ibaseTransactionLevel = %d",
                $this->_ibaseTransactionLevel
            ));
        }
        return $this->_ibaseActiveTransaction;
    }

    /**
     * Checks ibase_error and raises an exception if an error occured
     *
     * @throws Exception
     */
    protected function checkLastApiCall()
    {
        $lastError = $this->errorInfo();
        if (isset($lastError['code']) && $lastError['code']) {
            throw Exception::fromErrorInfo($lastError);
        }
    }

    /**
     * @param bool $commitDefaultTransaction
     * @return resource The ibase transaction.
     */
    protected function createTransaction($commitDefaultTransaction = true)
    {
        if ($commitDefaultTransaction) {
            @ibase_commit($this->_ibaseConnectionRc);
        }
        $sql = $this->getStartTransactionSql($this->attrDcTransIsolationLevel);
        $result = @ibase_query($this->_ibaseConnectionRc, $sql);
        if (false == is_resource($result)) {
            $this->checkLastApiCall();
        }
        return $result;
    }
}
