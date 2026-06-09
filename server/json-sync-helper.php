<?php

function _jsonRoot(): string
{
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' .
           trim(file_get_contents(__DIR__ . '/../server-name.txt') ?: '');
}

function _writeJson(string $path, mixed $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $tmp  = $path . '.tmp';
    if (file_put_contents($tmp, $json) === false) return false;
    return rename($tmp, $path);
}

// ── Countries + Cities ────────────────────────────────────────────────
function syncCountriesJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT id, sys_id, name, code, currency, currency_code, default_rate, region, cities
            FROM countries WHERE status = 'active' ORDER BY name ASC
        ")->fetchAll();

        $countries = [];
        $cities    = [];

        foreach ($rows as $row) {
            $countries[] = [
                'sys_id'        => $row['sys_id'],
                'name'          => $row['name'],
                'code'          => $row['code'],
                'currency'      => $row['currency'],
                'currency_code' => $row['currency_code'],
                'default_rate'  => (float) $row['default_rate'],
                'region'        => $row['region'],
            ];
            foreach (json_decode($row['cities'] ?? '[]', true) ?: [] as $city) {
                $cities[] = [
                    'sys_id'         => $city['sys_id'],
                    'name'           => $city['name'],
                    'country_sys_id' => $row['sys_id'],
                    'country_name'   => $row['name'],
                    'type'           => $city['type']       ?? [],
                    'popularity'     => $city['popularity'] ?? 3,
                    'cost_level'     => $city['cost_level'] ?? 'medium',
                    'visa_ease'      => $city['visa_ease']  ?? 'medium',
                ];
            }
        }
        return _writeJson(_jsonRoot() . '/api/countries.json',
            ['countries' => $countries, 'cities' => $cities]);
    } catch (Throwable $e) {
        error_log('syncCountriesJson: ' . $e->getMessage());
        return false;
    }
}

// ── Activities ─────────────────────────────────────────────────────────
function syncActivitiesJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, country_name, city_sys_id, city_name,
                   name, type, category, duration_hours, popularity, images
            FROM activities
            WHERE status = 'active' AND is_package_override = 0
            ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $images = json_decode($row['images'] ?? '[]', true) ?: [];
            $out[] = [
                'sys_id'         => $row['sys_id'],
                'country_sys_id' => $row['country_sys_id'],
                'country_name'   => $row['country_name'],
                'city_sys_id'    => $row['city_sys_id'],
                'city_name'      => $row['city_name'],
                'name'           => $row['name'],
                'type'           => $row['type'],
                'category'       => $row['category'],
                'duration_hours' => (float) $row['duration_hours'],
                'popularity'     => (int)   $row['popularity'],
                'thumb'          => $images[0]['url'] ?? null,
            ];
        }
        return _writeJson(_jsonRoot() . '/api/activities.json', ['activities' => $out]);
    } catch (Throwable $e) {
        error_log('syncActivitiesJson: ' . $e->getMessage());
        return false;
    }
}

// ── Cars ───────────────────────────────────────────────────────────────
function syncCarsJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, country_name, name, type, seats, max_luggage
            FROM cars WHERE status = 'active' ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'sys_id'         => $row['sys_id'],
                'country_sys_id' => $row['country_sys_id'],
                'country_name'   => $row['country_name'],
                'name'           => $row['name'],
                'type'           => $row['type'],
                'seats'          => (int) $row['seats'],
                'max_luggage'    => $row['max_luggage'],
            ];
        }
        return _writeJson(_jsonRoot() . '/api/cars.json', ['cars' => $out]);
    } catch (Throwable $e) {
        error_log('syncCarsJson: ' . $e->getMessage());
        return false;
    }
}

// ── Hotels ─────────────────────────────────────────────────────────────
function syncHotelsJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, country_name, city_sys_id, city_name,
                   name, star_rating, check_in_time, check_out_time, images
            FROM hotels WHERE status = 'active' ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $images = json_decode($row['images'] ?? '[]', true) ?: [];
            $out[] = [
                'sys_id'         => $row['sys_id'],
                'country_sys_id' => $row['country_sys_id'],
                'country_name'   => $row['country_name'],
                'city_sys_id'    => $row['city_sys_id'],
                'city_name'      => $row['city_name'],
                'name'           => $row['name'],
                'star_rating'    => (int) ($row['star_rating'] ?? 0),
                'check_in_time'  => $row['check_in_time'],
                'check_out_time' => $row['check_out_time'],
                'thumb'          => $images[0]['url'] ?? null,
            ];
        }
        return _writeJson(_jsonRoot() . '/api/hotels.json', ['hotels' => $out]);
    } catch (Throwable $e) {
        error_log('syncHotelsJson: ' . $e->getMessage());
        return false;
    }
}

// ── Currencies ────────────────────────────────────────────────────────
function syncCurrenciesJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, currency_code, name, symbol, decimal_places
            FROM currencies WHERE status = 'active' ORDER BY currency_code ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'sys_id'         => $row['sys_id'],
                'currency_code'  => $row['currency_code'],
                'name'           => $row['name'],
                'symbol'         => $row['symbol'],
                'decimal_places' => (int) $row['decimal_places'],
            ];
        }
        return _writeJson(_jsonRoot() . '/api/currencies.json', ['currencies' => $out]);
    } catch (Throwable $e) {
        error_log('syncCurrenciesJson: ' . $e->getMessage());
        return false;
    }
}

// ── Transport Services ────────────────────────────────────────────────
function syncTransportJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, country_sys_id, country_name, name, type,
                   from_city_name, to_city_name, direction, duration_typical
            FROM transport_services WHERE status = 'active' ORDER BY country_sys_id ASC, name ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'sys_id'          => $row['sys_id'],
                'country_sys_id'  => $row['country_sys_id'],
                'country_name'    => $row['country_name'],
                'name'            => $row['name'],
                'type'            => $row['type'],
                'from_city_name'  => $row['from_city_name'],
                'to_city_name'    => $row['to_city_name'],
                'direction'       => $row['direction'],
                'duration_typical'=> $row['duration_typical'],
            ];
        }
        return _writeJson(_jsonRoot() . '/api/transport.json', ['transport' => $out]);
    } catch (Throwable $e) {
        error_log('syncTransportJson: ' . $e->getMessage());
        return false;
    }
}

// ── Components ────────────────────────────────────────────────────────
function syncComponentsJson(PDO $pdo): bool
{
    try {
        $rows = $pdo->query("
            SELECT sys_id, name, category, description
            FROM components WHERE status = 'active' ORDER BY category ASC, name ASC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'sys_id'      => $row['sys_id'],
                'name'        => $row['name'],
                'category'    => $row['category'],
                'description' => $row['description'],
            ];
        }
        return _writeJson(_jsonRoot() . '/api/components.json', ['components' => $out]);
    } catch (Throwable $e) {
        error_log('syncComponentsJson: ' . $e->getMessage());
        return false;
    }
}