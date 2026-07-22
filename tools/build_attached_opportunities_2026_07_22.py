from __future__ import annotations

import csv
import io
import json
import re
import urllib.parse
from collections import Counter
from datetime import datetime
from pathlib import Path

import pandas as pd

from hunt_july_20_22_2026_opportunities import exact_url, infer_country


ROOT = Path(__file__).resolve().parents[1]
OUT_JSON = ROOT / "data" / "attached_opportunities_import_2026-07-22.json"
AUDIT_JSON = ROOT / "data" / "attached_opportunities_import_2026-07-22_audit.json"
WEAK_CSV = ROOT / "data" / "attached_opportunities_import_2026-07-22_weak_links.csv"

STYLE_WORKBOOK = Path("/Users/pastanlusiba/Downloads/aitomic_style_opportunities.xlsx")
POSTED_WORKBOOK = Path("/Users/pastanlusiba/Downloads/Aitomic_Opportunities_Posted_20-22_July_2026.xlsx")
PASTED_CSV = Path("/Users/pastanlusiba/.codex/attachments/cb773df0-4788-49ba-8044-10e760698baa/pasted-text.txt")

EXCLUDE_RE = re.compile(
    r"\b(scholarships?|grants?|fellowships?|awards?|competitions?|conferences?|studentships?)\b",
    re.I,
)

CITY_COUNTRY = {
    "Abuja": "Nigeria",
    "Addis Ababa": "Ethiopia",
    "Atlanta": "United States",
    "Aurangabad": "India",
    "Bethesda": "United States",
    "Boston": "United States",
    "Brazzaville": "Republic of the Congo",
    "Cambridge": "United Kingdom",
    "Chennai": "India",
    "Copenhagen": "Denmark",
    "Dakar": "Senegal",
    "Delhi": "India",
    "Geneva": "Switzerland",
    "Greenbelt": "United States",
    "Heidelberg": "Germany",
    "Ibadan": "Nigeria",
    "Jakarta": "Indonesia",
    "Kampala": "Uganda",
    "Lima": "Peru",
    "London": "United Kingdom",
    "Munich": "Germany",
    "Nairobi": "Kenya",
    "New York": "United States",
    "Norwich": "United Kingdom",
    "Oxford": "United Kingdom",
    "Panama City": "Panama",
    "Paris": "France",
    "Penang": "Malaysia",
    "Riverside": "United States",
    "Rome": "Italy",
    "Santa Monica": "United States",
    "Singapore": "Singapore",
    "Stockholm": "Sweden",
    "Stuttgart": "Germany",
    "Thuwal": "Saudi Arabia",
    "Tokyo": "Japan",
    "Toronto": "Canada",
    "Townsville": "Australia",
    "Washington": "United States",
    "Wageningen": "Netherlands",
    "Zurich": "Switzerland",
}

ALLOWED_CATEGORIES = {
    "administration": "Administration",
    "agriculture": "Agriculture",
    "business & finance": "Business & Finance",
    "communications": "Communications",
    "education": "Education",
    "engineering": "Engineering",
    "health": "Health",
    "humanitarian & development": "Humanitarian & Development",
    "information technology": "Information Technology",
    "legal & policy": "Legal & Policy",
    "monitoring & evaluation": "Monitoring & Evaluation",
    "operations & logistics": "Operations & Logistics",
}


def clean(value: object) -> str:
    if value is None or (isinstance(value, float) and pd.isna(value)):
        return ""
    return re.sub(r"\s+", " ", str(value).strip())


def normalize_url(value: object) -> str:
    text = clean(value)
    if not text:
        return ""
    text = re.split(r"\s+/\s+|\s+or\s+", text)[0].strip()
    if "," in text and not text.startswith("http"):
        text = text.split(",", 1)[0].strip()
    if not re.match(r"^https?://", text, re.I):
        text = "https://" + text
    parsed = urllib.parse.urlsplit(text)
    if not parsed.netloc:
        return ""
    return urllib.parse.urlunsplit((parsed.scheme, parsed.netloc, parsed.path.rstrip("/"), parsed.query, ""))


def normalize_type(value: object, title: object = "") -> str:
    raw_type = clean(value).lower()
    if raw_type in {"job", "jobs"}:
        return "Jobs"
    if raw_type in {"internship", "internships"}:
        return "Internships"
    if raw_type in {"volunteer", "volunteers", "volunteer opportunities"}:
        return "Volunteer opportunities"
    if raw_type in {"remote", "remote work", "remote work opportunities"}:
        return "Remote work opportunities"
    if raw_type in {"consultancy", "consultancies", "tender", "tenders", "tenders / consultancies"}:
        return "Tenders / Consultancies"
    if raw_type in {"training", "training / short courses", "short course", "short courses"}:
        return "Training / short courses"
    if raw_type in {"call", "calls", "call for applications", "calls for applications"}:
        return "Calls for applications"
    text = f"{raw_type} {clean(title)}".lower()
    if "volunteer" in text:
        return "Volunteer opportunities"
    if "intern" in text:
        return "Internships"
    if any(term in text for term in ["consult", "tender", "procurement", "rfq", "rfp", "lta", "eoi", "roster"]):
        return "Tenders / Consultancies"
    if "remote" in text:
        return "Remote work opportunities"
    if "call" in text:
        return "Calls for applications"
    if "training" in text or "short course" in text:
        return "Training / short courses"
    return "Jobs"


def infer_category(title: str, supplied: str = "", field: str = "") -> str:
    text = f"{title} {supplied} {field}".lower()
    field = clean(field)
    supplied = clean(supplied)
    explicit = normalize_category(supplied)
    if explicit:
        return explicit
    explicit = normalize_category(field)
    if explicit:
        return explicit
    if any(x in text for x in ["health", "medical", "nurs", "clinical", "epidemiology", "cancer", "malaria"]):
        return "Health"
    if any(x in text for x in ["data", "software", "ai", "digital", "developer", "it ", "quantum"]):
        return "Information Technology"
    if any(x in text for x in ["agricultur", "food", "plant", "crop"]):
        return "Agriculture"
    if any(x in text for x in ["finance", "econom", "business", "growth", "marketing"]):
        return "Business & Finance"
    if any(x in text for x in ["communication", "media", "fundraising"]):
        return "Communications"
    if any(x in text for x in ["policy", "rights", "legal", "governance"]):
        return "Legal & Policy"
    if any(x in text for x in ["research", "scientist", "postdoctoral", "professor", "laboratory", "biology", "genomics", "analysis", "evaluation"]):
        return "Monitoring & Evaluation"
    if any(x in text for x in ["climate", "environment", "marine", "conservation", "sustainability", "development"]):
        return "Humanitarian & Development"
    return "Operations & Logistics"


def normalize_category(value: str) -> str:
    text = clean(value).lower()
    if not text or text in {"job", "jobs", "consultancy", "consultancies", "remote", "volunteer", "training"}:
        return ""
    for key, label in ALLOWED_CATEGORIES.items():
        if key in text:
            return label
    if any(x in text for x in ["ict", "technology", "digital", "data", "software", "developer", "cyber"]):
        return "Information Technology"
    if any(x in text for x in ["finance", "procurement", "econom", "account", "business", "marketing"]):
        return "Business & Finance"
    if any(x in text for x in ["health", "medical", "clinical", "nutrition", "nursing"]):
        return "Health"
    if any(x in text for x in ["education", "learning", "teacher", "academic"]):
        return "Education"
    if any(x in text for x in ["communication", "media", "translation", "editing"]):
        return "Communications"
    if any(x in text for x in ["climate", "environment", "conservation", "humanitarian", "development", "programme", "program"]):
        return "Humanitarian & Development"
    if any(x in text for x in ["research", "analysis", "evaluation", "oversight", "investigation"]):
        return "Monitoring & Evaluation"
    if any(x in text for x in ["engineering", "infrastructure", "technical"]):
        return "Engineering"
    return ""


def infer_country_from_location(location: str, supplied: str = "") -> str:
    supplied = clean(supplied)
    if supplied and supplied.lower() not in {"not specified", "multiple destinations", "multiple locations", "multiple", "various"}:
        supplied = supplied.replace("+1", "").strip()
        if re.search(r"\b(remote|home-based|home based)\b", supplied, re.I):
            return "Remote"
        if re.search(r"\b(global|international|multiple|various)\b", supplied, re.I):
            return "Global/International"
        if "/" in supplied and not re.search(r"\bUnited States\b", supplied, re.I):
            first = supplied.split("/", 1)[0].strip()
            if first.lower() in {"netherlands", "nigeria"}:
                return first
            return "Global/International"
        supplied = re.sub(r"\s*\([^)]*\)", "", supplied).strip()
        if supplied in {"USA", "US"}:
            return "United States"
        if supplied in {"UK", "U.K."}:
            return "United Kingdom"
        if supplied == "UAE":
            return "United Arab Emirates"
        if supplied in {"Asia", "Sahel region", "Indian Ocean region"}:
            return "Global/International"
        return supplied
    location = clean(location)
    if re.search(r"\b(remote|home-based|home based)\b", location, re.I):
        return "Remote"
    for city, country in CITY_COUNTRY.items():
        if re.search(r"\b" + re.escape(city) + r"\b", location, re.I):
            return country
    country = infer_country(location)
    if country in {"USA", "US"}:
        return "United States"
    if country in {"UK", "U.K."}:
        return "United Kingdom"
    if country.lower() in {"multiple locations", "multiple", "various"}:
        return "Global/International"
    return country or "Global/International"


def infer_work_mode(location: str, opportunity_type: str) -> str:
    text = f"{location} {opportunity_type}".lower()
    if "remote" in text or "home-based" in text or "home based" in text:
        return "Remote"
    if "hybrid" in text:
        return "Hybrid"
    if "multiple" in text or "various" in text:
        return "Various"
    return "On-site"


def normalize_deadline(value: object) -> tuple[str, str]:
    text = clean(value)
    if not text or text.lower() in {"nan", "nat", "not specified"}:
        return "", "Not specified"
    if text.lower() == "check listing":
        return "", "Check application page"
    if re.match(r"^\d{4}-\d{2}-\d{2}", text):
        return text[:10], text[:10]
    for pattern in [r"(\d{1,2}\s+[A-Za-z]+\s+20\d{2})", r"([A-Za-z]+\s+\d{1,2},?\s+20\d{2})"]:
        match = re.search(pattern, text)
        if not match:
            continue
        raw = match.group(1).replace(",", "")
        for fmt in ("%d %B %Y", "%d %b %Y", "%B %d %Y", "%b %d %Y"):
            try:
                return datetime.strptime(raw, fmt).date().isoformat(), text
            except ValueError:
                pass
    return "", text


def organization_name(value: object, fallback: str = "Organization not specified") -> str:
    org = clean(value)
    if not org or org.lower() in {"(via reliefweb)", "via reliefweb", "nan"}:
        return fallback
    return org


def content_for(item: dict) -> dict:
    typ = item["opportunity_type"]
    category = item["category"]
    title = item["title"]
    org = item["organization"]
    location = item["location"]
    if typ == "Tenders / Consultancies":
        noun = "consultancy or procurement opportunity"
        responsibilities = [
            f"Review the terms of reference, procurement notice, or consultancy scope for {title}.",
            "Prepare the requested technical, administrative, and financial documentation.",
            "Submit the application, proposal, or quotation through the channel stated on the application page.",
        ]
        requirements = [
            f"Relevant experience in {category.lower()} or the technical area described in the notice.",
            "Ability to meet the submission format, eligibility, registration, and deadline requirements.",
            "Capacity to provide supporting documents, references, samples, or certifications where requested.",
        ]
    elif typ == "Internships":
        noun = "internship opportunity"
        responsibilities = [
            f"Support day-to-day work connected to {category.lower()} and the {title} assignment.",
            "Assist with research, analysis, documentation, coordination, or communications tasks as assigned.",
            "Contribute to team outputs while building practical professional experience.",
        ]
        requirements = [
            f"Academic background, training, or demonstrated interest related to {category.lower()}.",
            "Ability to work carefully, communicate clearly, and follow organizational procedures.",
            "Availability for the internship period and any location or remote-work conditions stated by the organization.",
        ]
    elif typ == "Volunteer opportunities":
        noun = "volunteer opportunity"
        responsibilities = [
            f"Provide volunteer support for activities connected to {category.lower()} and {title}.",
            "Complete agreed tasks professionally and communicate progress with the coordinating team.",
            "Follow the organization’s volunteer guidance, safeguarding, and reporting expectations.",
        ]
        requirements = [
            f"Relevant skills, interest, or experience in {category.lower()} or the stated volunteer area.",
            "Ability to commit the required time and complete assigned activities responsibly.",
            "Meet any volunteer eligibility, onboarding, language, or location requirements.",
        ]
    elif typ == "Calls for applications":
        noun = "call for applications"
        responsibilities = [
            f"Review the call objectives, eligibility criteria, and submission requirements for {title}.",
            "Prepare the requested concept note, application form, profile, supporting documents, or proposal materials.",
            "Submit the application through the stated platform or contact channel before the deadline.",
        ]
        requirements = [
            f"Relevant eligibility for the call area, especially experience or interest in {category.lower()}.",
            "Ability to provide all required documents and meet the stated submission conditions.",
            "Applicants should confirm any age, country, organization, sector, or thematic eligibility requirements.",
        ]
    elif typ == "Training / short courses":
        noun = "training or short course opportunity"
        responsibilities = [
            f"Participate in learning activities, assignments, or sessions connected to {title}.",
            "Complete registration, onboarding, coursework, or participation requirements as stated by the provider.",
            "Apply the learning outcomes or outputs expected from the course or training programme.",
        ]
        requirements = [
            f"Interest, background, or work experience related to {category.lower()}.",
            "Availability for the stated training period, delivery mode, and participation requirements.",
            "Ability to meet any language, documentation, fee, or eligibility conditions.",
        ]
    elif typ == "Remote work opportunities":
        noun = "remote work opportunity"
        responsibilities = [
            f"Carry out remote duties related to {category.lower()} and the {title} role.",
            "Coordinate with distributed teams, partners, or supervisors using the required digital channels.",
            "Deliver assigned outputs, reports, services, or technical work within agreed timelines.",
        ]
        requirements = [
            f"Relevant education, professional experience, or technical skills in {category.lower()}.",
            "Ability to work independently and communicate effectively in a remote setting.",
            "Meet the application, documentation, eligibility, and deadline requirements stated by the organization.",
        ]
    else:
        noun = "job opportunity"
        responsibilities = [
            f"Carry out duties related to {category.lower()} and the {title} role.",
            "Work with relevant teams, partners, or stakeholders to deliver assigned outputs.",
            "Prepare reports, analysis, services, technical work, or programme support linked to the position.",
        ]
        requirements = [
            f"Relevant education, professional experience, or technical skills in {category.lower()}.",
            "Ability to deliver high-quality work in the stated location, country, or work arrangement.",
            "Meet the application, documentation, eligibility, and deadline requirements stated by the organization.",
        ]

    summary = item.get("supplied_summary") or f"{org} is inviting applications for {title}, a {noun} based in {location}."
    description = (
        f"{title} is a {noun} with {org}. The opportunity is categorized under {category} and is listed for "
        f"{location}. Candidates or eligible organizations should review the role scope, eligibility expectations, "
        "deadline, and submission instructions before applying."
    )
    if item.get("supplied_summary"):
        description = f"{item['supplied_summary']} {description}"

    item.update(
        {
            "summary": summary,
            "description": description,
            "responsibilities": responsibilities,
            "requirements": requirements,
            "benefits": [
                item["compensation"]
                if item["compensation"] != "Not specified"
                else "Compensation, stipend, consultancy fees, benefits, or volunteer arrangements are not specified."
            ],
            "how_to_apply": "Use the Apply now button and follow the instructions on the application page.",
        }
    )
    return item


def row_to_item(row: dict, source_group: str) -> tuple[dict | None, str]:
    title = clean(row.get("title") or row.get("Title") or row.get("Opportunity Title"))
    org = organization_name(row.get("organization") or row.get("Organization") or row.get("Organisation"))
    location = clean(row.get("location") or row.get("Location") or row.get("Location / Arrangement"))
    supplied_type = clean(row.get("type") or row.get("Type") or row.get("Opportunity Type") or row.get("Category"))
    category = infer_category(title, clean(row.get("Category") or row.get("Opportunity Type")), clean(row.get("Field")))
    source_url = normalize_url(row.get("link") or row.get("Link") or row.get("Source") or row.get("Source URL"))
    posted_date = clean(row.get("posted_date") or row.get("Posted Date") or row.get("Publication Date"))
    if posted_date and re.match(r"^\d{4}-\d{2}-\d{2}", posted_date):
        posted_date = posted_date[:10]
    deadline, deadline_label = normalize_deadline(row.get("Deadline"))
    grant_like_call = re.search(r"\b(funding opportunity|protection fund|grant fund|funding call)\b", f"{title} {supplied_type}", re.I)
    if EXCLUDE_RE.search(f"{title} {supplied_type} {category}") or grant_like_call:
        return None, "excluded-category"
    if not title or not org or not source_url:
        return None, "missing-required-field"

    opportunity_type = normalize_type(supplied_type, title)
    country = infer_country_from_location(location, clean(row.get("Country") or row.get("Country / Coverage")))
    work_mode = infer_work_mode(location, opportunity_type)
    if country == "Remote":
        work_mode = "Remote"

    item = {
        "title": title,
        "organization": org,
        "opportunity_type": opportunity_type,
        "category": category,
        "country": country,
        "location": location or country,
        "work_mode": work_mode,
        "compensation": "Not specified",
        "duration": "Not specified",
        "start_date": posted_date,
        "posted_date": posted_date,
        "deadline": deadline,
        "deadline_label": deadline_label,
        "source": organization_name(row.get("Source Platform"), org),
        "source_url": source_url,
        "application_link": source_url,
        "institution_url": source_url,
        "source_group": source_group,
        "discovery_method": source_group,
        "verification_notes": "Imported from user-supplied opportunity file on 2026-07-22.",
        "supplied_summary": clean(row.get("Summary")),
    }
    return content_for(item), "kept"


def load_rows() -> list[tuple[dict, str]]:
    rows: list[tuple[dict, str]] = []
    if STYLE_WORKBOOK.exists():
        df = pd.read_excel(STYLE_WORKBOOK, sheet_name="Opportunities")
        rows.extend((record, "aitomic_style_opportunities.xlsx") for record in df.to_dict("records"))
    if POSTED_WORKBOOK.exists():
        df = pd.read_excel(POSTED_WORKBOOK, sheet_name="Opportunities 20-22 Jul")
        rows.extend((record, "Aitomic_Opportunities_Posted_20-22_July_2026.xlsx") for record in df.to_dict("records"))
    if PASTED_CSV.exists():
        pasted = PASTED_CSV.read_text(encoding="utf-8-sig")
        rows.extend((record, "pasted-text.csv") for record in csv.DictReader(io.StringIO(pasted)))
    return rows


def dedupe_key(item: dict) -> str:
    return "|".join(
        re.sub(r"[^a-z0-9]+", " ", clean(item.get(key)).lower()).strip()
        for key in ["title", "organization", "source_url"]
    )


def main() -> None:
    raw_rows = load_rows()
    kept: dict[str, dict] = {}
    skipped = []
    for row, source_group in raw_rows:
        item, status = row_to_item(row, source_group)
        if not item:
            skipped.append({"status": status, "source_group": source_group, "title": clean(row.get("Title") or row.get("Opportunity Title"))})
            continue
        kept.setdefault(dedupe_key(item), item)

    items = sorted(kept.values(), key=lambda row: (row.get("posted_date") or "", row["opportunity_type"], row["country"], row["title"]))
    OUT_JSON.write_text(json.dumps(items, indent=2, ensure_ascii=False), encoding="utf-8")

    weak_rows = [
        {
            "title": item["title"],
            "organization": item["organization"],
            "opportunity_type": item["opportunity_type"],
            "source_url": item["source_url"],
            "source_group": item["source_group"],
        }
        for item in items
        if not exact_url(item["source_url"])
    ]
    with WEAK_CSV.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=["title", "organization", "opportunity_type", "source_url", "source_group"])
        writer.writeheader()
        writer.writerows(weak_rows)

    audit = {
        "raw_rows": len(raw_rows),
        "kept_unique_rows": len(items),
        "skipped_rows": len(skipped),
        "exact_link_rows": sum(1 for item in items if exact_url(item["source_url"])),
        "weak_link_rows": len(weak_rows),
        "skipped_by_reason": Counter(row["status"] for row in skipped).most_common(),
        "by_source_group": Counter(item["source_group"] for item in items).most_common(),
        "by_type": Counter(item["opportunity_type"] for item in items).most_common(),
        "by_country": Counter(item["country"] for item in items).most_common(),
        "skipped_sample": skipped[:30],
    }
    AUDIT_JSON.write_text(json.dumps(audit, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps(audit, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
