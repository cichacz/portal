<?php

namespace Portal\Modules\Admin\Controller;

use Portal\Core\Curl;
use Portal\Core\Model\PortalAuth;
use Portal\Core\PortalController;

class IndexController extends PortalController {

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexGetAction($request, $response, $args) {
        if(!PortalAuth::isLoggedIn()) {
            $uri = self::$_router->pathFor('admin-login');
            return $response->withRedirect($uri);
        }

        return $args;
    }

    /**
     * @url-param return
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function loginGetAction($request, $response, $args) {
        $args['formAction'] = self::$_router->pathFor('admin-login', $args);

        $args['modules'] = PortalController::getModules();

        return $args;
    }

    /**
     * @url-param return
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function loginPostAction($request, $response, $args) {
        $formVars = $request->getParsedBody();

        PortalAuth::login($formVars['login'], $formVars['pass']);

        if(!empty($args['return'])) {
            $uri = self::$_router->pathFor(base64_decode($args['return'], true));
        } else {
            $uri = self::$_router->pathFor('admin');
        }

        return $response->withRedirect($uri);
    }

    protected function logoutGetAction($request, $response, $args) {
        PortalAuth::logout();
        $uri = self::$_router->pathFor('admin');
        return $response->withRedirect($uri);
    }
}