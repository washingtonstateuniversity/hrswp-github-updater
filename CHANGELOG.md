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

## 0.2.0-rc.1 (:construction: Future)

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
