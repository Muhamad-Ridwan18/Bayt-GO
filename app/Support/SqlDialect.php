<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Abstraksi kecil agar query SQL portable di mysql/mariadb/pgsql/sqlite.
 * MongoDB tidak didukung: skema BaytGo relasional (FK, transaksi, lockForUpdate).
 */
final class SqlDialect
{
    /** @var list<string> */
    public const SUPPORTED_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlite'];

    public static function driver(?string $connection = null): string
    {
        return DB::connection($connection)->getDriverName();
    }

    public static function assertSupportedSqlDriver(?string $connection = null): void
    {
        $driver = self::driver($connection);
        if (! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new InvalidArgumentException(
                "Driver database [{$driver}] tidak didukung. Gunakan: ".implode(', ', self::SUPPORTED_DRIVERS).'.'
            );
        }
    }

    public static function isPostgres(?string $connection = null): bool
    {
        return self::driver($connection) === 'pgsql';
    }

    public static function isSqlite(?string $connection = null): bool
    {
        return self::driver($connection) === 'sqlite';
    }

    /** Ekspresi waktu “sekarang” untuk UPDATE/raw SQL. */
    public static function nowExpression(?string $connection = null): string
    {
        return self::isSqlite($connection) ? "datetime('now')" : 'NOW()';
    }

    /**
     * LIKE case-insensitive: ILIKE di Postgres, LOWER(...) LIKE di lainnya.
     *
     * @param  EloquentBuilder<\Illuminate\Database\Eloquent\Model>|QueryBuilder  $query
     */
    public static function whereLike(
        EloquentBuilder|QueryBuilder $query,
        string $column,
        string $pattern,
        bool $or = false,
    ): EloquentBuilder|QueryBuilder {
        $connection = $query->getConnection();
        $wrapped = $connection->getQueryGrammar()->wrap($column);
        $method = $or ? 'orWhereRaw' : 'whereRaw';

        if ($connection->getDriverName() === 'pgsql') {
            $query->{$method}("{$wrapped} ILIKE ?", [$pattern]);

            return $query;
        }

        $query->{$method}("LOWER({$wrapped}) LIKE ?", [mb_strtolower($pattern, 'UTF-8')]);

        return $query;
    }

    /**
     * @param  EloquentBuilder<\Illuminate\Database\Eloquent\Model>|QueryBuilder  $query
     */
    public static function orWhereLike(
        EloquentBuilder|QueryBuilder $query,
        string $column,
        string $pattern,
    ): EloquentBuilder|QueryBuilder {
        return self::whereLike($query, $column, $pattern, true);
    }

    public static function indexExists(string $table, string $index, ?string $connection = null): bool
    {
        $conn = DB::connection($connection);
        $driver = $conn->getDriverName();
        $schema = $conn->getSchemaBuilder();

        if (method_exists($schema, 'hasIndex')) {
            return (bool) $schema->hasIndex($table, $index);
        }

        try {
            if ($driver === 'pgsql') {
                $rows = $conn->select(
                    'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                    [$table, $index]
                );

                return $rows !== [];
            }

            if ($driver === 'sqlite') {
                $rows = $conn->select('PRAGMA index_list('.$conn->getQueryGrammar()->wrapTable($table).')');
                foreach ($rows as $row) {
                    $name = is_object($row) ? ($row->name ?? null) : ($row['name'] ?? null);
                    if ($name === $index) {
                        return true;
                    }
                }

                return false;
            }

            // mysql / mariadb
            $database = $conn->getDatabaseName();
            $rows = $conn->select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $index]
            );

            return $rows !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    public static function registerQueryMacros(): void
    {
        $apply = function (string $column, string $pattern, bool $or = false) {
            /** @var EloquentBuilder<\Illuminate\Database\Eloquent\Model>|QueryBuilder $this */
            return SqlDialect::whereLike($this, $column, $pattern, $or);
        };

        QueryBuilder::macro('whereILike', function (string $column, string $pattern) use ($apply) {
            return $apply->call($this, $column, $pattern, false);
        });

        QueryBuilder::macro('orWhereILike', function (string $column, string $pattern) use ($apply) {
            return $apply->call($this, $column, $pattern, true);
        });

        EloquentBuilder::macro('whereILike', function (string $column, string $pattern) use ($apply) {
            return $apply->call($this, $column, $pattern, false);
        });

        EloquentBuilder::macro('orWhereILike', function (string $column, string $pattern) use ($apply) {
            return $apply->call($this, $column, $pattern, true);
        });
    }
}
