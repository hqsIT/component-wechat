<?php

namespace Xjchengo\Component;

interface TokenStoreInterface
{
//    public function __construct($componentAppId);

    public function getToken($authorizerAppId);

    public function getRefreshToken($authorizerAppId);

    public function storeToken($authorizerAppId, $authorizerAccessToken, $authorizerRefreshToken, $expiresAt);
}
