<?php

namespace LaraGram\Filesystem\Aws\EventStream;

use Generator;
use LaraGram\Filesystem\Aws\Exceptions\AwsException;
use LaraGram\Http\Factory\StreamInterface;

class EventStreamDecoder
{
    /**
     * The size of a message prelude: total length, headers length and prelude CRC.
     */
    protected const PRELUDE_LENGTH = 12;

    /**
     * Decode the messages of an "application/vnd.amazon.eventstream" body as they arrive.
     *
     * Each message is yielded as an array with its "headers" and raw "payload".
     *
     * @param  \LaraGram\Http\Factory\StreamInterface  $stream
     * @return \Generator<int, array{headers: array<string, mixed>, payload: string}>
     */
    public function decode(StreamInterface $stream): Generator
    {
        while (! $stream->eof()) {
            $prelude = $this->read($stream, self::PRELUDE_LENGTH);

            if ($prelude === '') {
                return;
            }

            if (strlen($prelude) < self::PRELUDE_LENGTH) {
                throw new AwsException('The event stream ended in the middle of a message.');
            }

            ['total' => $total, 'headers' => $headersLength, 'crc' => $preludeCrc] = unpack('Ntotal/Nheaders/Ncrc', $prelude);

            if (crc32(substr($prelude, 0, 8)) !== $preludeCrc) {
                throw new AwsException('The event stream prelude checksum does not match.');
            }

            $rest = $this->read($stream, $total - self::PRELUDE_LENGTH);

            if (strlen($rest) !== $total - self::PRELUDE_LENGTH) {
                throw new AwsException('The event stream ended in the middle of a message.');
            }

            $messageCrc = unpack('N', substr($rest, -4))[1];

            if (crc32($prelude.substr($rest, 0, -4)) !== $messageCrc) {
                throw new AwsException('The event stream message checksum does not match.');
            }

            yield [
                'headers' => $this->parseHeaders(substr($rest, 0, $headersLength)),
                'payload' => substr($rest, $headersLength, -4),
            ];
        }
    }

    /**
     * Parse the binary headers of a message.
     *
     * @param  string  $bytes
     * @return array<string, mixed>
     */
    protected function parseHeaders(string $bytes): array
    {
        $headers = [];
        $offset = 0;
        $length = strlen($bytes);

        while ($offset < $length) {
            $nameLength = ord($bytes[$offset++]);
            $name = substr($bytes, $offset, $nameLength);
            $offset += $nameLength;

            $type = ord($bytes[$offset++]);

            switch ($type) {
                case 0:
                    $value = true;
                    break;
                case 1:
                    $value = false;
                    break;
                case 2:
                    $value = unpack('c', $bytes, $offset)[1];
                    $offset += 1;
                    break;
                case 3:
                    $value = unpack('n', $bytes, $offset)[1];
                    $value = $value >= 0x8000 ? $value - 0x10000 : $value;
                    $offset += 2;
                    break;
                case 4:
                    $value = unpack('N', $bytes, $offset)[1];
                    $value = $value >= 0x80000000 ? $value - 0x100000000 : $value;
                    $offset += 4;
                    break;
                case 5:
                case 8:
                    $value = unpack('J', $bytes, $offset)[1];
                    $offset += 8;
                    break;
                case 6:
                case 7:
                    $valueLength = unpack('n', $bytes, $offset)[1];
                    $value = substr($bytes, $offset + 2, $valueLength);
                    $offset += 2 + $valueLength;
                    break;
                case 9:
                    $value = bin2hex(substr($bytes, $offset, 16));
                    $offset += 16;
                    break;
                default:
                    throw new AwsException("Unknown event stream header type [{$type}].");
            }

            $headers[$name] = $value;
        }

        return $headers;
    }

    /**
     * Read exactly the given number of bytes unless the stream ends first.
     *
     * @param  \LaraGram\Http\Factory\StreamInterface  $stream
     * @param  int  $length
     * @return string
     */
    protected function read(StreamInterface $stream, int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length && ! $stream->eof()) {
            $chunk = $stream->read($length - strlen($buffer));

            if ($chunk === '') {
                break;
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }
}
