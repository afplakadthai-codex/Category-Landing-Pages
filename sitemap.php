<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = defined('APP_URL') ? rtrim((string) APP_URL, '/') : 'https://www.bettavaro.com';
$today = gmdate('Y-m-d');

$urls = [
    [
        'loc' => $baseUrl . '/',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '1.0',
    ],
    [
        'loc' => $baseUrl . '/listings.php',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '0.9',
    ],
];

$xmlEscape = static function (string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
};

$getDate = static function ($value, string $fallback) use ($today): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_numeric($value)) {
        $ts = (int) $value;
        if ($ts > 0) {
            return gmdate('Y-m-d', $ts);
        }
    }

    if (is_string($value) && trim($value) !== '') {
        $ts = strtotime($value);
        if ($ts !== false) {
            return gmdate('Y-m-d', $ts);
        }
    }

    if ($fallback !== '') {
        return $fallback;
    }

    return $today;
};

$getPdoFromGlobals = static function (): ?PDO {
    foreach (['pdo', 'PDO', 'db', 'conn'] as $name) {
        if (!array_key_exists($name, $GLOBALS)) {
            continue;
        }

        $candidate = $GLOBALS[$name];
        if ($candidate instanceof PDO) {
            return $candidate;
        }

        if (is_object($candidate) && method_exists($candidate, 'getPdo')) {
            try {
                $pdo = $candidate->getPdo();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            } catch (Throwable $e) {
                // Ignore and continue.
            }
        }
    }

    return null;
};

$getMysqliFromGlobals = static function (): ?mysqli {
    foreach (['conn', 'db', 'mysqli', 'link'] as $name) {
        if (array_key_exists($name, $GLOBALS) && $GLOBALS[$name] instanceof mysqli) {
            return $GLOBALS[$name];
        }
    }

    return null;
};

$findTableName = static function ($db, string $candidate): ?string {
    if ($db instanceof PDO) {
        $stmt = $db->prepare('SHOW TABLES LIKE ?');
        if ($stmt && $stmt->execute([$candidate]) && $stmt->fetchColumn() !== false) {
            return $candidate;
        }

        return null;
    }

    if ($db instanceof mysqli) {
        $safe = $db->real_escape_string($candidate);
        $res = $db->query("SHOW TABLES LIKE '{$safe}'");
        if ($res instanceof mysqli_result) {
            $row = $res->fetch_row();
            $res->free();
            if ($row) {
                return $candidate;
            }
        }

        return null;
    }

    return null;
};

$getColumns = static function ($db, string $table): array {
    $columns = [];

    if ($db instanceof PDO) {
        $stmt = $db->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['Field'])) {
                    $columns[strtolower((string) $row['Field'])] = true;
                }
            }
        }

        return $columns;
    }

    if ($db instanceof mysqli) {
        $res = $db->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        if ($res instanceof mysqli_result) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['Field'])) {
                    $columns[strtolower((string) $row['Field'])] = true;
                }
            }
            $res->free();
        }
    }

    return $columns;
};

try {
    foreach ([__DIR__ . '/config/db.php', __DIR__ . '/includes/db.php', __DIR__ . '/db.php'] as $bootstrap) {
        if (is_file($bootstrap)) {
            require_once $bootstrap;
        }
    }

    $pdo = $getPdoFromGlobals();
    $mysqli = $pdo ? null : $getMysqliFromGlobals();
    $db = $pdo ?: $mysqli;

    if ($db instanceof PDO || $db instanceof mysqli) {
        $table = null;
        foreach (['listings', 'listing'] as $tableCandidate) {
            $table = $findTableName($db, $tableCandidate);
            if ($table !== null) {
                break;
            }
        }

        if ($table !== null) {
            $columns = $getColumns($db, $table);
            if (isset($columns['id'])) {
                $selectParts = ['`id`'];
                if (isset($columns['slug'])) {
                    $selectParts[] = '`slug`';
                }
                if (isset($columns['status'])) {
                    $selectParts[] = '`status`';
                }
                if (isset($columns['sale_status'])) {
                    $selectParts[] = '`sale_status`';
                }
                if (isset($columns['updated_at'])) {
                    $selectParts[] = '`updated_at`';
                }
                if (isset($columns['created_at'])) {
                    $selectParts[] = '`created_at`';
                }

                $whereParts = [];
                if (isset($columns['status'])) {
                    $whereParts[] = "LOWER(`status`) IN ('active','available','published')";
                }
                if (isset($columns['sale_status'])) {
                    $whereParts[] = "LOWER(`sale_status`) IN ('available','reserved','sold')";
                }

                $sql = 'SELECT ' . implode(', ', $selectParts)
                    . ' FROM `' . str_replace('`', '``', $table) . '`';

                if (!empty($whereParts)) {
                    $sql .= ' WHERE (' . implode(' OR ', $whereParts) . ')';
                }

                if (isset($columns['updated_at'])) {
                    $sql .= ' ORDER BY `updated_at` DESC';
                } elseif (isset($columns['created_at'])) {
                    $sql .= ' ORDER BY `created_at` DESC';
                } else {
                    $sql .= ' ORDER BY `id` DESC';
                }

                $sql .= ' LIMIT 50000';

                $rows = [];
                if ($db instanceof PDO) {
                    $stmt = $db->query($sql);
                    if ($stmt) {
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } elseif ($db instanceof mysqli) {
                    $res = $db->query($sql);
                    if ($res instanceof mysqli_result) {
                        while ($row = $res->fetch_assoc()) {
                            $rows[] = $row;
                        }
                        $res->free();
                    }
                }

                foreach ($rows as $row) {
                    $id = isset($row['id']) ? (int) $row['id'] : 0;
                    if ($id <= 0) {
                        continue;
                    }

                    $slug = isset($row['slug']) ? trim((string) $row['slug']) : '';
                    $loc = $slug !== ''
                        ? $baseUrl . '/listing.php?slug=' . rawurlencode($slug)
                        : $baseUrl . '/listing.php?id=' . $id;

                    $status = strtolower(trim((string) ($row['status'] ?? '')));
                    $saleStatus = strtolower(trim((string) ($row['sale_status'] ?? '')));

                    $priority = '0.8';
                    if (in_array($saleStatus, ['reserved', 'sold'], true)) {
                        $priority = '0.6';
                    } elseif ($saleStatus === 'available' || in_array($status, ['active', 'available', 'published'], true)) {
                        $priority = '0.8';
                    }

                    $lastmod = $today;
                    if (array_key_exists('updated_at', $row) && $row['updated_at'] !== null && $row['updated_at'] !== '') {
                        $lastmod = $getDate($row['updated_at'], $lastmod);
                    } elseif (array_key_exists('created_at', $row) && $row['created_at'] !== null && $row['created_at'] !== '') {
                        $lastmod = $getDate($row['created_at'], $lastmod);
                    }

                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => $lastmod,
                        'changefreq' => 'weekly',
                        'priority' => $priority,
                    ];
                }
            }
        }
    }
} catch (Throwable $e) {
    // Fail safe: static URLs still render.
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($urls as $entry) {
    echo "  <url>\n";
    echo '    <loc>' . $xmlEscape((string) $entry['loc']) . "</loc>\n";
    echo '    <lastmod>' . $xmlEscape((string) $entry['lastmod']) . "</lastmod>\n";
    echo '    <changefreq>' . $xmlEscape((string) $entry['changefreq']) . "</changefreq>\n";
    echo '    <priority>' . $xmlEscape((string) $entry['priority']) . "</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
