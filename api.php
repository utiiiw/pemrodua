<?php
declare(strict_types=1);

function textField(array $item, array $keys, string $fallback = ''): string
{
    foreach ($keys as $key) {
        if (isset($item[$key]) && is_string($item[$key]) && trim($item[$key]) !== '') {
            return trim($item[$key]);
        }
    }
    return $fallback;
}

function datasetPosts(array $items): array
{
    $posts = [];
    foreach (array_slice($items, 0, 8) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $text = textField($item, ['text', 'comment', 'commentText', 'caption']);
        if ($text === '') {
            continue;
        }
        $url = textField($item, ['commentUrl', 'sourceUrl', 'url', 'postUrl']);
        $parts = parse_url($url);
        $safeUrl = filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            && !isset($parts['user']) && !isset($parts['pass']);
        $author = is_array($item['author'] ?? null) ? $item['author'] : [];
        $labels = ['positive' => 'Positif', 'positif' => 'Positif', 'neutral' => 'Netral', 'netral' => 'Netral', 'negative' => 'Negatif', 'negatif' => 'Negatif'];
        $sentiment = strtolower(textField($item, ['sentiment']));
        $posts[] = [
            'id' => 'apify-' . count($posts),
            'author' => textField($item, ['authorName', 'username', 'ownerUsername', 'channelName', 'channelUsername'], textField($author, ['name', 'username'], 'Pengguna media sosial')),
            'platform' => textField($item, ['platform'], ($item['type'] ?? '') === 'video' && in_array(strtolower($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com', 'youtu.be'], true) ? 'YouTube' : 'Media sosial'),
            'date' => textField($item, ['date', 'createdAt', 'timestamp'], 'Tanggal tidak tersedia'),
            'text' => $text,
            'location' => textField($item, ['location']),
            'category' => textField($item, ['category'], 'Opini masyarakat'),
            'sentiment' => $labels[$sentiment] ?? '',
            'sourceUrl' => $safeUrl ? $url : '',
            'title' => textField($item, ['title']),
            'recordType' => ($item['type'] ?? '') === 'video' ? 'Unggahan video' : 'Komentar',
        ];
    }
    return $posts;
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    exit;
}

if (PHP_SAPI === 'cli') {
    return;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    jsonResponse(['error' => 'Metode tidak didukung.'], 405);
}

$env = is_file(__DIR__ . '/.env') ? @parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW) : [];
if ($env === false) {
    jsonResponse(['error' => 'Konfigurasi server tidak dapat dibaca.'], 503);
}
$token = trim((string) (getenv('APIFY_TOKEN') ?: ($env['APIFY_TOKEN'] ?? '')));
$datasetId = trim((string) (getenv('APIFY_DATASET_ID') ?: ($env['APIFY_DATASET_ID'] ?? '')));
if ($token === '' || $datasetId === '') {
    jsonResponse(['error' => 'Dataset Apify belum dikonfigurasi.'], 503);
}
if (!preg_match('/^[A-Za-z0-9_~.-]{1,100}$/', $datasetId) || preg_match('/[\r\n]/', $token)) {
    jsonResponse(['error' => 'Konfigurasi Apify tidak valid.'], 503);
}
if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    jsonResponse(['error' => 'Koneksi Apify belum tersedia pada server ini.'], 503);
}

$options = [
    'http' => [
        'method' => 'GET',
        'header' => ['Authorization: Bearer ' . $token, 'Accept: application/json', 'Connection: close'],
        'timeout' => 15,
        'follow_location' => 0,
        'ignore_errors' => true,
    ],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
];
$ca = ini_get('openssl.cafile') ?: ini_get('curl.cainfo');
if ($ca && is_file($ca)) {
    $options['ssl']['cafile'] = $ca;
}
$url = 'https://api.apify.com/v2/datasets/' . rawurlencode($datasetId) . '/items?format=json&clean=true&limit=8';
$stream = @fopen($url, 'rb', false, stream_context_create($options));
if ($stream === false) {
    jsonResponse(['error' => 'Koneksi ke Apify gagal. Silakan coba lagi.'], 502);
}
$body = @stream_get_contents($stream, 2 * 1024 * 1024 + 1);
$metadata = stream_get_meta_data($stream);
fclose($stream);
if ($body === false || $metadata['timed_out'] || strlen($body) > 2 * 1024 * 1024) {
    jsonResponse(['error' => 'Respons Apify terlalu besar atau belum selesai diterima.'], 502);
}
$status = 0;
foreach ($metadata['wrapper_data'] ?? [] as $header) {
    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) {
        $status = (int) $match[1];
    }
}
if (in_array($status, [401, 403], true)) {
    jsonResponse(['error' => 'Akses dataset Apify ditolak. Periksa kredensial di server.'], 502);
}
if ($status < 200 || $status >= 300) {
    jsonResponse(['error' => 'Dataset Apify belum dapat diambil.'], 502);
}
try {
    $items = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    jsonResponse(['error' => 'Respons Apify tidak dapat dibaca.'], 502);
}
if (!is_array($items) || !array_is_list($items)) {
    jsonResponse(['error' => 'Format dataset Apify tidak didukung.'], 502);
}
jsonResponse(['posts' => datasetPosts($items)]);
