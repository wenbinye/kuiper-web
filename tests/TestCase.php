<?php

declare(strict_types=1);

namespace kuiper\web;

use Laminas\Diactoros\ServerRequestFactory;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createContainer()
    {
        return $this->container = Mockery::mock(ContainerInterface::class);
    }

    protected function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new \BadMethodCallException('call createContainer first');
        }

        return $this->container;
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->createContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
        \Mockery::close();
    }

    public function createRequest($req): ServerRequestInterface
    {
        [$method, $url] = explode(' ', $req, 2);
        $result = parse_url($url);
        if (isset($result['host'])) {
            $host = $result['host'].(isset($result['port']) ? ':'.$result['port'] : '');
        } else {
            $host = 'localhost';
        }

        return (new ServerRequestFactory())
            ->createServerRequest($method, sprintf('%s://%s%s', $result['scheme'] ?? 'http', $host, $result['path']));
    }
}
