# Icinga Web Performance Data Graphs

Icinga Web Module for Performance Data Graphs. This module enables graphs on the Host and Service Detail View for
the respective performance data.

The data is fetched by a "backend module", at least one backend module also need to be enabled.

## Features

* Interactive graphs for Host and Service performance data
  * Mouse click and select a region to zoom in
  * Click on a time range or double click to zoom out
* Graphs are adjustable via Icinga 2 custom variables
* Interchangeable performance data backends
  * Fetched data is cached to improve speed and reduce load on the backend

## Design Decisions

Here are some of our design decisions in order to understand why the module works the way it does.
This should also be used as a reference for future development.

This module aims to be a "batteries included" and opinionated solution.
Configuration options are limited by design.

### A chart for each metric

There is a separate chart for each metric because the magnitude of performance data for a single check plugin
can vary widely. Meaning one metric could be double digits and another only fractions.
Custom configuration for when metrics should be combined in a single chart would highly
increase the complexity of this module.

### Fixed time range selection

We decided to have a fixed set of time range to choose from.
Having user input for the time ranges would increase the complexity of this module.

### Warning and critical series

If available, the warning and critical series are not shown by default.
Rationale behind this was, that often times these values are many times higher than
the performance data values. This would cause the actual values to be almost invisible.

### Custom variables

We decided to use custom variables for graph configuration.
This avoids having another database for configuration and should integrate well with existing
Icinga configuration management tools.

In order to ease integration with Icinga Director, in which Icinga2 dictionary data types are currently
no the easiest to work with, we decided to use "flat" data types where possible (e.g. `perfdatagraphs_config_disable`).

However, for the `perfdatagraphs_metrics` variable a dictionary is the natural fit and "flat" data types
would have increased the complexity of the code base. Thus, we decided to use dictionaries.

### Y-axis units

Too high or low values for the y-axis are automatically transformed into exponential notation,
otherwise the width of the axis would grow endlessly.

Custom formats can be invoked by using `unit` for a metric:

```
vars.perfdatagraphs_metrics["disk"] = {
  unit = "bytes"
}
```

The configuration documentation shows all available units.
