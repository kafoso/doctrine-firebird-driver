<?php
namespace Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Kafoso\DoctrineFirebirdDriver\Driver\AbstractFirebirdInterbaseDriver;
use Kafoso\DoctrineFirebirdDriver\ValueFormatter;

/**
 * Based on https://github.com/helicon-os/doctrine-dbal
 */
class Connection implements ConnectionInterface, ServerInfoAwareConnection
{
    const DEFAULT_CHARSET = 'UTF-8';
    const DEFAULT_BUFFERS = 0;
    const DEFAULT_IS_PERSISTENT = true;
    const DEFAULT_DIALECT = 0;

    /**
     * @var null|string
     */
    protected $connectString = null;

    /**
     * @var bool
     */
    protected $isPersistent = true;

    /**
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @var int
     */
    protected $buffers = 0;

    /**
     * @var int
     */
    protected $dialect = 0;

    /**
     * @var resource (ibase_pconnect or ibase_connect)
     */
    private $_ibaseConnectionRc = null;

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
     * @param string $params
     * @param null|string $username
     * @param null|string $password
     * @param null|array $driverOptions
     * @throws \RuntimeException
     */
    public function __construct(array $params, $username, $password, array $driverOptions = array())
    {
        $this->close(); // Close/reset; because calling __construct after instantiation is apparently a thing

        $this->connectString = self::generateConnectString($params);
        $this->isPersistent = self::DEFAULT_IS_PERSISTENT;
        if (isset($params['isPersistent']) && is_bool($params['isPersistent'])) {
            $this->isPersistent = $params['isPersistent'];
        }
        $this->charset = self::DEFAULT_CHARSET;
        if (isset($params['charset']) && is_string($params['charset']) && $params['charset']) {
            $this->charset = $params['charset'];
        }
        $this->buffers = self::DEFAULT_BUFFERS;
        if (isset($params['buffers']) && is_int($params['buffers']) && $params['buffers'] >= 0) {
            $this->buffers = $params['buffers'];
        }
        $this->dialect = self::DEFAULT_DIALECT;
        if (isset($params['dialect'])
            && is_int($params['dialect'])
            && $params['dialect'] >= 0
            && $params['dialect'] <= 3) {
            $this->dialect = $params['dialect'];
        }
        $this->username = $username;
        $this->password = $password;
        if ($driverOptions) {
            foreach ($driverOptions as $k => $v) {
                $this->setAttribute($k, $v);
            }
        }
        $this->getActiveTransaction(); // Connects to the database
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritDoc}
     *
     * Additionally to the standard driver attributes, the attribute
     * {@link AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL} can be used to control the
     * isolation level used for transactions.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL:
                $this->attrDcTransIsolationLevel = $value;
                break;
            case AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT:
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
            case AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL:
                return $this->attrDcTransIsolationLevel;
            case AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT:
                return $this->attrDcTransWait;
            case \PDO::ATTR_AUTOCOMMIT:
                return $this->attrAutoCommit;
        }
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
    public function quote($value, $type=\PDO::PARAM_STR)
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
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return false;
        }
        if (false == is_string($name)) {
            throw new \InvalidArgumentException(sprintf(
                "Argument \$name must be null or a string. Found: %s",
                ValueFormatter::found($name)
            ));
        }
        $maxGeneratorLength = 31;
        $regex = "/^\w{1,{$maxGeneratorLength}}\$/";
        if (false == preg_match($regex, $name)) {
            throw new \UnexpectedValueException(sprintf(
                "Expects argument \$name to match regular expression '%s'. Found: %s",
                $regex,
                ValueFormatter::found($name)
            ));
        }
        $sql = "SELECT GEN_ID({$name}, 0) LAST_VAL FROM RDB\$DATABASE";
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
        if (!$this->_ibaseConnectionRc || false == is_resource($this->_ibaseConnectionRc)) {
            try {
                if ($this->isPersistent) {
                    $this->_ibaseConnectionRc = @ibase_pconnect( // Notice the "p"
                        $this->connectString,
                        $this->username,
                        $this->password,
                        $this->charset,
                        $this->buffers,
                        $this->dialect
                    );
                } else {
                    $this->_ibaseConnectionRc = @ibase_connect(
                        $this->connectString,
                        $this->username,
                        $this->password,
                        $this->charset,
                        $this->buffers,
                        $this->dialect
                    );
                }
                if (!is_resource($this->_ibaseConnectionRc)) {
                    $this->checkLastApiCall();
                }
                if (!is_resource($this->_ibaseConnectionRc)) {
                    throw Exception::fromErrorInfo($this->errorInfo());
                }
                $this->_ibaseActiveTransaction = $this->createTransaction(true);
            } catch (\Exception $e) {
                throw new \RuntimeException("Failed to connection to database", 0, $e);
            }
        }
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

    protected function close()
    {
        if ($this->_ibaseActiveTransaction && is_resource($this->_ibaseActiveTransaction)) {
            if ($this->_ibaseTransactionLevel > 0) {
                $this->rollback(); // Auto-rollback explicite transactions
            }
            $this->autoCommit();
        }
        $success = true;
        if ($this->_ibaseConnectionRc && is_resource($this->_ibaseConnectionRc)) {
            $success = @ibase_close($this->_ibaseConnectionRc);
        }
        $this->_ibaseConnectionRc = null;
        $this->_ibaseActiveTransaction  = null;
        $this->_ibaseTransactionLevel = 0;
        if (false == $success) {
            $this->checkLastApiCall();
        }
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    public static function generateConnectString(array $params)
    {
        if (isset($params['host'], $params['dbname']) && $params['host'] && $params['dbname']) {
            $str = $params['host'];
            if (isset($params['port'])) {
                if (!$params['port']) {
                    throw new \RuntimeException("Invalid \"port\" in argument \$params");
                }
                $str .= '/' . $params['port'];
            }
            $str .= ':' . $params['dbname'];
            return $str;
        }
        throw new \RuntimeException("Argument \$params must contain non-empty \"host\" and \"dbname\"");
    }
}
