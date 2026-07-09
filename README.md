# Global Opportunities Website

This project contains the starter WordPress build for a global jobs and opportunities website managed through WordPress.

## Included

- `wordpress/wp-content/plugins/global-opportunities/`
  - Registers the `opportunity` custom post type.
  - Adds taxonomies for category, opportunity type, country, and work mode.
  - Adds opportunity detail fields such as deadline, organization, duration, application link, source link, and compensation.
  - Provides the `[opportunity_search]` shortcode for searchable filters.

- `wordpress/wp-content/themes/global-opportunities-theme/`
  - Provides the public website design.
  - Includes a homepage, opportunity archive page, search results, taxonomy pages, single opportunity page, and opportunity cards.

- `opportunity-posting-templates.md`
  - Editorial templates for each approved opportunity category.

## Approved Opportunity Categories

- Jobs
- Internships
- Tenders / Consultancies
- Volunteer opportunities
- Remote work opportunities
- Training / short courses
- Calls for applications

## Redirect Categories

These should be handled as menu links or redirects to the existing website:

- Scholarships
- Grants
- Fellowships

## Removed Categories

These are intentionally outside the project:

- Conferences / events
- Competitions / awards


## Brand Assets

The theme uses `assets/images/aitomic-jobs-logo-horizontal.png` in the website header and `assets/images/aitomic-jobs-logo-icon.png` as the favicon/apple-touch icon.

## Website Template Direction

The current theme uses a distinctive corporate job-board style with:

- Ivory, deep blue, coral, mint, and warm gold as the core palette.
- A prominent search panel inspired by established job board homepages.
- Horizontal category tiles for the seven approved opportunity types.
- Dense list-style opportunity rows for easier scanning.
- A sticky header with a compact directory action.
- A WordPress admin guide under Opportunities > Opportunity Guide.

## Global Search

The theme displays the opportunity search form on every page below the header. Dropdowns are populated for Category, Opportunity type, Country, and Work mode. Country includes Remote at the top before the full global country list, including commonly used entries such as Kosovo and Taiwan.


## Current Opportunity Samples

The project includes `data/current_opportunities_sample.csv` and `assets/data/sample-opportunities.json` with a small set of current sample opportunities for previewing the theme before WordPress posts are imported. These sample rows are attributed to their source and link back to the original listing.

## Source Database

The project includes a first source database for populating vacancies and related opportunities:

- `data/job_source_database.csv` for automation and spreadsheet review.
- `data/job_source_database.md` for editorial review.
- `data/source_expansion_round2.csv` records the additional researched sources added after the workbook merge.

The database includes global job boards, remote job boards, UN and international organization sources, NGO/development sources, government job portals, tender portals, training sources, and the imported `global_country_jobs_institutions_directory_final_deduplicated.xlsx` directory.

## WordPress Setup

1. Copy `wordpress/wp-content/plugins/global-opportunities` into the target WordPress site's `wp-content/plugins/` directory.
2. Copy `wordpress/wp-content/themes/global-opportunities-theme` into the target WordPress site's `wp-content/themes/` directory.
3. Activate the `Global Opportunities` plugin.
4. Activate the `Global Opportunities Theme`.
5. Go to Settings > Permalinks and save once to refresh URLs.
6. Add a page for the homepage if needed and set it under Settings > Reading.
7. Create menu links for Scholarships, Grants, and Fellowships that redirect to the existing website.

## WordPress Connection

See `wordpress-setup.md` for local Docker setup and hosted WordPress installation instructions. The plugin includes an admin importer at Opportunities > Opportunity Guide for creating sample opportunities as real WordPress posts.
