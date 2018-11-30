<?php

namespace App\Controller;

use App\Message\DefaultMessage;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Default Controller.
 */
class DefaultController extends BaseController
{
    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->logger = $container->get('logger');
    }

    /**
     * Get Help.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getHelp($request, $response, $args)
    {
        $this->setParams($request, $response, $args);
//        $protocol = ($_SERVER['HTTP_X_FORWARDED_PORT'] == 443) ? "https://" : "http://";
//        $domainName = $_SERVER['HTTP_HOST'] . '/';
//        $url = $protocol . $domainName;
        $url = '/';
        $message = [
            'tasks' => $url . 'api/v1/tasks',
            'users' => $url . 'api/v1/users',
            'status' => $url . 'status',
            'this help' => $url . '',
        ];

        return $this->jsonResponse('success', $message, 200);
    }

    /**
     * Get Api Status.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getStatus($request, $response, $args)
    {
        $this->setParams($request, $response, $args);
        $status = [
            'status' => 'OK',
            'version' => DefaultMessage::API_VERSION,
            'timestamp' => time(),
        ];

        return $this->jsonResponse('success', $status, 200);
    }
}
