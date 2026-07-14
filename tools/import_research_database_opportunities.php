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

function aitomic_import_value(array $item, string $key, string $fallback = ''): string {
    return trim((string) ($item[$key] ?? $fallback));
}

function aitomic_import_content(array $item): string {
    $title = esc_html(aitomic_import_value($item, 'title'));
    $organization = esc_html(aitomic_import_value($item, 'organization'));
    $country = esc_html(aitomic_import_value($item, 'country'));
    $location = esc_html(aitomic_import_value($item, 'location', aitomic_import_value($item, 'country')));
    $work_mode = esc_html(aitomic_import_value($item, 'work_mode', 'On-site'));
    $type = esc_html(aitomic_import_value($item, 'opportunity_type'));
    $category = esc_html(aitomic_import_value($item, 'category'));
    $deadline = esc_html(aitomic_import_value($item, 'deadline'));
    $compensation = esc_html(aitomic_import_value($item, 'compensation', 'Not specified'));
    $source = esc_html(aitomic_import_value($item, 'source', aitomic_import_value($item, 'organization')));
    $source_url = esc_url(aitomic_import_value($item, 'source_url'));
    $summary = "This is an official {$type} opportunity from {$organization} in {$country}. Review the official source page for the full announcement, eligibility requirements, application documents, and submission instructions.";

    return '<h2>Short Summary</h2>'
        . '<p>' . esc_html($summary) . '</p>'
        . '<h2>Key Details</h2>'
        . '<ul>'
        . '<li><strong>Position / opportunity:</strong> ' . $title . '</li>'
        . '<li><strong>Organization:</strong> ' . $organization . '</li>'
        . '<li><strong>Country:</strong> ' . $country . '</li>'
        . '<li><strong>Location:</strong> ' . $location . '</li>'
        . '<li><strong>Work arrangement:</strong> ' . $work_mode . '</li>'
        . '<li><strong>Opportunity type:</strong> ' . $type . '</li>'
        . '<li><strong>Category:</strong> ' . $category . '</li>'
        . '<li><strong>Compensation:</strong> ' . $compensation . '</li>'
        . '<li><strong>Application deadline:</strong> ' . ($deadline ?: 'Not specified by source') . '</li>'
        . '</ul>'
        . '<h2>Description</h2>'
        . '<p>' . esc_html($summary) . '</p>'
        . '<h2>Responsibilities</h2>'
        . '<ul><li>Review the official opportunity page for role, tender, consultancy, call, or volunteer-specific responsibilities.</li><li>Follow the instructions, format, and submission channel stated by the source institution.</li><li>Prepare all supporting documents requested by the source before applying or submitting.</li></ul>'
        . '<h2>Requirements / Eligibility</h2>'
        . '<ul><li>Eligibility requirements are set by the source institution.</li><li>Applicants, bidders, consultants, and volunteers should confirm qualifications, registration, experience, and documentation requirements on the official source page.</li><li>Where a deadline is not shown on Aitomic Jobs, confirm the closing date directly from the source before applying.</li></ul>'
        . '<h2>Benefits</h2>'
        . '<ul><li>' . $compensation . '</li><li>Contract terms, salary, fees, volunteer arrangements, or supplier conditions should be confirmed from the official source.</li></ul>'
        . '<h2>How To Apply</h2>'
        . '<p>Use the application button on this page to continue to the official opportunity source.</p>'
        . '<h2>Source</h2>'
        . '<p>Source: <a href="' . $source_url . '">' . $source . '</a>.</p>';
}

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($items as $item) {
    $title = sanitize_text_field(aitomic_import_value($item, 'title'));
    $organization = sanitize_text_field(aitomic_import_value($item, 'organization'));
    $source_url = esc_url_raw(aitomic_import_value($item, 'source_url'));
    if (!$title || !$organization || !$source_url) {
        $errors++;
        continue;
    }

    $existing = get_posts([
        'post_type' => 'opportunity',
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => 1,
        'meta_key' => '_go_source_url',
        'meta_value' => $source_url,
    ]);
    if ($existing) {
        $skipped++;
        continue;
    }

    $slug = sanitize_title($title . '-' . $organization);
    if (get_page_by_path($slug, OBJECT, 'opportunity')) {
        $skipped++;
        continue;
    }

    $type = sanitize_text_field(aitomic_import_value($item, 'opportunity_type', 'Jobs'));
    $country = sanitize_text_field(aitomic_import_value($item, 'country'));
    $summary = sanitize_text_field("Official {$type} opportunity from {$organization}" . ($country ? " in {$country}" : '') . ".");

    $post_id = wp_insert_post([
        'post_type' => 'opportunity',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_excerpt' => wp_trim_words($summary, 36),
        'post_content' => aitomic_import_content($item),
    ], true);

    if (is_wp_error($post_id)) {
        $errors++;
        continue;
    }

    update_post_meta($post_id, '_go_organization', $organization);
    update_post_meta($post_id, '_go_opportunity_type', $type);
    update_post_meta($post_id, '_go_category', sanitize_text_field(aitomic_import_value($item, 'category')));
    update_post_meta($post_id, '_go_country', $country);
    update_post_meta($post_id, '_go_location', sanitize_text_field(aitomic_import_value($item, 'location', $country)));
    update_post_meta($post_id, '_go_work_mode', sanitize_text_field(aitomic_import_value($item, 'work_mode', 'On-site')));
    update_post_meta($post_id, '_go_compensation', sanitize_text_field(aitomic_import_value($item, 'compensation', 'Not specified')));
    update_post_meta($post_id, '_go_deadline', sanitize_text_field(aitomic_import_value($item, 'deadline')));
    update_post_meta($post_id, '_go_source', sanitize_text_field(aitomic_import_value($item, 'source', $organization)));
    update_post_meta($post_id, '_go_source_url', $source_url);
    update_post_meta($post_id, '_go_application_link', esc_url_raw(aitomic_import_value($item, 'application_link', $source_url)));
    update_post_meta($post_id, '_go_institution_url', esc_url_raw(aitomic_import_value($item, 'institution_url')));
    update_post_meta($post_id, '_go_discovery_listing_page', esc_url_raw(aitomic_import_value($item, 'discovery_listing_page')));
    update_post_meta($post_id, '_go_source_group', sanitize_text_field(aitomic_import_value($item, 'source_group')));
    update_post_meta($post_id, '_go_eligibility', $summary);
    $created++;
}

echo json_encode(['created' => $created, 'skipped' => $skipped, 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
