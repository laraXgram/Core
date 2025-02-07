<?php

namespace LaraGram\Console\Command;

use LaraGram\Console\Descriptor\ApplicationDescription;
use LaraGram\Console\Helper\DescriptorHelper;
use LaraGram\Console\Input\InputArgument;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

class ListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('list')
            ->setDefinition([
                new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name', null, fn () => array_keys((new ApplicationDescription($this->getApplication()))->getNamespaces())),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt', fn () => (new DescriptorHelper())->getFormats()),
                new InputOption('short', null, InputOption::VALUE_NONE, 'To skip describing commands\' arguments'),
            ])
            ->setDescription('List commands')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists all commands:

  <info>%command.full_name%</info>

You can also display the commands for a specific namespace:

  <info>%command.full_name% test</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <info>%command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

  <info>%command.full_name% --raw</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = new DescriptorHelper();
        $helper->describe($output, $this->getApplication(), [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'namespace' => $input->getArgument('namespace'),
            'short' => $input->getOption('short'),
        ]);

        return 0;
    }
}
