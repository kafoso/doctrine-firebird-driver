<?php
declare(strict_types=1);

namespace Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Kafoso\DoctrineFirebirdDriver\ValueFormatter;

/**
 * Based on:
 *   - https://github.com/helicon-os/doctrine-dbal
 *   - https://github.com/doctrine/dbal/blob/2.6/lib/Doctrine/DBAL/Driver/SQLSrv/SQLSrvStatement.php
 */
class Statement implements \IteratorAggregate, StatementInterface
{
    const DEFAULT_FETCH_CLASS = '\stdClass';
    const DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS = [];
    const DEFAULT_FETCH_COLUMN = 0;
    const DEFAULT_FETCH_MODE = \PDO::FETCH_BOTH;

    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var null|resource   Resource of the prepared statement
     */
    protected $ibaseStatementRc;

    /**
     * @var null|resource  Query result resource
     */
    public $ibaseResultRc = null;

    /**
     * The SQL or DDL statement.
     * @var string
     */
    protected $statement = null;

    /**
     * Zero-Based List of parameter bindings
     * @var array
     */
    protected $queryParamBindings = [];

    /**
     * Zero-Based List of parameter binding types
     * @var array
     */
    protected $queryParamTypes = [];

    /**
     * @var integer Default fetch mode set by setFetchMode
     */
    protected $defaultFetchMode = self::DEFAULT_FETCH_MODE;

    /**
     * @var string  Default class to be used by FETCH_CLASS or FETCH_OBJ
     */
    protected $defaultFetchClass = self::DEFAULT_FETCH_CLASS;

    /**
     * @var integer Default column to fetch by FETCH_COLUMN
     */
    protected $defaultFetchColumn = self::DEFAULT_FETCH_COLUMN;

    /**
     * @var array   Parameters to be passed to constructor in FETCH_CLASS
     */
    protected $defaultFetchClassConstructorArgs = self::DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS;

    /**
     * @var null|Object  Object used as target by FETCH_INTO
     */
    protected $defaultFetchInto = null;

    /**
     * Number of rows affected by last execute
     * @var integer
     */
    protected $affectedRows = 0;

    /**
     * @var int
     */
    protected $numFields = 0;

    /**
     * Mapping between parameter names and positions
     *
     * The map is indexed by parameter name including the leading ':'.
     *
     * Each item contains an array of zero-based parameter positions.
     */
    protected $namedParamsMap = [];

    /**
     * @throws Exception
     */
    public function __construct(Connection $connection, string $prepareString)
    {
        $this->connection = $connection;
        $this->setStatement($prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        switch ($fetchMode) {
            case \PDO::FETCH_OBJ:
            case \PDO::FETCH_CLASS:
                $fetchClass = '\stdClass';
                if (is_string($arg2)) {
                    $fetchClass = $arg2;
                }
                if (false == class_exists($fetchClass)) {
                    throw new Exception(sprintf(
                        "Fetch class %s (from \$arg2) does not exist",
                        ValueFormatter::cast($fetchClass)
                    ));
                }
                $this->defaultFetchMode = $fetchMode;
                $this->defaultFetchClass = $fetchClass;
                $this->defaultFetchClassConstructorArgs = self::DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS;
                if (is_array($arg3)) {
                    $this->defaultFetchClassConstructorArgs = $arg3;
                }
                $this->defaultFetchColumn = self::DEFAULT_FETCH_COLUMN;
                $this->defaultFetchInto = null;
                return;
            case \PDO::FETCH_INTO:
                $fetchInto = $arg2;
                if (false == is_object($fetchInto)) {
                    throw new Exception(sprintf(
                        "Fetch into (from \$arg2) must be an object. Found: %s",
                        ValueFormatter::found($fetchClass)
                    ));
                }
                $this->defaultFetchMode = $fetchMode;
                $this->defaultFetchClass = self::DEFAULT_FETCH_CLASS;
                $this->defaultFetchClassConstructorArgs = self::DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS;
                $this->defaultFetchColumn = self::DEFAULT_FETCH_COLUMN;
                $this->defaultFetchInto = $fetchInto;
                return;
            case \PDO::FETCH_COLUMN:
                $fetchColumn = self::DEFAULT_FETCH_COLUMN;
                if (is_int($arg2)) {
                    $fetchColumn = $arg2;
                }
                $this->defaultFetchMode = $fetchMode;
                $this->defaultFetchClass = self::DEFAULT_FETCH_CLASS;
                $this->defaultFetchClassConstructorArgs = self::DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS;
                $this->defaultFetchColumn = $fetchColumn;
                $this->defaultFetchInto = null;
                return;
            case \PDO::FETCH_BOTH:
            case \PDO::FETCH_ASSOC:
            case \PDO::FETCH_NUM:
                $this->defaultFetchMode = $fetchMode;
                $this->defaultFetchClass = self::DEFAULT_FETCH_CLASS;
                $this->defaultFetchClassConstructorArgs = self::DEFAULT_FETCH_CLASS_CONSTRUCTOR_ARGS;
                $this->defaultFetchColumn = self::DEFAULT_FETCH_COLUMN;
                $this->defaultFetchInto = null;
                return;
        }
        throw new Exception(sprintf(
            "Fetch mode %s is not supported",
            ValueFormatter::cast($fetchMode)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = \PDO::PARAM_STR)
    {
        return $this->bindParam($param, $value, $type, null);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = \PDO::PARAM_STR, $length = null, $driverOptions = null)
    {
        if (is_object($variable)) {
            $variable = (string) $variable;
        }
        if (is_numeric($column)) {
            $this->queryParamBindings[$column - 1] = &$variable;
            $this->queryParamTypes[$column - 1] = $type;
        } else {
            if (isset($this->namedParamsMap[$column])) {
                /**
                 * @var integer $pp *zero* based Parameter index
                 */
                foreach ($this->namedParamsMap[$column] as $pp) {
                    $this->queryParamBindings[$pp] = &$variable;
                    $this->queryParamTypes[$pp] = $type;
                }
            } else {
                throw new Exception('Cannot bind to unknown parameter ' . $column, null);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        if ($this->ibaseResultRc && is_resource($this->ibaseResultRc)) {
            $success = @ibase_free_result($this->ibaseResultRc);
            if (false == $success) {
                $this->checkLastApiCall();
            }
        }
        $this->ibaseResultRc = null;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null)
    {
        $this->affectedRows = 0;

        // Bind passed parameters
        if (is_array($params) && !empty($params)) {
            $idxShift = array_key_exists(0, $params) ? 1 : 0;
            $hasZeroIndex = array_key_exists(0, $params);
            foreach ($params as $key => $val) {
                $key = (is_numeric($key)) ? $key + $idxShift : $key;
                $this->bindValue($key, $val);
            }
        }

        // Execute statement
        if (count($this->queryParamBindings) > 0) {
            $this->ibaseResultRc = $this->doExecPrepared();
        } else {
            $this->ibaseResultRc = $this->doDirectExec();
        }

        // Check result

        if ($this->ibaseResultRc !== false) {
            // Result seems ok - is either #rows or result handle
            if (is_numeric($this->ibaseResultRc)) {
                $this->affectedRows = $this->ibaseResultRc;
                $this->numFields = 0;
                $this->ibaseResultRc = null;
            } elseif (is_resource($this->ibaseResultRc)) {
                $this->affectedRows = @ibase_affected_rows($this->connection->getActiveTransaction());
                $this->numFields = @ibase_num_fields($this->ibaseResultRc) ?: 0;
            }
            // As the ibase-api does not have an auto-commit-mode, autocommit is simulated by calling the
            // function autoCommit of the connection
            $this->connection->autoCommit();
        } else {
            // Error
            $this->checkLastApiCall();
        }
        if ($this->ibaseResultRc === false) {
            $this->ibaseResultRc = null;
            return false;
        }
        return true;
    }

    /**
     * $cursorOrientation and $cursorOffset are not supported in this driver.
     *
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if (null === $fetchMode) {
            $fetchMode = $this->defaultFetchMode;
        }
        switch ($fetchMode) {
            case \PDO::FETCH_OBJ:
            case \PDO::FETCH_CLASS:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchClassOrObject(
                        $this->defaultFetchClass,
                        $this->defaultFetchClassConstructorArgs
                    );
                }
                return false;
            case \PDO::FETCH_INTO:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchClassOrObject($this->defaultFetchInto, []);
                }
                return false;
            case \PDO::FETCH_ASSOC:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAssoc();
                }
                return false;
            case \PDO::FETCH_NUM:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchNum();
                }
                return false;
            case \PDO::FETCH_BOTH:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchBoth();
                }
                return false;
            default:
                throw new Exception(sprintf(
                    "Fetch mode %s not supported by this driver. Called in method %s",
                    ValueFormatter::cast($fetchMode),
                    __METHOD__
                ));
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        if (null === $fetchMode) {
            $fetchMode = $this->defaultFetchMode;
        }
        if (\PDO::FETCH_INTO === $fetchMode) {
            $fetchInto = $fetchArgument;
            if (null === $fetchInto) {
                $fetchInto = $this->defaultFetchInto;
            }
            throw new Exception(sprintf(
                "Cannot use \PDO::FETCH_INTO; fetching multiple rows into single object is impossible. Fetch object is: %s",
                ValueFormatter::cast($fetchInto)
            ));
        }
        switch ($fetchMode) {
            case \PDO::FETCH_CLASS:
            case \PDO::FETCH_OBJ:
                if (null === $fetchArgument) {
                    $fetchArgument = $this->defaultFetchClass;
                } elseif (false == is_string($fetchArgument)) {
                    throw new Exception(sprintf(
                        "Argument \$fetchArgument must - when fetch mode is %s - be null or a string. Found: %s",
                        ($fetchMode === \PDO::FETCH_CLASS ? '\PDO::FETCH_CLASS' : '\PDO::FETCH_OBJ'),
                        ValueFormatter::found($fetchArgument)
                    ));
                }
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAllClassOrObjects(
                        $fetchArgument,
                        $ctorArgs == null ? $this->defaultFetchClassConstructorArgs : $ctorArgs
                    );
                }
                return [];
            case \PDO::FETCH_COLUMN:
                $columnIndex = 0;
                if ($fetchArgument) {
                    if (is_int($fetchArgument)) {
                        $columnIndex = max(0, $fetchArgument);
                    } else {
                        throw new Exception(sprintf(
                            "Argument \$fetchArgument must - when fetch mode is \\PDO::FETCH_COLUMN - be an"
                            . " integer. Found: %s",
                            ValueFormatter::found($fetchArgument)
                        ));
                    }
                }
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAllColumn($columnIndex);
                }
                return [];
            case \PDO::FETCH_BOTH:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAllBoth();
                }
                return [];
            case \PDO::FETCH_ASSOC:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAllAssoc();
                }
                return [];
            case \PDO::FETCH_NUM:
                if ($this->ibaseResultRc) {
                    return $this->internalFetchAllNum();
                }
                return [];
            default:
                throw new Exception(sprintf(
                    "Fetch mode %s not supported by this driver. Called through method %s",
                    ValueFormatter::cast($fetchMode),
                    __METHOD__
                ));
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->internalFetchColumn(intval($columnIndex));
    }

    /**
     * {@inheritDoc}
     */
    public function rowCount()
    {
        return $this->affectedRows;
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return $this->numFields;
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
                'code' => $this->errorCode(),
                'message' => ibase_errmsg(),
            ];
        }
        return [
            'code' => 0,
            'message' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $data = $this->fetchAll();
        return new \ArrayIterator($data);
    }

    /**
     * @return bool|mixed
     */
    protected function internalFetchColumn(int $columnIndex = 0)
    {
        $rowData = $this->internalFetchNum();
        if (is_array($rowData)) {
            return (isset($rowData[$columnIndex]) ? $rowData[$columnIndex] : null);
        }
        return false;
    }

    protected function internalFetchAllColumn(int $columnIndex = 0): array
    {
        $result = [];
        while ($data = $this->internalFetchColumn()) {
            $result[] = $data;
        }
        return $result;
    }

    protected function internalFetchAllAssoc(): array
    {
        $result = [];
        while ($data = $this->internalFetchAssoc()) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Fetches a record into a array
     * @return bool|array
     */
    protected function internalFetchNum()
    {
        return @ibase_fetch_row($this->ibaseResultRc, IBASE_TEXT);
    }

    /**
     * Fetch all records into an array of numeric arrays.
     * @return array
     */
    protected function internalFetchAllNum(): array
    {
        $result = [];
        while ($data = $this->internalFetchNum()) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Fetches all records into an array containing arrays with column name and column index.
     */
    protected function internalFetchAllBoth(): array
    {
        $result = [];
        while ($data = $this->internalFetchBoth()) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Fetches into an object
     *
     * @param object|string $aClassOrObject Object instance or class
     * @param array $constructorArguments   Parameters to pass to the constructor
     * @return object|false                 Result object or false
     */
    protected function internalFetchClassOrObject($aClassOrObject, array $constructorArguments = null)
    {
        $rowData = $this->internalFetchAssoc();
        if (is_array($rowData)) {
            return $this->createObjectAndSetPropertiesCaseInsenstive(
                $aClassOrObject,
                (is_array($constructorArguments) ? $constructorArguments : []),
                $rowData
            );
        }
        return $rowData;
    }

    /**
     * Fetches all records into objects
     *
     * @param object|string $aClassOrObject Object instance or class
     * @param array $constructorArguments   Parameters to pass to the constructor
     * @return object|false                 Result object or false
     */
    protected function internalFetchAllClassOrObjects($aClassOrObject, array $constructorArguments)
    {
        $result = [];
        while ($row = $this->internalFetchClassOrObject($aClassOrObject, $constructorArguments)) {
            if ($row !== false) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Fetches the next record into an associative array
     * @return array|bool
     */
    protected function internalFetchAssoc()
    {
        return @ibase_fetch_assoc($this->ibaseResultRc, IBASE_TEXT);
    }

    /**
     * Fetches the next record into an array using column name and index as key
     * @return array|bool
     */
    protected function internalFetchBoth()
    {
        $tmpData = @ibase_fetch_assoc($this->ibaseResultRc, IBASE_TEXT);
        if (is_array($tmpData)) {
            return array_merge(array_values($tmpData), $tmpData);
        }
        return false;
    }

    /**
     * Creates an object and tries to set the object properties in a case insensitive way
     *
     * If a object is passed, the constructor is not called again
     *
     * NOTE: This function tries to mimic PDO's behaviour to set the properties *before* calling the constructor if possible.
     *
     * @param string|object $aClassOrObject Class name. If a object instance is passed, the given object is used
     * @param array $aConstructorArgArray Parameters passed to the constructor
     * @param array $aPropertyList Associative array of object properties
     * @return object created object or object passed in $aClassOrObject
     */
    protected function createObjectAndSetPropertiesCaseInsenstive($aClassOrObject, array $aConstructorArgArray, array $aPropertyList)
    {
        $callConstructor = false;
        if (is_object($aClassOrObject)) {
            $result = $aClassOrObject;
            $reflector = new \ReflectionObject($aClassOrObject);
        } else {
            if (!is_string($aClassOrObject))
                $aClassOrObject = '\stdClass';
            $classReflector = new \ReflectionClass($aClassOrObject);
            if (method_exists($classReflector, 'newInstanceWithoutConstructor')) {
                $result = $classReflector->newInstanceWithoutConstructor();
                $callConstructor = true;
            } else {
                $result = $classReflector->newInstance($aConstructorArgArray);
            }
            $reflector = new \ReflectionObject($result);
        }
        $propertyReflections = $reflector->getProperties();
        foreach ($aPropertyList as $properyName => $propertyValue) {
            $createNewProperty = true;
            foreach ($propertyReflections as $propertyReflector) /* @var $propertyReflector \ReflectionProperty */ {
                if (strcasecmp($properyName, $propertyReflector->name) == 0) {
                    $propertyReflector->setValue($result, $propertyValue);
                    $createNewProperty = false;
                    break; // ===> BREAK We have found what we want
                }
            }
            if ($createNewProperty) {
                $result->$properyName = $propertyValue;
            }
        }
        if ($callConstructor) {
            $constructorRefelector = $reflector->getConstructor();
            if ($constructorRefelector) {
                $constructorRefelector->invokeArgs($result, $aConstructorArgArray);
            }
        }
        return $result;
    }

    /**
     * Prepares the statement for further use and executes it
     * @return resource|bool
     */
    protected function doDirectExec()
    {
        $callArgs = $this->queryParamBindings;
        array_unshift($callArgs, $this->connection->getActiveTransaction(), $this->statement);
        return @call_user_func_array('ibase_query', $callArgs);
    }

    /**
     * Prepares the statement for further use and executes it
     * @return resource|bool
     */
    protected function doExecPrepared()
    {
        if (!$this->ibaseStatementRc || !is_resource($this->ibaseStatementRc)) {
            $this->ibaseStatementRc = @ibase_prepare(
                $this->connection->getActiveTransaction(),
                $this->statement
            );
            if (!$this->ibaseStatementRc || !is_resource($this->ibaseStatementRc)) {
                $this->checkLastApiCall();
            }
        }
        $callArgs = $this->queryParamBindings;
        array_unshift($callArgs, $this->ibaseStatementRc);
        return @call_user_func_array('ibase_execute', $callArgs);
    }

    /**
     * Sets and analyzes the statement.
     */
    protected function setStatement(string $statement)
    {
        $this->statement = $statement;
        $this->namedParamsMap = [];
        $pp = \Doctrine\DBAL\SQLParserUtils::getPlaceholderPositions($statement, false);
        if (!empty($pp)) {
            $pidx = 0; // index-position of the parameter
            $le = 0; // substr start position
            $convertedStatement = '';
            foreach ($pp as $ppos => $pname) {
                $convertedStatement .= substr($statement, $le, $ppos - $le) . '?';
                if (!isset($this->namedParamsMap[':' . $pname])) {
                    $this->namedParamsMap[':' . $pname] = (array)$pidx;
                } else {
                    $this->namedParamsMap[':' . $pname][] = $pidx;
                }
                $le = $ppos + strlen($pname) + 1; // Continue at position after :name
                $pidx++;
            }
            $convertedStatement .= substr($statement, $le);
            $this->statement = $convertedStatement;
        }
    }
}
