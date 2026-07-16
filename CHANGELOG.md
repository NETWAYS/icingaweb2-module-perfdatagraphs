# Changelog

## v0.5.0

- Raise minimum requirements to PHP 8.2
- Fix x-axis not adjusting when no data is available for the selected range

## v0.4.2

- Fix FileCache filename encoding may contain slashes

## v0.4.1

- Fix inconsistent order of graphs, metrics are now sorted using strnatcmp
- Small improvement on how custom variables are processed

## v0.4.0

- Change default render behavior to only show a limited number charts and add a Tab to all charts.
  This avoids the page being overloaded by charts. The number of charts on the object page is configurable
- Add dedicated page to show all charts for a given host/service that can be used in a Dashboard
- Add check_interval to PerfdataRequest
- Fix some missing translation hooks

## v0.3.2

- Fix custom timeranges not being applied
- Fix Monitoring hook being applied when IcingaDB is used

## v0.3.1

- Fix percent unit not being formatted
- Fix toggle show not surviving autorefresh

## v0.3.0

- Improve datetime formats in plots
- Improve rendering to hide charts that are not on screen
- Introduce show_thresholds variable to toggle rendering thresholds
- Handle configured backend names in lowercase
- Add more debug logging

## v0.2.2

- Fix ellipsis in error messages
- Fix time range controls not being shown on error

## v0.2.1

- Fix timezone not being used
- Fix errors not being shown

## v0.2.0

- Rework data-fetching to use PHP
- Add human-readable units in legend
- Add the option to configure custom timeranges
- Gaps for missing data are automatically added
- Use locale to adjust time format in legend
- Fix memory leak in chart rendering
- Fix inconsistent collapsible behavior during autorefresh
- Extend IcingaObjectHelper to be able to resolve Macros

## v0.1.2

- Fix custom variables could not be loaded in IDO

## v0.1.1

- Add selector for rendered event so that charts are loaded on Dashboards
- Add missing config element for the cache lifetime
- Change warning critical series to be shown by default

## v0.1.0

- Initial Release
