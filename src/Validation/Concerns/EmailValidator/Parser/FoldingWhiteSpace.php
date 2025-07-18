<?php

namespace LaraGram\Validation\Concerns\EmailValidator\Parser;

use LaraGram\Validation\Concerns\EmailValidator\EmailLexer;
use LaraGram\Validation\Concerns\EmailValidator\Warning\CFWSNearAt;
use LaraGram\Validation\Concerns\EmailValidator\Result\InvalidEmail;
use LaraGram\Validation\Concerns\EmailValidator\Warning\CFWSWithFWS;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\CRNoLF;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\AtextAfterCFWS;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\CRLFAtTheEnd;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\CRLFX2;
use LaraGram\Validation\Concerns\EmailValidator\Result\Reason\ExpectingCTEXT;
use LaraGram\Validation\Concerns\EmailValidator\Result\Result;
use LaraGram\Validation\Concerns\EmailValidator\Result\ValidEmail;

class  FoldingWhiteSpace extends PartParser
{
    public const FWS_TYPES = [
        EmailLexer::S_SP,
        EmailLexer::S_HTAB,
        EmailLexer::S_CR,
        EmailLexer::S_LF,
        EmailLexer::CRLF
    ];

    public function parse(): Result
    {
        if (!$this->isFWS()) {
            return new ValidEmail();
        }

        $previous = $this->lexer->getPrevious();

        $resultCRLF = $this->checkCRLFInFWS();
        if ($resultCRLF->isInvalid()) {
            return $resultCRLF;
        }

        if ($this->lexer->current->isA(EmailLexer::S_CR)) {
            return new InvalidEmail(new CRNoLF(), $this->lexer->current->value);
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC) && !$previous->isA(EmailLexer::S_AT)) {
            return new InvalidEmail(new AtextAfterCFWS(), $this->lexer->current->value);
        }

        if ($this->lexer->current->isA(EmailLexer::S_LF) || $this->lexer->current->isA(EmailLexer::C_NUL)) {
            return new InvalidEmail(new ExpectingCTEXT(), $this->lexer->current->value);
        }

        if ($this->lexer->isNextToken(EmailLexer::S_AT) || $previous->isA(EmailLexer::S_AT)) {
            $this->warnings[CFWSNearAt::CODE] = new CFWSNearAt();
        } else {
            $this->warnings[CFWSWithFWS::CODE] = new CFWSWithFWS();
        }

        return new ValidEmail();
    }

    protected function checkCRLFInFWS(): Result
    {
        if (!$this->lexer->current->isA(EmailLexer::CRLF)) {
            return new ValidEmail();
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB))) {
            return new InvalidEmail(new CRLFX2(), $this->lexer->current->value);
        }

        //this has no coverage. Condition is repeated from above one
        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB))) {
            return new InvalidEmail(new CRLFAtTheEnd(), $this->lexer->current->value);
        }

        return new ValidEmail();
    }

    protected function isFWS(): bool
    {
        if ($this->escaped()) {
            return false;
        }

        return in_array($this->lexer->current->type, self::FWS_TYPES);
    }
}
