<?php
/**
 * Rebuild thin opportunity posts from their source pages.
 *
 * Usage:
 * wp eval-file tools/enrich_thin_opportunities_from_sources.php -- --limit=25
 * wp eval-file tools/enrich_thin_opportunities_from_sources.php -- --post-id=3162
 */

$raw_args = $args ?? array_slice($argv, 1);
$opts = [
    'limit' => 25,
    'post-id' => 0,
    'dry-run' => false,
];

foreach ($raw_args as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $opts['limit'] = max(1, (int) substr($arg, 8));
    } elseif (strpos($arg, '--post-id=') === 0) {
        $opts['post-id'] = max(0, (int) substr($arg, 10));
    } elseif ($arg === '--dry-run') {
        $opts['dry-run'] = true;
    } elseif ($arg === 'dry-run') {
        $opts['dry-run'] = true;
    } elseif (ctype_digit((string) $arg)) {
        $opts['post-id'] = max(0, (int) $arg);
    }
}

function aitomic_thin_needles(): array
{
    return [
        'This is an official',
        'Review the official source page',
        'Review the official opportunity page',
        'Eligibility requirements are set',
        'Contract terms, salary, fees',
        'Use the application button on this page',
    ];
}

function aitomic_is_thin_post(WP_Post $post): bool
{
    foreach (aitomic_thin_needles() as $needle) {
        if (stripos($post->post_content, $needle) !== false) {
            return true;
        }
    }
    return false;
}

function aitomic_meta(int $post_id, string $key): string
{
    $value = get_post_meta($post_id, '_go_' . $key, true);
    if ($value === '' && function_exists('go_get_meta')) {
        $value = go_get_meta($post_id, $key);
    }
    return trim((string) $value);
}

function aitomic_fetch_source_text(string $url): array
{
    $response = wp_remote_get($url, [
        'timeout' => 20,
        'redirection' => 5,
        'headers' => [
            'User-Agent' => 'AitomicJobsBot/1.0 (+https://aitomic.net)',
            'Accept' => 'text/html,application/xhtml+xml',
        ],
    ]);

    if (is_wp_error($response)) {
        return ['ok' => false, 'error' => $response->get_error_message(), 'title' => '', 'chunks' => []];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $html = (string) wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300 || trim($html) === '') {
        return ['ok' => false, 'error' => 'HTTP ' . $code, 'title' => '', 'chunks' => []];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//script|//style|//noscript|//svg|//form|//nav|//header|//footer') as $node) {
        $node->parentNode?->removeChild($node);
    }

    $title = '';
    foreach (['//h1', '//title'] as $query) {
        $node = $xpath->query($query)->item(0);
        if ($node) {
            $title = aitomic_clean_text($node->textContent);
            if ($title !== '') {
                break;
            }
        }
    }

    $roots = $xpath->query('//main|//*[@role="main"]|//article|//*[contains(@class,"content")]|//body');
    $root = $roots->item(0) ?: $dom->documentElement;
    $chunks = [];

    foreach ((new DOMXPath($dom))->query('.//h1|.//h2|.//h3|.//h4|.//p|.//li', $root) as $node) {
        $text = aitomic_clean_text($node->textContent);
        if ($text === '' || strlen($text) < 24 || aitomic_is_noise_text($text)) {
            continue;
        }
        $tag = strtolower($node->nodeName);
        $chunks[] = [
            'tag' => $tag,
            'text' => $text,
        ];
        if (count($chunks) >= 260) {
            break;
        }
    }

    return ['ok' => true, 'error' => '', 'title' => $title, 'chunks' => $chunks];
}

function aitomic_clean_text(string $text): string
{
    $text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
}

function aitomic_is_noise_text(string $text): bool
{
    $lower = strtolower($text);
    if (preg_match('/^[A-Za-z][A-Za-z \/-]{2,48}:$/', trim($text))) {
        return true;
    }
    $noise = [
        'skip to main content',
        'privacy policy',
        'cookie',
        'all rights reserved',
        'share this',
        'follow us',
        'sign in',
        'subscribe',
        'search',
        'menu',
        'captcha',
    ];
    foreach ($noise as $needle) {
        if (str_contains($lower, $needle)) {
            return true;
        }
    }
    return false;
}

function aitomic_words_for_matching(string $title): array
{
    $words = preg_split('/[^a-z0-9]+/i', strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
    $stop = ['and', 'the', 'for', 'with', 'from', '2026', '2025', 'officer', 'manager', 'assistant'];
    $words = array_filter($words, fn($w) => strlen($w) > 3 && !in_array($w, $stop, true));
    return array_values(array_unique($words));
}

function aitomic_rank_chunks(array $chunks, string $title, string $type): array
{
    $words = aitomic_words_for_matching($title . ' ' . $type);
    foreach ($chunks as &$chunk) {
        $lower = strtolower($chunk['text']);
        $score = 0;
        foreach ($words as $word) {
            if (str_contains($lower, $word)) {
                $score += 3;
            }
        }
        if (in_array($chunk['tag'], ['h1', 'h2', 'h3'], true)) {
            $score += 2;
        }
        if (preg_match('/\b(responsib|duties|role|eligib|require|qualif|benefit|salary|allowance|apply|application|deadline|volunteer|intern|consult|tender)\b/i', $chunk['text'])) {
            $score += 2;
        }
        $chunk['score'] = $score;
    }
    unset($chunk);
    usort($chunks, fn($a, $b) => $b['score'] <=> $a['score']);
    return $chunks;
}

function aitomic_pick_paragraphs(array $chunks, string $title, string $type, int $max = 3): array
{
    $ranked = aitomic_rank_chunks(array_filter($chunks, fn($c) => $c['tag'] === 'p'), $title, $type);
    $picked = [];
    foreach ($ranked as $chunk) {
        $text = $chunk['text'];
        if (strlen($text) > 520) {
            $text = preg_replace('/^(.{240,520}?[.!?])\s.*/u', '$1', $text);
        }
        if (preg_match('/^[A-Z][A-Za-z &\/-]{2,32}:\s/u', $text)) {
            continue;
        }
        if (preg_match('/\b(apply|application|resume|cover letter|only those selected|instructions to upload|candidate profile|third party vendors|ability to|masters degree|master.s degree|ph\.?d|background check|priority consideration|conviction history)\b/i', $text)) {
            continue;
        }
        if (!in_array($text, $picked, true)) {
            $picked[] = $text;
        }
        if (count($picked) >= $max) {
            break;
        }
    }
    return $picked;
}

function aitomic_pick_section_items(array $chunks, array $keywords, int $max = 5): array
{
    $items = [];
    $active = false;
    foreach ($chunks as $chunk) {
        $text = $chunk['text'];
        $lower = strtolower($text);
        if (in_array($chunk['tag'], ['h1', 'h2', 'h3', 'h4'], true)) {
            $active = false;
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $active = true;
                    break;
                }
            }
            continue;
        }
        $matches_keyword = false;
        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                $matches_keyword = true;
                break;
            }
        }
        if (!$active && !$matches_keyword) {
            continue;
        }
        if (strlen($text) < 28 || strlen($text) > 360 || preg_match('/^[A-Za-z][A-Za-z \/-]{2,48}:$/', $text) || preg_match('/\b(background check|conviction history|priority consideration|apply by)\b/i', $text)) {
            continue;
        }
        if (!in_array($text, $items, true)) {
            $items[] = $text;
        }
        if (count($items) >= $max) {
            break;
        }
    }
    return $items;
}

function aitomic_filter_items(array $items, array $deny_keywords = [], array $allow_keywords = []): array
{
    $filtered = [];
    $deny_keywords = array_values(array_filter($deny_keywords, fn($keyword) => trim((string) $keyword) !== ''));
    foreach ($items as $item) {
        $lower = strtolower($item);
        $denied = false;
        foreach ($deny_keywords as $keyword) {
            $keyword = strtolower((string) $keyword);
            if (str_contains($lower, $keyword) || str_contains($keyword, substr($lower, 0, min(110, strlen($lower))))) {
                $denied = true;
                break;
            }
        }
        if ($denied) {
            continue;
        }

        if ($allow_keywords) {
            $allowed = false;
            foreach ($allow_keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                continue;
            }
        }

        $filtered[] = $item;
    }

    return array_values($filtered);
}

function aitomic_pick_items_by_heading(array $chunks, array $heading_keywords, int $max = 5): array
{
    $items = [];
    $active = false;

    foreach ($chunks as $chunk) {
        $text = $chunk['text'];
        $lower = strtolower($text);

        if (in_array($chunk['tag'], ['h1', 'h2', 'h3', 'h4'], true)) {
            $active = false;
            foreach ($heading_keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $active = true;
                    break;
                }
            }
            continue;
        }

        if (!$active || strlen($text) < 28 || strlen($text) > 420 || preg_match('/^[A-Za-z][A-Za-z \/-]{2,48}:$/', $text) || preg_match('/\b(background check|conviction history|priority consideration|apply by)\b/i', $text)) {
            continue;
        }

        if (!in_array($text, $items, true)) {
            $items[] = $text;
        }

        if (count($items) >= $max) {
            break;
        }
    }

    return $items;
}

function aitomic_fallback_items(string $type, string $category): array
{
    $lower = strtolower($type);
    if (str_contains($lower, 'volunteer')) {
        return [
            'Support the programme or project activities described by the source organization.',
            'Participate in scheduled activities, training, events, outreach or field-based work where required.',
            'Work with staff, participants, students, communities or partners in a professional and reliable manner.',
        ];
    }
    if (str_contains($lower, 'intern') || str_contains($lower, 'training')) {
        return [
            'Support assigned project, research, technical, administrative or communications activities.',
            'Participate in supervised learning, team meetings and assigned deliverables.',
            'Prepare notes, analysis, datasets, briefs, presentations or other outputs requested by the host team.',
        ];
    }
    if (str_contains($lower, 'consult') || str_contains($lower, 'tender')) {
        return [
            'Prepare a responsive technical, financial or procurement submission based on the source documents.',
            'Deliver the services, goods, research, analysis or advisory outputs requested by the contracting organization.',
            'Coordinate with the client team on timelines, reporting, quality assurance and final deliverables.',
        ];
    }
    return [
        'Deliver the technical, programme, research, administrative or operational work described by the source.',
        'Coordinate with internal teams, partners and stakeholders to complete assigned outputs.',
        'Prepare documentation, analysis, reports, systems or communications required by the role.',
    ];
}

function aitomic_fallback_requirements(string $type): array
{
    $lower = strtolower($type);
    if (str_contains($lower, 'volunteer')) {
        return [
            'Meet the age, availability, location and participation requirements stated by the source organization.',
            'Complete any application, interview, training, background check or onboarding steps required by the source.',
            'Be able to participate reliably in the stated work mode and schedule.',
        ];
    }
    if (str_contains($lower, 'intern') || str_contains($lower, 'training')) {
        return [
            'Meet the student, graduate, early-career or participant eligibility rules stated by the source.',
            'Have interest, coursework, experience or skills relevant to the opportunity area.',
            'Submit the required application materials before the stated deadline.',
        ];
    }
    return [
        'Meet the qualifications, experience and documentation requirements stated by the source organization.',
        'Have relevant skills or professional background for the role or assignment.',
        'Be able to work in the stated country, location or work mode and meet application deadlines.',
    ];
}

function aitomic_build_content(int $post_id, array $source): string
{
    $title = get_the_title($post_id);
    $organization = aitomic_meta($post_id, 'organization');
    $country = aitomic_meta($post_id, 'country');
    $location = aitomic_meta($post_id, 'location') ?: aitomic_meta($post_id, 'city');
    $work_mode = aitomic_meta($post_id, 'work_mode') ?: aitomic_meta($post_id, 'remote_option');
    $type = aitomic_meta($post_id, 'opportunity_type') ?: aitomic_meta($post_id, 'employment_type');
    $category = aitomic_meta($post_id, 'category');
    $compensation = aitomic_meta($post_id, 'compensation') ?: aitomic_meta($post_id, 'salary') ?: 'Not specified';
    $deadline = aitomic_meta($post_id, 'deadline') ?: 'Not specified by source';
    $source_url = aitomic_meta($post_id, 'source_url') ?: aitomic_meta($post_id, 'application_link');
    $source_name = aitomic_meta($post_id, 'source') ?: $organization;

    $description = aitomic_pick_paragraphs($source['chunks'], $title, $type, 3);
    if (!$description) {
        $description = [
            sprintf('%s is offering %s in %s. The listing is categorized as %s and is connected to %s.', $organization, $title, $country ?: 'the stated location', $type ?: 'an opportunity', $category ?: 'the stated sector'),
        ];
    }

    $responsibilities = aitomic_pick_items_by_heading($source['chunks'], ['responsib', 'duties', 'what you', 'what you will', 'key tasks', 'scope of work', 'role'], 6);
    if (!$responsibilities) {
        $responsibilities = aitomic_pick_section_items($source['chunks'], ['responsib', 'duties', 'what you', 'tasks', 'volunteer with', 'work directly', 'lead or assist'], 5);
    }
    if (!$responsibilities) {
        $responsibilities = aitomic_fallback_items($type, $category);
    }

    $requirements = aitomic_pick_items_by_heading($source['chunks'], ['qualification', 'requirements', 'eligib', 'criteria', 'who you are', 'about you', 'profile', 'experience'], 6);
    $requirements = aitomic_filter_items($requirements, [$description[0] ?? '']);
    if (!$requirements) {
        $requirements = aitomic_fallback_requirements($type);
    }

    $benefits = aitomic_pick_items_by_heading($source['chunks'], ['benefit', 'compensation', 'salary', 'allowance', 'what we offer', 'remuneration'], 5);
    $benefits = aitomic_filter_items(
        $benefits,
        ['apply', 'application', 'resume', 'cover letter', 'equal-opportunity', 'equal opportunity', 'only those selected', 'sponsor work permits'],
        ['benefit', 'salary', 'compensation', 'allowance', 'insurance', 'leave', 'pension', 'stipend', 'discount', 'training', 'paid', 'remuneration']
    );
    if (!$benefits && strtolower($compensation) !== 'not specified') {
        $benefits = [$compensation];
    }

    $apply = aitomic_pick_items_by_heading($source['chunks'], ['how to apply', 'apply', 'application process', 'submission', 'to apply'], 4);
    if (!$apply) {
        $apply = aitomic_pick_section_items($source['chunks'], ['apply with', 'please apply', 'submit', 'application', 'cover letter', 'resume', 'cv'], 3);
    }
    if (!$apply) {
        $apply = ['Use the Apply now button to open the source page and follow the application process provided by the source organization.'];
    }

    $summary = $description[0];
    $details = [
        'Position / opportunity' => $title,
        'Organization' => $organization,
        'Country / coverage' => $country,
        'Location' => $location,
        'Work arrangement' => $work_mode,
        'Opportunity type' => $type,
        'Sector' => $category,
        'Compensation' => $compensation,
        'Application deadline' => $deadline,
    ];

    $html = '<h2>Short Summary</h2><p>' . esc_html($summary) . '</p>';
    $html .= '<h2>Key Details</h2><ul>';
    foreach ($details as $label => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $html .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    $html .= '</ul>';
    $html .= '<h2>Description</h2>';
    foreach ($description as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '<h2>Responsibilities</h2>' . aitomic_list_html($responsibilities);
    $html .= '<h2>Requirements / Eligibility</h2>' . aitomic_list_html($requirements);
    if ($benefits) {
        $html .= '<h2>Benefits / Compensation</h2>' . aitomic_list_html($benefits);
    }
    $html .= '<h2>How To Apply</h2>' . aitomic_list_html($apply);
    $html .= '<h2>Source</h2><p>Source: <a href="' . esc_url($source_url) . '">' . esc_html($source_name) . '</a>. Details were rebuilt from the source page during the Aitomic Jobs cleanup pass.</p>';
    return $html;
}

function aitomic_list_html(array $items): string
{
    $html = '<ul>';
    foreach ($items as $item) {
        $html .= '<li>' . esc_html($item) . '</li>';
    }
    return $html . '</ul>';
}

$query_args = [
    'post_type' => 'opportunity',
    'post_status' => 'publish',
    'posts_per_page' => $opts['post-id'] ? 1 : $opts['limit'],
    'orderby' => 'ID',
    'order' => 'DESC',
];

if ($opts['post-id']) {
    $query_args['p'] = $opts['post-id'];
} else {
    $query_args['s'] = 'Review the official source page';
    $query_args['meta_query'] = [
        'relation' => 'OR',
        [
            'key' => '_go_enrichment_status',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key' => '_go_enrichment_status',
            'value' => 'source-fetch-failed',
            'compare' => 'NOT LIKE',
        ],
    ];
}

$query = new WP_Query($query_args);
$updated = 0;
$skipped = 0;
$failed = 0;
$results = [];

foreach ($query->posts as $post) {
    if (!$opts['post-id'] && !aitomic_is_thin_post($post)) {
        $skipped++;
        continue;
    }
    $source_url = aitomic_meta($post->ID, 'source_url') ?: aitomic_meta($post->ID, 'application_link');
    if (!$source_url || !filter_var($source_url, FILTER_VALIDATE_URL)) {
        $skipped++;
        $results[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'skipped-no-source'];
        continue;
    }

    $source = aitomic_fetch_source_text($source_url);
    if (!$source['ok'] || count($source['chunks']) < 3) {
        $failed++;
        update_post_meta($post->ID, '_go_enrichment_status', 'source-fetch-failed: ' . ($source['error'] ?? 'empty-source'));
        $results[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'failed', 'error' => $source['error']];
        continue;
    }

    $content = aitomic_build_content($post->ID, $source);
    if (!$opts['dry-run']) {
        $result = wp_update_post([
            'ID' => $post->ID,
            'post_content' => $content,
            'post_excerpt' => wp_trim_words(wp_strip_all_tags($content), 42),
        ], true);

        if (is_wp_error($result)) {
            $failed++;
            $results[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'failed', 'error' => $result->get_error_message()];
            continue;
        }

        update_post_meta($post->ID, '_go_enrichment_status', 'source-enriched');
        update_post_meta($post->ID, '_go_enriched_at', gmdate('c'));
        update_post_meta($post->ID, '_go_enriched_source_title', $source['title']);
    }
    $updated++;
    $results[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => $opts['dry-run'] ? 'would-update' : 'updated', 'chunks' => count($source['chunks'])];
}

echo wp_json_encode([
    'updated' => $updated,
    'skipped' => $skipped,
    'failed' => $failed,
    'dry_run' => $opts['dry-run'],
    'results' => $results,
], JSON_PRETTY_PRINT) . "\n";
