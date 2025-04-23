;(function(Icinga, $) {

    'use strict';

    // The element in which we will add the charts
    const CHART_CLASS = '.line-chart';
    // The element in which we will show errors
    const CHART_ERROR_CLASS = 'p.line-chart-error';
    // Names to identify the warning/critical series
    const CHART_WARN_SERIESNAME = 'warning';
    const CHART_CRIT_SERIESNAME = 'critical';
    // Endpoint to fetch the data from
    const FETCH_ENDPOINT = '/perfdatagraphs/fetch';
    // Timeout for the data fetch
    const FETCH_TIMEOUT = 15000;  // TODO: Can we make this configurable somehow?

    class Perfdatagraphs extends Icinga.EventListener {
        // data contains the fetched chart data with the element ID where it is rendered as key.
        // Where we store data in between the autorefresh.
        data = new Map();
        // plots contains the chart objects with the element ID where it is rendered as key.
        // Used for resizing the charts.
        plots = new Map();
        // Where we store data in between autorefresh
        currentSelect = null;
        currentCursor = null;
        currentSeriesShow = {1: true};
        // Where we store the variable selected and the constant timerange
        duration = '';
        defaultDuration = '';

        constructor(icinga)
        {
            super(icinga);

            // We register a ResizeObserver so that we can resize the charts
            // when their respective .container changes size.
            this.resizeObserver = new ResizeObserver(entries => {
                for (let elem of entries) {
                    const _plots = this.plots.get(elem.target) ?? [];
                    const s = this.getChartSize(elem.contentRect.width);
                    for (let u of _plots) {
                        u.setSize(s);
                    }
                }
            });

            this.on('rendered', '#main > .icinga-module', this.rendered, this);
            this.on('click', '.perfdata-charts a.action-link[data-duration]', this.onTimeClick, this);
        }

        /**
         * rendered makes sure the data is available and then renders the charts
         */
        rendered(event, isAutorefresh)
        {
            let _this = event.data.self;

            // This elements contains the configured default timerange from the
            // module's configuration.
            const elem = document.getElementById('perfdatagraphs-default-timerange');
            if (!elem) {
                return;
            }

            const defaultduration = elem.getAttribute('data-duration');
            this.duration = defaultduration;
            this.defaultDuration = defaultduration;

            if (!isAutorefresh) {
                // Reset the selection and set the duration when it's
                // an autorefresh and new data is being loaded.
                // _this.currentSelect = {min: 0, max: 0};
                _this.currentSelect = null;
                _this.currentSeriesShow = {1: true};
                _this.currentCursor = null;
                _this.duration = this.defaultDuration;
            }

            // Now we fetch
            _this.fetchData();
            // ...and render in case we already have data
            _this.renderCharts();
        }

        /**
         * onTimeClick retrieves the requested duration and calls the rendered.
         */
        onTimeClick(event)
        {
            let _this = event.data.self;
            let target = event.currentTarget;

            const duration = target.getAttribute('data-duration');

            // Reset the selection and set the duration.
            // These need to be stored in between the autorefresh
            _this.currentSelect = {min: 0, max: 0};
            _this.duration = duration;

            // Now we fetch and render
            _this.fetchData();
            _this.renderCharts();
        }

        /**
         * isValidData validates the received data and shows errors if there are any
         * contained in the data.
         */
        isValidData(data)
        {
            // There is absolutely nothing in the array we can use
            if (data === undefined || data.length === 0) {
                const errorMsg = $(CHART_ERROR_CLASS).attr('data-message-nodata');
                $(CHART_ERROR_CLASS).text(errorMsg).show();
                this.icinga.logger.warn('perfdatagraphs: no data received');
                return false;
            }

            // There are errors in the error section of the response
            if (data.errors !== undefined && data.errors.length > 0) {
                const errorMsg = $(CHART_ERROR_CLASS).attr('data-message-error');
                $(CHART_ERROR_CLASS).text(errorMsg +': '+ data.errors.join('; ')).show();
                this.icinga.logger.error('perfdatagraphs', data.errors.join('; '));
                return false;
            }

            // There is nothing in the data section of the response
            if (data.data === undefined || data.data.length === 0) {
                const errorMsg = $(CHART_ERROR_CLASS).attr('data-message-nodata');
                $(CHART_ERROR_CLASS).text(errorMsg).show();
                this.icinga.logger.warn('perfdatagraphs: no data received');
                return false;
            }

            return true;
        }

        /**
         * fetchData tries to get the data for the given object from the Controller.
         */
        fetchData()
        {
            var _this = this;

            // Get the elements we going to render the charts in
            const lineCharts = document.querySelectorAll(CHART_CLASS);
            // Check if the elements exist, just to be safe
            if (lineCharts.length < 1) {
                return;
            }

            _this.icinga.logger.debug('perfdatagraphs', 'start fetchData', lineCharts);

            for (let elem of lineCharts) {
                // Get the parameters the Hooks added to the element
                const parameters = {
                    host: elem.getAttribute('data-host'),
                    service: elem.getAttribute('data-service'),
                    checkcommand: elem.getAttribute('data-checkcommand'),
                    ishostcheck: elem.getAttribute('data-ishostcheck'),
                    duration: _this.duration,
                }

                // Make a request to the internal controller to get the data for the charts
                let req = $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    timeout: FETCH_TIMEOUT,
                    cache: true,
                    async: true,
                    url: this.icinga.config.baseUrl + FETCH_ENDPOINT,
                    data: parameters,
                    error: function (request, status, error) {
                        // Just in case the fetch controller explodes on us.
                        // There might be a better way.
                        $('i.spinner').hide();
                        // See module.less for why we set the min-height
                        $('.perfdata-charts-container').css('min-height', '0px');
                        const el = $(request.responseText);
                        const errorMsg = $('p.error-message', el).text();
                        _this.icinga.logger.error('perfdatagraphs:', errorMsg);
                        $(CHART_ERROR_CLASS).text($(CHART_ERROR_CLASS).attr('data-message-error') + ': ' + errorMsg).show();
                    },
                    beforeSend: function() {
                        // We show the spinner when we fetch data
                        $('i.spinner').show();
                    },
                    success: function(data) {
                        // On success try rendering the chart
                        $('i.spinner').hide();
                        _this.icinga.logger.debug('perfdatagraphs', 'finish fetchData', data);

                        if (! _this.isValidData(data)) {
                            // See module.less for why we set the min-height
                            $('.perfdata-charts-container').css('min-height', '0px');
                            return;
                        }

                        $(CHART_ERROR_CLASS).hide()
                        _this.data.set(elem.getAttribute('id'), data.data);
                        // Trigger a render after we fetched data
                        _this.renderCharts();
                    }
                });
            }
        }

        /**
         * getChartSize is used to recalculate the canvas size based on the
         * object-detail colum width.
         */
        getChartSize(width)
        {
            return {
                // Subtract some pixels to avoid flickering scollbar in Chrome
                // Maybe there's a better way?
                width: width - 10,
                // If you change this, remember to also change the collapsible height.
                height: 200,
            };
        }

        /**
         * getXProperty returns the properties for the x-axis.
         * Decided to make this a method to have future customization options.
         */
        getXProperty(axesColor)
        {
            return {
                stroke: axesColor,
                grid: { stroke: axesColor, width: 0.5 },
                ticks: { stroke: axesColor, width: 0.5 }
            };
        }

        /**
         * getYProperty returns the properties for the y-axis.
         * Decided to make this a method to have future customization options.
         */
        getYProperty(axesColor, formatFunction)
        {
            return {
                stroke: axesColor,
                values: formatFunction,
                grid: { stroke: axesColor, width: 0.5 },
                ticks: { stroke: axesColor, width: 0.5 },
                size(self, values, axisIdx, cycleNum) {
                    // We calculate the size of the axis based on the width of the elements
                    let axis = self.axes[axisIdx];

                    // Bail out, force convergence
                    if (cycleNum > 1)
                        return axis._size;

                    let axisSize = axis.ticks.size + axis.gap;

                    // Find longest value
                    let longestVal = (values ?? []).reduce((acc, val) => (val.length > acc.length ? val : acc), "");

                    if (longestVal != "") {
                        self.ctx.font = axis.font[0];
                        axisSize += self.ctx.measureText(longestVal).width / devicePixelRatio;
                    }

                    return Math.ceil(axisSize);
                },
            };
        }

        /**
         * getChartOptions returns shared base options for all charts.
         * These will get merged with individual options (e.g. axes config).
         */
        getChartBaseOptions()
        {
            // The shared options for each chart. These
            // can then be combined with individual options e.g. the width.
            const opts = {
                cursor: { sync: { key: 0, setSeries: true } },
                scales: { x: { time: true } },
                // series holds the config of each dataset, such as visibility, styling,
                // labels & value display in the legend
                series: [ {} ],
                hooks: {
                    init: [
                        u => {
                            u.over.ondblclick = e => {
                                // We need to reset the currentSelect to the min/max
                                // when we zoom out again.
                                this.currentSelect = {min: 0, max: 0};
                            }
                        }
                    ],
                    setCursor: [
                        (u) => {
                            // We need to store the current cursor
                            // to refresh it when the autorefresh hits.
                            this.currentCursor = u.cursor;
                        }
                    ],
                    setSeries: [
                        (u, sidx) => {
                            // When series are toggled, we store the current option
                            // so that it can be restored when the Icinga Web autorefresh hits.
                            if (u.series[sidx] !== undefined) {
                                this.currentSeriesShow[sidx] = u.series[sidx].show;
                            }
                        }
                    ],
                    setSelect: [
                        u => {
                            // When a select is performed, we store the current selection
                            // so that it can be restored when the Icinga Web autorefresh hits.
                            let _min = u.posToVal(u.select.left, 'x');
                            let _max = u.posToVal(u.select.left + u.select.width, 'x');
                            this.currentSelect = {min: _min, max: _max};
                        }
                    ]
                }
            };

            return opts;
        }

        /**
         * renderCharts creates the canvas objects given the provided datasets.
         */
        renderCharts()
        {
            // Get the colors from these sneaky little HTML elements
            const axesColor = $('div.axes-color').css('background-color');
            const warningColor = $('div.warning-color').css('background-color');
            const criticalColor = $('div.critical-color').css('background-color');
            const valueColor = $('div.value-color').css('background-color');
            // These are the shared options for all charts
            const baseOpts = this.getChartBaseOptions();

            // Reset the existing plots map for the new rendering
            this.plots = new Map();

            this.icinga.logger.debug('perfdatagraphs', 'start renderCharts', this.data);

            this.data.forEach((data, elemID, map) => {
                // Get the element in which we render the chart
                const elem = document.getElementById(elemID);

                if (elem === null) {
                    return;
                }

                // Small hack. Since we always collapse
                // we got to remove the button when there's just one chart
                if (data.length === 1) {
                    document.getElementById(elemID + '-control').style.display = 'none';
                };

                // The size can vary from chart to chart for example when
                // there are two contains on the page.
                let opts = {...baseOpts, ...this.getChartSize(elem.offsetWidth)};

                // Add each element to the resize observer so that we can
                // resize the chart when its container changes
                this.resizeObserver.observe(elem);

                // Reset the existing canvas elements for the new rendering
                elem.replaceChildren();

                // Create a new uplot chart for each performance dataset
                data.forEach((dataset) => {
                    dataset.timestamps = this.ensureArray(dataset.timestamps);
                    // Base format function for the y-axis
                    let formatFunction = (u, vals, space) => vals.map(v => this.formatNumber(v));

                    // We change the format function based on the unit of the dataset
                    // This can be extend in the future:
                    // - Create a new format function that returns a formated string for the given value
                    // - Add a new case with the function here
                    // - Update the documentation to include the new format option
                    switch (dataset.unit) {
                    case 'bytes':
                        formatFunction = (u, vals, space) => vals.map(v => this.formatBytesSI(v));
                        break;
                    case 'seconds':
                        formatFunction = (u, vals, space) => vals.map(v => this.formatTimeSeconds(v));
                        break;
                    case 'percentage':
                        formatFunction = (u, vals, space) => vals.map(v => this.formatPercentage(v));
                        break;
                    }

                    opts.axes = [this.getXProperty(axesColor), this.getYProperty(axesColor, formatFunction)];

                    // Add a new empty plot with a title for the dataset
                    opts.title = dataset.title;
                    opts.title += dataset.unit ? ' | ' + dataset.unit : '';

                    let u = new uPlot(opts, [], elem);
                    // Where we store the finished data for the chart
                    let d = [dataset.timestamps];

                    // Create the data for the plot and add the series
                    // Using a 'classic' for loop since we need the index
                    for (let idx = 0; idx < dataset.series.length; idx++) {
                        // // The series we are going to add (e.g. values, warn, crit, etc.)
                        let set = dataset.series[idx].values;
                        set = this.ensureArray(set);

                        // See if there are series options from the last autorefresh
                        // if so we use them, otherwise the default.
                        let show = this.currentSeriesShow[idx+1];
                        // Get the style either from the dataset or from CSS
                        let stroke = dataset.stroke ?? valueColor;
                        let fill = dataset.fill ?? this.ensureRgba(valueColor, 0.3);

                        // Add a new series to the plot. Need adjust the index, since 0 is the timestamps
                        if (dataset.series[idx].name === CHART_WARN_SERIESNAME) {
                            stroke = warningColor;
                            fill = false;
                        }
                        if (dataset.series[idx].name === CHART_CRIT_SERIESNAME) {
                            stroke = criticalColor;
                            fill = false;
                        }

                        u.addSeries({
                            label: dataset.series[idx].name,
                            stroke: stroke,
                            fill: fill,
                            show: show,
                            // Override the default uplot callback so that smaller values are
                            // shown in the hover.
                            value: (self, rawValue) => rawValue,
                        }, idx+1);
                        // Add this to the final data for the chart
                        d.push(set);
                    }
                    // Add the data to the chart
                    u.setData(d);

                    // If a selection is stored we restore it.
                    if (this.currentSelect !== null) {
                        u.setScale('x', this.currentSelect);
                    }
                    // If a cursor is stored we restore it.
                    if (this.currentCursor !== null) {
                        u.setCursor(this.currentCursor);
                    }

                    // Add the chart to the map which we use for the resize observer
                    const _plots = this.plots.get(elem) || [];

                    _plots.push(u)

                    this.plots.set(elem, _plots);
                });
            });
            this.icinga.logger.debug('perfdatagraphs', 'finish renderCharts', this.plots);
        }

        /**
         * ensureArray ensures the given object is an Array.
         * It will transform Objects if possible.
         * A dirty PHP 8.0 hack since I sometimes used SplFixedArray.
         * Can be removed once PHP 8.0 is ancient history.
         */
        ensureArray(obj) {
            if (typeof obj === 'object' && !Array.isArray(obj)) {
                return Object.values(obj);
            }

            return obj;
        }

        /**
         * Translate a given rgb() string into rgba().
         * Used for the fill of the chart.
         */
        ensureRgba(color, alpha=1) {
            // If already in rgba just return.
            if (color.startsWith('rgba')) {
                return color;
            }

            // Try to match the rgb format and return with alpha.
            const rgbMatch = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (!rgbMatch) {
                // If we match nothing return what was given just to be safe.
                return color;
            }

            // Add the given alpha.
            const [_, r, g, b] = rgbMatch;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        /**
         * formatNumber returns exponential format for too low/high numbers, so that the y-axis does not grow endlessly.
         */
        formatNumber(n)
        {
            if (n == 0) {
                return 0;
            }

            const str = n.toString();

            // If the output would be too long we change to exponential
            if (str.length >= 20) {
                return n.toExponential();
            }

            return str;
        }

        /**
         * formatPercentage returns the given value with % attached.
         */
        formatPercentage(n)
        {
            if (n == 0) {
                return "0.00%";
            }

            let value = n;
            return `${value.toFixed(2)}%`;
        }

        /**
         * formatSeconds turns the number of seconds into a time format
         * TODO: Maybe we should have a helper function that calculates the
         * required number of decimals. Because fixed decimals may not
         * help to distinquish certains values.
         */
        formatTimeSeconds(n)
        {
            if (n == 0) {
                return "0 s";
            }

            let value = n;

            if (Math.abs(n) < 0.000001) {
                value = n * 1e9;
                return `${value.toFixed(2)} ns`;
            }

            if (Math.abs(n) < 0.001) {
                value = n * 1e6;
                return `${value.toFixed(2)} µs`;
            }

            if (Math.abs(n) < 1) {
                value = n * 1e3;
                return `${value.toFixed(2)} ms`;
            }

            // TODO: Plurals could maybe be conditional
            if (Math.abs(n) < 60) {
                return `${value.toFixed(2)} s`;
            }  else if (Math.abs(n) < 3600) {
                value = n / 60;
                return `${value.toFixed(2)} mins`;
            } else if (Math.abs(n) < 86400) {
                value = n / 3600;
                return `${value.toFixed(2)} hours`;
            } else if (Math.abs(n) < 604800) {
                value = n / 86400;
                return `${value.toFixed(2)} days`;
            } else if (Math.abs(n) < 31536000) {
                value = n / 604800;
                return `${value.toFixed(2)} weeks`;
            }
        }

        /**
         * formatBytesSI turns a number of bytes into their SI format.
         */
        formatBytesSI(n)
        {
            if (n === 0) {
                return "0 bytes";
            }

            const k = 1000;
            const units = ["bytes", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
            const i = Math.floor(Math.log(n) / Math.log(k));

            const num = n / Math.pow(k, i);
            const value = num.toFixed(2);

            return `${value} ${units[i]}`;
        }
    }

    Icinga.Behaviors.Perfdatagraphs = Perfdatagraphs;

}(Icinga, jQuery));
