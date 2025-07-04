<?php

namespace LaraGram\Conversation;

use LaraGram\Support\Facades\Cache;
use LaraGram\Support\Facades\Request;

class Conversation
{
    private array $questions;

    public function create(callable $callback): void
    {
        $questioner = new Questioner();
        $callback($questioner);
        $this->questions = $questioner->getQuestions();
    }

    public function start(string $name)
    {
        $name = ucfirst($name);
        $user_id = user()->id ?? callback_query()->from->id;
        if (Cache::hasNot("conversation.$user_id")) {
            $class = new (include app()->path("Conversations/$name.php"));

            $class->start();

            $data = [
                'name' => $name,
                'step' => 0,
                'maxAttempts' => $class->maxAttempts,
                'cancelTimeout' => $class->cancelTimeout,
                'cancelCommand' => $class->cancelCommand,
                'totalAttempts' => 0,
                'answers' => [],
                'questions' => $this->questions,
                'waitForAnswer' => false,
                'start' => time(),
                'complete' => false,
                'forgot' => $class->forgotAfterComplete ?? true,
                'chatID' => chat()->id ?? user()->id,
            ];

            Cache::set("conversation.$user_id", json_encode($data));
        }
    }

    public function getAnswers(int|string $user_id): array|null
    {
        $cache = json_decode(Cache::get("conversation.$user_id"), true);
        if (isset($cache)) {
            return $cache['answers'];
        }
        return null;
    }

    public function getAnswer(int|string $user_id, string|int $name): array|null
    {
        $answers = $this->getAnswers($user_id);
        if (isset($answers)) {
            return $answers[$name];
        }
        return null;
    }
}