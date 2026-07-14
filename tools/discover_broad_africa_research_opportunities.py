import html
import json
import re
import ssl
import time
import os
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import date, datetime
from email.utils import parsedate_to_datetime
from html.parser import HTMLParser
from pathlib import Path
from collections import defaultdict

import pandas as pd

PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
DB = PROJECT / "data" / "aitomic_institution_source_database.xlsx"
OUT = Path(os.environ.get(
    "AITOMIC_OUT_FILE",
    PROJECT / "data" / "research_database_opportunity_candidates_broad_africa_2026-07-14.json",
))
HIGH = Path(os.environ.get(
    "AITOMIC_HIGH_FILE",
    PROJECT / "data" / "imported_research_database_opportunities_broad_africa_2026-07-14.json",
))

TODAY = date(2026, 7, 14)
UA = "AitomicJobsOpportunityDiscovery/2.0 (+https://aitomic.net)"
CTX = ssl._create_unverified_context()

AFRICA = {
    "Algeria", "Angola", "Benin", "Botswana", "Burkina Faso", "Burundi", "Cameroon",
    "Cape Verde", "Central African Republic", "Chad", "Comoros", "Congo", "Democratic Republic of the Congo",
    "Djibouti", "Egypt", "Equatorial Guinea", "Eritrea", "Eswatini", "Ethiopia", "Gabon",
    "Gambia", "Ghana", "Guinea", "Guinea-Bissau", "Ivory Coast", "Cote d’Ivoire", "Côte d'Ivoire",
    "Kenya", "Lesotho", "Liberia", "Libya", "Madagascar", "Malawi", "Mali", "Mauritania",
    "Mauritius", "Morocco", "Mozambique", "Namibia", "Niger", "Nigeria", "Rwanda",
    "São Tomé and Príncipe", "Sao Tome and Principe", "Senegal", "Seychelles", "Sierra Leone",
    "Somalia", "South Africa", "South Sudan", "Sudan", "Tanzania", "Togo", "Tunisia",
    "Uganda", "Zambia", "Zimbabwe",
}

PATHS = [
    "", "careers", "career", "jobs", "vacancies", "vacancy", "opportunities",
    "opportunity", "procurement", "tenders", "tender", "consultancies", "consultancy",
    "recruitment", "join-us", "work-with-us", "about/careers", "about/jobs",
    "category/jobs", "category/vacancies", "category/opportunities", "noticeboard/jobs",
    "jobs-careers", "business-opportunities", "calls", "announcements",
]

KEY_RE = re.compile(
    r"(vacanc|career|job|position|recruit|tender|procure|rfp|request[- ]for[- ]proposal|"
    r"consult|expression[- ]of[- ]interest|eoi|call[- ]for|call for|applications?|intern|"
    r"trainee|officer|coordinator|assistant|scientist|researcher|nurse|doctor|analyst|"
    r"manager|specialist|fellow|supplier|prequalification)",
    re.I,
)
EXCLUDE_RE = re.compile(r"(scholarship|grant|fellowship|award|competition|conference|webinar|symposium|newsletter|policy brief)", re.I)
BAD_DETAIL_RE = re.compile(
    r"(#gruemenu|25 comments|undefined|lorem ipsum|ooops|content unavailable|conteúdo indisponível|"
    r"card number|cvv|largest banks|\.path\{|\{fill:none|app-inscription|apk$)",
    re.I,
)
GENERIC_TITLE_RE = re.compile(
    r"^(home|about|contact|search|careers?|jobs?|vacanc(?:y|ies)|opportunities?|procurement|"
    r"tenders?|join us|work with us|login|register|apply|more details|read more|download|"
    r"page not found|404|all job post|recruitment portal)$",
    re.I,
)


class Parser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.title = []
        self.h1 = []
        self.h2 = []
        self.text = []
        self.links = []
        self.in_title = self.in_h1 = self.in_h2 = False
        self.href = None
        self.anchor = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "title":
            self.in_title = True
        elif tag == "h1":
            self.in_h1 = True
        elif tag == "h2":
            self.in_h2 = True
        elif tag == "a":
            self.href = attrs.get("href")
            self.anchor = []

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False
        elif tag == "h1":
            self.in_h1 = False
        elif tag == "h2":
            self.in_h2 = False
        elif tag == "a" and self.href is not None:
            self.links.append((self.href, clean(" ".join(self.anchor))))
            self.href = None
            self.anchor = []

    def handle_data(self, data):
        value = clean(data)
        if not value:
            return
        if self.in_title:
            self.title.append(value)
        if self.in_h1:
            self.h1.append(value)
        if self.in_h2:
            self.h2.append(value)
        if self.href is not None:
            self.anchor.append(value)
        if len(value) > 1:
            self.text.append(value)


def clean(value):
    return re.sub(r"\s+", " ", html.unescape(str(value or ""))).strip()


def fetch(url, timeout=7):
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "text/html,application/xhtml+xml"})
    with urllib.request.urlopen(req, timeout=timeout, context=CTX) as res:
        ctype = res.headers.get("content-type", "")
        if not any(x in ctype for x in ["text/html", "text/plain", "application/xhtml"]):
            return ""
        raw = res.read(900_000)
    return raw.decode("utf-8", errors="ignore")


def parse_html(body):
    p = Parser()
    p.feed(body)
    title = clean(" ".join(p.h1) or " ".join(p.title) or " ".join(p.h2))
    text = clean(" ".join(p.text[:420]))
    return title, text, p.links


def root_urls(home):
    try:
        split = urllib.parse.urlsplit(home)
        root = f"{split.scheme}://{split.netloc}/"
        return [urllib.parse.urljoin(root, p) for p in PATHS]
    except Exception:
        return []


def allowed_link(base, full):
    b = urllib.parse.urlsplit(base)
    s = urllib.parse.urlsplit(full)
    if s.scheme not in {"http", "https"}:
        return False
    if s.netloc == b.netloc:
        return True
    ats = ["lever.co", "greenhouse.io", "smartrecruiters.com", "workable.com", "applytojob.com", "bamboohr.com", "successfactors.com"]
    return any(host in s.netloc for host in ats)


def extract_links(base_url, page_url, body):
    title, text, links = parse_html(body)
    out = []
    for href, label in links:
        full = urllib.parse.urljoin(page_url, href).split("#", 1)[0]
        if not allowed_link(base_url, full):
            continue
        hay = f"{label} {full}"
        if KEY_RE.search(hay) and not EXCLUDE_RE.search(hay):
            out.append({"url": full, "anchor": label, "listing_page": page_url, "listing_title": title, "listing_text": text[:800]})
    # Treat the path page itself as a candidate if its title is specific.
    if KEY_RE.search(f"{title} {page_url}") and not GENERIC_TITLE_RE.match(title) and not EXCLUDE_RE.search(title):
        out.append({"url": page_url, "anchor": title, "listing_page": page_url, "listing_title": title, "listing_text": text[:800]})
    return out


def parse_deadline(text):
    patterns = [
        r"(?:deadline|closing date|closes|apply by|submission deadline|due date)[:\s-]*(\d{1,2}\s+[A-Z][a-z]+\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|submission deadline|due date)[:\s-]*([A-Z][a-z]+\s+\d{1,2},?\s+20\d{2})",
        r"(?:deadline|closing date|closes|apply by|submission deadline|due date)[:\s-]*(20\d{2}-\d{2}-\d{2})",
        r"(\d{1,2}\s+[A-Z][a-z]+\s+20\d{2})",
    ]
    for pat in patterns:
        m = re.search(pat, text, re.I)
        if not m:
            continue
        value = clean(m.group(1))
        for fmt in ("%d %b %Y", "%d %B %Y", "%B %d, %Y", "%B %d %Y", "%Y-%m-%d"):
            try:
                return datetime.strptime(value, fmt).date().isoformat()
            except ValueError:
                pass
        try:
            return parsedate_to_datetime(value).date().isoformat()
        except Exception:
            return value
    return ""


def deadline_is_past(value):
    if not value:
        return False
    try:
        return datetime.strptime(value[:10], "%Y-%m-%d").date() < TODAY
    except Exception:
        return False


def clean_title(title, anchor, url):
    title = clean(title)
    anchor = clean(anchor)
    if not title or GENERIC_TITLE_RE.match(title) or "page not found" in title.lower():
        title = anchor
    title = re.sub(r"\s*[-|]\s*(.+?)$", "", title).strip() if len(title) > 140 else title
    title = re.sub(r"\s*Deadline:\s*\d{1,2}\s+\w+\s+20\d{2}", "", title, flags=re.I)
    if GENERIC_TITLE_RE.match(title):
        path = urllib.parse.unquote(urllib.parse.urlsplit(url).path.strip("/").split("/")[-1])
        title = path.replace("-", " ").replace("_", " ").title()
    return clean(title)[:180]


def classify_type(text):
    low = text.lower()
    if any(k in low for k in ["tender", "procurement", "rfp", "request for proposal", "consult", "supplier", "prequalification", "eoi"]):
        return "Tenders / Consultancies"
    if "intern" in low or "trainee" in low:
        return "Internships"
    if "volunteer" in low:
        return "Volunteer opportunities"
    if "training" in low or "course" in low:
        return "Training / short courses"
    if "call for" in low or "applications" in low:
        return "Calls for applications"
    if "remote" in low:
        return "Remote work opportunities"
    return "Jobs"


def classify_category(sector, text):
    low = f"{sector} {text}".lower()
    if any(k in low for k in ["health", "medical", "biomedical", "disease", "nurse", "doctor", "clinical"]):
        return "Health"
    if any(k in low for k in ["agricultur", "livestock", "food", "forestry", "fish", "farm"]):
        return "Agriculture"
    if any(k in low for k in ["climate", "environment", "energy", "water", "geoscience", "wildlife"]):
        return "Environment"
    if any(k in low for k in ["data", "statistics", "digital", "technology", "engineering", "ict"]):
        return "Technology"
    if any(k in low for k in ["finance", "account", "procurement"]):
        return "Business & Finance"
    if any(k in low for k in ["communication", "media"]):
        return "Communications"
    if any(k in low for k in ["policy", "economic", "governance", "law", "legal"]):
        return "Legal & Policy"
    return "Research"


def detail_candidate(source, cand):
    try:
        body = fetch(cand["url"], timeout=8)
        page_title, page_text, _ = parse_html(body)
    except Exception:
        page_title, page_text = cand["anchor"], cand["listing_text"]
    combined = clean(f"{page_title} {cand['anchor']} {page_text}")
    title = clean_title(page_title, cand["anchor"], cand["url"])
    if not title or len(title) < 7 or EXCLUDE_RE.search(combined) or BAD_DETAIL_RE.search(combined) or GENERIC_TITLE_RE.match(title):
        return None
    deadline = parse_deadline(combined)
    if deadline_is_past(deadline):
        return None
    # Avoid ancient pages with clear old years and no future deadline.
    if not deadline and re.search(r"\b20(1\d|2[0-5])\b", combined) and not re.search(r"\b2026\b", combined):
        return None
    otype = classify_type(combined)
    return {
        "title": title,
        "organization": source["name"],
        "opportunity_type": otype,
        "category": classify_category(source.get("sectors", ""), combined),
        "country": source["countries_covered"],
        "location": source["countries_covered"],
        "work_mode": "Remote" if "remote" in combined.lower() else "On-site",
        "compensation": "Not specified",
        "deadline": deadline,
        "posted_date": "2026-07-14",
        "summary": clean(page_text or cand["listing_text"])[:900],
        "description": clean(page_text or cand["listing_text"])[:1800],
        "source": source["name"],
        "source_url": cand["url"],
        "application_link": cand["url"],
        "institution_url": source["url"],
        "discovery_listing_page": cand["listing_page"],
        "source_group": source.get("source_group", ""),
    }


def discover_source(source):
    seen = {}
    for url in root_urls(source["url"]):
        try:
            body = fetch(url)
            for cand in extract_links(source["url"], url, body):
                seen.setdefault(cand["url"].rstrip("/"), cand)
        except Exception:
            continue
        time.sleep(0.01)
    items = []
    for cand in list(seen.values())[:20]:
        item = detail_candidate(source, cand)
        if item:
            items.append(item)
    return items


def load_sources():
    df = pd.read_excel(DB, sheet_name="Source Database")
    mask = df["source_group"].astype(str).str.contains("Research institution|SCImago|Full directory|East Africa", case=False, na=False)
    df = df[mask].copy()
    df["url"] = df["url"].astype(str)
    df = df[df["url"].str.startswith("http")]
    df = df[~df["url"].str.contains("google.com|ror.org/search|scimagoir.com/rankings|wikidata.org", case=False, na=False)]
    df = df[df["countries_covered"].astype(str).isin(AFRICA)]
    df = df.drop_duplicates(subset=["name", "url", "countries_covered"])
    # Prefer official curated rows before broad SCImago rows.
    df["rank"] = 3
    df.loc[df["source_group"].astype(str).str.contains("East Africa", case=False, na=False), "rank"] = 0
    df.loc[df["source_group"].astype(str).str.contains("Full directory", case=False, na=False), "rank"] = 1
    df.loc[df["source_group"].astype(str).str.contains("Africa", case=False, na=False), "rank"] = 2
    df = df.sort_values(["rank", "countries_covered", "name"])
    return df.to_dict("records")


def main():
    sources = load_sources()
    max_sources = int(os.environ.get("AITOMIC_MAX_SOURCES", "500"))
    offset = int(os.environ.get("AITOMIC_SOURCE_OFFSET", "0"))
    if os.environ.get("AITOMIC_BALANCED", "1") == "1":
        grouped = defaultdict(list)
        for source in sources:
            grouped[source["countries_covered"]].append(source)
        balanced = []
        countries = sorted(grouped)
        target = offset + max_sources
        while len(balanced) < target and any(grouped.values()):
            for country in countries:
                if grouped[country]:
                    balanced.append(grouped[country].pop(0))
                    if len(balanced) >= target:
                        break
        sources = balanced[offset:target]
    else:
        sources = sources[offset:offset + max_sources]
    print(f"sources={len(sources)}")
    results = []
    with ThreadPoolExecutor(max_workers=24) as exe:
        futures = {exe.submit(discover_source, src): src for src in sources}
        for idx, fut in enumerate(as_completed(futures), 1):
            src = futures[fut]
            try:
                items = fut.result()
            except Exception as exc:
                items = []
                print("error", src["name"], exc)
            if items:
                print(idx, src["countries_covered"], src["name"], len(items), flush=True)
                results.extend(items)
            if idx % 100 == 0:
                print("progress", idx, "candidates", len(results), flush=True)
                checkpoint = sorted({r["source_url"].rstrip("/").lower(): r for r in results}.values(), key=lambda r: (r["country"], r["organization"], r["title"]))
                OUT.write_text(json.dumps(checkpoint, indent=2, ensure_ascii=False), encoding="utf-8")
    dedup = {}
    for item in results:
        key = item["source_url"].rstrip("/").lower()
        existing = dedup.get(key)
        if not existing or (item.get("deadline") and not existing.get("deadline")):
            dedup[key] = item
    candidates = sorted(dedup.values(), key=lambda r: (r["country"], r["organization"], r["title"]))
    OUT.write_text(json.dumps(candidates, indent=2, ensure_ascii=False), encoding="utf-8")
    high = [
        r for r in candidates
        if r.get("deadline") or r["source_url"].lower().endswith((".aspx", ".php")) or any(h in urllib.parse.urlsplit(r["source_url"]).netloc for h in ["lever.co", "greenhouse.io"])
    ]
    HIGH.write_text(json.dumps(high[:200], indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps({"sources": len(sources), "candidates": len(candidates), "high_confidence_batch": min(len(high), 200), "countries": len(set(r["country"] for r in candidates))}, indent=2))


if __name__ == "__main__":
    main()
