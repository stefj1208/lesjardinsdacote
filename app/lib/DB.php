<?php
declare(strict_types=1);

final class DB
{
    public PDO $pdo;

    public function __construct(string $host, string $name, string $user, string $pass)
    {
        $port = null;
        if (str_contains($host, ':')) [$host, $port] = explode(':', $host, 2);
        $dsn = 'mysql:host=' . $host . ($port ? ';port=' . $port : '') . ';dbname=' . $name . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $this->pdo->exec("SET time_zone = '" . date('P') . "'");
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($params) === $params ? $params : $params);
        return $st;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $r = $this->run($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public function val(string $sql, array $params = [])
    {
        $r = $this->run($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    public function col(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function exec(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        $this->run($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => '`' . $c . '` = ?', array_keys($data)));
        return $this->exec('UPDATE `' . $table . '` SET ' . $set . ' WHERE ' . $where, array_merge(array_values($data), $whereParams));
    }

    /** Exécute $fn dans une transaction (verrous SELECT … FOR UPDATE possibles). */
    public function tx(callable $fn)
    {
        if ($this->pdo->inTransaction()) return $fn($this);
        $this->pdo->beginTransaction();
        try {
            $r = $fn($this);
            $this->pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Numérotation séquentielle sans trou (commandes, factures). */
    public function nextSequence(string $name): int
    {
        return $this->tx(function (DB $db) use ($name) {
            $db->exec('INSERT IGNORE INTO sequences (name, value) VALUES (?, 0)', [$name]);
            $v = (int)$db->val('SELECT value FROM sequences WHERE name = ? FOR UPDATE', [$name]) + 1;
            $db->exec('UPDATE sequences SET value = ? WHERE name = ?', [$v, $name]);
            return $v;
        });
    }
}
