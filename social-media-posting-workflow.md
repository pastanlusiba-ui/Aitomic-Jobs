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
wp eval-file /home/u710255073/aitomic-tools/generate_social_media_queue.php limit=30 schedule_start=2026-07-20 times=09:00,13:00,17:00 timezone=Africa/Kampala
```

Useful options:

- `limit=50`: number of opportunities to include.
- `offset=0`: skip the latest N opportunities.
- `mark=yes`: mark generated opportunities as queued so the next run skips them.
- `include_expired=yes`: include expired opportunities if needed for archive campaigns.
- `schedule_start=YYYY-MM-DD`: first date for LinkedIn scheduling.
- `times=09:00,13:00,17:00`: daily posting slots, in the WordPress site timezone.
- `timezone=Africa/Kampala`: timezone used for scheduled slots.
- `skip_weekends=yes`: default; moves scheduled posts to weekdays.
- `include_thin=yes`: override the default quality filter. Avoid this unless manually reviewed.
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

## Next Automation Step

Once the LinkedIn page is ready, connect the CSV/JSON queue to a scheduler that supports LinkedIn pages, or use a custom LinkedIn API posting script.
