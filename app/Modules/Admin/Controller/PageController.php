<?php

namespace Portal\Modules\Admin\Controller;

use Portal\Common\Model\Page;
use Portal\Core\PortalController;
use Portal\Core\Utils;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

final class PageController extends PortalController
{
    /**
     * @url-param id
     *
     * @menu Dodaj stronę
     * @menu-order 1
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexGetAction($request, $response, $args)
    {
        $args['formAction'] = self::$_router->pathFor('admin-page', $args);

        if(isset($args['id'])) {
            $id = (int)$args['id'];
            $args['page'] = Page::get($id);

            if(empty($args['page'])) {
                $uri = self::$_router->pathFor('admin', $args);
                return $response->withRedirect($uri);
            }
        }

        $args['extensions'] = join(',', array_map(function($el) {
            return '.' . $el;
        }, Page::$allowedExtensions));

        $args['file_size_human'] = Utils::fileUploadMaxSize(true);
        $args['file_size'] = Utils::fileUploadMaxSize();

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
    protected function indexDeleteAction($request, $response, $args)
    {
        if(isset($args['id'])) {
            $id = (int)$args['id'];
            Page::delete($id);
        }

        return $this->json(array(
            'success' => true
        ));
    }

    /**
     * @url-param id
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    protected function indexPostAction($request, $response, $args)
    {
        $formVars = $request->getParsedBody();
        if(isset($args['id'])) {
            $id = (int)$args['id'];
            Page::update($id, $formVars);
        } else {
            $id = Page::save($formVars);
        }

        $args['id'] = $id;
        $args['notification'] = array('success' => 'Zapisano zmiany');

        return $this->indexGetAction($request, $response, $args);
    }

    protected function imagePostAction(Request $request, Response $response, $args) {
        $directory = self::$_config['upload_directory']['path'];
        $uploadedFiles = $request->getUploadedFiles();

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['file'];

        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        if(! in_array($extension, Page::$allowedExtensions)) {
            return $response->withStatus(415, 'Unsupported Media Type');
        }

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->_moveUploadedFile($directory, $uploadedFile);
            return $this->json($response, array('location' => self::$_config['upload_directory']['url'] . '/' . $filename));
        }

        return $response->withStatus(500);
    }

    private function _moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = md5($uploadedFile->getClientFilename());
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }
}