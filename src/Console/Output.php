<?php
namespace LaraGram\Console;

use Bramus\Ansi\Ansi;
use Bramus\Ansi\ControlSequences\EscapeSequences\Enums\SGR;
use Bramus\Ansi\Writers\StreamWriter;

class Output
{
    public function success($message, bool $exit = false): void
    {
        $ansi = new Ansi(new StreamWriter('php://stdout'));
        $ansi->color([SGR::COLOR_BG_GREEN, SGR::COLOR_FG_GREEN_BRIGHT])
            ->bold()
            ->tab()
            ->text("    {$message}    ")
            ->nostyle()
            ->bell();
        echo PHP_EOL;

        if ($exit) {
            exit();
        }
    }

    public function failed($message, bool $exit = false): void
    {
        $ansi = new Ansi(new StreamWriter('php://stdout'));
        $ansi->color([SGR::COLOR_BG_RED, SGR::COLOR_FG_RED_BRIGHT])
            ->bold()
            ->tab()
            ->text("    {$message}    ")
            ->nostyle()
            ->bell();
        echo PHP_EOL;

        if ($exit) {
            exit();
        }
    }

    public function warning($message, bool $exit = false): void
    {
        $ansi = new Ansi(new StreamWriter('php://stdout'));
        $ansi->color([SGR::COLOR_BG_YELLOW, SGR::COLOR_FG_YELLOW_BRIGHT])
            ->bold()
            ->tab()
            ->text("    {$message}    ")
            ->nostyle()
            ->bell();
        echo PHP_EOL;

        if ($exit) {
            exit();
        }
    }

    public function message($message, bool $exit = false): void
    {
        $ansi = new Ansi(new StreamWriter('php://stdout'));
        $ansi->color([SGR::COLOR_BG_BLUE, SGR::COLOR_FG_BLUE_BRIGHT])
            ->bold()
            ->tab()
            ->text("    {$message}    ")
            ->nostyle()
            ->bell();
        echo PHP_EOL;

        if ($exit) {
            exit();
        }
    }

    public function title($message, bool $exit = false): void
    {
        $ansi = new Ansi(new StreamWriter('php://stdout'));
        $ansi->color([SGR::COLOR_BG_PURPLE, SGR::COLOR_FG_PURPLE_BRIGHT])
            ->bold()
            ->tab()
            ->text("    {$message}    ")
            ->nostyle()
            ->bell();
        echo PHP_EOL;

        if ($exit) {
            exit();
        }
    }
}