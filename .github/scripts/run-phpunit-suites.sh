#!/usr/bin/env bash

# Separate PHPUnit processes share setup, but neither suite can hide the other.
set -uo pipefail
status=0
: "${PHPUNIT_MAJOR:?}"
case "$PHPUNIT_MAJOR" in 9|10) ;; *) echo 'Unsupported PHPUnit major' >&2; exit 1 ;; esac
for suite in test test_multi; do
	printf '::group::Composer %s\n' "$suite"
	if { [[ "$suite" == test ]] && composer run "phpunit-$PHPUNIT_MAJOR"; } || \
		{ [[ "$suite" == test_multi ]] && composer run "phpunit-$PHPUNIT_MAJOR" -- --multisite; }; then
		printf '%s passed\n' "$suite"
	else
		suite_status=$?
		printf '::error::%s failed with exit code %s\n' "$suite" "$suite_status"
		status=1
	fi
	printf '::endgroup::\n'
done
exit "$status"
