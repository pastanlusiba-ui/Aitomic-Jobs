# WordPress Connection Setup

This project is now ready to run as a WordPress-managed website.

## Local setup with Docker

Docker is not installed on the current machine, but when Docker Desktop is available, run:

```bash
docker compose up -d
```

Then open:

```text
http://localhost:8088
```

Complete the WordPress installer, then in WordPress Admin:

1. Go to Plugins and activate **Global Opportunities**.
2. Go to Appearance > Themes and activate **Global Opportunities Theme**.
3. Go to Settings > Permalinks and click Save.
4. Go to Opportunities > Opportunity Guide.
5. Click **Import sample opportunities** to create real WordPress opportunity posts.

## Hosted WordPress setup

Upload these folders to the hosted WordPress site:

- `wordpress/wp-content/plugins/global-opportunities` -> `wp-content/plugins/global-opportunities`
- `wordpress/wp-content/themes/global-opportunities-theme` -> `wp-content/themes/global-opportunities-theme`

Then activate the plugin and theme in WordPress Admin.
