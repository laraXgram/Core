<?php

namespace LaraGram\Validation\Concerns\EmailValidator;

use LaraGram\Validation\Concerns\EmailValidator\Result\Result;
use LaraGram\Validation\Concerns\EmailValidator\Parser\IDLeftPart;
use LaraGram\Validation\Concerns\EmailValidator\Parser\IDRightPart;
use LaraGram\Validation\Concerns\EmailValidator\Result\ValidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Result\InvalidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Warning\EmailTooLong;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\NoLocalPart;

class MessageIDParser extends Parser
{

    public const EMAILID_MAX_LENGTH = 254;

    /**
     * @var string
     */
    protected $idLeft = '';

    /**
     * @var string
     */
    protected $idRight = '';

    public function parse(string $str): Result
    {
        $result = parent::parse($str);

        $this->addLongEmailWarning($this->idLeft, $this->idRight);

        return $result;
    }

    protected function preLeftParsing(): Result
    {
        if (!$this->hasAtToken()) {
            return new InvalidEmail(new NoLocalPart(), $this->lexer->current->value);
        }
        return new ValidEmail();
    }

    protected function parseLeftFromAt(): Result
    {
        return $this->processIDLeft();
    }

    protected function parseRightFromAt(): Result
    {
        return $this->processIDRight();
    }

    private function processIDLeft(): Result
    {
        $localPartParser = new IDLeftPart($this->lexer);
        $localPartResult = $localPartParser->parse();
        $this->idLeft = $localPartParser->localPart();
        $this->warnings = [...$localPartParser->getWarnings(), ...$this->warnings];

        return $localPartResult;
    }

    private function processIDRight(): Result
    {
        $domainPartParser = new IDRightPart($this->lexer);
        $domainPartResult = $domainPartParser->parse();
        $this->idRight = $domainPartParser->domainPart();
        $this->warnings = [...$domainPartParser->getWarnings(), ...$this->warnings];

        return $domainPartResult;
    }

    public function getLeftPart(): string
    {
        return $this->idLeft;
    }

    public function getRightPart(): string
    {
        return $this->idRight;
    }

    private function addLongEmailWarning(string $localPart, string $parsedDomainPart): void
    {
        if (strlen($localPart . '@' . $parsedDomainPart) > self::EMAILID_MAX_LENGTH) {
            $this->warnings[EmailTooLong::CODE] = new EmailTooLong();
        }
    }
}
