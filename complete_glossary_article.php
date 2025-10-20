<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use KnowledgeBase\Kb;

/* @var KnowledgeBase\Article $article */
foreach (Kb::getArticles() as $article) {
    if (!$article->isGlossaryEntry()) {
        continue;
    }
    $headlines = $article->getH2List();
    if (count($headlines) === 5) {
        echo 'Article link: ' . $article->permalink() . PHP_EOL;
        foreach ($headlines as $headline) {
            echo ' - ' . $headline . PHP_EOL;
        }
    }
}