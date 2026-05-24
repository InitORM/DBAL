<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\DataMapper;

use InitORM\DBAL\DataMapper\DataMapper;
use InitORM\DBAL\Tests\Support\SqliteHelper;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DataMapperTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $connection = SqliteHelper::makeConnection();
        SqliteHelper::seedUsers($connection);
        $this->pdo = $connection->getPDO();
    }

    public function test_executes_a_statement(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id FROM users WHERE name = :name');
        $mapper = new DataMapper($stmt);

        self::assertTrue($mapper->bindValues(['name' => 'Alice']));
        self::assertTrue($mapper->execute());
    }

    public function test_bindValue_accepts_keys_with_or_without_colon(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id FROM users WHERE name = :name');
        $mapper = new DataMapper($stmt);

        $mapper->bindValue(':name', 'Alice');
        $mapper->execute();
        $row = $mapper->asAssoc()->row();
        self::assertSame(1, (int) $row['id']);

        $stmt2   = $this->pdo->prepare('SELECT id FROM users WHERE name = :name');
        $mapper2 = new DataMapper($stmt2);
        $mapper2->bindValue('name', 'Alice');
        $mapper2->execute();
        self::assertSame(1, (int) $mapper2->asAssoc()->row()['id']);
    }

    public function test_bind_chooses_correct_pdo_param_type(): void
    {
        $stmt   = $this->pdo->prepare('SELECT 1');
        $mapper = new DataMapper($stmt);

        self::assertSame(PDO::PARAM_INT,  $mapper->bind(7));
        self::assertSame(PDO::PARAM_BOOL, $mapper->bind(true));   // BUG-10 regression
        self::assertSame(PDO::PARAM_BOOL, $mapper->bind(false));
        self::assertSame(PDO::PARAM_NULL, $mapper->bind(null));
        self::assertSame(PDO::PARAM_STR,  $mapper->bind('abc'));
        self::assertSame(PDO::PARAM_STR,  $mapper->bind(1.5));
    }

    public function test_rows_returns_empty_array_when_no_match(): void
    {
        $stmt   = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $mapper = new DataMapper($stmt);
        $mapper->bindValue('id', 999);
        $mapper->execute();

        self::assertSame([], $mapper->rows());   // BUG-15 regression
    }

    public function test_row_returns_null_when_no_row_remaining(): void
    {
        $stmt   = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $mapper = new DataMapper($stmt);
        $mapper->bindValue('id', 999);
        $mapper->execute();

        self::assertNull($mapper->row());
    }

    public function test_asAssoc_returns_associative_arrays(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id, name FROM users ORDER BY id');
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        $rows = $mapper->asAssoc()->rows();
        self::assertSame('Alice', $rows[0]['name']);
        self::assertArrayNotHasKey(0, $rows[0]);
    }

    public function test_asObject_returns_stdClass_instances(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id, name FROM users ORDER BY id');
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        $row = $mapper->asObject()->row();
        self::assertInstanceOf(stdClass::class, $row);
        self::assertSame('Alice', $row->name);
    }

    public function test_asObject_with_target_fills_existing_object(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id, name FROM users ORDER BY id');
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        $target = new stdClass();
        $mapper->asObject($target);
        $mapper->row();
        self::assertSame('Alice', $target->name);
    }

    public function test_asClass_returns_named_class_instances(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id, name FROM users ORDER BY id');
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        $row = $mapper->asClass(UserRow::class)->row();
        self::assertInstanceOf(UserRow::class, $row);
        self::assertSame('Alice', $row->name);
    }

    public function test_asArray_and_asBoth_are_equivalent(): void
    {
        $stmt   = $this->pdo->prepare('SELECT id, name FROM users WHERE id = :id');
        $mapper = new DataMapper($stmt);
        $mapper->bindValue('id', 1);
        $mapper->execute();
        $row = $mapper->asBoth()->row();

        self::assertArrayHasKey(0, $row);
        self::assertArrayHasKey('name', $row);
    }

    public function test_numRows_reports_affected_count_after_insert(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email) VALUES ('Dan', 'dan@example.com')");
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        self::assertSame(1, $mapper->numRows());
    }

    public function test_forwards_pdo_statement_methods_via_call(): void
    {
        $stmt   = $this->pdo->prepare('SELECT name FROM users ORDER BY id');
        $mapper = new DataMapper($stmt);
        $mapper->execute();

        // closeCursor() returns true (PDOStatement, not $this) — should pass through.
        self::assertTrue($mapper->closeCursor());
    }

    public function test_getQuery_returns_the_prepared_sql(): void
    {
        $sql    = 'SELECT 1 AS one';
        $stmt   = $this->pdo->prepare($sql);
        $mapper = new DataMapper($stmt);

        self::assertSame($sql, $mapper->getQuery());
    }
}

final class UserRow
{
    public int $id    = 0;
    public string $name = '';
}
