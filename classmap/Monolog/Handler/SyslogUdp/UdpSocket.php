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

use Hyperf\Utils\Coroutine;
use Monolog\Utils;
use Socket;
use Swoole\Coroutine\Client;

class UdpSocket
{
    protected const DATAGRAM_MAX_LENGTH = 65023;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $port;

    /** @var null|resource|Socket */
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
        if (Coroutine::inCoroutine()) {
            return;
        }

        if (is_resource($this->socket) || $this->socket instanceof Socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * @return resource|Socket
     */
    protected function getSocket()
    {
        if ($this->socket !== null) {
            return $this->socket;
        }

        $domain = AF_INET;
        $protocol = SOL_UDP;
        // Check if we are using unix sockets.
        if ($this->port === 0) {
            $domain = AF_UNIX;
            $protocol = IPPROTO_IP;
        }

        $this->socket = socket_create($domain, SOCK_DGRAM, $protocol) ?: null;
        if ($this->socket === null) {
            throw new \RuntimeException('The UdpSocket to ' . $this->ip . ':' . $this->port . ' could not be opened via socket_create');
        }

        return $this->socket;
    }

    protected function send(string $chunk): void
    {
        if (Coroutine::inCoroutine()) {
            co(function () use ($chunk) {
                $socket = new Client(SWOOLE_SOCK_UDP);
                $socket->connect($this->ip, $this->port, 0.5);
                defer(function () use ($socket) {
                    $socket->close();
                });
                $socket->send($chunk);
            });

            return;
        }

        socket_sendto($this->getSocket(), $chunk, strlen($chunk), $flags = 0, $this->ip, $this->port);
    }

    protected function assembleMessage(string $line, string $header): string
    {
        $chunkSize = static::DATAGRAM_MAX_LENGTH - strlen($header);

        return $header . Utils::substr($line, 0, $chunkSize);
    }
}
