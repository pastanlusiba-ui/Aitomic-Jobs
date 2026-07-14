import json
import os
import re
from collections import Counter
from datetime import date, datetime
from email.utils import parsedate_to_datetime
from pathlib import Path

PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
INFILE = Path(os.environ.get(
    "AITOMIC_CANDIDATE_FILE",
    PROJECT / "data" / "research_database_opportunity_candidates_broad_africa_2026-07-14.json",
))
OUTFILE = Path(os.environ.get(
    "AITOMIC_PUBLISH_FILE",
    PROJECT / "data" / "imported_research_database_opportunities_broad_africa_publish_2026-07-14.json",
))
TODAY = date(2026, 7, 14)

MONTHS = {
    "janvier": "January",
    "fevrier": "February",
    "février": "February",
    "mars": "March",
    "avril": "April",
    "mai": "May",
    "juin": "June",
    "juillet": "July",
    "aout": "August",
    "août": "August",
    "septembre": "September",
    "octobre": "October",
    "novembre": "November",
    "decembre": "December",
    "décembre": "December",
}

OPPORTUNITY_RE = re.compile(
    r"(vacanc|career|job|position|recruit|tender|procure|rfp|request for proposal|"
    r"consultation|consultancy|consultant|expression of interest|eoi|call for|applications?|"
    r"internship|intern\b|trainee|volunteer|officer|coordinator|assistant|scientist|"
    r"researcher|analyst|manager|specialist|supplier|prequalification|avis d.?appel|avis de consultation)",
    re.I,
)
GENERIC_RE = re.compile(
    r"^(home|all posts|all job posts?|jobs?|careers?|vacanc(?:y|ies)|opportunities?|"
    r"procurement|tenders?|consultanc(?:y|ies)|training|events?|news|international|"
    r"read more|download|contact|about|search|login|register|post|page)$",
    re.I,
)
BAD_RE = re.compile(
    r"(scholarship|grant|fellowship|award|competition|conference|webinar|symposium|"
    r"newsletter|policy brief|journal|proceedings|seminar|colloque|journée d.?études|"
    r"salon national|hommage|rencontre internationale|école d.?automne|atelier sur)",
    re.I,
)
BROKEN_RE = re.compile(
    r"(#gruemenu|25 comments|33 decembre|undefined|lorem ipsum|ooops|content unavailable|"
    r"conteúdo indisponível|card number|cvv|largest banks|\.path\{|\{fill:none|"
    r"كلمة رئيس الجامعة|تسجيلات جامعية)",
    re.I,
)
NON_DETAIL_RE = re.compile(
    r"(advanced job search|jobs by email|jobs by rss|view all vacancies|full list of current|"
    r"current vacancies current vacancies|academic \(\d+\)|professional services \(\d+\)|"
    r"\bcareers?\s*\||\bcareers?$|career development center|welcome|bienvenue|our research|"
    r"directorate of research|procurement management unit|online application guide|"
    r"application form|fee-paying form|rpl application|refund process|career structure|"
    r"télécharger|apk$|app-inscription|list of bidders|notice of cancellation|cancellation of consultation|"
    r"unsuccessful consultation|avis d.?attribution|attribution provisoire|"
    r"centre de développement des energies renouvelables|centre de développement des énergies renouvelables|"
    r"مركز التوظيف|ompong|lisgis official|general conditions of contract|annual procurement plan|"
    r"vacancies at nust|vacancies - national university|advert cultural festival|master of public policy)",
    re.I,
)


def clean(value):
    return re.sub(r"\s+", " ", str(value or "")).strip()


def normalize_months(value):
    out = value
    for fr, en in MONTHS.items():
        out = re.sub(fr, en, out, flags=re.I)
    return out


def parse_date(value):
    value = clean(value)
    if not value:
        return None
    value = normalize_months(value)
    value = re.sub(r"(\d{1,2})(?:er|e)\b", r"\1", value, flags=re.I)
    for fmt in ("%Y-%m-%d", "%d %b %Y", "%d %B %Y", "%B %d, %Y", "%B %d %Y"):
        try:
            return datetime.strptime(value[:30], fmt).date()
        except ValueError:
            pass
    try:
        return parsedate_to_datetime(value).date()
    except Exception:
        return None


def dates_in_text(text):
    text = normalize_months(text)
    patterns = [
        r"\b20\d{2}-\d{2}-\d{2}\b",
        r"\b\d{1,2}\s+(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+20\d{2}\b",
        r"\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{1,2},?\s+20\d{2}\b",
    ]
    found = []
    for pat in patterns:
        for match in re.findall(pat, text, flags=re.I):
            parsed = parse_date(match)
            if parsed:
                found.append(parsed)
    return found


def normalize_title(title):
    title = clean(title)
    title = re.sub(r"\s*[-|]\s*(?:[^-|]{1,80})$", "", title).strip() if len(title) > 150 else title
    title = re.sub(r"\s*Deadline:\s*\d{1,2}\s+\w+\s+20\d{2}", "", title, flags=re.I)
    return clean(title).strip(" -")


def should_keep(item):
    title = normalize_title(item.get("title", ""))
    text = clean(" ".join([
        title,
        item.get("summary", ""),
        item.get("description", ""),
        item.get("source_url", ""),
    ]))
    if not title or len(title) < 10:
        return False, "short-title"
    if GENERIC_RE.search(title) or BAD_RE.search(text) or BROKEN_RE.search(text) or NON_DETAIL_RE.search(title):
        return False, "generic-or-bad"
    if not OPPORTUNITY_RE.search(text):
        return False, "no-opportunity-keyword"

    deadline_raw = clean(item.get("deadline", ""))
    deadline = None if deadline_raw in {"2099-12-31", "31 Dec 2099"} else parse_date(deadline_raw)
    all_dates = dates_in_text(text)
    future_dates = [d for d in all_dates if d >= TODAY]
    years = [int(y) for y in re.findall(r"\b(20\d{2})\b", text)]

    if deadline and deadline < TODAY:
        return False, "expired-deadline"
    if deadline and deadline >= TODAY:
        item["deadline"] = deadline.isoformat()
    elif future_dates:
        item["deadline"] = min(future_dates).isoformat()
    else:
        item["deadline"] = ""

    if years and max(years) < TODAY.year:
        return False, "old-year-only"

    # Jobs and calls without a deadline need a strong current signal.
    if not item["deadline"] and item.get("opportunity_type") in {"Jobs", "Calls for applications", "Internships", "Training / short courses"}:
        if TODAY.year not in years and not re.search(r"(current|ongoing|open positions?|recruitment portal)", text, re.I):
            return False, "weak-current-signal"
        if item.get("opportunity_type") in {"Internships", "Training / short courses"} and not re.search(r"(internship|intern\b|trainee|training|short course|call for applications?|apply)", title, re.I):
            return False, "weak-training-internship"

    item["title"] = title
    if re.search(r"(assistant biosecurity officer|assistant conservation officer|conservation coordinator|security officer|administration and human resource officer|field research officer)", title, re.I):
        item["opportunity_type"] = "Jobs"
    if re.search(r"\b(eoi|rfq|tender|procurement|provision of service|renewal of subscription|notice of consultation|avis de consultation|consultation n|tor for|service provider)\b", title, re.I) or "procurement/bids" in item.get("source_url", ""):
        item["opportunity_type"] = "Tenders / Consultancies"
    item["summary"] = clean(item.get("summary", ""))[:900]
    item["description"] = clean(item.get("description", ""))[:1800]
    return True, "kept"


def main():
    items = json.load(open(INFILE, encoding="utf-8"))
    kept = []
    seen = set()
    seen_title_org = set()
    reasons = Counter()
    for item in items:
        source_url = clean(item.get("source_url", "")).rstrip("/")
        if not source_url or source_url.lower() in seen:
            reasons["duplicate-or-missing-url"] += 1
            continue
        ok, reason = should_keep(item)
        reasons[reason] += 1
        if ok:
            title_org = (item.get("organization", "").strip().lower(), item.get("title", "").strip().lower())
            if title_org in seen_title_org:
                reasons["duplicate-title-org"] += 1
                continue
            seen.add(source_url.lower())
            seen_title_org.add(title_org)
            kept.append(item)

    kept.sort(key=lambda row: (
        0 if row.get("deadline") else 1,
        row.get("country", ""),
        row.get("organization", ""),
        row.get("title", ""),
    ))
    OUTFILE.write_text(json.dumps(kept, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps({
        "input": len(items),
        "publishable": len(kept),
        "countries": len({x.get("country", "") for x in kept}),
        "by_country": Counter(x.get("country", "") for x in kept).most_common(),
        "by_type": Counter(x.get("opportunity_type", "") for x in kept).most_common(),
        "reasons": reasons.most_common(),
        "output": str(OUTFILE),
    }, indent=2, ensure_ascii=False))
    for i, item in enumerate(kept[:80], 1):
        print(f"{i:03d}. {item.get('country')} | {item.get('opportunity_type')} | {item.get('organization')} | {item.get('title')} | {item.get('deadline', '')}")


if __name__ == "__main__":
    main()
