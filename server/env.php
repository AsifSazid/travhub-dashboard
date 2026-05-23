<?php
/**
 * env.php  —  Minimal .env loader for TravHub (no Composer dependency)
 * ====================================================================
 * Loads KEY=VALUE pairs from a .env file at the PROJECT ROOT into a static
 * store, readable via env('KEY', $default).
 *
 * Design goals:
 *   - Zero dependencies (no vlucas/phpdotenv needed).
 *   - Safe to include multiple times (loads once).
 *   - NON-BREAKING: every consumer passes a fallback, so if .env is missing
 *     or a key is absent, the app keeps using its current hardcoded value.
 *
 * Usage:
 *   require_once __DIR__ . '/env.php';
 *   $pass = env('DB_PASSWORD', 'travhub2025');   // fallback = old value
 *
 * .env format (at project root, NOT web-accessible, gitignored):
 *   DB_HOST=localhost
 *   DB_NAME=travhub_dev
 *   DB_USER=root
 *   DB_PASSWORD=secret
 *   # comments and blank lines are ignored
 */

if (!function_exists('env')) {

    /**
     * Locate and parse the .env file once. Searches a few likely roots so it
     * works whether called from /server, /api/travelers, or /auth.
     */
    function _th_load_env(): array
    {
        static $vars = null;
        if ($vars !== null) {
            return $vars;
        }
        $vars = [];

        // Candidate locations for the project-root .env, relative to this file.
        $candidates = [
            __DIR__ . '/../.env',   // server/ -> root
            __DIR__ . '/../../.env',// api/travelers/ depth (if env.php copied deeper)
            __DIR__ . '/.env',      // same dir (defensive)
        ];

        $envPath = null;
        foreach ($candidates as $c) {
            if (is_file($c) && is_readable($c)) {
                $envPath = $c;
                break;
            }
        }

        if ($envPath === null) {
            return $vars; // no .env -> consumers fall back to defaults
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));

            // Strip surrounding quotes if present
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last  = $val[strlen($val) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }
            $vars[$key] = $val;
        }
        return $vars;
    }

    /**
     * Read a config value: .env first, then $_ENV/$_SERVER, then the default.
     * The default keeps the app working when .env is not yet configured.
     */
    function env(string $key, $default = null)
    {
        $vars = _th_load_env();
        if (array_key_exists($key, $vars) && $vars[$key] !== '') {
            return $vars[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $default;
    }
}