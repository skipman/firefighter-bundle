# Changelog

All notable changes to this project are documented here.

## [1.1.0] - 2026-05-31

### Added
- Added backend permissions for firefighter member categories
- Added backend permissions for member homebase selection
- Added WhatsApp as a social media channel for departments
- Added additional event group options

### Changed
- Updated sorting for command function listings

### Fixed
- Fixed wizard field labels for courses, badges and awards
- Improved compatibility with Contao 5.7 while keeping Contao 5.3 support

## [1.0.2] . 2026-05-16

### Fixed

- Removed outdated backend template override (`be_main.html5`)
- Cleaned up obsolete core asset includes

## [1.0.1] . 2026-05-14

### Fixed

- Fixed required field logic for firefighter hierarchy settings
- Validated and unified required fields for BFK, AFK, FF and BTF
- Fixed the backend display of the vehicle selection in Contao 5.7
- Modernized backend asset integration for the bundle CSS

## [1.0.0] . 2026-05-06

### Added

- First stable release
- Prepared GPL-compliant publication
- Revised license notices and documentation
- Prepared optional support and service features

### Fixed

- Fixed sorting of supra-local command functions

## [0.9.3] . 2026-03-21

### Added

- Added Contao 5.7 compatibility

### Fixed

- Removed dependency on a content element
- Cleaned up MultiColumnWizard handling
- Improved select field behavior with Chosen
- Improved DCA compatibility
- Improved alias and headline generation
- Revised permission handling

## [0.9.2] . 2025-07-01

### Added

- Added the `ua` field for fire departments
- Added the `theme`, `responsible` and `participant` fields for events

### Fixed

- Fixed AFK filter returning the ID instead of the name
- Fixed duplicate field names in the firefighter module
- Improved backend display of the vehicle selection
- Fixed an issue with `ce_ff-resources` when used under a different domain

## [0.9.1] . 2025-05-23

### Added

- Added `firefighterCourses`
- Added `firefighterBadges`
- Added `firefighterAwards`

### Fixed

- Fixed handling of delete commands in MCW fields
- Fixed permission assignment for member management
