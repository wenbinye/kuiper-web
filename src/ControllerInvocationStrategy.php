<?php

declare(strict_types=1);

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

class ControllerInvocationStrategy implements RequestHandlerInvocationStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(callable $callable, ServerRequestInterface $request, ResponseInterface $response, array $routeArguments): ResponseInterface
    {
        if (is_array($callable) && $callable[0] instanceof ControllerInterface) {
            /** @var ControllerInterface $controller */
            $controller = $callable[0];
            $controller->setRequest($request);
            $controller->setResponse($response);
            $initResult = $controller->initialize();
            if (isset($initResult)) {
                if (false === $initResult) {
                    return $controller->getResponse();
                }

                if ($initResult instanceof ResponseInterface) {
                    return $initResult;
                }

                throw new \BadMethodCallException(get_class($controller).'::initialize should return false or '.ResponseInterface::class.', got '.gettype($initResult));
            }
            $result = $callable(...array_values($routeArguments));
            if (!isset($result)) {
                return $controller->getResponse();
            }
            if ($result instanceof ResponseInterface) {
                return $result;
            }

            throw new \BadMethodCallException(get_class($controller).'::'.$callable[1].' should return null or '.ResponseInterface::class.', got '.gettype($initResult));
        }

        return $callable($request, $response, ...array_values($routeArguments));
    }
}
