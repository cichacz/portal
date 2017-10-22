<?php

namespace Portal\Core;

trait Singleton {
    /**
     * Przechowuje instancję klasy Singleton
     *
     * @var object
     * @access private
     */
    private static $oInstances;

    /**
     * Zwraca instancję obiektu Singleton
     *
     * @return $this
     * @access public
     * @static
     *
     * @var $config
     */
    public static function getInstance($config = null)
    {
        $class = get_called_class();
        if( !isset(self::$oInstances[$class]) )
        {
            self::$oInstances[$class] = new static($config);
        }
        return self::$oInstances[$class];
    }

    protected function __construct($config)
    {

    }
}