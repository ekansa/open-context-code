/*! 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * Core functions for the timemap.js library.
 * Timemap.js is intended to sync a SIMILE Timeline with a Google Map.
 * Dependencies: Google Maps API v2, SIMILE Timeline v1.2 - 2.3.1
 * Thanks to Jorn Clausen (http://www.oe-files.de) for initial concept and code. 
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

// globals - for JSLint
/*global GBrowserIsCompatible, GLargeMapControl, GLatLngBounds, GMap2       */ 
/*global GMapTypeControl, GDownloadUrl, GEvent, GGroundOverlay, GIcon       */
/*global GMarker, GPolygon, GPolyline, GSize, GLatLng, G_DEFAULT_ICON       */
/*global G_DEFAULT_MAP_TYPES, G_NORMAL_MAP, G_PHYSICAL_MAP, G_HYBRID_MAP    */
/*global G_MOON_VISIBLE_MAP, G_SKY_VISIBLE_MAP, G_SATELLITE_MAP, Timeline   */

// A couple of aliases to save a few bytes
var DT = Timeline.DateTime, 
// Google icon path
GIP = "http://www.google.com/intl/en_us/mapfiles/ms/icons/";

/*----------------------------------------------------------------------------
 * TimeMap Class
 *---------------------------------------------------------------------------*/
 
/**
 * @class
 * The TimeMap object holds references to timeline, map, and datasets.
 * This will create the visible map, but not the timeline, which must be initialized separately.
 *
 * @constructor
 * @param {element} tElement     The timeline element.
 * @param {element} mElement     The map element.
 * @param {Object} options       A container for optional arguments:<pre>
 *   {Boolean} syncBands            Whether to synchronize all bands in timeline
 *   {GLatLng} mapCenter            Point for map center
 *   {Number} mapZoom               Intial map zoom level
 *   {GMapType/String} mapType      The maptype for the map
 *   {Array} mapTypes               The set of maptypes available for the map
 *   {Function/String} mapFilter    How to hide/show map items depending on timeline state;
                                    options: "hidePastFuture", "showMomentOnly", or function
 *   {Boolean} showMapTypeCtrl      Whether to display the map type control
 *   {Boolean} showMapCtrl          Whether to show map navigation control
 *   {Boolean} centerMapOnItems     Whether to center and zoom the map based on loaded item positions
 *   {Function} openInfoWindow      Function redefining how info window opens
 *   {Function} closeInfoWindow     Function redefining how info window closes
 * </pre>
 */

function TimeMap(tElement, mElement, options) {
    // save elements
    
    /**
     * Map element
     * @type DOM Element
     */
    this.mElement = mElement;
    /**
     * Timeline element
     * @type DOM Element
     */
    this.tElement = tElement;
    
    /** 
     * Map of datasets 
     * @type Object 
     */
    this.datasets = {};
    /**
     * Filter chains for this timemap 
     * @type Object
     */
    this.filters = {};
    /** 
     * Bounds of the map 
     * @type GLatLngBounds
     */
    this.mapBounds = new GLatLngBounds();
    
    // set defaults for options
    
    /** 
     * Container for optional settings passed in the "options" parameter
     * @type Object
     */
    this.opts = options || {};   // make sure the options object isn't null
    // allow map types to be specified by key
    if (typeof(options.mapType) == 'string') {
        options.mapType = TimeMap.mapTypes[options.mapType];
    }
    // allow map filters to be specified by key
    if (typeof(options.mapFilter) == 'string') {
        options.mapFilter = TimeMap.filters[options.mapFilter];
    }
    // these options only needed for map initialization
    var mapCenter =        options.mapCenter || new GLatLng(0,0),
        mapZoom =          options.mapZoom || 0,
        mapType =          options.mapType || G_PHYSICAL_MAP,
        mapTypes =         options.mapTypes || [G_NORMAL_MAP, G_SATELLITE_MAP, G_PHYSICAL_MAP],
        showMapTypeCtrl =  ('showMapTypeCtrl' in options) ? options.showMapTypeCtrl : true,
        showMapCtrl =      ('showMapCtrl' in options) ? options.showMapCtrl : true;
    
    // these options need to be saved for later
    this.opts.syncBands =        ('syncBands' in options) ? options.syncBands : true;
    this.opts.mapFilter =        options.mapFilter || TimeMap.filters.hidePastFuture;
    this.opts.centerOnItems =    ('centerMapOnItems' in options) ? options.centerMapOnItems : true;
    this.opts.theme =            TimeMapTheme.create(options.theme, options);
    
    // initialize map
    if (GBrowserIsCompatible()) {
        /** 
         * The associated map object 
         * @type GMap2
         */
        this.map = new GMap2(this.mElement);
        var map = this.map;
        if (showMapCtrl) {
            map.addControl(new GLargeMapControl());
        }
        if (showMapTypeCtrl) {
            map.addControl(new GMapTypeControl());
        }
        // drop all existing types
        var i;
        for (i=G_DEFAULT_MAP_TYPES.length-1; i>0; i--) {
            map.removeMapType(G_DEFAULT_MAP_TYPES[i]);
        }
        // you can't remove the last maptype, so add a new one first
        map.addMapType(mapTypes[0]);
        map.removeMapType(G_DEFAULT_MAP_TYPES[0]);
        // add the rest of the new types
        for (i=1; i<mapTypes.length; i++) {
            map.addMapType(mapTypes[i]);
        }
        map.enableDoubleClickZoom();
        map.enableScrollWheelZoom();
        map.enableContinuousZoom();
        // initialize map center and zoom
        map.setCenter(mapCenter, mapZoom);
        // must be called after setCenter, for reasons unclear
        map.setMapType(mapType);
    }
}

/**
 * Current library version.
 * @type String
 */
TimeMap.version = "1.5";

/**
 * Intializes a TimeMap.
 *
 * <p>This is an attempt to create a general initialization script that will
 * work in most cases. If you need a more complex initialization, write your
 * own script instead of using this one.</p>
 *
 * <p>The idea here is to throw all of the standard intialization settings into
 * a large object and then pass it to the TimeMap.init() function. The full
 * data format is outlined below, but if you leave elements off the script 
 * will use default settings instead.</p>
 *
 * <p>Call TimeMap.init() inside of an onLoad() function (or a jQuery 
 * $.(document).ready() function, or whatever you prefer). See the examples 
 * for usage.</p>
 *
 * @param {Object} config   Full set of configuration options.
 *                          See examples/timemapinit_usage.js for format.
 * @return {TimeMap}        The initialized TimeMap object, for future reference
 */
TimeMap.init = function(config) {
    
    // check required elements
    if (!('mapId' in config) || !config.mapId) {
        throw "TimeMap.init: No id for map";
    }
    if (!('timelineId' in config) || !config.timelineId) {
        throw "TimeMap.init: No id for timeline";
    }
    
    // set defaults
    config = config || {}; // make sure the config object isn't null
    config.options = config.options || {};
    config.datasets = config.datasets || [];
    config.bandInfo = config.bandInfo || false;
    config.scrollTo = config.scrollTo || "earliest";
    if (!config.bandInfo && !config.bands) {
        var intervals = config.bandIntervals || 
            config.options.bandIntervals ||
            [DT.WEEK, DT.MONTH];
        // allow intervals to be specified by key
        if (typeof(intervals) == 'string') {
            intervals = TimeMap.intervals[intervals];
        }
        // save for later reference
        config.options.bandIntervals = intervals;
        // make default band info
        config.bandInfo = [
    		{
                width:          "80%", 
                intervalUnit:   intervals[0], 
                intervalPixels: 70
            },
            {
                width:          "20%", 
                intervalUnit:   intervals[1], 
                intervalPixels: 100,
                showEventText:  false,
                overview: true,
                trackHeight:    0.4,
                trackGap:       0.2
            }
        ];
    }
    
    // create the TimeMap object
    var tm = new TimeMap(
  		document.getElementById(config.timelineId), 
		document.getElementById(config.mapId),
		config.options);
    
    // create the dataset objects
    var datasets = [], x, ds, dsOptions, dsId;
    for (x=0; x < config.datasets.length; x++) {
        ds = config.datasets[x];
        dsOptions = ds.options || {};
        dsOptions.title = ds.title || '';
        dsOptions.theme = ds.theme;
        dsOptions.dateParser = ds.dateParser;
        dsId = ds.id || "ds" + x;
        datasets[x] = tm.createDataset(dsId, dsOptions);
        if (x > 0) {
            // set all to the same eventSource
            datasets[x].eventSource = datasets[0].eventSource;
        }
    }
    // add a pointer to the eventSource in the TimeMap
    tm.eventSource = datasets[0].eventSource;
    
    // set up timeline bands
    var bands = [];
    // ensure there's at least an empty eventSource
    var eventSource = (datasets[0] && datasets[0].eventSource) || new Timeline.DefaultEventSource();
    // check for pre-initialized bands (manually created with Timeline.createBandInfo())
    if (config.bands) {
        bands = config.bands;
        // substitute dataset event source
        for (x=0; x < bands.length; x++) {
            // assume that these have been set up like "normal" Timeline bands:
            // with an empty event source if events are desired, and null otherwise
            if (bands[x].eventSource !== null) {
                bands[x].eventSource = eventSource;
            }
        }
    }
    // otherwise, make bands from band info
    else {
        for (x=0; x < config.bandInfo.length; x++) {
            var bandInfo = config.bandInfo[x];
            // if eventSource is explicitly set to null or false, ignore
            if (!(('eventSource' in bandInfo) && !bandInfo.eventSource)) {
                bandInfo.eventSource = eventSource;
            }
            else {
                bandInfo.eventSource = null;
            }
            bands[x] = Timeline.createBandInfo(bandInfo);
            if (x > 0 && TimeMap.util.TimelineVersion() == "1.2") {
                // set all to the same layout
                bands[x].eventPainter.setLayout(bands[0].eventPainter.getLayout()); 
            }
        }
    }
    // initialize timeline
    tm.initTimeline(bands);
    
    // initialize load manager
    var loadManager = TimeMap.loadManager;
    loadManager.init(tm, config.datasets.length, config);
    
    // load data!
    for (x=0; x < config.datasets.length; x++) {
        (function(x) { // deal with closure issues
            var data = config.datasets[x], options, type, callback, loaderClass, loader;
            // support some older syntax
            options = data.options || data.data || {};
            type = data.type || options.type;
            callback = function() { loadManager.increment() };
            // get loader class
            loaderClass = (typeof(type) == 'string') ? TimeMap.loaders[type] : type;
            // load with appropriate loader
            loader = new loaderClass(options);
            loader.load(datasets[x], callback);
        })(x);
    }
    // return timemap object for later manipulation
    return tm;
};

// for backwards compatibility
var timemapInit = TimeMap.init;

/**
 * @class Static singleton for managing multiple asynchronous loads
 */
TimeMap.loadManager = new function() {
    
    /**
     * Initialize (or reset) the load manager
     *
     * @param {TimeMap} tm          TimeMap instance
     * @param {int} target     Number of datasets we're loading
     * @param {Object} options      Container for optional settings:<pre>
     *   {Function} dataLoadedFunction      Custom function replacing default completion function;
     *                                      should take one parameter, the TimeMap object
     *   {String/Date} scrollTo             Where to scroll the timeline when load is complete
     *                                      Options: "earliest", "latest", "now", date string, Date
     *   {Function} dataDisplayedFunction   Custom function to fire once data is loaded and displayed;
     *                                      should take one parameter, the TimeMap object
     * </pre>
     */
    this.init = function(tm, target, config) {
        this.count = 0;
        this.tm = tm;
        this.target = target;
        this.opts = config || {};
    };
    
    /**
     * Increment the count of loaded datasets
     */
    this.increment = function() {
        this.count++;
        if (this.count >= this.target) {
            this.complete();
        }
    };
    
    /**
     * Function to fire when all loads are complete. 
     * Default behavior is to scroll to a given date (if provided) and
     * layout the timeline.
     */
    this.complete = function() {
        var tm = this.tm;
        // custom function including timeline scrolling and layout
        var func = this.opts.dataLoadedFunction;
        if (func) {
            func(tm);
        } else {
            var d = new Date();
            var eventSource = this.tm.eventSource;
            var scrollTo = this.opts.scrollTo;
            // make sure there are events to scroll to
            if (scrollTo && eventSource.getCount() > 0) {
                switch (scrollTo) {
                    case "now":
                        break;
                    case "earliest":
                        d = eventSource.getEarliestDate();
                        break;
                    case "latest":
                        d = eventSource.getLatestDate();
                        break;
                    default:
                        // assume it's a date, try to parse
                        if (typeof(scrollTo) == 'string') {
                            scrollTo = TimeMapDataset.hybridParser(scrollTo);
                        }
                        // either the parse worked, or it was a date to begin with
                        if (scrollTo.constructor == Date) d = scrollTo;
                }
                this.tm.timeline.getBand(0).setCenterVisibleDate(d);
            }
            this.tm.timeline.layout();
            // custom function to be called when data is loaded
            func = this.opts.dataDisplayedFunction;
            if (func) {
                func(tm);
            }
        }
    };
};

/**
 * @namespace
 * Namespace for different data loader functions.
 * New loaders should add their factories or constructors to this object; loader
 * functions are passed an object with parameters in TimeMap.init().
 */
TimeMap.loaders = {};

/**
 * @class
 * Basic loader class, for pre-loaded data. 
 * Other types of loaders should take the same parameter.
 *
 * @constructor
 * @param {Object} options          All options for the loader:<pre>
 *   {Array} data                       Array of items to load
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 */
TimeMap.loaders.basic = function(options) {
    // get standard functions
    TimeMap.loaders.mixin(this, options);
    // allow "value" for backwards compatibility
    this.data = options.items || options.value || [];
}

/**
 * New loaders should implement a load function with the same parameters.
 *
 * @param {TimeMapDataset} dataset  Dataset to load data into
 * @param {Function} callback       Function to call once data is loaded
 */
TimeMap.loaders.basic.prototype.load = function(dataset, callback) {
    // preload
    var items = this.preload(this.data);
    // load
    dataset.loadItems(items, this.transform);
    // run callback
    callback();
}

/**
 * @class
 * Generic class for loading remote data with a custom parser function
 *
 * @constructor
 * @param {Object} options          All options for the loader:<pre>
 *   {Array} url                        URL of file to load (NB: must be local address)
 *   {Function} parserFunction          Parser function to turn data into JavaScript array
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 */
TimeMap.loaders.remote = function(options) {
    // get standard functions
    TimeMap.loaders.mixin(this, options);
    // get URL to load
    this.url = options.url;
}

/**
 * Remote load function.
 *
 * @param {TimeMapDataset} dataset  Dataset to load data into
 * @param {Function} callback       Function to call once data is loaded
 */
TimeMap.loaders.remote.prototype.load = function(dataset, callback) {
    var loader = this;
    // get items
    GDownloadUrl(this.url, function(result) {
        // parse
        var items = loader.parse(result);
        // load
        items = loader.preload(items);
        dataset.loadItems(items, loader.transform);
        // callback
        callback();
    });
}

/**
 * Save a few lines of code by adding standard functions
 *
 * @param {Function} loader         Loader to add functions to
 * @param {Object} options          Options for the loader:<pre>
 *   {Function} parserFunction          Parser function to turn data into JavaScript array
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 */
TimeMap.loaders.mixin = function(loader, options) {
    // set preload and transform functions
    var dummy = function(data) { return data; };
    loader.parse = options.parserFunction || dummy;
    loader.preload = options.preloadFunction || dummy;
    loader.transform = options.transformFunction || dummy;
}  

/**
 * Map of common timeline intervals. Add custom intervals here if you
 * want to refer to them by key rather than as literals.
 * @type Object
 */
TimeMap.intervals = {
    'sec': [DT.SECOND, DT.MINUTE],
    'min': [DT.MINUTE, DT.HOUR],
    'hr': [DT.HOUR, DT.DAY],
    'day': [DT.DAY, DT.WEEK],
    'wk': [DT.WEEK, DT.MONTH],
    'mon': [DT.MONTH, DT.YEAR],
    'yr': [DT.YEAR, DT.DECADE],
    'dec': [DT.DECADE, DT.CENTURY]
};

/**
 * Map of Google map types. Using keys rather than literals allows
 * for serialization of the map type.
 * @type Object
 */
TimeMap.mapTypes = {
    'normal':G_NORMAL_MAP, 
    'satellite':G_SATELLITE_MAP, 
    'hybrid':G_HYBRID_MAP, 
    'physical':G_PHYSICAL_MAP, 
    'moon':G_MOON_VISIBLE_MAP, 
    'sky':G_SKY_VISIBLE_MAP
};

/**
 * Create an empty dataset object and add it to the timemap
 *
 * @param {String} id           The id of the dataset
 * @param {Object} options      A container for optional arguments for dataset constructor
 * @return {TimeMapDataset}     The new dataset object    
 */
TimeMap.prototype.createDataset = function(id, options) {
    options = options || {}; // make sure the options object isn't null
    if (!("title" in options)) {
        options.title = id;
    }
    var dataset = new TimeMapDataset(this, options);
    this.datasets[id] = dataset;
    // add event listener
    if (this.opts.centerOnItems) {
        var tm = this;
        GEvent.addListener(dataset, 'itemsloaded', function() {
            var map = tm.map, bounds = tm.mapBounds;
            // determine the zoom level from the bounds
            map.setZoom(map.getBoundsZoomLevel(bounds));
            // determine the center from the bounds
            map.setCenter(bounds.getCenter());
        });
    }
    return dataset;
};

/**
 * Run a function on each dataset in the timemap. This is the preferred
 * iteration method, as it allows for future iterator options.
 *
 * @param {Function} f    The function to run
 */
TimeMap.prototype.each = function(f) {
    for (var id in this.datasets) {
        if (this.datasets.hasOwnProperty(id)) {
            f(this.datasets[id]);
        }
    }
};

/**
 * Initialize the timeline - this must happen separately to allow full control of 
 * timeline properties.
 *
 * @param {BandInfo Array} bands    Array of band information objects for timeline
 */
TimeMap.prototype.initTimeline = function(bands) {
    
    // synchronize & highlight timeline bands
    for (var x=1; x < bands.length; x++) {
        if (this.opts.syncBands) {
            bands[x].syncWith = (x-1);
        }
        bands[x].highlight = true;
    }
    
    /** 
     * The associated timeline object 
     * @type Timeline 
     */
    this.timeline = Timeline.create(this.tElement, bands);
    
    // set event listeners
    var tm = this;
    // update map on timeline scroll
    this.timeline.getBand(0).addOnScrollListener(function() {
        tm.filter("map");
    });

    // hijack timeline popup window to open info window
    var painter = this.timeline.getBand(0).getEventPainter().constructor;
    painter.prototype._showBubble = function(x, y, evt) {
        evt.item.openInfoWindow();
    };
    
    // filter chain for map placemarks
    this.addFilterChain("map", 
        function(item) {
            item.showPlacemark();
        },
        function(item) {
            item.hidePlacemark();
        }
    );
    
    // filter: hide when item is hidden
    this.addFilter("map", function(item) {
        return item.visible;
    });
    // filter: hide when dataset is hidden
    this.addFilter("map", function(item) {
        return item.dataset.visible;
    });
    
    // filter: hide map items depending on timeline state
    this.addFilter("map", this.opts.mapFilter);
    
    // filter chain for timeline events
    this.addFilterChain("timeline", 
        function(item) {
            item.showEvent();
        },
        function(item) {
            item.hideEvent();
        }
    );
    
    // filter: hide when item is hidden
    this.addFilter("timeline", function(item) {
        return item.visible;
    });
    // filter: hide when dataset is hidden
    this.addFilter("timeline", function(item) {
        return item.dataset.visible;
    });
    
    // add callback for window resize
    var resizeTimerID = null;
    var oTimeline = this.timeline;
    window.onresize = function() {
        if (resizeTimerID === null) {
            resizeTimerID = window.setTimeout(function() {
                resizeTimerID = null;
                oTimeline.layout();
            }, 500);
        }
    };
};

/**
 * Update items, hiding or showing according to filters
 *
 * @param {String} fid      Filter chain to update on
 */
TimeMap.prototype.filter = function(fid) {
    var filters = this.filters[fid];
    // if no filters exist, forget it
    if (!filters || !filters.chain || filters.chain.length === 0) {
        return;
    }
    // run items through filter
    this.each(function(ds) {
        ds.each(function(item) {
            var done = false;
            F_LOOP: while (!done) { 
                for (var i = filters.chain.length - 1; i >= 0; i--) {
                    if (!filters.chain[i](item)) {
                        // false condition
                        filters.off(item);
                        break F_LOOP;
                    }
                }
                // true condition
                filters.on(item);
                done = true;
            }
        });
    });
};

/**
 * Add a new filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} fon    Function to run on an item if filter is true
 * @param {Function} foff   Function to run on an item if filter is false
 */
TimeMap.prototype.addFilterChain = function(fid, fon, foff) {
    this.filters[fid] = {
        chain:[],
        on: fon,
        off: foff
    };
};

/**
 * Remove a filter chain
 *
 * @param {String} fid      Id of the filter chain
 */
TimeMap.prototype.removeFilterChain = function(fid) {
    this.filters[fid] = null;
};

/**
 * Add a function to a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} f      Function to add
 */
TimeMap.prototype.addFilter = function(fid, f) {
    if (this.filters[fid] && this.filters[fid].chain) {
        this.filters[fid].chain.push(f);
    }
};

/**
 * Remove a function from a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * XXX: Support index here
 */
TimeMap.prototype.removeFilter = function(fid) {
    if (this.filters[fid] && this.filters[fid].chain) {
        this.filters[fid].chain.pop();
    }
};

/**
 * @namespace
 * Namespace for different filter functions. Adding new filters to this
 * object allows them to be specified by string name.
 */
TimeMap.filters = {};

/**
 * Static filter function: Hide items not in the visible area of the timeline.
 *
 * @param {TimeMapItem} item    Item to test for filter
 * @return {Boolean}            Whether to show the item
 */
TimeMap.filters.hidePastFuture = function(item) {
    var topband = item.dataset.timemap.timeline.getBand(0);
    var maxVisibleDate = topband.getMaxVisibleDate().getTime();
    var minVisibleDate = topband.getMinVisibleDate().getTime();
    if (item.event !== null) {
        var itemStart = item.event.getStart().getTime();
        var itemEnd = item.event.getEnd().getTime();
        // hide items in the future
        if (itemStart > maxVisibleDate) {
            return false;
        } 
        // hide items in the past
        else if (itemEnd < minVisibleDate || 
            (item.event.isInstant() && itemStart < minVisibleDate)) {
            return false;
        }
    }
    return true;
};

/**
 * Static filter function: Hide items not present at the exact
 * center date of the timeline (will only work for duration events).
 *
 * @param {TimeMapItem} item    Item to test for filter
 * @return {Boolean}            Whether to show the item
 */
TimeMap.filters.showMomentOnly = function(item) {
    var topband = item.dataset.timemap.timeline.getBand(0);
    var momentDate = topband.getCenterVisibleDate().getTime();
    if (item.event !== null) {
        var itemStart = item.event.getStart().getTime();
        var itemEnd = item.event.getEnd().getTime();
        // hide items in the future
        if (itemStart > momentDate) {
            return false;
        } 
        // hide items in the past
        else if (itemEnd < momentDate || 
            (item.event.isInstant() && itemStart < momentDate)) {
            return false;
        }
    }
    return true;
};

/*----------------------------------------------------------------------------
 * TimeMapDataset Class
 *---------------------------------------------------------------------------*/

/**
 * @class 
 * The TimeMapDataset object holds an array of items and dataset-level
 * options and settings, including visual themes.
 *
 * @constructor
 * @param {TimeMap} timemap         Reference to the timemap object
 * @param {Object} options          Object holding optional arguments:<pre>
 *   {String} id                        Key for this dataset in the datasets map
 *   {String} title                     Title of the dataset (for the legend)
 *   {String or theme object} theme     Theme settings.
 *   {String or Function} dateParser    Function to replace default date parser.
 *   {Function} openInfoWindow          Function redefining how info window opens
 *   {Function} closeInfoWindow         Function redefining how info window closes
 * </pre>
 */
function TimeMapDataset(timemap, options) {
    /** 
     * Reference to parent TimeMap
     * @type TimeMap
     */
    this.timemap = timemap;
    /** 
     * EventSource for timeline events
     * @type Timeline.EventSource
     */
    this.eventSource = new Timeline.DefaultEventSource();
    /** 
     * Array of child TimeMapItems
     * @type Array
     */
    this.items = [];
    /** 
     * Whether the dataset is visible
     * @type Boolean
     */
    this.visible = true;
    
    // set defaults for options
    
    /** 
     * Container for optional settings passed in the "options" parameter
     * @type Object
     */
    this.opts = options || {}; // make sure the options object isn't null
    this.opts.title = options.title || "";
    
    // get theme
    var tmtheme = this.timemap.opts.theme,
        theme = options.theme || tmtheme;
    // event icon path overrides custom themes
    options.eventIconPath = options.eventIconPath || tmtheme.eventIconPath;
    // configure theme
    this.opts.theme = TimeMapTheme.create(theme, options);
    
    // allow for other data parsers (e.g. Gregorgian) by key or function
    if (typeof(options.dateParser) == "string") { 
        options.dateParser = TimeMapDataset.dateParsers[options.dateParser];
    }
    this.opts.dateParser = options.dateParser || TimeMapDataset.hybridParser;
    
    /**
     * Return an array of this dataset's items
     *
     * @param {int} index       Optional index of single item to return
     * @return {TimeMapItem}    Single item, or array of all items if no index was supplied
     */
    this.getItems = function(index) {
        if (index !== undefined) {
            if (index < this.items.length) {
                return this.items[index];
            }
            else {
                return null;
            }
        }
        return this.items; 
    };
    
    /**
     * Return the title of the dataset
     * 
     * @return {String}     Dataset title
     */
    this.getTitle = function() { return this.opts.title; };
}

/**
 * Better Timeline Gregorian parser... shouldn't be necessary :(.
 * Gregorian dates are years with "BC" or "AD"
 *
 * @param {String} s    String to parse into a Date object
 * @return {Date}       Parsed date or null
 */
TimeMapDataset.gregorianParser = function(s) {
    if (!s) {
        return null;
    } else if (s instanceof Date) {
        return s;
    }
    // look for BC
    var bc = Boolean(s.match(/b\.?c\.?/i));
    // parse - parseInt will stop at non-number characters
    var year = parseInt(s);
    // look for success
    if (!isNaN(year)) {
        // deal with BC
        if (bc) year = 1 - year;
        // make Date and return
        var d = new Date(0);
        d.setUTCFullYear(year);
        return d;
    }
    else {
        return null;
    }
};

/**
 * Parse date strings with a series of date parser functions, until one works. 
 * In order:
 * <ol>
 *  <li>Date.parse() (so Date.js should work here, if it works with Timeline...)</li>
 *  <li>Gregorian parser</li>
 *  <li>The Timeline ISO 8601 parser</li>
 * </ol>
 *
 * @param {String} s    String to parse into a Date object
 * @return {Date}       Parsed date or null
 */
TimeMapDataset.hybridParser = function(s) {
    // try native date parse
    var d = new Date(Date.parse(s));
    if (isNaN(d)) {
        // look for Gregorian dates
        if (s.match(/^-?\d{1,6} ?(a\.?d\.?|b\.?c\.?e?\.?|c\.?e\.?)?$/i)) {
            d = TimeMapDataset.gregorianParser(s);
        }
        // try ISO 8601 parse
        else {
            try {
                d = DT.parseIso8601DateTime(s);
            } catch(e) {
                d = null;
            }
        }
    }
    // d should be a date or null
    return d;
};

/**
 * Map of supported date parser functions. Add custom date parsers here if you
 * want to refer to them by key rather than as a function name.
 * @type Object
 */
TimeMapDataset.dateParsers = {
    'hybrid': TimeMapDataset.hybridParser,
    'iso8601': DT.parseIso8601DateTime,
    'gregorian': TimeMapDataset.gregorianParser
};

/**
 * Run a function on each item in the dataset. This is the preferred
 * iteration method, as it allows for future iterator options.
 *
 * @param {Function} f    The function to run
 */
TimeMapDataset.prototype.each = function(f) {
    for (var x=0; x < this.items.length; x++) {
        f(this.items[x]);
    }
};

/**
 * Add an array of items to the map and timeline. 
 * Each item has both a timeline event and a map placemark.
 *
 * @param {Object} data             Data to be loaded. See loadItem() for the format.
 * @param {Function} transform      If data is not in the above format, transformation function to make it so
 * @see TimeMapDataset#loadItem
 */
TimeMapDataset.prototype.loadItems = function(data, transform) {
    for (var x=0; x < data.length; x++) {
        this.loadItem(data[x], transform);
    }
    GEvent.trigger(this, 'itemsloaded');
};

/**
 * Add one item to map and timeline. 
 * Each item has both a timeline event and a map placemark.
 *
 * @param {Object} data         Data to be loaded, in the following format: <pre>
 *      {String} title              Title of the item (visible on timeline)
 *      {DateTime} start            Start time of the event on the timeline
 *      {DateTime} end              End time of the event on the timeline (duration events only)
 *      {Object} point              Data for a single-point placemark: 
 *          {Float} lat                 Latitude of map marker
 *          {Float} lon                 Longitude of map marker
 *      {Array of points} polyline  Data for a polyline placemark, in format above
 *      {Array of points} polygon   Data for a polygon placemark, in format above
 *      {Object} overlay            Data for a ground overlay:
 *          {String} image              URL of image to overlay
 *          {Float} north               Northern latitude of the overlay
 *          {Float} south               Southern latitude of the overlay
 *          {Float} east                Eastern longitude of the overlay
 *          {Float} west                Western longitude of the overlay
 *      {Object} options            Optional arguments to be passed to the TimeMapItem (@see TimeMapItem)
 * </pre>
 * @param {Function} transform  If data is not in the above format, transformation function to make it so
 * @return {TimeMapItem}        The created item (for convenience, as it's already be added)
 * @see TimeMapItem
 */
TimeMapDataset.prototype.loadItem = function(data, transform) {
    // apply transformation, if any
    if (transform !== undefined) {
        data = transform(data);
    }
    // transform functions can return a null value to skip a datum in the set
    if (data === null) {
        return;
    }
    
    var options = data.options || {},
        tm = this.timemap,
        dstheme = this.opts.theme,
        theme = options.theme || dstheme;
    
    // event icon path overrides custom themes
    options.eventIconPath = options.eventIconPath || dstheme.eventIconPath;
    // get configured theme
    theme = TimeMapTheme.create(theme, options);
    
    // create timeline event
    var parser = this.opts.dateParser, start = data.start, end = data.end, instant;
    start = (start === undefined||start === "") ? null : parser(start);
    end = (end === undefined||end === "") ? null : parser(end);
    instant = (end === undefined);
    var eventIcon = theme.eventIcon,
        title = data.title,
        // allow event-less placemarks - these will be always present on map
        event = null;
    if (start !== null) {
        var eventClass = Timeline.DefaultEventSource.Event;
        if (TimeMap.util.TimelineVersion() == "1.2") {
            // attributes by parameter
            event = new eventClass(start, end, null, null,
                instant, title, null, null, null, eventIcon, theme.eventColor, 
                theme.eventTextColor);
        } else {
            var textColor = theme.eventTextColor;
            if (!textColor) {
                // tweak to show old-style events
                textColor = (theme.classicTape && !instant) ? '#FFFFFF' : '#000000';
            }
            // attributes in object
            event = new eventClass({
                "start": start,
                "end": end,
                "instant": instant,
                "text": title,
                "icon": eventIcon,
                "color": theme.eventColor,
                "textColor": textColor
            });
        }
    }
    
    // set the icon, if any, outside the closure
    var markerIcon = theme.icon,
        bounds = tm.mapBounds; // save some bytes
    
    
    // internal function: create map placemark
    // takes a data object (could be full data, could be just placemark)
    // returns an object with {placemark, type, point}
    var createPlacemark = function(pdata) {
        var placemark = null, type = "", point = null;
        // point placemark
        if ("point" in pdata) {
            point = new GLatLng(
                parseFloat(pdata.point.lat), 
                parseFloat(pdata.point.lon)
            );
            // add point to visible map bounds
            if (tm.opts.centerOnItems) {
                bounds.extend(point);
            }
            placemark = new GMarker(point, { icon: markerIcon });
            type = "marker";
            point = placemark.getLatLng();
        }
        // polyline and polygon placemarks
        else if ("polyline" in pdata || "polygon" in pdata) {
            var points = [], line;
            if ("polyline" in pdata) {
                line = pdata.polyline;
            } else {
                line = pdata.polygon;
            }
            for (var x=0; x<line.length; x++) {
                point = new GLatLng(
                    parseFloat(line[x].lat), 
                    parseFloat(line[x].lon)
                );
                points.push(point);
                // add point to visible map bounds
                if (tm.opts.centerOnItems) {
                    bounds.extend(point);
                }
            }
            if ("polyline" in pdata) {
                placemark = new GPolyline(points, 
                                          theme.lineColor, 
                                          theme.lineWeight,
                                          theme.lineOpacity);
                type = "polyline";
                point = placemark.getVertex(Math.floor(placemark.getVertexCount()/2));
            } else {
                placemark = new GPolygon(points, 
                                         theme.polygonLineColor, 
                                         theme.polygonLineWeight,
                                         theme.polygonLineOpacity,
                                         theme.fillColor,
                                         theme.fillOpacity);
                type = "polygon";
                point = placemark.getBounds().getCenter();
            }
        } 
        // ground overlay placemark
        else if ("overlay" in pdata) {
            var sw = new GLatLng(
                parseFloat(pdata.overlay.south), 
                parseFloat(pdata.overlay.west)
            );
            var ne = new GLatLng(
                parseFloat(pdata.overlay.north), 
                parseFloat(pdata.overlay.east)
            );
            // add to visible bounds
            if (tm.opts.centerOnItems) {
                bounds.extend(sw);
                bounds.extend(ne);
            }
            // create overlay
            var overlayBounds = new GLatLngBounds(sw, ne);
            placemark = new GGroundOverlay(pdata.overlay.image, overlayBounds);
            type = "overlay";
            point = overlayBounds.getCenter();
        }
        return {
            "placemark": placemark,
            "type": type,
            "point": point
        };
    };
    
    // create placemark or placemarks
    var placemark = [], pdataArr = [], pdata = null, type = "", point = null, i;
    // array of placemark objects
    if ("placemarks" in data) {
        pdataArr = data.placemarks;
    } else {
        // we have one or more single placemarks
        var types = ["point", "polyline", "polygon", "overlay"];
        for (i=0; i<types.length; i++) {
            if (types[i] in data) {
                pdata = {};
                pdata[types[i]] = data[types[i]];
                pdataArr.push(pdata);
            }
        }
    }
    if (pdataArr) {
        for (i=0; i<pdataArr.length; i++) {
            // create the placemark
            var p = createPlacemark(pdataArr[i]);
            // take the first point and type as a default
            point = point || p.point;
            type = type || p.type;
            placemark.push(p.placemark);
        }
    }
    // override type for arrays
    if (placemark.length > 1) {
        type = "array";
    }
    
    options.title = title;
    options.type = type || "none";
    options.theme = theme;
    // check for custom infoPoint and convert to GLatLng
    if (options.infoPoint) {
        options.infoPoint = new GLatLng(
            parseFloat(options.infoPoint.lat), 
            parseFloat(options.infoPoint.lon)
        );
    } else {
        options.infoPoint = point;
    }
    
    // create item and cross-references
    var item = new TimeMapItem(placemark, event, this, options);
    // add event if it exists
    if (event !== null) {
        event.item = item;
        this.eventSource.add(event);
    }
    // add placemark(s) if any exist
    if (placemark.length > 0) {
        for (i=0; i<placemark.length; i++) {
            placemark[i].item = item;
            // add listener to make placemark open when event is clicked
            GEvent.addListener(placemark[i], "click", function() {
                item.openInfoWindow();
            });
            // add placemark and event to map and timeline
            tm.map.addOverlay(placemark[i]);
            // hide placemarks until the next refresh
            placemark[i].hide();
        }
    }
    // add the item to the dataset
    this.items.push(item);
    // return the item object
    return item;
};

/*----------------------------------------------------------------------------
 * TimeMapTheme Class
 *---------------------------------------------------------------------------*/

/**
 * @class 
 * Predefined visual themes for datasets, defining colors and images for
 * map markers and timeline events.
 *
 * @constructor
 * @param {Object} options          A container for optional arguments:<pre>
 *      {GIcon} icon                    Icon for marker placemarks
 *      {String} color                  Default color in hex for events, polylines, polygons
 *      {String} lineColor              Color for polylines, defaults to options.color
 *      {String} polygonLineColor       Color for polygon outlines, defaults to lineColor
 *      {Number} lineOpacity            Opacity for polylines
 *      {Number} polgonLineOpacity      Opacity for polygon outlines, defaults to options.lineOpacity
 *      {Number} lineWeight             Line weight in pixels for polylines
 *      {Number} polygonLineWeight      Line weight for polygon outlines, defaults to options.lineWeight
 *      {String} fillColor              Color for polygon fill, defaults to options.color
 *      {String} fillOpacity            Opacity for polygon fill
 *      {String} eventColor             Background color for duration events
 *      {URL} eventIcon                 Icon URL for instant events
 *      {Boolean} classicTape           Whether to use the "classic" style timeline event tape
 *                                      (NB: this needs additional css to work - see examples/artists.html)
 * </pre>
 */
function TimeMapTheme(options) {
    // work out various defaults - the default theme is Google's reddish color
    options = options || {};
    
    if (!options.icon) {
        // make new red icon
        var markerIcon = new GIcon(G_DEFAULT_ICON);
        this.iconImage = options.iconImage || GIP + "red-dot.png";
        markerIcon.image = this.iconImage;
        markerIcon.iconSize = new GSize(32, 32);
        markerIcon.shadow = GIP + "msmarker.shadow.png";
        markerIcon.shadowSize = new GSize(59, 32);
        markerIcon.iconAnchor = new GPoint(16, 33);
        markerIcon.infoWindowAnchor = new GPoint(18, 3);
    }
    
    this.icon =              options.icon || markerIcon;
    this.color =             options.color || "#FE766A";
    this.lineColor =         options.lineColor || this.color;
    this.polygonLineColor =  options.polygonLineColor || this.lineColor;
    this.lineOpacity =       options.lineOpacity || 1;
    this.polgonLineOpacity = options.polgonLineOpacity || this.lineOpacity;
    this.lineWeight =        options.lineWeight || 2;
    this.polygonLineWeight = options.polygonLineWeight || this.lineWeight;
    this.fillColor =         options.fillColor || this.color;
    this.fillOpacity =       options.fillOpacity || 0.25;
    this.eventColor =        options.eventColor || this.color;
    this.eventTextColor =    options.eventTextColor || null;
    this.eventIconPath =     options.eventIconPath || "timemap/images/";
    this.eventIconImage =    options.eventIconImage || "red-circle.png";
    this.eventIcon =         options.eventIcon || this.eventIconPath + this.eventIconImage;
    
    // whether to use the older "tape" event style for the newer Timeline versions
    this.classicTape = ("classicTape" in options) ? options.classicTape : false;
}

/**
 * Create a theme, based on an optional new or pre-set theme
 *
 * @param {Object} options          Container for optional arguments - @see TimeMapTheme()
 * @return {TimeMapTheme}           Configured theme
 */
TimeMapTheme.create = function(theme, options) {
    // test for string matches
    if (typeof(theme) == "string") {
        if (theme in TimeMap.themes) {
            // get theme by key
            return new TimeMap.themes[theme](options)
        }
        else {
            theme = null;
        }
    }
    // no theme supplied, or bad string
    if (!theme) {
        return new TimeMapTheme(options);
    }
    // theme supplied - clone, overriding with options as necessary
    var clone = new TimeMapTheme(), prop;
    for (prop in theme) {
        if (theme.hasOwnProperty(prop)) {
            clone[prop] = options[prop] || theme[prop];
            // special case for event icon path
            if (prop == "eventIconPath") {
                clone.eventIconImage = clone[prop] + theme.eventIconImage;
            }
        }
    }
    return clone;
} 

/**
 * @namespace
 * Pre-set event/placemark themes in a variety of colors.
 */
TimeMap.themes = {

    /** 
     * Red theme: #FE766A
     * This is the default.
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    red: function(options) {
        return new TimeMapTheme(options);
    },
    
    /** 
     * Blue theme: #5A7ACF
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    blue: function(options) {
        options = options || {};
        options.iconImage = GIP + "blue-dot.png";
        options.color = "#5A7ACF";
        options.eventIconImage = "blue-circle.png";
        return new TimeMapTheme(options);
    },

    /** 
     * Green theme: #19CF54
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    green: function(options) {
        options = options || {};
        options.iconImage = GIP + "green-dot.png";
        options.color = "#19CF54";
        options.eventIconImage = "green-circle.png";
        return new TimeMapTheme(options);
    },

    /** 
     * Light blue theme: #5ACFCF
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    ltblue: function(options) {
        options = options || {};
        options.iconImage = GIP + "ltblue-dot.png";
        options.color = "#5ACFCF";
        options.eventIconImage = "ltblue-circle.png";
        return new TimeMapTheme(options);
    },

    /** 
     * Purple theme: #8E67FD
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    purple: function(options) {
        options = options || {};
        options.iconImage = GIP + "purple-dot.png";
        options.color = "#8E67FD";
        options.eventIconImage = "purple-circle.png";
        return new TimeMapTheme(options);
    },

    /** 
     * Orange theme: #FF9900
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    orange: function(options) {
        options = options || {};
        options.iconImage = GIP + "orange-dot.png";
        options.color = "#FF9900";
        options.eventIconImage = "orange-circle.png";
        return new TimeMapTheme(options);
    },

    /** 
     * Yellow theme: #ECE64A
     *
     * @param {Object} options  Container for optional settings
     * @return {TimeMapTheme}   Pre-set theme
     * @see TimeMapTheme
     */
    yellow: function(options) {
        options = options || {};
        options.iconImage = GIP + "yellow-dot.png";
        options.color = "#ECE64A";
        options.eventIconImage = "yellow-circle.png";
        return new TimeMapTheme(options);
    }
};


/*----------------------------------------------------------------------------
 * TimeMapItem Class
 *---------------------------------------------------------------------------*/

/**
 * @class
 * The TimeMapItem object holds references to one or more map placemarks and 
 * an associated timeline event.
 *
 * @constructor
 * @param {placemark} placemark     Placemark or array of placemarks (GMarker, GPolyline, etc)
 * @param {Event} event             The timeline event
 * @param {TimeMapDataset} dataset  Reference to the parent dataset object
 * @param {Object} options          A container for optional arguments:<pre>
 *   {String} title                     Title of the item
 *   {String} description               Plain-text description of the item
 *   {String} type                      Type of map placemark used (marker. polyline, polygon)
 *   {GLatLng} infoPoint                Point indicating the center of this item
 *   {String} infoHtml                  Full HTML for the info window
 *   {String} infoUrl                   URL from which to retrieve full HTML for the info window
 *   {Function} openInfoWindow          Function redefining how info window opens
 *   {Function} closeInfoWindow         Function redefining how info window closes
 *   {String/TimeMapTheme} theme        Theme applying to this item, overriding dataset theme
 * </pre>
 */
function TimeMapItem(placemark, event, dataset, options) {
    /**
     * This item's timeline event
     * @type Timeline.Event
     */
    this.event = event;
    
    /**
     * This item's parent dataset
     * @type TimeMapDataset
     */
    this.dataset = dataset;
    
    /**
     * The timemap's map object
     * @type GMap2
     */
    this.map = dataset.timemap.map;
    
    // initialize placemark(s) with some type juggling
    if (placemark && TimeMap.util.isArray(placemark) && placemark.length === 0) {
        placemark = null;
    }
    if (placemark && placemark.length == 1) {
        placemark = placemark[0];
    }
    /**
     * This item's placemark(s)
     * @type GMarker/GPolyline/GPolygon/GOverlay/Array
     */
    this.placemark = placemark;
    
    // set defaults for options
    this.opts = options || {};
    this.opts.type =        options.type || '';
    this.opts.title =       options.title || '';
    this.opts.description = options.description || '';
    this.opts.infoPoint =   options.infoPoint || null;
    this.opts.infoHtml =    options.infoHtml || '';
    this.opts.infoUrl =     options.infoUrl || '';
    
    // get functions
    
    /**
     * Return the placemark type for this item
     * 
     * @return {String}     Placemark type
     */
    this.getType = function() { return this.opts.type; };
    
    /**
     * Return the title for this item
     * 
     * @return {String}     Item title
     */
    this.getTitle = function() { return this.opts.title; };
    
    /**
     * Return the item's "info point" (the anchor for the map info window)
     * 
     * @return {GLatLng}    Info point
     */
    this.getInfoPoint = function() { 
        // default to map center if placemark not set
        return this.opts.infoPoint || this.map.getCenter(); 
    };
    
    /**
     * Whether the item is visible
     * @type Boolean
     */
    this.visible = true;
    
    /**
     * Whether the item's placemark is visible
     * @type Boolean
     */
    this.placemarkVisible = false;
    
    /**
     * Whether the item's event is visible
     * @type Boolean
     */
    this.eventVisible = true;
    
    // allow for custom open/close functions, set at item, dataset, or timemap level
    var openFunction, dopts = dataset.opts,
        tmopts = dataset.timemap.opts;
    // set open function
    openFunction = options.openInfoWindow ||
        dopts.openInfoWindow ||
        tmopts.openInfoWindow ||
        false;
    if (!openFunction) {
        if (this.opts.infoUrl !== "") {
            // load via AJAX if URL is provided
            openFunction = TimeMapItem.openInfoWindowAjax;
        } else {
            // otherwise default to basic window
            openFunction = TimeMapItem.openInfoWindowBasic;
        }
    }
    
    /**
     * Open the info window for this item.
     * By default this is the map infoWindow, but you can set custom functions
     * for whatever behavior you want when the event or placemark is clicked
     * @function
     */
    this.openInfoWindow = openFunction;
    
    /**
     * Close the info window for this item.
     * By default this is the map infoWindow, but you can set custom functions
     * for whatever behavior you want.
     * @function
     */
    this.closeInfoWindow = options.closeInfoWindow ||
        dopts.closeInfoWindow ||
        tmopts.closeInfoWindow ||
        TimeMapItem.closeInfoWindowBasic;
}

/** 
 * Show the map placemark(s)
 */
TimeMapItem.prototype.showPlacemark = function() {
    if (this.placemark) {
        if (this.getType() == "array") {
            for (var i=0; i<this.placemark.length; i++) {
                this.placemark[i].show();
            }
        } else {
            this.placemark.show();
        }
        this.placemarkVisible = true;
    }
};

/** 
 * Hide the map placemark(s)
 */
TimeMapItem.prototype.hidePlacemark = function() {
    if (this.placemark) {
        if (this.getType() == "array") {
            for (var i=0; i<this.placemark.length; i++) {
                this.placemark[i].hide();
            }
        } else {
            this.placemark.hide();
        }
        this.placemarkVisible = false;
    }
    this.closeInfoWindow();
};

/** 
 * Show the timeline event.
 * NB: Will likely require calling timeline.layout()
 */
TimeMapItem.prototype.showEvent = function() {
    if (this.event) {
        if (this.eventVisible === false){
            this.dataset.timemap.timeline.getBand(0)
                .getEventSource()._events._events.add(this.event);
        }
        this.eventVisible = true;
    }
};

/** 
 * Hide the timeline event.
 * NB: Will likely require calling timeline.layout()
 */
TimeMapItem.prototype.hideEvent = function() {
    if (this.event) {
        if (this.eventVisible == true){
            this.dataset.timemap.timeline.getBand(0)
                .getEventSource()._events._events.remove(this.event);
        }
        this.eventVisible = false;
    }
};

/**
 * Standard open info window function, using static text in map window
 */
TimeMapItem.openInfoWindowBasic = function() {
    var html = this.opts.infoHtml;
    // create content for info window if none is provided
    if (html === "") {
        html = '<div class="infotitle">' + this.opts.title + '</div>';
        if (this.opts.description !== "") {
            html += '<div class="infodescription">' + this.opts.description + '</div>';
        }
    }
    // scroll timeline if necessary
    if (this.placemark && !this.visible && this.event) {
        var topband = this.dataset.timemap.timeline.getBand(0);
        topband.setCenterVisibleDate(this.event.getStart());
    }
    // open window
    if (this.getType() == "marker") {
        this.placemark.openInfoWindowHtml(html);
    } else {
        this.map.openInfoWindowHtml(this.getInfoPoint(), html);
    }
    // custom functions will need to set this as well
    this.selected = true;
};

/**
 * Open info window function using ajax-loaded text in map window
 */
TimeMapItem.openInfoWindowAjax = function() {
    if (this.opts.infoHtml !== "") { // already loaded - change to static
        this.openInfoWindow = TimeMapItem.openInfoWindowBasic;
        this.openInfoWindow();
    } else { // load content via AJAX
        if (this.opts.infoUrl !== "") {
            var item = this;
            GDownloadUrl(this.opts.infoUrl, function(result) {
                    item.opts.infoHtml = result;
                    item.openInfoWindow();
            });
        } else { // fall back on basic function
            this.openInfoWindow = TimeMapItem.openInfoWindowBasic;
            this.openInfoWindow();
        }
    }
};

/**
 * Standard close window function, using the map window
 */
TimeMapItem.closeInfoWindowBasic = function() {
    if (this.getType() == "marker") {
        this.placemark.closeInfoWindow();
    } else {
        var infoWindow = this.map.getInfoWindow();
        // close info window if its point is the same as this item's point
        if (infoWindow.getPoint() == this.getInfoPoint() && !infoWindow.isHidden()) {
            this.map.closeInfoWindow();
        }
    }
    // custom functions will need to set this as well
    this.selected = false;
};

/*----------------------------------------------------------------------------
 * Utility functions
 *---------------------------------------------------------------------------*/

/**
 * @namespace
 * Namespace for TimeMap utility functions.
 */
TimeMap.util = {};

/**
 * Convenience trim function
 * 
 * @param {String} str      String to trim
 * @return {String}         Trimmed string
 */
TimeMap.util.trim = function(str) {
    str = str && String(str) || '';
    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
};

/**
 * Convenience array tester
 *
 * @param {Object} o        Object to test
 * @return {Boolean}        Whether the object is an array
 */
TimeMap.util.isArray = function(o) {   
    return o && !(o.propertyIsEnumerable('length')) && 
        typeof o === 'object' && typeof o.length === 'number';
};

/**
 * Get XML tag value as a string
 *
 * @param {XML Node} n      Node in which to look for tag
 * @param {String} tag      Name of tag to look for
 * @param {String} ns       Optional namespace
 * @return {String}         Tag value as string
 */
TimeMap.util.getTagValue = function(n, tag, ns) {
    var str = "";
    var nList = TimeMap.util.getNodeList(n, tag, ns);
    if (nList.length > 0) {
        n = nList[0].firstChild;
        // fix for extra-long nodes
        // see http://code.google.com/p/timemap/issues/detail?id=36
        while(n !== null) {
            str += n.nodeValue;
            n = n.nextSibling;
        }
    }
    return str;
};

/**
 * Empty container for mapping XML namespaces to URLs
 */
TimeMap.util.nsMap = {};

/**
 * Cross-browser implementation of getElementsByTagNameNS.
 * Note: Expects any applicable namespaces to be mapped in
 * TimeMap.util.nsMap. XXX: There may be better ways to do this.
 *
 * @param {XML Node} n      Node in which to look for tag
 * @param {String} tag      Name of tag to look for
 * @param {String} ns       Optional namespace
 * @return {XML Node List}  List of nodes with the specified tag name
 */
TimeMap.util.getNodeList = function(n, tag, ns) {
    var nsMap = TimeMap.util.nsMap;
    if (ns === undefined) {
        // no namespace
        return n.getElementsByTagName(tag);
    }
    if (n.getElementsByTagNameNS && nsMap[ns]) {
        // function and namespace both exist
        return n.getElementsByTagNameNS(nsMap[ns], tag);
    }
    // no function, try the colon tag name
    return n.getElementsByTagName(ns + ':' + tag);
};

/**
 * Make TimeMap.init()-style points from a GLatLng, array, or string
 *
 * @param {Object} coords       GLatLng, array, or string to convert
 * @param {Boolean} reversed    Whether the points are KML-style lon/lat, rather than lat/lon
 * @return {Object}             TimeMap.init()-style point 
 */
TimeMap.util.makePoint = function(coords, reversed) {
    var latlon = null, 
        trim = TimeMap.util.trim;
    // GLatLng
    if (coords.lat && coords.lng) {
        latlon = [coords.lat(), coords.lng()];
    }
    // array of coordinates
    if (TimeMap.util.isArray(coords)) {
        latlon = coords;
    }
    // string
    if (latlon === null) {
        // trim extra whitespace
        coords = trim(coords);
        if (coords.indexOf(',') > -1) {
            // split on commas
            latlon = coords.split(",");
        } else {
            // split on whitespace
            latlon = coords.split(/[\r\n\f ]+/);
        }
    }
    // deal with extra coordinates (i.e. KML altitude)
    if (latlon.length > 2) latlon = latlon.slice(0, 2);
    // deal with backwards (i.e. KML-style) coordinates
    if (reversed) latlon.reverse();
    return {
        "lat": trim(latlon[0]),
        "lon": trim(latlon[1])
    };
};

/**
 * Make TimeMap.init()-style polyline/polygons from a whitespace-delimited
 * string of coordinates (such as those in GeoRSS and KML).
 * XXX: Any reason for this to take arrays of GLatLngs as well?
 *
 * @param {Object} coords       String to convert
 * @param {Boolean} reversed    Whether the points are KML-style lon/lat, rather than lat/lon
 * @return {Object}             Formated coordinate array
 */
TimeMap.util.makePoly = function(coords, reversed) {
    var poly = [], latlon;
    var coordArr = TimeMap.util.trim(coords).split(/[\r\n\f ]+/);
    if (coordArr.length == 0) return [];
    // loop through coordinates
    for (var x=0; x<coordArr.length; x++) {
        latlon = (coordArr[x].indexOf(',') > 0) ?
            // comma-separated coordinates (KML-style lon/lat)
            coordArr[x].split(",") :
            // space-separated coordinates - increment to step by 2s
            [coordArr[x], coordArr[++x]];
        // deal with extra coordinates (i.e. KML altitude)
        if (latlon.length > 2) latlon = latlon.slice(0, 2);
        // deal with backwards (i.e. KML-style) coordinates
        if (reversed) latlon.reverse();
        poly.push({
            "lat": latlon[0],
            "lon": latlon[1]
        });
    }
    return poly;
}

/**
 * Format a date as an ISO 8601 string
 *
 * @param {Date} d          Date to format
 * @param {int} precision   Optional precision indicator:
 *                              3 (default): Show full date and time
 *                              2: Show full date and time, omitting seconds
 *                              1: Show date only
 * @return {String}         Formatted string
 */
TimeMap.util.formatDate = function(d, precision) {
    // default to high precision
    precision = precision || 3;
    var str = "";
    if (d) {
        // check for date.js support
        if (d.toISOString) {
            return d.toISOString();
        }
        // otherwise, build ISO 8601 string
        var pad = function(num) {
            return ((num < 10) ? "0" : "") + num;
        };
        var yyyy = d.getUTCFullYear(),
            mo = d.getUTCMonth(),
            dd = d.getUTCDate();
        str += yyyy + '-' + pad(mo + 1 ) + '-' + pad(dd);
        // show time if top interval less than a week
        if (precision > 1) {
            var hh = d.getUTCHours(),
                mm = d.getUTCMinutes(),
                ss = d.getUTCSeconds();
            str += 'T' + pad(hh) + ':' + pad(mm);
            // show seconds if the interval is less than a day
            if (precision > 2) {
                str += pad(ss);
            }
            str += 'Z';
        }
    }
    return str;
};

/**
 * Determine the SIMILE Timeline version.
 *
 * @return {String}     At the moment, only "1.2", "2.2.0", or what Timeline provides
 */
TimeMap.util.TimelineVersion = function() {
    // check for Timeline.version support - added in 2.3.0
    if (Timeline.version) {
        return Timeline.version;
    }
    if (Timeline.DurationEventPainter) {
        return "1.2";
    } else {
        return "2.2.0";
    }
};


/** 
 * Identify the placemark type. 
 * XXX: Not 100% happy with this implementation, which relies heavily on duck-typing.
 *
 * @param {Object} pm       Placemark to identify
 * @return {String}         Type of placemark, or false if none found
 */
TimeMap.util.getPlacemarkType = function(pm) {
    if ('getIcon' in pm) {
        return 'marker';
    }
    if ('getVertex' in pm) {
        return 'setFillStyle' in pm ? 'polygon' : 'polyline';
    }
    return false;
};
