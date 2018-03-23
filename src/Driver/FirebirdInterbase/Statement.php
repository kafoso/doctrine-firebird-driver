<?php
namespace IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\Statement as StatementInterace;
use Doctrine\DBAL\Driver\StatementIterator;

/**
 * Based on https://github.com/helicon-os/doctrine-dbal
 */
class Statement implements \IteratorAggregate, StatementInterace
{
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
    protected $defaultFetchMode = \PDO::FETCH_BOTH;
    /**
     * @var string  Default class to be used by FETCH_CLASS or FETCH_OBJ
     */
    protected $defaultFetchClass = '\stdClass';
    /**
     * @var integer Default column to fetch by FETCH_COLUMN
     */
    protected $defaultFetchColumn = 0;
    /**
     * @var array   Parameters to be passed to constructor in FETCH_CLASS
     */
    protected $defaultFetchClassConstructorArgs = [];
    /**
     * @var Object  Object used as target by FETCH_INTO
     */
    protected $defaultFetchInto = null;
    /**
     * Number of rows affected by last execute
     * @var integer
     */
    protected $affectedRows = 0;
    protected $numFields = false;
    /**
     * Mapping between parameter names and positions
     *
     * The map is indexed by parameter name including the leading ':'.
     *
     * Each item contains an array of zero-based parameter positions.
     */
    protected $namedParamsMap = [];

    /**
     * @param string  $prepareString
     *
     * @throws Exception
     */
    public function __construct(Connection $connection, $prepareString)
    {
        $this->connection = $connection;
        $this->setStatement($prepareString);
    }

    /**
     * Frees the ressources
     */
    public function __destruct()
    {
        $this->closeCursor();
        if ($this->ibaseStatementRc && is_resource($this->ibaseStatementRc)) {
            $success = @ibase_free_query($this->ibaseStatementRc);
            if (false == $success) {
                $this->checkLastApiCall();
            }
            $this->ibaseStatementRc = null;
        }
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
     * Checks ibase_error and raises an exception if an error occured
     */
    protected function checkLastApiCall()
    {
        $lastError = $this->errorInfo();
        if (isset($lastError['code']) && $lastError['code']) {
            throw Exception::fromErrorInfo($lastError);
        }
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
     * {@inheritDoc}
     * @param int $fetchMode
     * @param mixed $arg2
     * @param mixed $arg3
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        switch ($fetchMode) {
            case \PDO::FETCH_OBJ:
            case \PDO::FETCH_CLASS: {
                    $this->defaultFetchMode = $fetchMode;
                    $this->defaultFetchClass = is_string($arg2) ? $arg2 : '\stdClass';
                    $this->defaultFetchClassConstructorArgs = is_array($arg3) ? $arg3 : [];
                    break;
                }
            case \PDO::FETCH_INTO: {
                    $this->defaultFetchMode = $fetchMode;
                    $this->defaultFetchInfo = $arg2;
                }
            case \PDO::FETCH_COLUMN: {
                    $this->defaultFetchMode = $fetchMode;
                    $this->defaultFetchColumn = isset($arg2) ? $arg2 : 0;
                }
            default:
                $this->defaultFetchMode = $fetchMode;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
        return $this->bindParam($param, $value, $type, null);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        if (is_object($variable)) {
            $variable = (string) $variable;
        }
        if (is_numeric($column)) {
            $this->queryParamBindings[$column - 1] = &$variable;
            $this->queryParamTypes[$column - 1] = $type;
        } else {
            if (isset($this->namedParamsMap[$column])) {
                foreach ($this->namedParamsMap[$column] as $pp) /* @var integer $pp *zero* based Parameter index */ {
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
    public function columnCount()
    {
        return $this->numFields;
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
                $this->numFields = @ibase_num_fields($this->ibaseResultRc);
                $this->ibaseResultRc = null;
            } elseif (is_resource($this->ibaseResultRc)) {
                $this->affectedRows = @ibase_affected_rows($this->connection->getActiveTransaction());
                $this->numFields = @ibase_num_fields($this->ibaseResultRc);
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
        } else {
            return true;
        }
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
     * Fetches a single row into a object
     *
     * @param object|string $fetchIntoObjectOrClass Object class to create or object to update
     *
     * @return boolean
     */
    public function fetchObject($fetchIntoObjectOrClass = '\stdClass')
    {
        return $this->internalFetchClassOrObject($fetchIntoObjectOrClass);
    }

    /**
     * {@inheritdoc}
     * @param int $fetchMode
     * @param null|string $optArg1
     * @return mixed
     */
    public function fetch($fetchMode = null, $optArg1 = null)
    {
        $fetchMode !== null || $fetchMode = $this->defaultFetchMode;
        switch ($fetchMode) {
            case \PDO::FETCH_OBJ:
            case \PDO::FETCH_CLASS:
                return $this->internalFetchClassOrObject(isset($optArg1) ? $optArg1 : $this->defaultFetchClass, $this->defaultFetchClassConstructorArgs);
            case \PDO::FETCH_INTO:
                return $this->internalFetchClassOrObject(isset($optArg1) ? $optArg1 : $this->defaultFetchInto, []);
            case \PDO::FETCH_ASSOC:
                return $this->internalFetchAssoc();
                break;
            case \PDO::FETCH_NUM:
                return $this->internalFetchNum();
                break;
            case \PDO::FETCH_BOTH:
                return $this->internalFetchBoth();
            default:
                throw new Exception('Fetch mode ' . $fetchMode . ' not supported by this driver in ' . __METHOD__);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        $fetchMode !== null || $fetchMode = $this->defaultFetchMode;
        switch ($fetchMode) {
            case \PDO::FETCH_CLASS:
            case \PDO::FETCH_OBJ: {
                    return $this->internalFetchAllClassOrObjects(
                                    $fetchArgument == null ? $this->defaultFetchClass : $fetchArgument, $ctorArgs == null ? $this->defaultFetchClassConstructorArgs : $ctorArgs);
                    break;
                }
            case \PDO::FETCH_COLUMN: {
                    return $this->internalFetchAllColumn(
                                    $fetchArgument == null ? 0 : $fetchArgument);
                    break;
                }
            case \PDO::FETCH_BOTH: {
                    return $this->internalFetchAllBoth();
                }
            case \PDO::FETCH_ASSOC: {
                    return $this->internalFetchAllAssoc();
                }
            case \PDO::FETCH_NUM: {
                    return $this->internalFetchAllNum();
                }
            default: {
                    throw new Exception('Fetch mode ' . $fetchMode . ' not supported by this driver in ' . __METHOD__);
                }
        }
    }
    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->internalFetchColumn($columnIndex);
    }
    /**
     * {@inheritDoc}
     */
    public function rowCount()
    {
        return $this->affectedRows;
    }

    /**
     * Fetches a single column.
     * @param int $columnIndex
     * @return bool|mixed
     */
    protected function internalFetchColumn($columnIndex = 0)
    {
        $rowData = $this->internalFetchNum();
        if (is_array($rowData)) {
            return (isset($rowData[$columnIndex]) ? $rowData[$columnIndex] : NULL);
        } else {
            return FALSE;
        }
    }

    /**
     * @param int $columnIndex
     * @return array
     */
    protected function internalFetchAllColumn($columnIndex = 0)
    {
        $result = [];
        while ($data = $this->internalFetchColumn()) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Fetch associative array
     * @return array
     */
    protected function internalFetchAllAssoc()
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
     * Fetch all records into an array of numeric arrays
     * @return array
     */
    protected function internalFetchAllNum()
    {
        $result = [];
        while ($data = $this->internalFetchNum()) {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Fetches all records into an array containing arrays with column name and column index
     * @return array
     */
    protected function internalFetchAllBoth()
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
            return $this->createObjectAndSetPropertiesCaseInsenstive($aClassOrObject, is_array($constructorArguments) ? $constructorArguments : [], $rowData);
        } else {
            return $rowData;
        }
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
     * @return array
     */
    protected function internalFetchAssoc()
    {
        return @ibase_fetch_assoc($this->ibaseResultRc, IBASE_TEXT);
    }

    /**
     * Fetches the next record into an array using column name and index as key
     * @return array|boolean
     */
    protected function internalFetchBoth()
    {
        $tmpData = @ibase_fetch_assoc($this->ibaseResultRc, IBASE_TEXT);
        if (false !== $tmpData) {
            return array_merge(array_values($tmpData), $tmpData);
        }
        return false;
    }

    /**
     * Prepares the statement for further use and executes it
     * @result resource|boolean
     */
    protected function doDirectExec()
    {
        $callArgs = $this->queryParamBindings;
        array_unshift($callArgs, $this->connection->getActiveTransaction(), $this->statement);
        return @call_user_func_array('ibase_query', $callArgs);
    }

    /**
     * Prepares the statement for further use and executes it
     * @result resource|boolean
     */
    protected function doExecPrepared()
    {
        if (!$this->ibaseStatementRc || !is_resource($this->ibaseStatementRc)) {
            $this->ibaseStatementRc = @ibase_prepare(
                $this->connection->getActiveTransaction(),
                $this->statement
            );
            if (!$this->ibaseStatementRc || !is_resource($this->ibaseStatementRc))
                $this->checkLastApiCall();
        }
        $callArgs = $this->queryParamBindings;
        array_unshift($callArgs, $this->ibaseStatementRc);
        return @call_user_func_array('ibase_execute', $callArgs);
    }

    /**
     * Sets and analyzes the statement
     *
     * @param string $statement
     */
    protected function setStatement($statement)
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
