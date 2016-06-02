<?php

namespace Xjchengo\Component;

use Overtrue\Socialite\Providers\WeChatProvider;
use Symfony\Component\HttpFoundation\Request;

class SocialiteProvider extends WeChatProvider
{
    protected $componentAccessToken;

    public function __construct(Request $request, $clientId,AccessToken $componentAccessToken, $redirectUrl)
    {
        $this->componentAccessToken = $componentAccessToken;
        parent::__construct($request, $clientId, null, $redirectUrl);
    }

    protected function getCodeFields($state = null)
    {
        $codeFields = parent::getCodeFields($state);
        $codeFields['component_appid'] = $this->componentAccessToken->getAppId();

        return $codeFields;
    }

    protected function getTokenUrl()
    {
        return $this->baseUrl.'/oauth2/component/access_token';
    }

    protected function getTokenFields($code)
    {
        return [
            'appid'      => $this->clientId,
            'code'       => $code,
            'grant_type' => 'authorization_code',
            'component_appid' => $this->componentAccessToken->getAppId(),
            'component_access_token' => $this->componentAccessToken->getToken(),
        ];
    }
}
