# Installation

The installation includes this "frontend" module, which is responsible for rendering the data
and a "backend" module, which is responsible for fetching the data from a performance data backend (Graphite, OpenSearch, Elasticsearch, InfluxDB, etc.).

## Packages

NETWAYS provides this module via [https://packages.netways.de](https://packages.netways.de/).

To install this module, follow the setup instructions for the **extras** repository.

**RHEL or compatible:**

`dnf install icingaweb2-module-perfdatagraphs`

**Ubuntu/Debian:**

`apt install icingaweb2-module-perfdatagraphs`

Additionally, a backend module (Graphite, InfluxDB, etc.) needs to be installed.

## From source

1. Clone the Icinga Web Performance Data Graphs repository into `/usr/share/icingaweb2/modules/perfdatagraphs`

2. Clone a Icinga Web Performance Data Graphs Backend repository into `/usr/share/icingaweb2/modules/`

Note that hyphens are currently not allowed in Icinga Web module directories. Examples:

```
/usr/share/icingaweb2/modules/perfdatagraphsgraphite/
/usr/share/icingaweb2/modules/perfdatagraphsinfluxdbv1/
/usr/share/icingaweb2/modules/perfdatagraphsinfluxdbv2/
/usr/share/icingaweb2/modules/perfdatagraphsinfluxdbv3/
/usr/share/icingaweb2/modules/perfdatagraphselasticsearch/
/usr/share/icingaweb2/modules/perfdatagraphsopensearch/
```

3. Enable both modules using the `Configuration → Modules` menu or the `icingacli`

5. Configure the "backend" module (e.g. URL and authentication for the performance data backend)

5. (optionally) Grant permissions for the "frontend" and "backend" module for users

6. (optionally) Configure specific graphs via Icinga 2 Custom Variables
