<?php
declare(strict_types=1);

require __DIR__ . '/../api.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$items = [
    ['comment' => 'Komentar abu Anak Krakatau', 'commentUrl' => 'https://example.com/comments/1', 'username' => 'warga', 'sentiment' => 'negative', 'token' => 'private', 'email' => 'private@example.com'],
    ['text' => 'Informasi kondisi', 'url' => 'javascript:alert(1)', 'author' => ['name' => 'Pembaca']],
    ['text' => 'Komentar berikutnya', 'url' => 'https://user:password@example.com/comment'],
    ['text' => ['invalid'], 'author' => 'unexpected'],
    ['other' => 'no comment'],
    null,
];
$posts = datasetPosts($items);
check(count($posts) === 3, 'Skip invalid and empty comments.');
check($posts[0]['sourceUrl'] === 'https://example.com/comments/1', 'Preserve valid source links.');
check($posts[0]['sentiment'] === 'Negatif', 'Normalize supplied sentiment.');
check($posts[1]['sourceUrl'] === '', 'Reject non-HTTP links.');
check($posts[2]['sourceUrl'] === '', 'Reject credentials in source links.');
check($posts[1]['author'] === 'Pembaca', 'Read nested author names.');
check(!isset($posts[0]['token']) && !isset($posts[0]['email']), 'Return only dashboard fields.');
check(count(datasetPosts(array_fill(0, 12, ['text' => 'Komentar']))) === 8, 'Limit each dataset response to eight records.');
echo "Dataset normalization, source URL validation and record limit checks passed.\n";
