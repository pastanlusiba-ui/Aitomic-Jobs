<?php
/**
 * Publish due Aitomic Jobs opportunities to LinkedIn.
 *
 * Usage:
 * wp eval-file /home/u710255073/aitomic-tools/run_linkedin_scheduler.php limit=4
 * wp eval-file tools/run_linkedin_scheduler.php limit=4 dry_run=yes now="2026-07-20 07:00 Africa/Kampala"
 */

if (!defined('ABSPATH')) {
    exit;
}

function aitomic_linkedin_scheduler_arg(array $args, string $key, string $default = ''): string
{
    foreach ($args as $arg) {
        if (str_starts_with((string) $arg, $key . '=')) {
            return substr((string) $arg, strlen($key) + 1);
        }
    }

    return $default;
}

function aitomic_linkedin_scheduler_timezone(string $timezone_arg): DateTimeZone
{
    try {
        return new DateTimeZone($timezone_arg ?: 'Africa/Kampala');
    } catch (Exception $e) {
        return new DateTimeZone('Africa/Kampala');
    }
}

function aitomic_linkedin_scheduler_date(string $value, DateTimeZone $timezone): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value, $timezone);
    } catch (Exception $e) {
        return null;
    }
}

function aitomic_linkedin_scheduler_meta(int $post_id, string $key): string
{
    return trim((string) get_post_meta($post_id, '_go_' . $key, true));
}

function aitomic_linkedin_scheduler_term_list(int $post_id, string $taxonomy): string
{
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function aitomic_linkedin_scheduler_trim(string $text, int $limit): string
{
    $text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', (string) $text);
    $text = preg_replace("/\n{3,}/", "\n\n", (string) $text);
    $text = trim((string) $text);

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

function aitomic_linkedin_scheduler_content_detail(int $post_id, string $label): string
{
    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return '';
    }

    $pattern = '/<li>\s*<strong>\s*' . preg_quote($label, '/') . '\s*:\s*<\/strong>\s*(.*?)<\/li>/is';
    if (!preg_match($pattern, $content, $matches)) {
        return '';
    }

    return aitomic_linkedin_scheduler_trim($matches[1], 260);
}

function aitomic_linkedin_scheduler_deadline_label(int $post_id): string
{
    $deadline = aitomic_linkedin_scheduler_meta($post_id, 'deadline')
        ?: aitomic_linkedin_scheduler_content_detail($post_id, 'Application deadline');

    if ($deadline === '') {
        return 'Not specified';
    }

    $timestamp = strtotime($deadline);
    if (!$timestamp) {
        return $deadline;
    }

    return date_i18n('M j, Y', $timestamp);
}

function aitomic_linkedin_scheduler_is_expired(int $post_id): bool
{
    $deadline = aitomic_linkedin_scheduler_meta($post_id, 'deadline');
    if ($deadline === '') {
        return false;
    }

    $timestamp = strtotime($deadline . ' 23:59:59');

    return $timestamp && $timestamp < current_time('timestamp');
}

function aitomic_linkedin_scheduler_is_clean(int $post_id): bool
{
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

function aitomic_linkedin_scheduler_hashtags(string $type, string $category, string $country): string
{
    $tags = ['#AitomicJobs', '#Opportunities', '#CareerOpportunity'];
    $type_lower = strtolower($type);
    $category_lower = strtolower($category);

    if (str_contains($type_lower, 'intern')) {
        $tags = array_merge($tags, ['#Internship', '#Students', '#YoungProfessionals']);
    } elseif (str_contains($type_lower, 'tender') || str_contains($type_lower, 'consult')) {
        $tags = array_merge($tags, ['#Consultancies', '#Procurement']);
    } elseif (str_contains($type_lower, 'remote')) {
        $tags[] = '#RemoteJobs';
    } elseif (str_contains($type_lower, 'volunteer')) {
        $tags[] = '#VolunteerOpportunities';
    } elseif (str_contains($type_lower, 'training') || str_contains($type_lower, 'course')) {
        $tags = array_merge($tags, ['#Training', '#ShortCourses']);
    } else {
        $tags = array_merge($tags, ['#Jobs', '#Hiring']);
    }

    if (str_contains($category_lower, 'health')) {
        $tags[] = '#GlobalHealth';
    } elseif (str_contains($category_lower, 'education')) {
        $tags[] = '#Education';
    } elseif (str_contains($category_lower, 'agriculture')) {
        $tags[] = '#Agriculture';
    } elseif (str_contains($category_lower, 'development') || str_contains($category_lower, 'humanitarian')) {
        $tags[] = '#InternationalDevelopment';
    } elseif (str_contains($category_lower, 'technology') || str_contains($category_lower, 'information')) {
        $tags[] = '#Technology';
    }

    $country_tag = preg_replace('/[^A-Za-z0-9]/', '', $country);
    if ($country_tag !== '' && !in_array(strtolower($country), ['global/international', 'global', 'international', 'remote', 'various', 'multiple'], true)) {
        $tags[] = '#' . $country_tag;
    }

    return implode(' ', array_slice(array_unique($tags), 0, 12));
}

function aitomic_linkedin_scheduler_message(int $post_id): string
{
    $title = html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $organization = html_entity_decode(aitomic_linkedin_scheduler_meta($post_id, 'organization'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $type = aitomic_linkedin_scheduler_term_list($post_id, 'opportunity_type') ?: aitomic_linkedin_scheduler_meta($post_id, 'employment_type');
    $category = aitomic_linkedin_scheduler_term_list($post_id, 'opportunity_category');
    $country = aitomic_linkedin_scheduler_term_list($post_id, 'country') ?: aitomic_linkedin_scheduler_meta($post_id, 'country');
    $work_mode = aitomic_linkedin_scheduler_term_list($post_id, 'work_mode') ?: aitomic_linkedin_scheduler_meta($post_id, 'work_mode');
    $location = aitomic_linkedin_scheduler_meta($post_id, 'city');
    $duration = aitomic_linkedin_scheduler_meta($post_id, 'duration');
    $start_date = aitomic_linkedin_scheduler_meta($post_id, 'start_date');
    $compensation = aitomic_linkedin_scheduler_meta($post_id, 'salary') ?: aitomic_linkedin_scheduler_content_detail($post_id, 'Compensation');
    $eligibility = aitomic_linkedin_scheduler_trim(aitomic_linkedin_scheduler_meta($post_id, 'eligibility'), 360);
    $summary = get_the_excerpt($post_id) ?: (string) get_post_field('post_content', $post_id);
    $summary = aitomic_linkedin_scheduler_trim($summary, 430);
    $url = get_permalink($post_id);
    $location_bits = array_filter([$location, $country]);

    $detail_lines = array_filter([
        $organization !== '' ? 'Organization: ' . $organization : '',
        $type !== '' ? 'Opportunity type: ' . $type : '',
        $category !== '' ? 'Sector: ' . $category : '',
        !empty($location_bits) ? 'Location: ' . implode(', ', $location_bits) : '',
        $work_mode !== '' ? 'Work mode: ' . $work_mode : '',
        'Deadline: ' . aitomic_linkedin_scheduler_deadline_label($post_id),
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
    $parts[] = aitomic_linkedin_scheduler_hashtags($type, $category, $country);

    return aitomic_linkedin_scheduler_trim(implode("\n\n", array_filter($parts)), 2900);
}

function aitomic_linkedin_scheduler_sent_count(int $post_id): int
{
    $sent = get_post_meta($post_id, '_sent_to_linkedin', true);

    return is_array($sent) ? count($sent) : 0;
}

$raw_args = $args ?? array_slice($argv, 1);
$limit = max(1, min(10, (int) aitomic_linkedin_scheduler_arg($raw_args, 'limit', '4')));
$candidate_limit = max($limit * 8, (int) aitomic_linkedin_scheduler_arg($raw_args, 'candidate_limit', '80'));
$dry_run = aitomic_linkedin_scheduler_arg($raw_args, 'dry_run', 'no') === 'yes';
$include_expired = aitomic_linkedin_scheduler_arg($raw_args, 'include_expired', 'no') === 'yes';
$timezone = aitomic_linkedin_scheduler_timezone(aitomic_linkedin_scheduler_arg($raw_args, 'timezone', 'Africa/Kampala'));
$now_arg = aitomic_linkedin_scheduler_arg($raw_args, 'now', '');
$now = $now_arg !== ''
    ? aitomic_linkedin_scheduler_date($now_arg, $timezone)
    : new DateTimeImmutable('now', $timezone);

if (!$now) {
    $now = new DateTimeImmutable('now', $timezone);
}

$lock_key = 'aitomic_linkedin_scheduler_lock';
if (!$dry_run && get_transient($lock_key)) {
    echo wp_json_encode([
        'status' => 'locked',
        'message' => 'Another scheduler run is already active.',
        'published' => 0,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    return;
}

if (!$dry_run) {
    set_transient($lock_key, 1, 4 * MINUTE_IN_SECONDS);
}

$results = [];
$published = 0;
$due = 0;

try {
    if (!function_exists('wp_linkedin_autopublish_post_to_linkedin_common')) {
        throw new RuntimeException('WP LinkedIn Auto Publish plugin function is not available.');
    }

    $query = new WP_Query([
        'post_type' => 'opportunity',
        'post_status' => 'publish',
        'posts_per_page' => min(300, $candidate_limit),
        'orderby' => 'meta_value',
        'meta_key' => '_go_linkedin_scheduled_for',
        'order' => 'ASC',
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_go_linkedin_queued_at',
                'compare' => 'EXISTS',
            ],
            [
                'key' => '_go_linkedin_scheduled_for',
                'compare' => 'EXISTS',
            ],
            [
                'relation' => 'OR',
                [
                    'key' => '_go_linkedin_posted_at',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_go_linkedin_posted_at',
                    'value' => '',
                    'compare' => '=',
                ],
            ],
        ],
    ]);

    foreach ($query->posts as $post_id) {
        $post_id = (int) $post_id;
        $scheduled_raw = (string) get_post_meta($post_id, '_go_linkedin_scheduled_for', true);
        $scheduled = aitomic_linkedin_scheduler_date($scheduled_raw, $timezone);

        if (!$scheduled || $scheduled->getTimestamp() > $now->getTimestamp()) {
            continue;
        }

        $due++;
        $title = html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (aitomic_linkedin_scheduler_sent_count($post_id) > 0) {
            if (!$dry_run) {
                update_post_meta($post_id, '_go_linkedin_posted_at', current_time('mysql'));
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'already-posted');
            }
            $results[] = [
                'post_id' => $post_id,
                'title' => $title,
                'status' => 'already-posted',
                'scheduled_for' => $scheduled_raw,
            ];
            continue;
        }

        if (!$include_expired && aitomic_linkedin_scheduler_is_expired($post_id)) {
            if (!$dry_run) {
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'skipped-expired');
            }
            $results[] = [
                'post_id' => $post_id,
                'title' => $title,
                'status' => 'skipped-expired',
                'scheduled_for' => $scheduled_raw,
            ];
            continue;
        }

        if (!aitomic_linkedin_scheduler_is_clean($post_id)) {
            if (!$dry_run) {
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'skipped-thin-content');
            }
            $results[] = [
                'post_id' => $post_id,
                'title' => $title,
                'status' => 'skipped-thin-content',
                'scheduled_for' => $scheduled_raw,
            ];
            continue;
        }

        $message = aitomic_linkedin_scheduler_message($post_id);

        if ($dry_run) {
            $results[] = [
                'post_id' => $post_id,
                'title' => $title,
                'status' => 'due-dry-run',
                'scheduled_for' => $scheduled_raw,
                'message_preview' => aitomic_linkedin_scheduler_trim($message, 260),
            ];
        } else {
            update_post_meta($post_id, '_custom_linkedin_share_message', $message);
            update_post_meta($post_id, '_go_linkedin_scheduler_status', 'posting');
            update_post_meta($post_id, '_go_linkedin_last_attempt_at', current_time('mysql'));

            $before_count = aitomic_linkedin_scheduler_sent_count($post_id);
            $plugin_result = wp_linkedin_autopublish_post_to_linkedin_common($post_id);
            $after_count = aitomic_linkedin_scheduler_sent_count($post_id);

            if ($after_count > $before_count) {
                $published++;
                update_post_meta($post_id, '_go_linkedin_posted_at', current_time('mysql'));
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'posted');
                delete_post_meta($post_id, '_go_linkedin_last_error');
                $status = 'posted';
            } else {
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'failed');
                update_post_meta($post_id, '_go_linkedin_last_error', (string) $plugin_result);
                $status = 'failed';
            }

            $results[] = [
                'post_id' => $post_id,
                'title' => $title,
                'status' => $status,
                'scheduled_for' => $scheduled_raw,
                'plugin_result' => (string) $plugin_result,
            ];
        }

        if (count(array_filter($results, fn($row) => in_array($row['status'], ['due-dry-run', 'posted', 'failed'], true))) >= $limit) {
            break;
        }
    }

    echo wp_json_encode([
        'status' => 'ok',
        'dry_run' => $dry_run,
        'now' => $now->format('Y-m-d H:i T'),
        'limit' => $limit,
        'due_seen' => $due,
        'published' => $published,
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo wp_json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'published' => $published,
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} finally {
    if (!$dry_run) {
        delete_transient($lock_key);
    }
}
