<?php

namespace LaraGram\Console\Command;

use LaraGram\Console\Descriptor\ApplicationDescription;
use LaraGram\Console\Helper\DescriptorHelper;
use LaraGram\Console\Input\InputArgument;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

class HelpCommand extends Command
{
    private Command $command;

    protected function configure(): void
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('help')
            ->setDefinition([
                new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', 'help', fn () => array_keys((new ApplicationDescription($this->getApplication()))->getCommands())),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt', fn () => (new DescriptorHelper())->getFormats()),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command help'),
            ])
            ->setDescription('Display help for a command')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command displays help for a given command:

  <info>%command.full_name% list</info>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <info>%command.full_name% --format=xml list</info>

To display the list of available commands, please use the <info>list</info> command.
EOF
            )
        ;
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->command ??= $this->getApplication()->find($input->getArgument('command_name'));

        $helper = new DescriptorHelper();
        $helper->describe($output, $this->command, [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
        ]);

        unset($this->command);

        return 0;
    }
}
