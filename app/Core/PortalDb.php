<?php

namespace Portal\Core;

use Portal\Core\Model\PortalAuth;

final class PortalDb {
    use Singleton;

    const NULLABLE = 'YES';
    const NOT_NULLABLE = 'NO';

    const LEFT_JOIN = 'LEFT JOIN';
    const INNER_JOIN = 'INNER JOIN';

    private $_link;
    private $_dbConfig;

    private $_noValueOperators = array(
        'IS NOT NULL' => true,
        'IN' => true,
    );

    /**
     * PortalDb constructor.
     * @access protected
     *
     * @var $dbConfig
     */
    protected function __construct(array $dbConfig) {
        if($this->_link === null) {
            $this->_link = new \PDO(
                'mysql:host='. $dbConfig['host'] .';dbname='.$dbConfig['name'],
                $dbConfig['user'],
                $dbConfig['pass']
            );
            $this->_dbConfig = $dbConfig;
        }
    }

    /**
     * Zwraca obiekt z bazy spełniający warunki
     * @param $tableOrClass
     * @param $conditions
     * @param $joins
     * @param $subqueries
     * @param $fields
     * @param $limit
     * @param $order
     * @return array
     */
    public function get($tableOrClass, array $conditions = array(), array $joins = array(), array $subqueries = array(), array $fields = array(), $limit = null, $order = null) {
        $values = array();
        $constructorArguments = array();
        $prepare = '';
        $fetchClass = false;

        try {

            if(
                class_exists($tableOrClass)
                && method_exists($tableOrClass, 'getTableNameWithAlias')
                && method_exists($tableOrClass, 'getTableName')
            ) {
                $table = $tableOrClass::getTableNameWithAlias('frm_');
                $tableDescr = $tableOrClass::getTableName();
                $fetchClass = true;
            } else {
                $table = $tableDescr = $tableOrClass;
            }

            $q = $this->_link->prepare("DESCRIBE " . $tableDescr);
            $q->execute();
            $table_fields = $q->fetchAll(\PDO::FETCH_COLUMN);

            if(empty($fields)) {
                if(class_exists($tableOrClass) && method_exists($tableOrClass, 'getTableAlias')) {
                    $fieldsStr = $tableOrClass::getTableAlias('frm_') . '.*';
                } else {
                    $fieldsStr = '*';
                }
            } else {
                $fields = array_intersect($fields, $table_fields);
                $fieldsStr = implode(', ', $fields);
            }

            /**
             * Any subqueries?
             */
            if(!empty($subqueries)) {
                foreach($subqueries as $key => &$query) {
                    $query = '(' . trim($query, '()') . ') ' . $key . 's'; //plural
                }
                $fieldsStr .= ', ' . implode(', ', $subqueries);
            }

            /**
             * prepare JOINs
             */
            foreach($joins as $alias => $params) {
                switch($params['type']) {
                    case self::LEFT_JOIN:
                        $this->joinLeft(
                            $prepare,
                            $params['table'] . ' ' . $alias,
                            $params['on']
                        );
                        break;
                    case self::INNER_JOIN:
                        $this->joinInner(
                            $prepare,
                            $params['table'] . ' ' . $alias,
                            $params['on']
                        );
                        break;
                    default:
                        continue;
                }

                /**
                 * @todo don't run SQL in this loop?
                 */
                if(isset($params['model'])) {
                    $q = $this->_link->prepare("DESCRIBE " . $params['table']);
                    $q->execute();
                    $table_fields_join = $q->fetchAll(\PDO::FETCH_COLUMN);

                    $aliasedFields = array();
                    $fieldsStr .= ', ';

                    if($fetchClass && class_exists($params['model'])) {
                        $reflect = new \ReflectionClass($params['model']);
                        $modelShortName = $reflect->getShortName();
                    } else {
                        $modelShortName = $params['model'];
                    }

                    foreach($table_fields_join as $field) {
                        if(is_numeric($field)) {
                            continue;
                        }
                        $aliasedFields[] = $alias . '.' . $field . ' ' . $field . '_' . $modelShortName;
                        $constructorArguments[$field . '_' . $modelShortName] = $modelShortName;
                    }

                    if(!empty($aliasedFields)) {
                        $fieldsStr .= implode(', ', $aliasedFields);
                    } else {
                        $fieldsStr .= $alias . '.*';
                    }
                }
            }

            /**
             * prepare WHERE conditions
             */
            $prepare .= $this->prepareWhere($conditions, $values);

            /**
             * prepare ORDER clause
             */
            $prepare .= $this->prepareOrder($order, $table_fields);

            if(!empty($limit)) {
                if(is_array($limit)) {
                    $prepare .= ' LIMIT ' . intval($limit[0]) . ',' . intval($limit[1]);
                } else {
                    $prepare .= ' LIMIT ' . intval($limit);
                }
            }

            $query = $this->_link->prepare("SELECT $fieldsStr FROM $table" . $prepare);

            if ( !$query->execute($values) ) {
                $info = $query->errorInfo();
                $query->closeCursor();
                throw new \Exception($info[2]);
            }

            if($fetchClass) {
                return $query->fetchAll(\PDO::FETCH_CLASS, $tableOrClass, array($constructorArguments));
            }

            return $query->fetchAll(\PDO::FETCH_ASSOC);

        } catch(\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    /**
     * Wstawia obiekt do bazy
     * @param $tableOrClass
     * @param $items
     * @param $onDuplicate
     * @return bool|int
     */
    public function save($tableOrClass, array $items, $onDuplicate = null) {
        $items = array_filter($items);
        if(empty($items)) {
            return false;
        }

        try {
            if(class_exists($tableOrClass) && method_exists($tableOrClass, 'getTableName') && method_exists($tableOrClass, 'getPrimaryKey')) {
                $table = $tableOrClass::getTableName();
                $pk = $tableOrClass::getPrimaryKey();
            } else {
                $table = $tableOrClass;
            }

            $q = $this->_link->prepare("DESCRIBE " . $table);
            $q->execute();
            $table_fields = $q->fetchAll(\PDO::FETCH_COLUMN);
            $table_fields = array_flip($table_fields);

            $itemKeyScheme = reset($items);
            $fields = array_intersect_key($itemKeyScheme, $table_fields);
            $fieldsStr = '(' . implode(', ', array_keys($fields)) . ')';

            if(isset($pk) && array_key_exists($pk, $itemKeyScheme)) {
                $onDuplicateArray = array();
                foreach($fields as $key => $idx) {
                    $onDuplicateArray[] = $key . '=' . 'VALUES(' . $key . ')';
                }
                $onDuplicate = implode(', ', $onDuplicateArray);
            }

            $prepareStr = array();

            foreach($items as $idx => &$item) {
                $prepare = array();

                foreach($item as $key => &$value) {
                    $prepare[] = $this->paramStr($key, $idx);

                    //sanitize value
                    if(!empty($value)) {
                        $value = Utils::sanitizeText($value);
                    }
                }
                $prepareStr[] = '(' . implode(', ', $prepare) . ')';
            }

            $prepare = implode(', ', $prepareStr);

            if(!empty($onDuplicate)) {
                $prepare .= ' ON DUPLICATE KEY UPDATE ' . $onDuplicate;
            }

            $query = $this->_link->prepare("INSERT INTO $table $fieldsStr VALUES " . $prepare);
            $items = $this->extractValues($items);

            if ( !$query->execute($items) ) {
                $info = $query->errorInfo();
                $query->closeCursor();
                throw new \Exception($info[2]);
            }

            return $this->_link->lastInsertId();

        } catch(\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function update($tableOrClass, array $values, array $conditions) {
        try {
            if(class_exists($tableOrClass) && method_exists($tableOrClass, 'getTableName')) {
                $table = $tableOrClass::getTableName();
            } else {
                $table = $tableOrClass;
            }

            $suffix = 0;

            $prepare = array();
            foreach($values as $key => $value) {
                $prepare[] = $key . '=' . $this->paramStr($key, $suffix);

                //sanitize value
                if(!empty($value)) {
                    $value = strip_tags($value);
                }
                $values[$this->paramStr($key, $suffix++)] = $value;
                unset($values[$key]);
            }
            $prepareStr = implode(', ', $prepare);
            $prepareStr .= $this->prepareWhere($conditions, $values, $suffix);

            $query = $this->_link->prepare("UPDATE $table SET " . $prepareStr);

            if ( !$query->execute($values) ) {
                $info = $query->errorInfo();
                $query->closeCursor();
                throw new \Exception($info[2]);
            }

            return $query->rowCount();

        } catch(\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function delete($tableOrClass, array $conditions) {
        try {
            if(class_exists($tableOrClass) && method_exists($tableOrClass, 'getTableName')) {
                $table = $tableOrClass::getTableName();
            } else {
                $table = $tableOrClass;
            }

            $values = array();
            $prepare = $this->prepareWhere($conditions, $values);

            $query = $this->_link->prepare("DELETE FROM $table " . $prepare);

            if ( !$query->execute($values) ) {
                $info = $query->errorInfo();
                $query->closeCursor();
                throw new \Exception($info[2]);
            }

            return $query->rowCount();

        } catch(\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function updateSchema($tableOrClass, array $classTableDescription = array(), array $classTableRelations = array()) {
        if(!PortalAuth::isSuperAdmin() && PHP_SAPI != 'cli') {
            return false;
        }

        try {
            if(class_exists($tableOrClass) && method_exists($tableOrClass, 'getTableName') && method_exists($tableOrClass, 'getPrimaryKey')) {
                $table = $tableOrClass::getTableName();
                $pk = $tableOrClass::getPrimaryKey();
            } else {
                $table = $tableOrClass;
                /* @todo dynamic selection?? */
                $pk = 'id';
            }

            if(class_exists($tableOrClass) && property_exists($tableOrClass, 'tableDescription')) {
                $classTableDescription = $tableOrClass::$tableDescription;
            }

            $q = $this->_link->prepare("SELECT column_name, column_type, column_key FROM information_schema.columns WHERE table_schema = :schema
and table_name = :table");

            $q->execute(array('schema' => $this->_dbConfig['name'], 'table' => $table));
            $table_fields = $q->fetchAll(\PDO::FETCH_ASSOC);

            $dbTableStructure = array();
            $missing = array();
            $changed = array();
            $removed = array();

            foreach($table_fields as $dbData) {
                $dbTableStructure[$dbData['column_name']] = $dbData;
                if(!array_key_exists($dbData['column_name'], $classTableDescription)) {
                    $removed[] = $dbData['column_name'];
                }
            }

            foreach($classTableDescription as $clumnName => $columnData) {
                if(!array_key_exists($clumnName, $dbTableStructure)) {
                    $missing[$clumnName] = $columnData;
                } else {
                    foreach($columnData as $field => $value) {
                        if(array_key_exists($field, $dbTableStructure[$clumnName]) && $dbTableStructure[$clumnName][$field] != $value) {
                            $changed[$clumnName] = $columnData;
                            break;
                        }
                    }
                }
            }

            $isCreate = false;
            $dropArray = array();
            $updateArray = array();

            if(empty($table_fields)) {
                $isCreate = true;
            }

            foreach($removed as $clumnName) {
                $dropArray[] = 'DROP COLUMN ' . $clumnName;
            }

            foreach($missing as $columnName => $columnData) {
                if(!$isCreate && $columnName == 'pk') {
                    continue;
                }

                $columnString = array(($isCreate ? '' : 'ADD COLUMN'));
                $columnString[] = $columnData['column_name'];

                if($columnName == 'pk') {
                    $columnData['extra'] = '(' . $columnData['extra'] . ')';
                } else {
                    $columnString[] = $columnData['column_type'];
                    $columnString[] = $columnData['is_nullable'] == PortalDb::NOT_NULLABLE ? 'NOT NULL' : '';
                    $columnString[] = mb_strlen($columnData['column_default']) != 0 ? 'DEFAULT ' . $columnData['column_default'] : '';
                }

                $columnString[] = $columnData['extra'];

                $updateArray[] = implode(' ', $columnString);
            }

            foreach($changed as $clumnName => $columnData) {
                $columnString = array('MODIFY COLUMN');
                $columnString[] = $columnData['column_name'];
                $columnString[] = $columnData['column_type'];
                $columnString[] = $columnData['is_nullable'] == PortalDb::NOT_NULLABLE ? 'NOT NULL' : '';
                $columnString[] = mb_strlen($columnData['column_default']) != 0 ? 'DEFAULT ' . $columnData['column_default'] : '';
                $columnString[] = $columnData['extra'];

                $updateArray[] = implode(' ', $columnString);
            }

            /**
             * Update foreign keys
             */
            $q = $this->_link->prepare("
SELECT constraint_name, column_name, referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE constraint_schema = :schema AND table_name = :table AND referenced_table_name IS NOT NULL");

            $q->execute(array('schema' => $this->_dbConfig['name'], 'table' => $table));
            $table_constraints = $q->fetchAll(\PDO::FETCH_ASSOC);

            if(class_exists($tableOrClass) && property_exists($tableOrClass, 'relatedObjects')) {
                $classTableRelations = $tableOrClass::$relatedObjects;
            }

            $dbTableKeys = array();
            $missingKeys = array();
            $removedKeys = array();

            /**
             * @todo support for other relation types
             */
            foreach($table_constraints as $dbData) {
                $dbTableKeys[$dbData['column_name']] = $dbData;
                if(!array_key_exists($dbData['column_name'], $classTableRelations['one'])) {
                    $removedKeys[] = $dbData['constraint_name'];
                }
            }

            if(array_key_exists('one', $classTableRelations)) {
                foreach($classTableRelations['one'] as $clumnName => $model) {
                    list($relatedTable, $relatedColumn) = $this->extractKeyData($model);
                    if(empty($relatedTable) || empty($relatedColumn)) {
                        continue;
                    }

                    if(!array_key_exists($clumnName, $dbTableKeys)) {
                        $missingKeys[$clumnName] = $model;
                    } elseif($dbTableKeys[$clumnName]['referenced_table_name'] != $relatedTable || $dbTableKeys[$clumnName]['referenced_column_name'] != $relatedColumn) {
                        $missingKeys[$clumnName] = $model;
                        $removedKeys[] = $dbTableKeys[$clumnName]['constraint_name'];
                    }
                }
            }

            foreach($removedKeys as $constraintName) {
                $dropArray[] = 'DROP FOREIGN KEY ' . $constraintName;
            }

            foreach($missingKeys as $columnName => $model) {
                list($relatedTable, $relatedColumn) = $this->extractKeyData($model);
                if(empty($relatedTable) || empty($relatedColumn)) {
                    continue;
                }

                $columnString = array(($isCreate ? '' : 'ADD ') . 'FOREIGN KEY');
                $columnString[] = '(' . $columnName . ')';
                $columnString[] = 'REFERENCES';
                $columnString[] = $relatedTable . '(' . $relatedColumn . ')';

                $updateArray[] = implode(' ', $columnString);
            }

            $updateString = $emptyUpdateString = ($isCreate ? 'CREATE TABLE ' : 'ALTER TABLE ') . $table;

            if($isCreate) {
                $updateString .= '(';
            }

            if(!empty($dropArray)) {
                $updateString .= ' ' . implode(', ', $dropArray);
            }

            if(!empty($updateArray)) {
                $updateString .= (!empty($dropArray) ? ', ' : ' ') . implode(', ', $updateArray);
            }

            if($isCreate) {
                $updateString .= ')';
            }

            if($updateString == $emptyUpdateString) {
                return false;
            }

            $query = $this->_link->prepare($updateString);

            if ( !$query->execute() ) {
                $info = $query->errorInfo();
                $query->closeCursor();
                throw new \Exception($info[2]);
            }

            /**
             * update many to many relations after alters on base table
             */
            if(array_key_exists('many', $classTableRelations)) {
                foreach($classTableRelations['many'] as $tableName => $model) {
                    list($relatedTable, $relatedColumn) = $this->extractKeyData($model);
                    if(empty($relatedTable) || empty($relatedColumn)) {
                        continue;
                    }

                    $q = $this->_link->prepare("
SELECT *
FROM information_schema.tables
WHERE table_schema = :schema AND table_name = :table LIMIT 1;");

                    $q->execute(array('schema' => $this->_dbConfig['name'], 'table' => $tableName));
                    $multiRelatedTable = $q->rowCount();

                    if($multiRelatedTable != 1) {
                        //create table
                        $query = $this->_link->prepare("
CREATE TABLE $tableName (
id int(10) unsigned not null auto_increment,
{$table}_id int(10) unsigned not null,
{$relatedTable}_id int(10) unsigned not null,
PRIMARY KEY (id))");
                        $query->execute();
                    }

                    $q = $this->_link->prepare("
SELECT constraint_name, column_name, referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE constraint_schema = :schema AND table_name = :table AND referenced_table_name IS NOT NULL");

                    $q->execute(array('schema' => $this->_dbConfig['name'], 'table' => $tableName));
                    $table_constraints = $q->fetchAll(\PDO::FETCH_ASSOC);

                    $required = array(
                        $table => $pk,
                        $relatedTable => $relatedColumn
                    );

                    foreach($table_constraints as $existing) {
                        if(array_key_exists($existing['column_name']. '_id', $required)) {
                            unset($required[$existing['column_name']]);
                        }
                    }

                    if(!empty($required)) {
                        //create keys
                        $missingReferences = array();

                        foreach($required as $referencedTableName => $referencedColumnName) {
                            $missingReferences[] = 'ADD FOREIGN KEY ('. $referencedTableName .'_id)
                            REFERENCES ' . $referencedTableName . '('. $referencedColumnName .')';
                        }

                        $missingReferences = implode(', ', $missingReferences);

                        $query = $this->_link->prepare("ALTER TABLE $tableName $missingReferences");
                        $query->execute();
                    }
                }
            }

            return true;

        } catch(\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    protected function prepareOrder($order, $available_fields) {
        if(!empty($order)) {
            $prepare = array();
            if(!is_array($order)) {
                $order = array($order);
            }
            foreach($order as $idx => $field) {
                $fieldParts = explode(' ', $field);
                if(array_search($fieldParts[0], $available_fields) !== false) {
                    $prepare[] = $field;
                }
            }
            if(!empty($prepare)) {
                return ' ORDER BY ' . implode(', ', $prepare);
            }
        }
        return '';
    }

    protected function prepareWhere($conditions, &$values, $suffix = '') {
        $prepare = ' WHERE 1=1';

        foreach($conditions as $key => $value) {

            if(is_array($value)) {
                $operator = $value['operator'];
                if(isset($value['value']) && is_array($value['value'])) {
                    $collection = array();

                    foreach($value['value'] as $singleValue) {
                        $values[$this->paramStr($key, $suffix)] = $singleValue;

                        $collection[] = $this->paramStr($key, $suffix);

                        if(is_numeric($suffix)) {
                            $suffix++;
                        }
                    }

                    $collection = '(' . implode(', ', $collection) . ')';
                } else {
                    if(!array_key_exists($operator, $this->_noValueOperators) && !empty($value['value'])) {
                        $values[$this->paramStr($key, $suffix)] = $value['value'];
                    }
                }
            } else {
                $operator = '=';
                $values[$this->paramStr($key, $suffix)] = $value;
            }

            if(isset($value['join']) && $value['join'] == 'OR') {
                $this->whereOr(
                    $prepare,
                    $key,
                    $operator,
                    $suffix
                );
            } else {
                $this->whereAnd(
                    $prepare,
                    $key,
                    $operator,
                    $suffix
                );
            }

            if(isset($collection)) {
                $prepare .= $collection;
            }

            if(is_numeric($suffix)) {
                $suffix++;
            }
        }

        return $prepare;
    }

    protected function whereAnd(&$prepare, $col, $operator, $suffix) {
        return $this->where($prepare, $col, $operator, 'AND', $suffix);
    }

    protected function whereOr(&$prepare, $col, $operator, $suffix) {
        return $this->where($prepare, $col, $operator, 'OR', $suffix);
    }

    protected function where(&$prepare, $col, $operator, $join, $suffix) {
        if(!array_key_exists($operator, $this->_noValueOperators)) {
            $param = $this->paramStr($col, $suffix);
        }
        $args = compact('join', 'col', 'operator', 'param');
        $prepare .= ' ' . implode(' ', $args);

        return $this;
    }

    protected function joinLeft(&$prepare, $table_alias, $on) {
        return $this->join($prepare, $table_alias, $on, 'LEFT JOIN');
    }

    protected function joinInner(&$prepare, $table_alias, $on) {
        return $this->join($prepare, $table_alias, $on, 'INNER JOIN');
    }

    protected function join(&$prepare, $table_alias, $on, $join) {
        $on = 'ON ' . $on;
        $args = compact('join', 'table_alias', 'on');
        $prepare .= ' ' . implode(' ', $args);

        return $this;
    }

    protected function paramStr($key, $suffix = '') {
        return ':' . preg_replace('/[^a-z_]/i', '', $key) . $suffix;
    }

    protected function extractValues(array $array) {
        $return = array();
        foreach($array as $idx => $item) {
            foreach($item as $key => $value) {
                $return[$key.$idx] = $value;
            }
        }
        return $return;
    }

    protected function extractKeyData($model) {
        if(class_exists($model) && method_exists($model, 'getTableName') && property_exists($model, 'tableDescription')) {
            $relatedTable = $model::getTableName();
            $relatedColumn = $model::$tableDescription['pk']['extra'];
        } elseif(is_array($model)) {
            $relatedTable = $model['table'];
            $relatedColumn = $model['column'];
        } else {
            return false;
        }

        return array($relatedTable, $relatedColumn);
    }
}