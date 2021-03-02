<?php

declare(strict_types=1);
/**
 * This file is part of monolog-hook.
 *
 * @link     https://github.com/friendsofhyperf/monolog-hook
 * @document https://github.com/friendsofhyperf/monolog-hook/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\MonologHook;

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') or define('BASE_PATH', '');

        $classMap = [];

        if (version_compare(swoole_version(), '4.6', '>=')) {
            $classMap[\Monolog\Handler\SyslogUdp\UdpSocket::class] = __DIR__ . '/../classmap/Monolog/Handler/SyslogUdp/UdpSocket.php';
        }

        return [
            'dependencies' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => $classMap,
                ],
            ],
            'commands' => [],
            'listeners' => [],
            'publish' => [],
        ];
    }
}
