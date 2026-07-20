import html
import json
import os
import re
import ssl
import time
import urllib.parse
import urllib.request
from datetime import date, datetime
from html.parser import HTMLParser
from pathlib import Path

TODAY = date(2026, 7, 20)
PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
OUT = Path(os.environ.get("AITOMIC_INDEX_OUT", PROJECT / "data" / "indexed_opportunities_2026-07-20_batch1.json"))
AUDIT = Path(os.environ.get("AITOMIC_INDEX_AUDIT", PROJECT / "data" / "indexed_opportunities_audit_2026-07-20_batch1.json"))

CTX = ssl._create_unverified_context()
UA = "Mozilla/5.0 (compatible; AitomicJobsIndexedDiscovery/1.0; +https://aitomic.net)"

BAD_TITLE_RE = re.compile(
    r"^(details|apply|pitch us a role|shafqat|ojala|jobs?|job search|latest jobs|"
    r"find your next job|all jobs|vacancies|career|careers|login|register|job summary|"
    r"expression of interest|contribute building a secure nation|manufacturing positions)$",
    re.I,
)
EXCLUDE_RE = re.compile(r"(scholarship|grant|fellowship|award|competition|conference|webinar|symposium)", re.I)
OPPORTUNITY_RE = re.compile(
    r"(job|officer|assistant|coordinator|manager|specialist|analyst|engineer|developer|"
    r"consult|consultancy|intern|volunteer|programme|program|advisor|adviser|director|"
    r"researcher|scientist|administrator|associate|representative|evaluation|procurement)",
    re.I,
)


class TextParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.parts = []
        self.links = []
        self.href = None
        self.anchor = []
        self.title = []
        self.in_title = False

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "title":
            self.in_title = True
        if tag == "a":
            self.href = attrs.get("href")
            self.anchor = []
        if tag in {"p", "li", "br", "h1", "h2", "h3", "tr", "div"}:
            self.parts.append("\n")

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False
        if tag == "a" and self.href is not None:
            label = clean(" ".join(self.anchor))
            self.links.append((self.href, label))
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
        if self.href is not None:
            self.anchor.append(value)
        self.parts.append(value)


def clean(value):
    value = html.unescape(str(value or ""))
    value = re.sub(r"[\u200b-\u200f\ufeff]", "", value)
    value = re.sub(r"[ \t]+", " ", value)
    value = re.sub(r"\n\s*", "\n", value)
    return value.strip()


def plain_from_html(value):
    parser = TextParser()
    parser.feed(str(value or ""))
    text = clean(" ".join(parser.parts))
    text = re.sub(r"\s+", " ", text).strip()
    return text


def fetch(url, timeout=25):
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "text/html,application/json,*/*"})
    with urllib.request.urlopen(req, timeout=timeout, context=CTX) as response:
        return response.read(1_200_000).decode("utf-8", errors="ignore")


def fetch_json(url):
    return json.loads(fetch(url))


def trim(text, limit):
    text = clean(re.sub(r"\s+", " ", plain_from_html(text))).strip()
    if len(text) <= limit:
        return text
    cut = text[: max(0, limit - 3)]
    space = cut.rfind(" ")
    if space > 80:
        cut = cut[:space]
    return cut.rstrip(" .,;:") + "..."


def split_sentences(text, max_items=6):
    text = trim(text, 3000)
    chunks = re.split(r"(?<=[.!?])\s+", text)
    rows = []
    for chunk in chunks:
        chunk = clean(chunk)
        if len(chunk) < 35:
            continue
        if EXCLUDE_RE.search(chunk):
            continue
        rows.append(chunk[:280])
        if len(rows) >= max_items:
            break
    return rows


def parse_deadline(text):
    patterns = [
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date)[:\s-]*(\d{1,2}\s+[A-Z][a-z]+\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date)[:\s-]*([A-Z][a-z]+\s+\d{1,2},?\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|valid through|expires|expiry date)[:\s-]*(20\d{2}-\d{2}-\d{2})",
        r"\b(20\d{2}-\d{2}-\d{2})\b",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.I)
        if not match:
            continue
        value = clean(match.group(1))
        for fmt in ("%d %B %Y", "%d %b %Y", "%B %d, %Y", "%B %d %Y", "%Y-%m-%d"):
            try:
                return datetime.strptime(value, fmt).date().isoformat()
            except ValueError:
                pass
        return value
    return ""


def date_from_any(value):
    if not value:
        return ""
    if isinstance(value, (int, float)):
        try:
            return datetime.utcfromtimestamp(value).date().isoformat()
        except (OSError, OverflowError, ValueError):
            return ""
    value = str(value)
    if re.match(r"^\d{4}-\d{2}-\d{2}", value):
        return value[:10]
    return parse_deadline(value)


def expired(deadline):
    if not deadline:
        return False
    try:
        return datetime.strptime(deadline[:10], "%Y-%m-%d").date() < TODAY
    except ValueError:
        return False


def classify_category(text):
    low = text.lower()
    if any(x in low for x in ["health", "medical", "clinical", "pharma", "vaccine", "disease", "nutrition"]):
        return "Health"
    if any(x in low for x in ["agricultur", "food", "livestock", "fish", "forestry"]):
        return "Agriculture"
    if any(x in low for x in ["education", "learning", "school", "student", "teacher"]):
        return "Education"
    if any(x in low for x in ["software", "engineer", "developer", "data", "ai ", "technology", "digital", "cyber"]):
        return "Information Technology"
    if any(x in low for x in ["finance", "account", "budget", "bid", "procurement", "sales", "business"]):
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


def classify_type(text, remote=False):
    low = text.lower()
    if re.search(r"\bvolunteer\b", low):
        return "Volunteer opportunities"
    if re.search(r"\bintern(?:ship)?\b|\bstudent worker\b|\btrainee\b", low):
        return "Internships"
    if any(x in low for x in ["consultancy", "consultant", "retainer", "request for proposal", "rfp", "tender"]):
        return "Tenders / Consultancies"
    if remote:
        return "Remote work opportunities"
    return "Jobs"


def location_country(location):
    text = clean(location)
    if not text:
        return "Remote"
    if re.search(r"\b(remote|home based|home-based|worldwide|global)\b", text, re.I):
        return "Remote"
    city_map = {
        "Berlin": "Germany",
        "Bremen": "Germany",
        "Cologne": "Germany",
        "Dresden": "Germany",
        "Hamburg": "Germany",
        "Mannheim": "Germany",
        "Munich": "Germany",
        "München": "Germany",
        "Pleidelsheim": "Germany",
        "Sylt": "Germany",
        "Au in der Hallertau": "Germany",
        "Gemmingen": "Germany",
        "Hofheim": "Germany",
        "Lustadt": "Germany",
        "Mühlheim am Main": "Germany",
        "Norderstedt": "Germany",
        "Radbruch": "Germany",
        "Rheinmünster": "Germany",
        "Roth": "Germany",
        "Schwerte": "Germany",
        "Viereth-Trunstadt": "Germany",
    }
    for city, country in city_map.items():
        if re.search(r"\b" + re.escape(city) + r"\b", text, re.I):
            return country
    countries = [
        "United States", "United Kingdom", "Canada", "Germany", "France", "Netherlands", "Switzerland",
        "Belgium", "Denmark", "Sweden", "India", "Pakistan", "Kenya", "Uganda", "Tanzania", "South Africa",
        "Australia", "Austria", "Afghanistan", "Bangladesh", "Italy", "Spain", "Mexico", "Brazil",
    ]
    for country in countries:
        if re.search(r"\b" + re.escape(country) + r"\b", text, re.I):
            return country
    return text.split(",")[-1].strip() if "," in text else text


def base_item(title, org, description, source_name, source_url, location="", remote=False, deadline=""):
    title = clean(title)
    org = clean(org)
    text = plain_from_html(description)
    hay = f"{title} {org} {text} {location}"
    deadline = deadline or parse_deadline(hay)
    country = location_country(location)
    if remote:
        country = "Remote" if country in {"", "Remote"} else country
    summary = trim(text, 420)
    responsibilities = split_sentences(text, 5)
    requirements = []
    req_match = re.search(r"(requirements?|qualifications?|you have|what you bring|profile)(.*)", text, re.I)
    if req_match:
        requirements = split_sentences(req_match.group(2), 5)
    if not requirements:
        requirements = split_sentences(text[-1800:], 4)

    return {
        "title": title,
        "organization": org,
        "opportunity_type": classify_type(hay, remote=remote),
        "category": classify_category(hay),
        "country": country,
        "location": clean(location) or ("Remote" if remote else country),
        "work_mode": "Remote" if remote else "On-site",
        "compensation": "Not specified by source",
        "duration": "Not specified by source",
        "start_date": "",
        "deadline": deadline,
        "deadline_label": deadline or "Not specified by source",
        "summary": summary,
        "description": trim(text, 1400),
        "responsibilities": responsibilities[:5] or [summary],
        "requirements": requirements[:5],
        "benefits": ["See the indexed source page and official application page for compensation, benefits and contract terms."],
        "how_to_apply": "Open the source link and follow the application instructions provided by the index/source site.",
        "verification_notes": f"Found through {source_name}, an index/profile site for opportunities. Applicants should confirm details on the linked source/application page before applying.",
        "source": source_name,
        "source_url": source_url,
        "application_link": source_url,
    }


def keep(item):
    title = item["title"]
    hay = " ".join([item.get("title", ""), item.get("organization", ""), item.get("description", ""), item.get("source_url", "")])
    if len(title) < 10 or BAD_TITLE_RE.search(title):
        return False, "bad-title"
    if re.search(r"\b\d{3,} jobs?\b|\b\d{1,3},\d{3} jobs?\b", title, re.I):
        return False, "aggregated-search-page"
    if EXCLUDE_RE.search(hay):
        return False, "excluded-category"
    if not OPPORTUNITY_RE.search(hay):
        return False, "no-opportunity-signal"
    if expired(item.get("deadline", "")):
        return False, "expired"
    if len(item.get("description", "")) < 180:
        return False, "thin-description"
    if item.get("source") == "RemoteOK" and not re.search(
        r"(engineer|developer|designer|specialist|assistant|coordinator|manager|analyst|administrator|"
        r"officer|consultant|accountant|support|writer|marketing|recruiter|hr|finance|operations)",
        title,
        re.I,
    ):
        return False, "weak-remoteok-title"
    return True, "kept"


def remoteok(limit=25):
    data = fetch_json("https://remoteok.com/api")
    rows = []
    for job in data:
        if not isinstance(job, dict) or not job.get("position"):
            continue
        title = job.get("position", "")
        org = job.get("company", "")
        desc = job.get("description", "")
        location = "Remote"
        salary = job.get("salary_min") or job.get("salary_max")
        item = base_item(title, org, desc, "RemoteOK", job.get("url", ""), location, remote=True)
        if salary:
            item["compensation"] = str(salary)
        item["start_date"] = str(job.get("date", ""))[:10]
        item["benefits"] = ["Remote job indexed by RemoteOK.", "Review the RemoteOK listing and linked employer instructions before applying."]
        rows.append(item)
        if len(rows) >= limit:
            break
    return rows


def arbeitnow(limit=25):
    data = fetch_json("https://www.arbeitnow.com/api/job-board-api")
    rows = []
    for job in data.get("data", [])[:limit * 2]:
        item = base_item(
            job.get("title", ""),
            job.get("company_name", ""),
            job.get("description", ""),
            "Arbeitnow",
            job.get("url", ""),
            job.get("location", ""),
            remote=bool(job.get("remote")),
        )
        item["start_date"] = datetime.utcfromtimestamp(job.get("created_at", 0)).date().isoformat() if job.get("created_at") else ""
        item["benefits"] = ["Job indexed by Arbeitnow.", "Review the linked listing for salary, benefits and contract terms."]
        rows.append(item)
        if len(rows) >= limit:
            break
    return rows


def himalayas(limit=25):
    data = fetch_json("https://himalayas.app/jobs/api")
    rows = []
    for job in data.get("jobs", [])[:limit * 2]:
        location = ", ".join(job.get("locationRestrictions") or []) or "Remote"
        company = job.get("companyName")
        if not company or company in {"name", "company"}:
            company = (job.get("companySlug") or "Himalayas indexed employer").replace("-", " ").title()
        salary = ""
        if job.get("minSalary") or job.get("maxSalary"):
            salary = f"{job.get('currency') or ''} {job.get('minSalary') or ''}-{job.get('maxSalary') or ''} {job.get('salaryPeriod') or ''}".strip()
        item = base_item(
            job.get("title", ""),
            company,
            job.get("description") or job.get("excerpt", ""),
            "Himalayas",
            job.get("guid") or job.get("applicationLink", ""),
            location,
            remote=True,
            deadline=date_from_any(job.get("expiryDate")),
        )
        if salary:
            item["compensation"] = salary
        item["start_date"] = date_from_any(job.get("pubDate"))
        item["benefits"] = ["Remote job indexed by Himalayas.", "Review the listing and employer application link before applying."]
        rows.append(item)
        if len(rows) >= limit:
            break
    return rows


def unjobs_links(page):
    body = fetch(page)
    links = []
    for match in re.finditer(r'<a[^>]+href=["\']([^"\']*vacancies/[^"\']+)["\'][^>]*>(.*?)</a>', body, re.I | re.S):
        href = urllib.parse.urljoin(page, match.group(1))
        label = clean(re.sub("<.*?>", " ", match.group(2)))
        if label:
            links.append((href, label))
    seen = []
    used = set()
    for href, label in links:
        if href not in used:
            used.add(href)
            seen.append((href, label))
    return seen


def unjobs(limit=30):
    pages = [
        "https://unjobs.org/duty_stations/remote",
        "https://unjobs.org/themes/consultancy",
        "https://unjobs.org/themes/internship",
    ]
    rows = []
    used = set()
    for page in pages:
        try:
            links = unjobs_links(page)
        except Exception:
            continue
        for href, label in links[:18]:
            if href in used:
                continue
            used.add(href)
            try:
                body = fetch(href)
            except Exception:
                continue
            text = plain_from_html(body)
            title = re.sub(r"\s*\|\s*UNjobs.*$", "", clean(label))
            org = title.split(":", 1)[0].strip() if ":" in title and len(title.split(":", 1)[0]) < 60 else "UNjobs indexed organization"
            location_match = re.search(r",\s*([^,]{3,80}(?:Remote|Home based|[A-Z][A-Za-z ]+))\s*$", title)
            location = location_match.group(1) if location_match else "Remote" if "remote" in title.lower() else ""
            item = base_item(title, org, text, "UNjobs", href, location, remote="remote" in title.lower() or "home based" in title.lower())
            item["description"] = trim(text, 1400)
            rows.append(item)
            time.sleep(0.08)
            if len(rows) >= limit:
                return rows
    return rows


def main():
    sources = []
    for getter in [remoteok, arbeitnow, himalayas, unjobs]:
        try:
            items = getter()
            sources.extend(items)
            print(getter.__name__, len(items), flush=True)
        except Exception as exc:
            print(getter.__name__, "error", repr(exc), flush=True)

    kept = []
    audit = []
    seen = set()
    for item in sources:
        key = (item.get("title", "").lower(), item.get("organization", "").lower(), item.get("source_url", "").rstrip("/").lower())
        ok, reason = keep(item)
        audit.append({"reason": reason, "title": item.get("title"), "organization": item.get("organization"), "source": item.get("source"), "source_url": item.get("source_url")})
        if ok and key not in seen:
            seen.add(key)
            kept.append(item)

    kept.sort(key=lambda x: (x.get("opportunity_type", ""), x.get("country", ""), x.get("organization", ""), x.get("title", "")))
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(kept, indent=2, ensure_ascii=False), encoding="utf-8")
    AUDIT.write_text(json.dumps(audit, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps({
        "raw": len(sources),
        "kept": len(kept),
        "output": str(OUT),
        "audit": str(AUDIT),
    }, indent=2))


if __name__ == "__main__":
    main()
