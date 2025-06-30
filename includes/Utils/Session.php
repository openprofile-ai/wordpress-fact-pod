<?php

namespace OpenProfile\WordpressFactPod\Utils;

class Session
{
    const string PREFIX = 'openprofile-wordpress-fact-pod';

    public function __construct()
    {
        add_action('init', 'start_wp_session', 1);
    }

    public static function start_wp_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public static function put(string $key, mixed $data): void
    {
        $_SESSION[self::PREFIX . $key] = serialize($data);
    }

    public static function get(string $key): mixed
    {
        $data = $_SESSION[self::PREFIX . $key] ?? null;

        return ! is_null($data) ? unserialize($data) : null;
    }

    public static function delete(string $ke): void
    {
        unset($_SESSION[self::PREFIX . $ke]);
    }
}