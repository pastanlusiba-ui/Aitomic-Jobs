<?php
/**
 * Clear weak application links so Apply now only points to exact opportunity pages.
 *
 * Usage:
 * wp eval-file /home/u710255073/aitomic-tools/clean_non_exact_application_links.php
 * wp eval-file tools/clean_non_exact_application_links.php -- --dry-run=yes --limit=50
 */

$raw_args = [];
if (isset($args) && is_array($args)) {
    $raw_args = $args;
} elseif (isset($argv) && is_array($argv)) {
    $raw_args = array_slice($argv, 1);
}

$args_map = [];
foreach ($raw_args as $arg) {
    if (str_starts_with($arg, '--')) {
        $arg = substr($arg, 2);
    }
    if (str_contains($arg, '=')) {
        [$key, $value] = explode('=', $arg, 2);
        $args_map[$key] = $value;
    }
}

$dry_run = in_array(strtolower((string) ($args_map['dry-run'] ?? 'no')), ['1', 'yes', 'true'], true);
$limit = isset($args_map['limit']) ? max(1, (int) $args_map['limit']) : -1;

function aitomic_clean_is_exact_opportunity_link(string $url): bool {
    $url = trim($url);
    if ($url === '') {
        return false;
    }

    $parts = wp_parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }

    $path = trim((string) ($parts['path'] ?? ''), '/');
    if ($path === '') {
        return false;
    }

    if (preg_match('/^(jobs?|careers?|vacanc(?:y|ies)|opportunities?|employment|recruitment|join-us|work-with-us|search|apply|procurement|tenders?|listings?)$/i', $path)) {
        return false;
    }

    $has_exact_query_id = !empty($parts['query']) && preg_match('/(job|jobs|career|requisition|req|opening|position|vacancy|id|counter|jncounter|posting|post)=?[^&]*\d{3,}/i', $parts['query']);

    if (preg_match('/(jobsearchresults|jobssearchresults|searchresults)/i', $path) && !$has_exact_query_id) {
        return false;
    }

    if (preg_match('/(^|[\/_.-])(search|results?|jobsearch|jobssearch|job-search|jobs-search)($|[\/_.-])/i', $path) && !preg_match('/\b\d{4,}\b/', $path) && !$has_exact_query_id) {
        return false;
    }

    if (preg_match('/(job|jobs|careers?|career|vacanc|vacancies|position|posting|postings|opportun|apply|requisition|req|opening|details|vacatures|empleos|consult|tender|rfp|procurement|listings?\/.+|\d{4,})/i', $path)) {
        return true;
    }

    if ($has_exact_query_id) {
        return true;
    }

    return false;
}

$ids = get_posts([
    'post_type' => 'opportunity',
    'post_status' => 'any',
    'posts_per_page' => $limit,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'DESC',
]);

$stats = [
    'scanned' => 0,
    'already_exact' => 0,
    'replaced_with_exact_source' => 0,
    'cleared_weak_application_link' => 0,
    'missing_application_link' => 0,
    'dry_run' => $dry_run,
    'samples' => [],
];

foreach ($ids as $post_id) {
    $post_id = (int) $post_id;
    $stats['scanned']++;

    $application_link = trim((string) get_post_meta($post_id, '_go_application_link', true));
    $source_url = trim((string) get_post_meta($post_id, '_go_source_url', true));

    if ($application_link === '') {
        $stats['missing_application_link']++;
        continue;
    }

    if (aitomic_clean_is_exact_opportunity_link($application_link)) {
        update_post_meta($post_id, '_go_application_link_status', 'exact');
        $stats['already_exact']++;
        continue;
    }

    if ($source_url !== '' && $source_url !== $application_link && aitomic_clean_is_exact_opportunity_link($source_url)) {
        if (!$dry_run) {
            update_post_meta($post_id, '_go_application_link', esc_url_raw($source_url));
            update_post_meta($post_id, '_go_application_link_status', 'replaced-with-exact-source');
        }
        $stats['replaced_with_exact_source']++;
        if (count($stats['samples']) < 20) {
            $stats['samples'][] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'old_application_link' => $application_link,
                'new_application_link' => $source_url,
                'action' => 'replaced',
            ];
        }
        continue;
    }

    if (!$dry_run) {
        update_post_meta($post_id, '_go_application_link', '');
        update_post_meta($post_id, '_go_application_link_status', 'needs-exact-link');
    }
    $stats['cleared_weak_application_link']++;
    if (count($stats['samples']) < 20) {
        $stats['samples'][] = [
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'old_application_link' => $application_link,
            'action' => 'cleared',
        ];
    }
}

echo wp_json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
