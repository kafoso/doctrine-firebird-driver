<?php
namespace IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use IST\DoctrineFirebirdDriver\Driver\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL = 'doctrineTransactionIsolationLevel';

    const ATTR_DOCTRINE_DEFAULT_TRANS_WAIT = 'doctrineTransactionWait';

    /**
     * @var string
     */
    private $host;

    /**
     * @var null|string
     */
    private $port;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var int
     */
    private $buffers;

    /**
     * @var null|int
     */
    private $dialect;

    /**
     * @var bool
     */
    private $isPersistent;

    /**
     * @var array
     */
    private $driverOptions = [];

    /**
     * @param string $host
     * @param null|string $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $charset
     * @param int $buffers
     * @param null|int $dialect
     * @param bool $isPersistent
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $host,
        $port,
        $database,
        $username,
        $password,
        $charset,
        $buffers = 0,
        $dialect = null,
        $isPersistent = true
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->buffers = $buffers;
        $this->dialect = $dialect;
        $this->isPersistent = $isPersistent;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setDriverOption($key, $value)
    {
        if (trim($key) && in_array($key, self::getDriverOptionKeys())) {
            $this->driverOptions[$key] = $value;
        }
        return $this;
    }

    /**
     * @param array $options
     * @return self
     */
    public function setDriverOptions($options)
    {
        foreach ($options as $k => $v) {
            $this->setDriverOption($k, $v);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getBuffers()
    {
        return $this->buffers;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getDriverOption($key)
    {
        if (trim($key) && in_array($key, self::getDriverOptionKeys())) {
            return $this->driverOptions[$key];
        }
        return null;
    }

    /**
     * @return array
     */
    public function getDriverOptions()
    {
        return $this->driverOptions;
    }

    /**
     * @return string
     */
    public function getFullHostString()
    {
        $str = $this->getHost();
        if ($this->getPort()) {
            $str .= '/' . $this->getPort();
        }
        $str .= ':' . $this->getDatabase();
        return $str;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return ?string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return bool
     */
    public function isPersistent()
    {
        return $this->isPersistent;
    }

    /**
     * @return array
     */
    public static function getDriverOptionKeys()
    {
        return [
            self::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL,
            self::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT,
            \PDO::ATTR_AUTOCOMMIT,
        ];
    }
}
