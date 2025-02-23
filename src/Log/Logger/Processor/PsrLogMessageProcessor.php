<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Processor;

use LaraGram\Log\Logger\Utils;
use LaraGram\Log\Logger\LogRecord;

class PsrLogMessageProcessor implements ProcessorInterface
{
    public const SIMPLE_DATE = "Y-m-d\TH:i:s.uP";

    private ?string $dateFormat;

    private bool $removeUsedContextFields;

    /**
     * @param string|null $dateFormat              The format of the timestamp: one supported by DateTime::format
     * @param bool        $removeUsedContextFields If set to true the fields interpolated into message gets unset
     */
    public function __construct(?string $dateFormat = null, bool $removeUsedContextFields = false)
    {
        $this->dateFormat = $dateFormat;
        $this->removeUsedContextFields = $removeUsedContextFields;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if (false === strpos($record->message, '{')) {
            return $record;
        }

        $replacements = [];
        $context = $record->context;

        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (strpos($record->message, $placeholder) === false) {
                continue;
            }

            if (null === $val || \is_scalar($val) || (\is_object($val) && method_exists($val, "__toString"))) {
                $replacements[$placeholder] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                if (null === $this->dateFormat && $val instanceof \LaraGram\Log\Logger\JsonSerializableDateTimeImmutable) {
                    // handle LaraGram\Log\Logger dates using __toString if no specific dateFormat was asked for
                    // so that it follows the useMicroseconds flag
                    $replacements[$placeholder] = (string) $val;
                } else {
                    $replacements[$placeholder] = $val->format($this->dateFormat ?? static::SIMPLE_DATE);
                }
            } elseif ($val instanceof \UnitEnum) {
                $replacements[$placeholder] = $val instanceof \BackedEnum ? $val->value : $val->name;
            } elseif (\is_object($val)) {
                $replacements[$placeholder] = '[object '.Utils::getClass($val).']';
            } elseif (\is_array($val)) {
                $replacements[$placeholder] = 'array'.Utils::jsonEncode($val, null, true);
            } else {
                $replacements[$placeholder] = '['.\gettype($val).']';
            }

            if ($this->removeUsedContextFields) {
                unset($context[$key]);
            }
        }

        return $record->with(message: strtr($record->message, $replacements), context: $context);
    }
}
