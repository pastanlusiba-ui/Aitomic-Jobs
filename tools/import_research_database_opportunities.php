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

function aitomic_import_key(array $item): string {
    $parts = [
        strtolower(aitomic_import_value($item, 'title')),
        strtolower(aitomic_import_value($item, 'organization')),
        strtolower(aitomic_import_value($item, 'source_url')),
    ];
    return hash('sha256', implode('|', $parts));
}

function aitomic_is_generic_source_url(string $source_url): bool {
    $parts = wp_parse_url($source_url);
    $path = trim((string) ($parts['path'] ?? ''), '/');
    if ($path === '' || preg_match('/^(careers?|jobs?|employment|vacancies?|procurement|working-with-us)$/i', $path)) {
        return true;
    }
    return (bool) preg_match('/(careers\.|jobs\.|\/careers?$|\/employment$|\/procurement$)/i', $source_url);
}

function aitomic_term_slug_for_value(string $taxonomy, string $value): string {
    $value = trim($value);
    $normalized = strtolower(preg_replace('/\s+/', ' ', $value));

    $maps = [
        'opportunity_type' => [
            'job' => 'job',
            'jobs' => 'job',
            'internship' => 'internship',
            'internships' => 'internship',
            'tender / consultancy' => 'tender-consultancy',
            'tenders / consultancies' => 'tender-consultancy',
            'consultancy' => 'tender-consultancy',
            'consultancies' => 'tender-consultancy',
            'tenders' => 'tender-consultancy',
            'volunteer' => 'volunteer',
            'volunteer opportunity' => 'volunteer',
            'volunteer opportunities' => 'volunteer',
            'remote' => 'remote-work',
            'remote work' => 'remote-work',
            'remote work opportunity' => 'remote-work',
            'remote work opportunities' => 'remote-work',
            'training' => 'training-short-course',
            'training / short course' => 'training-short-course',
            'training / short courses' => 'training-short-course',
            'call for applications' => 'call-for-applications',
            'calls for applications' => 'call-for-applications',
        ],
        'work_mode' => [
            'on-site' => 'on-site',
            'onsite' => 'on-site',
            'hybrid' => 'hybrid',
            'remote' => 'remote',
            'field-based' => 'field-based',
            'field based' => 'field-based',
            'various' => 'field-based',
            'multiple' => 'field-based',
        ],
        'opportunity_category' => [
            'administration' => 'administration',
            'agriculture' => 'agriculture',
            'business & finance' => 'business-finance',
            'business finance' => 'business-finance',
            'communications' => 'communications',
            'education' => 'education',
            'engineering' => 'engineering',
            'health' => 'health',
            'humanitarian & development' => 'humanitarian-development',
            'humanitarian development' => 'humanitarian-development',
            'information technology' => 'information-technology',
            'legal & policy' => 'legal-policy',
            'legal policy' => 'legal-policy',
            'monitoring & evaluation' => 'monitoring-evaluation',
            'monitoring evaluation' => 'monitoring-evaluation',
            'operations & logistics' => 'operations-logistics',
            'operations logistics' => 'operations-logistics',
        ],
    ];

    if (isset($maps[$taxonomy][$normalized])) {
        return $maps[$taxonomy][$normalized];
    }

    if ($taxonomy === 'country') {
        if (in_array($normalized, ['global/international', 'global / international', 'global', 'international', 'various', 'multiple', 'remote / various'], true)) {
            return $normalized === 'remote' ? 'remote' : 'global-international';
        }
    }

    return sanitize_title($value);
}

function aitomic_country_slug_from_location(string $location): string {
    $text = strtolower(trim($location));
    if ($text === '') {
        return '';
    }

    $city_map = [
        'nairobi' => 'kenya',
        'mombasa' => 'kenya',
        'nakuru' => 'kenya',
        'kisumu' => 'kenya',
        'eldoret' => 'kenya',
        'thika' => 'kenya',
        'sagana' => 'kenya',
        'kakamega' => 'kenya',
        'rest of kenya' => 'kenya',
        'outside kenya' => 'kenya',
        'kampala' => 'uganda',
        'entebbe' => 'uganda',
        'jinja' => 'uganda',
        'gulu' => 'uganda',
        'rest of uganda' => 'uganda',
        'outside uganda' => 'uganda',
        'lagos' => 'nigeria',
        'abuja' => 'nigeria',
        'osun' => 'nigeria',
        'abia' => 'nigeria',
        'ebonyi' => 'nigeria',
        'rivers' => 'nigeria',
        'minna' => 'nigeria',
        'bauchi' => 'nigeria',
        'lafia' => 'nigeria',
        'kigali' => 'rwanda',
        'bugesera' => 'rwanda',
        'rubengera' => 'rwanda',
        'addis ababa' => 'ethiopia',
        'cairo' => 'egypt',
        'dakar' => 'senegal',
        'abidjan' => 'cote-d-ivoire',
        'zomba' => 'malawi',
        'lilongwe' => 'malawi',
        'goma' => 'democratic-republic-of-the-congo',
        'kabwe' => 'zambia',
        'muramvya' => 'burundi',
        'dolow' => 'somalia',
        'abu dhabi' => 'united-arab-emirates',
        'kabul' => 'afghanistan',
        'herat' => 'afghanistan',
        'jalalabad' => 'afghanistan',
        'kandahar' => 'afghanistan',
        'helmand' => 'afghanistan',
        'nimroz' => 'afghanistan',
        'sar-e-pol' => 'afghanistan',
        'colombo' => 'sri-lanka',
        'ankara' => 'turkey',
        'geneva' => 'switzerland',
        'rome' => 'italy',
        'vienna' => 'austria',
        'the hague' => 'netherlands',
        'hamburg' => 'germany',
        'bremerhaven' => 'germany',
        'washington' => 'united-states',
        'new york' => 'united-states',
        'ledyard' => 'united-states',
        'anaheim' => 'united-states',
        'phoenix' => 'united-states',
        'burbank' => 'united-states',
        'las vegas' => 'united-states',
        'seattle' => 'united-states',
        'the woodlands' => 'united-states',
        'los angeles' => 'united-states',
        'frisco' => 'united-states',
        'jersey city' => 'united-states',
        'blue ash' => 'united-states',
    ];

    foreach ($city_map as $needle => $slug) {
        if (str_contains($text, $needle)) {
            return $slug;
        }
    }

    if (function_exists('go_countries')) {
        foreach (go_countries() as $slug => $name) {
            if ($slug === 'remote') {
                continue;
            }
            if (preg_match('/\b' . preg_quote(strtolower($name), '/') . '\b/u', $text)) {
                return $slug;
            }
        }
    }

    if (preg_match('/\b(AL|AK|AZ|AR|CA|CO|CT|DC|DE|FL|GA|HI|IA|ID|IL|IN|KS|KY|LA|MA|MD|ME|MI|MN|MO|MS|MT|NC|ND|NE|NH|NJ|NM|NV|NY|OH|OK|OR|PA|RI|SC|SD|TN|TX|UT|VA|VT|WA|WI|WV|WY)\b/', $location)) {
        return 'united-states';
    }

    return '';
}

function aitomic_country_name_from_slug(string $slug): string {
    if ($slug === 'global-international') {
        return 'Global/International';
    }
    if (function_exists('go_countries')) {
        $countries = go_countries();
        if (isset($countries[$slug])) {
            return $countries[$slug];
        }
    }
    return ucwords(str_replace('-', ' ', $slug));
}

function aitomic_country_value_for_item(array $item): string {
    $country = aitomic_import_value($item, 'country');
    $location = aitomic_import_value($item, 'location');
    $work_mode = strtolower(aitomic_import_value($item, 'work_mode'));
    $generic = $country === '' || preg_match('/^(global\/international|global|international|various|multiple|remote \/ various|not specified|not specified \/ see official listing)$/i', $country);

    if ($generic) {
        $slug = aitomic_country_slug_from_location($location);
        if ($slug !== '') {
            return aitomic_country_name_from_slug($slug);
        }
        return str_contains($work_mode, 'remote') ? 'Remote' : 'Global/International';
    }

    return $country;
}

function aitomic_assign_term_value(int $post_id, string $taxonomy, string $value): void {
    $value = trim($value);
    if ($value === '') {
        return;
    }

    $slug = aitomic_term_slug_for_value($taxonomy, $value);
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        $term = term_exists($slug, $taxonomy);
    }
    if (!$term) {
        $term = wp_insert_term($value, $taxonomy, ['slug' => $slug]);
    }
    if (is_wp_error($term)) {
        return;
    }

    $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term->term_id;
    wp_set_object_terms($post_id, [$term_id], $taxonomy, false);
}

function aitomic_assign_imported_terms(int $post_id, array $item): void {
    aitomic_assign_term_value($post_id, 'opportunity_type', aitomic_import_value($item, 'opportunity_type'));
    aitomic_assign_term_value($post_id, 'opportunity_category', aitomic_import_value($item, 'category'));
    aitomic_assign_term_value($post_id, 'country', aitomic_country_value_for_item($item));
    aitomic_assign_term_value($post_id, 'work_mode', aitomic_import_value($item, 'work_mode'));
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
        'meta_key' => '_go_import_key',
        'meta_value' => aitomic_import_key($item),
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

    if (!aitomic_is_generic_source_url($source_url)) {
        $existing_source = get_posts([
            'post_type' => 'opportunity',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'meta_key' => '_go_source_url',
            'meta_value' => $source_url,
        ]);
        if ($existing_source) {
            $skipped++;
            continue;
        }
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
    update_post_meta($post_id, '_go_country', sanitize_text_field(aitomic_country_value_for_item($item)));
    update_post_meta($post_id, '_go_location', sanitize_text_field(aitomic_import_value($item, 'location', $country)));
    update_post_meta($post_id, '_go_work_mode', sanitize_text_field(aitomic_import_value($item, 'work_mode', 'On-site')));
    update_post_meta($post_id, '_go_compensation', sanitize_text_field(aitomic_import_value($item, 'compensation', 'Not specified')));
    update_post_meta($post_id, '_go_deadline', sanitize_text_field(aitomic_import_value($item, 'deadline')));
    update_post_meta($post_id, '_go_source', sanitize_text_field(aitomic_import_value($item, 'source', $organization)));
    update_post_meta($post_id, '_go_source_url', $source_url);
    update_post_meta($post_id, '_go_import_key', aitomic_import_key($item));
    update_post_meta($post_id, '_go_application_link', esc_url_raw(aitomic_import_value($item, 'application_link', $source_url)));
    update_post_meta($post_id, '_go_institution_url', esc_url_raw(aitomic_import_value($item, 'institution_url')));
    update_post_meta($post_id, '_go_discovery_listing_page', esc_url_raw(aitomic_import_value($item, 'discovery_listing_page')));
    update_post_meta($post_id, '_go_source_group', sanitize_text_field(aitomic_import_value($item, 'source_group')));
    update_post_meta($post_id, '_go_eligibility', $summary);
    aitomic_assign_imported_terms($post_id, $item);
    $created++;
}

echo json_encode(['created' => $created, 'skipped' => $skipped, 'errors' => $errors], JSON_PRETTY_PRINT) . "\n";
