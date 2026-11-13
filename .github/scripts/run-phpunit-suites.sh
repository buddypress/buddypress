#!/usr/bin/env bash

# Separate PHPUnit processes share setup, but neither suite can hide the other.
set -uo pipefail
status=0
for suite in test test_multi; do
	printf '::group::Composer %s\n' "$suite"
	if composer run "$suite"; then
		printf '%s passed\n' "$suite"
	else
		suite_status=$?
		printf '::error::%s failed with exit code %s\n' "$suite" "$suite_status"
		status=1
	fi
	printf '::endgroup::\n'
done
exit "$status"
