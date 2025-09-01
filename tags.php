<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use KnowledgeBase\Kb;

$list = false;
$articleId = 0;
$selectedTag = '';

for ($i = 1; $i < $_SERVER['argc']; $i++) {
    if (substr($_SERVER['argv'][$i], 0, 2) === '--') {
        if ($_SERVER['argv'][$i] === '--list') {
            $list = true;
            continue;
        }
        if (strpos($_SERVER['argv'][$i], '=') !== false) {
            [$arg, $val] = explode('=', $_SERVER['argv'][$i]);
            if ($arg === '--article') {
                $articleId = (int)$val;
                if ($val === 0) {
                    echo 'Invalid article id' . PHP_EOL;
                    exit(1);
                }
                continue;
            }
            if ($arg === '--tag') {
                $selectedTag = $val;
                continue;
            }
            echo 'Invalid argument ' . $arg . PHP_EOL;
            exit(1);
        }
        echo 'Invalid argument ' . $_SERVER['argv'][$i] . PHP_EOL;
        exit(1);
    }
}

if ($articleId > 0 && $selectedTag !== '') {
    echo 'Either --article or --tag can be set, not both' . PHP_EOL;
    exit(1);
}

// There should be a direct way to fetch the tags from the db.
// Meanwhile I am using the way via the post items, runtime is not
// an issue because this is rather used internally and not on a website.
$tags = [];
foreach (($articleId > 0 ? Kb::getArticles([$articleId]) : Kb::getArticles()) as $article) {
    foreach ($article->getTagsList() as $tag) {
        if (!array_key_exists($tag, $tags)) {
            $tags[$tag] = [];
        }
        $tags[$tag][$article->ID] = $article->post_title;
    }
}
if ($selectedTag !== '') {
    $token = strtolower($selectedTag);
    $tags = array_filter(
        $tags,
        function($key) use ($token) {
            return strtolower($key) === $token;
        },
        ARRAY_FILTER_USE_KEY
    );
    if (empty($tags)) {
        echo 'Tag ' . $selectedTag . ' does not exist or is not attached to any articles' . PHP_EOL;
        exit(0);
    }
}
uasort($tags, function ($a, $b) {
    $a = count($a);
    $b = count($b);
    if ($a < $b) return 1;
    if ($a > $b) return -1;
    return 0;
    //return $a < $b ? ) <=> count($b);
});
foreach (array_keys($tags) as $tag) {
    $cnt = count($tags[$tag]);
    echo "{$tag} ($cnt)\n";
    if ($list) {
        foreach ($tags[$tag] as $id => $title) {
            echo '    ' . str_pad($id, 8, ' ', STR_PAD_LEFT)
                . ' ' . $title . PHP_EOL;
        }
    }
}
