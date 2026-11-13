"""Offline contract tests for the public adoption metrics report."""

import importlib.util
from pathlib import Path
import unittest

module_path = Path(__file__).with_name("adoption_snapshot.py")
spec = importlib.util.spec_from_file_location("adoption_snapshot", module_path)
module = importlib.util.module_from_spec(spec)
assert spec.loader is not None
spec.loader.exec_module(module)


class SnapshotTests(unittest.TestCase):
    def test_metrics_and_numeric_latest_stable_sort(self):
        github = {
            "stargazers_count": 450,
            "forks_count": 27,
            "open_issues_count": 38,
            "subscribers_count": 5,
        }
        package = {
            "package": {
                "downloads": {"total": 12000, "monthly": 800, "daily": 42},
                "versions": {
                    "dev-main": {},
                    "3.8.1": {},
                    "4.1.0": {},
                    "4.10.0": {},
                    "4.2.0-beta1": {},
                },
            },
        }
        report = module.build_snapshot(github, package, "2026-11-14T00:00:00+00:00")
        self.assertEqual(report["github"]["stars"], 450)
        self.assertEqual(report["packagist"]["downloads_total"], 12000)
        self.assertEqual(report["packagist"]["latest_stable_visible"], "4.10.0")
        self.assertTrue(report["caveats"])

    def test_missing_downloads_fails_loudly(self):
        with self.assertRaises(KeyError):
            module.build_snapshot({}, {"package": {}}, "2026-11-14")


if __name__ == "__main__":
    unittest.main()
