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
wp eval-file /home/u710255073/aitomic-tools/generate_social_media_queue.php limit=50
```

Useful options:

- `limit=50`: number of opportunities to include.
- `offset=0`: skip the latest N opportunities.
- `mark=yes`: mark generated opportunities as queued so the next run skips them.
- `include_expired=yes`: include expired opportunities if needed for archive campaigns.

The script creates:

- A LinkedIn-only CSV queue for manual posting or upload into a LinkedIn-capable scheduler.
- A LinkedIn-only JSON queue for automation.

## Review Checklist

- Confirm the opportunity is still open if it has a deadline.
- Confirm the country and opportunity type look right.
- Use the `linkedin_text` field as the post copy.
- Keep the Aitomic Jobs URL in the post so traffic returns to the website.
- Update `status`, `scheduled_for`, and `posted_url` after scheduling or publishing.

## Next Automation Step

Once the LinkedIn page is ready, connect the CSV/JSON queue to a scheduler that supports LinkedIn pages, or use a custom LinkedIn API posting script.
