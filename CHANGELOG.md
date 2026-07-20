# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Histogram building for non-fractional bucket sizes (e.g. 1.0).
- Histogram samples for all label combinations but the first being silently dropped.

## [0.1.0] - 2025-12-05
Initial release.

[Unreleased]: https://github.com/erickskrauch/prometheus-php/compare/0.1.0...HEAD
[0.1.0]: https://github.com/erickskrauch/prometheus-php/releases/tag/0.1.0
