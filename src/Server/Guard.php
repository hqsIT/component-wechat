<?php

namespace Xjchengo\Server;

use EasyWeChat\Core\Exceptions\FaultException;
use EasyWeChat\Core\Exceptions\InvalidArgumentException;
use EasyWeChat\Core\Exceptions\RuntimeException;
use EasyWeChat\Encryption\Encryptor;
use EasyWeChat\Message\AbstractMessage;
use EasyWeChat\Message\Raw as RawMessage;
use EasyWeChat\Message\Text;
use EasyWeChat\Support\Collection;
use EasyWeChat\Support\Log;
use EasyWeChat\Support\XML;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use EasyWeChat\Server\Guard as BaseGuard;

/**
 * Class Guard.
 */
class Guard extends BaseGuard
{
    protected function handleRequest()
    {
        $message = $this->getMessage();
        $response = $this->handleMessage($message);

        return [
            'to' => isset($message['FromUserName']) ? $message['FromUserName'] : null,
            'from' => isset($message['ToUserName']) ? $message['ToUserName'] : null,
            'response' => $response,
        ];
    }

    /**
     * Handle message.
     *
     * @param array $message
     *
     * @return mixed
     */
    protected function handleMessage($message)
    {
        $handler = $this->messageHandler;

        if (!is_callable($handler)) {
            Log::debug('No handler enabled.');

            return;
        }

        Log::debug('Message detail:', $message);

        $message = new Collection($message);

        $response = null;

        $response = call_user_func_array($handler, [$message]);

        return $response;
    }
}
