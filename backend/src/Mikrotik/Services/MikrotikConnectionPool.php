<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\DomainModels\RouterConnectionData;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;

/**
 * Bounded TLS RouterOS API connection manager.
 *
 * It keeps one authenticated session alive for a complete discovery/health batch
 * and always closes it afterwards. This is safe for the current stateless PHP
 * runtime while preserving a pool boundary for a future long-lived worker.
 */
final class MikrotikConnectionPool implements MikrotikConnectionPoolContract
{
    /** @var array<string, int> */
    private array $leases = [];

    public function __construct(
        private readonly int $connectTimeoutSeconds,
        private readonly int $commandTimeoutSeconds,
        private readonly int $maxRetries,
        private readonly bool $verifyPeer,
        private readonly ?string $caFile,
        private readonly int $maxConnectionsPerRouter = 1,
    ) {
    }

    public function executeBatch(RouterConnectionData $connection, array $sentences): array
    {
        $key = strtolower($connection->host . ':' . $connection->apiPort . ':' . $connection->username);
        if (($this->leases[$key] ?? 0) >= $this->maxConnectionsPerRouter) {
            throw new MikrotikConnectionException('The router already has the maximum number of active API connections.');
        }
        $this->leases[$key] = ($this->leases[$key] ?? 0) + 1;

        try {
            return $this->withRetries($connection, $sentences);
        } finally {
            $this->leases[$key]--;
            if ($this->leases[$key] === 0) {
                unset($this->leases[$key]);
            }
        }
    }

    /** @param array<int, array<int, string>> $sentences @return array<int, array<int, array<string, string>>> */
    private function withRetries(RouterConnectionData $connection, array $sentences): array
    {
        $attempt = 0;
        do {
            $socket = null;
            try {
                $socket = $this->open($connection);
                $this->login($socket, $connection);
                $responses = [];
                foreach ($sentences as $sentence) {
                    $responses[] = $this->executeSentence($socket, $sentence);
                }

                return $responses;
            } catch (MikrotikCommandException $exception) {
                throw $exception;
            } catch (MikrotikConnectionException $exception) {
                if ($attempt >= $this->maxRetries) {
                    throw $exception;
                }
                $attempt++;
                usleep((int) ((100000 * (2 ** ($attempt - 1))) + random_int(0, 50000)));
            } catch (\Throwable $exception) {
                if ($attempt >= $this->maxRetries) {
                    throw new MikrotikConnectionException();
                }
                $attempt++;
                usleep((int) ((100000 * (2 ** ($attempt - 1))) + random_int(0, 50000)));
            } finally {
                if (is_resource($socket)) {
                    fclose($socket);
                }
            }
        } while ($attempt <= $this->maxRetries);

        throw new MikrotikConnectionException();
    }

    /** @return resource */
    private function open(RouterConnectionData $connection)
    {
        $ssl = [
            'verify_peer' => $this->verifyPeer,
            'verify_peer_name' => $this->verifyPeer,
            'allow_self_signed' => false,
            'peer_name' => $connection->host,
        ];
        if ($this->caFile !== null && $this->caFile !== '') {
            $ssl['cafile'] = $this->caFile;
        }
        $context = stream_context_create(['ssl' => $ssl]);
        $errorNumber = 0;
        $errorMessage = '';
        $socketHost = filter_var($connection->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
            ? '[' . $connection->host . ']'
            : $connection->host;
        $socket = @stream_socket_client(
            sprintf('tls://%s:%d', $socketHost, $connection->apiPort),
            $errorNumber,
            $errorMessage,
            $this->connectTimeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context,
        );
        if ($socket === false) {
            throw new MikrotikConnectionException();
        }
        stream_set_timeout($socket, $this->commandTimeoutSeconds);

        return $socket;
    }

    /** @param resource $socket */
    private function login($socket, RouterConnectionData $connection): void
    {
        $this->executeSentence($socket, ['/login', '=name=' . $connection->username, '=password=' . $connection->password]);
    }

    /**
     * @param resource $socket
     * @param array<int, string> $words
     * @return array<int, array<string, string>>
     */
    private function executeSentence($socket, array $words): array
    {
        foreach ($words as $word) {
            $this->writeWord($socket, $word);
        }
        $this->writeWord($socket, '');

        $rows = [];
        while (true) {
            $sentence = $this->readSentence($socket);
            $reply = $sentence[0] ?? '';
            if ($reply === '!re') {
                $rows[] = $this->attributesFromSentence($sentence);
                continue;
            }
            if ($reply === '!done') {
                return $rows;
            }
            if ($reply === '!trap' || $reply === '!fatal') {
                throw new MikrotikCommandException();
            }
            throw new MikrotikConnectionException();
        }
    }

    /** @param resource $socket */
    private function writeWord($socket, string $word): void
    {
        $payload = $this->encodeLength(strlen($word)) . $word;
        $written = fwrite($socket, $payload);
        if ($written === false || $written !== strlen($payload)) {
            throw new MikrotikConnectionException();
        }
    }

    /** @param resource $socket @return array<int, string> */
    private function readSentence($socket): array
    {
        $words = [];
        while (true) {
            $word = $this->readWord($socket);
            if ($word === '') {
                return $words;
            }
            $words[] = $word;
        }
    }

    /** @param resource $socket */
    private function readWord($socket): string
    {
        $first = $this->readExact($socket, 1);
        $firstByte = ord($first);
        if ($firstByte < 0x80) {
            $length = $firstByte;
        } elseif (($firstByte & 0xC0) === 0x80) {
            $length = (($firstByte & 0x3F) << 8) + ord($this->readExact($socket, 1));
        } elseif (($firstByte & 0xE0) === 0xC0) {
            $length = (($firstByte & 0x1F) << 16) + (ord($this->readExact($socket, 1)) << 8) + ord($this->readExact($socket, 1));
        } elseif (($firstByte & 0xF0) === 0xE0) {
            $length = (($firstByte & 0x0F) << 24) + (ord($this->readExact($socket, 1)) << 16) + (ord($this->readExact($socket, 1)) << 8) + ord($this->readExact($socket, 1));
        } elseif ($firstByte === 0xF0) {
            $length = unpack('Nlength', $this->readExact($socket, 4))['length'];
        } else {
            throw new MikrotikConnectionException();
        }
        if ($length === 0) {
            return '';
        }
        if ($length > 8_388_608) {
            throw new MikrotikConnectionException('Router returned an unexpectedly large response.');
        }

        return $this->readExact($socket, $length);
    }

    /** @param resource $socket */
    private function readExact($socket, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = fread($socket, $length - strlen($buffer));
            $metadata = stream_get_meta_data($socket);
            if ($chunk === false || $chunk === '' || $metadata['timed_out']) {
                throw new MikrotikConnectionException();
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    /** @return array<string, string> */
    private function attributesFromSentence(array $sentence): array
    {
        $attributes = [];
        foreach (array_slice($sentence, 1) as $word) {
            if (!str_starts_with($word, '=')) {
                continue;
            }
            $parts = explode('=', substr($word, 1), 2);
            if (count($parts) === 2) {
                $attributes[$parts[0]] = $parts[1];
            }
        }

        return $attributes;
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        if ($length < 0x4000) {
            return pack('n', $length | 0x8000);
        }
        if ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . pack('n', $length & 0xFFFF);
        }
        if ($length < 0x10000000) {
            return pack('N', $length | 0xE0000000);
        }

        return "\xF0" . pack('N', $length);
    }
}
