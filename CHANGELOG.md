# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-11-17

First release: CCPA/CPRA consumer requests for Laravel, part of the laravel-ccpa suite.

- Requests to know, delete and correct (Cal. Civ. Code §§1798.105, 1798.106 and 1798.110), with status and verification before any export or deletion.
- Receipt must be confirmed within 10 business days (CCPA Regulations §7021): `acknowledge()` and the `acknowledgementOverdue` scope.
- A 45-day deadline that `extend()` stretches once by 45 more days, within the first 45 days and with a reason (§1798.130(a)(2)(A)).
- Requests to know export every declared column as JSON to a private disk. Deletion deidentifies every record of the consumer; `Keep` columns stay, for the §1798.105(d) exceptions.
- `ResolvesConsumer` finds the consumer's records in other models.
- The `Opened`, `Closed` and `Overdue` events, and `ccpa:requests-overdue`.

## [Unreleased]
