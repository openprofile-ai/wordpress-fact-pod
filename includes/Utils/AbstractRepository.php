<?php

namespace OpenProfile\WordpressFactPod\Utils;

abstract class AbstractRepository
{
    protected const string FACT_POD = 'fact_pod_';

    abstract public function getTable(): string;

    public static function getPrefix(): string
    {
        return self::getDB()->prefix . self::FACT_POD;
    }

    public static function getDB(): \wpdb
    {
        global $wpdb;

        return $wpdb;
    }
}