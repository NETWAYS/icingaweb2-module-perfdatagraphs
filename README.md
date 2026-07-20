# Icinga Web Performance Data Graphs

Icinga Web Module for Performance Data Graphs. This module enables graphs on the Host and Service Detail View for
the respective performance data.

<img src="https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs/raw/main/doc/_images/screenshot_light.png" alt="Icinga Performance Data Graphs Light Mode">

<img src="https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs/raw/main/doc/_images/screenshot_dark.png" alt="Icinga Performance Data Graphs Dark Mode">

The data is fetched by a "backend module", at least one backend module also need to be enabled:

* [Elasticsearch backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-elasticsearch)
* [Graphite backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-graphite)
* [Influxdb v1 backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-influxdbv1)
* [Influxdb v2 backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-influxdbv2)
* [Prometheus backend](https://github.com/NETWAYS/icingaweb2-module-perfdatagraphs-prometheus)
* [Write your own backend](doc/20-PerfdataSourceHook.md)

This module aims to be a "batteries included" and opinionated solution.
Configuration options are limited by design.

## Features

* Interactive graphs for Host and Service performance data
  * Mouse click and select a region to zoom in
  * Click on a time range or double click to zoom out
* Graphs are adjustable via Icinga 2 custom variables
* Adjustable time range buttons via configuration
* A Tab view that can be included in dashboards
* Changeable backends for performance data
  * Fetched data is cached to improve speed and reduce load on the backend
* Provides Hooks to modify the data

## Installation Requirements

* PHP version ≥ 8.2
* IcingaDB or IDO Database

## Documentation

Documentation for this module is available at [doc](doc/).
