<?php
declare(strict_types=1);

final class Database
{
    private PDO $connection;

    public function __construct(array $config)
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'], $config['port'], $config['name']);
        $this->connection = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->connection->exec("SET time_zone = '+00:00'");
        $this->connection->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    public function connection(): PDO
    {
        return $this->connection;
    }
}
