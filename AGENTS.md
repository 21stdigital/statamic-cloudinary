# AGENTS.md

Guidance for coding agents working in `tfd/statamic-cloudinary`.

## Project Overview

- This repository is a PHP package for Statamic/Laravel.
- Package name: `tfd/statamic-cloudinary`.
- PSR-4 root namespace: `TFD\Cloudinary\`, mapped to `src/`.
- Main source code lives in `src/`.
- Package runtime config lives in `config/cloudinary.php` and is published to `config/statamic/cloudinary.php`.
- Blade views live in `resources/views/`.
- There is no frontend build pipeline for this addon.
- Tests use Pest with Orchestra Testbench in `tests/`, for example `tests/Unit/CloudinaryConverterTest.php`.

## Rule Sources

- Before this file, there was no repository-wide `AGENTS.md`.
- No `.cursorrules` file was found.
- No files were found in `.cursor/rules/`.
- No `.github/copilot-instructions.md` file was found.
- If any of these files are added later, treat them as additional instructions.

## Environment And Tooling

- Package manager: Composer.
- Formatter/linter: PHP CS Fixer via `.php-cs-fixer.php`.
- Dev dependencies include `pestphp/pest`, `orchestra/testbench`, `mockery/mockery`, and `phpunit/phpunit`.
- The package targets Statamic `^5.73.11 || ^6.4`.
- Laravel helpers such as `config()`, `resource_path()`, and `collect()` can be assumed.

## Setup Commands

Install dependencies:

```bash
composer install
```

Install production dependencies only:

```bash
composer install --no-dev
```

Refresh Composer autoloads after namespace or file changes:

```bash
composer dump-autoload
```

## Build Commands

- There is no dedicated build step for this package.
- `composer install` and `composer dump-autoload` are the closest setup/build-like commands.
- For package validation, use:

```bash
composer validate
```

## Linting And Formatting

Repository lint command:

```bash
composer lint
```

This expands to:

```bash
php ./vendor/bin/php-cs-fixer fix -v --config .php-cs-fixer.php
```

Safe check-only variant:

```bash
php ./vendor/bin/php-cs-fixer fix --dry-run --diff -v --config .php-cs-fixer.php
```

## Test Commands

Run all tests:

```bash
composer test
```

Equivalent command:

```bash
./vendor/bin/pest
```

Run filtered tests:

```bash
./vendor/bin/pest --filter=CloudinaryConverter
```

Test config lives in `phpunit.xml`; `tests/Pest.php` binds `Tests\TestCase` for `tests/Unit`.

## Files And Responsibilities

- `src/ServiceProvider.php`: package registration, config merge for `statamic.cloudinary`, publish definitions, view namespace `cloudinary`, Blade component registration.
- `src/Converter/CloudinaryConverter.php`: core transformation and URL generation logic.
- `src/Tags/Cloudinary.php`: Statamic tag implementation and Glide fallback behavior.
- `src/Components/Image.php`: Blade component wrapper around the converter.
- `src/Interfaces/CloudinaryInterface.php`: small shared interface.
- `config/cloudinary.php`: package defaults and config wiring.
- `resources/views/components/cloudinary-image.blade.php`: rendered markup for `<x-cloudinary-image />`.

## Code Style

Follow the existing repository style first, then normalize with PHP CS Fixer.

- Use PSR-4 namespaces under `TFD\Cloudinary`.
- Keep `declare(strict_types=1);` in new or already modernized PHP files.
- Use short arrays: `[]`.
- Prefer single quotes unless interpolation or escaping makes double quotes clearer.
- Use one import per statement.
- Keep imports alphabetized.
- Remove unused imports.
- Leave exactly one blank line after the namespace and after the import block.
- Use explicit visibility for methods and properties.
- Prefer one class member per statement.
- End files with exactly one trailing newline.
- Do not use a closing `?>` tag in PHP files.

## Imports

- Import framework and package classes at the top of the file.
- Use import aliases only when needed to avoid collisions or confusion, for example `Asset as AssetsAsset`.
- Avoid inline fully qualified class names when a `use` statement is clearer.
- The fixer does not enforce leading import slashes and may not guarantee alphabetic ordering on its own.

## Formatting

- Keep 4-space indentation.
- Match existing brace style and method layout.
- Use blank lines only between logical blocks.
- Keep simple delegation methods compact.
- Use multiline arrays when they improve readability.
- Keep trailing commas in multiline arrays.
- Concatenate strings without extra spaces around `.`.

## Types And Signatures

- The codebase is mixed: older files are looser, newer files use `strict_types`, typed properties, and return types.
- Preserve compatibility with the surrounding code when editing existing files, but do not remove modern typing from already typed files.
- Do not add scalar type hints or return types broadly unless all affected usage is updated consistently.
- Prefer constructor dependency injection for services and components.
- Prefer types on new APIs when they are clearly safe for the supported PHP/Laravel/Statamic matrix.
- Use `Collection` deliberately when a method already works in collection terms.

## Naming

- Classes use PascalCase.
- Methods and properties use camelCase.
- Config keys and array keys use snake_case when mirroring external configuration or Cloudinary parameters.
- Align terminology with Statamic and Cloudinary, such as `asset`, `tag`, `transformations`, `delivery_type`, and `fetch_format`.
- Namespace directories should reflect intent, for example `Tags`, `Components`, `Converter`, and `Interfaces`.

## Error Handling And Logging

- Prefer graceful fallbacks over hard failures, especially when delegating to Statamic Glide is possible.
- Existing code catches `ItemNotFoundException` and logs with `Log::error(...)`.
- Preserve fallback paths when configuration is missing or asset lookups fail.
- Use logging for operational problems that should not fully break rendering.
- Do not silently swallow exceptions unless there is a clear reason and it matches the established pattern.

## Package-Specific Conventions

- Always validate configuration before generating Cloudinary URLs.
- Preserve the Glide fallback in `src/Tags/Cloudinary.php` when Cloudinary is unavailable.
- Keep parameter translation centralized in `CloudinaryConverter`.
- Treat Statamic asset IDs, asset URLs, and mapped external URLs as supported inputs.
- Do not change config/view publish paths, the `cloudinary` view namespace, or existing tag/component names unless a breaking change is intentional.

## Blade And View Conventions

- Keep Blade component output simple and predictable.
- Output dynamic values with normal Blade escaping via `{{ }}`.
- Prefer preparing view data in PHP rather than hiding transformation logic in Blade.
- Keep HTML attributes explicit when they affect dimensions or accessibility.
- The current Blade component is `<x-cloudinary-image />`; keep alias, view path, and docs in sync if it changes.

## When Editing Code

- Make the smallest change that solves the problem.
- Avoid broad refactors unless the task requires them.
- Add new tests under `tests/` and run `composer test`.
- If formatting changes materially, run PHP CS Fixer afterward.
- When adding new classes under `src/`, ensure path and namespace match PSR-4.
- Update `README.md` when public behavior or configuration changes.

## Watchouts

- Some files still have inconsistent spacing; prefer fixer-compliant formatting when touching them.
- `composer lint` modifies files because it runs `php-cs-fixer fix`, not a dry run.
- Run `composer test` after relevant changes.
- Be especially careful with generated URLs, fallback semantics, and config key names.

## Recommended Workflow

1. Read the target file and nearby usages before editing.
2. Make a focused change that follows existing Statamic/Laravel patterns.
3. Run `composer lint` or the dry-run variant as appropriate.
4. Run `composer test` or a focused `./vendor/bin/pest --filter=...` command.
5. Summarize behavior changes, fallback impact, and config implications briefly.
