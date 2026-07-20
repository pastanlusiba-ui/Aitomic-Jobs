<?php
/**
 * Verify recently imported opportunity posts by ID floor.
 *
 * Usage:
 * wp eval-file /home/u710255073/aitomic-tools/verify_recent_opportunity_import.php min_id=3418
 */

if (!defined('ABSPATH')) {
    exit;
}

$min_id = 0;
foreach (($args ?? []) as $arg) {
    if (str_starts_with($arg, 'min_id=')) {
        $min_id = max(0, (int) substr($arg, strlen('min_id=')));
    }
}

global $wpdb;

$ids = $wpdb->get_col($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND ID >= %d ORDER BY ID ASC",
    'opportunity',
    'publish',
    $min_id
));

$missing_sections = 0;
$by_type = [];
$by_country = [];
$samples = [];

foreach ($ids as $raw_id) {
    $id = (int) $raw_id;
    $content = (string) get_post_field('post_content', $id);
    if (stripos($content, '<h2>Responsibilities</h2>') === false || stripos($content, '<h2>Requirements / Eligibility</h2>') === false) {
        $missing_sections++;
    }

    foreach (wp_get_post_terms($id, 'opportunity_type', ['fields' => 'names']) as $type) {
        $by_type[$type] = ($by_type[$type] ?? 0) + 1;
    }

    foreach (wp_get_post_terms($id, 'country', ['fields' => 'names']) as $country) {
        $by_country[$country] = ($by_country[$country] ?? 0) + 1;
    }

    if (count($samples) < 10) {
        $samples[] = [
            'id' => $id,
            'title' => get_the_title($id),
            'url' => get_permalink($id),
            'source' => get_post_meta($id, '_go_source', true),
            'source_url' => get_post_meta($id, '_go_source_url', true),
        ];
    }
}

arsort($by_country);
ksort($by_type);

echo wp_json_encode([
    'min_id' => $min_id,
    'checked' => count($ids),
    'id_range' => $ids ? [(int) min($ids), (int) max($ids)] : [null, null],
    'missing_sections' => $missing_sections,
    'by_type' => $by_type,
    'top_countries' => array_slice($by_country, 0, 20, true),
    'samples' => $samples,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
