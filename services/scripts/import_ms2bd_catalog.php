<?php

declare(strict_types=1);

use App\Models\Categorie;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Variation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sourceBase = 'https://www.ms2bd.com';
$testMode = in_array('--test', $argv, true);
$wipeMode = in_array('--wipe', $argv, true);
$limitArg = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limitArg = (int) substr($arg, 8);
    }

    if (str_starts_with($arg, '--source=')) {
        $sourceBase = rtrim((string) substr($arg, 9), '/');
    }
}

$limit = $testMode ? 1 : ($limitArg && $limitArg > 0 ? $limitArg : PHP_INT_MAX);

function info(string $message): void
{
    echo '[INFO] ' . $message . PHP_EOL;
}

function warn(string $message): void
{
    echo '[WARN] ' . $message . PHP_EOL;
}

function normalizeText(string $html): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim((string) $text);
}

function firstMatch(string $pattern, string $subject): ?string
{
    if (!preg_match($pattern, $subject, $m)) {
        return null;
    }

    return isset($m[1]) ? trim((string) $m[1]) : null;
}

function absoluteUrl(string $base, string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function downloadToUploads(string $url, string $subdir): ?string
{
    if ($url === '') {
        return null;
    }

    try {
        $response = Http::withoutVerifying()->timeout(30)->get($url);
    } catch (Throwable $e) {
        warn('Download failed: ' . $url . ' | ' . $e->getMessage());

        return null;
    }

    if (!$response->ok()) {
        warn('Download non-200: ' . $url . ' | HTTP ' . $response->status());

        return null;
    }

    $pathPart = parse_url($url, PHP_URL_PATH) ?: '';
    $ext = pathinfo((string) $pathPart, PATHINFO_EXTENSION);
    if ($ext === '') {
        $ext = 'jpg';
    }

    $filename = strtoupper(Str::ulid()->toBase32()) . '.' . strtolower($ext);
    $relative = trim($subdir, '/') . '/' . $filename;
    $full = public_path('uploads/' . $relative);

    $dir = dirname($full);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($full, $response->body());

    return $relative;
}

function upsertGeneralSetting(string $name, mixed $value): void
{
    $now = now();

    DB::table('settings')->updateOrInsert(
        [
            'group' => 'general',
            'name' => $name,
        ],
        [
            'locked' => 0,
            'payload' => json_encode($value, JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
            'created_at' => $now,
        ]
    );
}

function parseHtml(string $html): DOMXPath
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function importProductPage(string $sourceBase, string $productUrl, Product $product): array
{
    $absUrl = absoluteUrl($sourceBase, $productUrl);
    info('Importing product details: ' . $absUrl);

    $html = Http::withoutVerifying()->timeout(30)->get($absUrl)->body();

    $inputLabel = firstMatch('/<label[^>]*class="label-title"[^>]*>(.*?)<\/label>/is', $html);
    if (!empty($inputLabel)) {
        $product->input = normalizeText($inputLabel);
    }

    preg_match_all(
        '/<button[^>]*class="[^"]*variation_list[^"]*"[^>]*id="([^"]+)"[^>]*data-price="([0-9.]+)"[^>]*>(.*?)<\/button>/is',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    Variation::query()->where('product_id', $product->id)->delete();
    DB::table('product_packages')->where('product_id', $product->id)->delete();

    $imported = 0;

    foreach ($matches as $row) {
        $block = (string) ($row[3] ?? '');
        $titleRaw = firstMatch('/<span[^>]*class="[^"]*font-primary[^"]*"[^>]*>(.*?)<\/span>/is', $block);
        $title = normalizeText($titleRaw ?? '');
        $price = (float) ($row[2] ?? 0);
        $isStockout = stripos((string) $row[0], 'stockout') !== false;

        if ($title === '' || $price <= 0) {
            continue;
        }

        Variation::query()->create([
            'sync_uid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'title' => $title,
            'price' => $price,
            'gift_coins' => 0,
            'stock' => $isStockout ? 0 : 9999,
            'automatic' => 0,
            'provider' => null,
            'provider_product_id' => (string) ($row[1] ?? null),
        ]);

        DB::table('product_packages')->insert([
            'sync_uid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'external_id' => (string) ($row[1] ?? ''),
            'name' => $title,
            'price' => $price,
            'is_active' => $isStockout ? 0 : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imported++;
    }

    return ['variations' => $imported];
}

info('Fetching source homepage...');
$homeHtml = Http::withoutVerifying()->timeout(30)->get($sourceBase . '/')->body();
$xpath = parseHtml($homeHtml);

if ($wipeMode) {
    DB::table('product_packages')->delete();
    DB::table('variations')->delete();
    DB::table('products')->delete();
    DB::table('categories')->delete();
    DB::table('sliders')->delete();
    info('Existing catalog wiped.');
}

$noticeText = firstMatch('/<div[^>]*class="notice-text[^\"]*"[^>]*>(.*?)<\/div>/is', $homeHtml);
if ($noticeText !== null && $noticeText !== '') {
    upsertGeneralSetting('enable_notice', true);
    upsertGeneralSetting('notice_title', 'Notice');
    upsertGeneralSetting('notice_content', normalizeText($noticeText));
    info('Notice imported.');
}

$sliderNodes = $xpath->query('//section[contains(@class,"carousel")]//li[contains(@class,"carousel__slide")]');
if ($sliderNodes !== false && $sliderNodes->length > 0) {
    Slider::query()->delete();

    $order = 1;
    foreach ($sliderNodes as $slide) {
        $imgNode = $xpath->query('.//img', $slide)?->item(0);
        $linkNode = $xpath->query('.//a', $slide)?->item(0);

        $imgSrc = $imgNode?->attributes?->getNamedItem('src')?->nodeValue ?? '';
        $url = $linkNode?->attributes?->getNamedItem('href')?->nodeValue ?? null;

        $uploaded = downloadToUploads(absoluteUrl($sourceBase, $imgSrc), 'slider');

        if ($uploaded === null) {
            continue;
        }

        Slider::query()->create([
            'url' => $url,
            'image_url' => $uploaded,
            'order_column' => $order,
            'status' => 1,
        ]);

        $order++;
    }

    info('Slider imported.');
}

$sections = $xpath->query('//section[@id="topup"]');
$totalProducts = 0;
$totalVariations = 0;
$seen = [];

if ($sections !== false) {
    foreach ($sections as $section) {
        if ($totalProducts >= $limit) {
            break;
        }

        $categoryTitleNode = $xpath->query('.//h3', $section)?->item(0);
        $categoryTitle = normalizeText($categoryTitleNode?->textContent ?? 'Popular Topup');
        if ($categoryTitle === '') {
            $categoryTitle = 'Popular Topup';
        }

        $category = Categorie::query()->firstOrCreate(
            ['title' => $categoryTitle],
            [
                'slot' => (string) (Categorie::query()->count() + 1),
                'status' => '1',
            ]
        );

        $links = $xpath->query('.//a[contains(@href,"/topup/")]', $section);
        if ($links === false) {
            continue;
        }

        foreach ($links as $a) {
            if ($totalProducts >= $limit) {
                break;
            }

            $href = trim((string) ($a->attributes?->getNamedItem('href')?->nodeValue ?? ''));
            if ($href === '') {
                continue;
            }

            $slug = basename(parse_url($href, PHP_URL_PATH) ?: '');
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;

            $titleNode = $xpath->query('.//h1', $a)?->item(0);
            $title = normalizeText($titleNode?->textContent ?? $slug);
            $title = $title !== '' ? $title : ucfirst(str_replace('-', ' ', $slug));

            $imgNode = $xpath->query('.//img', $a)?->item(0);
            $imgSrc = trim((string) ($imgNode?->attributes?->getNamedItem('src')?->nodeValue ?? ''));
            $uploadedImage = downloadToUploads(absoluteUrl($sourceBase, $imgSrc), 'products');

            $product = Product::query()->firstOrNew(['slug' => $slug]);
            $product->title = $title;
            $product->slug = $slug;
            $product->categorie_id = $category->id;
            $product->content = $title;
            $product->type = 'IDCODE';
            $product->percentage = 0;
            $product->uid_checker = 0;
            $product->has_tutorial = 0;
            $product->slot = (int) Product::query()->where('categorie_id', $category->id)->max('slot') + 1;
            $product->input = $product->input ?: 'Player ID';
            $product->dynamic_fields = [];
            $product->status = 1;
            $product->image = $uploadedImage ?: ($product->image ?: '');
            $product->source = 'local';
            $product->website_name = 'MS2BD';
            $product->source_site_url = $sourceBase;
            $product->sync_status = 'synced';
            $product->synced_at = now();
            $product->save();

            $stats = importProductPage($sourceBase, $href, $product);

            $totalProducts++;
            $totalVariations += (int) ($stats['variations'] ?? 0);

            info('Imported product #' . $totalProducts . ': ' . $title . ' | variations=' . ($stats['variations'] ?? 0));
        }
    }
}

$homeTitleNode = $xpath->query('//title')?->item(0);
if ($homeTitleNode) {
    $homeTitle = normalizeText((string) $homeTitleNode->textContent);
    if ($homeTitle !== '') {
        upsertGeneralSetting('site_name', 'MS2 BD');
        upsertGeneralSetting('home_title', $homeTitle);
    }
}

$facebook = firstMatch('/href="(https?:\/\/[^\"]*facebook[^\"]*)"/i', $homeHtml);
$telegram = firstMatch('/href="(https?:\/\/t\.me\/[^\"]+)"/i', $homeHtml);
$youtube = firstMatch('/href="(https?:\/\/[^\"]*youtu[^\"]*)"/i', $homeHtml);
$email = firstMatch('/href="mailto:([^\"]+)"/i', $homeHtml);

if ($facebook) {
    upsertGeneralSetting('facebook_link', $facebook);
}
if ($telegram) {
    upsertGeneralSetting('telegram_link', $telegram);
    upsertGeneralSetting('messenger_link', $telegram);
    upsertGeneralSetting('whatsapp_number', $telegram);
}
if ($youtube) {
    upsertGeneralSetting('youtube_link', $youtube);
}
if ($email) {
    upsertGeneralSetting('email_address', $email);
}

info('Import finished. source=' . $sourceBase . ', products=' . $totalProducts . ', variations=' . $totalVariations . ', testMode=' . ($testMode ? 'yes' : 'no') . ', wipeMode=' . ($wipeMode ? 'yes' : 'no'));
