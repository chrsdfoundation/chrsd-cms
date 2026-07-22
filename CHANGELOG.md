# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Git repository setup and hygiene: hardened `.gitignore`, `.gitattributes`,
  `LICENSE`, `CONTRIBUTING.md`, `CHANGELOG.md`.
- Consolidated GitHub Actions pipeline (`ci.yml`): test, lint, build, and a
  push-to-`main` cPanel deploy gated on passing CI.
