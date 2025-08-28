<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use KnowledgeBase\Kb;

echo Kb::getArticleCount() . PHP_EOL;

foreach (Kb::getArticles() as $article) {
    echo $article->ID . ' ' . $article->post_title . PHP_EOL;
}