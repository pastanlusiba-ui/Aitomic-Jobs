from __future__ import annotations

import csv
import html
import json
import os
import re
import ssl
import time
import urllib.parse
import urllib.request
from collections import Counter
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import date, datetime, timedelta, timezone
from html.parser import HTMLParser
from pathlib import Path


PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
OUT_JSON = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt.json"
OUT_CSV = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt.csv"
AUDIT_JSON = PROJECT / "data" / "july_20_22_2026_exact_opportunity_hunt_audit.json"

START = date(2026, 7, 20)
END = date(2026, 7, 22)
TODAY = date(2026, 7, 22)

UA = "AitomicJobsOpportunityHunt/1.0 (+https://aitomic.net)"
CTX = ssl._create_unverified_context()

EXCLUDE_RE = re.compile(r"(scholarship|grant|fellowship|award|competition|conference|webinar|symposium)", re.I)
JOB_SIGNAL_RE = re.compile(
    r"(job|vacanc|career|position|hiring|officer|assistant|coordinator|manager|specialist|analyst|"
    r"engineer|developer|consult|consultancy|intern|volunteer|programme|program|advisor|adviser|"
    r"director|researcher|scientist|administrator|associate|procurement|tender|rfp|request for proposal)",
    re.I,
)
GENERIC_PATH_RE = re.compile(
    r"^/?(jobs?|careers?|vacanc(?:y|ies)|opportunities?|employment|recruitment|join-us|work-with-us|search|apply)?/?$",
    re.I,
)


class TextParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.title: list[str] = []
        self.h1: list[str] = []
        self.parts: list[str] = []
        self.links: list[tuple[str, str]] = []
        self.in_title = False
        self.in_h1 = False
        self.href: str | None = None
        self.anchor: list[str] = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "title":
            self.in_title = True
        elif tag == "h1":
            self.in_h1 = True
        elif tag == "a":
            self.href = attrs.get("href")
            self.anchor = []
        if tag in {"p", "li", "br", "h1", "h2", "h3", "tr", "div"}:
            self.parts.append("\n")

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False
        elif tag == "h1":
            self.in_h1 = False
        elif tag == "a" and self.href is not None:
            self.links.append((self.href, clean(" ".join(self.anchor))))
            self.href = None
            self.anchor = []
        if tag in {"p", "li", "h1", "h2", "h3", "tr"}:
            self.parts.append("\n")

    def handle_data(self, data):
        value = clean(data)
        if not value:
            return
        if self.in_title:
            self.title.append(value)
        if self.in_h1:
            self.h1.append(value)
        if self.href is not None:
            self.anchor.append(value)
        self.parts.append(value)


def clean(value: object) -> str:
    value = html.unescape(str(value or ""))
    value = re.sub(r"[\u200b-\u200f\ufeff]", "", value)
    return re.sub(r"\s+", " ", value).strip()


def parse_html(body: str) -> tuple[str, str, list[tuple[str, str]]]:
    parser = TextParser()
    parser.feed(body)
    title = clean(" ".join(parser.h1) or " ".join(parser.title))
    text = clean(" ".join(parser.parts))
    return title, text, parser.links


def trim(text: str, limit: int) -> str:
    text = clean(re.sub(r"\s+", " ", text))
    if len(text) <= limit:
        return text
    cut = text[: max(0, limit - 3)]
    space = cut.rfind(" ")
    if space > 80:
        cut = cut[:space]
    return cut.rstrip(" .,;:") + "..."


def fetch(url: str, timeout: int = 25) -> str:
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": UA,
            "Accept": "text/html,application/json,application/xml,text/xml,*/*",
        },
    )
    with urllib.request.urlopen(req, timeout=timeout, context=CTX) as res:
        return res.read(2_500_000).decode("utf-8", errors="ignore")


def fetch_json(url: str, timeout: int = 30) -> object:
    return json.loads(fetch(url, timeout=timeout))


def iso_date(value: object) -> str:
    if value is None:
        return ""
    if isinstance(value, (int, float)):
        try:
            return datetime.fromtimestamp(value, tz=timezone.utc).date().isoformat()
        except (OSError, OverflowError, ValueError):
            return ""
    text = clean(value)
    if not text:
        return ""
    if re.match(r"^\d{4}-\d{2}-\d{2}", text):
        return text[:10]
    for fmt in (
        "%Y-%m-%dT%H:%M:%S%z",
        "%Y-%m-%dT%H:%M:%S.%f%z",
        "%a, %d %b %Y %H:%M:%S %z",
        "%d %B %Y",
        "%d %b %Y",
        "%B %d, %Y",
        "%B %d %Y",
    ):
        try:
            return datetime.strptime(text.replace("Z", "+0000"), fmt).date().isoformat()
        except ValueError:
            pass
    return ""


def in_window(value: str) -> bool:
    if not value:
        return False
    try:
        d = datetime.strptime(value[:10], "%Y-%m-%d").date()
    except ValueError:
        return False
    return START <= d <= END


def parse_deadline(text: str) -> str:
    patterns = [
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date|end date)[:\s-]*(\d{1,2}\s+[A-Z][a-z]+\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date|end date)[:\s-]*([A-Z][a-z]+\s+\d{1,2},?\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date|end date)[:\s-]*(20\d{2}-\d{2}-\d{2})",
        r"\b(20\d{2}-\d{2}-\d{2})\b",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.I)
        if match:
            return iso_date(match.group(1)) or clean(match.group(1))
    return ""


def classify_type(text: str, remote: bool = False) -> str:
    low = text.lower()
    if re.search(r"\bvolunteer\b", low):
        return "Volunteer opportunities"
    if re.search(r"\bintern(?:ship)?\b|\bstudent worker\b|\btrainee\b", low):
        return "Internships"
    if any(x in low for x in ["consultancy", "consultant", "retainer", "request for proposal", "rfp", "tender", "procurement"]):
        return "Tenders / Consultancies"
    if remote:
        return "Remote work opportunities"
    return "Jobs"


def classify_category(text: str) -> str:
    low = text.lower()
    if any(x in low for x in ["health", "medical", "clinical", "pharma", "vaccine", "disease", "nutrition", "nurse"]):
        return "Health"
    if any(x in low for x in ["agricultur", "food", "livestock", "fish", "forestry", "crop"]):
        return "Agriculture"
    if any(x in low for x in ["education", "learning", "school", "student", "teacher", "university"]):
        return "Education"
    if any(x in low for x in ["software", "engineer", "developer", "data", "ai ", "technology", "digital", "cyber", "ict"]):
        return "Information Technology"
    if any(x in low for x in ["finance", "account", "budget", "bid", "procurement", "sales", "business", "marketing"]):
        return "Business & Finance"
    if any(x in low for x in ["communication", "content", "media", "advocacy", "campaign"]):
        return "Communications"
    if any(x in low for x in ["policy", "legal", "rights", "governance", "protection"]):
        return "Legal & Policy"
    if any(x in low for x in ["monitoring", "evaluation", "research", "analysis", "analyst"]):
        return "Monitoring & Evaluation"
    if any(x in low for x in ["humanitarian", "development", "ngo", "relief", "peace"]):
        return "Humanitarian & Development"
    return "Operations & Logistics"


def infer_country(location: str) -> str:
    text = clean(location)
    if not text:
        return "Global/International"
    if re.search(r"\b(remote|home based|home-based|worldwide|global|anywhere)\b", text, re.I):
        return "Remote"
    aliases = {
        "usa": "United States",
        "u.s.": "United States",
        "u.s.a.": "United States",
        "us": "United States",
        "uk": "United Kingdom",
        "u.k.": "United Kingdom",
        "uae": "United Arab Emirates",
        "drc": "Democratic Republic of the Congo",
    }
    for alias, country in aliases.items():
        if re.search(r"\b" + re.escape(alias) + r"\b", text, re.I):
            return country
    countries = [
        "United States", "United Kingdom", "Canada", "Germany", "France", "Netherlands", "Switzerland",
        "Belgium", "Denmark", "Sweden", "Norway", "Finland", "India", "Pakistan", "Kenya", "Uganda",
        "Tanzania", "South Africa", "Australia", "Austria", "Afghanistan", "Bangladesh", "Italy", "Spain",
        "Mexico", "Brazil", "Colombia", "Chile", "Peru", "Ecuador", "Panama", "Mozambique", "Nigeria",
        "Ethiopia", "Somalia", "Lebanon", "Syria", "Ukraine", "Japan", "China", "Thailand", "Nepal",
        "Malawi", "Rwanda", "South Sudan", "Egypt", "Qatar", "United Arab Emirates", "Indonesia",
        "Poland", "Ireland", "Portugal", "Czech Republic", "Greece", "Turkey", "Serbia", "Romania",
        "Hungary", "Bulgaria", "Philippines", "Singapore", "Malaysia", "Vietnam", "Argentina",
        "Costa Rica", "Guatemala", "Honduras", "El Salvador", "Dominican Republic", "Jamaica",
        "Ghana", "Zambia", "Zimbabwe", "Namibia", "Botswana", "Senegal", "Mali", "Niger",
        "Burkina Faso", "Cameroon", "Democratic Republic of the Congo", "Iraq", "Jordan",
        "Israel", "Kazakhstan", "Uzbekistan", "Georgia", "Armenia", "Azerbaijan",
    ]
    for country in countries:
        if re.search(r"\b" + re.escape(country) + r"\b", text, re.I):
            return country
    city_map = {
        "New York": "United States",
        "Washington": "United States",
        "Geneva": "Switzerland",
        "Nairobi": "Kenya",
        "Copenhagen": "Denmark",
        "Vienna": "Austria",
        "Paris": "France",
        "Rome": "Italy",
        "Bogota": "Colombia",
        "Bogotá": "Colombia",
        "Maputo": "Mozambique",
        "Quito": "Ecuador",
        "Kabul": "Afghanistan",
        "Kyiv": "Ukraine",
        "Cairo": "Egypt",
        "Berlin": "Germany",
        "Cologne": "Germany",
        "Bochum": "Germany",
        "Munich": "Germany",
        "Hamburg": "Germany",
        "Frankfurt": "Germany",
        "Stuttgart": "Germany",
        "Dusseldorf": "Germany",
        "Düsseldorf": "Germany",
        "Bonn": "Germany",
        "London": "United Kingdom",
        "Manchester": "United Kingdom",
        "Oxford": "United Kingdom",
        "Cambridge": "United Kingdom",
        "Dublin": "Ireland",
        "Brussels": "Belgium",
        "Amsterdam": "Netherlands",
        "Rotterdam": "Netherlands",
        "Stockholm": "Sweden",
        "Oslo": "Norway",
        "Helsinki": "Finland",
        "Madrid": "Spain",
        "Barcelona": "Spain",
        "Lisbon": "Portugal",
        "Warsaw": "Poland",
        "Prague": "Czech Republic",
        "Athens": "Greece",
        "Istanbul": "Turkey",
        "Chennai": "India",
        "Mumbai": "India",
        "Delhi": "India",
        "Bengaluru": "India",
        "Bangalore": "India",
        "Jakarta": "Indonesia",
        "Manila": "Philippines",
        "Kuala Lumpur": "Malaysia",
        "Singapore": "Singapore",
        "Bangkok": "Thailand",
        "Hanoi": "Vietnam",
        "Melbourne": "Australia",
        "Sydney": "Australia",
        "Toronto": "Canada",
        "Vancouver": "Canada",
        "Montreal": "Canada",
        "Ottawa": "Canada",
        "Boston": "United States",
        "Chicago": "United States",
        "Denver": "United States",
        "Seattle": "United States",
        "San Francisco": "United States",
        "Los Angeles": "United States",
        "Houston": "United States",
        "Atlanta": "United States",
        "Trenton": "United States",
        "Lancaster": "United States",
        "Anchorage": "United States",
        "Horsham": "United States",
        "Titusville": "United States",
        "Finney County": "United States",
    }
    for city, country in city_map.items():
        if re.search(r"\b" + re.escape(city) + r"\b", text, re.I):
            return country
    if "," in text:
        return text.split(",")[-1].strip()
    return text


def exact_url(url: str) -> bool:
    parts = urllib.parse.urlsplit(url)
    if parts.scheme not in {"http", "https"} or not parts.netloc:
        return False
    path = urllib.parse.unquote(parts.path.strip("/"))
    if GENERIC_PATH_RE.match(path):
        return False
    if any(host in parts.netloc for host in ["reliefweb.int", "linkedin.com", "unjobs.org"]):
        return bool(re.search(r"(job|jobs|vacanc|vacancies|view|opportun|positions?|\d{4,})", path, re.I))
    return len(path) > 10 and bool(re.search(r"(job|jobs|careers?|vacanc|position|posting|postings|opportun|apply|requisition|req|opening|\d{4,})", path, re.I))


def list_from_text(text: str, max_items: int = 5) -> list[str]:
    text = trim(text, 3500)
    chunks = re.split(r"(?<=[.!?])\s+", text)
    rows: list[str] = []
    for chunk in chunks:
        chunk = clean(chunk)
        if len(chunk) < 45 or EXCLUDE_RE.search(chunk):
            continue
        rows.append(chunk[:300])
        if len(rows) >= max_items:
            break
    return rows


def make_item(
    *,
    title: str,
    organization: str,
    description: str,
    url: str,
    source: str,
    posted_date: str,
    location: str = "",
    remote: bool = False,
    deadline: str = "",
    compensation: str = "Not specified",
) -> dict:
    title = clean(title)
    organization = clean(organization) or source
    description_text = trim(description, 1800)
    hay = f"{title} {organization} {description_text} {location}"
    country = infer_country(location)
    opportunity_type = classify_type(hay, remote=remote)
    category = classify_category(hay)
    deadline = deadline or parse_deadline(hay)
    summary = trim(f"{title} is an opportunity with {organization}. {description_text}", 430)
    responsibilities = list_from_text(description_text, 5) or [summary]
    requirements_match = re.search(r"(requirements?|qualifications?|you have|profile|eligibility)(.*)", description_text, re.I)
    requirements = list_from_text(requirements_match.group(2), 5) if requirements_match else list_from_text(description_text[-1500:], 4)
    return {
        "title": title,
        "organization": organization,
        "opportunity_type": opportunity_type,
        "category": category,
        "country": country,
        "location": clean(location) or country,
        "work_mode": "Remote" if remote or country == "Remote" else "On-site",
        "compensation": clean(compensation) or "Not specified",
        "duration": "Not specified",
        "start_date": posted_date,
        "posted_date": posted_date,
        "deadline": deadline,
        "deadline_label": deadline or "Not specified",
        "summary": summary,
        "description": description_text or summary,
        "responsibilities": responsibilities,
        "requirements": requirements,
        "benefits": [clean(compensation)] if compensation and compensation != "Not specified" else ["Compensation, benefits, fees, allowances, or volunteer arrangements are not specified."],
        "how_to_apply": "Use the Apply now button and follow the stated application instructions.",
        "verification_notes": "",
        "source": source,
        "source_url": url,
        "application_link": url,
        "discovery_method": source,
    }


def keep(item: dict) -> tuple[bool, str]:
    url = item.get("source_url", "")
    hay = " ".join(str(item.get(k, "")) for k in ["title", "organization", "description", "source_url"])
    if not in_window(item.get("posted_date", "")):
        return False, "outside-posted-window"
    if not exact_url(url):
        return False, "not-exact-url"
    if len(item.get("title", "")) < 8:
        return False, "thin-title"
    if EXCLUDE_RE.search(hay):
        return False, "excluded-category"
    if not JOB_SIGNAL_RE.search(hay):
        return False, "no-job-signal"
    return True, "kept"


def source_reliefweb(limit: int = 1000) -> list[dict]:
    url = (
        "https://api.reliefweb.int/v2/jobs?"
        + urllib.parse.urlencode(
            {
                "appname": "aitomic-jobs",
                "profile": "full",
                "limit": str(limit),
                "sort[]": "date.created:desc",
                "filter[field]": "date.created",
                "filter[value][from]": f"{START.isoformat()}T00:00:00+00:00",
                "filter[value][to]": f"{END.isoformat()}T23:59:59+00:00",
            }
        )
    )
    data = fetch_json(url)
    rows = []
    for row in data.get("data", []):
        fields = row.get("fields", {})
        posted = iso_date((fields.get("date") or {}).get("created"))
        sources = fields.get("source") or []
        org = sources[0].get("name", "") if sources and isinstance(sources[0], dict) else ""
        countries = fields.get("country") or []
        country_names = ", ".join(x.get("name", "") for x in countries if isinstance(x, dict))
        rows.append(
            make_item(
                title=fields.get("title", ""),
                organization=org,
                description=fields.get("body", ""),
                url=fields.get("url") or row.get("href", ""),
                source="ReliefWeb",
                posted_date=posted,
                location=country_names,
                deadline=iso_date((fields.get("date") or {}).get("closing")),
            )
        )
    return rows


def source_remoteok(limit: int = 250) -> list[dict]:
    data = fetch_json("https://remoteok.com/api")
    rows = []
    for job in data:
        if not isinstance(job, dict) or not job.get("position"):
            continue
        posted = iso_date(job.get("date"))
        rows.append(
            make_item(
                title=job.get("position", ""),
                organization=job.get("company", ""),
                description=job.get("description", ""),
                url=job.get("url", ""),
                source="RemoteOK",
                posted_date=posted,
                location="Remote",
                remote=True,
                compensation=" ".join(str(job.get(k) or "") for k in ["salary_min", "salary_max"]).strip() or "Not specified",
            )
        )
        if len(rows) >= limit:
            break
    return rows


def source_remotive(limit: int = 250) -> list[dict]:
    data = fetch_json("https://remotive.com/api/remote-jobs?limit=250")
    rows = []
    for job in data.get("jobs", [])[:limit]:
        rows.append(
            make_item(
                title=job.get("title", ""),
                organization=job.get("company_name", ""),
                description=job.get("description", ""),
                url=job.get("url", ""),
                source="Remotive",
                posted_date=iso_date(job.get("publication_date")),
                location=job.get("candidate_required_location") or "Remote",
                remote=True,
                compensation=job.get("salary") or "Not specified",
            )
        )
    return rows


def source_arbeitnow(limit: int = 250) -> list[dict]:
    data = fetch_json("https://www.arbeitnow.com/api/job-board-api")
    rows = []
    for job in data.get("data", [])[:limit]:
        rows.append(
            make_item(
                title=job.get("title", ""),
                organization=job.get("company_name", ""),
                description=job.get("description", ""),
                url=job.get("url", ""),
                source="Arbeitnow",
                posted_date=iso_date(job.get("created_at")),
                location=job.get("location", ""),
                remote=bool(job.get("remote")),
            )
        )
    return rows


def source_himalayas(limit: int = 250) -> list[dict]:
    data = fetch_json("https://himalayas.app/jobs/api")
    rows = []
    for job in data.get("jobs", [])[:limit]:
        location = ", ".join(job.get("locationRestrictions") or []) or "Remote"
        salary = ""
        if job.get("minSalary") or job.get("maxSalary"):
            salary = f"{job.get('currency') or ''} {job.get('minSalary') or ''}-{job.get('maxSalary') or ''} {job.get('salaryPeriod') or ''}".strip()
        rows.append(
            make_item(
                title=job.get("title", ""),
                organization=job.get("companyName") or clean((job.get("companySlug") or "").replace("-", " ").title()),
                description=job.get("description") or job.get("excerpt", ""),
                url=job.get("guid") or job.get("applicationLink", ""),
                source="Himalayas",
                posted_date=iso_date(job.get("pubDate")),
                location=location,
                remote=True,
                deadline=iso_date(job.get("expiryDate")),
                compensation=salary or "Not specified",
            )
        )
    return rows


def source_jobicy(limit: int = 250) -> list[dict]:
    data = fetch_json("https://jobicy.com/api/v2/remote-jobs?count=250")
    rows = []
    for job in data.get("jobs", [])[:limit]:
        rows.append(
            make_item(
                title=job.get("jobTitle", ""),
                organization=job.get("companyName", ""),
                description=job.get("jobDescription", ""),
                url=job.get("url", ""),
                source="Jobicy",
                posted_date=iso_date(job.get("pubDate")),
                location=job.get("jobGeo") or "Remote",
                remote=True,
                compensation=job.get("annualSalaryMin") and f"{job.get('salaryCurrency', '')} {job.get('annualSalaryMin')}-{job.get('annualSalaryMax')}" or "Not specified",
            )
        )
    return rows


def unjobs_page_links(page_url: str) -> list[tuple[str, str, str]]:
    body = fetch(page_url, timeout=25)
    _, text, links = parse_html(body)
    out = []
    for href, label in links:
        full = urllib.parse.urljoin(page_url, href).split("#", 1)[0]
        if "/vacancies/" not in full:
            continue
        out.append((full, clean(label), text))
    seen = {}
    for href, label, text in out:
        seen.setdefault(href, (href, label, text))
    return list(seen.values())


def unjobs_posted_from_context(context: str, title: str) -> str:
    idx = context.lower().find(title.lower()[:50])
    snippet = context[max(0, idx - 300) : idx + 500] if idx >= 0 else context[:800]
    iso_match = re.search(r"Updated:\s*(20\d{2}-\d{2}-\d{2})T", snippet, re.I)
    if iso_match:
        return iso_match.group(1)
    if re.search(r"updated:\s*(about\s*)?\d+\s*hours? ago|updated:\s*an?\s*hour ago", snippet, re.I):
        return TODAY.isoformat()
    if re.search(r"updated:\s*a day ago", snippet, re.I):
        return (TODAY - timedelta(days=1)).isoformat()
    if re.search(r"updated:\s*2 days ago", snippet, re.I):
        return (TODAY - timedelta(days=2)).isoformat()
    match = re.search(r"updated:\s*(\d{1,2}\s+[A-Z][a-z]+\s+2026)", snippet, re.I)
    if match:
        return iso_date(match.group(1))
    return ""


def source_unjobs(limit: int = 350) -> list[dict]:
    pages = ["https://unjobs.org"] + [f"https://unjobs.org/New/{i}" for i in range(2, 24)]
    links = []
    for page in pages:
        try:
            links.extend(unjobs_page_links(page))
        except Exception:
            continue
        time.sleep(0.1)
    rows = []
    used = set()
    for href, label, context in links:
        if href in used:
            continue
        used.add(href)
        posted = unjobs_posted_from_context(context, label)
        if posted and not in_window(posted):
            continue
        if not posted:
            continue
        title = re.sub(r"\s*\|\s*UNjobs.*$", "", clean(label))
        idx = context.lower().find(title.lower()[:50])
        snippet = context[max(0, idx - 120) : idx + 700] if idx >= 0 else context[:900]
        after_title = snippet[snippet.lower().find(title.lower()[:50]) + len(title[:50]) :] if title[:50].lower() in snippet.lower() else snippet
        org = ""
        org_match = re.search(r"^\s*(.*?)\s*Updated:", after_title, re.I)
        if org_match:
            org = clean(org_match.group(1))
        if not org:
            org = "UNjobs listed organization"
        bits = [x.strip() for x in title.split(",")]
        location = ", ".join(bits[-2:]) if len(bits) >= 3 else bits[-1] if len(bits) > 1 else ""
        text = clean(f"{title}. {org}. {snippet}")
        rows.append(
            make_item(
                title=title,
                organization=org,
                description=text,
                url=href,
                source="UNjobs",
                posted_date=posted,
                location=location,
                remote="remote" in f"{title} {location}".lower() or "home based" in f"{title} {location}".lower(),
                deadline=parse_deadline(text),
            )
        )
        if len(rows) >= limit:
            break
        time.sleep(0.05)
    return rows


def source_usajobs(limit: int = 250) -> list[dict]:
    # USAJOBS accepts unauthenticated search for many deployments but may reject without an API key.
    url = "https://data.usajobs.gov/api/search?DatePosted=3&ResultsPerPage=250"
    req = urllib.request.Request(url, headers={"User-Agent": "pastanlusiba@gmail.com", "Host": "data.usajobs.gov"})
    with urllib.request.urlopen(req, timeout=30, context=CTX) as res:
        data = json.loads(res.read(2_500_000).decode("utf-8", errors="ignore"))
    rows = []
    for item in data.get("SearchResult", {}).get("SearchResultItems", [])[:limit]:
        desc = item.get("MatchedObjectDescriptor", {})
        posted = iso_date(desc.get("PublicationStartDate"))
        locations = desc.get("PositionLocation") or []
        location = ", ".join(x.get("LocationName", "") for x in locations if isinstance(x, dict))
        apply_uris = desc.get("ApplyURI") or []
        url = apply_uris[0] if apply_uris else desc.get("PositionURI", "")
        rows.append(
            make_item(
                title=desc.get("PositionTitle", ""),
                organization=desc.get("OrganizationName", ""),
                description=" ".join(desc.get(k, "") for k in ["QualificationSummary", "UserArea", "JobSummary"] if isinstance(desc.get(k, ""), str)),
                url=url,
                source="USAJobs",
                posted_date=posted,
                location=location,
                deadline=iso_date(desc.get("ApplicationCloseDate")),
                compensation=clean(" ".join(desc.get("PositionRemuneration", [{}])[0].get(k, "") for k in ["MinimumRange", "MaximumRange", "RateIntervalCode"] if isinstance(desc.get("PositionRemuneration", [{}])[0], dict))) if desc.get("PositionRemuneration") else "Not specified",
            )
        )
    return rows


def crawl_exact_from_listing(source_name: str, start_url: str, limit: int = 80) -> list[dict]:
    rows = []
    try:
        body = fetch(start_url, timeout=20)
    except Exception:
        return rows
    listing_title, listing_text, links = parse_html(body)
    candidates = []
    for href, label in links:
        full = urllib.parse.urljoin(start_url, href).split("#", 1)[0]
        hay = f"{label} {full}"
        if exact_url(full) and JOB_SIGNAL_RE.search(hay) and not EXCLUDE_RE.search(hay):
            candidates.append((full, label))
    seen = set()
    for full, label in candidates[:limit]:
        if full in seen:
            continue
        seen.add(full)
        try:
            detail = fetch(full, timeout=18)
            title, text, _ = parse_html(detail)
        except Exception:
            title, text = label, listing_text
        combined = f"{title} {label} {text}"
        posted = ""
        for pattern in [
            r"(?:posted|published|date posted|posting date)[:\s-]*(20\d{2}-\d{2}-\d{2})",
            r"(?:posted|published|date posted|posting date)[:\s-]*(\d{1,2}\s+[A-Z][a-z]+\s+2026)",
            r"(?:posted|published|date posted|posting date)[:\s-]*([A-Z][a-z]+\s+\d{1,2},?\s+2026)",
        ]:
            m = re.search(pattern, combined, re.I)
            if m:
                posted = iso_date(m.group(1))
                break
        if not posted:
            continue
        org = source_name
        location_match = re.search(r"(?:location|duty station|job location)[:\s-]*([A-Z][^|\n]{2,80})", combined, re.I)
        rows.append(
            make_item(
                title=title or label,
                organization=org,
                description=text,
                url=full,
                source=source_name,
                posted_date=posted,
                location=location_match.group(1) if location_match else "",
                deadline=parse_deadline(combined),
                remote="remote" in combined.lower(),
            )
        )
    return rows


def source_selected_indexes() -> list[dict]:
    starts = [
        ("Impactpool", "https://www.impactpool.org/jobs"),
        ("Idealist", "https://www.idealist.org/en/jobs"),
        ("Fuzu", "https://www.fuzu.com/jobs"),
        ("MyJobMag Kenya", "https://www.myjobmag.co.ke/jobs-by-date"),
        ("Ethiojobs", "https://www.ethiojobs.net/latest-jobs"),
        ("Great Uganda Jobs", "https://www.greatugandajobs.com/jobs/"),
        ("Devex", "https://www.devex.com/jobs/search"),
        ("LinkedIn Jobs", "https://www.linkedin.com/jobs/search/?f_TPR=r259200"),
    ]
    rows = []
    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = {executor.submit(crawl_exact_from_listing, name, url): name for name, url in starts}
        for fut in as_completed(futures):
            try:
                rows.extend(fut.result())
            except Exception:
                pass
    return rows


def main() -> None:
    source_funcs = [
        source_reliefweb,
        source_unjobs,
        source_remoteok,
        source_remotive,
        source_arbeitnow,
        source_himalayas,
        source_jobicy,
        source_usajobs,
    ]
    raw: list[dict] = []
    audit: list[dict] = []
    for func in source_funcs:
        try:
            items = func()
            print(func.__name__, len(items), flush=True)
            raw.extend(items)
        except Exception as exc:
            audit.append({"source": func.__name__, "reason": "source-error", "error": repr(exc)})
            print(func.__name__, "ERROR", repr(exc), flush=True)

    dedup: dict[str, dict] = {}
    for item in raw:
        ok, reason = keep(item)
        audit.append({
            "title": item.get("title"),
            "organization": item.get("organization"),
            "source": item.get("source"),
            "source_url": item.get("source_url"),
            "posted_date": item.get("posted_date"),
            "reason": reason,
        })
        if not ok:
            continue
        key = item["source_url"].rstrip("/").lower()
        existing = dedup.get(key)
        if not existing or (len(item.get("description", "")) > len(existing.get("description", ""))):
            dedup[key] = item

    items = sorted(dedup.values(), key=lambda x: (x["posted_date"], x["source"], x["country"], x["organization"], x["title"]))
    OUT_JSON.parent.mkdir(parents=True, exist_ok=True)
    OUT_JSON.write_text(json.dumps(items, indent=2, ensure_ascii=False), encoding="utf-8")
    AUDIT_JSON.write_text(json.dumps(audit, indent=2, ensure_ascii=False), encoding="utf-8")

    headers = [
        "title", "organization", "opportunity_type", "category", "country", "location", "work_mode",
        "compensation", "posted_date", "deadline", "source", "source_url", "summary",
    ]
    with OUT_CSV.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        for item in items:
            writer.writerow({h: item.get(h, "") for h in headers})

    print(json.dumps({
        "raw": len(raw),
        "kept": len(items),
        "by_source": Counter(item["source"] for item in items).most_common(),
        "by_type": Counter(item["opportunity_type"] for item in items).most_common(),
        "by_posted_date": Counter(item["posted_date"] for item in items).most_common(),
        "json": str(OUT_JSON),
        "csv": str(OUT_CSV),
        "audit": str(AUDIT_JSON),
    }, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
