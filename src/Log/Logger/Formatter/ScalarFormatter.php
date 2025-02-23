<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Formatter;

use LaraGram\Log\Logger\LogRecord;

class ScalarFormatter extends NormalizerFormatter
{
    /**
     * @inheritDoc
     *
     * @phpstan-return array<string, scalar|null> $record
     */
    public function format(LogRecord $record): array
    {
        $result = [];
        foreach ($record->toArray() as $key => $value) {
            $result[$key] = $this->toScalar($value);
        }

        return $result;
    }

    protected function toScalar(mixed $value): string|int|float|bool|null
    {
        $normalized = $this->normalize($value);

        if (\is_array($normalized)) {
            return $this->toJson($normalized, true);
        }

        return $normalized;
    }
}
