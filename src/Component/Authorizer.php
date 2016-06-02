<?php

namespace Xjchengo\Component;

use EasyWeChat\Core\AccessToken as NormalAccessToken;
use EasyWeChat\Core\Http;
use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\Support\Collection;
use EasyWeChat\Support\Log;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Authorizer extends NormalAccessToken
{
    protected $componentAccessToken;

    protected $tokenStore;

    const API_AUTHORIZER_TOKEN = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token';

    const API_GET_AUTHORIZER_INFO = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info';

    const API_GET_AUTHORIZER_OPTION = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option';

    const API_SET_AUTHORIZER_OPTION = 'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option';

    public function __construct(AccessToken $componentAccessToken, $appId, TokenStoreInterface $tokenStore)
    {
        parent::__construct($appId, '');
        $this->componentAccessToken = $componentAccessToken;
        $this->tokenStore = $tokenStore;
    }

    public function getTokenStore()
    {
        return $this->tokenStore;
    }

    public function getComponentAccessToken()
    {
        return $this->componentAccessToken;
    }

    public function getToken($forceRefresh = false)
    {
        $cached = $this->tokenStore->getToken($this->getAppId());

        if ($forceRefresh || empty($cached)) {
            $token = $this->getTokenFromServer();

            $this->tokenStore->storeToken($this->getAppId(), $token['authorizer_access_token'], $token['authorizer_refresh_token'], $token['expires_in']);

            return $token['authorizer_access_token'];
        }

        return $cached;
    }

    public function getTokenFromServer()
    {
        $refreshToken = $this->tokenStore->getRefreshToken($this->getAppId());

        $params = [
            'component_appid' => $this->componentAccessToken->getAppId(),
            'authorizer_appid' => $this->getAppId(),
            'authorizer_refresh_token' => $refreshToken,
        ];

        $token = $this->parseJSON('json' ,[self::API_AUTHORIZER_TOKEN, $params]);

        return $token;
    }

    public function getAuthorizerInfo()
    {
        $params = [
            'component_appid' => $this->componentAccessToken->getAppId(),
            'authorizer_appid' => $this->getAppId(),
        ];

        $info = $this->parseJSON('json' ,[self::API_GET_AUTHORIZER_INFO, $params]);

        return $info;
    }

    public function getAuthorizerOption($optionName)
    {
        $params = [
            'component_appid' => $this->componentAccessToken->getAppId(),
            'authorizer_appid' => $this->getAppId(),
            'option_name' => $optionName,
        ];

        $info = $this->parseJSON('json' ,[self::API_GET_AUTHORIZER_OPTION, $params]);

        return $info;
    }

    public function setAuthorizerOption($optionName, $optionValue)
    {
        $params = [
            'component_appid' => $this->componentAccessToken->getAppId(),
            'authorizer_appid' => $this->getAppId(),
            'option_name' => $optionName,
            'option_value' => $optionValue,
        ];

        $result = $this->parseJSON('json' ,[self::API_SET_AUTHORIZER_OPTION, $params]);

        return $result;
    }

    /**
     * Return the http instance.
     *
     * @return \EasyWeChat\Core\Http
     */
    public function getHttp()
    {
        if (is_null($this->http)) {
            $this->http = new Http();
        }

        if (count($this->http->getMiddlewares()) === 0) {
            $this->registerHttpMiddlewares();
        }

        return $this->http;
    }

    /**
     * Parse JSON from response and check error.
     *
     * @param string $method
     * @param array  $args
     *
     * @return \EasyWeChat\Support\Collection
     */
    public function parseJSON($method, array $args)
    {
        $http = $this->getHttp();

        $contents = $http->parseJSON(call_user_func_array([$http, $method], $args));

        $this->checkAndThrow($contents);

        return new Collection($contents);
    }

    /**
     * Register Guzzle middlewares.
     */
    protected function registerHttpMiddlewares()
    {
        // log
        $this->http->addMiddleware($this->logMiddleware());
        // retry
        $this->http->addMiddleware($this->retryMiddleware());
        // access token
        $this->http->addMiddleware($this->accessTokenMiddleware());
    }

    /**
     * Attache access token to request query.
     *
     * @return \Closure
     */
    protected function accessTokenMiddleware()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$this->componentAccessToken) {
                    return $handler($request, $options);
                }

                $field = $this->componentAccessToken->getQueryName();
                $token = $this->componentAccessToken->getToken();

                $request = $request->withUri(Uri::withQueryValue($request->getUri(), $field, $token));

                return $handler($request, $options);
            };
        };
    }

    /**
     * Log the request.
     *
     * @return \Closure
     */
    protected function logMiddleware()
    {
        return Middleware::tap(function (RequestInterface $request, $options) {
            Log::debug("Request: {$request->getMethod()} {$request->getUri()} ".json_encode($options));
            Log::debug('Request headers:'.json_encode($request->getHeaders()));
        });
    }

    /**
     * Return retry middleware.
     *
     * @return \Closure
     */
    protected function retryMiddleware()
    {
        return Middleware::retry(function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null
        ) {
            // Limit the number of retries to 2
            if ($retries <= 2 && $response && $body = $response->getBody()) {
                // Retry on server errors
                if (stripos($body, 'errcode') && (stripos($body, '40001') || stripos($body, '42001'))) {
                    $field = $this->componentAccessToken->getQueryName();
                    $token = $this->componentAccessToken->getToken(true);

                    $request = $request->withUri($newUri = Uri::withQueryValue($request->getUri(), $field, $token));

                    Log::debug("Retry with Request Token: {$token}");
                    Log::debug("Retry with Request Uri: {$newUri}");

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Check the array data errors, and Throw exception when the contents contains error.
     *
     * @param array $contents
     *
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function checkAndThrow(array $contents)
    {
        if (isset($contents['errcode']) && 0 !== $contents['errcode']) {
            if (empty($contents['errmsg'])) {
                $contents['errmsg'] = 'Unknown';
            }

            throw new HttpException($contents['errmsg'], $contents['errcode']);
        }
    }
}
