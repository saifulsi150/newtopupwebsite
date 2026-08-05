<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PDO;

$config = config('database.connections.mysql');
$host = $config['host'] ?? '127.0.0.1';
$port = $config['port'] ?? '3306';
$username = $config['username'] ?? 'root';
$password = $config['password'] ?? '';
$sourceDatabase = $config['database'] ?? 'topup';
$destinationDatabases = ['topup_user', 'api_topup'];

function makeServerPdo(string $host, string $port, string $username, string $password): PDO
{
    return new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function makeDatabasePdo(string $host, string $port, string $username, string $password, string $database): PDO
{
    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

$serverPdo = makeServerPdo($host, $port, $username, $password);
$serverPdo->exec('SET FOREIGN_KEY_CHECKS=0');

$sourcePdo = makeDatabasePdo($host, $port, $username, $password, $sourceDatabase);
$tables = $sourcePdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$tableNames = array_map(static fn (array $row): string => $row[0], $tables);

foreach ($destinationDatabases as $destinationDatabase) {
    $serverPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', quoteIdentifier($destinationDatabase)));
    $destPdo = makeDatabasePdo($host, $port, $username, $password, $destinationDatabase);
    $destPdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tableNames as $tableName) {
        if ($destinationDatabase === 'topup_user' && $tableName === 'connected_websites') {
            continue;
        }

        $destPdo->exec(sprintf('DROP TABLE IF EXISTS %s', quoteIdentifier($tableName)));
        $destPdo->exec(sprintf(
            'CREATE TABLE %s LIKE %s',
            quoteIdentifier($tableName),
            quoteIdentifier($sourceDatabase) . '.' . quoteIdentifier($tableName)
        ));
        $destPdo->exec(sprintf(
            'INSERT INTO %s SELECT * FROM %s',
            quoteIdentifier($tableName),
            quoteIdentifier($sourceDatabase) . '.' . quoteIdentifier($tableName)
        ));
    }

    if ($destinationDatabase === 'topup_user') {
        $destPdo->exec('DROP TABLE IF EXISTS ' . quoteIdentifier('connected_websites'));
    }

    $destPdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

$serverPdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "Databases split completed from {$sourceDatabase} into: " . implode(', ', $destinationDatabases) . PHP_EOL;
