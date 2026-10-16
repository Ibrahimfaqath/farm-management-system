---
paths:
  - lang/id.json
---

# Lang

## A deployment that skips lang/ ships English UI strings
The FTP mirror list has to include lang/ alongside resources/, app/ and public/build/. Uploading views without translations leaves every new label rendering its English source key on a fully Indonesian page, and nothing in the build or the test suite catches it — the views are correct, the dictionary is simply absent. Check the rendered page for stray English after deploying a view that adds strings.
