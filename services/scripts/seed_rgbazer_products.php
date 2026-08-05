<?php

declare(strict_types=1);

/**
 * Seed rgbazer product/image links into local DB.
 * Usage: php scripts/seed_rgbazer_products.php
 */

function parseEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === '\'' && substr($value, -1) === '\''))) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
    }

    return $vars;
}

$rootEnv = parseEnvFile(__DIR__ . '/../.env');
$frontendEnv = parseEnvFile(__DIR__ . '/../frontend-legacy-nuxt/.env');
$env = array_merge($rootEnv, $frontendEnv);

$dbHost = $env['DB_HOST'] ?? $env['MYSQL_HOST'] ?? '127.0.0.1';
$dbPort = (int)($env['DB_PORT'] ?? $env['MYSQL_PORT'] ?? 3306);
$dbName = $env['DB_DATABASE'] ?? $env['MYSQL_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? $env['MYSQL_USER'] ?? 'root';
$dbPass = $env['DB_PASSWORD'] ?? $env['MYSQL_PASSWORD'] ?? '';

if ($dbName === '') {
    fwrite(STDERR, "Database name was not found in .env files.\n");
    exit(1);
}

$products = [
    ['title' => 'Free Fire TopUp (BD)', 'slug' => 'free-fire-topup', 'image_url' => 'https://admin.rgbazer.com/products/1772817289.jpg', 'price_from' => 120, 'subtitle' => 'Secure topup checkout'],
    ['title' => 'Tiktok Coin', 'slug' => 'tiktok-coin', 'image_url' => 'https://admin.rgbazer.com/products/1785140846.jpg', 'price_from' => 180, 'subtitle' => 'Instant topup checkout'],
    ['title' => 'FRIDAY OFFER', 'slug' => 'friday-offer', 'image_url' => 'https://admin.rgbazer.com/products/1773896706.png', 'price_from' => 140, 'subtitle' => 'Special offer topup checkout'],
    ['title' => 'Unipin Voucher (BD)', 'slug' => 'unipin-voucher-bd', 'image_url' => 'https://admin.rgbazer.com/products/1772817579.jpg', 'price_from' => 79, 'subtitle' => 'Voucher purchase checkout'],
    ['title' => 'Weekly&Monthly', 'slug' => 'weekly-monthly', 'image_url' => 'https://admin.rgbazer.com/products/1772820023.jpg', 'price_from' => 158, 'subtitle' => 'Bundle purchase checkout'],
    ['title' => 'Weekly Lite (BD Server)', 'slug' => 'weekly-lite-bd-server', 'image_url' => 'https://admin.rgbazer.com/products/1772818245.jpg', 'price_from' => 90, 'subtitle' => 'BD server topup checkout'],
    ['title' => 'E-Badge/Evo Access (BD)', 'slug' => 'e-badge-evo-access-bd', 'image_url' => 'https://admin.rgbazer.com/products/1772818351.jpg', 'price_from' => 110, 'subtitle' => 'Access package checkout'],
    ['title' => 'New Level Up Pass', 'slug' => 'new-level-up-pass', 'image_url' => 'https://admin.rgbazer.com/products/1772818450.jpg', 'price_from' => 130, 'subtitle' => 'Pass package checkout'],
];

$packages = [
    'free-fire-topup' => [['title' => '25 Diamond', 'price' => 20], ['title' => '50 Diamond', 'price' => 35], ['title' => '115 Diamond', 'price' => 79], ['title' => '240 Diamond', 'price' => 158]],
    'tiktok-coin' => [['title' => 'Basic Pack', 'price' => 180], ['title' => 'Standard Pack', 'price' => 360]],
    'friday-offer' => [['title' => 'Offer Pack', 'price' => 140], ['title' => 'Offer Pack Plus', 'price' => 280]],
    'unipin-voucher-bd' => [['title' => 'Voucher 79', 'price' => 79], ['title' => 'Voucher 158', 'price' => 158]],
    'weekly-monthly' => [['title' => 'Weekly', 'price' => 158], ['title' => 'Monthly', 'price' => 790]],
    'weekly-lite-bd-server' => [['title' => 'Weekly Lite', 'price' => 90], ['title' => '2x Weekly Lite', 'price' => 180]],
    'e-badge-evo-access-bd' => [['title' => 'E-Badge', 'price' => 110], ['title' => 'Evo Access', 'price' => 240]],
    'new-level-up-pass' => [['title' => 'Level Up Pass', 'price' => 130], ['title' => '2x Level Up Pass', 'price' => 260]],
];

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('products', $tables, true)) {
    fwrite(STDERR, "Table 'products' not found.\n");
    exit(1);
}

$defaultCategoryId = 1;
if (in_array('categories', $tables, true)) {
    $categoryCount = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($categoryCount === 0) {
        $categoryColumns = $pdo->query('SHOW COLUMNS FROM categories')->fetchAll();
        $catFields = array_map(static fn(array $row): string => $row['Field'], $categoryColumns);
        $cols = [];
        $vals = [];
        $bind = [];

        foreach ($catFields as $field) {
            if ($field === 'id') {
                continue;
            }
            if ($field === 'title') {
                $cols[] = 'title';
                $vals[] = '?';
                $bind[] = 'Free Fire';
                continue;
            }
            if ($field === 'slot') {
                $cols[] = 'slot';
                $vals[] = '?';
                $bind[] = '1';
                continue;
            }
            if ($field === 'status') {
                $cols[] = 'status';
                $vals[] = '?';
                $bind[] = 'show';
                continue;
            }
            if ($field === 'created_at' || $field === 'updated_at') {
                $cols[] = $field;
                $vals[] = 'NOW()';
            }
        }

        if (!empty($cols)) {
            $sql = sprintf('INSERT INTO categories (%s) VALUES (%s)', implode(',', $cols), implode(',', $vals));
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
        }
    }

    $firstCategoryId = $pdo->query('SELECT id FROM categories ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($firstCategoryId !== false) {
        $defaultCategoryId = (int)$firstCategoryId;
    }
}

$productColumnRows = $pdo->query('SHOW COLUMNS FROM products')->fetchAll();
$productColumns = array_map(static fn(array $row): string => $row['Field'], $productColumnRows);
$has = static fn(string $name): bool => in_array($name, $productColumns, true);

$requiredProductCols = [];
foreach ($productColumnRows as $row) {
    $field = $row['Field'];
    $nullable = strtoupper((string)$row['Null']) === 'YES';
    $hasDefault = array_key_exists('Default', $row) && $row['Default'] !== null;
    $isAutoIncrement = str_contains(strtolower((string)($row['Extra'] ?? '')), 'auto_increment');

    if (!$nullable && !$hasDefault && !$isAutoIncrement) {
        $requiredProductCols[$field] = (string)$row['Type'];
    }
}

$pdo->beginTransaction();

try {
    $deleteStmt = $pdo->prepare('DELETE FROM products WHERE slug = ?');
    foreach ($products as $item) {
        if ($has('slug')) {
            $deleteStmt->execute([$item['slug']]);
        }

        $cols = [];
        $vals = [];
        $bind = [];

        foreach (['title', 'name', 'slug', 'categorie_id', 'image_url', 'price_from', 'subtitle', 'is_active', 'sort_order'] as $col) {
            if (!$has($col)) {
                continue;
            }

            $cols[] = $col;
            $vals[] = '?';

            if ($col === 'name') {
                $bind[] = $item['title'];
            } elseif ($col === 'categorie_id') {
                $bind[] = $defaultCategoryId;
            } elseif ($col === 'is_active') {
                $bind[] = 1;
            } elseif ($col === 'sort_order') {
                $bind[] = 0;
            } else {
                $bind[] = $item[$col] ?? null;
            }
        }

        // Fill any remaining mandatory product columns with safe defaults.
        foreach ($requiredProductCols as $col => $type) {
            if (in_array($col, $cols, true)) {
                continue;
            }

            $cols[] = $col;
            $vals[] = '?';

            $t = strtolower($type);
            if ($col === 'categorie_id' || $col === 'category_id') {
                $bind[] = 1;
            } elseif ($col === 'input') {
                $bind[] = '';
            } elseif ($col === 'source') {
                $bind[] = 'local';
            } elseif ($col === 'type') {
                $bind[] = 'INGAME';
            } elseif ($col === 'category') {
                $bind[] = 'free-fire';
            } elseif ($col === 'image') {
                $bind[] = $item['image_url'];
            } elseif ($col === 'content') {
                $bind[] = $item['subtitle'];
            } elseif ($col === 'categorie_id' || $col === 'category_id') {
                $bind[] = $defaultCategoryId;
            } elseif ($col === 'status') {
                $bind[] = 1;
            } elseif ($col === 'has_tutorial' || $col === 'slot') {
                $bind[] = 0;
            } elseif ($col === 'description') {
                $bind[] = $item['subtitle'];
            } elseif ($col === 'is_active') {
                $bind[] = 1;
            } elseif ($col === 'sort_order') {
                $bind[] = 0;
            } elseif (str_contains($t, 'int') || str_contains($t, 'decimal') || str_contains($t, 'float') || str_contains($t, 'double')) {
                $bind[] = 0;
            } elseif (str_contains($t, 'tinyint(1)')) {
                $bind[] = 1;
            } else {
                $bind[] = '';
            }
        }

        if ($has('created_at')) {
            $cols[] = 'created_at';
            $vals[] = 'NOW()';
        }
        if ($has('updated_at')) {
            $cols[] = 'updated_at';
            $vals[] = 'NOW()';
        }

        $sql = sprintf('INSERT INTO products (%s) VALUES (%s)', implode(',', $cols), implode(',', $vals));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
    }

    $packageTable = null;
    if (in_array('topup_packages', $tables, true)) {
        $packageTable = 'topup_packages';
    } elseif (in_array('product_packages', $tables, true)) {
        $packageTable = 'product_packages';
    }

    if ($packageTable !== null && $has('slug')) {
        $pkgColumns = $pdo->query("SHOW COLUMNS FROM {$packageTable}")->fetchAll(PDO::FETCH_COLUMN);
        $pkgHas = static fn(string $name): bool => in_array($name, $pkgColumns, true);

        $productRows = $pdo->query('SELECT id, slug FROM products')->fetchAll();
        $idBySlug = [];
        foreach ($productRows as $row) {
            $idBySlug[$row['slug']] = (int)$row['id'];
        }

        foreach ($packages as $slug => $list) {
            $productId = $idBySlug[$slug] ?? null;
            if (!$productId) {
                continue;
            }

            if ($pkgHas('product_id')) {
                $delPkg = $pdo->prepare("DELETE FROM {$packageTable} WHERE product_id = ?");
                $delPkg->execute([$productId]);
            }

            foreach ($list as $i => $pkg) {
                $cols = [];
                $vals = [];
                $bind = [];

                foreach (['product_id', 'title', 'name', 'price', 'is_active', 'sort_order'] as $col) {
                    if (!$pkgHas($col)) {
                        continue;
                    }

                    $cols[] = $col;
                    $vals[] = '?';

                    if ($col === 'product_id') {
                        $bind[] = $productId;
                    } elseif ($col === 'title' || $col === 'name') {
                        $bind[] = $pkg['title'];
                    } elseif ($col === 'is_active') {
                        $bind[] = 1;
                    } elseif ($col === 'sort_order') {
                        $bind[] = $i;
                    } else {
                        $bind[] = $pkg[$col] ?? null;
                    }
                }

                if ($pkgHas('created_at')) {
                    $cols[] = 'created_at';
                    $vals[] = 'NOW()';
                }
                if ($pkgHas('updated_at')) {
                    $cols[] = 'updated_at';
                    $vals[] = 'NOW()';
                }

                $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $packageTable, implode(',', $cols), implode(',', $vals));
                $stmt = $pdo->prepare($sql);
                $stmt->execute($bind);
            }
        }
    }

    $pdo->commit();
    echo "Seed completed successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed failed: " . $e->getMessage() . "\n");
    exit(1);
}
