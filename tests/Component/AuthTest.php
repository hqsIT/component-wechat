<?php

use Xjchengo\Component\Auth;

class AuthTest extends TestCase
{
    public function getMockAccessToken()
    {
        return Mockery::mock('Xjchengo\Component\AccessToken');
    }

    public function getMockCache()
    {
        return Mockery::mock('Doctrine\Common\Cache\Cache');
    }

    public function getPartialMockAuth()
    {
        return Mockery::mock('Xjchengo\Component\Auth[parseJSON]', [$this->getMockAccessToken()]);
    }

    public function testConstruct()
    {
        $reflection = new ReflectionClass('Xjchengo\Component\Auth');
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('Xjchengo\Component\AccessToken', $params[0]->getClass()->name);
    }

    public function testCacheSetterAndGetter()
    {
        $auth = new Auth($this->getMockAccessToken());

        $this->assertInstanceOf('Doctrine\Common\Cache\Cache', $auth->getCache());
        $cache = $this->getMockCache();
        $auth->setCache($cache);
        $this->assertEquals($cache, $auth->getCache());
    }

    public function testGetPreAuthCodeWithCache()
    {
        $accessToken = $this->getMockAccessToken();
        $accessToken->shouldReceive('getAppId')->andReturn('appId');
        $cache = $this->getMockCache();
        $cache->shouldReceive('fetch')->with('xjchengo.component.preauthcode.appId')->andReturn('pre_auth_code');
        $auth = new Auth($accessToken);
        $auth->setCache($cache);
        $preAuthCode = $auth->getPreAuthCode();
        $this->assertEquals('pre_auth_code', $preAuthCode);
    }

    public function testGetPreAuthCodeWithoutCache()
    {
        $auth = $this->getPartialMockAuth();
        $accessToken = $auth->getAccessToken();
        $accessToken->shouldReceive('getAppId')->andReturn('appId');
        $cache = $this->getMockCache();
        $cache->shouldReceive('fetch')->with('xjchengo.component.preauthcode.appId')->andReturn(null);
        $cache->shouldReceive('save')->andReturn(true);
        $auth->setCache($cache);
        $callParseJsonWith = [];
        $auth->shouldReceive('parseJSON')->andReturnUsing(function ($method, $params) use (&$callParseJsonWith) {
            $callParseJsonWith['method'] = $method;
            $callParseJsonWith['params'] = $params;
            return [
                'pre_auth_code' => 'pre_auth_code',
                'expires_in' => 600,
            ];
        });
        $preAuthCode = $auth->getPreAuthCode();
        $this->assertEquals('json', $callParseJsonWith['method']);
        $this->assertEquals([
            'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode',
            [
                'component_appid' => 'appId',
            ]
        ], $callParseJsonWith['params']);
        $this->assertEquals('pre_auth_code', $preAuthCode);
    }

    public function testQueryAuth()
    {
        $auth = $this->getPartialMockAuth();
        $accessToken = $auth->getAccessToken();
        $accessToken->shouldReceive('getAppId')->andReturn('appId');
        $auth->shouldReceive('parseJSON')
            ->with('json', [
                'https://api.weixin.qq.com/cgi-bin/component/api_query_auth',
                [
                    'component_appid' => 'appId',
                    'authorization_code' => 'code',
                ]
            ])
            ->andReturn(['foo' => 'bar']);
        $result = $auth->queryAuth('code');
        $this->assertEquals('bar', $result['foo']);
    }

    public function testGetComponentLoginPageUrl()
    {
        $accessToken = $this->getMockAccessToken();
        $accessToken->shouldReceive('getAppId')->andReturn('appId');
        $auth = Mockery::mock('Xjchengo\Component\Auth[getPreAuthCode]', [$accessToken]);
        $auth->shouldReceive('getPreAuthCode')->andReturn('pre_auth_code');
        $this->assertEquals(
            'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=appId&pre_auth_code=pre_auth_code&redirect_uri=xxxx',
            $auth->getComponentLoginPageUrl('xxxx')
        );
    }
}
