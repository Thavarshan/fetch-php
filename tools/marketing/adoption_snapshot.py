#!/usr/bin/env python3
"""Collect a public, reproducible Fetch PHP adoption snapshot.

Only public GitHub repository metadata and the Packagist package API are used.
GitHub traffic/referrers require owner access and are intentionally excluded.
"""

from __future__ import annotations

import datetime as dt
import json
import os
from pathlib import Path
import sys
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

GITHUB_URL = "https://api.github.com/repos/Thavarshan/fetch-php"
PACKAGIST_URL = "https://packagist.org/packages/jerome/fetch-php.json"


def get_json(url: str) -> dict:
    headers = {
        "Accept": "application/vnd.github+json" if "api.github.com" in url else "application/json",
        "User-Agent": "FetchPHP-Adoption-Snapshot/1.0",
    }
    token = os.getenv("GITHUB_TOKEN")
    if token and "api.github.com" in url:
        headers["Authorization"] = f"Bearer {token}"

    with urlopen(Request(url, headers=headers), timeout=20) as response:
        result = json.load(response)
    if not isinstance(result, dict):
        raise ValueError(f"Expected JSON object from {url}")
    return result


def build_snapshot(github: dict, packagist: dict, when: str) -> dict:
    package = packagist["package"]
    downloads = package["downloads"]
    if not isinstance(downloads, dict):
        raise ValueError("Packagist downloads must be an object")

    versions = package.get("versions", {})
    available_versions = [
        name for name in versions
        if name.lstrip("v").count(".") == 2
        and all(part.isdigit() for part in name.lstrip("v").split("."))
    ]
    newest_stable = max(
        available_versions,
        key=lambda name: tuple(int(part) for part in name.lstrip("v").split(".")),
        default=None,
    )

    return {
        "collected_at_utc": when,
        "github": {
            "stars": github["stargazers_count"],
            "forks": github["forks_count"],
            "open_issues_and_prs": github["open_issues_count"],
            "subscribers": github["subscribers_count"],
        },
        "packagist": {
            "downloads_total": downloads["total"],
            "downloads_monthly": downloads["monthly"],
            "downloads_daily": downloads["daily"],
            "latest_stable_visible": newest_stable,
        },
        "sources": {
            "github": GITHUB_URL,
            "packagist": PACKAGIST_URL,
        },
        "caveats": [
            "Packagist downloads are not unique users or production installations.",
            "open_issues_count includes pull requests.",
            "GitHub traffic and referral analytics are owner-only and not included.",
            "Measurements are snapshots, not attribution of stars to a specific campaign.",
        ],
    }


def main() -> int:
    try:
        now = dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds")
        github = get_json(GITHUB_URL)
        packagist = get_json(PACKAGIST_URL)
        snapshot = build_snapshot(github, packagist, now)
    except (HTTPError, URLError, TimeoutError, ValueError, KeyError, TypeError) as exc:
        print(f"Unable to collect public adoption data: {exc}", file=sys.stderr)
        return 1

    out = Path(os.getenv("SNAPSHOT_DIR", "adoption-snapshot"))
    out.mkdir(parents=True, exist_ok=True)
    (out / "snapshot.json").write_text(
        json.dumps(snapshot, indent=2, sort_keys=True) + "\n", encoding="utf-8"
    )
    g = snapshot["github"]
    p = snapshot["packagist"]
    lines = [
        "# Fetch PHP weekly public adoption snapshot",
        "",
        f"Collected (UTC): {snapshot['collected_at_utc']}",
        "",
        "| Metric | Current value |",
        "| --- | ---: |",
        f"| GitHub stars | {g['stars']:,} |",
        f"| GitHub forks | {g['forks']:,} |",
        f"| GitHub subscribers | {g['subscribers']:,} |",
        f"| GitHub open issues + PRs | {g['open_issues_and_prs']:,} |",
        f"| Packagist total downloads | {p['downloads_total']:,} |",
        f"| Packagist monthly downloads | {p['downloads_monthly']:,} |",
        f"| Packagist daily downloads | {p['downloads_daily']:,} |",
        f"| Packagist latest indexed stable | {p['latest_stable_visible'] or 'Not determined'} |",
        "",
        "Only public counters are captured. No traffic, clone or referrer data is accessible here.",
        "Downloads are not unique users; do not attribute a count change to a specific post.",
    ]
    (out / "summary.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
    print("\n".join(lines))
    summary_path = os.getenv("GITHUB_STEP_SUMMARY")
    if summary_path:
        with open(summary_path, "a", encoding="utf-8") as summary:
            summary.write("\n".join(lines) + "\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
