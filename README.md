# Icinga Web Performance Data Graphs

Icinga Web Module for Performance Data Graphs. This module enables graphs on the Host and Service Detail View for
the respective performance data.

<img src="https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs/raw/main/doc/_images/screenshot_light.png" alt="Icinga Performance Data Graphs Light Mode">

<img src="https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs/raw/main/doc/_images/screenshot_dark.png" alt="Icinga Performance Data Graphs Dark Mode">

The data is fetched by a "backend module", at least one backend module also need to be enabled:

* [Graphite backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-graphite/)
* [Influxdb v1 backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-influxdbv1)
* [Influxdb v2 backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-influxdbv2)
* [Write your own backend](doc/20-PerfdataSourceHook.md)

This module aims to be a "batteries included" and opinionated solution.
Configuration options are limited by design.

## Features

* Interactive graphs for Host and Service performance data
  * Mouse click and select a region to zoom in
  * Click on a time range or double click to zoom out
* Graphs are adjustable via Icinga 2 custom variables
* Interchangeable performance data backends
  * Fetched data is cached to improve speed and reduce load on the backend

## Installation Requirements

* PHP version ≥ 8.0
* IcingaDB or IDO Database

## Documentation

Documentation for this module is available at [doc](doc/).

## Known Issues

### Timeout or page performance degradation when rendering many charts

When there are many performance data timeseries for a singe check command,
and the selected time range is very long, fetching the data might timeout.

Use `perfdatagraphs_config_metrics_exlude` and `perfdatagraphs_config_metrics_include`
to reduce the amout of data being loaded.

# Road to Version 1.0.0

What our current idea for a version 1.0.0 of this module is:

* It should work with every Icinga performance data writer with minimal configuration by the user
* It should be a robust solution for all check plugins
* It should integrate seamlessly in the Icinga Web UI
* It should provide enough options for customization for most use cases
