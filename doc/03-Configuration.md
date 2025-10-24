# Configuration

This describes the configuration options for the "frontend" module.

```
cat /etc/icingaweb2/modules/perfdatagraphs/config.ini
[perfdatagraphs]
default_timerange = "PT12H"
default_backend = "Graphite"
```

Each individual "backend" module, which is responsible for fetching the data from a performance data backend (Graphite, OpenSearch, Elasticsearch, InfluxDB, etc.), provides its own configuration options.

## Performance Data Backend

Hint: If you only installed one backend module, it will be used by default. No need for configuration.

1. Install the Icinga Web Performance Data Graphs backend module you need (depending on where Icinga 2 sends its data)
2. If there is more than one, configure the backend using the `Configuration → Modules → Performance Data Graphs → General` menu

## Timeranges

The module defines a list of default timeranges.
The value for the "Current" time range button can be configured.
These buttons use ISO8601 durations in the background (e.g. PT3H, P1D, P1Y).

By default it uses `PT12H` meaning, 12 hours.

Custom timeranges can be defined by creating a `/etc/icingaweb2/modules/perfdatagraphs/timeranges.ini` configuration file.

**Note:** A higher timerange (e.g. many years) will load a lot of data from the backend.
This might cause out-of-memory errors. It is recommended to use the default timeranges.

```ini
;; The key needs to be an ISO8601 duration
[PT24H]
;; display_name is the name of the button
display_name = "Day"
;; href_title is the hover title of the button
href_title = "Show performance data for the last day"
;; href_icon is the icon of the button
href_icon = "calendar"

[P31D]
display_name = "Month"
href_title = "Show performance data for the last month"
href_icon = "calendar"

[P2Y]
display_name = "2 Years"
href_title = "Show performance data for the last 2 years"
href_icon = "calendar"
```

## Custom Variables

Icinga custom variables can be used to modify the rendering of graphs.

| Custom Variable Name  | Function |
|---------|--------|
| perfdatagraphs_config_disable (bool) | Disable graphs for this object |
| perfdatagraphs_config_backend (string) | Set a specific backend for this object |
| perfdatagraphs_config_highlight (string) | Set the specified metric to the first graph to show in the web UI |
| perfdatagraphs_metrics (dictionary)  | Modify specific metric graphs for this object |
| perfdatagraphs_config_metrics_include (array[string]) | Include only the specified metrics for this object |
| perfdatagraphs_config_metrics_exclude (array[string]) | Exclude the specified metrics for this object |

### perfdatagraphs_config_disable

The custom variable `perfdatagraphs_config_disable (bool)` is used to disable all graphs for an object.

```
apply Service "icinga" {
  vars.perfdatagraphs_config_disable = true
}
```

### perfdatagraphs_config_backend

The custom variable `perfdatagraphs_config_backend (string)` is used to set a specific backend for an object.
This backend needs to be enabled and configured.
The backend is specified via its name, see available backends in the module configuration.

```
apply Service "users" {
  vars.perfdatagraphs_config_backend = "MyCustomGraphiteBackend"
}
```

If the backend is not available no data will be returned.

### perfdatagraphs_config_highlight

The custom variable `perfdatagraphs_config_highlight (string)` is used to highlight a specific metric.
This means that it will be the top most graph shown in the web UI.

```
apply Service "ping6" {
  vars.perfdatagraphs_config_highlight = "rta"
}
```

If the given metric is unavailable, the order given by the backend will be used.

### perfdatagraphs_metrics

The custom variables `perfdatagraphs_metrics (dictionary)` is used to modify a specific graph:

- `unit`, unit of this metric
- `fill`, color of the inside of the graph
- `stroke` color of the line of the graph

The variable `perfdatagraphs_metrics` is a dictionary, its keys are the name of the metric
you want to modify. Wildcards can be used with: `*`.

Examples:

```
apply Service "apt" {
  // Set specific colors for a metric
  vars.perfdatagraphs_metrics["critical_updates"] = {
    fill = "rgba(255, 0, 30, 0.3)"
    stroke = "rgba(255, 0, 30, 1)"
  }
}

apply Service "disk" {
  // Set or override a unit for multiple metric
  vars.perfdatagraphs_metrics["/opt/*"] = {
    unit = "bytes"
  }
}
```

The `unit` option can be any string, however, some unit of measurement can be used to apply custom formatting:

- `unit = "bytes"`
- `unit = "seconds"`
- `unit = "percentage"`

**Hint:** Be aware that Icinga 2 sends normalized performance data to the backend (e.g. a check plugin that returns `ms` will be `s` in the backend).

### perfdatagraphs_config_metrics_include/exclude

The custom variable `perfdatagraphs_config_metrics_include (array[string])` is used to select specific metrics that
should be rendered, if not set all metrics are rendered. Wildcards can be used with: `*`.

The custom variable `perfdatagraphs_config_metrics_exclude (array[string])` is used to exclude metrics.
This takes precedence over the include. Wildcards can be used with: `*`.

Examples:

```
apply Service "icinga" {
  vars.perfdatagraphs_config_metrics_include = ["uptime", "*_latency"]
  vars.perfdatagraphs_config_metrics_exclude = ["avg_latency"]
}
```

## Director Integration

Custom variables as dictionaries aren't available as in the DSL, thus to provide variables for specific graphs you need to use Director baskets.

The graphs module provides a few basic baskets to change the behavior of graphs, those can be found in `templates/director`.

Syntax of those baskets are pretty straight forward and can therefore be easily modified if needed.
Copy the following example into a file, change the variables and names to your liking and import them via baskets.

```
{
    "ServiceTemplate": {
        "perfdatagraphs_ping4": {
            "check_command": "ping4",
            "fields": [],
            "object_name": "perfdatagraphs_ping4",
            "object_type": "template",
            "vars": {
                "perfdatagraphs_config_highlight": "rta",
                "perfdatagraphs_metrics": {
                    "pl": {
                        "unit": "%"
                    },
                    "rta": {
                        "unit": "ms"
                    }
                }
            }
        }
    }
}
```
