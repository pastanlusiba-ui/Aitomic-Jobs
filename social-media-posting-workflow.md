# Aitomic Jobs LinkedIn Posting Workflow

## Goal

Publish selected opportunities from Aitomic Jobs to LinkedIn while driving traffic back to the Aitomic Jobs opportunity detail page.

## Channel

- LinkedIn page: the only active social channel for this workflow.

## Posting Rule

Every post should link to the Aitomic Jobs detail page, not directly to the source website. The detail page then provides the structured summary, key details, and official application/source links.

## Queue Generation

Use the WP-CLI helper:

```bash
wp eval-file /home/u710255073/aitomic-tools/generate_social_media_queue.php limit=108 schedule_start=2026-07-20 window_start=07:00 window_end=20:00 interval_minutes=30 per_slot=4 timezone=Africa/Kampala
```

Useful options:

- `limit=50`: number of opportunities to include.
- `offset=0`: skip the latest N opportunities.
- `mark=yes`: mark generated opportunities as queued so the next run skips them.
- `include_expired=yes`: include expired opportunities if needed for archive campaigns.
- `schedule_start=YYYY-MM-DD`: first date for LinkedIn scheduling.
- `times=09:00,13:00,17:00`: daily posting slots, in the WordPress site timezone.
- `window_start=07:00 window_end=20:00 interval_minutes=30`: generate slots every 30 minutes across the day.
- `per_slot=4`: schedule four opportunities at each generated slot.
- `timezone=Africa/Kampala`: timezone used for scheduled slots.
- `skip_weekends=yes`: default; moves scheduled posts to weekdays.
- `include_queued=yes`: include opportunities that were already marked queued when rebuilding a schedule.
- `include_thin=yes`: override the default quality filter. Avoid this unless manually reviewed.
- `include_posted=yes`: override the default LinkedIn safety filter. Avoid this unless intentionally reposting.
- Opportunities are skipped when their deadline would have passed before the scheduled LinkedIn slot.

The script creates:

- A LinkedIn-only CSV queue for manual posting or upload into a LinkedIn-capable scheduler.
- A LinkedIn-only JSON queue for automation.
- Each row includes `scheduled_for`, `status`, `linkedin_text`, the Aitomic Jobs URL, and the original source URL.

## Review Checklist

- Confirm the opportunity is still open if it has a deadline.
- Confirm the country and opportunity type look right.
- Use the `linkedin_text` field as the post copy.
- Keep the Aitomic Jobs URL in the post so traffic returns to the website.
- Update `status`, `scheduled_for`, and `posted_url` after scheduling or publishing.
- By default, the queue excludes posts that still contain old placeholder wording.

## Automated Scheduler

The live site uses a WordPress-native scheduler event registered by the Global Opportunities plugin:

```bash
wp cron event list --fields=hook,next_run_relative,recurrence | grep go_linkedin_scheduler_tick
```

The scheduler:

- Finds queued opportunities whose `_go_linkedin_scheduled_for` time has arrived.
- Posts up to `limit=4` opportunities per run.
- Uses the connected WP LinkedIn Auto Publish plugin to publish to the Aitomic Jobs LinkedIn page.
- Generates detailed LinkedIn copy from each opportunity before posting.
- Skips expired opportunities and thin placeholder-content posts.
- Marks successful posts with `_go_linkedin_posted_at` and `_go_linkedin_scheduler_status=posted`.
- Uses a short lock so overlapping cron runs do not duplicate posts.
- Runs through the WordPress hook `go_linkedin_scheduler_tick` every 5 minutes when WP-Cron is triggered.

Useful checks:

```bash
wp eval-file /home/u710255073/aitomic-tools/run_linkedin_scheduler.php limit=4 dry_run=yes
wp eval-file /home/u710255073/aitomic-tools/run_linkedin_scheduler.php limit=4 dry_run=yes now="2026-07-20 07:00 Africa/Kampala"
wp eval 'echo wp_json_encode(go_linkedin_scheduler_run(4), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);'
```
