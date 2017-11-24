<?php

namespace Portal\Common\Model;

use Portal\Core\PortalDb;
use Portal\Core\PortalModel;
use Portal\Core\Utils;

class Page extends PortalModel {
    protected static $_tableName = 'prt_pages';

    public static $tableDescription = array(
        'id' => array(
            'column_name' => 'id',
            'column_type' => 'int(10) unsigned',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => 'auto_increment'
        ),
        'title' => array(
            'column_name' => 'title',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => ''
        ),
        'slug' => array(
            'column_name' => 'slug',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => ''
        ),
        'color' => array(
            'column_name' => 'color',
            'column_type' => 'varchar(20)',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'text_color' => array(
            'column_name' => 'text_color',
            'column_type' => 'varchar(20)',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'image' => array(
            'column_name' => 'image',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'logo' => array(
            'column_name' => 'logo',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'content' => array(
            'column_name' => 'content',
            'column_type' => 'text',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'pk' => array(
            'column_name' => 'PRIMARY KEY',
            'extra' => 'id'
        ),
    );

    public static $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

    public static function save(array $items, $onDuplicate = null)
    {
        $firstItem = reset($items);

        if(!is_array($firstItem)) {
            $items = array($items);
        }

        foreach($items as &$page) {
            $page['slug'] = Utils::nameToKey($page['title']);
        }

        return parent::save($items, $onDuplicate);
    }

    public static function update($id = null, array $values, array $conditions = array())
    {
        $values['slug'] = Utils::nameToKey($values['title']);
        return parent::update($id, $values, $conditions);
    }

    public static function get($id = null, $getRelated = false, array $conditions = array(), array $fields = array())
    {
        $item = parent::get($id, $getRelated, $conditions, $fields);
        if(!empty($item)) {
            $item->content = htmlspecialchars_decode($item->content, ENT_QUOTES);
        }

        return $item;
    }
}