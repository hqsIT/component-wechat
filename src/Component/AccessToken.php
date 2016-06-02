<?php

namespace Xjchengo\Component;

use EasyWeChat\Core\AccessToken as NormalAccessToken;
use EasyWeChat\Core\Exceptions\HttpException;
use Doctrine\Common\Cache\Cache;

class AccessToken extends NormalAccessToken
{
    protected $queryName = 'component_access_token';

    protected $verifyTicket;

    protected $prefix = 'xjchengo.component.access_token.';

    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';

    public function __construct($appId, $secret, $verifyTicket, Cache $cache = null)
    {
        parent::__construct($appId, $secret, $cache);
        $this->verifyTicket = $verifyTicket;
    }

    public function getTokenFromServer()
    {
        $params = [
            'component_appid' => $this->appId,
            'component_appsecret' => $this->secret,
            'component_verify_ticket' => $this->verifyTicket,
        ];

        $http = $this->getHttp();

        $token = $http->parseJSON($http->json(self::API_TOKEN_GET, $params));

        if (empty($token['component_access_token'])) {
            throw new HttpException('Request AccessToken fail. response: '.json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        $token['access_token'] = $token['component_access_token'];

        return $token;
    }
}
