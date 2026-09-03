<?php
/**
 * FILE PATH: server/credential_crypto.php
 *
 * AES-256-GCM encrypt/decrypt শুধুমাত্র traveler credentials.password field-এর
 * জন্য — portal/username/url/notes plaintext-ই থাকে, শুধু password
 * এনক্রিপ্ট হয়।
 *
 * Key file: root-level 'encryption-key.txt' (gemini-apikey.txt এর একই
 * convention) — .gitignore-এ যোগ করা আবশ্যক, কখনো commit করা যাবে না।
 * প্রথমবার require হলে ফাইল না থাকলে নিজে থেকেই random 256-bit key
 * generate করে ফাইলে লিখে দেয়।
 *
 * Format (per encrypted value, base64-encoded):
 *   IV (12 bytes) + ciphertext + auth-tag (16 bytes)
 * প্রতিটা encryption-এ নতুন random IV — একই password বারবার এনক্রিপ্ট
 * করলেও ভিন্ন output আসে।
 */

function _credCryptoKeyPath(): string
{
    return __DIR__ . '/../encryption-key.txt';
}

function _credCryptoGetKey(): string
{
    static $key = null;
    if ($key !== null) return $key;

    $path = _credCryptoKeyPath();

    if (file_exists($path)) {
        $stored = trim(@file_get_contents($path));
        $decoded = base64_decode($stored, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            $key = $decoded;
            return $key;
        }
        // ফাইল আছে কিন্তু invalid/করাপ্টেড — নিরাপত্তার জন্য silently
        // নতুন key generate না করে explicit error দেওয়া হচ্ছে, নাহলে
        // পুরনো encrypted data চিরতরে অপাঠযোগ্য হয়ে যেতে পারে চুপচাপ
        throw new RuntimeException('encryption-key.txt পাওয়া গেছে কিন্তু valid base64(32-byte) key না — ম্যানুয়ালি চেক করুন');
    }

    // প্রথমবার — নতুন key generate করে সেভ করো
    $newKey = random_bytes(32);
    $written = @file_put_contents($path, base64_encode($newKey));
    if ($written === false) {
        throw new RuntimeException('encryption-key.txt লেখা যায়নি — ফাইল পারমিশন চেক করুন');
    }
    @chmod($path, 0600); // শুধু owner পড়তে পারবে

    $key = $newKey;
    return $key;
}

/**
 * Plaintext password → encrypted base64 string
 * খালি/null input হলে খালি স্ট্রিং-ই ফেরত (এনক্রিপ্ট করার কিছু নেই)
 */
function credEncrypt(?string $plaintext): string
{
    if ($plaintext === null || $plaintext === '') return '';

    $key = _credCryptoGetKey();
    $iv  = random_bytes(12); // GCM-এর জন্য standard 96-bit IV

    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext, 'aes-256-gcm', $key,
        OPENSSL_RAW_DATA, $iv, $tag, '', 16
    );

    if ($ciphertext === false) {
        throw new RuntimeException('Password encryption failed');
    }

    return base64_encode($iv . $ciphertext . $tag);
}

/**
 * Encrypted base64 string → plaintext password
 * Decrypt ব্যর্থ হলে (corrupted data, key mismatch, বা এই value আসলে
 * এখনো plaintext legacy data) exception না ছুঁড়ে খালি স্ট্রিং ফেরত —
 * caller UI-তে 'password unreadable' এর বদলে খালি দেখাবে, পুরো request
 * ভেঙে পড়বে না।
 */
function credDecrypt(?string $encoded): string
{
    if ($encoded === null || $encoded === '') return '';

    try {
        $key  = _credCryptoGetKey();
        $raw  = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 12 + 16) return '';

        $iv         = substr($raw, 0, 12);
        $tag        = substr($raw, -16);
        $ciphertext = substr($raw, 12, -16);

        $plaintext = openssl_decrypt(
            $ciphertext, 'aes-256-gcm', $key,
            OPENSSL_RAW_DATA, $iv, $tag
        );

        return $plaintext !== false ? $plaintext : '';
    } catch (Throwable $e) {
        error_log('[credential_crypto] decrypt failed: ' . $e->getMessage());
        return '';
    }
}