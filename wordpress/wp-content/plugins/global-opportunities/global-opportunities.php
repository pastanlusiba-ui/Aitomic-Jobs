<?php
/**
 * Plugin Name: Global Opportunities
 * Description: Adds WordPress content types, taxonomies, fields, and shortcodes for a global jobs and opportunities directory.
 * Version: 0.1.0
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
                            <?php echo esc_html($term->name); ?>
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
    $query->set('posts_per_page', 12);

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
    $source = esc_html($item['source'] ?? '');

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
        . '<h2>Responsibilities</h2><ul><li>Review the official opportunity details and complete role-specific tasks described by the organization.</li><li>Work professionally with the hiring organization or client team.</li><li>Meet deadlines and communication expectations for the role.</li></ul>'
        . '<h2>Requirements / Eligibility</h2><ul><li>Relevant skills or experience for the role.</li><li>Ability to work in the stated location or work mode.</li><li>Applicants should review the official source listing before applying.</li></ul>'
        . '<h2>Benefits</h2><ul><li>' . $compensation . '</li><li>Opportunity details and benefits should be confirmed on the official source website.</li></ul>'
        . '<h2>How To Apply</h2><p>Use the application button on this page to continue to the official source website.</p>'
        . '<h2>Source</h2><p>Source: ' . $source . '.</p>';
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
