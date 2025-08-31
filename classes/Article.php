<?php

namespace KnowledgeBase;

use PHPHtmlParser\Dom;
use \AllowDynamicProperties;

#[AllowDynamicProperties]
class Article {

    /**
     * Parsed h2 headlines, inner and outer html.
     * @var array
     */
    private array|null $_headlines;

    private array|null $_pdf;

    /**
     * Create the object from the result of the database stdObj.
     */
    public function __construct($obj)
    {
        foreach (get_object_vars($obj) as $property => $value) {
            $this->$property = $value;
        }
        $this->_headlines = null;
        $this->_pdf = null;
    }

    /**
     * Get the category hierarchy from the first category entry (assuming each article
     * has only one category assignment).
     *
     * @return string
     */
    public function getCategoryStr(): string
    {
        if (!isset($this->categories) || empty($this->categories)) {
            return '';
        }
        return implode(' > ', array_map(fn($c) => $c->name, reset($this->categories)));
    }

    /**
     * Get the tag names attached to this article.
     *
     * @return array
     */
    public function getTagsList(): array
    {
        if (!isset($this->tags) || empty($this->tags)) {
            return [];
        }
        return array_map(fn($t) => $t->name, $this->tags);
    }

    /**
     * Get a list of h2 headlines in the order as they appear in the content.
     *
     * @param ?bool $outerhtml 
     * @return array
     */
    public function getH2List(?bool $outerhtml = false): array
    {
        if ($this->_headlines === null) {
            $this->_headlines = [
                'inner' => [],
                'outer' => [],
            ];
            // parse the HTML content using php-html-parser
            $dom = new Dom;
            $dom->loadStr($this->post_content);
            foreach ($dom->find('h2') as $h2) {
                $this->_headlines['inner'][] = $h2->innerHTML;
                $this->_headlines['outer'][] = $h2;
            }
        }
        return $outerhtml === true
            ? $this->_headlines['outer']
            : $this->_headlines['inner'];
    }

    /**
     * Return headlines that have no text followed.
     *
     * @return array list of headlines
     */
    public function getEmptyHeadlines(): array
    {
        $emptyAfterHeadline = [];
        $headlines = $this->getH2List(true);
        $headlines[] = '__~end_marker~__';
        $content = $this->post_content . '__~end_marker~__';
        for ($i = 0, $is = count($headlines) - 1; $i < $is; $i++) {
            $start = strpos($content, $headlines[$i]) + strlen($headlines[$i]);
            $end =  strpos($content, $headlines[$i + 1]);
            $snippet = substr($content, $start, ($end - $start));
            $snippet = trim(str_replace('&nbsp;', '', $snippet));
            if (empty($snippet)) {
                $emptyAfterHeadline[] = strip_tags($headlines[$i]);
            }
        }
        return $emptyAfterHeadline;
    }

    /**
     * Compare the H2 headlines, that they appear in a given order.
     *
     * @param array $expected headlines
     * @param bool $ignoreMissing headlines (default false)
     * @return bool true on success false otherwise
     */
    public function checkHeadlineSequence(array $expected, ?bool $ignoreMissing = true): bool
    {
        $incoming = $this->getH2List();
        if (!empty($expected) && empty($incoming)) {
            return false;
        }
        if ($ignoreMissing === false) {
            for ($i = 0, $is = count($incoming); $i < $is; $i++) {
                if (trim($incoming[$i]) !== $expected[$i]) {
                    return false;
                }
            }
            return true;
        }
        $pos = 0;
        foreach ($incoming as $item) {
            $item = trim($item);
            // Search for $item in $expected starting from $pos
            $found = false;

            while ($pos < count($expected)) {
                if ($expected[$pos] === $item) {
                    $found = true;
                    $pos++; // Move to next position for the next search
                    break;
                }
                $pos++;
            }
            // If an item from incoming is not found in order, return false
            if (!$found) {
                return false;
            }
        }
        return true;            
    }

    /**
     * If this article is a glossary entry can be checked via the category.
     *
     * @return bool
     */
    public function isGlossarEntry(): bool
    {
        foreach ($this->categories as $category) {
            foreach ($category as $item) {
                if (stripos($item->name, 'glossar') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all headlines (h1 - h5) in the order as they appear in the post content.
     * We include here h1 headlines although they should not appear in the post content.
     * @return array
     */
    public function getAllHeadlines(): array
    {
        $headlines = [];
        $dom = new Dom;
        $dom->loadStr("<body>{$this->post_content}</body>");
        $elements = [$dom->find('body')[0]->firstChild()];
        while ($el = \array_shift($elements)) {
            if (\in_array($el->tag->name(), ['h1', 'h2', 'h3', 'h4', 'h5'])) {
                $level = (int)substr($el->tag->name(), 1);
                $headlines[] = [
                    'level' => $level,
                    'label' => strip_tags($el->innerHTML),
                ];
            }
            if ($el->tag->name() !== 'text' && $el->hasChildren()) {
                $elements[] = $el->firstChild();
            }
            if ($el->hasNextSibling()) {
                $elements[] = $el->nextSibling();
            }
        }
        return $headlines;
    }

    /**
     * Retrieve all inline PDF that are not part of a link.
     * @return array
     */
    public function getInlinePdf(): array
    {
        if ($this->_pdf === null) {
            $this->_pdf = [];
            // Eliminate all links:
            $content = preg_replace('/<a[^>]*>.*?<\/a>/', '', $this->post_content);
            // Check for the remaining inline PDF that are rendered in a js reader on Wordpress.
            if (preg_match_all('/\bhttp.*?\.pdf\b/', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $this->_pdf[$match] = true;
                }
            }
        }
        return $this->_pdf;
    }

    /**
     * Approx count of words of a post.
     *
     * @return int
     */
    public function wordcount(): int
    {
        return count(array_filter(
            explode(' ', strip_tags($this->post_content)),
            fn($s) => !empty(trim($s))
        )); 
    }

    /**
     * Interlal permalink without slug.
     *
     * @return string
     */
    public function permalink(): string
    {
        return substr($this->guid, 0, strpos($this->guid, '/', 10)) . '/?p=' . $this->ID;
    }

    /**
     * Returns an array of the link content of the cc link and an image, in case there is one.
     *
     * @return array
     */
    public function getCcLicense(): array
    {
        $res = [];
        $dom = new Dom;
        $dom->loadStr($this->post_content);
        foreach ($dom->find('a, img') as $tag) {
            if ($tag->tag->name() === 'a' &&
                strpos($tag->getAttribute('href'), 'https://creativecommons.org/licenses/') === 0
            ) {
                $res[] = $tag->text();
            }
            if ($tag->tag->name() === 'img') {
                $src = $tag->getAttribute('src');
                $file = substr($src, strrpos($src, '/') + 1);
                if (str_contains($file, 'by-nc') || str_contains($file, 'by-sa')
                    || str_contains($file, 'CC-by') || str_contains($file, 'by-nd')
                    || str_contains($file, 'cc-zero')
                ) {
                    $res[] = $file;
                }
            }
        }
        return $res;
    }

    /**
     * Check with what the article starts.
     *
     * @return string
     */
    function checkPostIntro(): string {

        // Load the DOM parser
        $dom = new Dom;
        $dom->loadStr("<body>{$this->post_content}</body>");

        // Find all top-level elements inside the content
        $el = $dom->find('body')[0]->firstChild();

        // Loop through elements to find the first significant tag
        while ($el) {
            $tagName = $el->tag->name();
            // First check for empty or abitrary text nodes, that come with spaces and newlines.
            if ($tagName === 'text') {

                if (trim($el->text) === '') {
                    // Skip empty text nodes / whitespace
                    if ($el->hasNextSibling()) {
                        $el = $el->nextSibling();
                        continue;
                    }
                }

                // If the text starts with someting like [caption id="attachment_6660" align="alignnone" width="595"]
                // then that is an image.
                if (strpos(trim($el->text), '[caption ') === 0) {
                    return 'starts_with_image';
                }

                // Apparently some other text.
                return 'starts_with_text';
            }

            // Case 1: First element is an image
            if ($tagName === 'img' || ($tagName === 'figure' && $el->find('img')->count() > 0)) {
                return 'starts_with_image';
            }

            // Case 2: First element is a subheadline AND next is an image
            if (\in_array($tagName, ['h2', 'h3', 'h4', 'h5'])) {
                // Check next element if available
                if ($el->hasNextSibling()) {
                    $nextEl = $el->nextSibling();
                    $nextTagName = $nextEl->tag->name();
                    if (
                        $nextTagName === 'img' ||
                        ($nextTagName === 'figure' && $nextEl->find('img')->count() > 0) ||
                        ($nextTagName === 'text' && strpos(trim($nextEl->text), '[caption ') === 0)
                    ) {
                        return 'starts_with_subheadline_and_image';
                    }
                }
                return 'starts_with_subheadline';
            }
            // We are here because we have an element, e.g. a div or span or whatever.
            if ($el->hasChildren()) {
                $el = $el->firstChild();
            } else if ($el->hasNextSibling()) {
                $el = $el->nextSibling();
            } else {
                $el = null;
            }
        }

        return 'no_content';
    }
}
