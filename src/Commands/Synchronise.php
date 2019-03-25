<?php

namespace mtolhuijs\LDS\Commands;

use Illuminate\Console\Command;
use mtolhuijs\LDS\DatabaseSynchronizer;

class Synchronise extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        db:sync
        { --from= : Synchronize data from this database instead of the one specified in config }
        { --to= : Synchronize data to this database instead of the one specified in config }
        { --t|tables=* : Only run for given table(s) }
        { --l|limit= : Limit query rows (defaults to 5000) }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes your \'from\' database with you\'re \'to\' database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $synchronizer = new DatabaseSynchronizer(
            $this->option('from') ?? config('database-synchronizer.from'),
            $this->option('to') ?? config('database-synchronizer.to'),
            $this
        );

        if ($this->option('tables')) {
            $synchronizer->tables = $this->option('tables');
        }

        if ($this->option('limit')) {
            $synchronizer->limit = (int) $this->option('limit');
        }

        $synchronizer->run();

        $this->info(PHP_EOL.'Synchronization done!');
    }
}
