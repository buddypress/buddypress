#!/usr/bin/env python3
"""Partition stable WordPress release lines without a PHP/version cross-product."""

import json
import os
import re
from urllib.request import urlopen


def version_key(version):
    if not isinstance(version, str) or not re.fullmatch(r"\d+\.\d+(?:\.\d+)?", version):
        raise ValueError(f"Invalid stable WordPress version: {version!r}")
    return tuple(int(part) for part in version.split("."))


def build_matrices(lines, offers):
    if not isinstance(lines, list) or not lines:
        raise ValueError("WordPress release discovery returned no versions")
    lines = sorted(set(lines), key=version_key, reverse=True)
    if "6.4" not in lines or any(version_key(line) < (6, 4) for line in lines):
        raise ValueError("Expected release lines from WordPress 6.4 onward")
    if any(len(version_key(line)) != 2 for line in lines) or len(lines) < 3:
        raise ValueError("Expected at least three stable release lines")

    patches = {}
    for offer in offers:
        if offer.get("response") not in ("upgrade", "autoupdate"):
            continue
        version = offer["version"]
        key = version_key(version)
        line = ".".join(version.split(".")[:2])
        if line not in patches or key > version_key(patches[line]["version"]):
            patches[line] = offer
    for line in lines:
        if line not in patches:
            raise ValueError(f"No current patch found for {line}")

    def entry(php, line, cache=""):
        if line == "trunk":
            return {"php": php, "wp_version": line, "object": cache}
        offer = patches[line]
        minimum = offer.get("php_version")
        if not minimum or version_key(php) < version_key(minimum)[:2]:
            raise ValueError(f"PHP {php} is below the WordPress {line} minimum")
        return {"php": php, "wp_version": offer["version"], "object": cache}

    main = [entry("8.6", "trunk", "redis"), entry("8.4", "trunk", "redis"),
            entry("8.3", "trunk", "memcached")]
    main.extend(entry(php, lines[0]) for php in ("8.2", "8.1", "8.0", "7.4"))
    main.extend(entry("8.5", line, "memcached" if index == 0 else "")
                for index, line in enumerate(lines[:3]))
    # WP 6.4 retains null-offset and spl_object_hash() calls deprecated in PHP
    # 8.5/8.6. Fixes shipped in WP 6.9 (#63957) and 7.1 (#58291), not 6.4.
    # Keep newer PHP coverage in the main matrix without suppressing deprecations.
    minimum = [entry(php, "6.4") for php in
               ("8.4", "8.3", "8.2", "8.1", "8.0", "7.4")]
    intermediate = [entry("8.2", line) for line in lines[3:] if line != "6.4"]
    return {"main": {"include": main}, "minimum": {"include": minimum},
            "intermediate": {"include": intermediate},
            "has-intermediate": bool(intermediate)}


def main():
    lines = json.loads(os.environ["WORDPRESS_VERSIONS"])
    with urlopen("https://api.wordpress.org/core/version-check/1.7/", timeout=30) as response:
        offers = json.load(response)["offers"]
    matrices = build_matrices(lines, offers)
    with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as output:
        for name, value in matrices.items():
            encoded = json.dumps(value, separators=(",", ":"))
            print(f"{name}={encoded}", file=output)
            print(f"{name}: {encoded}")


if __name__ == "__main__":
    main()
