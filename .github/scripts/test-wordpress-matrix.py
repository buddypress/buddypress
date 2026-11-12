#!/usr/bin/env python3
"""Offline tests for release partitioning and paired-suite exit handling."""

import importlib.util
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

SCRIPTS = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location("matrix", SCRIPTS / "wordpress-matrix.py")
matrix = importlib.util.module_from_spec(spec)
spec.loader.exec_module(matrix)


class MatrixTests(unittest.TestCase):
    def setUp(self):
        self.lines = ["7.1", "7.0", "6.9", "6.8", "6.7", "6.6", "6.5", "6.4"]
        self.offers = [{"response": "autoupdate", "version": line + ".2",
                        "php_version": "7.4"} for line in self.lines]

    def test_partition_and_patch_resolution(self):
        result = matrix.build_matrices(self.lines, self.offers)
        self.assertEqual(10, len(result["main"]["include"]))
        self.assertEqual(8, len(result["minimum"]["include"]))
        self.assertEqual(["6.8.2", "6.7.2", "6.6.2", "6.5.2"],
                         [job["wp_version"] for job in result["intermediate"]["include"]])
        self.assertEqual({"7.4", "8.0", "8.1", "8.2", "8.3", "8.4", "8.5", "8.6"},
                         {job["php"] for job in result["minimum"]["include"]})
        self.assertEqual(["memcached", "", ""],
                         [job["object"] for job in result["main"]["include"][-3:]])
        self.assertTrue(all(job["wp_version"] == "6.4.2"
                            for job in result["minimum"]["include"]))

    def test_numeric_sorting_deduplication_and_latest_patch(self):
        self.lines += ["6.10", "6.9"]
        self.offers += [{"response": "autoupdate", "version": "6.10.12", "php_version": "7.4"},
                        {"response": "autoupdate", "version": "6.10.9", "php_version": "7.4"}]
        result = matrix.build_matrices(self.lines, self.offers)
        self.assertEqual("6.10.12", result["main"]["include"][-1]["wp_version"])

    def test_empty_intermediate_window(self):
        result = matrix.build_matrices(["6.6", "6.5", "6.4"], self.offers)
        self.assertFalse(result["has-intermediate"])
        self.assertEqual([], result["intermediate"]["include"])

    def test_invalid_discovery(self):
        for lines in ([], ["7.1"], ["7.1", "7.0", "6.4;echo x"], ["7.1", "7.0", "6.3"]):
            with self.subTest(lines=lines), self.assertRaises(ValueError):
                matrix.build_matrices(lines, self.offers)

    def test_missing_patch_and_incompatible_php_fail(self):
        with self.assertRaises(ValueError):
            matrix.build_matrices(self.lines, self.offers[:-1])
        self.offers[0]["php_version"] = "8.3"
        with self.assertRaises(ValueError):
            matrix.build_matrices(self.lines, self.offers)

    def test_both_suites_run_and_either_failure_fails_job(self):
        with tempfile.TemporaryDirectory() as temp:
            composer = Path(temp) / "composer"
            composer.write_text('#!/bin/sh\nprintf "%s\\n" "$2" >> "$CALL_LOG"\n'
                                '[ "$2" != "$FAIL_SUITE" ]\n')
            composer.chmod(0o755)
            for failing in ("", "test", "test_multi"):
                log = Path(temp) / ("calls-" + failing)
                env = dict(os.environ, PATH=temp + os.pathsep + os.environ["PATH"],
                           CALL_LOG=str(log), FAIL_SUITE=failing)
                result = subprocess.run(["bash", str(SCRIPTS / "run-phpunit-suites.sh")],
                                        env=env, capture_output=True, text=True, check=False)
                self.assertEqual(["test", "test_multi"], log.read_text().splitlines())
                self.assertEqual(1 if failing else 0, result.returncode)


if __name__ == "__main__":
    unittest.main()
