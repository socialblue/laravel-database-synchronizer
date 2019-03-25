<?php

namespace mtolhuijs\LDS;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseSynchronizer
{
    public
        $cli,
        $limit = 5000,
        $tables,
        $from,
        $to;

    private
        $fromDB,
        $toDB;

    public function __construct(string $from, string $to, $cli = false)
    {
        $this->from = $from;
        $this->to = $to;

        if ($cli) {
            $this->cli = $cli;
        }

        try {
            $this->fromDB = DB::connection($this->from);
            $this->toDB = DB::connection($this->to);
        } catch (\Exception $e) {
            $this->feedback($e->getMessage(), 'error');

            exit();
        }
    }

    public function run(): void
    {
        foreach ($this->getTables() as $table) {
            $this->feedback(PHP_EOL.PHP_EOL . "Table: $table", 'line');

            $this->syncTable($table);
            $this->syncRows($table);
        }
    }

    /**
     * Check if tables and columns are present
     * Create or update them if not
     *
     * @param string $table
     */
    public function syncTable(string $table): void
    {
        $schema = Schema::connection($this->to);
        $columns = Schema::connection($this->from)->getColumnListing($table);

        if ($schema->hasTable($table)) {
            foreach ($columns as $column) {
                if ($schema->hasColumn($table, $column)) {
                    continue;
                }

                $this->updateTable($table, $column);
            }

            return;
        }

        $this->createTable($table, $columns);
    }

    /**
     * Fetch all rows in $this->from and insert or update $this->to
     *
     * @param string $table
     */
    public function syncRows(string $table): void
    {
        $queryColumn = Schema::connection($this->from)->getColumnListing($table)[0];
        $rows = $this->fromDB->table($table)->orderBy($queryColumn, 'DESC')->take($this->limit)->get();
        $amount = count($rows);

        if ($this->cli) {
            if ($amount > 0) {
                $this->feedback("Synchronizing '$this->to.$table' rows", 'comment');
                $bar = $this->cli->getOutput()->createProgressBar($amount);
            } else {
                $this->feedback('No rows...', 'comment');
            }
        }

        foreach ($rows as $row) {
            $exists = $this->toDB->table($table)->where($queryColumn, $row->{$queryColumn})->first();

            if (! $exists) {
                $this->toDB->table($table)->insert((array)$row);
            } else {
                $this->toDB->table($table)->where($queryColumn, $row->{$queryColumn})->update((array)$row);
            }

            if (isset($bar)) {
                $bar->advance();
            }
        }

        if (isset($bar)) {
            $bar->finish();
        }
    }

    public function getTables(): array
    {
        if (!empty($this->tables)) {
            return $this->tables;
        }

        return DB::connection($this->from)->getDoctrineSchemaManager()->listTableNames();
    }

    private function createTable(string $table, array $columns): void
    {
        $this->feedback("Creating '$this->to.$table' table", 'warn');

        Schema::connection($this->to)->create($table, function (Blueprint $table_bp) use($table, $columns) {
            foreach ($columns as $column) {
                $type = Schema::connection($this->from)->getColumnType($table, $column);

                $table_bp->{$type}($column)->nullable();

                $this->feedback("Added {$type}('$column')->nullable()");
            }
        });
    }

    private function updateTable(string $table, string $column): void
    {
        Schema::connection($this->to)->table($table, function (Blueprint $table_bp) use ($table, $column) {
            $type = Schema::connection($this->from)->getColumnType($table, $column);

            $table_bp->{$type}($column)->nullable();

            $this->feedback("Added {$type}('$column')->nullable()");
        });
    }

    private function feedback(string $msg, $type = 'info'): void
    {
        if ($this->cli) {
            $this->cli->{$type}($msg);
        } else {
            echo PHP_EOL . $msg . PHP_EOL;
        }
    }
}
