<?php

namespace LaraGram\Conversation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateConversation extends Command
{
    protected $signature = 'make:conversation';
    protected $description = 'Create new conversation';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Conversation name not set!", true);
        }

        $file_structure = file_get_contents($this->getStub('/stubs/conversation.stub'));
        $name = str_replace('Conversation', '', ucfirst($this->getArgument(0)));

        $conversation_path = app('path.app') . DIRECTORY_SEPARATOR . 'Conversations';
        if (!file_exists($conversation_path)){
            mkdir($conversation_path);
        }

        if (file_exists($conversation_path . DIRECTORY_SEPARATOR . $name . '.php')){
            Console::output()->warning("Conversation [ $name ] already exist!", exit: true);
        }

        file_put_contents($conversation_path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        Console::output()->success("Conversation [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}