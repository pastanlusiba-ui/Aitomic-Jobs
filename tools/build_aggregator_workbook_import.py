from __future__ import annotations

import json
import re
from collections import Counter, defaultdict
from datetime import date, datetime
from pathlib import Path
from urllib.parse import urlparse

import pandas as pd


SOURCE_XLSX = Path("/Users/pastanlusiba/Downloads/Aitomic_Aggregator_Only_Opportunities_17-20_July_2026_updated.xlsx")
PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
IMPORT_JSON = PROJECT / "data" / "aggregator_only_opportunities_import_2026-07-20.json"
SOURCE_DB_JSON = PROJECT / "data" / "high_performing_opportunity_sources_2026-07-20.json"


COUNTRY_HINTS = {
    "uganda": "Uganda",
    "kenya": "Kenya",
    "tanzania": "Tanzania",
    "ethiopia": "Ethiopia",
    "senegal": "Senegal",
    "malawi": "Malawi",
    "nigeria": "Nigeria",
    "pakistan": "Pakistan",
    "india": "India",
    "ireland": "Ireland",
    "china": "China",
    "hong kong": "Hong Kong",
    "south africa": "South Africa",
    "new zealand": "New Zealand",
    "nepal": "Nepal",
    "kazakhstan": "Kazakhstan",
    "uzbekistan": "Uzbekistan",
    "indonesia": "Indonesia",
    "egypt": "Egypt",
    "ghana": "Ghana",
    "côte d’ivoire": "Côte d’Ivoire",
    "cote d'ivoire": "Côte d’Ivoire",
    "switzerland": "Switzerland",
    "usa": "United States",
    "united states": "United States",
    "uk": "United Kingdom",
    "united kingdom": "United Kingdom",
    "germany": "Germany",
    "france": "France",
    "peru": "Peru",
    "chile": "Chile",
    "jamaica": "Jamaica",
    "afghanistan": "Afghanistan",
    "philippines": "Philippines",
    "sri lanka": "Sri Lanka",
    "angola": "Angola",
    "bolivia": "Bolivia",
    "paraguay": "Paraguay",
    "taiwan": "Taiwan",
    "uae": "United Arab Emirates",
    "united arab emirates": "United Arab Emirates",
    "macau": "Macau",
    "qatar": "Qatar",
    "ilri": "Kenya",
    "icraf": "Kenya",
    "iita": "Nigeria",
    "italy": "Italy",
    "austria": "Austria",
    "netherlands": "Netherlands",
    "belgium": "Belgium",
    "washington": "United States",
    "new york": "United States",
    "illinois": "United States",
    "lexington": "United States",
    "wayne": "United States",
    "doha": "Qatar",
    "geneva": "Switzerland",
    "abu dhabi": "United Arab Emirates",
    "abuja": "Nigeria",
    "vienna": "Austria",
    "rome": "Italy",
    "the hague": "Netherlands",
    "nairobi": "Kenya",
    "kampala": "Uganda",
    "dakar": "Senegal",
    "addis ababa": "Ethiopia",
    "lilongwe": "Malawi",
    "juba": "South Sudan",
    "kathmandu": "Nepal",
    "astana": "Kazakhstan",
    "jakarta": "Indonesia",
    "beijing": "China",
    "tashkent": "Uzbekistan",
    "islamabad": "Pakistan",
    "cairo": "Egypt",
    "santiago": "Chile",
    "lima": "Peru",
    "kingston": "Jamaica",
}


def clean(value: object) -> str:
    if pd.isna(value):
        return ""
    return re.sub(r"\s+", " ", str(value)).strip()


def parse_date(value: object) -> str:
    value = clean(value)
    if not value or value.lower() in {"check listing", "nan", "nat"}:
        return ""
    if re.match(r"^\d{4}-\d{2}-\d{2}", value):
        return value[:10]
    try:
        parsed = pd.to_datetime(value, errors="coerce")
    except Exception:
        return ""
    if pd.isna(parsed):
        return ""
    return parsed.date().isoformat()


def category_to_type(category: str, title: str) -> str:
    text = f"{category} {title}".lower()
    if "consult" in text or "tender" in text:
        return "Tenders / Consultancies"
    if "intern" in text or "phd" in text or "postdoc" in text:
        return "Internships" if "intern" in text else "Jobs"
    if "volunteer" in text or "assignment" in text:
        return "Volunteer opportunities"
    if "remote" in text:
        return "Remote work opportunities"
    return "Jobs"


def sector_from_field(field: str, title: str, org: str) -> str:
    text = f"{field} {title} {org}".lower()
    if any(x in text for x in ["health", "medical", "wash", "emergency", "refugee", "nutrition"]):
        return "Health"
    if any(x in text for x in ["food", "agriculture", "natural resources", "climate", "environment", "energy"]):
        return "Agriculture" if "food" in text or "agric" in text else "Operations & Logistics"
    if any(x in text for x in ["it ", "technology", "digital", "cyber", "software", "data", "ai ", "product"]):
        return "Information Technology"
    if any(x in text for x in ["legal", "law", "rights", "policy", "governance", "elections"]):
        return "Legal & Policy"
    if any(x in text for x in ["monitoring", "evaluation", "outcome", "research", "analysis"]):
        return "Monitoring & Evaluation"
    if any(x in text for x in ["communication", "editorial", "translation", "editing", "media"]):
        return "Communications"
    if any(x in text for x in ["finance", "procurement", "supply", "account", "audit", "portfolio", "bid"]):
        return "Business & Finance"
    if any(x in text for x in ["education", "training", "teaching", "faculty", "phd", "postdoc"]):
        return "Education"
    if any(x in text for x in ["programme", "program", "humanitarian", "development", "gender", "peace"]):
        return "Humanitarian & Development"
    return "Operations & Logistics"


def infer_country(country_coverage: str, location: str) -> str:
    text = f"{country_coverage} {location}".strip()
    low = text.lower()
    coverage_low = clean(country_coverage).lower()
    location_low = clean(location).lower()
    region_labels = {
        "europe",
        "asia",
        "africa",
        "latin america",
        "caribbean",
        "central asia",
        "global/asia",
        "europe/africa",
        "latin america and caribbean",
        "headquarters/field offices",
        "western pacific region",
        "eastern mediterranean region",
        "ungm portal",
        "brain and mind institute",
        "shanghai, beijing, milan",
        "shanghai, pune",
    }
    if not text:
        return "Global/International"
    if any(x in low for x in ["remote", "home-based", "home based"]):
        return "Remote"
    if low in region_labels or coverage_low in region_labels or location_low in region_labels:
        return "Global/International"
    if any(x in low for x in ["various", "multiple", "global", "destinations", "locations"]):
        return "Global/International"
    for needle, country in COUNTRY_HINTS.items():
        if re.search(r"\b" + re.escape(needle) + r"\b", low):
            return country
    return clean(country_coverage) or clean(location) or "Global/International"


def work_mode(location: str, category: str) -> str:
    text = f"{location} {category}".lower()
    if "remote" in text or "home-based" in text or "home based" in text:
        return "Remote"
    if "hybrid" in text:
        return "Hybrid"
    if "field" in text or "various" in text or "multiple" in text:
        return "Field-based"
    return "On-site"


def exact_url(url: str) -> bool:
    path = urlparse(url).path.lower()
    return bool(re.search(r"/(job|jobs|vacanc|vacancies|career|careers|opportunities?)/", path) or "reliefweb.int/job/" in url.lower())


def source_domain(url: str) -> str:
    host = urlparse(url).netloc.lower()
    return host[4:] if host.startswith("www.") else host


def type_phrase(opp_type: str) -> str:
    low = opp_type.lower()
    if "intern" in low:
        return "an internship opportunity"
    if "volunteer" in low:
        return "a volunteer opportunity"
    if "consult" in low or "tender" in low:
        return "a tender or consultancy opportunity"
    if "remote" in low:
        return "a remote work opportunity"
    if "training" in low:
        return "a training or short course opportunity"
    if "call" in low:
        return "a call for applications"
    if "job" in low:
        return "a job opportunity"
    return "an opportunity"


def build_content_fields(row: dict[str, str]) -> dict[str, object]:
    title = row["Opportunity Title"]
    org = row["Organisation"]
    category = row["Category"]
    field = row["Field"]
    location = row["Location / Arrangement"]
    country = infer_country(row["Country / Coverage"], location)
    opp_type = category_to_type(category, title)
    sector = sector_from_field(field, title, org)
    mode = work_mode(location, category)
    deadline = parse_date(row["Deadline"])
    deadline_label = deadline or row["Deadline"] or "Check listing"
    aggregator = row["Aggregator"]
    url = row["Aggregator URL"]
    pub_date = parse_date(row["Publication Date"])
    evidence = row["Publication Evidence"]

    summary = (
        f"{title} is {type_phrase(opp_type)} with {org}"
        f"{' in ' + field if field else ''}. "
        f"The opportunity is connected to {location or row['Country / Coverage'] or country} and is suitable for candidates interested in {sector.lower()}."
    )
    description = (
        f"{org} is offering {title} for people interested in {sector.lower()}, categorized under {category or opp_type}"
        f"{' with a focus on ' + field if field else ''}. "
        f"The role or opportunity is associated with {location or row['Country / Coverage'] or country}, with a {mode.lower()} work arrangement. "
        f"Interested applicants should review the opportunity requirements, prepare the requested materials, and complete the application process before the stated deadline."
    )

    responsibilities = [
        f"Review the role, assignment, tender, or programme scope for {title}.",
        "Check the application requirements, submission steps, and supporting document instructions.",
        "Prepare the documents, profile, proposal, CV, or application materials required for the opportunity.",
    ]
    if field:
        responsibilities.insert(1, f"Assess whether the field or functional area, {field}, matches your experience and interests.")

    requirements = [
        "Applicants should meet the education, experience, location, documentation, and eligibility requirements for the opportunity.",
        f"Applicants should check the country, work arrangement, and deadline details before applying. The current deadline/status is {deadline_label}.",
        "Applications should be submitted through the stated application channel.",
    ]
    if category_to_type(category, title) == "Tenders / Consultancies":
        requirements.append("Consultants and bidders should review the Terms of Reference, submission format, evaluation criteria, and procurement requirements.")

    benefits = [
        "Compensation details are not specified for this opportunity.",
        "Salary, stipend, consultancy fee, contract terms, or volunteer conditions should be reviewed before applying.",
    ]

    return {
        "title": title,
        "organization": org,
        "opportunity_type": opp_type,
        "category": sector,
        "country": country,
        "location": location or row["Country / Coverage"] or country,
        "work_mode": mode,
        "compensation": "Not specified",
        "duration": "Not specified",
        "start_date": "",
        "deadline": deadline,
        "deadline_label": deadline_label,
        "summary": summary,
        "description": description,
        "responsibilities": responsibilities,
        "requirements": requirements,
        "benefits": benefits,
        "how_to_apply": "Use the Apply now button and follow the stated application instructions.",
        "verification_notes": "",
        "source": row["Aggregator"],
        "source_url": url,
        "application_link": url,
        "aggregator": aggregator,
        "publication_date": pub_date,
        "publication_evidence": evidence,
        "import_batch": "aggregator-only-2026-07-17-to-2026-07-20",
    }


def source_rows(df: pd.DataFrame) -> list[dict[str, object]]:
    grouped: dict[tuple[str, str], list[dict[str, str]]] = defaultdict(list)
    for _, raw in df.iterrows():
        row = {col: clean(raw.get(col, "")) for col in df.columns}
        grouped[(row["Aggregator"], row["Aggregator URL"])].append(row)

    rows = []
    for (name, url), items in grouped.items():
        cats = sorted({x["Category"] for x in items if x["Category"]})
        countries = sorted({infer_country(x["Country / Coverage"], x["Location / Arrangement"]) for x in items})
        regions = sorted({x["Region"] for x in items if x["Region"]})
        deadlines = sum(1 for x in items if parse_date(x["Deadline"]))
        exact = exact_url(url)
        count = len(items)
        score = count * 3 + len(countries) * 2 + len(cats) + deadlines * 2 + (8 if exact else 0)
        status = "High-performing" if count >= 5 or exact else "Useful niche source"
        if "remotive" in url.lower():
            status = "Restricted / do not import without permission"
        rows.append({
            "Source name": name,
            "Source domain": source_domain(url),
            "Source URL": url,
            "Source type": "Aggregator / index / profile site",
            "Status": status,
            "Imported rows in workbook": count,
            "Categories covered": "; ".join(cats),
            "Countries / coverage": "; ".join(countries[:20]),
            "Regions covered": "; ".join(regions[:20]),
            "Rows with exact deadline": deadlines,
            "Exact listing URL": "Yes" if exact else "No / aggregator-level",
            "Quality score": score,
            "Recommended use": "Automate/import after row-level review" if count >= 3 else "Use for discovery and manual verification",
            "Notes": "Built from aggregator-only workbook supplied on 2026-07-20.",
            "Last reviewed": "2026-07-20",
        })
    rows.sort(key=lambda r: (-int(r["Quality score"]), str(r["Source name"]).lower()))
    return rows


def main() -> None:
    df = pd.read_excel(SOURCE_XLSX, sheet_name="Aggregator Opportunities")
    df = df.fillna("")
    normalized = []
    seen = set()
    duplicate_count = 0
    for _, raw in df.iterrows():
        row = {col: clean(raw.get(col, "")) for col in df.columns}
        if not row["Opportunity Title"] or not row["Organisation"] or not row["Aggregator URL"]:
            continue
        key = (
            row["Opportunity Title"].lower(),
            row["Organisation"].lower(),
            row["Location / Arrangement"].lower(),
            row["Aggregator URL"].rstrip("/").lower(),
        )
        if key in seen:
            duplicate_count += 1
            continue
        seen.add(key)
        normalized.append(build_content_fields(row))

    source_db = source_rows(df)
    IMPORT_JSON.parent.mkdir(parents=True, exist_ok=True)
    IMPORT_JSON.write_text(json.dumps(normalized, indent=2, ensure_ascii=False), encoding="utf-8")
    SOURCE_DB_JSON.write_text(json.dumps({
        "generated": "2026-07-20",
        "source_workbook": str(SOURCE_XLSX),
        "import_rows": len(normalized),
        "duplicates_excluded": duplicate_count,
        "source_rows": source_db,
        "summary": {
            "by_opportunity_type": Counter(x["opportunity_type"] for x in normalized),
            "by_source": Counter(x["source"] for x in normalized),
            "by_country": Counter(x["country"] for x in normalized),
        },
    }, indent=2, ensure_ascii=False), encoding="utf-8")

    print(json.dumps({
        "import_json": str(IMPORT_JSON),
        "source_db_json": str(SOURCE_DB_JSON),
        "rows": len(normalized),
        "duplicates_excluded": duplicate_count,
        "sources": len(source_db),
        "by_type": Counter(x["opportunity_type"] for x in normalized).most_common(),
        "top_sources": Counter(x["source"] for x in normalized).most_common(12),
    }, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
