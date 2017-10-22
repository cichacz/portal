<?php

namespace Portal\Modules\Admin\Controller;

use Portal\Common\Model\Page;
use Portal\Core\Curl;
use Portal\Core\Model\PortalAuth;
use Portal\Core\PortalController;
use Portal\Core\PortalModel;

class IndexController extends PortalController {

    /**
     * @menu Lista stron
     *
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

        $args['pages'] = Page::getList();

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

    protected final function logoutGetAction($request, $response, $args) {
        PortalAuth::logout();
        $uri = self::$_router->pathFor('admin');
        return $response->withRedirect($uri);
    }
}