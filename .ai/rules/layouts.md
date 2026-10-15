---
paths:
  - resources/views/layouts/app.blade.php
---

# Layouts

## The layout is the one file a test revert can silently take with it
Reverting this file to prove a test bites also reverts every other change made to it, and the loss is invisible until the file is read again on the server. When testing anything here, edit only the single line under test and put it back by hand — never `git checkout` the whole file. Assert on the whole file's key affordances (drawer max-width, nav scroll mask) so a partial revert fails a test.
