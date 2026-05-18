<?php
/**
 * TravHub Smart Upload v3 — Perceptual Hash Helper
 * ================================================
 * Computes a 64-bit perceptual hash (dHash algorithm) for each rasterized
 * page image. Used by classify-document.php for Layer 3 duplicate detection
 * (catches re-scans at different DPI, color/grayscale variants, etc.).
 *
 * Algorithm: dHash (difference hash)
 *   1. Resize image to 9x8 grayscale
 *   2. For each row, compare adjacent pixels: left > right → 1, else → 0
 *   3. 8 rows × 8 comparisons = 64 bits → 16 hex chars
 *
 * Why dHash (not pHash or aHash):
 *   - Faster than pHash (no DCT transform needed)
 *   - More robust than aHash (uses gradients, not absolute brightness)
 *   - Battle-tested for "same document, different scan" cases
 *
 * Two images of the SAME passport page from different scans will produce
 * hashes with Hamming distance < 8 (out of 64). Different documents
 * typically score 20+.
 *
 * Used by:
 *   - api/travelers/classify-document.php  (compute on each page)
 *   - api/travelers/commit-documents.php   (compare for identity-merge)
 *
 * Dependencies: Imagick PHP extension (same as PDF rasterization)
 */


/**
 * Compute a 64-bit dHash perceptual fingerprint of an image file.
 *
 * @param  string  $imagePath  Absolute path to JPG/PNG file
 * @return string|false        16-char hex string (64 bits), or false on failure
 */
function computePerceptualHash($imagePath) {
    if (!file_exists($imagePath) || !extension_loaded('imagick')) {
        return false;
    }

    try {
        $img = new Imagick($imagePath);

        // Strip color, normalize to grayscale 9x8
        $img->setImageColorspace(Imagick::COLORSPACE_GRAY);
        $img->resizeImage(9, 8, Imagick::FILTER_LANCZOS, 1);
        $img->setImageDepth(8);

        // Read pixel grid: 9 cols × 8 rows
        $pixels = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 9; $x++) {
                $px = $img->getImagePixelColor($x, $y);
                $color = $px->getColor();
                // Average channels (Imagick may return RGB even for gray)
                $pixels[$y][$x] = ($color['r'] + $color['g'] + $color['b']) / 3;
            }
        }

        // Build 64-bit hash by comparing adjacent horizontal pixels
        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $bits .= ($pixels[$y][$x] > $pixels[$y][$x + 1]) ? '1' : '0';
            }
        }

        $img->clear();
        $img->destroy();

        // Convert 64-bit binary string → 16-char hex
        return bitsToHex($bits);

    } catch (Exception $e) {
        error_log('[phash] ' . $e->getMessage());
        return false;
    }
}


/**
 * Compute Hamming distance between two hex-encoded hashes.
 * Returns 0-64 (number of differing bits).
 *
 * Decision thresholds (tuned for document scans):
 *   distance < 8   → same page (duplicate)
 *   distance 8-15  → likely same page, slight scan variation
 *   distance 16-25 → different but similar (e.g. consecutive visa stamps)
 *   distance > 25  → clearly different
 *
 * Recommended threshold for "is duplicate?": distance < 8
 *
 * @param  string  $hash1  16-char hex
 * @param  string  $hash2  16-char hex
 * @return int             0-64, or -1 if either hash is invalid
 */
function hammingDistance($hash1, $hash2) {
    if (!isValidHash($hash1) || !isValidHash($hash2)) {
        return -1;
    }

    $bits1 = hexToBits($hash1);
    $bits2 = hexToBits($hash2);

    $distance = 0;
    $len = strlen($bits1);
    for ($i = 0; $i < $len; $i++) {
        if ($bits1[$i] !== $bits2[$i]) $distance++;
    }
    return $distance;
}


/**
 * Convenience: check whether two hashes are similar enough to be considered
 * the same page. Default threshold = 8.
 */
function arePagesSimilar($hash1, $hash2, $threshold = 8) {
    $dist = hammingDistance($hash1, $hash2);
    return $dist >= 0 && $dist < $threshold;
}


/**
 * Given a list of existing page entries (each with `phash`) and a list of
 * new page hashes (in order), return a merge plan.
 *
 * @param  array  $existingPages  Array of {phash, page_no, ...}
 * @param  array  $newHashes      Array of hex strings, one per new page (in order)
 * @param  int    $threshold      Hamming distance below which pages are "same"
 * @return array {
 *     'duplicate_indices' => [0, 2, ...],   // new page indices that match existing
 *     'new_indices'       => [1, 3, 4, ...],// new page indices that are genuinely new
 *     'matches'           => [              // detail per duplicate
 *         ['new_idx' => 0, 'matched_page_no' => 1, 'distance' => 3],
 *         ...
 *     ]
 * }
 */
function planPageMerge($existingPages, $newHashes, $threshold = 8) {
    $plan = ['duplicate_indices' => [], 'new_indices' => [], 'matches' => []];

    foreach ($newHashes as $idx => $newHash) {
        if (!isValidHash($newHash)) {
            // Can't compare — treat as new (better to over-store than mis-merge)
            $plan['new_indices'][] = $idx;
            continue;
        }

        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($existingPages as $existing) {
            $existingHash = $existing['phash'] ?? null;
            if (!isValidHash($existingHash)) continue;

            $d = hammingDistance($newHash, $existingHash);
            if ($d >= 0 && $d < $bestDistance) {
                $bestDistance = $d;
                $bestMatch = $existing;
            }
        }

        if ($bestMatch !== null && $bestDistance < $threshold) {
            $plan['duplicate_indices'][] = $idx;
            $plan['matches'][] = [
                'new_idx'         => $idx,
                'matched_page_no' => $bestMatch['page_no'] ?? null,
                'distance'        => $bestDistance,
            ];
        } else {
            $plan['new_indices'][] = $idx;
        }
    }

    return $plan;
}


// ============================================================================
// Internal helpers
// ============================================================================

function isValidHash($hash) {
    return is_string($hash) && strlen($hash) === 16 && ctype_xdigit($hash);
}

function bitsToHex($bits) {
    $hex = '';
    for ($i = 0; $i < strlen($bits); $i += 4) {
        $hex .= dechex(bindec(substr($bits, $i, 4)));
    }
    return str_pad($hex, 16, '0', STR_PAD_LEFT);
}

function hexToBits($hex) {
    $bits = '';
    for ($i = 0; $i < strlen($hex); $i++) {
        $bits .= str_pad(decbin(hexdec($hex[$i])), 4, '0', STR_PAD_LEFT);
    }
    return $bits;
}