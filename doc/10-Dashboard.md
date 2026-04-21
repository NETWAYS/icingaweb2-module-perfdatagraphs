# Performance Data Graph Dashboard

The module offers a dedicated page for graphs that can be used on an Icinga Web Dashboard.

This page is available at `icingadb/host/graphs?name=yourHostName` for Hosts and at `icingadb/service/graphs?name=yourServiceName&host.name=yourHostName` for Service
or `monitoring/host/tabhook?host=yourHostName&hook=graphs` or `monitoring/service/tabhook?host=yourHostName&service=yourServiceName&hook=graphs` depending if you use the icingadb or monitoring module.


HTTP parameters are used to managed what is rendered:

| Parameter  | Function |
|---------|--------|
| `host` | Name of the Icinga host |
| `service` | Name of the Icinga service |
| `checkcommand` | Name of the Icinga check command |
| `ishostcheck` | is this a Host or Service Check that is requested  |
| `perfdatagraphs.duration` | duration for which to fetch the data for in PHP's [DateInterval](https://www.php.net/manual/en/class.dateinterval.php) format (e.g. PT12H, P1D, P1Y) |
| `label` | (optional) Name of a specific performance data label to render. Can be used multiple times |
| `perfdatagraphs.duration` | duration for which to fetch the data for in PHP's [DateInterval](https://www.php.net/manual/en/class.dateinterval.php) format (e.g. PT12H, P1D, P1Y) |
| `perfdatagraphs.headline` | Headline for the page. |

Example:

IcingaDB Host
```
http://icingaweb2.internal/icingadb/host/graphs
 ?name=example
 &perfdatagraphs.duration=P1D
 &perfdatagraphs.headline=Example Host HTTP Service
 &label=time&label=size
```
