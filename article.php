<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use PHPHtmlParser\Dom;

$long = false;
$id = 0;

for ($i = 1; $i < $_SERVER['argc']; $i++) {
    if (substr($_SERVER['argv'][$i], 0, 2) === '--') {
        if ($_SERVER['argv'][$i] === '--verbose') {
            $long = true;
            continue;
        }
        echo 'Invalid argument ' . $_SERVER['argv'][$i] . PHP_EOL;
        exit(1);
    }
    $id = (int)$_SERVER['argv'][$i];
    if ($id === 0) {
        echo 'Invalid article/post id provided' . PHP_EOL;
        exit(1);
    }
}
if ($id === 0) {
    echo 'No article/post id provided' . PHP_EOL;
    exit(1);
}
$articles = Kb::getArticles([$id]);
if (empty($articles)) {
    echo "Article/post with ID $id not found\n";
    exit(1);
}
$article = null;
foreach ($articles as $article) {
    break;
}
if ($long) {
    print_r($article);
    exit(0);
}
$headlines = '';
// parse the HTML content using php-html-parser
$dom = new Dom;
$dom->loadStr($article->post_content);
foreach ($dom->find('h2') as $h2) {
    $headlines .= PHP_EOL . "\t\t" . $h2->innerHTML;
}

$category = implode(' > ', array_map(fn($c) => $c->name, array_shift($article->categories)));
$tags = implode(', ', array_map(fn($t) => $t->name, $article->tags));

echo "ID: {$article->ID}
Post date: {$article->post_date}
Modified: {$article->post_modified}
Title: {$article->post_title}
Headlines: {$headlines}
Category: {$category}
Keywords: {$tags}
";
