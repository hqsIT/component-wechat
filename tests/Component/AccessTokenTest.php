<?php

use Xjchengo\Component\AccessToken;
use EasyWeChat\Core\Http;

class AccessTokenTest extends TestCase
{
    public function testGetQueryName()
    {
        $accessToken = new AccessToken('appId', 'secret', 'verifyTicket');

        $this->assertEquals('component_access_token', $accessToken->getQueryName());
    }

    public function testGetTokenFromServer()
    {
        $http = Mockery::mock(Http::class.'[json]', function ($mock) {
            $mock->shouldReceive('json')
                ->with('https://api.weixin.qq.com/cgi-bin/component/api_component_token', [
                    'component_appid' => 'appId',
                    'component_appsecret' => 'secret',
                    'component_verify_ticket' => 'verifyTicket',
                ])
                ->andReturn(json_encode([
                'component_access_token' => 'thisIsATokenFromHttp',
                'expires_in' => 7200,
            ]));
        });

        $accessToken = new AccessToken('appId', 'secret', 'verifyTicket');
        $accessToken->setHttp($http);

        $token = $accessToken->getTokenFromServer();
        $this->assertEquals('thisIsATokenFromHttp', $token['access_token']);

        $http = Mockery::mock(Http::class.'[json]', function ($mock) {
            $mock->shouldReceive('json')->andReturn(json_encode([
                'foo' => 'bar', // without "access_token"
            ]));
        });

        $accessToken = new AccessToken('appId', 'secret', 'verifyTicket');
        $accessToken->setHttp($http);

        $this->setExpectedException(\EasyWeChat\Core\Exceptions\HttpException::class, 'Request AccessToken fail. response: {"foo":"bar"}');
        $accessToken->getToken();
        $this->fail();
    }
}
