<?php

namespace Portal\Core;

use Interop\Container\ContainerInterface;
use Portal;
use Portal\Core\Model\PortalAuth;
use Slim\Route;

class PortalController {
    private $_templateDir;

    /* @var \Slim\Router */
    protected static $_router;

    /* @var \Slim\Views\Twig */
    protected static $_view;

    protected static $_config;

    protected static $_menu = array();

    protected static $_urlParamRegex = '/@url-param ([a-z]+)/i';
    protected static $_menuLabelRegex = '/@menu (.+)/i';

    private static $_modules = array();

    public function __construct()
    {
        $reflect = new \ReflectionClass($this);
        $moduleName = basename(dirname(dirname($reflect->getFileName())));

        $this->_templateDir = strtolower($moduleName . DIRECTORY_SEPARATOR . str_replace('Controller', '', $reflect->getShortName()));
    }

    public function __call($method, $arguments) {
        $methodSuffix = self::$_config['controller']['methodSuffix'];

        /* @var \Slim\Http\Request $request */
        $request = $arguments[0];

        /* @var \Slim\Http\Response $response */
        $response = $arguments[1];

        $class = new \ReflectionClass(get_called_class());
        if($class->isFinal() || $class->getMethod($method)->isFinal()) {
            //needs auth before any display
            if(!PortalAuth::isLoggedIn()) {
                /* @var Route $routeInfo */
                $routeInfo = $request->getAttribute('route');

                $uri = self::$_router->pathFor('', array('return' => base64_encode($routeInfo->getName())));

                return $response->withRedirect($uri);
            }
        }

        if(method_exists($this, $method) && Utils::endsWith($method, $methodSuffix)) {
            $args = call_user_func_array(array($this,$method),$arguments);

            list($methodType, $methodSimpleName) = self::splitMethodName($method);

            if(empty($methodSimpleName) || !is_array($args)) {
                return $args;
            }

            //setup menu
            $moduleName = basename(dirname(dirname($class->getFileName())));
            $args['menu'] = self::getMenu($moduleName);

            //helps in generating links for current route
            $args['portal_route'] = $request->getAttribute('route')->getName();

            return $this->render($response, $methodSimpleName, $args);
        }

        return $response;
    }

    protected final function render($response, $template, $args) {
        if(empty($args['title'])) {
            $args['title'] = $this->_templateDir;
        }

        if(empty($args['template_path'])) {
            $args['template_path'] = $this->_templateDir . DIRECTORY_SEPARATOR;
        }

        if(empty($args['template_extension'])) {
            $args['template_extension'] = self::$_config['renderer']['template_extension'];
        }

        return self::$_view->render(
            $response,
            $this->getTemplatePath($template),
            $args
        );
    }

    protected function getTemplatePath($template)
    {
        return $this->_templateDir . DIRECTORY_SEPARATOR . $template . '.' . self::$_config['renderer']['template_extension'];
    }

    protected function json($response, $data = [])
    {
        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    }

    /**
     * STATIC methods
     */

    public static function setup(ContainerInterface $container)
    {
        $settings = $container->get('settings');

        static::$_router = $container->get('router');
        static::$_view = $container['view'];
        static::$_config = $settings;
    }

    public static function getRouteName($route)
    {
        $route = preg_replace('/\[.*\]/i', '', $route);
        return Utils::nameToKey($route);
    }

    public static function getModules()
    {
        return self::$_modules;
    }

    public static function getControllerInfo($controllerName)
    {
        $controllerSimpleName = basename($controllerName, 'Controller.php');
        $controllerNamespace = str_ireplace(array(dirname(__DIR__) . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'), array('','\\',''), $controllerName);
        $controller =  Portal::class . '\\' . $controllerNamespace;

        if(class_exists($controller)) {
            $moduleName = basename(dirname(dirname($controllerName)));

            $class = new \ReflectionClass($controller);
            $methods = $class->getMethods();

            $methodSuffix = self::$_config['controller']['methodSuffix'];

            $routes = array();
            foreach($methods as $methodObj) {
                $methodName = $methodObj->name;

                if (Utils::endsWith($methodName, $methodSuffix)) {
                    list($methodType, $methodSimpleName) = self::splitMethodName($methodName);

                    if(empty($methodType) || empty($methodSimpleName)) {
                        continue;
                    }

                    $getAction = '/';
                    if(strcasecmp($moduleName, 'index') !== 0 || strcasecmp($controllerSimpleName, 'index') !== 0 || strcasecmp($methodSimpleName, 'index') !== 0) {
                        $getAction = Utils::nameToUrl($moduleName);
                        $isModuleIndex = true;

                        if(strcasecmp($controllerSimpleName, 'index') !== 0) {
                            $getAction .= Utils::nameToUrl($controllerSimpleName);
                            $isModuleIndex = false;
                        }

                        if(strcasecmp($methodSimpleName, 'index') !== 0) {
                            $getAction .= Utils::nameToUrl($methodSimpleName);
                            $isModuleIndex = false;
                        }

                        if($isModuleIndex) {
                            //controller's main method, register as module
                            self::$_modules[$moduleName] = $getAction;
                        }

                        $getAction .= '/';
                    }

                    /**
                     * MAGIC!!
                     * read comments to enable custom params in url :)
                     */
                    $docComment = $methodObj->getDocComment();
                    preg_match_all(self::$_urlParamRegex, $docComment, $params);
                    preg_match(self::$_menuLabelRegex, $docComment, $menu);

                    $params = $params[1];
                    if(!empty($params)) {
                        $getAction .= self::prepareRouteParams($params, $params[0] === $methodSimpleName);
                    }

                    $route = array($getAction, $controller . ':' . $methodName);
                    $routes[strtolower($methodType)][] = $route;

                    if(!empty($menu) && strtolower($methodType) == 'get') {
                        self::$_menu[$moduleName][$menu[1]] = PortalController::getRouteName($route[0]);
                    }
                }
            }

            return array(
                'routes' => $routes
            );
        }

        return false;
    }

    protected static function splitMethodName($methodName) {
        $methodSuffix = self::$_config['controller']['methodSuffix'];

        preg_match('/(.*)([A-Z].*)'.$methodSuffix.'/', $methodName, $matches);

        if(count($matches) != 3) {
            return false;
        }

        $methodType = $matches[2];//preg_replace('/.*([A-Z].*)'.$methodSuffix.'/','$1', $methodName);
        $methodSimpleName = $matches[1];

        return array(
            $methodType,
            $methodSimpleName
        );
    }

    protected static function prepareRouteParams($params, $methodAsName = false)
    {
        if(empty($params)) {
            return '';
        }

        $param = array_shift($params);
        if($methodAsName) {
            return '[{' . $param . '}/]';
        }

        return '[' . $param . '/{' . $param . '}/'. self::prepareRouteParams($params) .']';
    }

    protected static function getMenu($module)
    {
        if(!array_key_exists($module, self::$_menu)) {
            return array();
        }
        return self::$_menu[$module];
    }
}