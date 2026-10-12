# Changelog

## 0.1.0

- 77 Thai provinces added as Magento regions for Thailand (English and Thai names), with the official province code
  stored as the region code. Safe to re-run.
- Postal code lookup: `GET /V1/thai-address/postcode/:postcode` and the GraphQL query `thaiAddress(postcode)`.
- Checkout validation for Thai shipping and billing addresses: the postal code must exist and, optionally, the
  province must match it. Works for guests and logged-in customers.
- Admin settings under Stores > Configuration > General > Thai Address.
- Thai translations (`th_TH`) of the messages.
- 41 unit tests. Verified on a real Magento Open Source 2.4.8-p4 install with PHP 8.3.
