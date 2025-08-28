<?php

namespace KnowledgeBase;

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
