# Performance Data Graph Dashboard

The module offers a dedicated page for graphs that can be used on an Icinga Web Dashboard.

This page is available at `perfdatagraphs/graphs`.

HTTP parameters are used to managed what is rendered:

| Parameter  | Function |
|---------|--------|
| `host` | Name of the Icinga host |
| `service` | Name of the Icinga service |
| `checkcommand` | Name of the Icinga check command |
| `ishostcheck` | is this a Host or Service Check that is requested  |
| `perfdatagraphs.duration` | duration for which to fetch the data for in PHP's [DateInterval](https://www.php.net/manual/en/class.dateinterval.php) format (e.g. PT12H, P1D, P1Y) |
| `label` | (optional) Name of a specific performance data label to render. Can be used multiple times |

Example:

```
http://icingaweb2.internal/perfdatagraphs/graphs
 ?host=example
 &service=http
 &checkcommand=http
 &ishostcheck=false
 &perfdatagraphs.duration=P1D
 &label=time&label=size
```
