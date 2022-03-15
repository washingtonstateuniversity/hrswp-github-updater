# HRSWP GitHub Updater

Author: Adam Turner  
Author: Washington State University  
URI: https://github.com/washingtonstateuniversity/hrswp-github-updater

<!--
Changelog formatting (https://keepachangelog.com/en/1.0.0/):

## Major.MinorAddorDeprec.Bugfix YYYY-MM-DD

### Added (for new features.)
### Changed (for changes in existing functionality.)
### Deprecated (for soon-to-be removed features.)
### Removed (for now removed features.)
### Fixed (for any bug fixes.)
### Security (in case of vulnerabilities.)
-->

## 1.0.2-rc.1 (:construction: TBD)

### Changed

- Upgrade npm-package-json-lint from 5.4.2 to 6.0.3. (a60c307)
- Update @wordpress/npm-package-json-lint-config from 4.0.5 to 4.1.2. (06c9d73)
- Update roave/security-advisories dev-master from 2ec9ad6 to 86b842d. (48f3a4d)

### Security

- Bump sirbrillig/phpcs-variable-analysis from 2.11.2 to 2.11.3. (7e5a2ec)

## 1.0.1 (2022-03-10)

### Changed

- Bump WP tested-to to 5.9.1.

### Security

- Upgrade roave/security-advisories dev-master 0488e16 to 2ec9ad6. (27e6657)
- Bump squizlabs/php_codesniffer from 3.6.0 to 3.6.2. (3c66817)
- Bump dealerdirect/phpcodesniffer-composer-installer from 0.7.1 to 0.7.2. (1380b49)
- Bump npm-package-json-lint from 5.4.0 to 5.4.2. (fbc6de3)

## 1.0.0 (2021-10-12)

### Added

- Sanitize plugin settings option, close #13. (79e5fb8)
- Extend GitHub URI validation, close #16. (ba76dd1)
- Create site health tests for the GitHub update APIs, close #6. (ba76dd1)
- Add WP rest API route for running site health tests. (ba76dd1)

### Changed

- Update README with description and instructions.
- Update API check to use the updated error response format. (ba76dd1)
- Modify the data stored in each repo tranient to store the etag value for later API checks and use a more useful error response. (ba76dd1)
- Upgrade npm-package-json-lint from 5.2.4 to 5.4.0. (85dc876)

### Fixed

- Fix typo in class method call `get_error_message`. (ba76dd1)

### Security

- Update roave/security-advisories. (0901021)
- Bump npm-package-json-lint from 5.2.3 to 5.2.4. (e8f113c)

## 0.3.0 (2021-09-14)

### Added

- Add a user notice when no GitHub plugins are selected to be managed, close #8. (6f1e249)
- Add actions to refresh plugin data when users save HRSWP GU settings, close #5 and #7. (ff9ab9b)
- Add functions to correct the upgrade directory name during update process. (b157e53)

### Changed

- Bump WP tested-to to 5.8.1.
- Add option key to the main plugin option to track the unmanaged plugins nag, and fix default options. (6f1e249)
- Add plugin file value to the `github_plugins` array. (ff9ab9b)

### Fixed

- Fix #12 reorder delete actions to fix lingering data and check setting exists before trying to unregister. (2edb64e)
- Fix #4 add check to prevent undefined index error on new activation. (4c51fca)
- Fix #9 no not override non-GitHub plugin descriptions. (66adb9d)
- Fix #10 remove manually added view details link. (ddc92e4)
- Fix non-managed plugins getting version update info. (ff9ab9b)

## 0.2.0 (2021-08-19)

### Added

- Create admin settings screen and functions to handle registering, unregistering, and updating settings options. (a3a13a5)
- Add function to delete plugin transients and hook into uninstall. (c0c43eb)
- Add plugin option to store transient keys for later cleanup. (c0c43eb)
- Create the GitHub plugin version check handler. (1146431)
- Create `api.php` file in `lib` directory with function to get all plugins with a GitHub URL in the `Update URI` field, and a function to get repo details from the GitHub API. (1434a5f, 63d6012)
- Create `plugins.php` file in `admin` directory with modifications to the WP Plugins admin screen. (233ee5b, 63d6012)
- Add function to help retrieve plugin status option values. (afa6e98)

### Changed

- Bump required PHP version to 7.3. (9bdc182)
- Update plugins screen with managed plugins option and settings to check that the GitHub plugin is one we are managing. (a3a13a5)
- Set default settings and change option names. (a3a13a5)
- Move the GitHub repository call transient check to the API function. (1146431)
- Move the `load` file to the `inc` directory. (301ca55)
- Update options functions to use new transient naming. (724820a)
- Shorten the transient namespace to help with key length. (28d0d26)
- Update npm-package-json-lint from 5.1.0 to 5.2.3. (c2de2cb)

## 0.1.0 (2021-08-04)

### Added

- Add function to manage plugin data updates with timeout transient. (20f33c8)
- Add activate, deactivate, and uninstall processes with meta helper function. (20f33c8)
- Create options file to manage plugin options. (20f33c8)
- Create plugin entrypoint with init method. (2f6ddd9)
- Create GitHub Actions linting CI config. (70e3404)
- Add readme with installation and basic use information. (db6fd1c)
- Create change log and contributing guide. (db6fd1c)
- Create pull request, bug report, and feature request templates. (db6fd1c)
- Add PHP codesniffer config file. (5588ce1)
- Add NPM Package JSON Lint config file. (5588ce1)
- Create `package.json` file to manage npm build tools and scripts. (5588ce1)
- Create Composer file to manage Composer dev-dependencies for PHP linting. (5588ce1)
- Create shared `.editorconfig`, `.gitattributes`, and `.gitignore` files. (5588ce1)
- GPL v3.0 license.
