<?php

namespace Tests\Unit\Support;

use App\Support\SqlDialect;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlDialectTest extends TestCase
{
    public function test_supported_drivers_include_sql_family(): void
    {
        $this->assertContains(SqlDialect::driver(), SqlDialect::SUPPORTED_DRIVERS);
        SqlDialect::assertSupportedSqlDriver();
    }

    public function test_where_i_like_macro_builds_portable_sql(): void
    {
        SqlDialect::registerQueryMacros();

        /** @var Builder $query */
        $query = DB::table('users')->whereILike('name', '%Ada%');
        $sql = strtolower($query->toSql());

        if (SqlDialect::isPostgres()) {
            $this->assertStringContainsString('ilike', $sql);
        } else {
            $this->assertStringContainsString('lower(', $sql);
            $this->assertStringContainsString('like', $sql);
        }
    }

    public function test_now_expression_for_sqlite_vs_others(): void
    {
        if (SqlDialect::isSqlite()) {
            $this->assertSame("datetime('now')", SqlDialect::nowExpression());
        } else {
            $this->assertSame('NOW()', SqlDialect::nowExpression());
        }
    }
}
