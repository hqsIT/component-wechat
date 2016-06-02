<?php

namespace Xjchengo\Component;

use InvalidArgumentException;
use Overtrue\Socialite\SocialiteManager as BaseSocialiteManager;

/**
 * Class SocialiteManager.
 */
class SocialiteManager extends BaseSocialiteManager
{
    protected function createDriver($driver)
    {
        if (isset($this->initialDrivers[$driver])) {
            $provider = $this->initialDrivers[$driver];
            $provider = __NAMESPACE__.'\\Providers\\'.$provider.'Provider';

            return $this->buildProvider($provider, $this->formatConfig($this->config->get($driver)));
        }

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }
}
