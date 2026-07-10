<?php

namespace LaraGram\Listening\Contracts;

/**
 * Contract for request objects that can drive the Listening matching engine.
 *
 * By reading the matchable value from the request via this contract instead,
 * any request surface — the Bot-API Request or the MTProto ClientRequest — can
 * drive the *same* engine. The Bot-API Request implements this by delegating to
 * the existing helpers; the MTProto ClientRequest implements it with native MTProto verbs.
 */
interface ProvidesListenContext
{
    /**
     * The verb (method) this request dispatches under (e.g. "TEXT", "COMMAND",
     * "NEW_MESSAGE"). Mirrors the value used to bucket registered listens.
     */
    public function listenVerb(): string;

    /**
     * The regex-matchable string value for the given verb, or null when the
     * verb is structural (matched by predicate, not by a regex) or carries no
     * value for this request.
     *
     * Implementations must return the value already normalised for matching
     * (e.g. COMMAND with its leading "/" stripped).
     */
    public function listenValue(string $verb): ?string;

    /**
     * The message entities for this request, used by ENTITIES-style matching.
     *
     * @return array
     */
    public function entities(): array;
}
