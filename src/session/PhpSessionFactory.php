<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Http\Message\ServerRequestInterface;

class PhpSessionFactory implements SessionFactoryInterface
{
    /**
     * @var bool
     */
    private $autoStart;

    public function __construct(array $options = [])
    {
        if (isset($options['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
        }
        if (isset($options['cookie_name'])) {
            ini_set('session.name', $options['cookie_name']);
        }
        $this->autoStart = (bool) ($options['auto_start'] ?? ini_get('session.auto_start'));
    }

    public function create(ServerRequestInterface $request): SessionInterface
    {
        return new PhpSession($this->autoStart);
    }
}
