<?php

namespace Portal\Modules\Admin\Controller;

use Portal\Common\Model\Page;
use Portal\Core\PortalController;

final class PageController extends PortalController
{
    /**
     * @url-param id
     *
     * @menu Dodaj stronę
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexGetAction($request, $response, $args)
    {
        $args['formAction'] = self::$_router->pathFor('admin-page-save', $args);

        if(isset($args['id'])) {
            $id = (int)$args['id'];
            $args['page'] = Page::get($id);
        }

        return $args;
    }

    /**
     * @url-param id
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function savePostAction($request, $response, $args)
    {
        $formVars = $request->getParsedBody();
        if(isset($args['id'])) {
            $id = (int)$args['id'];
            Page::update($id, $formVars);
        } else {
            $id = Page::save($formVars);
        }

        $args['id'] = $id;

        $uri = self::$_router->pathFor('admin-page', $args);
        return $response->withRedirect($uri);
    }
}