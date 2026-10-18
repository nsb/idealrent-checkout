# Danish address autocomplete migration

## Live version 1.2 reconciliation

The owner supplied the live main PHP file, checkout widget, checkout JavaScript,
CSS, and DAWA widget. They confirm substantive version 1.2 changes absent from the
original repository: closed dates, configurable flexible-time text, pricing fields,
Stripe customer metadata, HTTPS redirects, mobile styling, and a separate address
step. The updated main PHP and checkout JavaScript are based on these live files.
The live CSS and checkout widget are preserved byte-for-byte in this checkout.
The supplied DAWA widget matches the original repository version.

Release 1.2.1 preserves all of those live changes. Address-step continuation does
not require entrance selection: entrance belongs to the following step in 1.2.
The earlier 1.1.1 ZIP must not be installed. The installed folder name is
`idealrent_checkout` (underscore), not `idealrent-checkout`.

Only three changed production files are packaged in the new File Manager update.
Other live plugin files have not been supplied, so this is deliberately not a
complete WordPress-installable ZIP.

## Root cause and replacement

The plugin's sole retired endpoint was `https://dawa.aws.dk/autocomplete`, which
returned HTTP 410 during investigation on 18 October 2026. It is replaced by the
official KDS Adressevælger API, not merely a new hostname for the old contract.

References:

- [DAWA retirement](https://dataforsyningen.dk/data/4924)
- [Search contract](https://confluence.kds.dk/pages/viewpage.action?pageId=244318431)
- [ID lookup contract](https://confluence.kds.dk/pages/viewpage.action?pageId=246743156)
- [Public token and future authentication](https://confluence.kds.dk/display/ADV/Brugerstyring)
- [FAQ and rate limits](https://confluence.kds.dk/display/ADV/FAQ)

The API currently requires the public placeholder token `adressevaelger123`.
It is not a private credential. KDS documents no rate limit and expects user
management in late 2026 or early 2027. Monitor KDS notices before that transition;
do not replace this browser token with a private credential. If private credentials
become necessary, introduce a server-side integration under the new documented policy.

## Contract and compatibility

Previously, GET `/autocomplete` accepted `q`, `type`, `caretpos`, `fuzzy`, and
optional building/street refinement parameters. Its array contained `type`,
`tekst`, `forslagstekst`, `caretpos`, and `data`.

The replacement uses HTTPS JSON requests:

| Operation | Endpoint | Response used |
| --- | --- | --- |
| Search | `/adresser/soeg?tekst=...&maksimum=20&token=...` | `status`, `fund[].type/id/titel` |
| Street refinement | `/adresser/soeg?vejnavn=...&postnummer=...&husnummer=...&maksimum=20&token=...` (optional house/postcode) | Same search response |
| Building expansion | `/husnumre/{id}/adresser?token=...` | `status`, `adresser[]` |
| Resolve selection | `/adresser/{id}?token=...` | `status`, `adresse` |

Native phonetic matching replaces the old fuzzy/caret parameters. Search types
`vejnavn`, `navngivenvejpostnummer`, and `vejnavnhusnummer` refine the input;
`husnummer` expands to addresses; only a resolved current (`status: "3"`) address
completes selection. One valid building child selects automatically; multiple
children remain a menu for floor/door selection.

The adapter preserves `.dawaautocomplete()` and its booking selection callback:
`type: "adresse"`, `tekst`, `forslagstekst`, `caretpos`, and
`data.{id,postnr,vejnavn,husnr,etage,dør}`. The postcode is read from
`adresse.husnummer.postnummer.postnr`, never parsed from the label.

Only address text is submitted/stored downstream (including the booking email).
IDs serve autocomplete only. Coordinates and municipality data are unused; the
new API's EPSG:25832 coordinates are not mapped to old WGS84 fields. Legacy DAWA
options for JSONP, access-address-only selection, arbitrary DAWA parameters, and
special recipient postcodes are not retained; this repository has no callers using
those options. The adapter targets the booking callback, not every DAWA consumer.

The existing `postnr < 3000` service-area rule and version 1.2 step sequence
remain. Editing clears the hidden address and disables address-step continuation.
Selection is a client-side booking-step requirement, not new server-side address
validation. Pricing, payments, form field names, and database schema are unchanged.

## Files and reliability

- `assets/dawa-autocomplete.js`: API adapter, cancellation/generation guards,
  200 ms debounce, two-character minimum, 73-character search limit, ten-second
  timeout, safe suggestion rendering, Danish live status messages, teardown.
- `assets/idealrent_checkout.js`: clear invalidated selections, render address
  summaries as text, and guard address-step progression.
- `idealrent_checkout.php`: version both changed scripts as `1.2.1` for cache busting.
- `tests/address.test.cjs`, `tests/browser.js`, `tests/README.md`: reproducible tests.
- `ADDRESS-MIGRATION.md`: contract, compatibility, verification, deployment.

No runtime dependencies were added. Failed and superseded searches settle jQuery
UI response callbacks. Late search, building, and address-detail results cannot
change newer input. Blank input makes no requests; failures leave the form usable
and offer a retry message without accepting an unresolved address.

## Verification

On 18 October 2026:

- Nine Node tests passed; JavaScript syntax checks passed.
- The Playwright fixture passed keyboard/mouse selection, postcode mapping,
  address progression without entrance, edit invalidation, unresolved continuation guard, blank
  requests, building expansion, apartment selection, service-area rejection,
  Danish characters, safe rendering, API failures, empty results, teardown,
  out-of-order suggestions, and clearing during an address-detail request.
- No JavaScript page exceptions occurred. The mocked 503 network error is expected.
- Live read-only requests confirmed Sluseholmen search, complete-address matching,
  invalid-input empty results, building expansion, and postcode-bearing detail
  lookup. Search returned HTTP 200 and `Access-Control-Allow-Origin: *` with
  `Origin: https://idealrent.dk`. The complete-address search was repeated after
  implementation.
- PHP is unavailable locally, so PHP lint was not run. There is no local WordPress
  installation or pre-existing test suite. No bookings, payments, or deployment
  were performed.

## Deployment and rollback (Simply.com File Manager)

1. Keep the downloaded live files as backups. Extract
   `/tmp/idealrent-checkout-1.2.1-file-update.zip` on your computer.
   Do not upload this partial ZIP through WordPress's plugin installer.
2. Prefer staging first. In Simply.com File Manager, open
   `/public_html/wp-content/plugins/idealrent_checkout/assets/` and upload/overwrite
   `dawa-autocomplete.js` and `idealrent_checkout.js` from the extracted `assets`
   folder. Then go up one level and upload/overwrite `idealrent_checkout.php`.
   Upload the files themselves, not a new plugin folder. Leave all other files
   unchanged. The supplied live files already contain the CSS and widget changes.
3. Run `php -l idealrent_checkout.php` using the hosting environment's PHP version.
4. Clear WordPress optimization/page caches and CDN caches. Verify both modified
   script URLs carry `ver=1.2.1` and serve the updated contents.
5. Ensure any Content Security Policy permits `connect-src https://adressevaelger.dk`.
6. Verify the installed WordPress/jQuery combination in both booking layouts and
   on mobile. Try Sluseholmen, an apartment, a postcode outside the service area,
   editing an existing selection, rapid typing, and a simulated API outage.
   Confirm address continuation works before choosing entrance on the next step. Stop
   before final booking/payment submission.
7. After staging passes, deploy the same three files and repeat the non-submitting
   smoke test. Watch browser/network errors and monitor KDS authentication notices.

Rollback by restoring the three backed-up files together and clearing caches.
The previous code still points to the retired service, so rollback restores the
old behavior but does not restore address availability.
