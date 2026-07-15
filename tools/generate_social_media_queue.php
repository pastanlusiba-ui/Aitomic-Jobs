<?php
if (!defined('ABSPATH')) {
    exit;
}

function aitomic_social_arg(array $args, string $key, string $default = ''): string {
    foreach ($args as $arg) {
        if (str_starts_with($arg, $key . '=')) {
            return substr($arg, strlen($key) + 1);
        }
    }
    return $default;
}

function aitomic_social_term_list(int $post_id, string $taxonomy): string {
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    return implode(', ', wp_list_pluck($terms, 'name'));
}

function aitomic_social_meta(int $post_id, string $key): string {
    $value = get_post_meta($post_id, '_go_' . $key, true);
    return trim((string) $value);
}

function aitomic_social_trim(string $text, int $limit): string {
    $text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    $text = trim($text);
    if (strlen($text) <= $limit) {
        return $text;
    }
    $cut = substr($text, 0, max(0, $limit - 3));
    $space = strrpos($cut, ' ');
    if ($space !== false && $space > 40) {
        $cut = substr($cut, 0, $space);
    }
    return rtrim($cut, " \t\n\r\0\x0B.,;:") . '...';
}

function aitomic_social_deadline_label(int $post_id): string {
    $deadline = aitomic_social_meta($post_id, 'deadline');
    if ($deadline === '') {
        return 'Deadline not specified';
    }
    $timestamp = strtotime($deadline);
    if (!$timestamp) {
        return 'Deadline: ' . $deadline;
    }
    return 'Deadline: ' . date_i18n('M j, Y', $timestamp);
}

function aitomic_social_is_expired(int $post_id): bool {
    $deadline = aitomic_social_meta($post_id, 'deadline');
    if ($deadline === '') {
        return false;
    }
    $timestamp = strtotime($deadline . ' 23:59:59');
    return $timestamp && $timestamp < current_time('timestamp');
}

function aitomic_social_hashtags(string $type, string $country): string {
    $tags = ['#AitomicJobs'];
    $type_lower = strtolower($type);
    if (str_contains($type_lower, 'intern')) {
        $tags[] = '#Internships';
    } elseif (str_contains($type_lower, 'tender') || str_contains($type_lower, 'consult')) {
        $tags[] = '#Consultancies';
    } elseif (str_contains($type_lower, 'remote')) {
        $tags[] = '#RemoteJobs';
    } elseif (str_contains($type_lower, 'volunteer')) {
        $tags[] = '#VolunteerOpportunities';
    } else {
        $tags[] = '#Jobs';
    }
    $country_tag = preg_replace('/[^A-Za-z0-9]/', '', $country);
    if ($country_tag !== '' && !in_array(strtolower($country), ['global/international', 'remote', 'various'], true)) {
        $tags[] = '#' . $country_tag;
    }
    return implode(' ', array_slice(array_unique($tags), 0, 4));
}

function aitomic_social_record(int $post_id): array {
    $title = html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $organization = html_entity_decode(aitomic_social_meta($post_id, 'organization'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $type = aitomic_social_term_list($post_id, 'opportunity_type') ?: aitomic_social_meta($post_id, 'employment_type');
    $country = aitomic_social_term_list($post_id, 'country') ?: aitomic_social_meta($post_id, 'country');
    $work_mode = aitomic_social_term_list($post_id, 'work_mode') ?: aitomic_social_meta($post_id, 'work_mode');
    $deadline = aitomic_social_deadline_label($post_id);
    $url = get_permalink($post_id);
    $short_url = home_url('/?p=' . $post_id);
    $source_url = aitomic_social_meta($post_id, 'source_link') ?: aitomic_social_meta($post_id, 'source_url');
    $hashtags = aitomic_social_hashtags($type, $country);
    $base = trim($title . ' - ' . $organization);
    $context = trim(implode(' | ', array_filter([$type, $country, $work_mode, $deadline])));

    $linkedin = aitomic_social_trim(
        "Opportunity alert: {$base}\n\n{$context}\n\nSee the structured details and application link on Aitomic Jobs:\n{$url}\n\n{$hashtags}",
        1200
    );
    $facebook = aitomic_social_trim(
        "New opportunity on Aitomic Jobs: {$base}.\n\n{$context}\n\nDetails and application link: {$url}\n\n{$hashtags}",
        900
    );
    $short_context = aitomic_social_trim($context, 90);
    $fixed_x = "\n{$short_context}\n{$short_url}\n{$hashtags}";
    $base_limit = max(40, 275 - strlen($fixed_x));
    $short_base = aitomic_social_trim($base, $base_limit);
    $x = aitomic_social_trim("{$short_base}{$fixed_x}", 275);
    $whatsapp = aitomic_social_trim(
        "*Opportunity:* {$base}\n{$context}\nDetails: {$url}",
        700
    );

    return [
        'post_id' => $post_id,
        'title' => $title,
        'organization' => $organization,
        'opportunity_type' => $type,
        'country' => $country,
        'work_mode' => $work_mode,
        'deadline' => aitomic_social_meta($post_id, 'deadline'),
        'url' => $url,
        'short_url' => $short_url,
        'source_url' => $source_url,
        'linkedin_text' => $linkedin,
        'facebook_text' => $facebook,
        'x_text' => $x,
        'whatsapp_telegram_text' => $whatsapp,
        'status' => 'Ready',
        'notes' => '',
    ];
}

$limit = max(1, min(300, (int) aitomic_social_arg($args ?? [], 'limit', '50')));
$offset = max(0, (int) aitomic_social_arg($args ?? [], 'offset', '0'));
$mark = aitomic_social_arg($args ?? [], 'mark', 'no') === 'yes';
$include_expired = aitomic_social_arg($args ?? [], 'include_expired', 'no') === 'yes';

$query = new WP_Query([
    'post_type' => 'opportunity',
    'post_status' => 'publish',
    'posts_per_page' => $limit,
    'offset' => $offset,
    'orderby' => 'date',
    'order' => 'DESC',
    'fields' => 'ids',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => '_go_social_queued_at',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key' => '_go_social_queued_at',
            'value' => '',
            'compare' => '=',
        ],
    ],
]);

$rows = [];
foreach ($query->posts as $post_id) {
    if (!$include_expired && aitomic_social_is_expired((int) $post_id)) {
        continue;
    }
    $rows[] = aitomic_social_record((int) $post_id);
    if ($mark) {
        update_post_meta((int) $post_id, '_go_social_queued_at', current_time('mysql'));
    }
}

$upload_dir = wp_upload_dir();
$stamp = current_time('Y-m-d-His');
$csv_file = trailingslashit($upload_dir['basedir']) . "aitomic-social-posting-queue-{$stamp}.csv";
$json_file = trailingslashit($upload_dir['basedir']) . "aitomic-social-posting-queue-{$stamp}.json";

$headers = [
    'post_id',
    'title',
    'organization',
    'opportunity_type',
    'country',
    'work_mode',
    'deadline',
    'url',
    'short_url',
    'source_url',
    'linkedin_text',
    'facebook_text',
    'x_text',
    'whatsapp_telegram_text',
    'status',
    'notes',
];

$handle = fopen($csv_file, 'w');
fputcsv($handle, $headers);
foreach ($rows as $row) {
    fputcsv($handle, array_map(fn($key) => $row[$key] ?? '', $headers));
}
fclose($handle);

file_put_contents($json_file, wp_json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo wp_json_encode([
    'generated' => count($rows),
    'csv' => str_replace(ABSPATH, home_url('/'), $csv_file),
    'json' => str_replace(ABSPATH, home_url('/'), $json_file),
    'marked_as_queued' => $mark,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
