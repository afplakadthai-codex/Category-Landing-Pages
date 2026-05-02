<?php

declare(strict_types=1);

$categories = [
    'halfmoon' => [
        'label' => 'Halfmoon Betta Fish',
        'keyword' => 'Halfmoon Betta Fish for Sale',
        'terms' => ['Halfmoon', 'Half Moon', 'HM'],
        'wild_terms' => [],
        'intro' => 'Explore premium halfmoon bettas with wide caudal fin, strong finnage, and collector quality.',
    ],
    'koi' => [
        'label' => 'Koi Betta Fish',
        'keyword' => 'Koi Betta Fish for Sale',
        'terms' => ['Koi', 'Galaxy Koi', 'Nemo Koi'],
        'wild_terms' => [],
        'intro' => 'Explore colorful koi bettas with marble patterns and vivid contrast.',
    ],
    'giant' => [
        'label' => 'Giant Betta Fish',
        'keyword' => 'Giant Betta Fish for Sale',
        'terms' => ['Giant', 'Giant Betta'],
        'wild_terms' => [],
        'intro' => 'Explore larger bettas selected for size, strength, and presence.',
    ],
    'plakat' => [
        'label' => 'Plakat Betta Fish',
        'keyword' => 'Plakat Betta Fish for Sale',
        'terms' => ['Plakat', 'HMPK', 'PK'],
        'wild_terms' => [],
        'intro' => 'Explore short-fin plakat bettas known for active form, strength, and clean body shape.',
    ],
    'fancy' => [
        'label' => 'Fancy Betta Fish',
        'keyword' => 'Fancy Betta Fish for Sale',
        'terms' => ['Fancy', 'Multicolor', 'Marble'],
        'wild_terms' => [],
        'intro' => 'Explore fancy bettas with unique colors, patterns, and collector appeal.',
    ],
    'crowntail' => [
        'label' => 'Crowntail Betta Fish',
        'keyword' => 'Crowntail Betta Fish for Sale',
        'terms' => ['Crowntail', 'Crown Tail', 'CT'],
        'wild_terms' => [],
        'intro' => 'Explore crowntail bettas with dramatic ray extensions and striking fin structure.',
    ],
    'wild-type' => [
        'label' => 'Wild Type Betta Fish',
        'keyword' => 'Wild Type Betta Fish for Sale',
        'terms' => ['Wild', 'Betta imbellis', 'Betta smaragdina', 'Betta mahachaiensis'],
        'wild_terms' => ['Wild', 'Betta imbellis', 'Betta smaragdina', 'Betta mahachaiensis'],
        'intro' => 'Explore wild type bettas and natural species for collectors and breeders.',
    ],
    'female-betta' => [
        'label' => 'Female Betta Fish',
        'keyword' => 'Female Betta Fish for Sale',
        'terms' => ['Female'],
        'wild_terms' => [],
        'intro' => 'Explore female bettas selected for color, body form, breeding potential, and collection.',
    ],
];

$currentSlug = isset($_GET['strain']) ? strtolower(trim((string) $_GET['strain'])) : 'halfmoon';
$baseUrl = defined('APP_URL') ? rtrim((string) APP_URL, '/') : 'https://www.bettavaro.com';
$metaRobots = 'index,follow,max-image-preview:large';
$ogImage = 'https://www.bettavaro.com/assets/img/og-listings.jpg';

if (!isset($categories[$currentSlug])) {
    http_response_code(404);
    $pageTitle = 'Betta Fish Categories | Bettavaro';
    $metaDescription = 'Browse supported betta fish categories on Bettavaro including Halfmoon, Koi, Giant, Plakat, Fancy, Crowntail, Wild Type, and Female Betta Fish.';
    $canonicalUrl = $baseUrl . '/betta-fish.php';
    $categoryJsonLd = '';
    include __DIR__ . '/includes/head.php';
    include __DIR__ . '/includes/menu.php';
    ?>
    <main class="category-landing" style="max-width:1100px;margin:0 auto;padding:24px 16px 48px;">
        <h1 style="margin-bottom:10px;">Category Not Found</h1>
        <p style="margin:0 0 16px;">The requested betta category is unavailable. Please choose one of the supported categories below.</p>
        <ul>
            <?php foreach ($categories as $slug => $cfg): ?>
                <li><a href="<?= htmlspecialchars('/betta-fish.php?strain=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
        </ul>
    </main>
    <?php
    exit;
}

$currentCategory = $categories[$currentSlug];
$keyword = $currentCategory['keyword'];
$label = $currentCategory['label'];
$canonicalUrl = $baseUrl . '/betta-fish.php?strain=' . rawurlencode($currentSlug);
$pageTitle = $keyword . ' | Premium Thai Betta Marketplace | Bettavaro';
$metaDescription = 'Explore ' . $keyword . ' on Bettavaro. Discover premium Thai betta fish from trusted breeders with photos, details, price, and availability for collectors worldwide.';

$faqItems = [
    [
        'q' => 'What is a ' . $label . '?',
        'a' => $label . ' refers to betta fish known for the traits associated with this strain category, including distinct finnage, body form, and pattern characteristics valued by hobbyists.',
    ],
    [
        'q' => 'Are these betta fish suitable for collectors?',
        'a' => 'Many listings are selected with collectors in mind. Review each listing’s photos, strain notes, and condition details to determine if it matches your goals.',
    ],
    [
        'q' => 'How do I choose a premium betta fish?',
        'a' => 'Compare body shape, finnage, color quality, activity level, and listing transparency. Check images, pricing, and breeder details before deciding.',
    ],
    [
        'q' => 'How can I buy a betta fish on Bettavaro?',
        'a' => 'Open a listing to review details, availability status, and seller information, then follow the marketplace purchase flow provided on the listing page.',
    ],
];

$pdo = null;
$dbBootstrapFiles = [
    __DIR__ . '/config/db.php',
    __DIR__ . '/includes/db.php',
    __DIR__ . '/db.php',
];

foreach ($dbBootstrapFiles as $dbFile) {
    if (is_file($dbFile)) {
        include_once $dbFile;
    }
}

foreach (['pdo', 'PDO', 'db', 'conn'] as $globalKey) {
    if (isset($GLOBALS[$globalKey]) && $GLOBALS[$globalKey] instanceof PDO) {
        $pdo = $GLOBALS[$globalKey];
        break;
    }
}

$results = [];
$dbError = null;

if ($pdo instanceof PDO) {
    try {
        $tableExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name");
        $columnExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name");

        $hasListingsTable = false;
        $tableExistsStmt->execute([':table_name' => 'listings']);
        $hasListingsTable = ((int) $tableExistsStmt->fetchColumn()) > 0;

        if ($hasListingsTable) {
            $candidateColumns = [
                'id', 'title', 'slug', 'species', 'strain', 'color', 'grade', 'price', 'currency',
                'cover_image', 'status', 'sale_status', 'short_description', 'description', 'created_at',
                'sex', 'gender',
            ];
            $existingColumns = [];
            foreach ($candidateColumns as $columnName) {
                $columnExistsStmt->execute([
                    ':table_name' => 'listings',
                    ':column_name' => $columnName,
                ]);
                if ((int) $columnExistsStmt->fetchColumn() > 0) {
                    $existingColumns[$columnName] = true;
                }
            }

            $hasRankingTable = false;
            $tableExistsStmt->execute([':table_name' => 'listing_ranking_scores']);
            $hasRankingTable = ((int) $tableExistsStmt->fetchColumn()) > 0;

            $hasRankingColumns = false;
            if ($hasRankingTable) {
                $columnExistsStmt->execute([':table_name' => 'listing_ranking_scores', ':column_name' => 'listing_id']);
                $hasListingId = ((int) $columnExistsStmt->fetchColumn()) > 0;
                $columnExistsStmt->execute([':table_name' => 'listing_ranking_scores', ':column_name' => 'final_score']);
                $hasFinalScore = ((int) $columnExistsStmt->fetchColumn()) > 0;
                $hasRankingColumns = $hasListingId && $hasFinalScore;
            }

            $selectColumns = ['l.id'];
            $fieldMap = ['title', 'slug', 'species', 'strain', 'color', 'grade', 'price', 'currency', 'cover_image', 'status', 'sale_status', 'short_description', 'description', 'created_at'];
            foreach ($fieldMap as $field) {
                if (isset($existingColumns[$field])) {
                    $selectColumns[] = 'l.' . $field;
                }
            }

            $sql = 'SELECT ' . implode(', ', $selectColumns) . ' FROM listings l ';
            if ($hasRankingColumns) {
                $sql .= 'LEFT JOIN listing_ranking_scores r ON r.listing_id = l.id ';
            }

            $where = ['1=1'];
            $params = [];

            if (isset($existingColumns['status'])) {
                $where[] = 'l.status IN (\'active\',\'available\',\'published\')';
            }
            if (isset($existingColumns['sale_status'])) {
                $where[] = 'l.sale_status IN (\'available\',\'reserved\',\'sold\')';
            }

            $orMatch = [];
            $terms = $currentCategory['terms'];

            if ($currentSlug === 'female-betta') {
                if (isset($existingColumns['sex'])) {
                    $orMatch[] = 'LOWER(l.sex) LIKE :female_sex';
                    $params[':female_sex'] = '%female%';
                }
                if (isset($existingColumns['gender'])) {
                    $orMatch[] = 'LOWER(l.gender) LIKE :female_gender';
                    $params[':female_gender'] = '%female%';
                }
                if (isset($existingColumns['strain'])) {
                    $orMatch[] = 'LOWER(l.strain) LIKE :female_strain';
                    $params[':female_strain'] = '%female%';
                }
                if (isset($existingColumns['title'])) {
                    $orMatch[] = 'LOWER(l.title) LIKE :female_title';
                    $params[':female_title'] = '%female%';
                }
            } else {
                if (isset($existingColumns['strain'])) {
                    foreach ($terms as $idx => $term) {
                        $p = ':strain_term_' . $idx;
                        $orMatch[] = 'LOWER(l.strain) LIKE ' . $p;
                        $params[$p] = '%' . strtolower($term) . '%';
                    }
                }

                if ($currentSlug === 'wild-type' && isset($existingColumns['species'])) {
                    foreach ($currentCategory['wild_terms'] as $idx => $term) {
                        $p = ':species_term_' . $idx;
                        $orMatch[] = 'LOWER(l.species) LIKE ' . $p;
                        $params[$p] = '%' . strtolower($term) . '%';
                    }
                }

                if (isset($existingColumns['title'])) {
                    foreach ($terms as $idx => $term) {
                        $p = ':title_term_' . $idx;
                        $orMatch[] = 'LOWER(l.title) LIKE ' . $p;
                        $params[$p] = '%' . strtolower($term) . '%';
                    }
                }
            }

            if (!empty($orMatch)) {
                $where[] = '(' . implode(' OR ', $orMatch) . ')';
            }

            $sql .= ' WHERE ' . implode(' AND ', $where);
            if ($hasRankingColumns) {
                $sql .= ' ORDER BY COALESCE(r.final_score, 0) DESC, l.created_at DESC';
            } else {
                $sql .= ' ORDER BY l.created_at DESC';
            }
            $sql .= ' LIMIT 24';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
        $results = [];
    }
}

$itemListElements = [];
foreach ($results as $index => $item) {
    $listingUrl = $baseUrl . '/listing.php?id=' . rawurlencode((string) ($item['id'] ?? ''));
    if (!empty($item['slug'])) {
        $listingUrl = $baseUrl . '/listing.php?slug=' . rawurlencode((string) $item['slug']);
    }
    $itemListElements[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $listingUrl,
        'name' => (string) ($item['title'] ?? ($label . ' Listing ' . ($index + 1))),
    ];
}

$schemaGraph = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $keyword,
        'url' => $canonicalUrl,
        'description' => $metaDescription,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Bettavaro',
            'url' => $baseUrl,
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Betta Fish for Sale', 'item' => $baseUrl . '/listings.php'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $label, 'item' => $canonicalUrl],
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static function (array $faq): array {
            return [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ];
        }, $faqItems),
    ],
];

if (!empty($itemListElements)) {
    $schemaGraph[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => $itemListElements,
    ];
}

$categoryJsonLd = json_encode($schemaGraph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/menu.php';
?>
<?php if (!empty($categoryJsonLd)): ?>
<script type="application/ld+json">
<?= $categoryJsonLd . "\n" ?>
</script>
<?php endif; ?>
<main class="category-landing" style="max-width:1180px;margin:0 auto;padding:20px 16px 48px;">
    <header style="margin-bottom:18px;">
        <h1 style="margin:0 0 8px;"><?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?></h1>
        <p style="margin:0;color:#444;"><?= htmlspecialchars($currentCategory['intro'], ENT_QUOTES, 'UTF-8') ?></p>
    </header>

    <section style="background:#f8f9fb;border:1px solid #e6e8ef;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
        <h2 style="margin:0 0 10px;font-size:1.15rem;">Why buy <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> on Bettavaro?</h2>
        <ul style="margin:0;padding-left:18px;line-height:1.6;">
            <li>Premium Thai betta marketplace</li>
            <li>Listings from trusted breeders</li>
            <li>Photos, price, details, and availability in one place</li>
        </ul>
    </section>

    <section aria-label="Listings">
        <h2 style="margin:0 0 12px;font-size:1.2rem;">Available Listings</h2>
        <?php if (empty($results)): ?>
            <p style="margin:0 0 14px;color:#555;">No listings are currently available in this category. Please check back soon.</p>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
                <?php foreach ($results as $row): ?>
                    <?php
                    $title = (string) ($row['title'] ?? 'Betta Fish Listing');
                    $image = !empty($row['cover_image']) ? (string) $row['cover_image'] : '';
                    $currency = !empty($row['currency']) ? (string) $row['currency'] : 'USD';
                    $price = isset($row['price']) && $row['price'] !== '' ? (string) $row['price'] : '—';
                    $statusParts = [];
                    if (!empty($row['status'])) {
                        $statusParts[] = (string) $row['status'];
                    }
                    if (!empty($row['sale_status'])) {
                        $statusParts[] = (string) $row['sale_status'];
                    }
                    $listingHref = '/listing.php?id=' . rawurlencode((string) ($row['id'] ?? ''));
                    if (!empty($row['slug'])) {
                        $listingHref = '/listing.php?slug=' . rawurlencode((string) $row['slug']);
                    }
                    ?>
                    <article style="border:1px solid #e5e7ee;border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column;">
                        <a href="<?= htmlspecialchars($listingHref, ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;color:inherit;display:block;">
                            <div style="background:#f2f4f8;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                <?php if ($image !== ''): ?>
                                    <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                                <?php else: ?>
                                    <span style="color:#777;font-size:0.9rem;">No image</span>
                                <?php endif; ?>
                            </div>
                            <div style="padding:12px;">
                                <h3 style="margin:0 0 8px;font-size:1rem;line-height:1.35;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                                <p style="margin:0 0 8px;font-weight:600;"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?></p>
                                <p style="margin:0 0 8px;color:#555;font-size:0.9rem;line-height:1.5;">
                                    <?php
                                    $traits = [];
                                    if (!empty($row['strain'])) {
                                        $traits[] = 'Strain: ' . (string) $row['strain'];
                                    }
                                    if (!empty($row['species'])) {
                                        $traits[] = 'Species: ' . (string) $row['species'];
                                    }
                                    if (!empty($row['color'])) {
                                        $traits[] = 'Color: ' . (string) $row['color'];
                                    }
                                    echo htmlspecialchars(!empty($traits) ? implode(' • ', $traits) : 'View listing for more details.', ENT_QUOTES, 'UTF-8');
                                    ?>
                                </p>
                                <?php if (!empty($statusParts)): ?>
                                    <span style="display:inline-block;background:#eef3ff;color:#2f4f93;border-radius:999px;padding:4px 10px;font-size:0.78rem;text-transform:capitalize;">
                                        <?= htmlspecialchars(implode(' / ', $statusParts), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section style="margin-top:24px;">
        <h2 style="margin:0 0 12px;font-size:1.2rem;">Frequently Asked Questions</h2>
        <?php foreach ($faqItems as $faq): ?>
            <article style="margin-bottom:10px;padding:10px 12px;border:1px solid #e8e8e8;border-radius:8px;">
                <h3 style="margin:0 0 6px;font-size:1rem;"><?= htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p style="margin:0;color:#444;line-height:1.55;"><?= htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>
