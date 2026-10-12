# Changelog

## 0.3.1 - 2026-10-12

- The drop-downs show one language: Thai names in a Thai store view, English names otherwise, each sorted in its
  own alphabet (they showed both before).
- District and sub-district validation also accepts the English names.
- 71 unit tests.

## 0.3.0 - 2026-10-12

- Luma checkout for Thailand: City becomes a district drop-down and a sub-district drop-down replaces the second
  street line, in the order country, province, district, sub-district, postal code. The postal code is filled from
  the sub-district. Other countries are unchanged.
- REST: `GET /V1/thai-address/provinces`, `/provinces/:provinceCode/districts` and
  `/districts/:districtCode/subdistricts`, sorted in Thai alphabetical order.
- Setting "Drop-downs and postal code autocomplete" (was "Suggest the address from the postal code").
- 67 unit tests.

## 0.2.0 - 2026-10-12

- District and sub-district validation: the City field is the district and the second street line the sub-district.
  Names are matched ignoring เขต/อำเภอ/แขวง/ตำบล, tone marks and spaces.
- New setting *Strictness* (postal code / + province / + district and sub-district). It replaces *Province must match
  the postal code*; the default behaves as before.
- Luma checkout autocomplete from the postal code, with a setting to turn it off.
- Official district and sub-district codes (`district_code`, `subdistrict_code`) and English names (`district_en`,
  `subdistrict_en`) in the REST and GraphQL lookup. The data file now records its source and upstream commit.
- 62 unit tests.

## 0.1.0

- 77 Thai provinces added as Magento regions for Thailand (English and Thai names), with the official province code
  stored as the region code. Safe to re-run.
- Postal code lookup: `GET /V1/thai-address/postcode/:postcode` and the GraphQL query `thaiAddress(postcode)`.
- Checkout validation for Thai shipping and billing addresses: the postal code must exist and, optionally, the
  province must match it. Works for guests and logged-in customers.
- Admin settings under Stores > Configuration > General > Thai Address.
- Thai translations (`th_TH`) of the messages.
- 41 unit tests. Verified on a real Magento Open Source 2.4.8-p4 install with PHP 8.3.
