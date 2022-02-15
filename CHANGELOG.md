# Release Notes for Fragments

## [Unreleased]

## 1.0.10 - 2022-02-15
### Added
- Added GraphQL query for fetching a list of fragments by `type` and `zone`. Also supports passing in a `currentUrl` to return only fragments that meet the visibility rules.

## 1.0.8 - 2021-11-09
### Fixed
- Fixed bug causing fragment sort order to be reset when updating an existing fragment

## 1.0.7 - 2021-09-14
### Fixed
- Fixed error when creating fragments in installs with custom DB table prefix set up (#6).

## 1.0.6 - 2021-09-09
### Fixed
- Fixed `getCanonicalId()` method not found error in CMS versions lower than 3.7

## 1.0.5 - 2021-08-30
### Fixed
- Fixed deprecation warnings due to usage of element's `getSourceId()` method

## 1.0.4 - 2021-08-15
### Fixed
- Fixed CHANGELOG.md

## 1.0.3 - 2021-08-14
### Fixed
- Fixed bug when saving fragments in single site setups. 

## 1.0.2 - 2021-08-13
### Fixed
- Fixed bug in FragmentQuery trying to get current URL when current request is a console request.

## 1.0.1 - 2021-08-13
### Fixed
- Fixed new fragment button error when only one fragment type is available.

## 1.0.0 - 2021-08-11
### Added
- Initial release
