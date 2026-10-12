// Builds data/thai-addresses.json from thailand-geography-data/thailand-geography-json (MIT).
// Usage: node scripts/build-addresses.mjs path/to/geography.json [upstream-commit-sha]
//
// Output: provinces and districts keyed by their official code; one entry per sub-district:
//   [postalCode, subdistrictCode, nameTh, nameEn]
// The district and province of an entry are the first 4 and the first 2 digits of the sub-district code.
import { readFileSync, writeFileSync } from "node:fs"

const src = process.argv[2]
if (!src) throw new Error("usage: node scripts/build-addresses.mjs <geography.json> [upstream-commit-sha]")

const rows = JSON.parse(readFileSync(src, "utf8"))
const provinces = {}
const districts = {}
const entries = []
for (const r of rows) {
  provinces[r.provinceCode] = { th: r.provinceNameTh, en: r.provinceNameEn }
  districts[r.districtCode] = { th: r.districtNameTh, en: r.districtNameEn }
  if (String(r.subdistrictCode).slice(0, 4) !== String(r.districtCode) || String(r.districtCode).slice(0, 2) !== String(r.provinceCode)) {
    throw new Error(`code prefixes disagree for ${r.subdistrictCode}`)
  }
  entries.push([String(r.postalCode), r.subdistrictCode, r.subdistrictNameTh, r.subdistrictNameEn])
}
entries.sort((a, b) => a[0].localeCompare(b[0]) || a[1] - b[1])

const source = {
  repository: "https://github.com/thailand-geography-data/thailand-geography-json",
  commit: process.argv[3] ?? null,
  file: "src/geography.json",
  codes: "DOPA / TIS 1099-2548: 2-digit province, 4-digit district, 6-digit sub-district",
}

const out = new URL("../data/thai-addresses.json", import.meta.url)
writeFileSync(out, JSON.stringify({ source, provinces, districts, entries }))
console.log(`provinces=${Object.keys(provinces).length} districts=${Object.keys(districts).length} entries=${entries.length}`)
