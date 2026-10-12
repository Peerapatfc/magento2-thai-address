#!/usr/bin/env bash
# Creates a test customer with two saved Thai addresses on the local dev store (http://localhost:8081).
# Local only: these credentials exist nowhere but in this dev database.
set -euo pipefail
BASE=${BASE:-http://localhost:8081/rest/V1}
EMAIL=thai.tester@example.com
PASSWORD='Thai-Tester-2026!'

region() { curl -s "$BASE/thai-address/postcode/$1" | python3 -c "import sys,json;print(json.load(sys.stdin)[0]['region_id'])"; }
BKK=$(region 10500)
CNX=$(region 50200)

python3 - "$EMAIL" "$PASSWORD" "$BKK" "$CNX" <<'PY' | curl -s -X POST "$BASE/customers" -H 'Content-Type: application/json' -d @- | head -c 200
import sys, json
email, password, bkk, cnx = sys.argv[1:5]
def address(street, city, region_id, postcode, default):
    return {"firstname": "Thai", "lastname": "Tester", "street": street, "city": city, "country_id": "TH",
            "region_id": int(region_id), "postcode": postcode, "telephone": "0812345678",
            "default_shipping": default, "default_billing": default}
print(json.dumps({"customer": {"email": email, "firstname": "Thai", "lastname": "Tester", "addresses": [
    address(["99 Silom Rd", "สีลม"], "บางรัก", bkk, "10500", True),
    address(["12 Nimman Rd", "สุเทพ"], "เมืองเชียงใหม่", cnx, "50200", False),
]}, "password": password}))
PY
echo
