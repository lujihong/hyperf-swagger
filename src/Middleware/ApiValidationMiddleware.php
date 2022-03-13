<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Middleware;

use FastRoute\Dispatcher;
use Hyperf\Apidoc\Exception\ApiDocException;
use Hyperf\Apidoc\Validation\ValidationApi;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Server;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiValidationMiddleware extends CoreMiddleware
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var HttpResponse
     */
    protected HttpResponse $response;

    /**
     * @var ValidationApi
     */
    protected ValidationApi $validationApi;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request, ValidationApi $validation, Server $server)
    {
        $this->response = $response;
        $this->request = $request;
        $this->validationApi = $validation;
        parent::__construct($container, $server->getServerName());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Dispatched $dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);
        if ($dispatched->status !== Dispatcher::FOUND) {
            return $handler->handle($request);
        }

        // do not check Closure
        if ($dispatched->handler->callback instanceof \Closure) {
            return $handler->handle($request);
        }

        [$controller, $action] = $this->prepareHandler($dispatched->handler->callback);

        $result = $this->validationApi->validated($controller, $action);

        if ($result !== true) {
            $config = $this->container->get(ConfigInterface::class);
            $exceptionEnable = $config->get('apidoc.exception_enable', false);
            if ($exceptionEnable) {
                $fieldErrorMessage = $config->get('apidoc.field_error_message', 'message');
                throw new ApiDocException($result[$fieldErrorMessage]);
            }
            $httpStatusCode = $config->get('apidoc.http_status_code', 400);
            return $this->response->json($result)->withStatus($httpStatusCode);
        }

        return $handler->handle($request);
    }
}
