<?php

namespace Xjchengo\Component;

use EasyWeChat\Core\AbstractAPI;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;

class Auth extends AbstractAPI
{
    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    const PREAUTHCODE_PREFIX = 'xjchengo.component.preauthcode.';

    const API_CREATE_PREAUTHCODE = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode';

    const API_QUERY_AUTH = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth';

    const COMPONENT_LOGIN_PAGE = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage';

    public function __construct(AccessToken $accessToken)
    {
        parent::__construct($accessToken);
    }

    public function getPreAuthCode()
    {
        $key = self::PREAUTHCODE_PREFIX.$this->getAccessToken()->getAppId();

        if ($preAuthCode = $this->getCache()->fetch($key)) {
            return $preAuthCode;
        }

        $params = [
            'component_appid' => $this->getAccessToken()->getAppId(),
        ];
        $result = $this->parseJSON('json', [self::API_CREATE_PREAUTHCODE, $params]);

        $this->getCache()->save($key, $result['pre_auth_code'], $result['expires_in'] - 100);

        return $result['pre_auth_code'];
    }

    public function getComponentLoginPageUrl($redirectUri)
    {
        $query = [
            'component_appid' => $this->accessToken->getAppId(),
            'pre_auth_code' => $this->getPreAuthCode(),
            'redirect_uri' => $redirectUri,
        ];

        return self::COMPONENT_LOGIN_PAGE.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC1738);
    }

    public function queryAuth($authCode)
    {
        $params = [
            'component_appid' => $this->getAccessToken()->getAppId(),
            'authorization_code' => $authCode,
        ];
        $result = $this->parseJSON('json', [self::API_QUERY_AUTH, $params]);

        return $result;
    }

    /**
     * Set cache manager.
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Return cache manager.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache ?: $this->cache = new FilesystemCache(sys_get_temp_dir());
    }
}
