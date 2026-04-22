# Performance Data Graph Dashboard

The module offers a dedicated page for graphs that can be used on an Icinga Web Dashboard.

With the IcingaDB module at:

* `icingadb/host/graphs?name=yourHostName`
* `icingadb/service/graphs?name=yourServiceName&host.name=yourHostName`

With the Monitoring module at:

* `monitoring/host/tabhook?host=yourHostName&hook=graphs`
* `monitoring/service/tabhook?host=yourHostName&service=yourServiceName&hook=graphs`

HTTP parameters are used to managed what is rendered:

| Parameter  | Function |
|---------|--------|
| `host` | Name of the Icinga host |
| `service` | Name of the Icinga service |
| `label` | (optional) Name of a specific performance data label to render. Can be used multiple times |
| `perfdatagraphs.duration` | duration for which to fetch the data for in PHP's [DateInterval](https://www.php.net/manual/en/class.dateinterval.php) format (e.g. PT12H, P1D, P1Y) |

Example:

```
http://icingaweb2.internal/icingadb/host/graphs
 ?name=example
 &perfdatagraphs.duration=P1D
 &label=time&label=size
```
