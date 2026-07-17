import html
import json
import os
import re
import ssl
import time
import urllib.parse
import urllib.request
from collections import defaultdict
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import date, datetime
from email.utils import parsedate_to_datetime
from html.parser import HTMLParser
from pathlib import Path

import pandas as pd

PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
DB = PROJECT / "data" / "aitomic_institution_source_database.xlsx"
TODAY = date(2026, 7, 17)
OUT = Path(os.environ.get("AITOMIC_OUT_FILE", PROJECT / "data" / "global_all_sector_candidates_2026-07-17.json"))

UA = "AitomicJobsGlobalDiscovery/1.0 (+https://aitomic.net)"
CTX = ssl._create_unverified_context()

PATHS = [
    "", "careers", "career", "jobs", "vacancies", "vacancy", "opportunities",
    "opportunity", "procurement", "tenders", "tender", "consultancies", "consultancy",
    "recruitment", "join-us", "work-with-us", "about/careers", "about/jobs",
    "noticeboard/jobs", "jobs-careers", "business-opportunities", "calls",
    "announcements", "work-with-us/careers", "employment", "current-vacancies",
]

KEY_RE = re.compile(
    r"(vacanc|career|job|position|recruit|hiring|tender|procure|rfp|request[- ]for[- ]proposal|"
    r"consult|expression[- ]of[- ]interest|eoi|call[- ]for|call for|applications?|intern|"
    r"trainee|volunteer|officer|coordinator|assistant|scientist|researcher|analyst|"
    r"manager|specialist|supplier|prequalification|ssa|consultancy)",
    re.I,
)
EXCLUDE_RE = re.compile(
    r"(scholarship|grant|fellowship|award|competition|conference|webinar|symposium|newsletter|"
    r"policy brief|journal|proceedings|seminar|annual report|press release)",
    re.I,
)
GENERIC_TITLE_RE = re.compile(
    r"^(home|about|contact|search|careers?|jobs?|vacanc(?:y|ies)|opportunities?|procurement|"
    r"tenders?|join us|work with us|login|register|apply|more details|read more|download|"
    r"page not found|404|all job post|recruitment portal|current vacancies)$",
    re.I,
)
BAD_RE = re.compile(
    r"(undefined|lorem ipsum|ooops|content unavailable|card number|cvv|largest banks|"
    r"\.path\{|\{fill:none|javascript is disabled|enable cookies)",
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
        self.in_title = False
        self.in_h1 = False
        self.in_h2 = False
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


def fetch(url, timeout=9):
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "text/html,application/xhtml+xml,text/plain"})
    with urllib.request.urlopen(req, timeout=timeout, context=CTX) as res:
        ctype = res.headers.get("content-type", "")
        if not any(x in ctype for x in ["text/html", "text/plain", "application/xhtml"]):
            return ""
        return res.read(900_000).decode("utf-8", errors="ignore")


def parse_html(body):
    parser = Parser()
    parser.feed(body)
    title = clean(" ".join(parser.h1) or " ".join(parser.title) or " ".join(parser.h2))
    text = clean(" ".join(parser.text[:440]))
    return title, text, parser.links


def is_search_url(url):
    host = urllib.parse.urlsplit(url).netloc.lower()
    return "google." in host and "/search" in urllib.parse.urlsplit(url).path


def google_result_links(page_url, body):
    _, text, links = parse_html(body)
    out = []
    for href, label in links:
        if href.startswith("/url?"):
            qs = urllib.parse.parse_qs(urllib.parse.urlsplit(href).query)
            href = (qs.get("q") or [""])[0]
        if not href.startswith("http"):
            continue
        host = urllib.parse.urlsplit(href).netloc.lower()
        if any(bad in host for bad in ["google.", "gstatic.", "youtube.", "accounts.google"]):
            continue
        hay = f"{label} {href}"
        if KEY_RE.search(hay) and not EXCLUDE_RE.search(hay):
            out.append({"url": href.split("#", 1)[0], "anchor": label, "listing_page": page_url, "listing_title": "Google search result", "listing_text": text[:700]})
    return out[:10]


def root_urls(home):
    if not str(home).startswith("http"):
        return []
    if is_search_url(home):
        return [home]
    split = urllib.parse.urlsplit(home)
    root = f"{split.scheme}://{split.netloc}/"
    return [urllib.parse.urljoin(root, path) for path in PATHS]


def allowed_link(base, full):
    if is_search_url(base):
        return True
    b = urllib.parse.urlsplit(base)
    s = urllib.parse.urlsplit(full)
    if s.scheme not in {"http", "https"}:
        return False
    if s.netloc == b.netloc:
        return True
    ats = [
        "lever.co", "greenhouse.io", "smartrecruiters.com", "workable.com",
        "applytojob.com", "bamboohr.com", "successfactors.com", "icims.com",
        "myworkdayjobs.com", "oraclecloud.com", "linkedin.com/jobs",
    ]
    return any(host in full for host in ats)


def extract_links(source, page_url, body):
    if is_search_url(page_url):
        return google_result_links(page_url, body)
    title, text, links = parse_html(body)
    out = []
    for href, label in links:
        full = urllib.parse.urljoin(page_url, href).split("#", 1)[0]
        if not allowed_link(source["url"], full):
            continue
        hay = f"{label} {full}"
        if KEY_RE.search(hay) and not EXCLUDE_RE.search(hay):
            out.append({"url": full, "anchor": label, "listing_page": page_url, "listing_title": title, "listing_text": text[:800]})
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
    for pattern in patterns:
        match = re.search(pattern, text, re.I)
        if not match:
            continue
        value = clean(match.group(1))
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


def date_is_past(value):
    if not value:
        return False
    try:
        return datetime.strptime(value[:10], "%Y-%m-%d").date() < TODAY
    except Exception:
        return False


def classify_type(text):
    low = text.lower()
    if any(k in low for k in ["tender", "procurement", "rfp", "request for proposal", "consult", "supplier", "prequalification", "eoi", "ssa"]):
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
    if any(k in low for k in ["health", "medical", "biomedical", "disease", "nurse", "doctor", "clinical", "nutrition"]):
        return "Health"
    if any(k in low for k in ["agricultur", "livestock", "food", "forestry", "fish", "farm"]):
        return "Agriculture"
    if any(k in low for k in ["climate", "environment", "energy", "water", "geoscience", "wildlife"]):
        return "Environment"
    if any(k in low for k in ["data", "statistics", "digital", "technology", "engineering", "ict", "software", "platform"]):
        return "Information Technology"
    if any(k in low for k in ["finance", "account", "procurement", "budget"]):
        return "Business & Finance"
    if any(k in low for k in ["communication", "media", "advocacy"]):
        return "Communications"
    if any(k in low for k in ["education", "learning", "school"]):
        return "Education"
    if any(k in low for k in ["policy", "economic", "governance", "law", "legal"]):
        return "Legal & Policy"
    if any(k in low for k in ["humanitarian", "development", "wash", "protection"]):
        return "Humanitarian & Development"
    return "Research"


def clean_title(title, anchor, url):
    title = clean(title)
    anchor = clean(anchor)
    if not title or GENERIC_TITLE_RE.match(title) or "page not found" in title.lower():
        title = anchor
    title = re.sub(r"\s*Deadline:\s*\d{1,2}\s+\w+\s+20\d{2}", "", title, flags=re.I)
    if GENERIC_TITLE_RE.match(title):
        path = urllib.parse.unquote(urllib.parse.urlsplit(url).path.strip("/").split("/")[-1])
        title = path.replace("-", " ").replace("_", " ").title()
    return clean(title)[:180]


def detail_candidate(source, cand):
    try:
        body = fetch(cand["url"], timeout=10)
        page_title, page_text, _ = parse_html(body)
    except Exception:
        page_title, page_text = cand["anchor"], cand["listing_text"]
    combined = clean(f"{page_title} {cand['anchor']} {page_text} {cand['url']}")
    title = clean_title(page_title, cand["anchor"], cand["url"])
    if not title or len(title) < 8 or GENERIC_TITLE_RE.match(title):
        return None
    if EXCLUDE_RE.search(combined) or BAD_RE.search(combined) or not KEY_RE.search(combined):
        return None
    deadline = parse_deadline(combined)
    if date_is_past(deadline):
        return None
    if not deadline and re.search(r"\b20(1\d|2[0-5])\b", combined) and not re.search(r"\b2026\b", combined):
        return None
    source_name = clean(re.sub(r"<[^>]+>", " ", str(source["name"]))) or urllib.parse.urlsplit(source["url"]).netloc
    country = clean(source.get("countries_covered", ""))
    otype = classify_type(combined)
    return {
        "title": title,
        "organization": source_name,
        "opportunity_type": otype,
        "category": classify_category(source.get("sectors", ""), combined),
        "country": country,
        "location": country,
        "work_mode": "Remote" if "remote" in combined.lower() or "home-based" in combined.lower() else "On-site",
        "compensation": "Not specified",
        "deadline": deadline,
        "posted_date": TODAY.isoformat(),
        "summary": clean(page_text or cand["listing_text"])[:900],
        "description": clean(page_text or cand["listing_text"])[:1800],
        "source": source_name,
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
            for cand in extract_links(source, url, body):
                seen.setdefault(cand["url"].rstrip("/"), cand)
        except Exception:
            continue
        time.sleep(0.02)
    items = []
    for cand in list(seen.values())[:18]:
        item = detail_candidate(source, cand)
        if item:
            items.append(item)
    return items


def load_sources():
    df = pd.read_excel(DB, sheet_name="Source Database")
    df["url"] = df["url"].astype(str)
    df = df[df["url"].str.startswith("http")].copy()
    df = df[~df["url"].str.contains("ror.org/search|scimagoir.com/rankings|wikidata.org", case=False, na=False)]
    df = df.drop_duplicates(subset=["name", "url", "countries_covered"])
    df["rank"] = 5
    df.loc[~df["url"].str.contains("google.com/search", case=False, na=False), "rank"] = 1
    df.loc[df["source_group"].astype(str).str.contains("UN country|ReliefWeb|Impactpool|Government|Public service|Consultancy|NGO|INGO|LinkedIn|General all-sector", case=False, na=False), "rank"] = 0
    df.loc[df["source_group"].astype(str).str.contains("Research institution", case=False, na=False), "rank"] = 2
    df = df.sort_values(["rank", "countries_covered", "source_group", "name"])
    return df.to_dict("records")


def balanced_slice(sources, offset, limit):
    grouped = defaultdict(list)
    for source in sources:
        key = (source.get("countries_covered", ""), source.get("source_group", ""))
        grouped[key].append(source)
    keys = sorted(grouped)
    ordered = []
    target = offset + limit
    while len(ordered) < target and any(grouped.values()):
        for key in keys:
            if grouped[key]:
                ordered.append(grouped[key].pop(0))
                if len(ordered) >= target:
                    break
    return ordered[offset:target]


def main():
    sources = load_sources()
    limit = int(os.environ.get("AITOMIC_MAX_SOURCES", "700"))
    offset = int(os.environ.get("AITOMIC_SOURCE_OFFSET", "0"))
    sources = balanced_slice(sources, offset, limit)
    print(f"sources={len(sources)} offset={offset}")
    results = []
    with ThreadPoolExecutor(max_workers=int(os.environ.get("AITOMIC_WORKERS", "28"))) as executor:
        futures = {executor.submit(discover_source, source): source for source in sources}
        for idx, fut in enumerate(as_completed(futures), 1):
            source = futures[fut]
            try:
                items = fut.result()
            except Exception as exc:
                print("error", source.get("name"), exc)
                items = []
            if items:
                print(idx, source.get("countries_covered"), source.get("source_group"), source.get("name"), len(items), flush=True)
                results.extend(items)
            if idx % 100 == 0:
                checkpoint = sorted({r["source_url"].rstrip("/").lower(): r for r in results}.values(), key=lambda r: (r["country"], r["organization"], r["title"]))
                OUT.write_text(json.dumps(checkpoint, indent=2, ensure_ascii=False), encoding="utf-8")
                print("progress", idx, "candidates", len(checkpoint), flush=True)
    dedup = {}
    for item in results:
        key = item["source_url"].rstrip("/").lower()
        existing = dedup.get(key)
        if not existing or (item.get("deadline") and not existing.get("deadline")):
            dedup[key] = item
    items = sorted(dedup.values(), key=lambda r: (0 if r.get("deadline") else 1, r["country"], r["organization"], r["title"]))
    OUT.write_text(json.dumps(items, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps({
        "sources": len(sources),
        "candidates": len(items),
        "countries": len({x["country"] for x in items}),
        "output": str(OUT),
    }, indent=2))


if __name__ == "__main__":
    main()
