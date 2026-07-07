<?php

namespace LaraGram\Conversation;

/**
 * Reads a {@see Question}'s protected configuration into an immutable
 * {@see QuestionDefinition} for the engine.
 *
 * @internal
 */
final class QuestionAccessor
{
    /**
     * Compile a question builder into an immutable definition.
     *
     * @param  \LaraGram\Conversation\Question  $question
     * @return \LaraGram\Conversation\QuestionDefinition
     */
    public static function compile(Question $question): QuestionDefinition
    {
        $reader = function (): array {
            return [
                'prompt'      => $this->prompt,
                'name'        => $this->name,
                'type'        => $this->type,
                'rules'       => $this->rules,
                'messages'    => $this->messages,
                'skipCommand' => $this->skipCommand,
                'keyboard'    => $this->keyboard,
                'parseMode'   => $this->parseMode,
                'callback'    => $this->callback,
                'deferred'    => $this->deferred,
                'sender'      => $this->sender,
                'maxAttempts' => $this->maxAttempts,
                'promptKind'  => $this->promptKind,
                'promptMedia' => $this->promptMedia,
                'back'        => $this->back,
            ];
        };

        // Bind to the Question instance so protected properties are readable.
        $attributes = $reader->call($question);

        return new QuestionDefinition(
            prompt: $attributes['prompt'],
            name: $attributes['name'],
            type: $attributes['type'],
            rules: $attributes['rules'],
            messages: $attributes['messages'],
            skipCommand: $attributes['skipCommand'],
            keyboard: $attributes['keyboard'],
            parseMode: $attributes['parseMode'],
            callback: $attributes['callback'],
            deferred: $attributes['deferred'],
            sender: $attributes['sender'],
            maxAttempts: $attributes['maxAttempts'],
            promptKind: $attributes['promptKind'],
            promptMedia: $attributes['promptMedia'],
            back: $attributes['back'],
        );
    }
}
