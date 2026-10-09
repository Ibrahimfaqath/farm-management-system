#!/usr/bin/env python3
"""Run the test suite and print a compact summary of failures with root causes."""
import json
import re
import subprocess
import sys

args = sys.argv[1:] or []
proc = subprocess.run(["php", "artisan", "test", "--compact", *args], capture_output=True, text=True)
raw = proc.stdout.strip() or proc.stderr.strip()

try:
    data = json.loads(raw)
except json.JSONDecodeError:
    print(raw[-5000:])
    sys.exit(proc.returncode)

print(f"{data.get('result')} | tests={data.get('tests')} passed={data.get('passed')} "
      f"failed={data.get('failed')} errors={data.get('errors')}")

for group in ("failures", "error_details"):
    for entry in data.get(group) or []:
        name = entry.get("test", "?").split("::")[-1]
        msg = (entry.get("message") or "").replace("\n", " ")
        print(f"\n--- {name}\n    {msg[:300]}")

sys.exit(0 if data.get("result") == "passed" else 1)