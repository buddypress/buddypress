# Continuous integration

CI covers supported WordPress releases and PHP versions without testing every combination:

| Workflow | Coverage | Object cache |
| --- | --- | --- |
| [Main](workflows/unit-test-object.yml) | Trunk on PHP 8.3, 8.4, and 8.6; latest stable on PHP 7.4 and 8.0–8.2; latest three stable release lines on PHP 8.5 | Redis, Memcached, and no cache |
| [Minimum](workflows/unit-test-minimum.yml) | WordPress 6.4 on PHP 7.4 and 8.0–8.6 | No cache |
| [Intermediate](workflows/unit-test-intermediate.yml) | Remaining stable release lines above 6.4 on PHP 8.2 | No cache |

The workflows run on pull requests and pushes to `master`, except docs-only changes under `docs/`. Stable releases use their latest patches. See [matrix generation](scripts/wordpress-matrix.py) for exact combinations.

Each job shares setup and runs both single-site and multisite suites, even if the first fails. Either failure fails the job, except WordPress 6.4 on PHP 8.5/8.6: these experimental jobs report failures without blocking CI because of known Core deprecations.

Trunk core and test helpers use the same revision. Composer audit is enabled. [Coding standards](workflows/coding-standards.yml) runs separately on PHP 8.5.
