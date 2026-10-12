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
  must exist, and the province must agree with it.
- **Admin settings** to switch the validation, or only the province check, on and off (Stores > Configuration >
  General > Thai Address).
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
    "district": "คลองเตย",
    "subdistrict": "คลองเตย",
    "region_id": 1157
  }
]
```

A code that does not exist returns `[]`. A malformed one returns HTTP 400.

### GraphQL

```graphql
{
  thaiAddress(postcode: "50200") {
    province_name_en
    district
    subdistrict
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

District and sub-district are **not** enforced, because customers spell them in too many ways. Use the lookup to
suggest them instead.

## Settings

Stores > Configuration > General > Thai Address

| Setting | Default | |
|---|---|---|
| Validate Thai addresses | Yes | Rejects a Thai address whose postal code does not exist. |
| Province must match the postal code | Yes | Also rejects a province that does not use that postal code. |

## What it does not do yet

- **No autocomplete in the checkout form.** The lookup is there; a Luma and a Hyvä front end that fills province,
  district and sub-district as you type is the next step.
- The customer address book (My Account) is not validated, only checkout.
- Sub-district is not stored on the address.
- District and sub-district names are in Thai only. Province names are in Thai and English.
- Tested on Magento Open Source **2.4.8-p4 with PHP 8.3**. 2.4.9 and Adobe Commerce are not tested.

## Data

`data/thai-addresses.json` (7,436 sub-districts, 77 provinces) is built from
[thailand-geography-json](https://github.com/thailand-geography-data/thailand-geography-json) (MIT, see
`THIRD_PARTY_LICENSES.md`). Postal codes change from time to time, so refresh it before a release:

```bash
node scripts/build-addresses.mjs path/to/geography.json
```

## Develop

`dev/` holds a Docker setup (PHP 8.3, MariaDB, OpenSearch) that installs a real Magento 2.4.8 from the public
[Mage-OS mirror](https://mirror.mage-os.org/), so no Adobe account is needed.

```bash
bash dev/install.sh
```

Then:

```bash
# unit tests (41)
docker compose -f dev/docker-compose.yml exec -T php bash -lc \
  'cd /var/www/html && vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist /module/Test/Unit'

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
