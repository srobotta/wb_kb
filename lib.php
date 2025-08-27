<?php

class Db {

    protected static function get(): \PDO
    {
        global $CFG;
        static $instance;

        if (!$instance) {
            $options = [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];
            //$instance = new mysqli($CFG['DB_SERVER'], $CFG['DB_USER'], $CFG['DB_PASS'], $CFG['DB_NAME']);
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $CFG['DB_HOST'], $CFG['DB_NAME'], $CFG['DB_CHARSET']);
            $instance = new \PDO($dsn, $CFG['DB_USER'], $CFG['DB_PASS'], $options);
        }

        return $instance;
    }

    public static function query(string $sql, array $params = []): array
    {
        global $CFG;
        $res = [];
        $sql = preg_replace('/\{(\w[\w_\d]+\w)\}/', $CFG['TABLE_PREFIX'] . "$1", $sql);
        $db = static::get();
        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            foreach($params as $i => $param) {
                if (is_int($param)) {
                    $stmt->bindValue($i + 1, $param, \PDO::PARAM_INT);
                } else if (is_bool($param)) {
                    $stmt->bindValue($i + 1, $param, \PDO::PARAM_BOOL);
                } else {
                    $stmt->bindValue($i + 1, $param);
                }
            }
        }
        $stmt->execute();
        $result = $stmt->getIterator();
        foreach($result as $row) {
            $res[] = $row;
        }
        return $res;
    }
}

class Kb {
    public static function getArticleCount(): int
    {
        global $CFG;
        $sql = '
            SELECT count(id) as cnt FROM {posts}
            WHERE post_type = ?
            AND post_status = ?
        ';
        $res = Db::query($sql, ['epkb_post_type_' . $CFG['EP_KB_ID'], 'publish']);
        if ($res) {
            return (int)$res[0]->cnt;
        }
        return 0;
    }

    public static function getArticles($ids = []): Iterator
    {
        global $CFG;
        $sql = '
            SELECT * FROM {posts}
            WHERE post_type = ?
            AND post_status = ?
            ORDER BY id
            LIMIT ?, ?
        ';
        if (!empty($ids)) {
            $sanitized = implode(',', array_map(fn($id) => (int)$id, $ids));
            $sql = str_replace('WHERE', 'WHERE ID IN (' . $sanitized . ') AND ', $sql);
        }
        $offset = 0;
        $limit = 50;
        do {
            $res = Db::query($sql, [
                'epkb_post_type_' . $CFG['EP_KB_ID'],
                'publish',
                $offset,
                $limit
            ]);
            foreach ($res as $row) {
                $post = static::getLatestRevision($row);
                $post = static::getMetaData($post);
                $post = static::getTaxonomiesForPost($post);
                yield $post;
            }
            $offset += $limit;
        } while (count($res) === $limit);
    }

    protected static function getLatestRevision(object $post): object
    {
        $properties = ['post_content', 'post_title', 'post_excerpt', 'post_modified',
            'post_modified_gmt', 'post_content_filtered'
        ];
        $sql = '
            SELECT ' . implode(',', $properties) . '
            FROM {posts}
            WHERE post_type = \'revision\'
            AND post_parent = ?
            ORDER BY post_modified DESC
            LIMIT 1
        ';
        $res = Db::query($sql, [$post->ID]);
        if (count($res) === 1) {
            foreach ($properties as $property) {
                $post->$property = $res[0]->$property;
            }
        }
        return $post;
    }

    protected static function getMetaData(object $post): object
    {
        $sql = '
            SELECT meta_key, meta_value 
            FROM {postmeta} 
            WHERE post_id = ? 
        ';
        $res = Db::query($sql, [$post->ID]);
        $post->meta = [];
        foreach ($res as $row) {
            $post->meta[$row->meta_key] = $row->meta_value;
        }
        return $post;
    }

    protected static function getTaxonomiesForPost(object $post)
    {
        $sql = 'SELECT term_taxonomy_id FROM {term_relationships} WHERE object_id = ? ORDER BY term_order';
        $res = Db::query($sql, [$post->ID]);
        $post->categories = [];
        $post->tags = [];
        foreach ($res as $row) {
            if (\array_key_exists($row->term_taxonomy_id, static::getCategories())) {
                $post->categories[$row->term_taxonomy_id] = [static::getCategories()[$row->term_taxonomy_id]];
                while ($post->categories[$row->term_taxonomy_id][0]->parent > 0) {
                    \array_unshift(
                        $post->categories[$row->term_taxonomy_id],
                        static::getCategories()[$post->categories[$row->term_taxonomy_id][0]->parent]
                    );
                }
                continue;
            }
            if (\array_key_exists($row->term_taxonomy_id, static::getTags())) {
                $post->tags[$row->term_taxonomy_id] = static::getTags()[$row->term_taxonomy_id];
            }
        }
        return $post;
    }

    /**
     * Get the categories of the Knowledge Base.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        static $categories;

        if (!\is_array($categories)) {
            global $CFG;
            $taxonomy = sprintf('epkb_post_type_%d_category', $CFG['EP_KB_ID']);
            $categories = [];
            foreach (static::getTermsByTaxonomy($taxonomy) as $term) {
                $categories[$term->term_id] = $term;
            }
        }
        return $categories;
    }

    /**
     * Get all the defined tags of the Knowledge Base.
     *
     * @return array
     */
    protected static function getTags(): array
    {
        static $tags;

        if (!\is_array($tags)) {
            global $CFG;
            $taxonomy = sprintf('epkb_post_type_%d_tag', $CFG['EP_KB_ID']);
            $tags = [];
            foreach(static::getTermsByTaxonomy($taxonomy) as $term) {
                $tags[$term->term_id] = $term;
            }
        }
        return $tags;
    }

    /**
     * Get a taxonomy by type.
     *
     * @param string $type
     * return @array 
     */
    protected static function getTermsByTaxonomy(string $type): array
    {
        $sql = '
            SELECT t.term_id, t.name, t.slug, tt.parent
            FROM {terms} t
            JOIN {term_taxonomy} tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = ?
            ORDER BY t.term_id
        ';
        return Db::query($sql, [$type]);
    }
}
