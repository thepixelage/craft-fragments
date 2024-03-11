# Release Notes for Fragments

## [Unreleased]

## 5.0.0-beta.1 - 2024-03-11
- Initial beta release for Craft CMS 5

## 4.0.3 - 2023-02-21
### Fixed
- Fixed "Undefined array key" error that appears when edit form is redisplayed after form validation fails.

## 4.0.2 - 2023-01-25
### Fixed
- Fixed bug where entry query fails when `type` or `zone` arguments are used in Fragments field query

### Removed
- Removed irrelevant arguments for Fragments field GQL query: `entryUri`, `entryId`, `userId` and `requestProps`

## 4.0.1 - 2023-01-20
### Fixed
- Fixed bug where array instead of `Fragment` model is passed to `matchConditions` ([#24](https://github.com/thepixelage/craft-fragments/issues/24))
- Fixed bug in GQL `fragments` query where `limit` is applied before matching conditions, causing returned results to exclude fragments that should be matched ([#25](https://github.com/thepixelage/craft-fragments/issues/25))

## 4.0.0 - 2022-05-05
### Changed
- Refactor code to bring it up to Craft CMS 4 compatibility
- Rebuild visibility rules functionality with the new condition builder in Craft CMS 4
- Convert legacy URL visibility rules to use the new `EntryUriConditionRule`
- Replace GraphQL argument `currentUrl` for `fragments` query with `entryUri`

### Fixed
- Fixed issues with converting legacy URL rules that contain regex sensitive characters
- Fixed error caused by undefined `$siteStatuses` variable in single-site instances
- Fixed issue of migrations not running by bumping up `$schemaVersion`

### Added
- New condition rule type `EntryUriConditionRule` with regex matching operators
- Added new `FragmentEntryCondition` to avoid rule type clashes with the native `EntryCondition`
- Added new request condition rule types
- Added user and request conditions in Visibility Rules condition builder
- Added GraphQL arguments to `fragments` query for specifying current user and current request props

## 1.1.2 - 2022-03-19
- Allow getting the fragment type from fragment by `fragment.type` instead of `fragment.fragmentType`.

## 1.1.1 - 2022-02-16
### Added
- Added GraphQL support for Fragments field.

## 1.1.0 - 2022-02-15
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
