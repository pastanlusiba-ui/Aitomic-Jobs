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

function aitomic_batch_clean_text(string $value): string {
    $value = wp_strip_all_tags($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim((string) $value);
}

function aitomic_batch_content(array $item): string {
    $title = esc_html($item['title'] ?? '');
    $organization = esc_html($item['organization'] ?? '');
    $country = esc_html($item['country'] ?? '');
    $location = esc_html($item['location'] ?? $country);
    $work_mode = esc_html($item['work_mode'] ?? '');
    $type = esc_html($item['opportunity_type'] ?? '');
    $category = esc_html($item['category'] ?? '');
    $deadline = esc_html($item['deadline'] ?? '');
    $compensation = esc_html(($item['compensation'] ?? '') ?: 'Not specified');
    $source = esc_html($item['source'] ?? $organization);
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
        . '<ul><li>Review the official opportunity page for role, tender, consultancy, or call-specific responsibilities.</li><li>Follow the instructions, format, and submission channel stated by the source institution.</li><li>Prepare all supporting documents requested by the source before applying or submitting.</li></ul>'
        . '<h2>Requirements / Eligibility</h2>'
        . '<ul><li>Eligibility requirements are set by the source institution.</li><li>Applicants or bidders should confirm qualifications, registration, experience, and documentation requirements on the official source page.</li><li>Where a deadline is not shown on Aitomic Jobs, confirm the closing date directly from the source before applying.</li></ul>'
        . '<h2>Benefits</h2>'
        . '<ul><li>' . $compensation . '</li><li>Contract terms, salary, fees, or supplier conditions should be confirmed from the official source.</li></ul>'
        . '<h2>How To Apply</h2>'
        . '<p>Use the application button on this page to continue to the official opportunity source.</p>'
        . '<h2>Source</h2>'
        . '<p>Source: ' . $source . '.</p>';
}

$updated = 0;
$skipped = 0;
foreach ($items as $item) {
    $title = sanitize_text_field($item['title'] ?? '');
    $organization = sanitize_text_field($item['organization'] ?? '');
    if (!$title || !$organization) {
        $skipped++;
        continue;
    }
    $slug = sanitize_title($title . '-' . $organization);
    $post = get_page_by_path($slug, OBJECT, 'opportunity');
    if (!$post instanceof WP_Post) {
        $skipped++;
        continue;
    }
    $type = sanitize_text_field($item['opportunity_type'] ?? 'opportunity');
    $country = sanitize_text_field($item['country'] ?? '');
    $summary = "Official {$type} opportunity from {$organization}" . ($country ? " in {$country}" : '') . ". Review the official source page for full details and application instructions.";
    wp_update_post([
        'ID' => $post->ID,
        'post_excerpt' => wp_trim_words($summary, 36),
        'post_content' => aitomic_batch_content($item),
    ]);
    update_post_meta($post->ID, '_go_eligibility', $summary);
    $updated++;
}

echo json_encode(['updated' => $updated, 'skipped' => $skipped], JSON_PRETTY_PRINT) . "\n";
