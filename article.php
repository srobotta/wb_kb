<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use KnowledgeBase\Kb;

$long = false;
$id = 0;

for ($i = 1; $i < $_SERVER['argc']; $i++) {
    if (substr($_SERVER['argv'][$i], 0, 2) === '--') {
        if ($_SERVER['argv'][$i] === '--dump') {
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
/** @var $article \KnowledgeBase\Article */
$article = null;
foreach ($articles as $article) {
    break;
}
if ($long) {
    print_r($article);
    exit(0);
}

//$headlines = PHP_EOL . "\t" . implode(PHP_EOL . "\t", $article->getH2List());
$headlines = PHP_EOL . implode(PHP_EOL, array_map(
    fn($headline) => str_repeat('  ', $headline['level']) . $headline['label'],
    $article->getAllHeadlines()
));
$tags = implode(', ', $article->getTagsList());

echo "ID: {$article->ID}
Post date: {$article->post_date}
Modified: {$article->post_modified}
Title: {$article->post_title}
Headlines: {$headlines}
Category: {$article->getCategoryStr()}
Keywords: {$tags}
";

// Check for headlines with empty content following.
$emptyHeadlines = $article->getEmptyHeadlines();
if (!empty($emptyHeadlines)) {
    echo "\nThese headlines have no content:\n  " . implode("\n  ", $emptyHeadlines) . "\n";
}

// When a glossary entry, check that the expected headlines are in the correct order.
if ($article->isGlossarEntry()) {
    $glossaryEntryHeadlines = [
        'Beschreibung/Definition',
        'Empfehlungen',
        'Vertiefung zum Thema',
        'Verwandte Themen',
        'Literatur',
    ];
    if (empty($article->getH2List())) {
        echo "\nNo headlines found to check with expected structure.\n";
    }
    else if (!$article->checkHeadlineSequence($glossaryEntryHeadlines)) {
        echo "\nGlossary article does contain different headlines or in a different order:\n";
        echo implode(', ', array_intersect($glossaryEntryHeadlines, $article->getH2List())) . PHP_EOL;
    }
}

// Check, whether the article has a cc license.
$ccLicense = $article->getCcLicense();
if (!empty($ccLicense)) {
    echo "License: {$ccLicense[0]}";
    if (count($ccLicense) > 1) {
        echo '  ' . $ccLicense[1];
    }
    echo PHP_EOL;
}