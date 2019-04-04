<?php

namespace mtolhuijs\LDS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSynchronizer
{
    const DEFAULT_LIMIT = 5000;

    public $cli;
    public $limit = self::DEFAULT_LIMIT;
    public $tables;
    public $skipTables;
    public $from;
    public $to;

    private $fromDB;
    private $toDB;

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

    public function setSkipTables($skipTables)
    {
        $this->skipTables = $skipTables;

        return $this;
    }

    public function setTables($tables)
    {
        $this->tables = $tables;

        return $this;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    protected function getFromDb()
    {
        if ($this->fromDB === null) {
            $this->fromDB = DB::connection($this->from);
        }

        return $this->fromDB;
    }

    protected function getToDb()
    {
        if ($this->toDB === null) {
            $this->toDB = DB::connection($this->to);
        }

        return $this->toDB;
    }

    public function run(): void
    {
        foreach ($this->getTables() as $table) {
            $this->feedback(PHP_EOL.PHP_EOL."Table: $table", 'line');

            $this->syncTable($table);
            $this->syncRows($table);
        }
    }

    /**
     * Check if tables and columns are present
     * Create or update them if not.
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
     * Fetch all rows in $this->from and insert or update $this->to.
     * @todo need to get the real primary key
     * @todo add limit offset setup
     * @todo investigate: insert into on duplicate key update
     *
     * @param string $table
     */
    public function syncRows(string $table): void
    {
        $queryColumn = $this->getFromDb()->getColumnListing($table)[0];
        $pdo = $this->getFromDb()->getPdo();

        $builder = $this->fromDB->table($table);
        $statement = $pdo->prepare($builder->toSql());

        if (! $statement instanceof  \PDOStatement) {
            return;
        }

        $statement->execute($builder->getBindings());
        $amount = $statement->rowCount();

        if ($this->cli) {
            if ($amount > 0) {
                $this->feedback("Synchronizing '$this->to.$table' rows", 'comment');
                $bar = $this->cli->getOutput()->createProgressBar($amount);
            } else {
                $this->feedback('No rows...', 'comment');
            }
        }

        while ($row = $statement->fetch(\PDO::FETCH_OBJ)) {
            $exists = $this->getToDb()->table($table)->where($queryColumn, $row->{$queryColumn})->first();

            if (! $exists) {
                $this->getToDb()->table($table)->insert((array) $row);
            } else {
                $this->getToDb()->table($table)->where($queryColumn, $row->{$queryColumn})->update((array) $row);
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
        if (empty($this->tables)) {
            $this->tables = $this->getFromDb()->getDoctrineSchemaManager()->listTableNames();
        }

        $skipTables = $this->skipTables;

        return array_filter($this->tables, function ($val) use ($skipTables) {
            return ! in_array($val, $skipTables);
        });
    }

    private function createTable(string $table, array $columns): void
    {
        $this->feedback("Creating '$this->to.$table' table", 'warn');

        Schema::connection($this->to)->create($table, function (Blueprint $table_bp) use ($table, $columns) {
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
            echo PHP_EOL.$msg.PHP_EOL;
        }
    }
}
