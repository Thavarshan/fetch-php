# Weekly adoption snapshot

Run `python3 tools/marketing/adoption_snapshot.py` from the repository root.
The script uses Python 3's standard library. It fetches only public GitHub
and Packagist counters and writes `adoption-snapshot/snapshot.json` plus
a Markdown summary. Set `SNAPSHOT_DIR` to change the output directory.

The corresponding Actions workflow also runs weekly and can be triggered
manually. Each run displays a Job Summary and uploads its JSON/Markdown
artifact for up to 90 days (subject to repository policy). To establish
a baseline, go to **Actions → Weekly adoption snapshot → Run workflow**.

**Excluded by design:** private GitHub traffic/referrer insights, exact
unique users, download-to-install conversion and per-post attribution.
Capture Insights → Traffic manually at least weekly if those data matter;
GitHub only retains a short window in its UI. Public GitHub repository
statistics and Packagist download counters are not directly comparable.

If the workflow is unexpectedly disabled due to repo inactivity, re-enable
it from GitHub Actions. A scheduled job is not a guarantee of punctuality.

No paid service, external secret, push permissions or third-party API keys
are required.
