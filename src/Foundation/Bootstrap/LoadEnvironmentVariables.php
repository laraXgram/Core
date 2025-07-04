<?php

namespace LaraGram\Foundation\Bootstrap;

use LaraGram\Support\Env\Dotenv;
use LaraGram\Support\Env\Exception\InvalidFileException;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Support\Env;
use LaraGram\Console\Input\ArgvInput;
use LaraGram\Console\Output\ConsoleOutput;

class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($app);

        try {
            $this->createDotenv($app)->safeLoad();
        } catch (InvalidFileException $e) {
            $this->writeErrorAndDie($e);
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if ($app->runningInConsole() &&
            ($input = new ArgvInput)->hasParameterOption('--env') &&
            $this->setEnvironmentFilePath($app, $app->environmentFile().'.'.$input->getParameterOption('--env'))) {
            return;
        }

        $environment = Env::get('APP_ENV');

        if (! $environment) {
            return;
        }

        $this->setEnvironmentFilePath(
            $app, $app->environmentFile().'.'.$environment
        );
    }

    /**
     * Load a custom environment file.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return bool
     */
    protected function setEnvironmentFilePath($app, $file)
    {
        if (is_file($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }

    /**
     * Create a Dotenv instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return \LaraGram\Support\Env\Dotenv
     */
    protected function createDotenv($app)
    {
        return Dotenv::create(
            Env::getRepository(),
            $app->environmentPath(),
            $app->environmentFile()
        );
    }

    /**
     * Write the error information to the screen and exit.
     *
     * @param  \LaraGram\Support\Env\Exception\InvalidFileException  $e
     * @return never
     */
    protected function writeErrorAndDie(InvalidFileException $e)
    {
        $output = (new ConsoleOutput)->getErrorOutput();

        $output->writeln('The environment file is invalid!');
        $output->writeln($e->getMessage());

        http_response_code(500);

        exit(1);
    }
}
