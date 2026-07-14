<?php
if (!defined('ABSPATH')) {
    exit;
}

$query = new WP_Query([
    'post_type' => 'opportunity',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => [[
        'taxonomy' => 'country',
        'field' => 'slug',
        'terms' => ['global-international'],
    ]],
]);

$locations = [];
foreach ($query->posts as $post_id) {
    $location = trim((string) get_post_meta($post_id, '_go_location', true));
    if ($location === '') {
        $location = trim((string) get_post_meta($post_id, '_go_city', true));
    }
    if ($location === '') {
        $location = '(blank)';
    }
    $locations[$location] = ($locations[$location] ?? 0) + 1;
}

arsort($locations);
$top = array_slice($locations, 0, 120, true);
foreach ($top as $location => $count) {
    echo $count . "\t" . $location . "\n";
}
