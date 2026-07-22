# Contributing — CHRSD CMS

Internal contribution guide for CHRSD Foundation developers. This is proprietary
software (see [LICENSE](LICENSE)); external contributions are not accepted.

## Prerequisites
- PHP 8.3+, Composer 2.x
- Node.js 20+ and npm
- Git 2.40+

## Local setup
See the Quick Start in [README.md](README.md) and [ONBOARDING.md](ONBOARDING.md).

## Branching
| Branch | Purpose |
|---|---|
| `main` | Production. Protected. Deploys on push. |
| `develop` | Integration branch for the next release. |
| `feature/*` | New work. Branch from `develop`. |
| `bugfix/*` | Non-urgent fixes. Branch from `develop`. |
| `hotfix/*` | Urgent production fixes. Branch from `main`. |
| `release/*` | Release stabilization. Branch from `develop`. |

Flow: `feature/*` → PR into `develop` → `release/*` → `main`.
Hotfixes: `hotfix/*` → `main` (then back-merge to `develop`).

## Commit messages — Conventional Commits
Format: `<type>(<optional scope>): <description>`

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`,
`ci`, `chore`, `revert`.

Examples:
- `feat(certificate): add per-year serial reset on Jan 1`
- `fix(verify): correct HMAC comparison for legacy hashes`
- `ci: skip puppeteer chromium download in build job`
- `chore(deps): bump filament to 3.2.x`

## Pull requests
1. Open the PR against `develop` (or `main` for hotfixes).
2. All CI checks (`test`, `lint`, `build`) must pass — they are required.
3. At least one review before merge. Keep PRs focused and small.

## Code quality
- Run `vendor/bin/pint` before pushing (CI enforces `pint --test`).
- Add/adjust tests for behavioral changes; `php artisan test` must pass.
