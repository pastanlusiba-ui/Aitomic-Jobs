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

function aitomic_social_content_detail(int $post_id, string $label): string {
    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return '';
    }
    $pattern = '/<li>\s*<strong>\s*' . preg_quote($label, '/') . '\s*:\s*<\/strong>\s*(.*?)<\/li>/is';
    if (!preg_match($pattern, $content, $matches)) {
        return '';
    }
    return aitomic_social_trim($matches[1], 220);
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
    $deadline = aitomic_social_meta($post_id, 'deadline') ?: aitomic_social_content_detail($post_id, 'Application deadline');
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

function aitomic_social_will_be_expired(int $post_id, string $scheduled_for): bool {
    $deadline = aitomic_social_meta($post_id, 'deadline');
    if ($deadline === '') {
        return false;
    }

    $deadline_timestamp = strtotime($deadline . ' 23:59:59');
    $schedule_timestamp = strtotime($scheduled_for);

    return $deadline_timestamp && $schedule_timestamp && $deadline_timestamp < $schedule_timestamp;
}

function aitomic_social_is_clean(int $post_id): bool {
    $content = (string) get_post_field('post_content', $post_id);
    $bad_needles = [
        'This is an official',
        'Review the official source page',
        'Review the official opportunity page',
        'Eligibility requirements are set',
        'Contract terms, salary, fees',
        'Use the application button on this page',
    ];

    foreach ($bad_needles as $needle) {
        if (stripos($content, $needle) !== false) {
            return false;
        }
    }

    return true;
}

function aitomic_social_schedule_times(string $times_arg): array {
    $times = array_filter(array_map('trim', explode(',', $times_arg)));
    $valid = [];

    foreach ($times as $time) {
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                $valid[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }
    }

    return $valid ?: ['09:00', '13:00', '17:00'];
}

function aitomic_social_interval_times(string $start_arg, string $end_arg, int $interval_minutes): array {
    if (!preg_match('/^\d{1,2}:\d{2}$/', $start_arg) || !preg_match('/^\d{1,2}:\d{2}$/', $end_arg)) {
        return [];
    }

    $start = DateTimeImmutable::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $start_arg);
    $end = DateTimeImmutable::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $end_arg);
    if (!$start || !$end || $end < $start) {
        return [];
    }

    $times = [];
    $cursor = $start;
    $interval_minutes = max(1, min(1440, $interval_minutes));

    while ($cursor <= $end) {
        $times[] = $cursor->format('H:i');
        $cursor = $cursor->modify('+' . $interval_minutes . ' minutes');
    }

    return $times;
}

function aitomic_social_timezone(string $timezone_arg): DateTimeZone {
    try {
        return new DateTimeZone($timezone_arg ?: 'Africa/Kampala');
    } catch (Exception $e) {
        return new DateTimeZone('Africa/Kampala');
    }
}

function aitomic_social_next_schedule_date(DateTimeImmutable $start, int $day_offset, bool $skip_weekends): DateTimeImmutable {
    $date = $start;
    $added = 0;

    while ($added < $day_offset || ($skip_weekends && in_array((int) $date->format('N'), [6, 7], true))) {
        $date = $date->modify('+1 day');
        if (!$skip_weekends || !in_array((int) $date->format('N'), [6, 7], true)) {
            $added++;
        }
    }

    return $date;
}

function aitomic_social_scheduled_for(int $index, DateTimeImmutable $start, array $times, bool $skip_weekends, int $per_slot): string {
    $slot_count = max(1, count($times));
    $slot_index = intdiv($index, max(1, $per_slot));
    $day_offset = intdiv($slot_index, $slot_count);
    $slot = $times[$slot_index % $slot_count];
    $date = aitomic_social_next_schedule_date($start, $day_offset, $skip_weekends);

    return $date->format('Y-m-d') . ' ' . $slot . ' ' . $date->getTimezone()->getName();
}

function aitomic_social_hashtags(string $type, string $country): string {
    $tags = ['#AitomicJobs', '#Opportunities', '#CareerOpportunity'];
    $type_lower = strtolower($type);
    if (str_contains($type_lower, 'intern')) {
        $tags[] = '#Internship';
        $tags[] = '#Students';
        $tags[] = '#YoungProfessionals';
    } elseif (str_contains($type_lower, 'tender') || str_contains($type_lower, 'consult')) {
        $tags[] = '#Consultancies';
        $tags[] = '#Procurement';
    } elseif (str_contains($type_lower, 'remote')) {
        $tags[] = '#RemoteJobs';
    } elseif (str_contains($type_lower, 'volunteer')) {
        $tags[] = '#VolunteerOpportunities';
    } elseif (str_contains($type_lower, 'training') || str_contains($type_lower, 'course')) {
        $tags[] = '#Training';
        $tags[] = '#ShortCourses';
    } else {
        $tags[] = '#Jobs';
        $tags[] = '#Hiring';
    }
    $country_tag = preg_replace('/[^A-Za-z0-9]/', '', $country);
    if ($country_tag !== '' && !in_array(strtolower($country), ['global/international', 'global', 'international', 'remote', 'various', 'multiple'], true)) {
        $tags[] = '#' . $country_tag;
    }
    return implode(' ', array_slice(array_unique($tags), 0, 10));
}

function aitomic_social_category_hashtags(string $category): array {
    $category_lower = strtolower($category);
    if (str_contains($category_lower, 'health')) {
        return ['#GlobalHealth'];
    }
    if (str_contains($category_lower, 'education')) {
        return ['#Education'];
    }
    if (str_contains($category_lower, 'communication')) {
        return ['#Communications'];
    }
    if (str_contains($category_lower, 'agriculture')) {
        return ['#Agriculture'];
    }
    if (str_contains($category_lower, 'humanitarian') || str_contains($category_lower, 'development')) {
        return ['#InternationalDevelopment'];
    }
    if (str_contains($category_lower, 'information') || str_contains($category_lower, 'technology')) {
        return ['#Technology'];
    }
    return [];
}

function aitomic_social_summary(int $post_id): string {
    $excerpt = get_the_excerpt($post_id);
    if ($excerpt !== '') {
        return aitomic_social_trim($excerpt, 430);
    }
    return aitomic_social_trim((string) get_post_field('post_content', $post_id), 430);
}

function aitomic_social_linkedin_text(int $post_id, string $title, string $organization, string $type, string $category, string $country, string $work_mode, string $url): string {
    $location = aitomic_social_meta($post_id, 'city');
    $duration = aitomic_social_meta($post_id, 'duration');
    $start_date = aitomic_social_meta($post_id, 'start_date');
    $compensation = aitomic_social_meta($post_id, 'salary') ?: aitomic_social_content_detail($post_id, 'Compensation');
    $deadline = str_replace('Deadline: ', '', aitomic_social_deadline_label($post_id));
    $eligibility = aitomic_social_trim(aitomic_social_meta($post_id, 'eligibility'), 360);
    $summary = aitomic_social_summary($post_id);
    $focus = aitomic_social_content_detail($post_id, 'Category');
    $location_bits = array_filter([$location, $country]);
    $tags = trim(aitomic_social_hashtags($type, $country) . ' ' . implode(' ', aitomic_social_category_hashtags($category)));

    $detail_lines = array_filter([
        $organization !== '' ? 'Organization: ' . $organization : '',
        $type !== '' ? 'Opportunity type: ' . $type : '',
        $category !== '' ? 'Sector: ' . $category : '',
        $focus !== '' && $focus !== $category ? 'Focus: ' . $focus : '',
        !empty($location_bits) ? 'Location: ' . implode(', ', $location_bits) : '',
        $work_mode !== '' ? 'Work mode: ' . $work_mode : '',
        'Deadline: ' . $deadline,
        $start_date !== '' ? 'Start date: ' . $start_date : '',
        $duration !== '' ? 'Duration: ' . $duration : '',
        $compensation !== '' ? 'Compensation: ' . $compensation : '',
    ]);

    $parts = [
        'Opportunity alert: ' . $title,
        $summary,
        "Key details\n" . implode("\n", $detail_lines),
    ];

    if ($eligibility !== '' && $eligibility !== $summary) {
        $parts[] = "Who should consider this\n" . $eligibility;
    }

    $parts[] = "What to review on Aitomic Jobs\nFull description, responsibilities or submission instructions, eligibility requirements, benefits or compensation notes, and the official source link.";
    $parts[] = "Full details and official application link\n" . $url;
    $parts[] = $tags;

    return aitomic_social_trim(implode("\n\n", array_filter($parts)), 2700);
}

function aitomic_social_record(int $post_id, string $scheduled_for): array {
    $title = html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $organization = html_entity_decode(aitomic_social_meta($post_id, 'organization'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $type = aitomic_social_term_list($post_id, 'opportunity_type') ?: aitomic_social_meta($post_id, 'employment_type');
    $category = aitomic_social_term_list($post_id, 'opportunity_category');
    $country = aitomic_social_term_list($post_id, 'country') ?: aitomic_social_meta($post_id, 'country');
    $work_mode = aitomic_social_term_list($post_id, 'work_mode') ?: aitomic_social_meta($post_id, 'work_mode');
    $url = get_permalink($post_id);
    $source_url = aitomic_social_meta($post_id, 'source_link') ?: aitomic_social_meta($post_id, 'source_url');
    $linkedin = aitomic_social_linkedin_text($post_id, $title, $organization, $type, $category, $country, $work_mode, $url);

    return [
        'post_id' => $post_id,
        'title' => $title,
        'organization' => $organization,
        'opportunity_type' => $type,
        'category' => $category,
        'country' => $country,
        'work_mode' => $work_mode,
        'deadline' => aitomic_social_meta($post_id, 'deadline'),
        'url' => $url,
        'source_url' => $source_url,
        'linkedin_text' => $linkedin,
        'status' => 'Scheduled',
        'scheduled_for' => $scheduled_for,
        'posted_url' => '',
        'notes' => 'Review copy and opportunity status before posting.',
    ];
}

$limit = max(1, min(300, (int) aitomic_social_arg($args ?? [], 'limit', '50')));
$offset = max(0, (int) aitomic_social_arg($args ?? [], 'offset', '0'));
$mark = aitomic_social_arg($args ?? [], 'mark', 'no') === 'yes';
$include_expired = aitomic_social_arg($args ?? [], 'include_expired', 'no') === 'yes';
$include_thin = aitomic_social_arg($args ?? [], 'include_thin', 'no') === 'yes';
$window_start = aitomic_social_arg($args ?? [], 'window_start', '');
$window_end = aitomic_social_arg($args ?? [], 'window_end', '');
$interval_minutes = max(1, min(1440, (int) aitomic_social_arg($args ?? [], 'interval_minutes', '30')));
$times = $window_start !== '' && $window_end !== ''
    ? aitomic_social_interval_times($window_start, $window_end, $interval_minutes)
    : aitomic_social_schedule_times(aitomic_social_arg($args ?? [], 'times', '09:00,13:00,17:00'));
if (!$times) {
    $times = ['09:00', '13:00', '17:00'];
}
$per_slot = max(1, min(50, (int) aitomic_social_arg($args ?? [], 'per_slot', '1')));
$skip_weekends = aitomic_social_arg($args ?? [], 'skip_weekends', 'yes') !== 'no';
$include_queued = aitomic_social_arg($args ?? [], 'include_queued', 'no') === 'yes';
$timezone = aitomic_social_timezone(aitomic_social_arg($args ?? [], 'timezone', 'Africa/Kampala'));
$default_start = current_datetime()->modify('+1 day')->format('Y-m-d');
$schedule_start_arg = aitomic_social_arg($args ?? [], 'schedule_start', $default_start);
$schedule_start = DateTimeImmutable::createFromFormat('Y-m-d', $schedule_start_arg, $timezone) ?: new DateTimeImmutable('+1 day', $timezone);

$query_args = [
    'post_type' => 'opportunity',
    'post_status' => 'publish',
    'posts_per_page' => min(3000, max($limit * 8, $limit)),
    'offset' => $offset,
    'orderby' => 'date',
    'order' => 'DESC',
    'fields' => 'ids',
];

if (!$include_queued) {
    $query_args['meta_query'] = [
        'relation' => 'OR',
        [
            'key' => '_go_linkedin_queued_at',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key' => '_go_linkedin_queued_at',
            'value' => '',
            'compare' => '=',
        ],
    ];
}

$query = new WP_Query($query_args);

$rows = [];
foreach ($query->posts as $post_id) {
    if (!$include_expired && aitomic_social_is_expired((int) $post_id)) {
        continue;
    }
    if (!$include_thin && !aitomic_social_is_clean((int) $post_id)) {
        continue;
    }

    $scheduled_for = aitomic_social_scheduled_for(count($rows), $schedule_start, $times, $skip_weekends, $per_slot);
    if (!$include_expired && aitomic_social_will_be_expired((int) $post_id, $scheduled_for)) {
        continue;
    }
    $rows[] = aitomic_social_record((int) $post_id, $scheduled_for);
    if ($mark) {
        update_post_meta((int) $post_id, '_go_linkedin_queued_at', current_time('mysql'));
        update_post_meta((int) $post_id, '_go_linkedin_scheduled_for', $scheduled_for);
    }
    if (count($rows) >= $limit) {
        break;
    }
}

$upload_dir = wp_upload_dir();
$stamp = current_time('Y-m-d-His');
$csv_file = trailingslashit($upload_dir['basedir']) . "aitomic-linkedin-posting-queue-{$stamp}.csv";
$json_file = trailingslashit($upload_dir['basedir']) . "aitomic-linkedin-posting-queue-{$stamp}.json";

$headers = [
    'post_id',
    'title',
    'organization',
    'opportunity_type',
    'category',
    'country',
    'work_mode',
    'deadline',
    'url',
    'source_url',
    'linkedin_text',
    'status',
    'scheduled_for',
    'posted_url',
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
    'platform' => 'LinkedIn',
    'generated' => count($rows),
    'csv' => str_replace(ABSPATH, home_url('/'), $csv_file),
    'json' => str_replace(ABSPATH, home_url('/'), $json_file),
    'marked_as_linkedin_queued' => $mark,
    'schedule_start' => $schedule_start->format('Y-m-d'),
    'times' => $times,
    'timezone' => $timezone->getName(),
    'per_slot' => $per_slot,
    'window_start' => $window_start,
    'window_end' => $window_end,
    'interval_minutes' => $interval_minutes,
    'skip_weekends' => $skip_weekends,
    'include_thin' => $include_thin,
    'include_queued' => $include_queued,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
