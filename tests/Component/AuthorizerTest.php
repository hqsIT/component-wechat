<?php

use Xjchengo\Component\Authorizer;

class AuthorizerTest extends TestCase
{
    public function getPartialMockAuthorizer()
    {
        $componentAccessToken = Mockery::mock('Xjchengo\Component\AccessToken');
        $componentAccessToken->shouldReceive('getAppId')->andReturn('componentAppId');
        return Mockery::mock('Xjchengo\Component\Authorizer[parseJSON]', [
            $componentAccessToken,
            'appId',
            Mockery::mock('Xjchengo\Component\TokenStoreInterface'),
        ]);
    }

    public function testGetToken()
    {
        $authorizer = $this->getPartialMockAuthorizer();
        $tokenStore = $authorizer->getTokenStore();
        $tokenStore->shouldReceive('getToken')->with('appId')->andReturn('token');
        $this->assertEquals('token', $authorizer->getToken());
        $tokenStore->shouldReceive('getRefreshToken')->with('appId')->andReturn('refreshToken');
        $authorizer->shouldReceive('parseJSON')->with('json', [
            'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token',
            [
                'component_appid' => 'componentAppId',
                'authorizer_appid' => 'appId',
                'authorizer_refresh_token' => 'refreshToken',
            ]
        ])->andReturn([
            'authorizer_access_token' => 'accessToken',
            'expires_in' => 7200,
            'authorizer_refresh_token' => 'refreshToken',
        ]);
        $tokenStore->shouldReceive('storeToken')
            ->with('appId', 'accessToken', 'refreshToken', 7200)
            ->andReturn(true);
        $this->assertEquals('accessToken', $authorizer->getToken(true));
    }

    public function testGetAuthorizerInfo()
    {
        $authorizer = $this->getPartialMockAuthorizer();
        $authorizer->shouldReceive('parseJSON')->with('json', [
            'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info',
            [
                'component_appid' => 'componentAppId',
                'authorizer_appid' => 'appId',
            ]
        ])->andReturn(['foo' => 'bar']);
        $result = $authorizer->getAuthorizerInfo();
        $this->assertEquals('bar', $result['foo']);
    }

    public function testGetAuthorizerOption()
    {
        $authorizer = $this->getPartialMockAuthorizer();
        $authorizer->shouldReceive('parseJSON')->with('json', [
            'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option',
            [
                'component_appid' => 'componentAppId',
                'authorizer_appid' => 'appId',
                'option_name' => 'voice_recognize',
            ]
        ])->andReturn([
            'authorizer_appid' => 'appId',
            'option_name' => 'voice_recognize',
            'option_value' => '1',
        ]);
        $result = $authorizer->getAuthorizerOption('voice_recognize');
        $this->assertEquals('1', $result['option_value']);
    }

    public function testSetAuthorizerOption()
    {
        $authorizer = $this->getPartialMockAuthorizer();
        $authorizer->shouldReceive('parseJSON')->with('json', [
            'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option',
            [
                'component_appid' => 'componentAppId',
                'authorizer_appid' => 'appId',
                'option_name' => 'voice_recognize',
                'option_value' => '1',
            ]
        ])->andReturn([
            'errcode' => 0,
            'errmsg' => 'ok',
        ])->once();
        $result = $authorizer->setAuthorizerOption('voice_recognize', '1');
        $this->assertEquals(0, $result['errcode']);
    }
}
