<?php

declare(strict_types=1);

namespace kuiper\web\security;

use kuiper\web\session\SessionInterface;

class Auth implements AuthInterface
{
    /**
     * 为降低用户在页面使用（ajax 调用）时出现过期情况，加入 regenerate_after 设置
     * regenerate_after 比实际过期时间要提前 300 秒（如果 lifetime/5 小于300s，则使用 lifetime/5）
     * 当用户进入页面时，如果到了 regenerate_after 时间，则需要重新登录.
     */
    private const REGENERATE_AFTER = '__gc_time';

    /**
     * session 数据中记录 auth 信息 key 值
     *
     * @var string
     */
    private $sessionKey;

    /**
     * session 数据.
     *
     * @var array
     */
    private $sessionData;

    /**
     * session 组件.
     *
     * @var SessionInterface
     */
    private $session;

    /**
     * 是否需要重新生成 session.
     *
     * @var bool
     */
    private $needRegenerate = false;

    /**
     * @var int
     */
    private $lifetime;

    public function __construct(SessionInterface $session, $sessionKey = 'auth:id', $lifetime = null)
    {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->lifetime = $lifetime;
        $this->sessionData = $this->session->get($this->sessionKey);
        if (isset($this->sessionData) && is_array($this->sessionData)) {
            $now = time();
            $discardTime = $this->sessionData[self::REGENERATE_AFTER] ?? $now;
            $this->needRegenerate = ($now >= $discardTime);
        } else {
            $this->sessionData = [];
        }
    }

    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->sessionData[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->sessionData[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->sessionData[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->sessionData[$offset]);
    }

    public function __get($name)
    {
        return $this->sessionData[$name] ?? null;
    }

    public function __set($name, $value)
    {
        if (isset($this->sessionData[$name])) {
            $this->sessionData[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->sessionData[$name]);
    }

    public function getIdentity(): array
    {
        return $this->sessionData;
    }

    /**
     * 用户登录操作.
     *
     * @param mixed $identity 用户数据
     */
    public function login(array $identity): void
    {
        if ($this->needRegenerate) {
            $this->session->regenerateId(true);
        }
        foreach ($identity as $name => $val) {
            $this->sessionData[$name] = $val;
        }
        $lifetime = $this->lifetime ?? (int) ini_get('session.cookie_lifetime');
        $this->sessionData[self::REGENERATE_AFTER] = time() + $lifetime - min($lifetime * 0.2, 300);
        $this->session->set($this->sessionKey, $this->sessionData);
    }

    /**
     * 用户注销操作.
     *
     * @param bool $destroySession
     */
    public function logout($destroySession = true): void
    {
        if ($destroySession) {
            $this->session->destroy();
        } else {
            $this->session->set($this->sessionKey, false);
        }
        $this->sessionData = [];
    }

    /**
     * 判断用户是否登录.
     */
    public function isGuest(): bool
    {
        return empty($this->sessionData);
    }

    /**
     * 判断用户是否需要重新登录.
     */
    public function isNeedLogin(): bool
    {
        return $this->isGuest() || $this->needRegenerate;
    }
}
