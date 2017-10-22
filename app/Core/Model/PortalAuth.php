<?php

namespace Portal\Core\Model;

use Portal\Core\PortalDb;
use Portal\Core\PortalModel;

class PortalAuth extends PortalModel {
    protected static $_tableName = 'prt_users';

    public static $tableDescription = array(
        'id' => array(
            'column_name' => 'id',
            'column_type' => 'int(10) unsigned',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => 'auto_increment'
        ),
        'login' => array(
            'column_name' => 'login',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => ''
        ),
        'pass' => array(
            'column_name' => 'pass',
            'column_type' => 'varchar(255)',
            'column_default' => null,
            'is_nullable' => PortalDb::NOT_NULLABLE,
            'extra' => ''
        ),
        'superadmin' => array(
            'column_name' => 'superadmin',
            'column_type' => 'tinyint(1)',
            'column_default' => null,
            'is_nullable' => PortalDb::NULLABLE,
            'extra' => ''
        ),
        'pk' => array(
            'column_name' => 'PRIMARY KEY',
            'extra' => 'id'
        ),
    );

    public static function init()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_name('PSID');
            session_start();
        }
    }

    public static function login($user, $pass)
    {
        $user = strip_tags($user);
        $pass = strip_tags($pass);

        $user = preg_replace('/[^0-9a-z_.@]/i', '', $user);
        $pass = preg_replace('/[\s\X]/i', '', $pass);

        $user = self::get(null, true, array(
            'login' => $user,
            'pass' => md5($pass)
        ));

        if($user !== false) {
            $_SESSION['user'] = $user;
        }

        return $user;
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
    }

    public static function isLoggedIn()
    {
        return !empty($_SESSION['user']);
    }

    public static function isSuperAdmin()
    {
        return !empty($_SESSION['user']) ? $_SESSION['user']->superadmin : false;
    }

    public static function currentUser()
    {
        return $_SESSION['user'];
    }

    public static function currentUserId()
    {
        return $_SESSION['user']->id;
    }
}