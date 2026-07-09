<?php
if (!defined('ABSPATH')) {
    exit;
}

function gotheme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'global-opportunities-theme'),
    ]);
}
add_action('after_setup_theme', 'gotheme_setup');

function gotheme_enqueue_assets(): void
{
    wp_enqueue_style(
        'gotheme-main',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        '0.1.0'
    );
}
add_action('wp_enqueue_scripts', 'gotheme_enqueue_assets');

function gotheme_term_list(string $taxonomy): string
{
    $terms = get_the_terms(get_the_ID(), $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return '';
    }

    return esc_html(implode(', ', wp_list_pluck($terms, 'name')));
}

function gotheme_meta(string $key): string
{
    if (!function_exists('go_get_meta')) {
        return '';
    }

    return go_get_meta(get_the_ID(), $key);
}

function gotheme_opportunity_meta_list(): string
{
    if (!function_exists('go_render_opportunity_meta_list')) {
        return '';
    }

    return go_render_opportunity_meta_list(get_the_ID());
}

function gotheme_site_icon(): void
{
    echo '<link rel="icon" href="' . esc_url(get_template_directory_uri() . '/assets/images/aitomic-jobs-logo-icon.png') . '">';
    echo '<link rel="apple-touch-icon" href="' . esc_url(get_template_directory_uri() . '/assets/images/aitomic-jobs-logo-icon.png') . '">';
}
add_action('wp_head', 'gotheme_site_icon');


function gotheme_sample_opportunities(): array
{
    $sample_file = get_template_directory() . '/assets/data/sample-opportunities.json';
    if (!file_exists($sample_file)) {
        return [];
    }

    $json = file_get_contents($sample_file);
    $items = json_decode($json, true);

    return is_array($items) ? $items : [];
}


function gotheme_sample_points(string $summary, array $fallbacks): array
{
    $summary = trim(wp_strip_all_tags($summary));
    $sentences = preg_split('/(?<=[.!?])\s+/', $summary, -1, PREG_SPLIT_NO_EMPTY);
    $points = [];

    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if ($sentence !== '' && strlen($sentence) > 35) {
            $points[] = $sentence;
        }
        if (count($points) >= 3) {
            break;
        }
    }

    if (count($points) < 3) {
        foreach ($fallbacks as $fallback) {
            if (!in_array($fallback, $points, true)) {
                $points[] = $fallback;
            }
            if (count($points) >= 3) {
                break;
            }
        }
    }

    return array_slice($points, 0, 3);
}

function gotheme_render_points(array $points): void
{
    echo '<ul>';
    foreach ($points as $point) {
        echo '<li>' . esc_html($point) . '</li>';
    }
    echo '</ul>';
}

function gotheme_sample_opportunity_url(array $item): string
{
    $slug_source = ($item['title'] ?? 'sample-opportunity') . '-' . ($item['organization'] ?? 'source');

    return add_query_arg('sample_opportunity', sanitize_title($slug_source), home_url('/sample-opportunity/'));
}

function gotheme_find_sample_opportunity(string $slug): array
{
    foreach (gotheme_sample_opportunities() as $item) {
        $candidate = sanitize_title(($item['title'] ?? 'sample-opportunity') . '-' . ($item['organization'] ?? 'source'));
        if ($candidate === $slug) {
            return $item;
        }
    }

    return [];
}

function gotheme_render_sample_opportunity_card(array $item): void
{
    $title = $item['title'] ?? '';
    $organization = $item['organization'] ?? '';
    $type = $item['opportunity_type'] ?? 'Opportunity';
    $country = $item['country'] ?? '';
    $location = $item['location'] ?? '';
    $posted = $item['posted_date'] ?? '';
    $summary = $item['summary'] ?? '';
    $details_link = gotheme_sample_opportunity_url($item);
    ?>
    <article class="opportunity-card sample-opportunity-card">
        <div class="card-kicker">
            <span class="pill"><?php echo esc_html($type); ?></span>
            <span><?php echo esc_html($posted); ?></span>
        </div>
        <div>
            <h3><a href="<?php echo esc_url($details_link); ?>"><?php echo esc_html($title); ?></a></h3>
            <p class="card-org"><?php echo esc_html($organization); ?></p>
        </div>
        <p><?php echo esc_html(wp_trim_words($summary, 26)); ?></p>
        <div class="card-footer">
            <span><?php echo esc_html($country ?: $location); ?></span>
            <a href="<?php echo esc_url($details_link); ?>">Details</a>
        </div>
    </article>
    <?php
}
