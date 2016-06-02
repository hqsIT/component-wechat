<?php

namespace Xjchengo\Foundation\ServiceProviders;

use Xjchengo\Component\SocialiteManager as Socialite;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Xjchengo\Component\SocialiteProvider;

class OAuthServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['oauth'] = function ($pimple) {
            $callback = $this->prepareCallbackUrl($pimple);
            $scopes = $pimple['config']->get('oauth.scopes', []);
            $socialite = new Socialite(
                [
                    'component_wechat' => [
                        'client_id' => $pimple['config']['app_id'],
                        'component_access_token' => $pimple['component_access_token'],
                        'redirect' => $callback,
                    ],
                ]
            );
            $socialite->extend('component_wechat', function ($config) use ($socialite) {
                return new SocialiteProvider(
                    $socialite->getRequest(),
                    $config['component_wechat']['client_id'],
                    $config['component_wechat']['component_access_token'],
                    $config['component_wechat']['redirect']
                );
            });
            $driver = $socialite->driver('component_wechat');

            if (!empty($scopes)) {
                $driver->scopes($scopes);
            }

            return $driver;
        };
    }

    private function prepareCallbackUrl($pimple)
    {
        $callback = $pimple['config']->get('oauth.callback');
        if (0 === stripos($callback, 'http')) {
            return $callback;
        }
        $baseUrl = $pimple['request']->getSchemeAndHttpHost();

        return $baseUrl.'/'.ltrim($callback, '/');
    }
}
