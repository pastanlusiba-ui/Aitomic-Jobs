# Hostinger GitHub Deployment

This project deploys the WordPress plugin and theme from GitHub to Hostinger using GitHub Actions over SSH.

## Hostinger SSH Details

Use these GitHub repository secrets:

```text
HOSTINGER_SSH_HOST=92.113.28.250
HOSTINGER_SSH_USER=u710255073
HOSTINGER_SSH_PORT=65002
HOSTINGER_SSH_KEY=<private deploy key>
HOSTINGER_WP_PATH=<WordPress root path on Hostinger>
```

`HOSTINGER_WP_PATH` is usually one of these:

```text
/home/u710255073/domains/YOURDOMAIN.com/public_html
/home/u710255073/public_html
```

## GitHub Secrets

In GitHub, open:

```text
https://github.com/pastanlusiba-ui/Aitomic-Jobs
```

Go to **Settings > Secrets and variables > Actions > New repository secret**.

Add:

```text
HOSTINGER_SSH_HOST
HOSTINGER_SSH_USER
HOSTINGER_SSH_PORT
HOSTINGER_SSH_KEY
HOSTINGER_WP_PATH
```

## Deployment

After the secrets are added, deployment runs automatically on every push to `main`.

You can also run it manually from:

**Actions > Deploy WordPress Theme and Plugin to Hostinger > Run workflow**

The workflow deploys only:

```text
wordpress/wp-content/plugins/global-opportunities
wordpress/wp-content/themes/global-opportunities-theme
```

It does not overwrite WordPress core, uploads, or the WordPress database.

## WordPress Activation

After the first successful deployment:

1. Open WordPress Admin.
2. Activate plugin: **Global Opportunities**.
3. Activate theme: **Global Opportunities Theme**.
4. Go to **Settings > Permalinks** and click **Save Changes**.
5. Go to **Opportunities > Opportunity Guide**.
6. Click **Import sample opportunities**.
