<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Migration runner is CLI only.');
}

$root = dirname(__DIR__);
$force = in_array('--force', $argv, true);
$env = read_env_file($root . '/.env');

$host = require_env($env, 'DB_HOST');
$port = (int)($env['DB_PORT'] ?? 3306);
$database = require_env($env, 'DB_DATABASE');
$username = require_env($env, 'DB_USERNAME');
$password = require_env($env, 'DB_PASSWORD');
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS database_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        checksum CHAR(64) NOT NULL,
        batch INT NOT NULL DEFAULT 1,
        ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$ran = [];
foreach ($pdo->query('SELECT migration FROM database_migrations')->fetchAll() as $row) {
    $ran[(string)$row['migration']] = true;
}

$files = glob($root . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

$pending = array_values(array_filter($files, static function (string $file) use ($ran): bool {
    return empty($ran[basename($file)]);
}));

if (!$pending) {
    echo "No pending migrations.\n";
    exit(0);
}

$batch = ((int)$pdo->query('SELECT COALESCE(MAX(batch), 0) FROM database_migrations')->fetchColumn()) + 1;

foreach ($pending as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Could not read migration: {$name}");
    }

    if (contains_dangerous_sql($sql) && !$force) {
        echo "Dangerous SQL detected in {$name} (DROP TABLE / DROP DATABASE).\n";
        echo "Type RUN to execute this migration: ";
        $answer = trim((string)fgets(STDIN));
        if ($answer !== 'RUN') {
            echo "Skipped {$name}.\n";
            exit(1);
        }
    }

    echo "Running {$name}...\n";
    foreach (split_sql_statements($sql) as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }

    $stmt = $pdo->prepare('INSERT INTO database_migrations (migration, checksum, batch) VALUES (:migration, :checksum, :batch)');
    $stmt->execute([
        'migration' => $name,
        'checksum' => hash('sha256', $sql),
        'batch' => $batch,
    ]);
    echo "Done {$name}.\n";
}

echo "Migrations complete.\n";

function read_env_file(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Missing .env file. Copy .env.example to .env and set DB credentials.');
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $position = strpos($line, '=');
        if ($position === false) {
            continue;
        }

        $key = trim(substr($line, 0, $position));
        $value = trim(substr($line, $position + 1));
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $values[$key] = $value;
    }

    return $values;
}

function require_env(array $env, string $key): string
{
    $value = trim((string)($env[$key] ?? ''));
    if ($value === '') {
        throw new RuntimeException("Missing {$key} in .env");
    }

    return $value;
}

function contains_dangerous_sql(string $sql): bool
{
    return preg_match('/\bDROP\s+(TABLE|DATABASE)\b/i', $sql) === 1;
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $buffer .= $char;

        if ($quote !== null) {
            if ($char === '\\' && !$escaped) {
                $escaped = true;
                continue;
            }

            if ($char === $quote && !$escaped) {
                $quote = null;
            }

            $escaped = false;
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = substr($buffer, 0, -1);
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
