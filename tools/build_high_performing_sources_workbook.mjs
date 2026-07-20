import fs from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const project = "/Users/pastanlusiba/Library/CloudStorage/GoogleDrive-pastanlusiba@gmail.com/My Drive/Working folder/Apps/Aitomic Jobs";
const inputPath = path.join(project, "data", "high_performing_opportunity_sources_2026-07-20.json");
const outputPath = path.join(project, "data", "high_performing_opportunity_sources_2026-07-20.xlsx");
const outputDir = path.join(project, "outputs", "aggregator-source-database-2026-07-20");
const outputCopyPath = path.join(outputDir, "high_performing_opportunity_sources_2026-07-20.xlsx");

const payload = JSON.parse(await fs.readFile(inputPath, "utf8"));
const sourceRows = payload.source_rows;
const summary = payload.summary;

const workbook = Workbook.create();
const summarySheet = workbook.worksheets.add("Summary");
const dbSheet = workbook.worksheets.add("Source Database");
const performanceSheet = workbook.worksheets.add("Performance");
const notesSheet = workbook.worksheets.add("Run Notes");

const colors = {
  navy: "#12245A",
  blue: "#1D4E89",
  orange: "#F05A3D",
  paleBlue: "#EAF1FB",
  paleOrange: "#FDECE7",
  grey: "#F5F7FA",
  border: "#D6DEE8",
  text: "#1F2937",
};

function styleTitle(sheet, title, subtitle, range = "A1:H1") {
  sheet.showGridLines = false;
  const titleRange = sheet.getRange(range);
  titleRange.merge();
  titleRange.values = [[title]];
  titleRange.format.fill.color = colors.navy;
  titleRange.format.font.color = "#FFFFFF";
  titleRange.format.font.bold = true;
  titleRange.format.font.size = 16;
  titleRange.format.rowHeight = 30;
  const subtitleRange = sheet.getRange("A2:H2");
  subtitleRange.merge();
  subtitleRange.values = [[subtitle]];
  subtitleRange.format.fill.color = colors.paleBlue;
  subtitleRange.format.font.color = colors.text;
  subtitleRange.format.font.italic = true;
  subtitleRange.format.rowHeight = 24;
}

function writeTable(sheet, startRow, startCol, headers, rows) {
  const matrix = [headers, ...rows.map((row) => headers.map((h) => row[h] ?? ""))];
  const range = sheet.getRangeByIndexes(startRow, startCol, matrix.length, headers.length);
  range.values = matrix;
  const header = sheet.getRangeByIndexes(startRow, startCol, 1, headers.length);
  header.format.fill.color = colors.blue;
  header.format.font.color = "#FFFFFF";
  header.format.font.bold = true;
  header.format.wrapText = true;
  const body = sheet.getRangeByIndexes(startRow + 1, startCol, Math.max(1, matrix.length - 1), headers.length);
  body.format.borders = { preset: "inside", style: "thin", color: colors.border };
  range.format.borders = { preset: "outside", style: "thin", color: colors.border };
  range.format.font.color = colors.text;
  range.format.wrapText = true;
  range.format.autofitColumns();
  range.format.autofitRows();
  sheet.freezePanes.freezeRows(startRow + 1);
  return range;
}

styleTitle(
  summarySheet,
  "Aitomic Jobs - High-Performing Opportunity Source Database",
  "Separate source database built from the aggregator-only opportunity workbook supplied on 2026-07-20."
);

const sourceCount = sourceRows.length;
const importRows = payload.import_rows;
const duplicates = payload.duplicates_excluded;
const highPerforming = sourceRows.filter((r) => r.Status === "High-performing").length;
const exactLinks = sourceRows.filter((r) => r["Exact listing URL"] === "Yes").length;

const metricRows = [
  { Metric: "Importable opportunities from workbook", Value: importRows },
  { Metric: "Duplicates excluded", Value: duplicates },
  { Metric: "Distinct source/index URLs", Value: sourceCount },
  { Metric: "High-performing sources", Value: highPerforming },
  { Metric: "Sources with exact listing URLs", Value: exactLinks },
  { Metric: "Generated date", Value: payload.generated },
];
writeTable(summarySheet, 4, 0, ["Metric", "Value"], metricRows);

const typeRows = Object.entries(summary.by_opportunity_type || {}).map(([Name, Count]) => ({ Type: "Opportunity type", Name, Count }));
const sourceRowsSummary = Object.entries(summary.by_source || {})
  .sort((a, b) => b[1] - a[1])
  .slice(0, 20)
  .map(([Name, Count]) => ({ Type: "Top source", Name, Count }));
const countryRows = Object.entries(summary.by_country || {})
  .sort((a, b) => b[1] - a[1])
  .slice(0, 20)
  .map(([Name, Count]) => ({ Type: "Top country/coverage", Name, Count }));
writeTable(summarySheet, 12, 0, ["Type", "Name", "Count"], [...typeRows, ...sourceRowsSummary, ...countryRows]);
summarySheet.getRange("A:A").format.columnWidth = 30;
summarySheet.getRange("B:B").format.columnWidth = 55;
summarySheet.getRange("C:C").format.columnWidth = 14;

styleTitle(
  dbSheet,
  "Ranked Source Database",
  "Use this sheet to prioritize opportunity index/profile sites that produce useful opportunities."
);
const dbHeaders = [
  "Source name",
  "Source domain",
  "Source URL",
  "Source type",
  "Status",
  "Imported rows in workbook",
  "Categories covered",
  "Countries / coverage",
  "Regions covered",
  "Rows with exact deadline",
  "Exact listing URL",
  "Quality score",
  "Recommended use",
  "Notes",
  "Last reviewed",
];
writeTable(dbSheet, 4, 0, dbHeaders, sourceRows);
dbSheet.getRange("A:A").format.columnWidth = 24;
dbSheet.getRange("B:B").format.columnWidth = 24;
dbSheet.getRange("C:C").format.columnWidth = 42;
dbSheet.getRange("G:I").format.columnWidth = 34;
dbSheet.getRange("N:N").format.columnWidth = 34;

styleTitle(
  performanceSheet,
  "Source Performance Views",
  "Counts by source and category help decide where future searches should concentrate."
);
const perfRows = [];
for (const row of sourceRows) {
  const categories = String(row["Categories covered"] || "").split(";").map((x) => x.trim()).filter(Boolean);
  for (const category of categories.length ? categories : ["Unspecified"]) {
    perfRows.push({
      "Source name": row["Source name"],
      "Source domain": row["Source domain"],
      Category: category,
      "Imported rows in workbook": row["Imported rows in workbook"],
      "Quality score": row["Quality score"],
      "Exact listing URL": row["Exact listing URL"],
      "Recommended use": row["Recommended use"],
    });
  }
}
writeTable(performanceSheet, 4, 0, [
  "Source name",
  "Source domain",
  "Category",
  "Imported rows in workbook",
  "Quality score",
  "Exact listing URL",
  "Recommended use",
], perfRows);
performanceSheet.getRange("A:B").format.columnWidth = 26;
performanceSheet.getRange("C:C").format.columnWidth = 24;
performanceSheet.getRange("G:G").format.columnWidth = 34;

styleTitle(
  notesSheet,
  "Run Notes",
  "Audit notes for this source database and import pass."
);
const notesRows = [
  { Item: "Source workbook", Detail: payload.source_workbook },
  { Item: "Search window", Detail: "17-20 July 2026, aggregator/index/profile sites only." },
  { Item: "Import file", Detail: "aggregator_only_opportunities_import_2026-07-20.json" },
  { Item: "Important limitation", Detail: "Many rows supplied aggregator-level URLs rather than exact vacancy URLs; the website listings disclose this and direct users to verify the source listing." },
  { Item: "Strong sources", Detail: "UNjobs, ReliefWeb, UNICEF Jobs, UNESCO Careers, jobs.ac.uk, Fuzu, ITU, WHO, Idealist and Nature produced multiple useful rows in this workbook." },
  { Item: "Database purpose", Detail: "This workbook is separate from the broader institution database and should be used to prioritize high-yield sites for future opportunity hunting." },
];
writeTable(notesSheet, 4, 0, ["Item", "Detail"], notesRows);
notesSheet.getRange("A:A").format.columnWidth = 26;
notesSheet.getRange("B:B").format.columnWidth = 90;

for (const sheet of [summarySheet, dbSheet, performanceSheet, notesSheet]) {
  const used = sheet.getUsedRange();
  used.format.font.name = "Aptos";
  used.format.font.size = 10;
}

const inspect = await workbook.inspect({
  kind: "sheet,table",
  maxChars: 3000,
  tableMaxRows: 6,
  tableMaxCols: 8,
});
console.log(inspect.ndjson);

await fs.mkdir(outputDir, { recursive: true });
const preview = await workbook.render({ sheetName: "Summary", range: "A1:H28", scale: 1, format: "png" });
await fs.writeFile(path.join(outputDir, "summary-preview.png"), new Uint8Array(await preview.arrayBuffer()));

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(outputPath);
await xlsx.save(outputCopyPath);
console.log(JSON.stringify({ outputPath, outputCopyPath }, null, 2));
