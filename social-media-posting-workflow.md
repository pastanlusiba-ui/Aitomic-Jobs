# Aitomic Jobs Social Posting Workflow

## Goal

Publish selected opportunities from Aitomic Jobs to social media while driving traffic back to the Aitomic Jobs opportunity detail page.

## Recommended Channels

- LinkedIn page: primary professional channel for jobs, consultancies, internships, and tenders.
- Facebook page: broad public reach and community sharing.
- X: short opportunity alerts with country/type hashtags.
- WhatsApp / Telegram channels: concise opportunity alerts for subscribers.

## Posting Rule

Every post should link to the Aitomic Jobs detail page, not directly to the source website. The detail page then provides the structured summary, key details, and official application/source links.

## Queue Generation

Use the WP-CLI helper:

```bash
wp eval-file wp-content/uploads/generate_social_media_queue.php limit=50
```

Useful options:

- `limit=50`: number of opportunities to include.
- `offset=0`: skip the latest N opportunities.
- `mark=yes`: mark generated opportunities as queued so the next run skips them.
- `include_expired=yes`: include expired opportunities if needed for archive campaigns.

The script creates:

- A CSV queue for manual posting or upload into a scheduler.
- A JSON queue for automation or API posting.

## Review Checklist

- Confirm the opportunity is still open if it has a deadline.
- Confirm the country and opportunity type look right.
- Use the LinkedIn text for LinkedIn.
- Use the Facebook text for Facebook.
- Use the X text for X.
- Use the WhatsApp / Telegram text for direct channels.

## Next Automation Step

Once the social media accounts are ready, connect the CSV/JSON queue to a scheduler such as Buffer, Meta Business Suite, LinkedIn Page admin tools, or a custom API posting script.
