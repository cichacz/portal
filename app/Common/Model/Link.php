<?php

namespace Portal\Common\Model;

use Portal\Core\PortalDb;
use Portal\Core\PortalModel;
use Portal\Core\Utils;

class Link extends PortalModel {
    protected static $_tableName = 'prt_links';

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
        'url' => array(
            'column_name' => 'url',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => ''
        ),
        'pk' => array(
            'column_name' => 'PRIMARY KEY',
            'extra' => 'id'
        ),
    );
}