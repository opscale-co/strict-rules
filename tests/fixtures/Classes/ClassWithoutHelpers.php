<?php

namespace Opscale\Classes;

use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository;

class ClassWithoutHelpers
{
    private AuthManager $auth;
    private CacheManager $cache;
    private Repository $config;

    public function __construct(AuthManager $auth, CacheManager $cache, Repository $config)
    {
        $this->auth = $auth;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function someMethod()
    {
        $user = $this->auth->user();
        $data = $this->cache->get('key');
        $appName = $this->config->get('app.name');
        
        return $data;
    }
}