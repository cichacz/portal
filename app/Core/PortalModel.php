<?php

namespace Portal\Core;

use Portal\Core\Model\PortalAuth;

abstract class PortalModel {

    protected static $_tableName;

    public static function getTableName() {
        $class = get_called_class();
        if(property_exists($class, '_tableName')) {
            return $class::$_tableName;
        }
        return self::$_tableName;
    }

    public static function getTableNameWithAlias($prefix) {
        $class = get_called_class();
        if(property_exists($class, '_tableName')) {
            return $class::$_tableName . ' ' . $prefix . substr($class::$_tableName, 4, 2);
        }
        return self::$_tableName;
    }

    public static function getTableAlias($prefix) {
        $class = get_called_class();
        if(property_exists($class, '_tableName')) {
            return $prefix . substr($class::$_tableName, 4, 2);
        }
        return self::$_tableName;
    }

    public static function getPrimaryKey() {
        $class = get_called_class();
        if(property_exists($class, 'tableDescription') && array_key_exists('pk', $class::$tableDescription)) {
            return $class::$tableDescription['pk']['extra'];
        }
        return false;
    }

    /**
     * @param null $id
     * @param bool $getRelated
     * @param array $conditions
     * @param array $fields
     * @return $this
     */
    public static function get($id = null, $getRelated = false, array $conditions = array(), array $fields = array()) {
        if($id !== null) {
            /* @var \Portal\Core\PortalModel $class */
            $class = get_called_class();

            $pk = $class::getPrimaryKey();
            $conditions[$class::getTableAlias('frm_') . '.' .$pk] = $id;
        }

        return reset(self::getList($getRelated, $conditions, $fields, 1));
    }

    public static function getList($getRelated = false, array $conditions = array(), array $fields = array(), $limit = null, $order = null, $withDeleted = false) {
        /* @var \Portal\Core\PortalModel $class */
        $class = get_called_class();
        $joins = array();

        if(property_exists($class, 'relatedObjects')) {
            $relations = $class::$relatedObjects;
        }

        if($getRelated && !empty($relations)) {
            $i = 0;

            if(array_key_exists('one', $relations)) {
                /* @var \Portal\Core\PortalModel $model */
                foreach($relations['one'] as $key => $model) {
                    if(!property_exists($model, '_tableName')) {
                        continue;
                    }
                    $joins[$model::getTableAlias('jn'.$i.'_')] = array(
                        'type' => PortalDb::LEFT_JOIN,
                        'model' => $model,
                        'table' => $model::$_tableName,
                        'on' => $class::getTableAlias('frm_') . '.' . $key .
                            '=' . $model::getTableAlias('jn'.$i.'_') . '.' . $model::getPrimaryKey()
                    );
                    $i++;
                }
            }
        }

        if(!$withDeleted && property_exists($class, 'deletedColumn')) {
            $conditions[$class::getTableAlias('frm_') . '.' .$class::$deletedColumn] = 0;
        }

        $list = PortalDb::getInstance()->get($class, $conditions, $joins, array(), $fields, $limit, $order);

        if($getRelated && isset($relations) && array_key_exists('many', $relations)) {
            $ids = array();
            /* @var \Portal\Core\PortalModel $item */
            foreach($list as $item) {
                $ids[] = $item->{$item::getPrimaryKey()};
            }

            $relatedGrouped = array();

            /* @var \Portal\Core\PortalModel $model */
            foreach($relations['many'] as $tableName => $model) {
                if(!property_exists($model, '_tableName')) {
                    continue;
                }

                $joins = array();
                $pk = $model::getPrimaryKey();

                if(property_exists($model, 'relatedObjects')) {
                    $relations = $model::$relatedObjects;
                }

                if(!empty($relations)) {
                    $i = 0;

                    if(array_key_exists('one', $relations)) {
                        /* @var \Portal\Core\PortalModel $model */
                        foreach($relations['one'] as $key => $relatedModel) {
                            if(!property_exists($relatedModel, '_tableName')) {
                                continue;
                            }
                            $joins[$relatedModel::getTableAlias('jn'.$i.'_')] = array(
                                'type' => PortalDb::LEFT_JOIN,
                                'model' => $relatedModel,
                                'table' => $relatedModel::$_tableName,
                                'on' => $model::getTableAlias('frm_') . '.' . $key .
                                    '=' . $relatedModel::getTableAlias('jn'.$i.'_') . '.' . $relatedModel::getPrimaryKey()
                            );
                            $i++;
                        }
                    }
                }

                $joins[$model::getTableAlias('jn_')] = array(
                    'type' => PortalDb::INNER_JOIN,
                    'model' => $tableName,
                    'table' => $tableName,
                    'on' => $model::getTableAlias('frm_') . '.' . $pk .
                        '=' . $model::getTableAlias('jn_') . '.' . $model::getTableName() . '_id'
                );

                $conditions = array();
                if(!$withDeleted && property_exists($model, 'deletedColumn')) {
                    $conditions[$model::getTableAlias('frm_') . '.' .$model::$deletedColumn] = 0;
                }

                $related = PortalDb::getInstance()->get($model, $conditions, $joins, []);

                $reflect = new \ReflectionClass($model);
                $modelShortName = strtolower($reflect->getShortName());

                foreach($related as $item) {
                    $relatedGrouped[$item->{$class::getTableName() . '_id_' . $tableName}][$modelShortName][] = $item;
                }
            }

            foreach($list as &$item) {
                $key = $item->{$item::getPrimaryKey()};
                if(array_key_exists($key, $relatedGrouped)) {
                    foreach($relatedGrouped[$key] as $type => $items) {
                        $item->{$type.'s'} = $items;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * @param PortalModel $relatedModel
     * @param array $conditions
     * @param array $fields
     * @param null $limit
     * @param null $order
     * @return array|bool
     */
    public static function getListWithRelatedCount($relatedModel, array $conditions = array(), array $fields = array(), $limit = null, $order = null) {
        /* @var \Portal\Core\PortalModel $class */
        $class = get_called_class();
        $subqueries = array();

        if(!class_exists($relatedModel)) {
            return false;
        }

        $reflect = new \ReflectionClass($relatedModel);
        $modelShortName = strtolower($reflect->getShortName());

        if(property_exists($class, 'relatedObjects')) {
            $relatedObjects = $class::$relatedObjects;

            if(!empty($relatedObjects['many'])) {
                $tableName = array_search($relatedModel, $relatedObjects['many']);
                if($tableName !== false) {
                    $pk = $class::getPrimaryKey();
                    $relatedPk = $relatedModel::getPrimaryKey();
                    $relatedTable = $relatedModel::getTableName();
                    $table = $class::getTableAlias('frm_');
                    $relatedWhere = self::baseWhere($relatedModel);

                    $subqueries[$modelShortName] = 'SELECT COUNT(tt.id)
                        FROM ' . $tableName. ' tt
                        INNER JOIN '. $relatedTable .' rr ON rr.'. $relatedPk .' = '. $relatedTable .'_id
                        WHERE ' . $class::getTableName() . '_id = ' . $table . '.' . $pk . $relatedWhere;
                }
            }
        }

        if(property_exists($relatedModel, 'relatedObjects')) {
            $relations = $relatedModel::$relatedObjects;

            foreach($relations as $cols) {
                foreach($cols as $key => $model) {
                    if($model == $class
                        && method_exists($model, 'getPrimaryKey')
                        && method_exists($relatedModel, 'getPrimaryKey')
                        && method_exists($relatedModel, 'getTableName')
                        && method_exists($model, 'getTableAlias')
                    ) {
                        $pk = $model::getPrimaryKey();
                        $relatedPk = $relatedModel::getPrimaryKey();
                        $relatedTable = $relatedModel::getTableName();
                        $table = $model::getTableAlias('frm_');
                        $relatedWhere = self::baseWhere($relatedModel);

                        $subqueries[$modelShortName] = 'SELECT COUNT('. $relatedPk .')
                        FROM ' . $relatedTable . '
                        WHERE ' . $key . '=' . $table . '.' . $pk . $relatedWhere;
                    }
                }
            }
        }

        if(property_exists($class, 'deletedColumn') && !array_key_exists($class::$deletedColumn, $conditions)) {
            $conditions[$class::$deletedColumn] = 0;
        }

        return PortalDb::getInstance()->get($class, $conditions, array(), $subqueries, $fields, $limit, $order);
    }

    public static function save(array $items, $onDuplicate = null) {
        /* @var \Portal\Core\PortalModel $class */
        $class = get_called_class();
        $firstItem = reset($items);

        if(!is_array($firstItem)) {
            $items = array($items);
        }

        if(property_exists($class, 'relatedObjects')) {
            $related = $class::$relatedObjects;
            if(!empty($related['many'])) {
                $manyRelatedItems = array();
                foreach($items as &$item) {
                    /* @var \Portal\Core\PortalModel $model */
                    foreach($related['many'] as $tableName => $model) {
                        if(array_key_exists($tableName, $item)) {
                            $ids = array();
                            foreach($item[$tableName] as $id) {
                                $ids[] = array(
                                    $model::getTableName() . '_id' => $id
                                );
                            }
                            $manyRelatedItems[$tableName] = $ids;
                            unset($item[$tableName]);
                        }
                    }
                }
            }
        }

        $insertedId = PortalDb::getInstance()->save(get_called_class(), $items, $onDuplicate);

        if(isset($manyRelatedItems) && !empty($manyRelatedItems)) {
            foreach($manyRelatedItems as $tableName => $items) {
                foreach($items as &$item) {
                    $item[$class::getTableName() . '_id'] = $insertedId;
                }
                PortalDb::getInstance()->save($tableName, $items);
            }
        }

        return $insertedId;
    }

    public static function update($id = null, array $values, array $conditions) {
        $class = get_called_class();

        if($id !== null) {
            if(method_exists($class, 'getPrimaryKey')) {
                $pk = $class::getPrimaryKey();
            }
            $conditions[$pk] = $id;
        }

        return PortalDb::getInstance()->update($class, $values, $conditions);
    }

    public static function delete($id = null, array $conditions) {
        $class = get_called_class();

        if($id !== null) {
            if(method_exists($class, 'getPrimaryKey')) {
                $pk = $class::getPrimaryKey();
            }
            $conditions[$pk] = $id;
        }

        if(property_exists($class, 'deletedColumn')) {
            //update status instead of deleting
            return PortalDb::getInstance()->update($class, array(
                $class::$deletedColumn => 1
            ), $conditions);
        }

        return PortalDb::getInstance()->delete($class, $conditions);
    }

    public static function updateSchema() {
        return PortalDb::getInstance()->updateSchema(get_called_class());
    }

    public static function removeExisting(array $data, $cb) {
        $dbItems = self::getList(false, [], [], null, null, true);

        if(!is_callable($cb)) {
            return $data;
        }

        foreach($data as $key => $item) {
            $unique = $cb($item, $dbItems);
            if($unique == false) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected static function baseWhere($model)
    {
        $where = '';

        if(property_exists($model, 'userColumn') && !PortalAuth::isSuperAdmin()) {
            $where = ' AND ' . $model::$userColumn . '=' . PortalAuth::currentUserId();
        }

        if(property_exists($model, 'deletedColumn')) {
            $where .= ' AND ' . $model::$deletedColumn . '=0';
        }

        return $where;
    }

    public function __construct($args = array())
    {
        if(empty($args)) {
            return;
        }
        foreach($args as $argName => $model) {
            if(!isset($this->$model)) {
                $class = new \ReflectionClass(get_called_class());
                $ns = $class->getNamespaceName();
                $clsName = $ns . '\\' . $model;
                if(!class_exists($clsName)) {
                    $clsName = Model::class . '\\' . $model;
                }
                if(!class_exists($clsName)) {
                    continue;
                }
                $this->$model = new $clsName();
            }

            if(isset($this->$argName)) {
                $newArg = str_ireplace('_' . $model, '', $argName);
                $this->$model->$newArg = $this->$argName;
                unset($this->$argName);
            }
        }
    }

    public function toArray()
    {
        $class = get_called_class();
        if(property_exists($class, 'tableDescription') && array_key_exists('pk', $class::$tableDescription)) {
            $fieldsArray = array();
            foreach($class::$tableDescription as $key => $data) {
                if(isset($this->$key)) {
                    $fieldsArray[$key] = $this->$key;
                }
            }
            return $fieldsArray;
        }
        return false;
    }
}