# magento2-thai-address

Thai addresses for Magento 2: the 77 provinces as regions, postal code lookup over REST and GraphQL, and server-side
address validation at checkout.

A stock Magento has **no regions for Thailand at all** (0 of 1,156 in a fresh 2.4.8 install), so Thai customers type
the province into a free text box and nothing checks it. This module fixes that.

## What you get

- **77 provinces as Magento regions** (English and Thai names). The official province code, for example 10 for
  Bangkok, is stored as the region code. Added by a data patch, safe to re-run.
- **Postal code lookup**: give a 5-digit code, get every sub-district that uses it, with province, district and the
  Magento `region_id`.
- **Checkout validation** for Thai shipping and billing addresses, for guests and logged-in customers: the postal code
  must exist, and optionally the province, district and sub-district must agree with it (three strictness levels).
- **Luma checkout drop-downs** for Thailand, in the order a Thai address is written: country, province, district,
  sub-district, postal code. Choosing a province lists its districts, choosing a district lists its sub-districts (in
  Thai alphabetical order) and choosing a sub-district fills the postal code. Typing the postal code first still
  works: it selects the province and fills the district and sub-district when only one fits. Other countries keep the
  normal text fields. **The sub-district is stored in the second street line, so the store needs at least 2 street
  lines** (Stores > Configuration > Customers > Customer Configuration > Name and Address Options > Number of Lines in a
  Street Address, default 2). With 1 line only the district is a drop-down and the sub-district is not asked for or
  checked. The names are in **one language at a time**: Thai in a store view with a Thai locale (`th_TH`),
  English in any other, each sorted in its own alphabet. The stored value is the name shown, and the checkout
  validation accepts both the Thai and the English name.
- **Saved addresses are checked too.** The storefront address book (My Account) refuses to save a Thai address the
  checkout would refuse, with the same message. In the checkout a saved address that fails the check shows the reason
  and a link to edit it in My Account, and has no "Ship Here" button.
- **Admin settings** for the strictness level and the autocomplete (Stores > Configuration > General > Thai Address).
- Thai translations of the messages.

## Install

The module is not on Packagist yet, so add it from GitHub:

```bash
composer config repositories.thai-address vcs https://github.com/Peerapatfc/magento2-thai-address
composer require peerapatfc/magento2-thai-address:dev-main
bin/magento module:enable Peerapatfc_ThaiAddress
bin/magento setup:upgrade
bin/magento setup:di:compile   # production mode only
```

Requires PHP 8.2 to 8.5 and Magento Open Source 2.4.7 or newer (or a distribution based on it, such as Mage-OS).

## Use

### REST

```bash
curl https://your-store.example/rest/V1/thai-address/postcode/10110
```

```json
[
  {
    "postcode": "10110",
    "province_code": 10,
    "province_name_th": "กรุงเทพมหานคร",
    "province_name_en": "Bangkok",
    "district_code": 1033,
    "district": "คลองเตย",
    "district_en": "Khlong Toei",
    "subdistrict_code": 103301,
    "subdistrict": "คลองเตย",
    "subdistrict_en": "Khlong Toei",
    "region_id": 1157
  }
]
```

A code that does not exist returns `[]`. A malformed one returns HTTP 400.

### Lists for drop-downs

```bash
curl https://your-store.example/rest/V1/thai-address/provinces                      # 77, with region_id
curl https://your-store.example/rest/V1/thai-address/provinces/10/districts         # districts of Bangkok
curl https://your-store.example/rest/V1/thai-address/districts/1004/subdistricts    # with postcode
```

Each item has `code`, `name_th` and `name_en`; sub-districts also have `postcode`, provinces `region_id`. They are
sorted in Thai alphabetical order.

### GraphQL

```graphql
{
  thaiAddress(postcode: "50200") {
    province_name_en
    district_code
    district
    district_en
    subdistrict_code
    subdistrict
    subdistrict_en
    region_id
  }
}
```

### Checkout validation

| Address | Result |
|---|---|
| 10110 with Bangkok | accepted |
| 10110 with Phuket | rejected: *Postal code 10110 belongs to กรุงเทพมหานคร, not "Phuket".* |
| 00000 (does not exist) | rejected |
| 1011 (not 5 digits) | rejected |
| Province typed as text: `กทม.`, `จ.เชียงใหม่`, `Bangkok` | understood |
| Any address outside Thailand | not touched |

Errors use Magento's normal API format: a `message` template plus a `parameters` list. The checkout fills the
placeholders itself.

Some postal codes are shared by more than one province (83000 lists both Phuket and Phang Nga), so the check
accepts any province that uses the code.

### District and sub-district

Magento has no district or sub-district field, so the module uses the ones Thai stores already use: the **City**
field holds the district (อำเภอ / เขต) and the **second street line** holds the sub-district (ตำบล / แขวง). Nothing
is added to the database.

At the strictest level both are compared with the postal code, ignoring the words เขต, อำเภอ, แขวง, ตำบล (and
อ., ต.), tone marks and spaces, so `เขตบางรัก`, `บางรัก` and `บางรั่ก` are the same. An empty value is not checked.

| Postal code | City | Street line 2 | Result |
|---|---|---|---|
| 10500 | เขตบางรัก | แขวงสีลม | accepted |
| 10500 | ห้วยขวาง | | rejected: district not in this postal code |
| 10500 | บางรัก | คลองเตย | rejected: sub-district not in this postal code |

## Settings

Stores > Configuration > General > Thai Address

| Setting | Default | |
|---|---|---|
| Validate Thai addresses | Yes | Rejects a Thai address whose postal code does not exist. |
| Strictness | Postal code and province | *Postal code must exist*, *Postal code and province must match*, or *Postal code, province, district and sub-district must match*. |
| Drop-downs and postal code autocomplete | Yes | Luma checkout: district and sub-district drop-downs and the postal code autocomplete. |

The old setting "Province must match the postal code" (0.1.0) is replaced by *Strictness*; the default behaves the
same as before.

## What it does not do yet

- **The drop-downs are Luma only.** Hyvä is not done. The sub-district drop-down is shown in place of the second
  street line (which is hidden for Thailand) and its choice is stored there, as text, so the address book and orders
  show the Thai name, not a code.
- The province list is Magento's own region field, ordered by Magento, not in Thai alphabetical order.
- The My Account address form has no drop-downs (only a check when you save).
- The address book is checked only on the storefront; the admin and the API can still save an address the checkout
  would refuse.
- District and sub-district are not separate address fields (see above), so they are not in GraphQL address types or
  the admin address form.
- The Luma drop-downs were checked in a browser against a real checkout (guest checkout up to the payment step), but
  have no automated JavaScript tests.
- Tested on Magento Open Source **2.4.8-p4 with PHP 8.3**. 2.4.9 and Adobe Commerce are not tested.

## Data

`data/thai-addresses.json` holds 77 provinces, 928 districts and 7,436 sub-districts with their official codes
(2, 4 and 6 digits: the Department of Provincial Administration codes of TIS 1099-2548) and Thai and English names.
It records where it came from under `source`.

It is built from [thailand-geography-json](https://github.com/thailand-geography-data/thailand-geography-json) (MIT,
see `THIRD_PARTY_LICENSES.md`), a community project. **It is not an official government file**, and the project does
not say which official list its postal codes were taken from. What was checked, at upstream commit `b8b3fb9`
(2026-06-23): 7,436 unique sub-district codes, every code starts with its district and province code, no empty
names, and the rows are identical to the ones this module shipped before.

Postal codes change from time to time, so refresh the file before a release:

```bash
curl -O https://raw.githubusercontent.com/thailand-geography-data/thailand-geography-json/main/src/geography.json
node scripts/build-addresses.mjs geography.json <upstream-commit-sha>
```

## Develop

`dev/` holds a Docker setup (PHP 8.3, MariaDB, OpenSearch) that installs a real Magento 2.4.8 from the public
[Mage-OS mirror](https://mirror.mage-os.org/), so no Adobe account is needed.

```bash
bash dev/install.sh
```

A test customer with two saved Thai addresses (for trying the logged-in checkout and the address book) comes from
`bash dev/seed-customer.sh`. It is for the local store only; the login is in the script.

Then:

```bash
# unit tests (87)
docker compose -f dev/docker-compose.yml exec -T php bash -lc \
  'cd /var/www/html/dev/tests/unit && ../../../vendor/bin/phpunit -c phpunit.xml.dist /module/Test/Unit'

# coding standard
docker compose -f dev/docker-compose.yml exec -T php bash -lc \
  'cd /var/www/html && vendor/bin/phpcs --standard=/module/phpcs.xml.dist /module'
```

## Verified

On a real Magento Open Source 2.4.8-p4 install (not just unit tests):

- the data patch creates 77 regions, and Magento's own `/V1/directory/countries/TH` API lists them
- `setup:di:compile` succeeds
- REST and GraphQL return the expected data and errors
- guest checkout behaves as expected in 8 of 8 scenarios: valid Thai and non-Thai addresses go through, a wrong
  province, an unknown postal code and a malformed one are rejected with HTTP 400

## License

MIT. Address data: MIT, see [THIRD_PARTY_LICENSES.md](THIRD_PARTY_LICENSES.md).
