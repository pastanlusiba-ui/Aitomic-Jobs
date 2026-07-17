<?php
$json_file = $args[0] ?? ($argv[1] ?? '');
if (!$json_file || !file_exists($json_file)) {
    fwrite(STDERR, "JSON file not found.\n");
    exit(1);
}

$items = json_decode((string) file_get_contents($json_file), true);
if (!is_array($items)) {
    fwrite(STDERR, "JSON file could not be decoded.\n");
    exit(1);
}

function aitomic_upsert_value(array $item, string $key, string $fallback = ''): string {
    return trim((string) ($item[$key] ?? $fallback));
}

function aitomic_upsert_list(array $item, string $key): array {
    $value = $item[$key] ?? [];
    if (is_string($value)) {
        $value = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|;/', $value)));
    }
    if (!is_array($value)) {
        return [];
    }
    return array_values(array_filter(array_map(fn($row) => trim((string) $row), $value)));
}

function aitomic_upsert_paragraphs(array $item, string $key): string {
    $value = aitomic_upsert_value($item, $key);
    if ($value === '') {
        return '';
    }
    $paragraphs = array_filter(array_map('trim', preg_split('/\r\n|\r|\n{2,}/', $value)));
    return implode('', array_map(fn($p) => '<p>' . esc_html($p) . '</p>', $paragraphs));
}

function aitomic_upsert_list_html(array $rows): string {
    if (!$rows) {
        return '';
    }
    return '<ul>' . implode('', array_map(fn($row) => '<li>' . esc_html($row) . '</li>', $rows)) . '</ul>';
}

function aitomic_upsert_content(array $item): string {
    $title = esc_html(aitomic_upsert_value($item, 'title'));
    $organization = esc_html(aitomic_upsert_value($item, 'organization'));
    $country = esc_html(aitomic_upsert_value($item, 'country'));
    $location = esc_html(aitomic_upsert_value($item, 'location', aitomic_upsert_value($item, 'country')));
    $work_mode = esc_html(aitomic_upsert_value($item, 'work_mode', 'On-site'));
    $type = esc_html(aitomic_upsert_value($item, 'opportunity_type'));
    $category = esc_html(aitomic_upsert_value($item, 'category'));
    $deadline = esc_html(aitomic_upsert_value($item, 'deadline_label', aitomic_upsert_value($item, 'deadline')));
    $compensation = esc_html(aitomic_upsert_value($item, 'compensation', 'Not specified'));
    $duration = esc_html(aitomic_upsert_value($item, 'duration'));
    $start_date = esc_html(aitomic_upsert_value($item, 'start_date'));
    $source = esc_html(aitomic_upsert_value($item, 'source', aitomic_upsert_value($item, 'organization')));
    $source_url = esc_url(aitomic_upsert_value($item, 'source_url'));
    $summary = aitomic_upsert_value($item, 'summary');
    $description = aitomic_upsert_value($item, 'description', $summary);
    $responsibilities = aitomic_upsert_list($item, 'responsibilities');
    $requirements = aitomic_upsert_list($item, 'requirements');
    $benefits = aitomic_upsert_list($item, 'benefits');
    $how_to_apply = aitomic_upsert_value($item, 'how_to_apply', 'Use the Apply now button to open the official opportunity page and follow the stated application instructions.');
    $verification = aitomic_upsert_value($item, 'verification_notes');

    $details = [
        'Position / opportunity' => $title,
        'Organization' => $organization,
        'Country / coverage' => $country,
        'Location' => $location,
        'Work arrangement' => $work_mode,
        'Opportunity type' => $type,
        'Sector' => $category,
        'Compensation' => $compensation,
        'Duration' => $duration,
        'Start date' => $start_date,
        'Application deadline' => $deadline ?: 'Not specified by source',
    ];

    $html = '<h2>Short Summary</h2><p>' . esc_html($summary) . '</p>';
    $html .= '<h2>Key Details</h2><ul>';
    foreach ($details as $label => $value) {
        if ($value === '') {
            continue;
        }
        $html .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    $html .= '</ul>';

    $html .= '<h2>Description</h2>' . aitomic_upsert_paragraphs($item, 'description');
    if ($responsibilities) {
        $html .= '<h2>Responsibilities</h2>' . aitomic_upsert_list_html($responsibilities);
    }
    if ($requirements) {
        $html .= '<h2>Requirements / Eligibility</h2>' . aitomic_upsert_list_html($requirements);
    }
    if ($benefits) {
        $html .= '<h2>Benefits / Compensation</h2>' . aitomic_upsert_list_html($benefits);
    }
    $html .= '<h2>How To Apply</h2><p>' . esc_html($how_to_apply) . '</p>';
    if ($verification !== '') {
        $html .= '<h2>Verification Notes</h2><p>' . esc_html($verification) . '</p>';
    }
    $html .= '<h2>Source</h2><p>Source: <a href="' . $source_url . '">' . $source . '</a>.</p>';
    return $html;
}

function aitomic_upsert_key(array $item): string {
    return hash('sha256', implode('|', [
        strtolower(aitomic_upsert_value($item, 'title')),
        strtolower(aitomic_upsert_value($item, 'organization')),
        strtolower(aitomic_upsert_value($item, 'source_url')),
    ]));
}

function aitomic_upsert_term_slug_for_value(string $taxonomy, string $value): string {
    $value = trim($value);
    $normalized = strtolower(preg_replace('/\s+/', ' ', $value));
    $maps = [
        'opportunity_type' => [
            'jobs' => 'job',
            'job' => 'job',
            'internship' => 'internship',
            'internships' => 'internship',
            'tender / consultancy' => 'tender-consultancy',
            'tenders / consultancies' => 'tender-consultancy',
            'consultancy' => 'tender-consultancy',
            'tender' => 'tender-consultancy',
            'remote work opportunities' => 'remote-work',
            'remote work opportunity' => 'remote-work',
            'remote work' => 'remote-work',
            'training / short courses' => 'training-short-course',
            'calls for applications' => 'call-for-applications',
            'call for applications' => 'call-for-applications',
            'volunteer opportunities' => 'volunteer',
        ],
        'work_mode' => [
            'on-site' => 'on-site',
            'onsite' => 'on-site',
            'hybrid' => 'hybrid',
            'remote' => 'remote',
            'field-based' => 'field-based',
            'field based' => 'field-based',
            'various' => 'field-based',
        ],
        'opportunity_category' => [
            'administration' => 'administration',
            'agriculture' => 'agriculture',
            'business & finance' => 'business-finance',
            'communications' => 'communications',
            'education' => 'education',
            'engineering' => 'engineering',
            'health' => 'health',
            'humanitarian & development' => 'humanitarian-development',
            'information technology' => 'information-technology',
            'legal & policy' => 'legal-policy',
            'monitoring & evaluation' => 'monitoring-evaluation',
            'operations & logistics' => 'operations-logistics',
        ],
    ];
    return $maps[$taxonomy][$normalized] ?? sanitize_title($value);
}

function aitomic_upsert_assign_term(int $post_id, string $taxonomy, string $value): void {
    $value = trim($value);
    if ($value === '') {
        return;
    }
    $slug = aitomic_upsert_term_slug_for_value($taxonomy, $value);
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        $term = wp_insert_term($value, $taxonomy, ['slug' => $slug]);
    }
    if (is_wp_error($term)) {
        return;
    }
    $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term->term_id;
    wp_set_object_terms($post_id, [$term_id], $taxonomy, false);
}

function aitomic_upsert_find_existing(array $item): int {
    $import_key = aitomic_upsert_key($item);
    $source_url = aitomic_upsert_value($item, 'source_url');
    $title = aitomic_upsert_value($item, 'title');
    $organization = aitomic_upsert_value($item, 'organization');

    $slug = sanitize_title($title . '-' . $organization);
    $existing = get_page_by_path($slug, OBJECT, 'opportunity');
    if ($existing instanceof WP_Post) {
        return (int) $existing->ID;
    }

    foreach ([
        ['key' => '_go_import_key', 'value' => $import_key],
        ['key' => '_go_source_url', 'value' => $source_url],
    ] as $meta) {
        if ($meta['value'] === '') {
            continue;
        }
        $ids = get_posts([
            'post_type' => 'opportunity',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 20,
            'meta_key' => $meta['key'],
            'meta_value' => $meta['value'],
        ]);
        foreach ($ids as $id) {
            $post = get_post((int) $id);
            if (!$post instanceof WP_Post) {
                continue;
            }
            $post_org = trim((string) get_post_meta((int) $id, '_go_organization', true));
            $same_title = strtolower(trim($post->post_title)) === strtolower($title);
            $same_org = strtolower($post_org) === strtolower($organization);
            if ($meta['key'] === '_go_import_key' || ($same_title && $same_org)) {
                return (int) $id;
            }
        }
    }

    return 0;
}

$created = 0;
$updated = 0;
$errors = 0;

foreach ($items as $item) {
    $title = sanitize_text_field(aitomic_upsert_value($item, 'title'));
    $organization = sanitize_text_field(aitomic_upsert_value($item, 'organization'));
    $source_url = esc_url_raw(aitomic_upsert_value($item, 'source_url'));
    if (!$title || !$organization || !$source_url) {
        $errors++;
        continue;
    }

    $postarr = [
        'post_type' => 'opportunity',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => sanitize_title($title . '-' . $organization),
        'post_excerpt' => wp_trim_words(sanitize_text_field(aitomic_upsert_value($item, 'summary')), 42),
        'post_content' => aitomic_upsert_content($item),
    ];

    $post_id = aitomic_upsert_find_existing($item);
    if ($post_id) {
        $postarr['ID'] = $post_id;
        $result = wp_update_post($postarr, true);
        $updated++;
    } else {
        $result = wp_insert_post($postarr, true);
        $post_id = is_wp_error($result) ? 0 : (int) $result;
        $created++;
    }

    if (is_wp_error($result) || !$post_id) {
        $errors++;
        continue;
    }

    update_post_meta($post_id, '_go_organization', $organization);
    update_post_meta($post_id, '_go_opportunity_type', sanitize_text_field(aitomic_upsert_value($item, 'opportunity_type')));
    update_post_meta($post_id, '_go_category', sanitize_text_field(aitomic_upsert_value($item, 'category')));
    update_post_meta($post_id, '_go_country', sanitize_text_field(aitomic_upsert_value($item, 'country')));
    update_post_meta($post_id, '_go_location', sanitize_text_field(aitomic_upsert_value($item, 'location')));
    update_post_meta($post_id, '_go_city', sanitize_text_field(aitomic_upsert_value($item, 'location')));
    update_post_meta($post_id, '_go_work_mode', sanitize_text_field(aitomic_upsert_value($item, 'work_mode', 'On-site')));
    update_post_meta($post_id, '_go_remote_option', sanitize_text_field(aitomic_upsert_value($item, 'work_mode', 'On-site')));
    update_post_meta($post_id, '_go_compensation', sanitize_text_field(aitomic_upsert_value($item, 'compensation', 'Not specified')));
    update_post_meta($post_id, '_go_salary', sanitize_text_field(aitomic_upsert_value($item, 'compensation', 'Not specified')));
    update_post_meta($post_id, '_go_deadline', sanitize_text_field(aitomic_upsert_value($item, 'deadline')));
    update_post_meta($post_id, '_go_duration', sanitize_text_field(aitomic_upsert_value($item, 'duration')));
    update_post_meta($post_id, '_go_start_date', sanitize_text_field(aitomic_upsert_value($item, 'start_date')));
    update_post_meta($post_id, '_go_source', sanitize_text_field(aitomic_upsert_value($item, 'source', $organization)));
    update_post_meta($post_id, '_go_source_url', $source_url);
    update_post_meta($post_id, '_go_source_link', $source_url);
    update_post_meta($post_id, '_go_application_link', esc_url_raw(aitomic_upsert_value($item, 'application_link', $source_url)));
    update_post_meta($post_id, '_go_institution_url', esc_url_raw(aitomic_upsert_value($item, 'institution_url')));
    update_post_meta($post_id, '_go_import_key', aitomic_upsert_key($item));
    update_post_meta($post_id, '_go_source_group', sanitize_text_field(aitomic_upsert_value($item, 'source_group')));
    update_post_meta($post_id, '_go_eligibility', sanitize_text_field(implode(' ', aitomic_upsert_list($item, 'requirements'))));

    aitomic_upsert_assign_term($post_id, 'opportunity_type', aitomic_upsert_value($item, 'opportunity_type'));
    aitomic_upsert_assign_term($post_id, 'opportunity_category', aitomic_upsert_value($item, 'category'));
    aitomic_upsert_assign_term($post_id, 'country', aitomic_upsert_value($item, 'country'));
    aitomic_upsert_assign_term($post_id, 'work_mode', aitomic_upsert_value($item, 'work_mode', 'On-site'));
}

echo json_encode(['created' => $created, 'updated' => $updated, 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
