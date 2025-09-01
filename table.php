<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use KnowledgeBase\Kb;

$htmlpage = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'table.html');
$start = strpos($htmlpage, '<tbody>') + 7;
$end = strpos($htmlpage, '</tbody>');
$htmlsnippet = substr($htmlpage, $start, $end - $start);
echo substr($htmlpage, 0, $start);
$htmltail = substr($htmlpage, $end);
unset($htmlpage);

/** @var $article KnowledgeBase\Article */
foreach (Kb::getArticles() as $article) {
    $info = [
        'Category' => $article->getCategoryStr(),
        'Headlines' => implode('<br/>', array_map(
            fn($headline) => str_repeat('&nbsp;&nbsp;', $headline['level']) . $headline['label'],
            $article->getAllHeadlines()
        )),
    ];
    $error = [];

    $headlineCheck = true;
    if ($article->isGlossarEntry()) {
        if (empty($article->getH2List())) {
            $headlineCheck = false;
            $error[] = 'Glossary article contains no headlines to check with expected structure.';
        }
        else if (!$article->checkHeadlineSequence(explode(',', $CFG['GLOSSARY_HEADLINES']))) {
            $headlineCheck = false;
            $error[] = 'Glossary article contains other headlines or in a different order.'
                . '<br/>contained: '
                . implode(', ', $article->getH2List())
                . '  - expected: ' . str_replace(',', ', ', $CFG['GLOSSARY_HEADLINES']);
        }
    }
    // Check for headlines with empty content following.
    $emptyHeadlines = $article->getEmptyHeadlines();
    if (!empty($emptyHeadlines)) {
        $headlineCheck = false;
        $error[] = 'These headlines have no content:'
            . '<ul><li>' . implode('</li><li>', $emptyHeadlines) . '</li></ul>';
    }
    $h1headlines = array_filter($article->getAllHeadlines(), fn($h1) => $h1['level'] === 1);
    if (!empty($h1headlines)) {
        $error[] = 'H1 headline found: ' . implode(', ', array_map(fn($h) => $h['label'], $h1headlines));
    }

    if (empty($article->getTagsList())) {
        $error[] = 'Article has no keywords set.';
    }

    $info['Article into'] = Kb::checkPostIntro($article);

    // Check if article contains an inline PDF that is displayed at the page with some javascript.
    if (!empty($article->getInlinePdf())) {
        $info['Inline PDF'] = '<ul><li>' . implode('</li><li>', array_keys($article->getInlinePdf())) . '</li></ul>';
    }
    // Check, whether the article has a cc license.
    $ccLicense = $article->getCcLicense();
    if (!empty($ccLicense)) {
        $info['License'] = $ccLicense[0];
        if (count($ccLicense) > 1) {
            $info['License'] .= '<br/>' . $ccLicense[1];
        }
    }

    $editlink = str_replace(
        "?p={$article->ID}",
        "wp-admin/post.php?post={$article->ID}&action=edit",
        $article->permalink()
    );

    $data = [
        'ID' => $article->ID,
        'VIEWLINK' => "<a href=\"{$article->permalink()}\">[VIEW]</a>",
        'EDITLINK' => "<a href=\"{$editlink}\">[EDIT]</a>",
        'TITLE' => $article->post_title,
        'WC' => $article->wordcount(),
        'PDF' => count($article->getInlinePdf()),
        'HEADLINE_CHECK' => $headlineCheck ? 'OK' : 'ERROR',
        'GLOSSARY' => $article->isGlossarEntry() ? 'yes' : 'no',
        'TAGS' => implode(', ', $article->getTagsList()),
        'LICENSE_CHECK' => (!empty($ccLicense)) ? 'yes' : 'no',
    ];
    
    $tableRow = $htmlsnippet;
    foreach ($data as $key => $val) {
        $tableRow = str_replace('__' . $key . '__', $val, $tableRow);
    }
    $infoStr = '';
    foreach ($info as $key => $val) {
        $tag = strpos($val, '<ul>') ? 'div' : 'p';
        $infoStr .= "<h3>{$key}</h3><{$tag}>{$val}</{$tag}>";
    }
    if (!empty($error)) {
        $tableRow = str_replace('"styleid"><button>', '"stylered"><button>', $tableRow);
        $infoStr .= '<h3>Errors</h3><ul>';
        foreach ($error as $err) {
            $infoStr .= "<li>{$err}</li>";
        }
    }
    $tableRow = str_replace('__INFO__', $infoStr, $tableRow);
    echo $tableRow;
}
echo str_replace('__DATE__', date(DATE_RFC850), $htmltail);