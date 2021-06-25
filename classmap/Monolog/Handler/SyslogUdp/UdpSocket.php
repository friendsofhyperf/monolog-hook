<?php

declare(strict_types=1);
/**
 * This file is part of monolog-hook.
 *
 * @link     https://github.com/friendsofhyperf/monolog-hook
 * @document https://github.com/friendsofhyperf/monolog-hook/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Monolog\Handler\SyslogUdp;

use Monolog\Utils;
use Swoole\Coroutine\Client;

class UdpSocket
{
    protected const DATAGRAM_MAX_LENGTH = 65023;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $port;

    /**
     * @var Client
     */
    protected $socket;

    public function __construct(string $ip, int $port = 514)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function write($line, $header = '')
    {
        $this->send($this->assembleMessage($line, $header));
    }

    public function close(): void
    {
        // todo
    }

    protected function send(string $chunk): void
    {
        co(function () use ($chunk) {
            $socket = new Client(SWOOLE_SOCK_UDP);
            $socket->connect($this->ip, $this->port, 0.5);
            defer(function () use ($socket) {
                $socket->close();
            });
            $socket->send($chunk);
        });
    }

    protected function assembleMessage(string $line, string $header): string
    {
        $chunkSize = static::DATAGRAM_MAX_LENGTH - strlen($header);

        return $header . Utils::substr($line, 0, $chunkSize);
    }
}
