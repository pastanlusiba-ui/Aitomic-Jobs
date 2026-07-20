<?php
/**
 * Rebuild opportunity page content without import/source/workbook language.
 *
 * Usage:
 * wp eval-file /home/u710255073/aitomic-tools/clean_opportunity_source_language.php
 */

if (!defined('ABSPATH')) {
    exit;
}

function aitomic_clean_page_value(string $value): string
{
    $value = trim($value);
    $replacements = [
        'Not specified in supplied workbook' => 'Not specified',
        'Not specified by source' => 'Not specified',
        'Check listing' => 'Check application page',
    ];

    return $replacements[$value] ?? $value;
}

function aitomic_clean_page_meta(int $post_id, string $key): string
{
    return aitomic_clean_page_value((string) get_post_meta($post_id, '_go_' . $key, true));
}

function aitomic_clean_page_terms(int $post_id, string $taxonomy): string
{
    $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
    if (is_wp_error($terms) || !$terms) {
        return '';
    }

    return aitomic_clean_page_value(implode(', ', $terms));
}

function aitomic_clean_page_detail(string $content, string $label): string
{
    $pattern = '/<li>\s*<strong>\s*' . preg_quote($label, '/') . '\s*:\s*<\/strong>\s*(.*?)<\/li>/is';
    if (!preg_match($pattern, $content, $matches)) {
        return '';
    }

    return aitomic_clean_page_value(trim(html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function aitomic_clean_page_sentence(string $value): string
{
    return esc_html(aitomic_clean_page_value($value));
}

function aitomic_clean_page_type_phrase(string $type): string
{
    $type = aitomic_clean_page_value($type);
    $type_lower = strtolower($type);
    if (str_contains($type_lower, 'intern')) {
        return 'an internship opportunity';
    }
    if (str_contains($type_lower, 'volunteer')) {
        return 'a volunteer opportunity';
    }
    if (str_contains($type_lower, 'consult') || str_contains($type_lower, 'tender')) {
        return 'a tender or consultancy opportunity';
    }
    if (str_contains($type_lower, 'remote')) {
        return 'a remote work opportunity';
    }
    if (str_contains($type_lower, 'training')) {
        return 'a training or short course opportunity';
    }
    if (str_contains($type_lower, 'call')) {
        return 'a call for applications';
    }
    if (str_contains($type_lower, 'job')) {
        return 'a job opportunity';
    }

    return 'an opportunity';
}

function aitomic_clean_page_list(array $rows): string
{
    $items = array_values(array_filter(array_map('aitomic_clean_page_value', $rows)));
    if (!$items) {
        return '';
    }

    return '<ul><li>' . implode('</li><li>', array_map('esc_html', $items)) . '</li></ul>';
}

function aitomic_clean_page_content(int $post_id): string
{
    $current = (string) get_post_field('post_content', $post_id);
    $title = get_the_title($post_id);
    $organization = aitomic_clean_page_meta($post_id, 'organization') ?: aitomic_clean_page_detail($current, 'Organization');
    $country = aitomic_clean_page_terms($post_id, 'country') ?: aitomic_clean_page_meta($post_id, 'country') ?: aitomic_clean_page_detail($current, 'Country / coverage') ?: aitomic_clean_page_detail($current, 'Country');
    $location = aitomic_clean_page_meta($post_id, 'location') ?: aitomic_clean_page_meta($post_id, 'city') ?: aitomic_clean_page_detail($current, 'Location') ?: $country;
    $work_mode = aitomic_clean_page_terms($post_id, 'work_mode') ?: aitomic_clean_page_meta($post_id, 'work_mode') ?: aitomic_clean_page_meta($post_id, 'remote_option') ?: aitomic_clean_page_detail($current, 'Work arrangement');
    $type = aitomic_clean_page_terms($post_id, 'opportunity_type') ?: aitomic_clean_page_meta($post_id, 'opportunity_type') ?: aitomic_clean_page_meta($post_id, 'employment_type') ?: aitomic_clean_page_detail($current, 'Opportunity type');
    $category = aitomic_clean_page_terms($post_id, 'opportunity_category') ?: aitomic_clean_page_meta($post_id, 'category') ?: aitomic_clean_page_detail($current, 'Sector') ?: aitomic_clean_page_detail($current, 'Category');
    $compensation = aitomic_clean_page_meta($post_id, 'compensation') ?: aitomic_clean_page_meta($post_id, 'salary') ?: aitomic_clean_page_detail($current, 'Compensation') ?: 'Not specified';
    $duration = aitomic_clean_page_meta($post_id, 'duration') ?: aitomic_clean_page_detail($current, 'Duration');
    $start_date = aitomic_clean_page_meta($post_id, 'start_date') ?: aitomic_clean_page_detail($current, 'Start date');
    $deadline = aitomic_clean_page_meta($post_id, 'deadline') ?: aitomic_clean_page_detail($current, 'Application deadline') ?: 'Not specified';

    $type_label = $type ?: 'opportunity';
    $category_label = $category ?: 'the stated field';
    $location_label = $location ?: $country ?: 'the stated location';
    $organization_label = $organization ?: 'the recruiting organization';

    $summary = sprintf(
        '%s is %s with %s. It is connected to %s and falls under %s.',
        $title,
        aitomic_clean_page_type_phrase($type_label),
        $organization_label,
        $location_label,
        $category_label
    );

    $description = sprintf(
        '%s is offering %s for people interested in %s. The opportunity is associated with %s%s. Applicants should review the role or assignment details, eligibility requirements, benefits, deadline, and application process before applying.',
        $organization_label,
        $title,
        strtolower($category_label),
        $location_label,
        $work_mode ? ' with a ' . strtolower($work_mode) . ' work arrangement' : ''
    );

    $type_lower = strtolower($type_label);
    if (str_contains($type_lower, 'volunteer')) {
        $responsibilities = [
            'Support programme activities, events, outreach, fieldwork, education, research, or community-facing work connected to the opportunity.',
            'Participate reliably in the stated schedule, training, onboarding, and team coordination requirements.',
            'Work professionally with staff, participants, communities, students, visitors, or partners connected to the opportunity.',
        ];
        $requirements = [
            'Meet the age, availability, location, onboarding, and participation requirements for the opportunity.',
            'Have interest or experience relevant to ' . $category_label . ', community service, education, research, or public engagement.',
            'Complete the application and any screening, interview, training, or onboarding steps required for participation.',
        ];
    } elseif (str_contains($type_lower, 'intern') || str_contains($type_lower, 'training')) {
        $responsibilities = [
            'Support assigned project, research, technical, administrative, operational, or communications activities.',
            'Participate in supervised learning, team meetings, documentation, and practical work connected to ' . $category_label . '.',
            'Prepare assigned notes, analysis, datasets, briefs, presentations, reports, or other deliverables requested by the host team.',
        ];
        $requirements = [
            'Meet the student, graduate, early-career, or participant eligibility rules for the opportunity.',
            'Have interest, coursework, training, or experience relevant to ' . $category_label . '.',
            'Submit the required application materials before the stated deadline and follow the application process.',
        ];
    } elseif (str_contains($type_lower, 'consult') || str_contains($type_lower, 'tender')) {
        $responsibilities = [
            'Prepare a responsive technical, financial, consultancy, or procurement submission based on the tender or assignment documents.',
            'Deliver the services, goods, research, analysis, advisory work, or outputs requested by the contracting organization.',
            'Coordinate with the client team on timelines, reporting, quality assurance, and final deliverables.',
        ];
        $requirements = [
            'Demonstrate relevant professional, technical, institutional, or supplier experience for the assignment.',
            'Provide the registration, technical, financial, eligibility, and supporting documents requested for the opportunity.',
            'Meet the deadline, submission format, and procurement or consultancy rules for the assignment.',
        ];
    } else {
        $responsibilities = [
            'Deliver the technical, programme, research, administrative, operational, or stakeholder-facing work connected to the role.',
            'Coordinate with internal teams, partners, clients, communities, or stakeholders to complete assigned outputs.',
            'Prepare documentation, analysis, reports, tools, systems, communications, or services required by the role.',
        ];
        $requirements = [
            'Have relevant education, skills, training, or professional experience related to ' . $category_label . '.',
            'Meet the qualifications, location, work authorization, documentation, and deadline requirements for the opportunity.',
            'Communicate clearly and work professionally with multidisciplinary teams or stakeholders.',
        ];
    }

    $benefits = strtolower($compensation) !== 'not specified'
        ? [$compensation]
        : ['Compensation, benefits, fees, allowances, or volunteer arrangements are not specified.'];

    $details = [
        'Position / opportunity' => $title,
        'Organization' => $organization,
        'Country / coverage' => $country,
        'Location' => $location,
        'Work arrangement' => $work_mode,
        'Opportunity type' => $type_label,
        'Sector' => $category,
        'Compensation' => $compensation,
        'Duration' => $duration,
        'Start date' => $start_date,
        'Application deadline' => $deadline,
    ];

    $html = '<h2>Short Summary</h2><p>' . aitomic_clean_page_sentence($summary) . '</p>';
    $html .= '<h2>Key Details</h2><ul>';
    foreach ($details as $label => $value) {
        $value = aitomic_clean_page_value((string) $value);
        if ($value === '') {
            continue;
        }
        $html .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    $html .= '</ul>';
    $html .= '<h2>Description</h2><p>' . aitomic_clean_page_sentence($description) . '</p>';
    $html .= '<h2>Responsibilities</h2>' . aitomic_clean_page_list($responsibilities);
    $html .= '<h2>Requirements / Eligibility</h2>' . aitomic_clean_page_list($requirements);
    $html .= '<h2>Benefits / Compensation</h2>' . aitomic_clean_page_list($benefits);
    $html .= '<h2>How To Apply</h2><p>Use the Apply now button and follow the stated application process.</p>';

    return $html;
}

$ids = get_posts([
    'post_type' => 'opportunity',
    'post_status' => 'any',
    'fields' => 'ids',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$updated = 0;
foreach ($ids as $post_id) {
    $post_id = (int) $post_id;
    $content = aitomic_clean_page_content($post_id);
    $result = wp_update_post([
        'ID' => $post_id,
        'post_content' => $content,
        'post_excerpt' => wp_trim_words(wp_strip_all_tags($content), 42),
    ], true);

    if (!is_wp_error($result)) {
        foreach (['compensation', 'salary', 'duration', 'deadline'] as $key) {
            $meta_key = '_go_' . $key;
            $value = get_post_meta($post_id, $meta_key, true);
            if (is_string($value) && $value !== aitomic_clean_page_value($value)) {
                update_post_meta($post_id, $meta_key, aitomic_clean_page_value($value));
            }
        }
        $updated++;
    }
}

echo wp_json_encode([
    'checked' => count($ids),
    'updated' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
