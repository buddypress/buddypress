#!/usr/bin/env python3
"""Check CLI dispatch without WordPress or installed PHPUnit dependencies.

Run with: python3 tests/phpunit/test-runner.py
Set PHP_BINARY to test another PHP executable.
"""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

SOURCE = Path(__file__).resolve().parent
PHP = os.environ.get("PHP_BINARY", "php")

with tempfile.TemporaryDirectory(prefix="bp-runner-") as directory:
    root = Path(directory).resolve() / "project with spaces"
    runner = root / "tests/phpunit"
    (runner / "includes").mkdir(parents=True)
    shutil.copyfile(SOURCE / "run.php", runner / "run.php")
    shutil.copyfile(
        SOURCE / "includes/class-phpunit-runner.php",
        runner / "includes/class-phpunit-runner.php",
    )
    vendor = root / "vendor"
    (vendor / "phpunit/phpunit").mkdir(parents=True)
    (vendor / "autoload.php").write_text(
        "<?php namespace PHPUnit\\Runner; class Version {"
        " public static function id() { return getenv('TEST_MAJOR') . '.0.0'; } }"
    )
    (vendor / "phpunit/phpunit/phpunit").write_text(
        "#!/usr/bin/env php\n<?php declare(strict_types=1);\n"
        "echo json_encode(array('args' => array_slice($argv, 1),"
        " 'environment' => getenv('TEST_FORWARD')));\n"
        "fwrite(STDERR, 'child stderr');\nexit((int) getenv('TEST_EXIT'));\n"
    )

    for major in (9, 10):
        for multisite in (False, True):
            for status in (0, 7):
                literal = "a value; $(echo unsafe) 'quoted'"
                arguments = [f"--phpunit-major={major}", "--filter", literal]
                if multisite:
                    arguments.append("--multisite")
                env = dict(os.environ, TEST_MAJOR=str(major), TEST_EXIT=str(status),
                           TEST_FORWARD="inherited", WP_MULTISITE="0")
                result = subprocess.run(
                    [PHP, str(runner / "run.php"), *arguments],
                    env=env, capture_output=True, text=True, check=False,
                )
                assert result.returncode == status, result.stderr
                assert result.stderr == "child stderr", result.stderr
                data = json.loads(result.stdout)
                config = (f"tests/phpunit/multisite{'10' if major == 10 else ''}.xml"
                          if multisite else f"phpunit{'10' if major == 10 else ''}.xml.dist")
                expected = ["--configuration", str(root / config)]
                if major == 10:
                    expected.append("--do-not-fail-on-skipped")
                expected += ["--filter", literal]
                assert data == {"args": expected, "environment": "inherited"}, data

    result = subprocess.run(
        [PHP, str(runner / "run.php"), "--phpunit-major=10"],
        env=dict(os.environ, TEST_MAJOR="9"), capture_output=True, text=True,
        check=False,
    )
    assert result.returncode == 1
    assert "does not match" in result.stderr
    assert not result.stdout

print("Runner dispatch, arguments, environment, stderr and exit codes: OK")
