"""Emit machine-readable and reviewer-friendly Chromium acceptance evidence.

Run exclusively against the disposable E2E Docker Compose CI stack. The
runner uses Python's standard library; Playwright remains installed by CI.
"""
import json
import os
import re
import sys
import time
import unittest
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlsplit
from xml.etree import ElementTree as ET


def main():
    target = os.environ.get("E2E_BASE_URL", "")
    parts = urlsplit(target)
    if (os.environ.get("E2E_TEST_STACK") != "1"
            or parts.scheme != "http"
            or parts.hostname not in {"127.0.0.1", "localhost"}
            or not parts.port):
        raise SystemExit("Refusing browser acceptance outside explicit loopback E2E_TEST_STACK=1")

    output = Path(os.environ.get("E2E_REPORT_DIR", "artifacts/acceptance"))
    output.mkdir(parents=True, exist_ok=True)
    suite = unittest.defaultTestLoader.discover("tests/browser", pattern="test_*.py")

    class AcceptanceResult(unittest.TextTestResult):
        def __init__(self, *args, **kwargs):
            super().__init__(*args, **kwargs)
            self.cases = []
            self.started = {}

        def startTest(self, test):
            self.started[test.id()] = time.monotonic()
            super().startTest(test)

        def _record(self, test, status, detail=""):
            name = test.id()
            elapsed = round(time.monotonic() - self.started.get(name, time.monotonic()), 3)
            case = {"name": name, "status": status, "duration_seconds": elapsed}
            if detail:
                case["error"] = detail[-4000:]
            if status in {"FAIL", "ERROR"}:
                page = getattr(test, "page", None)
                if page is not None:
                    try:
                        shot = output / ("failure-" + str(len(self.cases) + 1) + ".png")
                        page.screenshot(path=str(shot), full_page=True, timeout=3000)
                        case["screenshot"] = shot.name
                    except Exception as exc:
                        case["screenshot_error"] = str(exc)[:300]
            self.cases.append(case)

        def addSuccess(self, test):
            super().addSuccess(test)
            self._record(test, "PASS")

        def addFailure(self, test, err):
            detail = self._exc_info_to_string(err, test)
            super().addFailure(test, err)
            self._record(test, "FAIL", detail)

        def addError(self, test, err):
            detail = self._exc_info_to_string(err, test)
            super().addError(test, err)
            self._record(test, "ERROR", detail)

        def addSkip(self, test, reason):
            super().addSkip(test, reason)
            self._record(test, "SKIP", reason)

    result = unittest.TextTestRunner(verbosity=2, resultclass=AcceptanceResult).run(suite)
    cases = result.cases
    counts = {s: sum(c["status"] == s for c in cases) for s in ("PASS", "FAIL", "ERROR", "SKIP")}
    passed = result.wasSuccessful() and counts["PASS"] > 0 and counts["SKIP"] == 0
    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "commit": os.environ.get("GITHUB_SHA", "local"),
        "environment": "disposable-loopback-compose",
        "browser": "chromium",
        "result": "PASS" if passed else "FAIL",
        "counts": counts,
        "cases": cases,
    }
    (output / "acceptance.json").write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")

    root = ET.Element("testsuite", name="chromium-acceptance",
                      tests=str(len(cases)), failures=str(counts["FAIL"]),
                      errors=str(counts["ERROR"]), skipped=str(counts["SKIP"]))
    for case in cases:
        node = ET.SubElement(root, "testcase", name=case["name"],
                             time=str(case["duration_seconds"]))
        if case["status"] == "FAIL":
            ET.SubElement(node, "failure", message="Browser assertion failed").text = case.get("error", "")
        elif case["status"] == "ERROR":
            ET.SubElement(node, "error", message="Browser test error").text = case.get("error", "")
        elif case["status"] == "SKIP":
            ET.SubElement(node, "skipped", message=case.get("error", ""))
    ET.ElementTree(root).write(output / "acceptance.xml", encoding="utf-8", xml_declaration=True)

    rows = [
        "## Chromium browser acceptance — " + report["result"],
        "",
        "Commit: `" + report["commit"] + "` · Disposable Compose only",
        "",
        "| PASS | FAIL | ERROR | SKIP |",
        "| ---: | ---: | ---: | ---: |",
        "| " + " | ".join(str(counts[k]) for k in ("PASS", "FAIL", "ERROR", "SKIP")) + " |",
        "",
        "| Test | Status | Seconds | Evidence |",
        "| --- | --- | ---: | --- |",
    ]
    for case in cases:
        rows.append("| " + case["name"].replace("|", "\\|") + " | " + case["status"]
                    + " | " + str(case["duration_seconds"]) + " | "
                    + case.get("screenshot", "—") + " |")
    rows.extend(["", "Automated Chromium results do not certify Safari/Firefox, manual accessibility, "
                 "legal/editorial content, or production domain/VPS acceptance.", ""])
    markdown = "\n".join(rows)
    (output / "acceptance.md").write_text(markdown, encoding="utf-8")
    if os.environ.get("GITHUB_STEP_SUMMARY"):
        with open(os.environ["GITHUB_STEP_SUMMARY"], "a", encoding="utf-8") as summary:
            summary.write(markdown)
    return 0 if passed else 1


if __name__ == "__main__":
    sys.exit(main())
