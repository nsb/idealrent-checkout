# Address autocomplete verification

Run from the repository root:

```sh
node --test tests/address.test.cjs
node --check assets/dawa-autocomplete.js
node --check assets/idealrent_checkout.js
```

For browser verification, install/use `playwright-cli`, then prepare the temporary
jQuery dependency (WordPress provides jQuery in production):

```sh
curl -fsS https://code.jquery.com/jquery-3.7.1.min.js -o /tmp/idealrent-test-jquery.js
playwright-cli -s=idealrent open about:blank
playwright-cli -s=idealrent --raw run-code --filename=tests/browser.js
playwright-cli -s=idealrent close
```

The browser fixture loads the actual plugin JavaScript and CSS with jQuery 3.7.1
and the bundled jQuery UI 1.12.1. It mocks API requests, blocks all other network
traffic, stubs Stripe, and rejects form submissions. It tests behavior, not a full
WordPress installation or payment flow. The intentional HTTP 503 case produces an
expected browser network-console error; JavaScript page errors must remain empty.

The Node tests exercise the widget through a small jQuery transport harness,
including callbacks deliberately delivered after cancellation. Browser tests also
exercise late callbacks against the real jQuery UI and DOM input handlers.
