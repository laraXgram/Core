<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Broadcasting\BroadcastManager;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;

#[AsCommand(name: 'broadcast:status')]
class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:status
                    {id? : The broadcast identifier}
                    {--limit=20 : The number of broadcasts to list}
                    {--json : Output the progress as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the recent Telegram broadcasts, or the progress of one';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    public function handle(BroadcastManager $manager)
    {
        return is_null($this->argument('id'))
            ? $this->recent($manager)
            : $this->progress($manager);
    }

    /**
     * List the recent broadcasts, including the scheduled ones.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    protected function recent(BroadcastManager $manager)
    {
        $broadcasts = $manager->recent((int) $this->option('limit'));

        if ($this->option('json')) {
            $this->line(json_encode(array_map(
                fn ($progress) => $progress->toArray(), $broadcasts
            ), JSON_PRETTY_PRINT));

            return 0;
        }

        if ($broadcasts === []) {
            $this->components->info('No broadcasts were found.');

            return 0;
        }

        $this->table(
            ['ID', 'Status', 'Method', 'Audiences', 'Sent', 'Unreachable', 'Failed', 'Progress', 'Time'],
            array_map(function ($progress) {
                $data = $progress->toArray();

                $time = $data['status'] === 'scheduled'
                    ? 'at '.date('Y-m-d H:i', (int) $data['scheduled_at'])
                    : date('Y-m-d H:i', (int) ($data['started_at'] ?? $data['queued_at'] ?? time()));

                return [
                    $data['id'],
                    $data['status'],
                    $data['method'],
                    implode(', ', $data['audiences']),
                    $data['sent'],
                    $data['unreachable'],
                    $data['failed'],
                    is_null($data['percentage']) ? '-' : $data['percentage'].'%',
                    $time,
                ];
            }, $broadcasts)
        );

        return 0;
    }

    /**
     * Show the progress of a single broadcast.
     *
     * @param  \LaraGram\Broadcasting\BroadcastManager  $manager
     * @return int
     */
    protected function progress(BroadcastManager $manager)
    {
        $progress = $manager->progress($this->argument('id'));

        if (is_null($progress)) {
            $this->components->error('Broadcast ['.$this->argument('id').'] was not found.');

            return 1;
        }

        if ($this->option('json')) {
            $this->line($progress->toJson(JSON_PRETTY_PRINT));

            return 0;
        }

        $data = $progress->toArray();

        $this->newLine();

        foreach ([
            'Status' => $data['status'],
            'Method' => $data['method'],
            'Bot' => $data['bot'] ?? '-',
            'Audiences' => implode(', ', $data['audiences']),
            'Report to' => implode(', ', $data['report_to'] ?? []) ?: '-',
            'Recipients' => $data['total'] ?? 'counting...',
            'Sent' => $data['sent'],
            'Unreachable' => $data['unreachable'],
            'Failed' => $data['failed'],
            'Skipped' => $data['skipped'],
            'Progress' => is_null($data['percentage']) ? '-' : $data['percentage'].'%',
            'Scheduled for' => ! empty($data['scheduled_at']) ? date('Y-m-d H:i:s', $data['scheduled_at']) : '-',
            'Resumes at' => ! empty($data['resumes_at']) ? date('Y-m-d H:i:s', $data['resumes_at']) : '-',
            'Started' => ! empty($data['started_at']) ? date('Y-m-d H:i:s', $data['started_at']) : '-',
            'Finished' => ! empty($data['finished_at']) ? date('Y-m-d H:i:s', $data['finished_at']) : '-',
        ] as $label => $value) {
            $this->components->twoColumnDetail($label, (string) $value);
        }

        $this->newLine();

        return 0;
    }
}
