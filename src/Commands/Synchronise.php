<?php

namespace mtolhuijs\LDS\Commands;

use Illuminate\Console\Command;
use mtolhuijs\LDS\DatabaseSynchronizer;
use mtolhuijs\LDS\Exceptions\DatabaseConnectionException;

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
        { --t|table=* : Only run for given table(s) (Only used if optional --tables is given) }
        { --l|limit= : Limit query rows (defaults to 5000) }
        { --truncate : Truncate before inserting data }
        { --tables : Use tables specified through config or options }
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
        try {
            $synchronizer = new DatabaseSynchronizer(
                $this->option('from') ?? config('database-synchronizer.from'),
                $this->option('to') ?? config('database-synchronizer.to'),
                $this
            );
        } catch (DatabaseConnectionException $e) {
            $this->error($e->getMessage());

            return;
        }

        if ($this->option('tables')) {
            $synchronizer->tables = $this->getTables();
        }

        if ($this->option('limit')) {
            $synchronizer->limit = (int) $this->option('limit');
        }

        $synchronizer->truncate = $this->option('truncate');

        $synchronizer->run();

        $this->info(PHP_EOL.'Synchronization done!');
    }

    private function getTables()
    {
        return empty($this->option('table')) ?
            config('database-synchronizer.tables') : $this->option('table');
    }
}
