<?php

namespace Xjchengo\Foundation;

use EasyWeChat\Foundation\Application as BaseApplication;
use Doctrine\Common\Cache\FilesystemCache;
use EasyWeChat\Core\Http;
use EasyWeChat\Support\Log;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Xjchengo\Component\AccessToken as ComponentAccessToken;
use Xjchengo\Component\Auth as ComponentAuth;
use Xjchengo\Component\Authorizer;
use EasyWeChat\Foundation\ServiceProviders\OAuthServiceProvider as NormalOAuthServiceProvider;

class Application extends BaseApplication
{
    public function __construct($config)
    {

        $this->replaceOAuthServiceProvider();
        parent::__construct($config);
        $this->registerAccessToken();
    }

    protected function replaceOAuthServiceProvider()
    {
        foreach($this->providers as $key => $provider) {
            if ($provider == NormalOAuthServiceProvider::class) {
                $this->providers[$key] = ServiceProviders\OAuthServiceProvider::class;
            }
        }
    }

    protected function registerAccessToken()
    {
        $this['component_access_token'] = function () {
            return new ComponentAccessToken(
                $this['config']['component_app_id'],
                $this['config']['component_secret'],
                $this['config']['component_verify_ticket'],
                $this['cache']
            );
        };

        $this['component_auth'] = function () {
            return new ComponentAuth(
                $this['component_access_token']
            );
        };

        $this['access_token'] = function () {
            return new Authorizer(
                $this['component_access_token'],
                $this['config']['app_id'],
                $this['config']['token_store']
            );
        };
    }
}
