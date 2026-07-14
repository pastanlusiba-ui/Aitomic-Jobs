<?php
if (!defined('ABSPATH')) {
    exit;
}

function aitomic_backfill_value(int $post_id, array $keys): string {
    foreach ($keys as $key) {
        $value = trim((string) get_post_meta($post_id, $key, true));
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function aitomic_backfill_slug(string $value): string {
    return sanitize_title(trim($value));
}

function aitomic_backfill_type_slug(string $value): string {
    $value = strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    if (str_contains($value, 'intern')) {
        return 'internship';
    }
    if (str_contains($value, 'volunteer')) {
        return 'volunteer';
    }
    if (str_contains($value, 'remote')) {
        return 'remote-work';
    }
    if (str_contains($value, 'training') || str_contains($value, 'short course')) {
        return 'training-short-course';
    }
    if (str_contains($value, 'call') && str_contains($value, 'application')) {
        return 'call-for-applications';
    }
    if (str_contains($value, 'tender') || str_contains($value, 'consult') || str_contains($value, 'procurement') || str_contains($value, 'rfq')) {
        return 'tender-consultancy';
    }
    return 'job';
}

function aitomic_backfill_type_name(string $slug): string {
    $names = [
        'job' => 'Job',
        'internship' => 'Internship',
        'tender-consultancy' => 'Tender / Consultancy',
        'volunteer' => 'Volunteer Opportunity',
        'remote-work' => 'Remote Work Opportunity',
        'training-short-course' => 'Training / Short Course',
        'call-for-applications' => 'Call for Applications',
    ];
    return $names[$slug] ?? 'Job';
}

function aitomic_backfill_category_slug(string $value, string $title = '', string $organization = ''): string {
    $text = strtolower($value . ' ' . $title . ' ' . $organization);
    if (preg_match('/admin|human resources|hr|finance|budget|account|treasury|audit|investment|business|market|procurement|supply/i', $text)) {
        return str_contains($text, 'procurement') || str_contains($text, 'supply') ? 'operations-logistics' : 'business-finance';
    }
    if (preg_match('/agric|crop|livestock|fish|forestry|food|climate|environment|water|marine|biodiversity|natural resource|soil|seed/i', $text)) {
        return 'agriculture';
    }
    if (preg_match('/communication|media|public affairs|design|website|publishing|outreach|engagement/i', $text)) {
        return 'communications';
    }
    if (preg_match('/education|training|school|learning|culture|science|academic|university|student/i', $text)) {
        return 'education';
    }
    if (preg_match('/engineer|construction|infrastructure|energy|mechanical|electrical|civil|transport|laboratory equipment/i', $text)) {
        return 'engineering';
    }
    if (preg_match('/health|medical|doctor|nurse|clinical|disease|nutrition|cancer|public health|pharma/i', $text)) {
        return 'health';
    }
    if (preg_match('/it|ict|digital|data|software|cyber|artificial intelligence|ai|information system|technology/i', $text)) {
        return 'information-technology';
    }
    if (preg_match('/legal|policy|governance|legislative|political|rights|gender|protection/i', $text)) {
        return 'legal-policy';
    }
    if (preg_match('/monitoring|evaluation|meal|m&e|impact assessment|research coordination/i', $text)) {
        return 'monitoring-evaluation';
    }
    if (preg_match('/logistics|operations|driver|field|security|emergency|programme management|project management/i', $text)) {
        return 'operations-logistics';
    }
    return 'humanitarian-development';
}

function aitomic_backfill_category_name(string $slug): string {
    $names = [
        'administration' => 'Administration',
        'agriculture' => 'Agriculture',
        'business-finance' => 'Business & Finance',
        'communications' => 'Communications',
        'education' => 'Education',
        'engineering' => 'Engineering',
        'health' => 'Health',
        'humanitarian-development' => 'Humanitarian & Development',
        'information-technology' => 'Information Technology',
        'legal-policy' => 'Legal & Policy',
        'monitoring-evaluation' => 'Monitoring & Evaluation',
        'operations-logistics' => 'Operations & Logistics',
    ];
    return $names[$slug] ?? 'Humanitarian & Development';
}

function aitomic_backfill_country_slug(string $country, string $work_mode): string {
    $country = trim($country);
    $work_mode = strtolower(trim($work_mode));
    if ($country === '' || preg_match('/^(global\/international|global|international|various|multiple|remote \/ various)$/i', $country)) {
        return str_contains($work_mode, 'remote') ? 'remote' : 'global-international';
    }
    return aitomic_backfill_slug($country);
}

function aitomic_backfill_work_mode_slug(string $work_mode): string {
    $work_mode = strtolower(trim($work_mode));
    if (str_contains($work_mode, 'remote') || str_contains($work_mode, 'home-based') || str_contains($work_mode, 'home based')) {
        return 'remote';
    }
    if (str_contains($work_mode, 'hybrid')) {
        return 'hybrid';
    }
    if (str_contains($work_mode, 'field') || str_contains($work_mode, 'various') || str_contains($work_mode, 'multiple')) {
        return 'field-based';
    }
    return 'on-site';
}

function aitomic_backfill_work_mode_name(string $slug): string {
    $names = [
        'on-site' => 'On-site',
        'hybrid' => 'Hybrid',
        'remote' => 'Remote',
        'field-based' => 'Field-based',
    ];
    return $names[$slug] ?? 'On-site';
}

function aitomic_backfill_set_term(int $post_id, string $taxonomy, string $slug, string $name): void {
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        $term = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
    }
    if (is_wp_error($term)) {
        return;
    }
    $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term->term_id;
    wp_set_object_terms($post_id, [$term_id], $taxonomy, false);
}

$query = new WP_Query([
    'post_type' => 'opportunity',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$updated = 0;
foreach ($query->posts as $post_id) {
    $title = get_the_title($post_id);
    $organization = aitomic_backfill_value($post_id, ['_go_organization']);
    $type_value = aitomic_backfill_value($post_id, ['_go_opportunity_type', '_go_employment_type']);
    $category_value = aitomic_backfill_value($post_id, ['_go_category']);
    $country_value = aitomic_backfill_value($post_id, ['_go_country']);
    $work_value = aitomic_backfill_value($post_id, ['_go_work_mode', '_go_remote_option']);

    $type_slug = aitomic_backfill_type_slug($type_value ?: $title);
    $category_slug = aitomic_backfill_category_slug($category_value, $title, $organization);
    $work_slug = aitomic_backfill_work_mode_slug($work_value);
    $country_slug = aitomic_backfill_country_slug($country_value, $work_slug);

    aitomic_backfill_set_term($post_id, 'opportunity_type', $type_slug, aitomic_backfill_type_name($type_slug));
    aitomic_backfill_set_term($post_id, 'opportunity_category', $category_slug, aitomic_backfill_category_name($category_slug));
    aitomic_backfill_set_term($post_id, 'work_mode', $work_slug, aitomic_backfill_work_mode_name($work_slug));
    aitomic_backfill_set_term($post_id, 'country', $country_slug, $country_slug === 'global-international' ? 'Global/International' : ucwords(str_replace('-', ' ', $country_slug)));
    $updated++;
}

wp_update_term_count_now(get_terms(['taxonomy' => 'opportunity_type', 'fields' => 'ids', 'hide_empty' => false]), 'opportunity_type');
wp_update_term_count_now(get_terms(['taxonomy' => 'opportunity_category', 'fields' => 'ids', 'hide_empty' => false]), 'opportunity_category');
wp_update_term_count_now(get_terms(['taxonomy' => 'country', 'fields' => 'ids', 'hide_empty' => false]), 'country');
wp_update_term_count_now(get_terms(['taxonomy' => 'work_mode', 'fields' => 'ids', 'hide_empty' => false]), 'work_mode');

echo json_encode(['updated' => $updated], JSON_PRETTY_PRINT) . "\n";
