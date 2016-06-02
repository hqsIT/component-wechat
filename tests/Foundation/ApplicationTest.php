<?php

use Xjchengo\Foundation\Application;

class ApplicationTest extends TestCase
{
    protected function getApplication()
    {
        $tokenStore = Mockery::mock('Xjchengo\Component\TokenStoreInterface');

        $application = new Application([
            'token_store' => $tokenStore,
        ]);

        return $application;
    }

    public function testReplaceOAuthServiceProvider()
    {
        $application = $this->getApplication();

        $this->assertInstanceOf('Xjchengo\Component\SocialiteProvider', $application['oauth']);
    }

    public function testRegisterAccessTokenMethod()
    {
        $application = $this->getApplication();

        $this->assertInstanceOf('Xjchengo\Component\Authorizer', $application['access_token']);
        $this->assertInstanceOf('Xjchengo\Component\Authorizer', $application['user']->getAccessToken());
        $this->assertInstanceOf('Xjchengo\Component\AccessToken', $application['component_access_token']);
        $this->assertInstanceOf('Xjchengo\Component\Auth', $application['component_auth']);
    }
}
