from __future__ import annotations

import csv
import json
from collections import Counter
from pathlib import Path

from hunt_july_20_22_2026_opportunities import infer_country, make_item, keep


PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
OUT_JSON = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt.json"
OUT_CSV = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt.csv"
AUDIT_JSON = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt_audit.json"


SEEDS = [
    {
        "title": "Senior Technical Application Engineer - Nuclear Control Upgrades (REMOTE)",
        "organization": "GE Vernova",
        "url": "https://careers.gevernova.com/senior-technical-application-engineer-nuclear-control-upgrades-remote/job/R5047872",
        "source": "Google web search - GE Vernova careers",
        "posted_date": "2026-07-21",
        "deadline": "2026-10-30",
        "location": "Remote - United States",
        "remote": True,
        "compensation": "$131,700 - $219,300",
        "description": "Technical sales and application engineering role supporting nuclear control upgrade opportunities for GE Vernova's Software & Control Solutions business.",
    },
    {
        "title": "Sr Talent Acquisition Partner",
        "organization": "GE Vernova",
        "url": "https://careers.gevernova.com/sr-talent-acquisition-partner/job/R5048088",
        "source": "Google web search - GE Vernova careers",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-29",
        "location": "Remote - United States",
        "remote": True,
        "compensation": "$79,500 - $132,400",
        "description": "Strategic talent acquisition role responsible for recruitment support, sourcing, screening, hiring-manager advisory, and recruitment performance improvement.",
    },
    {
        "title": "Global Head of Brand Governance & Operations",
        "organization": "GE Vernova",
        "url": "https://careers.gevernova.com/global-head-of-brand-governance-operations/job/R5048018",
        "source": "Google web search - GE Vernova careers",
        "posted_date": "2026-07-21",
        "deadline": "2026-08-07",
        "location": "Cambridge, MA; New York, NY; Remote - United States",
        "remote": True,
        "description": "Brand governance and operations leadership role for GE Vernova's global communications and brand function.",
    },
    {
        "title": "Senior Software Engineer (Java)",
        "organization": "Caterpillar",
        "url": "https://careers.caterpillar.com/en/jobs/r0000382279/senior-software-engineer-java/",
        "source": "Google web search - Caterpillar careers",
        "posted_date": "2026-07-20",
        "deadline": "2026-07-22",
        "location": "Chennai, Tamil Nadu, India",
        "description": "Java and AWS backend engineering role focused on Spring Boot, microservices, cloud-native services, APIs, testing, CI/CD, and production troubleshooting.",
    },
    {
        "title": "Clinical Learning Portfolio Partner - Rheumatology",
        "organization": "Johnson & Johnson",
        "url": "https://jj.wd5.myworkdayjobs.com/en-US/DisplacedEmployees/job/Horsham-Pennsylvania-United-States-of-America/Portfolio-Partner--Rheumatology-Clinical_R-083376",
        "source": "Google web search - Johnson & Johnson Workday",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-22",
        "location": "Horsham, Pennsylvania; Titusville, New Jersey, United States",
        "description": "Hybrid clinical learning portfolio role in rheumatology within Johnson & Johnson's healthcare innovation and learning environment.",
    },
    {
        "title": "Executive Assistant",
        "organization": "New York Civil Liberties Union",
        "url": "https://recruiting.paylocity.com/recruiting/jobs/Details/4311910/New-York-Civil-Liberties-Union-Foundation/Executive-Assistant",
        "source": "LinkedIn social search -> Paylocity original posting",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-22",
        "location": "New York, NY, United States",
        "description": "Executive Assistant role supporting administration and executive operations for the New York Civil Liberties Union.",
    },
    {
        "title": "Presumptive Elig Coord",
        "organization": "Kansas Department of Health and Environment",
        "url": "https://www.linkedin.com/jobs/view/presumptive-elig-coord-at-kansas-department-of-health-and-environment-4437081216",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-22",
        "location": "Finney County, Kansas, United States",
        "description": "Eligibility coordination role with the Kansas Department of Health and Environment covering application, documentation, and state public-health programme requirements.",
    },
    {
        "title": "Fishery Analyst and/or Economist, North Pacific Fishery Management Council",
        "organization": "Association of Environmental and Resource Economists",
        "url": "https://www.linkedin.com/jobs/view/fishery-analyst-and-or-economist-north-pacific-fishery-management-council-at-association-of-environmental-and-resource-economists-4431941124",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-20",
        "deadline": "2026-07-22",
        "location": "Anchorage, Alaska, United States",
        "description": "Fishery analyst/economist role supporting fisheries management analysis for the North Pacific Fishery Management Council.",
    },
    {
        "title": "CURRICULUM SPECIALIST, WORLD LANGUAGES",
        "organization": "Denver Public Schools",
        "url": "https://www.linkedin.com/jobs/view/curriculum-specialist-world-languages-at-denver-public-schools-4433121693",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-20",
        "deadline": "2026-07-22",
        "location": "Denver, Colorado, United States",
        "description": "Curriculum specialist role supporting high-quality instructional resources and world language teaching and learning programmes.",
    },
    {
        "title": "Associate Digital Designer",
        "organization": "HOPE International",
        "url": "https://www.linkedin.com/jobs/view/associate-digital-designer-at-hope-international-inc-4436844755",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-22",
        "location": "Lancaster, Pennsylvania, United States",
        "description": "Digital design role supporting marketing, web, email, presentation, animation, and campaign assets for a mission-driven organization.",
    },
    {
        "title": "(DOE-014-26) State School Nurse Consultant",
        "organization": "New Jersey Department of Education",
        "url": "https://www.linkedin.com/jobs/view/doe-014-26-state-school-nurse-consultant-at-new-jersey-department-of-education-4429861849",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-21",
        "deadline": "2026-07-22",
        "location": "Trenton, New Jersey, United States",
        "description": "School nurse consultant role focused on comprehensive school health services, curricula, training, programme improvement, and liaison support.",
    },
    {
        "title": "XR Developer",
        "organization": "SmartSignal",
        "url": "https://in.linkedin.com/jobs/view/xr-developer-at-smartsignal-4442679904",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-22",
        "location": "Guindy, Tamil Nadu, India",
        "description": "XR developer role connected to immersive technology work at SmartSignal's Centre of Excellence.",
    },
    {
        "title": "Assistant Manager - EHS",
        "organization": "DIAGEO India",
        "url": "https://in.linkedin.com/jobs/view/assistant-manager-ehs-at-diageo-india-4443358349",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-21",
        "location": "Aurangabad, Maharashtra, India",
        "description": "Environment, health and safety assistant manager role requiring communication, presentation, multitasking, and stakeholder engagement skills.",
    },
    {
        "title": "Customer Service Manager",
        "organization": "Diageo",
        "url": "https://id.linkedin.com/jobs/view/customer-service-manager-at-diageo-4443350530",
        "source": "LinkedIn social search",
        "posted_date": "2026-07-21",
        "location": "Jakarta, Indonesia",
        "description": "Customer service management role for Diageo in Jakarta with regular employment status and business operations responsibilities.",
    },
]


def main() -> None:
    items = json.loads(OUT_JSON.read_text(encoding="utf-8")) if OUT_JSON.exists() else []
    by_url = {item["source_url"].rstrip("/").lower(): item for item in items}
    added = []
    skipped = []
    for seed in SEEDS:
        item = make_item(
            title=seed["title"],
            organization=seed["organization"],
            description=seed["description"],
            url=seed["url"],
            source=seed["source"],
            posted_date=seed["posted_date"],
            location=seed.get("location", ""),
            remote=seed.get("remote", False),
            deadline=seed.get("deadline", ""),
            compensation=seed.get("compensation", "Not specified"),
        )
        ok, reason = keep(item)
        key = item["source_url"].rstrip("/").lower()
        if ok and key not in by_url:
            by_url[key] = item
            added.append(item)
        else:
            skipped.append({"title": item["title"], "reason": reason if not ok else "duplicate"})

    for item in by_url.values():
        inferred_country = infer_country(item.get("location") or item.get("country") or "")
        if inferred_country and inferred_country != "Global/International":
            item["country"] = inferred_country
        if item.get("country") in {"USA", "US"}:
            item["country"] = "United States"
        elif item.get("country") == "UK":
            item["country"] = "United Kingdom"
        if item.get("country") == "Remote":
            item["work_mode"] = "Remote"

    merged = sorted(by_url.values(), key=lambda x: (x["posted_date"], x["source"], x["country"], x["organization"], x["title"]))
    OUT_JSON.write_text(json.dumps(merged, indent=2, ensure_ascii=False), encoding="utf-8")

    headers = [
        "title", "organization", "opportunity_type", "category", "country", "location", "work_mode",
        "compensation", "posted_date", "deadline", "source", "source_url", "summary",
    ]
    with OUT_CSV.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        for item in merged:
            writer.writerow({h: item.get(h, "") for h in headers})

    audit = {
        "search_window": "2026-07-20 to 2026-07-22",
        "total": len(merged),
        "web_social_seed_rows_configured": len(SEEDS),
        "web_social_seed_rows_present": sum(1 for item in merged if item["source"].startswith(("Google web search", "LinkedIn social search"))),
        "added_on_this_run": len(added),
        "duplicate_supplement_rows": len([row for row in skipped if row["reason"] == "duplicate"]),
        "by_source": Counter(item["source"] for item in merged).most_common(),
        "by_type": Counter(item["opportunity_type"] for item in merged).most_common(),
        "by_date": Counter(item["posted_date"] for item in merged).most_common(),
        "by_country": Counter(item["country"] for item in merged).most_common(),
    }
    AUDIT_JSON.write_text(json.dumps(audit, indent=2, ensure_ascii=False), encoding="utf-8")

    print(json.dumps({
        "before": len(items),
        "added": len(added),
        "skipped": skipped,
        "after": len(merged),
        "by_source": Counter(item["source"] for item in merged).most_common(12),
    }, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
