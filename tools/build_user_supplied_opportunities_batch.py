import csv
import hashlib
import io
import json
import re
from pathlib import Path

import pandas as pd


PROJECT = Path("/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs")
OUT = PROJECT / "data" / "imported_user_supplied_global_opportunities_2026-07-14.json"

WORKBOOKS = [
    Path("/Users/pastanlusiba/Downloads/Aitomic_Latin_America_Caribbean_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_North_America_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_Europe_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_Asia_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_Africa_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_Global_Remote_opportunities_batch_1_14_July_2026.xlsx"),
    Path("/Users/pastanlusiba/Downloads/Aitomic_Oceania_opportunities_batch_1_14_July_2026.xlsx"),
]

ATTACHMENT_CSV = Path("/Users/pastanlusiba/.codex/attachments/ef704c13-6c9e-4b75-b423-05c63df7e89b/pasted-text.txt")

EXTRA_CSV_BLOCKS = [
    """Opportunity Title,Organization,Location,Type,Link
"INTERNSHIP: Office of the Director-General",UNESCO,Multiple,Internships,https://careers.unesco.org
"INTERNSHIP: Sciences",UNESCO,Multiple,Internships,https://careers.unesco.org
"INTERNSHIP: Education Sector",UNESCO,Multiple locations,Internships,https://www.unesco.org/en/careers
"INTERNSHIP: Communications and Public Engagement",UNESCO,Multiple locations,Internships,https://www.unesco.org/en/careers
"INTERNSHIP: All Sectors / Bureaus",UNESCO,Headquarters/Field Offices,Internships,https://www.unesco.org/en/careers
"INTERNSHIP: Natural Science Sector",UNESCO,Multiple,Internships,https://www.unesco.org/en/careers
"INTERNSHIP: Culture Sector",UNESCO,Multiple,Internships,https://www.unesco.org/en/careers
"Administration (internship)",UNESCO,Nairobi Kenya,Internships,https://www.unesco.org/en/careers
"WHO Internship Programme",World Health Organization (WHO),Various,Internships,https://www.who.int/careers
"WHO Internship Programme - WPRO",World Health Organization (WHO),Western Pacific Region,Internships,https://www.who.int
"WHO Internship Programme - EMRO",World Health Organization (WHO),Eastern Mediterranean Region,Internships,https://www.emro.who.int
"WHO Internship Programme - WKC",World Health Organization (WHO),Various,Internships,https://wkc.who.int
"UNESCO Internship Programme",UNESCO,Various,Internships,https://careers.unesco.org
"Artificial Intelligence Intern",UNICEF,Abu Dhabi,Internships,https://www.unicef.org/careers
"Research Intern Digital Impact Division",UNICEF,Geneva,Internships,https://www.unicef.org/careers
"Sustainable Finance Analytics & Strategy intern",UNDP,Home-based,Internships,https://www.undp.org/careers
"Sustainable Finance Research intern",UNDP,Home-based,Internships,https://www.undp.org/careers
"Intern (Division of Knowledge Management)",UNIDO,Various,Internships,https://www.unido.org/careers
"Intern (Energy Systems and Industrial Decarbonization)",UNIDO,Vienna,Internships,https://www.unido.org/careers
"Intern - Sustainable Investment and Responsible Business",UNIDO,Vienna,Internships,https://www.unido.org/careers
"Intern - Crop Improvement",CIP,Nairobi Kenya,Internships,https://cipotato.org/careers
"Paid Internship",IFPRI,Abuja Nigeria,Internships,https://www.ifpri.org/careers
"Treasury Operations Intern - Capital Markets",Inter-American Development Bank,Washington D.C.,Internships,https://www.iadb.org/en/careers
"Diplomatic Cultural and Academic Program Intern",Inter-American Development Bank,Washington D.C.,Internships,https://www.iadb.org/en/careers
"AI/Data Engineering - Data Driven Communications Intern",Inter-American Development Bank,Washington D.C.,Internships,https://www.iadb.org/en/careers
"Partnership Development Intern",UNOPS,Various,Internships,https://www.unops.org/careers
"Administration Intern",UNOPS,South Sudan,Internships,https://www.unops.org/careers
"Stagiaire graphiste et designer",UNFPA,Dakar Senegal,Internships,https://www.unfpa.org/careers
"Women's Economic Empowerment Intern",UN Women,Dakar Senegal,Internships,https://www.unwomen.org/en/about-us/employment
"Data Intern",UN Women,Nairobi Kenya,Internships,https://www.unwomen.org/en/about-us/employment
"Internship: Public Affairs Branch",OPCW,The Hague,Internships,https://www.opcw.org/careers
"Communications Intern",ITU,Various,Internships,https://www.itu.int/en/careers
"WTO Internship Programme",WTO,Geneva Switzerland,Internships,https://www.wto.org/english/thewto_e/vacan_e/intern_e.htm
"IFAD Internship Programme",IFAD,Rome,Internships,https://www.ifad.org/en/careers
""",
    """Opportunity Title,Organization,Location,Type,Link
"Long-Term Agreement (LTA) for Individual Consultant as Behavioural Scientists",UNICEF,Remote,Consultancies,https://jobs.unicef.org
"Paid Media Consultant for EU and EAP Markets",UNICEF,Geneva Switzerland,Consultancies,https://jobs.unicef.org
"Consultant for Research Coordination, Remedial Learning Implementation Research",UNICEF,Rwanda,Consultancies,https://jobs.unicef.org
"Social Behavior Change Consultant",UNICEF,Juba South Sudan,Consultancies,https://jobs.unicef.org
"National Consultant – Development of Uganda Gavi 6.0 Country Application Package",UNICEF,Kampala Uganda,Consultancies,https://jobs.unicef.org
"International Consultancy: Resource Mobilisation Specialist",UNICEF,Lilongwe Malawi,Consultancies,https://jobs.unicef.org
"Communication Consultant (Joint Programme Coordination Unit)",UNICEF,Kathmandu Nepal,Consultancies,https://jobs.unicef.org
"Information Management Consultant (Nationals only)",UNICEF,Nairobi Kenya,Consultancies,https://jobs.unicef.org
"Public Health Emergency Specialist (National Consultant)",UNICEF,Kampala Uganda,Consultancies,https://jobs.unicef.org
"Communication for Private Sector Fundraising Consultant",UNICEF,Astana Kazakhstan,Consultancies,https://jobs.unicef.org
"National Consultant – Global Fund TB Lead Writer",World Health Organization (WHO),Jakarta Indonesia,Consultancies,https://www.who.int/careers
"Consultant - Research and Publication",World Health Organization (WHO),Remote/Multiple,Consultancies,https://www.who.int/careers
"Consultant (CLT)",UNESCO,Beijing China,Consultancies,https://careers.unesco.org
"National Junior Consultant Oral History (3 posts)",UNESCO,Tashkent Uzbekistan,Consultancies,https://careers.unesco.org
"Consultant",UNESCO,Lilongwe Malawi,Consultancies,https://careers.unesco.org
"Consultant",UNESCO,Islamabad Pakistan,Consultancies,https://careers.unesco.org
"Consultancy for Design of a Phytosanitary Risk-based Border Control System",World Bank,Colombia,Consultancies,https://www.worldbank.org
"Wastewater Treatment Plant (WWTP) Tivaouane Peulh - Climate Consultant",World Bank,Dakar Senegal,Consultancies,https://www.worldbank.org
"BICs Consultant for Train of Zipaquira",World Bank,Colombia,Consultancies,https://www.worldbank.org
"Ukraine Agribusiness Investment Strategy Consultant",World Bank,Ukraine,Consultancies,https://www.worldbank.org
"Consultant for Impact Evaluation Research",IFPRI,Addis Ababa,Consultancies,https://www.ifpri.org/careers
"Consultant (Knowledge Product & Outreach)",CIP,Various,Consultancies,https://cipotato.org/careers
"Consultant (Knowledge Management Learning And Communications)",WorldFish,Cairo Egypt,Consultancies,https://www.worldfishcenter.org/careers
"Consultant for Senior ICT/Digital Policy",ITU,Africa,Consultancies,https://www.itu.int/en/careers
"Senior Policy and Legislative Strategy Consultant",ITU,Various,Consultancies,https://www.itu.int/en/careers
"Cybersecurity Programme Consultant",ITU,Various,Consultancies,https://www.itu.int/en/careers
"BDT Digital Ecosystem Consultant Roster",ITU,Various,Consultancies,https://www.itu.int/en/careers
"Child Online Protection Consultant Roster",ITU,Various,Consultancies,https://www.itu.int/en/careers
"Outreach and Engagement Consultant",UNEP,Various,Consultancies,https://www.unep.org/working-with-us
"Junior Analyst — Sustainable Public Finance",UNEP,Geneva,Consultancies,https://www.unep.org/working-with-us
"National Investment Coordinator",UNIDO,Various,Consultancies,https://www.unido.org/careers
"Readvertisement - Activity Coordinator - Entomologist",ICARDA,UAE,Consultancies,https://www.icarda.org/careers
"Senior Internal Auditor (P-4)",OPCW,The Hague,Consultancies,https://www.opcw.org/careers
"Humanitarian Action and Gender Consultant",UN Women,Home-based,Consultancies,https://www.unwomen.org/en/about-us/employment
"Financial Management - HACT Roving National Consultant",UNICEF,Abuja Nigeria / Remote,Consultancies,https://www.unicef.org/careers
"Child protection Consultant",UNICEF,Addis Ababa Ethiopia,Consultancies,https://www.unicef.org/careers
"Emergency Specialist (Cash Based Assistance Beneficiary Data), P-3",UNICEF,Nairobi Kenya,Consultancies,https://jobs.unicef.org
""",
    """Opportunity Title,Organization,Location,Type,Link
"TENDER: Procurement of High-Performance Liquid Chromatography (HPLC)",United Nations,UNGM Portal,Tenders,https://iran.un.org
"RFQ17-2026 : Acquisition d’équipements et de matériels spécialisés de laboratoire",UNDP,Tunisia,Tenders,https://tunisia.un.org
"RFQ 2026-11 : Acquisition d’un véhicule de transport",UNDP,Tunisia,Tenders,https://tunisia.un.org
"RFQ for provision of office premises renting services",UNDP,Azerbaijan,Tenders,https://azerbaijan.un.org
"Tenders - FAO Regional Office for Latin America and the Caribbean",FAO,Latin America and Caribbean,Tenders,https://www.fao.org
"Tenders",AfricaRice,Various,Tenders,https://www.africarice.org/procurement
"Procurement Notices",World Bank Group,Various,Tenders,https://www.worldbank.org/en/procurement
"Tenders and Procurement",UNDP,Various,Tenders,https://www.undp.org/procurement
"Tender Notices",UNESCO,Various,Tenders,https://www.unesco.org/en/procurement
"Supply and Delivery of Laboratory Equipment",WHO,Various,Tenders,https://www.who.int/about/procurement
"Current procurement opportunities",UNICEF,Various,Tenders,https://www.unicef.org/procurement
"Tender Opening Schedule",United Nations,Various,Tenders,https://www.un.org
""",
    """Opportunity Title,Organization,Location,Type,Link
"Long-Term Agreement (LTA) for Individual Consultant as Behavioural Scientists",UNICEF,Remote,Remote,https://jobs.unicef.org
"Paid Media Consultant for EU and EAP Markets",UNICEF,Geneva Switzerland (Remote),Remote,https://jobs.unicef.org
"International Consultancy: Resource Mobilisation Specialist",UNICEF,Lilongwe Malawi (Remote/Work from home),Remote,https://jobs.unicef.org
"Consultant - Research and Publication",World Health Organization (WHO),Remote,Remote,https://www.who.int/careers
"Sustainable Finance Analytics & Strategy intern",UNDP,Home-based,Remote,https://www.undp.org/careers
"Sustainable Finance Research intern",UNDP,Home-based,Remote,https://www.undp.org/careers
"Financial Management - HACT Roving National Consultant",UNICEF,Abuja Nigeria / Remote,Remote,https://www.unicef.org/careers
"Recruitment Consultant",UNFPA,Remote,Remote,https://www.unfpa.org/careers
"Humanitarian Action and Gender Consultant",UN Women,Home-based,Remote,https://www.unwomen.org/en/about-us/employment
"UNESCO Online Volunteering",UNESCO,Various,Volunteer,https://careers.unesco.org
"UN Volunteers Talent Database",UN Volunteers,Various,Volunteer,https://www.unv.org/become-volunteer
"UNICEF UNV Youth on the Move Programme",UNICEF/UNV,Various,Volunteer,https://www.unv.org
"World Heritage Volunteers Initiative",UNESCO,Various,Volunteer,https://whc.unesco.org
"Volunteer with UNDP",UNDP,Various,Volunteer,https://www.undp.org/volunteer
""",
]

COUNTRY_ALIASES = {
    "UAE": "United Arab Emirates",
    "USA": "United States",
    "US": "United States",
    "D.C.": "United States",
    "Lao PDR": "Laos",
    "Laos": "Laos",
}

COUNTRY_NAMES = [
    "Afghanistan", "Albania", "Argentina", "Australia", "Azerbaijan", "Bangladesh", "Barbados",
    "Bosnia", "Brazil", "Canada", "China", "Colombia", "Egypt", "Ethiopia", "France", "Germany",
    "Ghana", "India", "Indonesia", "Italy", "Japan", "Kazakhstan", "Kenya", "Lebanon", "Libya",
    "Malawi", "Malaysia", "Mongolia", "Morocco", "Mozambique", "Namibia", "Nepal", "Nigeria",
    "Pakistan", "Panama", "Philippines", "Rwanda", "Senegal", "Solomon Islands", "South Africa",
    "South Sudan", "Sudan", "Switzerland", "Thailand", "Tunisia", "Turkey", "Uganda", "Ukraine",
    "United Arab Emirates", "United States", "Uzbekistan", "Vietnam", "Yemen", "Zimbabwe", "Zambia",
]


def clean(value):
    if pd.isna(value):
        return ""
    return re.sub(r"\s+", " ", str(value).strip())


def infer_country(location, fallback=""):
    location = clean(location)
    fallback = clean(fallback)
    if fallback and fallback not in {"Various", "Remote / various", "Remote / various locations", "Multiple", "Multiple locations"}:
        return COUNTRY_ALIASES.get(fallback, fallback)
    if re.search(r"\b(remote|home-based|home based)\b", location, re.I):
        return "Remote"
    if re.search(r"\b(various|multiple|global|worldwide|portal|headquarters|field offices)\b", location, re.I):
        return "Global/International"
    for alias, country in COUNTRY_ALIASES.items():
        if re.search(rf"\b{re.escape(alias)}\b", location):
            return country
    for country in sorted(COUNTRY_NAMES, key=len, reverse=True):
        if re.search(rf"\b{re.escape(country)}\b", location, re.I):
            if country == "Bosnia":
                return "Bosnia and Herzegovina"
            return country
    if "Washington" in location or "New York" in location:
        return "United States"
    if "Geneva" in location:
        return "Switzerland"
    if "Vienna" in location:
        return "Austria"
    if "Rome" in location:
        return "Italy"
    if "The Hague" in location:
        return "Netherlands"
    if "Dakar" in location:
        return "Senegal"
    if "Nairobi" in location:
        return "Kenya"
    if "Addis Ababa" in location:
        return "Ethiopia"
    if "Abuja" in location or "Ibadan" in location:
        return "Nigeria"
    if "Cairo" in location:
        return "Egypt"
    return fallback or "Global/International"


def map_type(value, title=""):
    raw = f"{value} {title}".lower()
    if "volunteer" in raw:
        return "Volunteer opportunities"
    if "remote" in raw and "consult" not in raw and "intern" not in raw:
        return "Remote work opportunities"
    if "intern" in raw or "studentship" in raw:
        return "Internships"
    if any(term in raw for term in ["consult", "tender", "procurement", "rfq", "lta", "eoi", "roster"]):
        return "Tenders / Consultancies"
    if "training" in raw or "short course" in raw:
        return "Training / short courses"
    if "call for" in raw or "applications" in raw:
        return "Calls for applications"
    return "Jobs"


def infer_work_mode(work_arrangement, location, opportunity_type):
    text = f"{work_arrangement} {location} {opportunity_type}".lower()
    if "remote" in text or "home-based" in text or "home based" in text:
        return "Remote"
    if "hybrid" in text:
        return "Hybrid"
    if "various" in text or "multiple" in text:
        return "Various"
    return "On-site"


def category_from(row, fallback="International Development"):
    field = clean(row.get("Field", ""))
    if field:
        return field.split("/")[0].split(",")[0].strip()[:80]
    org = clean(row.get("Organization", row.get("Institution", ""))).lower()
    if any(x in org for x in ["who", "unicef", "health", "worldfish", "cip", "icarda", "ifpri", "iita", "ilri", "icipe"]):
        return "Health / Research"
    if any(x in org for x in ["unesco", "itu", "un women", "unfpa"]):
        return "Education / Communications"
    if any(x in org for x in ["world bank", "ifad", "undp", "afdb", "adb"]):
        return "Development Finance"
    if any(x in org for x in ["unep", "fao", "cgiar", "csiro", "cifor"]):
        return "Environment / Agriculture"
    return fallback


def make_record(title, organization, location, typ, link, **extra):
    title = clean(title)
    organization = clean(organization)
    location = clean(location)
    link = clean(link)
    opportunity_type = map_type(typ, title)
    country = infer_country(location, clean(extra.get("country", "")))
    work_mode = infer_work_mode(clean(extra.get("work_arrangement", "")), location, opportunity_type)
    if opportunity_type == "Remote work opportunities":
        work_mode = "Remote"
    if country == "Remote":
        work_mode = "Remote"
    category = clean(extra.get("category", "")) or category_from({"Organization": organization, "Field": extra.get("field", "")})
    deadline = clean(extra.get("deadline", ""))
    if deadline.lower() in {"nat", "nan", "open until removed", "open until filled", "rolling"}:
        deadline = ""
    source_group = clean(extra.get("source_group", "User supplied opportunity batch"))
    summary = f"Official {opportunity_type} opportunity from {organization}. Review the official source page for full details, eligibility requirements, application documents, and submission instructions."
    return {
        "title": title,
        "organization": organization,
        "opportunity_type": opportunity_type,
        "category": category,
        "country": country,
        "location": location or country,
        "work_mode": work_mode,
        "compensation": "Not specified",
        "deadline": deadline,
        "posted_date": "2026-07-14",
        "summary": summary,
        "description": summary + (" " + clean(extra.get("notes", "")) if clean(extra.get("notes", "")) else ""),
        "source": organization,
        "source_url": link,
        "application_link": link,
        "institution_url": clean(extra.get("institution_url", "")) or link,
        "discovery_listing_page": link,
        "source_group": source_group,
    }


def records_from_workbooks():
    for path in WORKBOOKS:
        xl = pd.ExcelFile(path)
        sheet = next(s for s in xl.sheet_names if "Opportunities" in s)
        df = pd.read_excel(path, sheet_name=sheet)
        for _, row in df.iterrows():
            row = {k: row[k] for k in df.columns}
            title = row.get("Opportunity Title")
            organization = row.get("Institution")
            location = row.get("Location") or row.get("Location / Arrangement") or row.get("Work Arrangement") or row.get("Coverage") or ""
            country = row.get("Country") or row.get("Country / Coverage") or row.get("Coverage") or ""
            typ = row.get("Category")
            link = row.get("Official Source")
            if not clean(title) or not clean(organization) or not clean(link):
                continue
            yield make_record(
                title,
                organization,
                location,
                typ,
                link,
                country=country,
                field=row.get("Field", ""),
                category=category_from(row),
                deadline=row.get("Deadline") or row.get("Deadline / Key Date") or "",
                work_arrangement=row.get("Work Arrangement") or row.get("Location / Arrangement") or "",
                notes=row.get("Notes", ""),
                source_group=f"{path.stem}",
            )


def records_from_csv_text(text, source_group):
    reader = csv.DictReader(io.StringIO(text.strip()))
    for row in reader:
        yield make_record(
            row.get("Opportunity Title", ""),
            row.get("Organization", ""),
            row.get("Location", ""),
            row.get("Type", ""),
            row.get("Link", ""),
            source_group=source_group,
        )


def dedupe(records):
    by_key = {}
    priority = {
        "Tenders / Consultancies": 6,
        "Remote work opportunities": 5,
        "Volunteer opportunities": 4,
        "Internships": 3,
        "Jobs": 2,
        "Calls for applications": 1,
        "Training / short courses": 1,
    }
    for record in records:
        key = hashlib.sha256("|".join([
            record["title"].lower(),
            record["organization"].lower(),
            record["source_url"].lower().rstrip("/"),
        ]).encode()).hexdigest()
        existing = by_key.get(key)
        if not existing or priority.get(record["opportunity_type"], 0) > priority.get(existing["opportunity_type"], 0):
            by_key[key] = record
    return sorted(by_key.values(), key=lambda r: (r["country"], r["organization"], r["title"]))


def main():
    records = list(records_from_workbooks())
    records.extend(records_from_csv_text(ATTACHMENT_CSV.read_text(), "Pasted UN and international jobs list"))
    for index, block in enumerate(EXTRA_CSV_BLOCKS, 1):
        records.extend(records_from_csv_text(block, f"User pasted extra list {index}"))
    records = dedupe(records)
    OUT.write_text(json.dumps(records, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps({
        "workbook_rows_and_pasted_rows_after_dedupe": len(records),
        "output": str(OUT),
    }, indent=2))
    counts = {}
    for record in records:
        counts[record["opportunity_type"]] = counts.get(record["opportunity_type"], 0) + 1
    print(json.dumps(counts, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
