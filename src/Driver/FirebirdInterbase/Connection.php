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
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var resource (ibase_pconnect or ibase_connect)
     */
    private $ibaseConnectionRc;

    /**
     * @var int Transaction Depth. Should never be > 1
     */
    protected $transactionDepth = 0;

    /**
     * @var resource Resource of the active transaction.
     */
    protected $ibaseActiveTransactionRc = null;

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
        $this->configuration = $configuration;
        foreach ($configuration->getDriverOptions() as $k => $v) {
            $this->setAttribute($k, $v);
        }
        $this->getActiveTransactionIbaseRes();
    }

    public function __destruct()
    {
        if ($this->transactionDepth > 0) {
            // Auto-Rollback explicite transactions
            $this->rollback();
        }
        $this->autoCommit();
        @ibase_close($this->ibaseConnectionRc);
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
     * Returns the current transaction context resource
     *
     * Inside an active transaction, the current transaction resource ({@link $activeTransactionIbaseRes}) is returned,
     * Otherwise the function returns the connection resource ({@link $connectionIbaseRes}).
     *
     * If the connection is not open, it gets opened.
     *
     * @return resource|null
     */
    public function getActiveTransactionIbaseRes()
    {
        if (!$this->ibaseConnectionRc || !is_resource($this->ibaseConnectionRc)) {
            if ($this->configuration->isPersistent()) {
                $this->ibaseConnectionRc = @ibase_pconnect(
                    $this->configuration->getFullHostString(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getCharset(),
                    $this->configuration->getBuffers(),
                    $this->configuration->getDialect()
                );
            } else {
                $this->ibaseConnectionRc = @ibase_connect(
                    $this->configuration->getFullHostString(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getCharset(),
                    $this->configuration->getBuffers(),
                    $this->configuration->getDialect()
                );
            }
            if (!is_resource($this->ibaseConnectionRc)) {
                $this->checkLastApiCall();
            }
            if (!is_resource($this->ibaseConnectionRc)) {
                throw Exception::fromErrorInfo($this->errorInfo());
            }
            $this->ibaseActiveTransactionRc = $this->internalBeginTransaction(true);
        }
        if ($this->ibaseActiveTransactionRc && is_resource($this->ibaseActiveTransactionRc)) {
            return $this->ibaseActiveTransactionRc;
        }
        return null;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return resource (ibase_pconnect or ibase_connect)
     */
    public function getInterbaseConnectionResource()
    {
        return $this->ibaseConnectionRc;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        return ibase_server_info($this->ibaseConnectionRc, IBASE_SVC_SERVER_VERSION);
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
        $sql = 'SELECT GEN_ID(' . $name . ', 0) LAST_VAL FROM RDB$DATABASE';
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
        if ($this->transactionDepth < 1) {
            $this->ibaseActiveTransactionRc = $this->internalBeginTransaction(true);
            $this->transactionDepth++;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if ($this->transactionDepth > 0) {
            $res = @ibase_commit($this->ibaseActiveTransactionRc);
            if (!$res) {
                $this->checkLastApiCall();
            }
            $this->transactionDepth--;
        }
        $this->ibaseActiveTransactionRc = $this->internalBeginTransaction(true);
        return true;
    }

    /**
     * Commits the transaction if autocommit is enabled no explicte transaction has been started.
     * @return null|bool
     */
    public function autoCommit()
    {
        if ($this->attrAutoCommit && $this->transactionDepth < 1) {
            $result = @ibase_commit_ret($this->getActiveTransactionIbaseRes());
            if (!$result) {
                $this->checkLastApiCall();
            }
            return $result;
        }
        return null;
    }

    /**
     * {@inheritdoc}non-PHPdoc)
     */
    public function rollBack()
    {
        if ($this->transactionDepth > 0) {
            $res = @ibase_rollback($this->ibaseActiveTransactionRc);
            if (!$res) {
                $this->checkLastApiCall();
            }
            $this->transactionDepth--;
        }
        $this->ibaseActiveTransactionRc = $this->internalBeginTransaction(true);
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
     * @param resource $commitDefaultTransaction
     */
    protected function internalBeginTransaction($commitDefaultTransaction = true)
    {
        if ($commitDefaultTransaction) {
            @ibase_commit($this->ibaseConnectionRc);
        }
        $result = @ibase_query($this->ibaseConnectionRc, $this->getStartTransactionSql($this->attrDcTransIsolationLevel));
        if (!is_resource($result)) {
            $this->checkLastApiCall();
        }
        return $result;
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
}
