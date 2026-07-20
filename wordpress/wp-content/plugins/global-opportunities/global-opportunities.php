<?php
/**
 * Plugin Name: Global Opportunities
 * Description: Adds WordPress content types, taxonomies, fields, and shortcodes for a global jobs and opportunities directory.
 * Version: 0.1.1
 * Author: Aitomic
 * Text Domain: global-opportunities
 */

if (!defined('ABSPATH')) {
    exit;
}

const GO_META_PREFIX = '_go_';

function go_opportunity_types(): array
{
    return [
        'job' => 'Job',
        'internship' => 'Internship',
        'tender-consultancy' => 'Tender / Consultancy',
        'volunteer' => 'Volunteer Opportunity',
        'remote-work' => 'Remote Work Opportunity',
        'training-short-course' => 'Training / Short Course',
        'call-for-applications' => 'Call for Applications',
    ];
}

function go_opportunity_categories(): array
{
    return [
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
}

function go_countries(): array
{
    return [
        'remote' => 'Remote',
        'afghanistan' => 'Afghanistan',
        'albania' => 'Albania',
        'algeria' => 'Algeria',
        'andorra' => 'Andorra',
        'angola' => 'Angola',
        'antigua-and-barbuda' => 'Antigua and Barbuda',
        'argentina' => 'Argentina',
        'armenia' => 'Armenia',
        'australia' => 'Australia',
        'austria' => 'Austria',
        'azerbaijan' => 'Azerbaijan',
        'bahamas' => 'Bahamas',
        'bahrain' => 'Bahrain',
        'bangladesh' => 'Bangladesh',
        'barbados' => 'Barbados',
        'belarus' => 'Belarus',
        'belgium' => 'Belgium',
        'belize' => 'Belize',
        'benin' => 'Benin',
        'bhutan' => 'Bhutan',
        'bolivia' => 'Bolivia',
        'bosnia-and-herzegovina' => 'Bosnia and Herzegovina',
        'botswana' => 'Botswana',
        'brazil' => 'Brazil',
        'brunei' => 'Brunei',
        'bulgaria' => 'Bulgaria',
        'burkina-faso' => 'Burkina Faso',
        'burundi' => 'Burundi',
        'cabo-verde' => 'Cabo Verde',
        'cambodia' => 'Cambodia',
        'cameroon' => 'Cameroon',
        'canada' => 'Canada',
        'central-african-republic' => 'Central African Republic',
        'chad' => 'Chad',
        'chile' => 'Chile',
        'china' => 'China',
        'colombia' => 'Colombia',
        'comoros' => 'Comoros',
        'congo' => 'Congo',
        'costa-rica' => 'Costa Rica',
        'cote-d-ivoire' => "Cote d'Ivoire",
        'croatia' => 'Croatia',
        'cuba' => 'Cuba',
        'cyprus' => 'Cyprus',
        'czechia' => 'Czechia',
        'democratic-republic-of-the-congo' => 'Democratic Republic of the Congo',
        'denmark' => 'Denmark',
        'djibouti' => 'Djibouti',
        'dominica' => 'Dominica',
        'dominican-republic' => 'Dominican Republic',
        'ecuador' => 'Ecuador',
        'egypt' => 'Egypt',
        'el-salvador' => 'El Salvador',
        'equatorial-guinea' => 'Equatorial Guinea',
        'eritrea' => 'Eritrea',
        'estonia' => 'Estonia',
        'eswatini' => 'Eswatini',
        'ethiopia' => 'Ethiopia',
        'fiji' => 'Fiji',
        'finland' => 'Finland',
        'france' => 'France',
        'gabon' => 'Gabon',
        'gambia' => 'Gambia',
        'georgia' => 'Georgia',
        'germany' => 'Germany',
        'ghana' => 'Ghana',
        'greece' => 'Greece',
        'grenada' => 'Grenada',
        'guatemala' => 'Guatemala',
        'guinea' => 'Guinea',
        'guinea-bissau' => 'Guinea-Bissau',
        'guyana' => 'Guyana',
        'haiti' => 'Haiti',
        'honduras' => 'Honduras',
        'hungary' => 'Hungary',
        'iceland' => 'Iceland',
        'india' => 'India',
        'indonesia' => 'Indonesia',
        'iran' => 'Iran',
        'iraq' => 'Iraq',
        'ireland' => 'Ireland',
        'israel' => 'Israel',
        'italy' => 'Italy',
        'jamaica' => 'Jamaica',
        'japan' => 'Japan',
        'jordan' => 'Jordan',
        'kazakhstan' => 'Kazakhstan',
        'kenya' => 'Kenya',
        'kiribati' => 'Kiribati',
        'kosovo' => 'Kosovo',
        'kuwait' => 'Kuwait',
        'kyrgyzstan' => 'Kyrgyzstan',
        'laos' => 'Laos',
        'latvia' => 'Latvia',
        'lebanon' => 'Lebanon',
        'lesotho' => 'Lesotho',
        'liberia' => 'Liberia',
        'libya' => 'Libya',
        'liechtenstein' => 'Liechtenstein',
        'lithuania' => 'Lithuania',
        'luxembourg' => 'Luxembourg',
        'madagascar' => 'Madagascar',
        'malawi' => 'Malawi',
        'malaysia' => 'Malaysia',
        'maldives' => 'Maldives',
        'mali' => 'Mali',
        'malta' => 'Malta',
        'marshall-islands' => 'Marshall Islands',
        'mauritania' => 'Mauritania',
        'mauritius' => 'Mauritius',
        'mexico' => 'Mexico',
        'micronesia' => 'Micronesia',
        'moldova' => 'Moldova',
        'monaco' => 'Monaco',
        'mongolia' => 'Mongolia',
        'montenegro' => 'Montenegro',
        'morocco' => 'Morocco',
        'mozambique' => 'Mozambique',
        'myanmar' => 'Myanmar',
        'namibia' => 'Namibia',
        'nauru' => 'Nauru',
        'nepal' => 'Nepal',
        'netherlands' => 'Netherlands',
        'new-zealand' => 'New Zealand',
        'nicaragua' => 'Nicaragua',
        'niger' => 'Niger',
        'nigeria' => 'Nigeria',
        'north-korea' => 'North Korea',
        'north-macedonia' => 'North Macedonia',
        'norway' => 'Norway',
        'oman' => 'Oman',
        'pakistan' => 'Pakistan',
        'palau' => 'Palau',
        'palestine' => 'Palestine',
        'panama' => 'Panama',
        'papua-new-guinea' => 'Papua New Guinea',
        'paraguay' => 'Paraguay',
        'peru' => 'Peru',
        'philippines' => 'Philippines',
        'poland' => 'Poland',
        'portugal' => 'Portugal',
        'qatar' => 'Qatar',
        'romania' => 'Romania',
        'russia' => 'Russia',
        'rwanda' => 'Rwanda',
        'saint-kitts-and-nevis' => 'Saint Kitts and Nevis',
        'saint-lucia' => 'Saint Lucia',
        'saint-vincent-and-the-grenadines' => 'Saint Vincent and the Grenadines',
        'samoa' => 'Samoa',
        'san-marino' => 'San Marino',
        'sao-tome-and-principe' => 'Sao Tome and Principe',
        'saudi-arabia' => 'Saudi Arabia',
        'senegal' => 'Senegal',
        'serbia' => 'Serbia',
        'seychelles' => 'Seychelles',
        'sierra-leone' => 'Sierra Leone',
        'singapore' => 'Singapore',
        'slovakia' => 'Slovakia',
        'slovenia' => 'Slovenia',
        'solomon-islands' => 'Solomon Islands',
        'somalia' => 'Somalia',
        'south-africa' => 'South Africa',
        'south-korea' => 'South Korea',
        'south-sudan' => 'South Sudan',
        'spain' => 'Spain',
        'sri-lanka' => 'Sri Lanka',
        'sudan' => 'Sudan',
        'suriname' => 'Suriname',
        'sweden' => 'Sweden',
        'switzerland' => 'Switzerland',
        'syria' => 'Syria',
        'taiwan' => 'Taiwan',
        'tajikistan' => 'Tajikistan',
        'tanzania' => 'Tanzania',
        'thailand' => 'Thailand',
        'timor-leste' => 'Timor-Leste',
        'togo' => 'Togo',
        'tonga' => 'Tonga',
        'trinidad-and-tobago' => 'Trinidad and Tobago',
        'tunisia' => 'Tunisia',
        'turkey' => 'Turkey',
        'turkmenistan' => 'Turkmenistan',
        'tuvalu' => 'Tuvalu',
        'uganda' => 'Uganda',
        'ukraine' => 'Ukraine',
        'united-arab-emirates' => 'United Arab Emirates',
        'united-kingdom' => 'United Kingdom',
        'united-states' => 'United States',
        'uruguay' => 'Uruguay',
        'uzbekistan' => 'Uzbekistan',
        'vanuatu' => 'Vanuatu',
        'vatican-city' => 'Vatican City',
        'venezuela' => 'Venezuela',
        'vietnam' => 'Vietnam',
        'yemen' => 'Yemen',
        'zambia' => 'Zambia',
        'zimbabwe' => 'Zimbabwe',
    ];
}

function go_work_modes(): array
{
    return [
        'on-site' => 'On-site',
        'hybrid' => 'Hybrid',
        'remote' => 'Remote',
        'field-based' => 'Field-based',
    ];
}

function go_register_opportunity_post_type(): void
{
    register_post_type('opportunity', [
        'labels' => [
            'name' => __('Opportunities', 'global-opportunities'),
            'singular_name' => __('Opportunity', 'global-opportunities'),
            'add_new_item' => __('Add New Opportunity', 'global-opportunities'),
            'edit_item' => __('Edit Opportunity', 'global-opportunities'),
            'new_item' => __('New Opportunity', 'global-opportunities'),
            'view_item' => __('View Opportunity', 'global-opportunities'),
            'search_items' => __('Search Opportunities', 'global-opportunities'),
            'not_found' => __('No opportunities found', 'global-opportunities'),
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-id-alt',
        'rewrite' => ['slug' => 'opportunities'],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'go_register_opportunity_post_type');

function go_register_taxonomies(): void
{
    register_taxonomy('opportunity_type', ['opportunity'], [
        'labels' => [
            'name' => __('Opportunity Types', 'global-opportunities'),
            'singular_name' => __('Opportunity Type', 'global-opportunities'),
        ],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'opportunity-type'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy('country', ['opportunity'], [
        'labels' => [
            'name' => __('Countries', 'global-opportunities'),
            'singular_name' => __('Country', 'global-opportunities'),
        ],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'country'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy('opportunity_category', ['opportunity'], [
        'labels' => [
            'name' => __('Categories', 'global-opportunities'),
            'singular_name' => __('Category', 'global-opportunities'),
        ],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'opportunity-category'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy('work_mode', ['opportunity'], [
        'labels' => [
            'name' => __('Work Modes', 'global-opportunities'),
            'singular_name' => __('Work Mode', 'global-opportunities'),
        ],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'work-mode'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'go_register_taxonomies');

function go_seed_terms(): void
{
    $seed_groups = [
        'opportunity_type' => go_opportunity_types(),
        'opportunity_category' => go_opportunity_categories(),
        'country' => go_countries(),
        'work_mode' => go_work_modes(),
    ];

    foreach ($seed_groups as $taxonomy => $terms) {
        foreach ($terms as $slug => $name) {
            if (!term_exists($slug, $taxonomy)) {
                wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            }
        }
    }
}

function go_activate(): void
{
    go_register_opportunity_post_type();
    go_register_taxonomies();
    go_seed_terms();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'go_activate');

function go_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'go_deactivate');
add_action('init', 'go_seed_terms', 20);

function go_meta_fields(): array
{
    return [
        'organization' => 'Organization / Employer',
        'city' => 'City / Location',
        'remote_option' => 'Remote Option',
        'deadline' => 'Application Deadline',
        'start_date' => 'Start Date',
        'duration' => 'Duration',
        'employment_type' => 'Employment / Opportunity Type',
        'salary' => 'Salary / Compensation',
        'eligibility' => 'Eligibility',
        'application_link' => 'Application Link',
        'source_link' => 'Source Link',
        'contact_email' => 'Contact Email',
        'status' => 'Status',
    ];
}

function go_add_meta_boxes(): void
{
    add_meta_box(
        'go_opportunity_details',
        __('Opportunity Details', 'global-opportunities'),
        'go_render_details_meta_box',
        'opportunity',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'go_add_meta_boxes');

function go_render_details_meta_box(WP_Post $post): void
{
    wp_nonce_field('go_save_opportunity_details', 'go_opportunity_nonce');

    echo '<div class="go-admin-grid">';
    foreach (go_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, GO_META_PREFIX . $key, true);
        $type = in_array($key, ['deadline', 'start_date'], true) ? 'date' : 'text';
        $input_name = 'go_' . esc_attr($key);

        echo '<p>';
        echo '<label for="' . esc_attr($input_name) . '"><strong>' . esc_html($label) . '</strong></label><br>';

        if (in_array($key, ['eligibility'], true)) {
            echo '<textarea id="' . esc_attr($input_name) . '" name="' . esc_attr($input_name) . '" rows="4" style="width:100%;">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input id="' . esc_attr($input_name) . '" name="' . esc_attr($input_name) . '" type="' . esc_attr($type) . '" value="' . esc_attr($value) . '" style="width:100%;">';
        }
        echo '</p>';
    }
    echo '</div>';
}

function go_save_opportunity_details(int $post_id): void
{
    if (!isset($_POST['go_opportunity_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['go_opportunity_nonce'])), 'go_save_opportunity_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (go_meta_fields() as $key => $label) {
        $field = 'go_' . $key;
        if (!array_key_exists($field, $_POST)) {
            continue;
        }

        $raw_value = wp_unslash($_POST[$field]);
        $value = $key === 'eligibility' ? sanitize_textarea_field($raw_value) : sanitize_text_field($raw_value);
        update_post_meta($post_id, GO_META_PREFIX . $key, $value);
    }
}
add_action('save_post_opportunity', 'go_save_opportunity_details');

function go_get_meta(int $post_id, string $key): string
{
    return (string) get_post_meta($post_id, GO_META_PREFIX . $key, true);
}

function go_render_opportunity_meta_list(int $post_id): string
{
    $items = [
        'organization' => 'Organization',
        'city' => 'Location',
        'remote_option' => 'Remote option',
        'deadline' => 'Deadline',
        'duration' => 'Duration',
        'employment_type' => 'Type',
        'salary' => 'Compensation',
    ];

    $html = '<dl class="opportunity-meta">';
    foreach ($items as $key => $label) {
        $value = go_get_meta($post_id, $key);
        if ($value === '') {
            continue;
        }

        $html .= '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }
    $html .= '</dl>';

    return $html;
}

function go_plain_text(string $text): string
{
    $text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim((string) $text);
}

function go_trim_text(string $text, int $limit): string
{
    $text = go_plain_text($text);
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

function go_term_names(int $post_id, string $taxonomy): string
{
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function go_content_detail(int $post_id, string $label): string
{
    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return '';
    }

    $pattern = '/<li>\s*<strong>\s*' . preg_quote($label, '/') . '\s*:\s*<\/strong>\s*(.*?)<\/li>/is';
    if (!preg_match($pattern, $content, $matches)) {
        return '';
    }

    return go_plain_text($matches[1]);
}

function go_deadline_label(int $post_id): string
{
    $deadline = trim(go_get_meta($post_id, 'deadline')) ?: go_content_detail($post_id, 'Application deadline');
    if ($deadline === '') {
        return 'Not specified';
    }

    $timestamp = strtotime($deadline);
    if (!$timestamp) {
        return $deadline;
    }

    return date_i18n('M j, Y', $timestamp);
}

function go_social_hashtags(int $post_id, string $type, string $country, string $category): string
{
    $tags = ['#AitomicJobs', '#Opportunities', '#CareerOpportunity'];
    $type_lower = strtolower($type);
    $category_lower = strtolower($category);

    if (str_contains($type_lower, 'intern')) {
        $tags[] = '#Internship';
        $tags[] = '#Students';
        $tags[] = '#YoungProfessionals';
    } elseif (str_contains($type_lower, 'consult') || str_contains($type_lower, 'tender')) {
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

    if (str_contains($category_lower, 'health')) {
        $tags[] = '#GlobalHealth';
    } elseif (str_contains($category_lower, 'education')) {
        $tags[] = '#Education';
    } elseif (str_contains($category_lower, 'communication')) {
        $tags[] = '#Communications';
    } elseif (str_contains($category_lower, 'agriculture')) {
        $tags[] = '#Agriculture';
    } elseif (str_contains($category_lower, 'humanitarian') || str_contains($category_lower, 'development')) {
        $tags[] = '#InternationalDevelopment';
    } elseif (str_contains($category_lower, 'information') || str_contains($category_lower, 'technology')) {
        $tags[] = '#Technology';
    }

    $country_tag = preg_replace('/[^A-Za-z0-9]/', '', $country);
    if ($country_tag !== '' && !in_array(strtolower($country), ['global/international', 'global', 'international', 'remote', 'various', 'multiple'], true)) {
        $tags[] = '#' . $country_tag;
    }

    return implode(' ', array_slice(array_unique($tags), 0, 10));
}

function go_linkedin_opportunity_share_text(string $content, int $post_id): string
{
    if (get_post_type($post_id) !== 'opportunity') {
        return $content;
    }

    $title = go_plain_text(get_the_title($post_id));
    $organization = go_plain_text(go_get_meta($post_id, 'organization'));
    $type = go_term_names($post_id, 'opportunity_type') ?: go_get_meta($post_id, 'employment_type');
    $category = go_term_names($post_id, 'opportunity_category');
    $focus = go_content_detail($post_id, 'Category');
    $country = go_term_names($post_id, 'country');
    $work_mode = go_term_names($post_id, 'work_mode') ?: go_get_meta($post_id, 'remote_option');
    $location = go_get_meta($post_id, 'city');
    $duration = go_get_meta($post_id, 'duration');
    $start_date = go_get_meta($post_id, 'start_date');
    $compensation = go_get_meta($post_id, 'salary') ?: go_content_detail($post_id, 'Compensation');
    $deadline = go_deadline_label($post_id);
    $eligibility = go_trim_text(go_get_meta($post_id, 'eligibility'), 360);
    $summary = go_trim_text(get_the_excerpt($post_id) ?: get_post_field('post_content', $post_id), 430);
    $url = get_permalink($post_id);
    $hashtags = go_social_hashtags($post_id, $type, $country, $category);

    $location_bits = array_filter([$location, $country]);
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
    $parts[] = $hashtags;

    return go_trim_text(implode("\n\n", array_filter($parts)), 2700);
}
add_filter('wp_linkedin_auto_publish_customise_content', 'go_linkedin_opportunity_share_text', 10, 2);


function go_ordered_country_term_ids(): array
{
    $ids = [];
    foreach (array_keys(go_countries()) as $slug) {
        $term = get_term_by('slug', $slug, 'country');
        if ($term && !is_wp_error($term)) {
            $ids[] = (int) $term->term_id;
        }
    }

    return $ids;
}

function go_shortcode_opportunity_search(): string
{
    $action = home_url('/');
    $taxonomies = [
        'opportunity_category' => 'Category',
        'opportunity_type' => 'Opportunity type',
        'country' => 'Country',
        'work_mode' => 'Work mode',
    ];

    ob_start();
    ?>
    <form class="opportunity-search" method="get" action="<?php echo esc_url($action); ?>">
        <input type="hidden" name="post_type" value="opportunity">
        <label>
            <span>Search</span>
            <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Search opportunities">
        </label>
        <?php foreach ($taxonomies as $taxonomy => $label) : ?>
            <?php
            $term_args = [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
            ];
            if ($taxonomy === 'country') {
                $term_args['orderby'] = 'include';
                $term_args['include'] = go_ordered_country_term_ids();
            }
            $terms = get_terms($term_args);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            ?>
            <label>
                <span><?php echo esc_html($label); ?></span>
                <select name="<?php echo esc_attr($taxonomy); ?>">
                    <option value="">All</option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected(get_query_var($taxonomy), $term->slug); ?>>
                            <?php echo esc_html(sprintf('%s (%d)', $term->name, (int) $term->count)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
        <button type="submit">Find opportunities</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('opportunity_search', 'go_shortcode_opportunity_search');

function go_shortcode_contact_form(): string
{
    $status = '';

    if (
        isset($_POST['go_contact_nonce'], $_POST['go_contact_submitted'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['go_contact_nonce'])), 'go_contact_form')
    ) {
        $name = sanitize_text_field(wp_unslash($_POST['go_contact_name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['go_contact_email'] ?? ''));
        $subject = sanitize_text_field(wp_unslash($_POST['go_contact_subject'] ?? ''));
        $message = sanitize_textarea_field(wp_unslash($_POST['go_contact_message'] ?? ''));
        $honeypot = trim((string) wp_unslash($_POST['go_contact_company'] ?? ''));

        if ($honeypot !== '') {
            $status = '<p class="form-notice success">Thank you. Your message has been received.</p>';
        } elseif ($name === '' || $email === '' || $message === '' || !is_email($email)) {
            $status = '<p class="form-notice error">Please add your name, a valid email address, and a message.</p>';
        } else {
            $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
            $mail_subject = $subject !== '' ? $subject : 'Aitomic Jobs website enquiry';
            $body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}\n";
            $sent = wp_mail(
                get_option('admin_email'),
                '[' . $site_name . '] ' . $mail_subject,
                $body,
                ['Reply-To: ' . $name . ' <' . $email . '>']
            );
            $status = $sent
                ? '<p class="form-notice success">Thank you. Your message has been sent.</p>'
                : '<p class="form-notice error">Sorry, the message could not be sent. Please try again later.</p>';
        }
    }

    ob_start();
    echo wp_kses_post($status);
    ?>
    <form class="contact-form" method="post">
        <?php wp_nonce_field('go_contact_form', 'go_contact_nonce'); ?>
        <input type="hidden" name="go_contact_submitted" value="1">
        <label class="hp-field">
            <span>Company</span>
            <input type="text" name="go_contact_company" value="" tabindex="-1" autocomplete="off">
        </label>
        <label>
            <span>Name</span>
            <input type="text" name="go_contact_name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="go_contact_email" required>
        </label>
        <label>
            <span>Subject</span>
            <input type="text" name="go_contact_subject">
        </label>
        <label>
            <span>Message</span>
            <textarea name="go_contact_message" required></textarea>
        </label>
        <button class="button" type="submit">Send message</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('go_contact_form', 'go_shortcode_contact_form');

function go_adsense_trust_pages(): array
{
    return [
        [
            'slug' => 'about',
            'title' => 'About Aitomic Jobs',
            'menu_order' => 10,
            'content' => <<<'HTML'
<p>Aitomic Jobs is a global opportunities directory built to help job seekers, consultants, interns, volunteers, institutions, and employers discover current opportunities across countries and sectors.</p>

<h2>What We Publish</h2>
<p>We focus on jobs, internships, tenders and consultancies, volunteer opportunities, remote work opportunities, training and short courses, and calls for applications. We do not publish scholarships, grants, fellowships, conferences, awards, or competitions on this website.</p>

<h2>How Listings Are Prepared</h2>
<p>Opportunities are gathered from official organizational websites, institutional career pages, procurement portals, and other credible public sources. Each listing is structured to help visitors quickly understand the organization, country, work mode, deadline, eligibility notes, and official application path.</p>

<h2>Our Purpose</h2>
<p>Our goal is to make opportunity discovery easier, especially for users who need one place to search across sectors and countries. Aitomic Jobs does not charge applicants to view opportunities and does not represent itself as the hiring organization unless explicitly stated.</p>

<h2>Source Links</h2>
<p>Every listing should point users to the official source or application page for final instructions. Applicants should always confirm details directly with the source organization before applying, bidding, registering, or submitting documents.</p>
HTML
        ],
        [
            'slug' => 'contact',
            'title' => 'Contact Aitomic Jobs',
            'menu_order' => 20,
            'content' => <<<'HTML'
<p>Use this page to contact Aitomic Jobs about corrections, source attribution, listing updates, partnerships, advertising, privacy requests, or general website enquiries.</p>

<h2>Before You Contact Us</h2>
<p>If your message is about a specific opportunity, please include the opportunity title and the page URL. If your message is about an application, tender, internship, or call, contact the source organization directly as Aitomic Jobs does not manage applications on behalf of listed organizations.</p>

[go_contact_form]
HTML
        ],
        [
            'slug' => 'privacy-policy',
            'title' => 'Privacy Policy',
            'menu_order' => 30,
            'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>This Privacy Policy explains how Aitomic Jobs collects, uses, stores, and shares information when visitors use this website.</p>

<h2>Information We Collect</h2>
<p>We may collect information that visitors provide directly, such as names, email addresses, and messages submitted through the contact form. We may also collect technical information automatically, including IP address, browser type, device information, pages visited, referring pages, approximate location, and interaction data.</p>

<h2>How We Use Information</h2>
<p>We use information to operate and improve the website, respond to enquiries, maintain security, analyze site performance, understand which pages are useful, detect spam or abuse, and improve opportunity listings and user experience.</p>

<h2>Cookies, Analytics, and Advertising</h2>
<p>Aitomic Jobs may use cookies, web beacons, IP addresses, local storage, and similar technologies for site functionality, analytics, measurement, security, and advertising. Cookies help websites remember preferences, measure traffic, and support advertising services.</p>

<p>We may use Google services, including Google Analytics, Google AdSense, Google Ad Manager, Google Ads, or related advertising and measurement products. When these services are used, Google and its partners may collect or receive information from this website and use that information to provide, personalize, measure, and improve ads and services.</p>

<p>Google explains how it uses information from sites and apps that use Google services here: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">How Google uses information from sites or apps that use our services</a>.</p>

<h2>Third-Party Advertising Cookies</h2>
<p>Third parties, including Google, may place and read cookies on users' browsers or use web beacons, IP addresses, and other identifiers as a result of ad serving on this website. These technologies may be used for ad delivery, frequency capping, fraud prevention, reporting, and ad personalization where permitted by law and user settings.</p>

<h2>Managing Cookies and Ads Personalization</h2>
<p>Visitors can manage cookies through their browser settings. Visitors can also manage Google ads personalization through Google's ad settings and related privacy tools. Disabling some cookies may affect how parts of the website function.</p>

<h2>External Links</h2>
<p>Aitomic Jobs links to source websites where opportunities are originally published. We are not responsible for the privacy practices, content, application forms, or security of third-party websites. Visitors should review the privacy policy of any external site before submitting personal information.</p>

<h2>Data Sharing</h2>
<p>We do not sell contact form messages. We may share information with trusted service providers that help operate the website, analytics tools, advertising platforms, security services, hosting providers, and authorities where required by law.</p>

<h2>Data Retention</h2>
<p>We keep information only for as long as reasonably necessary for the purposes described in this policy, including responding to enquiries, maintaining records, preventing abuse, and complying with legal obligations.</p>

<h2>Children's Privacy</h2>
<p>Aitomic Jobs is not directed to children under 13. We do not knowingly collect personal information from children under 13.</p>

<h2>Your Choices</h2>
<p>You may contact us to request access, correction, or deletion of information you provided through the website, subject to applicable law and technical limits.</p>

<h2>Contact</h2>
<p>For privacy questions or requests, please use the <a href="/contact/">Contact page</a>.</p>
HTML
        ],
        [
            'slug' => 'cookie-policy',
            'title' => 'Cookie Policy',
            'menu_order' => 40,
            'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>This Cookie Policy explains how Aitomic Jobs may use cookies and similar technologies.</p>

<h2>What Cookies Are</h2>
<p>Cookies are small text files stored on a browser or device. They can help a website function, remember preferences, measure visits, improve security, and support advertising.</p>

<h2>Types of Cookies We May Use</h2>
<ul>
<li><strong>Essential cookies:</strong> support basic website operation, security, and form protection.</li>
<li><strong>Analytics cookies:</strong> help us understand traffic, popular pages, and website performance.</li>
<li><strong>Advertising cookies:</strong> may be used by advertising partners such as Google to deliver, measure, and improve ads.</li>
<li><strong>Preference cookies:</strong> may remember user choices where applicable.</li>
</ul>

<h2>Google Advertising Cookies</h2>
<p>If Google ads are enabled, Google may use cookies or similar identifiers to serve ads, limit repeated ads, measure ad performance, detect fraud, and personalize ads where allowed. Google provides more information about AdSense cookies here: <a href="https://support.google.com/adsense/answer/7549925" target="_blank" rel="noopener">How AdSense uses cookies</a>.</p>

<h2>Managing Cookies</h2>
<p>Most browsers allow visitors to block, delete, or control cookies through browser settings. Some website features may not work correctly if cookies are disabled.</p>

<h2>Updates</h2>
<p>We may update this Cookie Policy as the website, advertising setup, or legal requirements change.</p>
HTML
        ],
        [
            'slug' => 'terms-of-use',
            'title' => 'Terms of Use',
            'menu_order' => 50,
            'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>By using Aitomic Jobs, you agree to these Terms of Use. If you do not agree, please do not use the website.</p>

<h2>Website Purpose</h2>
<p>Aitomic Jobs provides public opportunity listings and related information. We are not the employer, procuring entity, training provider, or application manager unless explicitly stated.</p>

<h2>No Guarantee of Availability</h2>
<p>Opportunities may change, close, be withdrawn, or contain source updates after publication. We work to keep listings useful, but we do not guarantee that every opportunity remains open, complete, accurate, or suitable for every visitor.</p>

<h2>User Responsibility</h2>
<p>Visitors are responsible for reviewing official source pages, confirming deadlines, checking eligibility, preparing documents, and applying or submitting through the correct official channel.</p>

<h2>No Fees to Apply Through Aitomic Jobs</h2>
<p>Aitomic Jobs does not charge visitors to view opportunities. Be cautious of any third party asking for payment in exchange for employment, tenders, internships, volunteer placement, or guaranteed selection.</p>

<h2>Intellectual Property</h2>
<p>The website design, structure, summaries, and organization are owned by Aitomic Jobs or its licensors. Source organizations retain rights in their own names, logos, notices, and official materials.</p>

<h2>External Websites</h2>
<p>Links to external sites are provided for convenience and source verification. We are not responsible for third-party content, forms, decisions, security, or privacy practices.</p>

<h2>Acceptable Use</h2>
<p>Visitors must not misuse the website, attempt unauthorized access, scrape the site in a way that harms performance, submit spam, or use the site for unlawful activity.</p>

<h2>Changes</h2>
<p>We may update these Terms of Use from time to time. Continued use of the website after changes means you accept the updated terms.</p>
HTML
        ],
        [
            'slug' => 'editorial-policy-disclaimer',
            'title' => 'Editorial Policy and Disclaimer',
            'menu_order' => 60,
            'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<h2>Editorial Approach</h2>
<p>Aitomic Jobs publishes opportunity listings from public and credible sources, including official institutional websites, career portals, procurement pages, and calls for applications. We aim to structure listings consistently so visitors can compare opportunities quickly.</p>

<h2>Verification</h2>
<p>We prioritize source links that lead to the original organization or official application page. When information is unclear, missing, or likely to change, visitors should use the official source link for the final version of the announcement.</p>

<h2>Corrections</h2>
<p>If you find an outdated deadline, incorrect country, broken source link, or other issue, please contact us through the <a href="/contact/">Contact page</a> and include the opportunity URL.</p>

<h2>Disclaimer</h2>
<p>Aitomic Jobs is an information directory. We do not guarantee selection, employment, award of contract, admission, training placement, visa approval, or any other outcome. We are not responsible for decisions made by employers, procuring entities, training providers, or source organizations.</p>

<h2>Advertising and Affiliate Disclosure</h2>
<p>Aitomic Jobs may display advertising or sponsored placements. Advertising helps support website operations. Sponsored or advertising content should not influence whether an opportunity is genuine, useful, or suitable for a visitor.</p>

<h2>Source Attribution</h2>
<p>Where possible, listings include source names and links so visitors can verify information directly. If you represent an organization and want a listing corrected or removed, please contact us.</p>
HTML
        ],
    ];
}

function go_upsert_adsense_trust_pages(): array
{
    $results = [];

    foreach (go_adsense_trust_pages() as $page) {
        $existing = get_page_by_path($page['slug'], OBJECT, 'page');
        $post_data = [
            'post_title' => $page['title'],
            'post_name' => $page['slug'],
            'post_content' => $page['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'menu_order' => $page['menu_order'],
        ];

        if ($existing instanceof WP_Post) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data, true);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data, true);
            $action = 'created';
        }

        if (is_wp_error($post_id)) {
            $results[] = [
                'slug' => $page['slug'],
                'status' => 'error',
                'message' => $post_id->get_error_message(),
            ];
            continue;
        }

        if ($page['slug'] === 'privacy-policy') {
            update_option('wp_page_for_privacy_policy', (int) $post_id);
        }

        $results[] = [
            'slug' => $page['slug'],
            'status' => $action,
            'id' => (int) $post_id,
            'url' => get_permalink((int) $post_id),
        ];
    }

    return $results;
}

function go_maybe_upsert_adsense_trust_pages(): void
{
    $version = '2026-07-20-1';
    if (get_option('go_adsense_trust_pages_version') === $version) {
        return;
    }

    $results = go_upsert_adsense_trust_pages();
    update_option('go_adsense_trust_pages_version', $version, false);
    update_option('go_adsense_trust_pages_last_result', $results, false);
}
add_action('init', 'go_maybe_upsert_adsense_trust_pages', 30);

function go_add_expired_opportunities_rewrite(): void
{
    add_rewrite_rule('^opportunities/expired/?$', 'index.php?post_type=opportunity&expired_opportunities=1', 'top');
}
add_action('init', 'go_add_expired_opportunities_rewrite');

function go_add_expired_opportunities_query_var(array $vars): array
{
    $vars[] = 'expired_opportunities';

    return $vars;
}
add_filter('query_vars', 'go_add_expired_opportunities_query_var');

function go_deadline_archive_meta_query(bool $expired): array
{
    $today = current_time('Y-m-d');

    if ($expired) {
        return [
            [
                'key' => '_go_deadline',
                'value' => '^\d{4}-\d{2}-\d{2}$',
                'compare' => 'REGEXP',
            ],
            [
                'key' => '_go_deadline',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE',
            ],
        ];
    }

    return [
        'relation' => 'OR',
        [
            'key' => '_go_deadline',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key' => '_go_deadline',
            'value' => '',
            'compare' => '=',
        ],
        [
            'key' => '_go_deadline',
            'value' => '^\d{4}-\d{2}-\d{2}$',
            'compare' => 'NOT REGEXP',
        ],
        [
            'key' => '_go_deadline',
            'value' => $today,
            'compare' => '>=',
            'type' => 'DATE',
        ],
    ];
}

function go_filter_opportunity_archive(WP_Query $query): void
{
    $is_opportunity_search = $query->is_search() && $query->get('post_type') === 'opportunity';
    $is_expired_archive = (bool) $query->get('expired_opportunities');

    if (is_admin() || !$query->is_main_query() || (!$query->is_post_type_archive('opportunity') && !$is_opportunity_search && !$is_expired_archive)) {
        return;
    }

    $tax_query = [];
    foreach (['opportunity_category', 'opportunity_type', 'country', 'work_mode'] as $taxonomy) {
        $value = get_query_var($taxonomy);
        if (!$value) {
            continue;
        }

        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => sanitize_title($value),
        ];
    }

    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }

    $query->set('meta_query', go_deadline_archive_meta_query($is_expired_archive));
    $query->set('posts_per_page', 18);

    if ($is_expired_archive) {
        $query->set('orderby', 'meta_value');
        $query->set('meta_key', '_go_deadline');
        $query->set('order', 'DESC');
    }
}
add_action('pre_get_posts', 'go_filter_opportunity_archive');


function go_add_admin_guide_page(): void
{
    add_submenu_page(
        'edit.php?post_type=opportunity',
        __('Opportunity Guide', 'global-opportunities'),
        __('Opportunity Guide', 'global-opportunities'),
        'edit_posts',
        'go-opportunity-guide',
        'go_render_admin_guide_page'
    );
}
add_action('admin_menu', 'go_add_admin_guide_page');

function go_render_admin_guide_page(): void
{
    $categories = [
        'Jobs',
        'Internships',
        'Tenders / Consultancies',
        'Volunteer opportunities',
        'Remote work opportunities',
        'Training / short courses',
        'Calls for applications',
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Opportunity Publishing Guide', 'global-opportunities'); ?></h1>
        <p><?php esc_html_e('Use this guide to keep opportunity posts consistent across the website.', 'global-opportunities'); ?></p>

        <?php
        $import_result = null;
        if (isset($_POST['go_import_samples']) && check_admin_referer('go_import_sample_opportunities')) {
            $import_result = go_import_sample_opportunities();
        }
        ?>
        <?php if ($import_result) : ?>
            <div class="notice notice-success"><p><?php echo esc_html($import_result['message'] . ' Created: ' . $import_result['created'] . '. Skipped: ' . $import_result['skipped'] . '.'); ?></p></div>
        <?php endif; ?>

        <div style="background:#fff;border:1px solid #dcdcde;padding:18px;margin-top:20px;max-width:760px;">
            <h2><?php esc_html_e('Connect sample opportunities to WordPress', 'global-opportunities'); ?></h2>
            <p><?php esc_html_e('Use this to create real WordPress opportunity posts from the current sample opportunities bundled with the plugin.', 'global-opportunities'); ?></p>
            <form method="post">
                <?php wp_nonce_field('go_import_sample_opportunities'); ?>
                <button class="button button-primary" type="submit" name="go_import_samples" value="1"><?php esc_html_e('Import sample opportunities', 'global-opportunities'); ?></button>
            </form>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:20px;">
            <div style="background:#fff;border:1px solid #dcdcde;padding:18px;">
                <h2><?php esc_html_e('Approved categories', 'global-opportunities'); ?></h2>
                <ul style="list-style:disc;padding-left:20px;">
                    <?php foreach ($categories as $category) : ?>
                        <li><?php echo esc_html($category); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div style="background:#fff;border:1px solid #dcdcde;padding:18px;">
                <h2><?php esc_html_e('Posting checklist', 'global-opportunities'); ?></h2>
                <ol style="padding-left:20px;">
                    <li><?php esc_html_e('Use a clear title with the role and organization.', 'global-opportunities'); ?></li>
                    <li><?php esc_html_e('Select category, opportunity type, country, and work mode.', 'global-opportunities'); ?></li>
                    <li><?php esc_html_e('Add the organization, deadline, location, and duration.', 'global-opportunities'); ?></li>
                    <li><?php esc_html_e('Use the official application link and source link.', 'global-opportunities'); ?></li>
                    <li><?php esc_html_e('Set a short excerpt for search results and SEO.', 'global-opportunities'); ?></li>
                </ol>
            </div>

            <div style="background:#fff;border:1px solid #dcdcde;padding:18px;">
                <h2><?php esc_html_e('Redirect elsewhere', 'global-opportunities'); ?></h2>
                <p><?php esc_html_e('Scholarships, Grants, and Fellowships should be menu links or redirects to the existing website.', 'global-opportunities'); ?></p>
                <h2><?php esc_html_e('Do not publish here', 'global-opportunities'); ?></h2>
                <p><?php esc_html_e('Conferences, events, competitions, and awards are outside this website scope.', 'global-opportunities'); ?></p>
            </div>
        </div>
    </div>
    <?php
}


function go_sample_opportunities_file(): string
{
    return plugin_dir_path(__FILE__) . 'assets/data/sample-opportunities.json';
}

function go_sample_opportunity_slug(array $item): string
{
    return sanitize_title(($item['title'] ?? 'opportunity') . '-' . ($item['organization'] ?? 'source'));
}

function go_import_sample_opportunities(): array
{
    $sample_file = go_sample_opportunities_file();
    if (!file_exists($sample_file)) {
        return ['created' => 0, 'skipped' => 0, 'message' => __('Sample opportunity file was not found.', 'global-opportunities')];
    }

    $items = json_decode((string) file_get_contents($sample_file), true);
    if (!is_array($items)) {
        return ['created' => 0, 'skipped' => 0, 'message' => __('Sample opportunity file could not be read.', 'global-opportunities')];
    }

    $created = 0;
    $skipped = 0;

    foreach ($items as $item) {
        if (empty($item['title']) || empty($item['organization'])) {
            $skipped++;
            continue;
        }

        $slug = go_sample_opportunity_slug($item);
        $existing = get_page_by_path($slug, OBJECT, 'opportunity');
        if ($existing instanceof WP_Post) {
            $skipped++;
            continue;
        }

        $title = sanitize_text_field($item['title']);
        $summary = sanitize_textarea_field($item['summary'] ?? '');
        $content = go_build_imported_opportunity_content($item);
        $post_id = wp_insert_post([
            'post_type' => 'opportunity',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
            'post_excerpt' => wp_trim_words($summary, 36),
            'post_content' => $content,
        ], true);

        if (is_wp_error($post_id)) {
            $skipped++;
            continue;
        }

        go_update_imported_opportunity_meta($post_id, $item);
        go_assign_imported_opportunity_terms($post_id, $item);
        $created++;
    }

    return ['created' => $created, 'skipped' => $skipped, 'message' => __('Sample opportunity import complete.', 'global-opportunities')];
}

function go_build_imported_opportunity_content(array $item): string
{
    $summary = esc_html($item['summary'] ?? '');
    $title = esc_html($item['title'] ?? '');
    $organization = esc_html($item['organization'] ?? '');
    $category = esc_html($item['category'] ?? '');
    $country = esc_html($item['country'] ?? '');
    $location = esc_html($item['location'] ?? '');
    $work_mode = esc_html($item['work_mode'] ?? '');
    $type = esc_html($item['opportunity_type'] ?? '');
    $compensation = esc_html($item['compensation'] ?: 'Not specified');
    $posted = esc_html($item['posted_date'] ?? '');
    $type_lower = strtolower((string) ($item['opportunity_type'] ?? ''));
    $category_label = esc_html($item['category'] ?? 'the stated field');

    if (str_contains($type_lower, 'volunteer')) {
        $responsibilities = [
            'Support the programme activities, events, outreach, fieldwork, education, research, or community-facing work connected to the opportunity.',
            'Participate reliably in the stated volunteer schedule, training, onboarding, and team coordination requirements.',
            'Work professionally with staff, participants, communities, students, visitors, or partners connected to the opportunity.',
        ];
        $requirements = [
            'Meet the age, availability, location, onboarding, and participation requirements for the opportunity.',
            'Have interest or experience relevant to ' . $category_label . ', community service, education, research, or public engagement.',
            'Complete the application and any screening, interview, training, or background-check steps required for participation.',
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
            'Meet the deadline, submission format, and procurement or consultancy rules stated by the contracting organization.',
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

    $responsibilities_html = '<ul><li>' . implode('</li><li>', array_map('esc_html', $responsibilities)) . '</li></ul>';
    $requirements_html = '<ul><li>' . implode('</li><li>', array_map('esc_html', $requirements)) . '</li></ul>';
    $benefits = strtolower((string) $compensation) !== 'not specified'
        ? '<ul><li>' . $compensation . '</li></ul>'
        : '<p>Compensation, benefits, fees, allowances, or volunteer arrangements are not specified.</p>';

    return '<h2>Short Summary</h2>'
        . '<p>' . esc_html(wp_trim_words($summary, 42)) . '</p>'
        . '<h2>Key Details</h2>'
        . '<ul>'
        . '<li><strong>Position:</strong> ' . $title . '</li>'
        . '<li><strong>Organization:</strong> ' . $organization . '</li>'
        . '<li><strong>Country:</strong> ' . $country . '</li>'
        . '<li><strong>Location:</strong> ' . $location . '</li>'
        . '<li><strong>Work arrangement:</strong> ' . $work_mode . '</li>'
        . '<li><strong>Opportunity type:</strong> ' . $type . '</li>'
        . '<li><strong>Category:</strong> ' . $category . '</li>'
        . '<li><strong>Compensation:</strong> ' . $compensation . '</li>'
        . '<li><strong>Posted date:</strong> ' . $posted . '</li>'
        . '</ul>'
        . '<h2>Description</h2><p>' . $summary . '</p>'
        . '<h2>Responsibilities</h2>' . $responsibilities_html
        . '<h2>Requirements / Eligibility</h2>' . $requirements_html
        . '<h2>Benefits / Compensation</h2>' . $benefits
        . '<h2>How To Apply</h2><p>Use the Apply now button and follow the stated application process.</p>';
}

function go_update_imported_opportunity_meta(int $post_id, array $item): void
{
    $meta_map = [
        'organization' => $item['organization'] ?? '',
        'city' => $item['location'] ?? '',
        'remote_option' => $item['work_mode'] ?? '',
        'deadline' => $item['deadline'] ?? '',
        'start_date' => $item['posted_date'] ?? '',
        'duration' => '',
        'employment_type' => $item['opportunity_type'] ?? '',
        'salary' => $item['compensation'] ?? '',
        'eligibility' => $item['summary'] ?? '',
        'application_link' => $item['application_link'] ?? '',
        'source_link' => $item['source_url'] ?? '',
        'contact_email' => '',
        'status' => 'Open',
    ];

    foreach ($meta_map as $key => $value) {
        update_post_meta($post_id, GO_META_PREFIX . $key, sanitize_text_field((string) $value));
    }
}

function go_assign_imported_opportunity_terms(int $post_id, array $item): void
{
    $term_map = [
        'opportunity_type' => $item['opportunity_type'] ?? '',
        'opportunity_category' => $item['category'] ?? '',
        'country' => $item['country'] ?? '',
        'work_mode' => $item['work_mode'] ?? '',
    ];

    foreach ($term_map as $taxonomy => $term_name) {
        $term_name = sanitize_text_field((string) $term_name);
        if ($term_name === '') {
            continue;
        }

        $term = term_exists($term_name, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($term_name, $taxonomy);
        }
        if (!is_wp_error($term)) {
            wp_set_object_terms($post_id, [(int) $term['term_id']], $taxonomy, true);
        }
    }
}

function go_linkedin_scheduler_intervals(array $schedules): array
{
    $schedules['go_every_five_minutes'] = [
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display' => __('Every 5 minutes', 'global-opportunities'),
    ];

    return $schedules;
}
add_filter('cron_schedules', 'go_linkedin_scheduler_intervals');

function go_linkedin_scheduler_schedule(): void
{
    if (!wp_next_scheduled('go_linkedin_scheduler_tick')) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'go_every_five_minutes', 'go_linkedin_scheduler_tick');
    }
}
add_action('init', 'go_linkedin_scheduler_schedule');

function go_linkedin_scheduler_deactivate(): void
{
    wp_clear_scheduled_hook('go_linkedin_scheduler_tick');
}
register_deactivation_hook(__FILE__, 'go_linkedin_scheduler_deactivate');

function go_linkedin_scheduler_meta(int $post_id, string $key): string
{
    return trim((string) get_post_meta($post_id, GO_META_PREFIX . $key, true));
}

function go_linkedin_scheduler_term_list(int $post_id, string $taxonomy): string
{
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function go_linkedin_scheduler_trim(string $text, int $limit): string
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

function go_linkedin_scheduler_is_clean(int $post_id): bool
{
    $content = (string) get_post_field('post_content', $post_id);
    foreach ([
        'This is an official',
        'Review the official source page',
        'Review the official opportunity page',
        'Eligibility requirements are set',
        'Contract terms, salary, fees',
        'Use the application button on this page',
    ] as $needle) {
        if (stripos($content, $needle) !== false) {
            return false;
        }
    }

    return true;
}

function go_linkedin_scheduler_is_expired(int $post_id): bool
{
    $deadline = go_linkedin_scheduler_meta($post_id, 'deadline');
    if ($deadline === '') {
        return false;
    }

    $timestamp = strtotime($deadline . ' 23:59:59');

    return $timestamp && $timestamp < current_time('timestamp');
}

function go_linkedin_scheduler_sent_count(int $post_id): int
{
    $sent = get_post_meta($post_id, '_sent_to_linkedin', true);

    return is_array($sent) ? count($sent) : 0;
}

function go_linkedin_scheduler_scheduled_time(string $value): ?int
{
    if (trim($value) === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($value, new DateTimeZone('Africa/Kampala'));
        return $date->getTimestamp();
    } catch (Exception $e) {
        return null;
    }
}

function go_linkedin_scheduler_hashtags(string $type, string $category, string $country): string
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
    } else {
        $tags = array_merge($tags, ['#Jobs', '#Hiring']);
    }

    if (str_contains($category_lower, 'health')) {
        $tags[] = '#GlobalHealth';
    } elseif (str_contains($category_lower, 'education')) {
        $tags[] = '#Education';
    } elseif (str_contains($category_lower, 'technology') || str_contains($category_lower, 'information')) {
        $tags[] = '#Technology';
    } elseif (str_contains($category_lower, 'development') || str_contains($category_lower, 'humanitarian')) {
        $tags[] = '#InternationalDevelopment';
    }

    $country_tag = preg_replace('/[^A-Za-z0-9]/', '', $country);
    if ($country_tag !== '' && !in_array(strtolower($country), ['global/international', 'global', 'international', 'remote', 'various', 'multiple'], true)) {
        $tags[] = '#' . $country_tag;
    }

    return implode(' ', array_slice(array_unique($tags), 0, 12));
}

function go_linkedin_scheduler_message(int $post_id): string
{
    $title = html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $organization = html_entity_decode(go_linkedin_scheduler_meta($post_id, 'organization'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $type = go_linkedin_scheduler_term_list($post_id, 'opportunity_type') ?: go_linkedin_scheduler_meta($post_id, 'employment_type');
    $category = go_linkedin_scheduler_term_list($post_id, 'opportunity_category');
    $country = go_linkedin_scheduler_term_list($post_id, 'country') ?: go_linkedin_scheduler_meta($post_id, 'country');
    $work_mode = go_linkedin_scheduler_term_list($post_id, 'work_mode') ?: go_linkedin_scheduler_meta($post_id, 'work_mode');
    $location = go_linkedin_scheduler_meta($post_id, 'city');
    $deadline = go_linkedin_scheduler_meta($post_id, 'deadline') ?: 'Not specified';
    $compensation = go_linkedin_scheduler_meta($post_id, 'salary');
    $summary = go_linkedin_scheduler_trim(get_the_excerpt($post_id) ?: (string) get_post_field('post_content', $post_id), 430);
    $url = get_permalink($post_id);

    $detail_lines = array_filter([
        $organization !== '' ? 'Organization: ' . $organization : '',
        $type !== '' ? 'Opportunity type: ' . $type : '',
        $category !== '' ? 'Sector: ' . $category : '',
        trim($location . $country) !== '' ? 'Location: ' . implode(', ', array_filter([$location, $country])) : '',
        $work_mode !== '' ? 'Work mode: ' . $work_mode : '',
        'Deadline: ' . $deadline,
        $compensation !== '' ? 'Compensation: ' . $compensation : '',
    ]);

    return go_linkedin_scheduler_trim(implode("\n\n", array_filter([
        'Opportunity alert: ' . $title,
        $summary,
        "Key details\n" . implode("\n", $detail_lines),
        "What to review on Aitomic Jobs\nFull description, responsibilities or submission instructions, eligibility requirements, benefits or compensation notes, and application guidance.",
        "Full details and official application link\n" . $url,
        go_linkedin_scheduler_hashtags($type, $category, $country),
    ])), 2900);
}

function go_linkedin_scheduler_run(int $limit = 4): array
{
    if (get_transient('go_linkedin_scheduler_lock')) {
        return ['status' => 'locked', 'published' => 0, 'results' => []];
    }

    set_transient('go_linkedin_scheduler_lock', 1, 4 * MINUTE_IN_SECONDS);
    $published = 0;
    $results = [];

    try {
        if (!function_exists('wp_linkedin_autopublish_post_to_linkedin_common')) {
            return ['status' => 'missing-linkedin-plugin', 'published' => 0, 'results' => []];
        }

        $query = new WP_Query([
            'post_type' => 'opportunity',
            'post_status' => 'publish',
            'posts_per_page' => 120,
            'orderby' => 'meta_value',
            'meta_key' => '_go_linkedin_scheduled_for',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_go_linkedin_queued_at', 'compare' => 'EXISTS'],
                ['key' => '_go_linkedin_scheduled_for', 'compare' => 'EXISTS'],
                [
                    'relation' => 'OR',
                    ['key' => '_go_linkedin_posted_at', 'compare' => 'NOT EXISTS'],
                    ['key' => '_go_linkedin_posted_at', 'value' => '', 'compare' => '='],
                ],
            ],
        ]);

        $now = current_time('timestamp');
        foreach ($query->posts as $post_id) {
            $post_id = (int) $post_id;
            $scheduled_raw = (string) get_post_meta($post_id, '_go_linkedin_scheduled_for', true);
            $scheduled = go_linkedin_scheduler_scheduled_time($scheduled_raw);
            if (!$scheduled || $scheduled > $now) {
                continue;
            }

            if (go_linkedin_scheduler_sent_count($post_id) > 0) {
                update_post_meta($post_id, '_go_linkedin_posted_at', current_time('mysql'));
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'already-posted');
                continue;
            }

            if (go_linkedin_scheduler_is_expired($post_id)) {
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'skipped-expired');
                continue;
            }

            if (!go_linkedin_scheduler_is_clean($post_id)) {
                update_post_meta($post_id, '_go_linkedin_scheduler_status', 'skipped-thin-content');
                continue;
            }

            update_post_meta($post_id, '_custom_linkedin_share_message', go_linkedin_scheduler_message($post_id));
            update_post_meta($post_id, '_go_linkedin_scheduler_status', 'posting');
            update_post_meta($post_id, '_go_linkedin_last_attempt_at', current_time('mysql'));

            $before_count = go_linkedin_scheduler_sent_count($post_id);
            $plugin_result = wp_linkedin_autopublish_post_to_linkedin_common($post_id);
            $after_count = go_linkedin_scheduler_sent_count($post_id);

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
                'title' => get_the_title($post_id),
                'status' => $status,
                'scheduled_for' => $scheduled_raw,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }
    } finally {
        delete_transient('go_linkedin_scheduler_lock');
    }

    update_option('go_linkedin_scheduler_last_run', [
        'time' => current_time('mysql'),
        'published' => $published,
        'results' => $results,
    ], false);

    return ['status' => 'ok', 'published' => $published, 'results' => $results];
}

function go_linkedin_scheduler_tick(): void
{
    go_linkedin_scheduler_run(4);
}
add_action('go_linkedin_scheduler_tick', 'go_linkedin_scheduler_tick');
