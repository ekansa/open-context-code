/*! 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @overview
 *
 * <p>Timemap.js is intended to sync a SIMILE Timeline with a Google Map. 
 * Depends on: Google Maps API v2, SIMILE Timeline v1.2 - 2.3.1. 
 * Thanks to Jorn Clausen (http://www.oe-files.de) for initial concept and code. 
 * Timemap.js is licensed under the MIT License (see <a href="../LICENSE.txt">LICENSE.txt</a>).</p>
 * <ul>
 *     <li><a href="http://code.google.com/p/timemap/">Project Homepage</a></li>
 *     <li><a href="http://groups.google.com/group/timemap-development">Discussion Group</a></li>
 *     <li><a href="../examples/index.html">Working Examples</a></li>
 * </ul>
 *
 * @name timemap.js
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 * @version 1.6
 */

// globals - for JSLint
/*global GBrowserIsCompatible, GLargeMapControl, GMap2, GIcon       */ 
/*global GMapTypeControl, GDownloadUrl, GGroundOverlay              */
/*global GMarker, GPolygon, GPolyline, GSize, G_DEFAULT_ICON        */
/*global G_HYBRID_MAP, G_MOON_VISIBLE_MAP, G_SKY_VISIBLE_MAP        */

(function(){

// borrowing some space-saving devices from jquery
var 
	// Will speed up references to window, and allows munging its name.
	window = this,
	// Will speed up references to undefined, and allows munging its name.
	undefined,
    // aliases for Timeline objects
    Timeline = window.Timeline, DateTime = Timeline.DateTime, 
    // aliases for Google variables (anything that gets used more than once)
    G_DEFAULT_MAP_TYPES = window.G_DEFAULT_MAP_TYPES, 
    G_NORMAL_MAP = window.G_NORMAL_MAP, 
    G_PHYSICAL_MAP = window.G_PHYSICAL_MAP, 
    G_SATELLITE_MAP = window.G_SATELLITE_MAP, 
    GLatLng = window.GLatLng, 
    GLatLngBounds = window.GLatLngBounds, 
    GEvent = window.GEvent,
    // Google icon path
    GIP = "http://www.google.com/intl/en_us/mapfiles/ms/icons/",
    // aliases for class names, allowing munging
    TimeMap, TimeMapFilterChain, TimeMapDataset, TimeMapTheme, TimeMapItem;

/*----------------------------------------------------------------------------
 * TimeMap Class
 *---------------------------------------------------------------------------*/
 
/**
 * @class
 * The TimeMap object holds references to timeline, map, and datasets.
 *
 * @constructor
 * This will create the visible map, but not the timeline, which must be initialized separately.
 *
 * @param {DOM Element} tElement     The timeline element.
 * @param {DOM Element} mElement     The map element.
 * @param {Object} [options]       A container for optional arguments
 * @param {TimeMapTheme|String} [options.theme=red] Color theme for the timemap
 * @param {Boolean} [options.syncBands=true]    Whether to synchronize all bands in timeline
 * @param {GLatLng} [options.mapCenter=0,0]     Point for map center
 * @param {Number} [options.mapZoom=0]          Initial map zoom level
 * @param {GMapType|String} [options.mapType=physical]  The maptype for the map
 * @param {Array} [options.mapTypes=normal,satellite,physical]  The set of maptypes available for the map
 * @param {Function|String} [options.mapFilter={@link TimeMap.filters.hidePastFuture}] 
 *                                              How to hide/show map items depending on timeline state;
 *                                              options: keys in {@link TimeMap.filters} or function
 * @param {Boolean} [options.showMapTypeCtrl=true]  Whether to display the map type control
 * @param {Boolean} [options.showMapCtrl=true]      Whether to show map navigation control
 * @param {Boolean} [options.centerMapOnItems=true] Whether to center and zoom the map based on loaded item 
 * @param {Boolean} [options.noEventLoad=false]     Whether to skip loading events on the timeline
 * @param {Boolean} [options.noPlacemarkLoad=false] Whether to skip loading placemarks on the map
 * @param {String} [options.eventIconPath]      Path for directory holding event icons; if set at the TimeMap
 *                                              level, will override dataset and item defaults
 * @param {String} [options.infoTemplate]       HTML for the info window content, with variable expressions
 *                                              (as "{{varname}}" by default) to be replaced by option data
 * @param {String} [options.templatePattern]    Regex pattern defining variable syntax in the infoTemplate
 * @param {Function} [options.openInfoWindow={@link TimeMapItem.openInfoWindowBasic}]   
 *                                              Function redefining how info window opens
 * @param {Function} [options.closeInfoWindow={@link TimeMapItem.closeInfoWindowBasic}]  
 *                                              Function redefining how info window closes
 * @param {mixed} [options[...]]                Any of the options for {@link TimeMapTheme} may be set here,
 *                                              to cascade to the entire TimeMap, though they can be overridden
 *                                              at lower levels
 * </pre>
 */
TimeMap = function(tElement, mElement, options) {
    var tm = this,
        // set defaults for options
        defaults = {
            mapCenter:          new GLatLng(0,0),
            mapZoom:            0,
            mapType:            G_PHYSICAL_MAP,
            mapTypes:           [G_NORMAL_MAP, G_SATELLITE_MAP, G_PHYSICAL_MAP],
            showMapTypeCtrl:    true,
            showMapCtrl:        true,
            syncBands:          true,
            mapFilter:          'hidePastFuture',
            centerOnItems:      true,
            theme:              'red'
        };
    
    // save DOM elements
    /**
     * Map element
     * @name TimeMap#mElement
     * @type DOM Element
     */
    tm.mElement = mElement;
    /**
     * Timeline element
     * @name TimeMap#tElement
     * @type DOM Element
     */
    tm.tElement = tElement;
    
    /** 
     * Map of datasets 
     * @name TimeMap#datasets
     * @type Object 
     */
    tm.datasets = {};
    /**
     * Filter chains for this timemap 
     * @name TimeMap#chains
     * @type Object
     */
    tm.chains = {};
    
    /** 
     * Container for optional settings passed in the "options" parameter
     * @name TimeMap#opts
     * @type Object
     */
    tm.opts = options = util.merge(options, defaults);
    
    // only these options will cascade to datasets and items
    options.mergeOnly = ['mergeOnly', 'theme', 'eventIconPath', 'openInfoWindow', 
                         'closeInfoWindow', 'noPlacemarkLoad', 'noEventLoad', 
                         'infoTemplate', 'templatePattern']
    
    // allow map types to be specified by key
    options.mapType = util.lookup(options.mapType, TimeMap.mapTypes);
    // allow map filters to be specified by key
    options.mapFilter = util.lookup(options.mapFilter, TimeMap.filters);
    // allow theme options to be specified in options
    options.theme = TimeMapTheme.create(options.theme, options);
    
    // initialize map
    tm.initMap();
};

/**
 * Initialize the map.
 */
TimeMap.prototype.initMap = function() {
    var options = this.opts, map, i;
    if (GBrowserIsCompatible()) {
    
        /** 
         * The associated GMap object 
         * @type GMap2
         */
        this.map = map = new GMap2(this.mElement);
        
        // drop all existing types
        for (i=G_DEFAULT_MAP_TYPES.length-1; i>0; i--) {
            map.removeMapType(G_DEFAULT_MAP_TYPES[i]);
        }
        // you can't remove the last maptype, so add a new one first
        map.addMapType(options.mapTypes[0]);
        map.removeMapType(G_DEFAULT_MAP_TYPES[0]);
        // add the rest of the new types
        for (i=1; i<options.mapTypes.length; i++) {
            map.addMapType(options.mapTypes[i]);
        }
        
        // initialize map center, zoom, and map type
        map.setCenter(options.mapCenter, options.mapZoom, options.mapType);
        
        // set basic parameters
        map.enableDoubleClickZoom();
        map.enableScrollWheelZoom();
        map.enableContinuousZoom();
        
        // set controls
        if (options.showMapCtrl) {
            map.addControl(new GLargeMapControl());
        }
        if (options.showMapTypeCtrl) {
            map.addControl(new GMapTypeControl());
        }
        
        /** 
         * Bounds of the map 
         * @type GLatLngBounds
         */
        this.mapBounds = options.mapZoom > 0 ?
            // if the zoom has been set, use the map bounds
            map.getBounds() :
            // otherwise, start from scratch
            new GLatLngBounds();
    }
};

/**
 * Current library version.
 * @constant
 * @type String
 */
TimeMap.version = "1.6";

/**
 * @name TimeMap.util
 * @namespace
 * Namespace for TimeMap utility functions.
 */
var util = TimeMap.util = {};

/**
 * Intializes a TimeMap.
 *
 * <p>The idea here is to throw all of the standard intialization settings into
 * a large object and then pass it to the TimeMap.init() function. The full
 * data format is outlined below, but if you leave elements out the script 
 * will use default settings instead.</p>
 *
 * <p>See the examples and the 
 * <a href="http://code.google.com/p/timemap/wiki/UsingTimeMapInit">UsingTimeMapInit wiki page</a>
 * for usage.</p>
 *
 * @param {Object} config                           Full set of configuration options.
 * @param {String} config.mapId                     DOM id of the element to contain the map
 * @param {String} config.timelineId                DOM id of the element to contain the timeline
 * @param {Object} [config.options]                 Options for the TimeMap object (see the {@link TimeMap} constructor)
 * @param {Object[]} config.datasets                Array of datasets to load
 * @param {Object} config.datasets[x]               Configuration options for a particular dataset
 * @param {String|Class} config.datasets[x].type    Loader type for this dataset (generally a sub-class 
 *                                                  of {@link TimeMap.loaders.base})
 * @param {Object} config.datasets[x].options       Options for the loader. See the {@link TimeMap.loaders.base}
 *                                                  constructor and the constructors for the various loaders for 
 *                                                  more details.
 * @param {String} [config.datasets[x].id]          Optional id for the dataset in the {@link TimeMap#datasets}
 *                                                  object, for future reference; otherwise "ds"+x is used
 * @param {String} [config.datasets[x][...]]         Other options for the {@link TimeMapDataset} object
 * @param {String|Array} [config.bandIntervals]     Intervals for the two default timeline bands. Can either be an 
 *                                                  array of interval constants or a key in {@link TimeMap.intervals}
 * @param {Object[]} [config.bandInfo]              Array of configuration objects for Timeline bands, to be passed to
 *                                                  Timeline.createBandInfo (see the <a href="http://code.google.com/p/simile-widgets/wiki/Timeline_GettingStarted">Timeline Getting Started tutorial</a>).
 *                                                  This will override config.bandIntervals, if provided.
 * @param {Timeline.Band[]} [config.bands]          Array of instantiated Timeline Band objects. This will override
 *                                                  config.bandIntervals and config.bandInfo, if provided.
 * @param {Function} [config.dataLoadedFunction]    Function to be run as soon as all datasets are loaded, but
 *                                                  before they've been displayed on the map and timeline
 *                                                  (this will override dataDisplayedFunction and scrollTo)
 * @param {Function} [config.dataDisplayedFunction] Function to be run as soon as all datasets are loaded and 
 *                                                  displayed on the map and timeline
 * @param {String|Date} [config.scrollTo]           Date to scroll to once data is loaded - see 
 *                                                  {@link TimeMap.parseDate} for options; default is "earliest"
 * @return {TimeMap}                                The initialized TimeMap object
 */
TimeMap.init = function(config) {
    var err = "TimeMap.init: No id for ",    
        // set defaults
        defaults = {
            options:        {},
            datasets:       [],
            bands:          false,
            bandInfo:       false,
            bandIntervals:  "wk",
            scrollTo:       "earliest"
        },
        state = TimeMap.state,
        intervals, tm,
        datasets = [], x, ds, dsOptions, topOptions, dsId,
        bands = [], eventSource, bandInfo;
    
    // check required elements
    if (!('mapId' in config) || !config.mapId) {
        throw err + "map";
    }
    if (!('timelineId' in config) || !config.timelineId) {
        throw err + "timeline";
    }
    
    // get state from url hash if state functions are available
    if (state) {
        state.setConfigFromUrl(config);
    }
    // merge options and defaults
    config = util.merge(config, defaults);

    if (!config.bandInfo && !config.bands) {
        // allow intervals to be specified by key
        intervals = util.lookup(config.bandIntervals, TimeMap.intervals);
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
                overview:       true,
                trackHeight:    0.4,
                trackGap:       0.2
            }
        ];
    }
    
    // create the TimeMap object
    tm = new TimeMap(
  		document.getElementById(config.timelineId), 
		document.getElementById(config.mapId),
		config.options);
    
    // create the dataset objects
    for (x=0; x < config.datasets.length; x++) {
        ds = config.datasets[x];
        // put top-level data into options
        topOptions = {
            title: ds.title,
            theme: ds.theme,
            dateParser: ds.dateParser
        };
        dsOptions = util.merge(ds.options, topOptions);
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
    // ensure there's at least an empty eventSource
    eventSource = (datasets[0] && datasets[0].eventSource) || new Timeline.DefaultEventSource();
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
            bandInfo = config.bandInfo[x];
            // if eventSource is explicitly set to null or false, ignore
            if (!(('eventSource' in bandInfo) && !bandInfo.eventSource)) {
                bandInfo.eventSource = eventSource;
            }
            else {
                bandInfo.eventSource = null;
            }
            bands[x] = Timeline.createBandInfo(bandInfo);
            if (x > 0 && util.TimelineVersion() == "1.2") {
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
            options = data.data || data.options || {};
            type = data.type || options.type;
            callback = function() { loadManager.increment(); };
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

/**
 * @class Static singleton for managing multiple asynchronous loads
 */
TimeMap.loadManager = new function() {
    var mgr = this;
    
    /**
     * Initialize (or reset) the load manager
     * @name TimeMap.loadManager#init
     * @function
     *
     * @param {TimeMap} tm          TimeMap instance
     * @param {Number} target       Number of datasets we're loading
     * @param {Object} [options]    Container for optional settings
     * @param {Function} [options.dataLoadedFunction]
     *                                      Custom function replacing default completion function;
     *                                      should take one parameter, the TimeMap object
     * @param {String|Date} [options.scrollTo]
     *                                      Where to scroll the timeline when load is complete
     *                                      Options: "earliest", "latest", "now", date string, Date
     * @param {Function} [options.dataDisplayedFunction]   
     *                                      Custom function to fire once data is loaded and displayed;
     *                                      should take one parameter, the TimeMap object
     */
    mgr.init = function(tm, target, config) {
        mgr.count = 0;
        mgr.tm = tm;
        mgr.target = target;
        mgr.opts = config || {};
    };
    
    /**
     * Increment the count of loaded datasets
     * @name TimeMap.loadManager#increment
     * @function
     */
    mgr.increment = function() {
        mgr.count++;
        if (mgr.count >= mgr.target) {
            mgr.complete();
        }
    };
    
    /**
     * Function to fire when all loads are complete. 
     * Default behavior is to scroll to a given date (if provided) and
     * layout the timeline.
     * @name TimeMap.loadManager#complete
     * @function
     */
    mgr.complete = function() {
        var tm = mgr.tm,
            opts = mgr.opts,
            // custom function including timeline scrolling and layout
            func = opts.dataLoadedFunction;
        if (func) {
            func(tm);
        } 
        else {
            tm.scrollToDate(opts.scrollTo, true);
            // check for state support
            if (tm.initState) tm.initState();
            // custom function to be called when data is loaded
            func = opts.dataDisplayedFunction;
            if (func) func(tm);
        }
    };
};

/**
 * Parse a date in the context of the timeline. Uses the standard parser
 * ({@link TimeMapDataset.hybridParser}) but accepts "now", "earliest", 
 * "latest", "first", and "last" (referring to loaded events)
 *
 * @param {String|Date} s   String (or date) to parse
 * @return {Date}           Parsed date
 */
TimeMap.prototype.parseDate = function(s) {
    var d = new Date(),
        eventSource = this.eventSource,
        parser = TimeMapDataset.hybridParser,
        // make sure there are events to scroll to
        hasEvents = eventSource.getCount() > 0 ? true : false;
    switch (s) {
        case "now":
            break;
        case "earliest":
        case "first":
            if (hasEvents) {
                d = eventSource.getEarliestDate();
            }
            break;
        case "latest":
        case "last":
            if (hasEvents) {
                d = eventSource.getLatestDate();
            }
            break;
        default:
            // assume it's a date, try to parse
            d = parser(s);
    }
    return d;
}

/**
 * Scroll the timeline to a given date. If lazyLayout is specified, this function
 * will also call timeline.layout(), but only if it won't be called by the 
 * onScroll listener. This involves a certain amount of reverse engineering,
 * and may not be future-proof.
 *
 * @param {String|Date} d           Date to scroll to (either a date object, a 
 *                                  date string, or one of the strings accepted 
 *                                  by TimeMap#parseDate)
 * @param {Boolean} [lazyLayout]    Whether to call timeline.layout() if not
 *                                  required by the scroll.
 */
TimeMap.prototype.scrollToDate = function(d, lazyLayout) {
    var d = this.parseDate(d), 
        timeline = this.timeline, x,
        layouts = [],
        band, minTime, maxTime;
    if (d) {
        // check which bands will need layout after scroll
        for (x=0; x < timeline.getBandCount(); x++) {
            band = timeline.getBand(x);
            minTime = band.getMinDate().getTime();
            maxTime = band.getMaxDate().getTime();
            layouts[x] = (lazyLayout && d.getTime() > minTime && d.getTime() < maxTime);
        }
        // do scroll
        timeline.getBand(0).setCenterVisibleDate(d);
        // layout as necessary
        for (x=0; x < layouts.length; x++) {
            if (layouts[x]) {
                timeline.getBand(x).layout();
            }
        }
    } 
    // layout if requested even if no date is found
    else if (lazyLayout) {
        timeline.layout();
    }
}

/**
 * Create an empty dataset object and add it to the timemap
 *
 * @param {String} id           The id of the dataset
 * @param {Object} options      A container for optional arguments for dataset constructor -
 *                              see the options passed to {@link TimeMapDataset}
 * @return {TimeMapDataset}     The new dataset object    
 */
TimeMap.prototype.createDataset = function(id, options) {
    var tm = this,
        dataset = new TimeMapDataset(tm, options);
    tm.datasets[id] = dataset;
    // add event listener
    if (tm.opts.centerOnItems) {
        var map = tm.map, 
            bounds = tm.mapBounds;
        GEvent.addListener(dataset, 'itemsloaded', function() {
            // determine the center and zoom level from the bounds
            map.setCenter(
                bounds.getCenter(),
                map.getBoundsZoomLevel(bounds)
            );
        });
    }
    return dataset;
};

/**
 * Initialize the timeline - this must happen separately to allow full control of 
 * timeline properties.
 *
 * @param {BandInfo Array} bands    Array of band information objects for timeline
 */
TimeMap.prototype.initTimeline = function(bands) {
    var tm = this,
        x, painter;
    
    // synchronize & highlight timeline bands
    for (x=1; x < bands.length; x++) {
        if (tm.opts.syncBands) {
            bands[x].syncWith = (x-1);
        }
        bands[x].highlight = true;
    }
    
    /** 
     * The associated timeline object 
     * @name TimeMap#timeline
     * @type Timeline 
     */
    tm.timeline = Timeline.create(tm.tElement, bands);
    
    // set event listeners
    
    // update map on timeline scroll
    tm.timeline.getBand(0).addOnScrollListener(function() {
        tm.filter("map");
    });

    // hijack timeline popup window to open info window
    for (x=0; x < tm.timeline.getBandCount(); x++) {
        painter = tm.timeline.getBand(x).getEventPainter().constructor;
        painter.prototype._showBubble = function(xx, yy, evt) {
            evt.item.openInfoWindow();
        };
    }
    
    // filter chain for map placemarks
    tm.addFilterChain("map", 
        function(item) {
            item.showPlacemark();
        },
        function(item) {
            item.hidePlacemark();
        }
    );
    
    // filter: hide when item is hidden
    tm.addFilter("map", function(item) {
        return item.visible;
    });
    // filter: hide when dataset is hidden
    tm.addFilter("map", function(item) {
        return item.dataset.visible;
    });
    
    // filter: hide map items depending on timeline state
    tm.addFilter("map", tm.opts.mapFilter);
    
    // filter chain for timeline events
    tm.addFilterChain("timeline", 
        // on
        function(item) {
            item.showEvent();
        },
        // off
        function(item) {
            item.hideEvent();
        },
        // pre
        null,
        // post
        function() {
            var tm = this.timemap;
            tm.eventSource._events._index();
            tm.timeline.layout();
        }
    );
    
    // filter: hide when item is hidden
    tm.addFilter("timeline", function(item) {
        return item.visible;
    });
    // filter: hide when dataset is hidden
    tm.addFilter("timeline", function(item) {
        return item.dataset.visible;
    });
    
    // add callback for window resize
    var resizeTimerID = null,
        timeline = tm.timeline;
    window.onresize = function() {
        if (resizeTimerID === null) {
            resizeTimerID = window.setTimeout(function() {
                resizeTimerID = null;
                timeline.layout();
            }, 500);
        }
    };
};

/**
 * Run a function on each dataset in the timemap. This is the preferred
 * iteration method, as it allows for future iterator options.
 *
 * @param {Function} f    The function to run, taking one dataset as an argument
 */
TimeMap.prototype.each = function(f) {
    var tm = this, 
        id;
    for (id in tm.datasets) {
        if (tm.datasets.hasOwnProperty(id)) {
            f(tm.datasets[id]);
        }
    }
};

/**
 * Run a function on each item in each dataset in the timemap.
 *
 * @param {Function} f    The function to run, taking one item as an argument
 */
TimeMap.prototype.eachItem = function(f) {
    this.each(function(ds) {
        ds.each(function(item) {
            f(item);
        });
    });
};

/**
 * Get all items from all datasets.
 *
 * @return {TimeMapItem[]}  Array of all items
 */
TimeMap.prototype.getItems = function() {
    var items = [];
    this.eachItem(function(item) {
        items.push(item);
    });
    return items;
};


/*----------------------------------------------------------------------------
 * Loader namespace and base classes
 *---------------------------------------------------------------------------*/
 
/**
 * @namespace
 * Namespace for different data loader functions.
 * New loaders can add their factories or constructors to this object; loader
 * functions are passed an object with parameters in TimeMap.init().
 *
 * @example
    TimeMap.init({
        datasets: [
            {
                // name of class in TimeMap.loaders
                type: "json_string",
                options: {
                    url: "mydata.json"
                },
                // etc...
            }
        ],
        // etc...
    });
 */
TimeMap.loaders = {

    /**
     * @namespace
     * Namespace for storing callback functions
     * @private
     */
    cb: {},
    
    /**
     * Cancel all current load requests. In practice, this is really only
     * applicable to remote asynchronous loads. Note that this doesn't cancel 
     * the download of data, just the callback that loads it.
     */
    cancelAll: function() {
        var namespace = TimeMap.loaders.cb,
            callbackName;
        for (callbackName in namespace) {
            if (namespace.hasOwnProperty(callbackName)) {
                // replace with self-cancellation function
                namespace[callbackName] = function() {
                    delete namespace[callbackName];
                };
            }
        }
    },
    
    /**
     * Static counter for naming callback functions
     * @private
     * @type int
     */
    counter: 0,

    /**
     * @class
     * Abstract loader class. All loaders should inherit from this class.
     *
     * @constructor
     * @param {Object} options          All options for the loader
     * @param {Function} [options.parserFunction=Do nothing]   
     *                                      Parser function to turn a string into a JavaScript array
     * @param {Function} [options.preloadFunction=Do nothing]      
     *                                      Function to call on data before loading
     * @param {Function} [options.transformFunction=Do nothing]    
     *                                      Function to call on individual items before loading
     * @param {String|Date} [options.scrollTo=earliest] Date to scroll the timeline to in the default callback 
     *                                                  (see {@link TimeMap#parseDate} for accepted syntax)
     */
    base: function(options) {
        var dummy = function(data) { return data; },
            loader = this;
         
        /**
         * Parser function to turn a string into a JavaScript array
         * @name TimeMap.loaders.base#parse
         * @function
         * @parameter {String} s        String to parse
         * @return {Object[]}           Array of item data
         */
        loader.parse = options.parserFunction || dummy;
        
        /**
         * Function to call on data object before loading
         * @name TimeMap.loaders.base#preload
         * @function
         * @parameter {Object} data     Data to preload
         * @return {Object[]}           Array of item data
         */
        loader.preload = options.preloadFunction || dummy;
        
        /**
         * Function to call on a single item data object before loading
         * @name TimeMap.loaders.base#transform
         * @function
         * @parameter {Object} data     Data to transform
         * @return {Object}             Transformed data for one item
         */
        loader.transform = options.transformFunction || dummy;
        
        /**
         * Date to scroll the timeline to on load
         * @name TimeMap.loaders.base#scrollTo
         * @default "earliest"
         * @type String|Date
         */
        loader.scrollTo = options.scrollTo || "earliest";
        
        /**
         * Get the name of a callback function that can be cancelled. This callback applies the parser,
         * preload, and transform functions, loads the data, then calls the user callback
         * @name TimeMap.loaders.base#getCallbackName
         * @function
         *
         * @param {TimeMapDataset} dataset  Dataset to load data into
         * @param {Function} callback       User-supplied callback function. If no function 
         *                                  is supplied, the default callback will be used
         * @return {String}                 The name of the callback function in TimeMap.loaders.cb
         */
        loader.getCallbackName = function(dataset, callback) {
            var callbacks = TimeMap.loaders.cb,
                // Define a unique function name
                callbackName = "_" + TimeMap.loaders.counter++,
                // Define default callback
                callback = callback || function() {
                    dataset.timemap.scrollToDate(loader.scrollTo, true);
                };
            
            // create callback
            callbacks[callbackName] = function(result) {
                // parse
                var items = loader.parse(result);
                // preload
                items = loader.preload(items);
                // load
                dataset.loadItems(items, loader.transform);
                // callback
                callback(); 
                // delete the callback function
                delete callbacks[callbackName];
            };
            
            return callbackName;
        };
        
        /**
         * Get a callback function that can be cancelled. This is a convenience function
         * to be used if the callback name itself is not needed.
         * @name TimeMap.loaders.base#getCallback 
         * @function
         * @see TimeMap.loaders.base#getCallbackName
         *
         * @param {TimeMapDataset} dataset  Dataset to load data into
         * @param {Function} callback       User-supplied callback function
         * @return {Function}               The configured callback function
         */
        loader.getCallback = function(dataset, callback) {
            // get loader callback name
            var callbackName = loader.getCallbackName(dataset, callback);
            // return the function
            return TimeMap.loaders.cb[callbackName];
        };
    }, 

    /**
     * @class
     * Basic loader class, for pre-loaded data. 
     * Other types of loaders should take the same parameter.
     *
     * @augments TimeMap.loaders.base
     * @example
TimeMap.init({
    datasets: [
        {
            type: "basic",
            options: {
                data: [
                    // object literals for each item
                    {
                        title: "My Item",
                        start: "2009-10-06",
                        point: {
                            lat: 37.824,
                            lon: -122.256
                        }
                    },
                    // etc...
                ]
            }
        }
    ],
    // etc...
});
     * @see <a href="../../examples/basic.html">Basic Example</a>
     *
     * @constructor
     * @param {Object} options          All options for the loader
     * @param {Array} options.data          Array of items to load
     * @param {mixed} [options[...]]        Other options (see {@link TimeMap.loaders.base})
     */
    basic: function(options) {
        var loader = new TimeMap.loaders.base(options);
        
        /**
         * Array of item data to load.
         * @name TimeMap.loaders.basic#data
         * @default []
         * @type Object[]
         */
        loader.data = options.items || 
            // allow "value" for backwards compatibility
            options.value || [];

        /**
         * Load javascript literal data.
         * New loaders should implement a load function with the same signature.
         * @name TimeMap.loaders.basic#load
         * @function
         *
         * @param {TimeMapDataset} dataset  Dataset to load data into
         * @param {Function} callback       Function to call once data is loaded
         */
        loader.load = function(dataset, callback) {
            // get callback function and call immediately on data
            (this.getCallback(dataset, callback))(this.data);
        };
        
        return loader;
    },

    /**
     * @class
     * Generic class for loading remote data with a custom parser function
     *
     * @augments TimeMap.loaders.base
     *
     * @constructor
     * @param {Object} options          All options for the loader
     * @param {String} options.url          URL of file to load (NB: must be local address)
     * @param {mixed} [options[...]]        Other options (see {@link TimeMap.loaders.base})
     */
    remote: function(options) {
        var loader = new TimeMap.loaders.base(options);
        
        /**
         * URL to load
         * @name TimeMap.loaders.remote#url
         * @type String
         */
        loader.url = options.url;
        
        /**
         * Load function for remote files.
         * @name TimeMap.loaders.remote#load
         * @function
         *
         * @param {TimeMapDataset} dataset  Dataset to load data into
         * @param {Function} callback       Function to call once data is loaded
         */
        loader.load = function(dataset, callback) {
            // download remote data and pass to callback
            GDownloadUrl(this.url, this.getCallback(dataset, callback));
        };
        
        return loader;
    }
    
};

/*----------------------------------------------------------------------------
 * TimeMapFilterChain Class
 *---------------------------------------------------------------------------*/
 
/**
 * @class
 * TimeMapFilterChains hold a set of filters to apply to the map or timeline.
 *
 * @constructor
 * @param {TimeMap} timemap Reference to the timemap object
 * @param {Function} fon    Function to run on an item if filter is true
 * @param {Function} foff   Function to run on an item if filter is false
 * @param {Function} [pre]  Function to run before the filter runs
 * @param {Function} [post] Function to run after the filter runs
 */
TimeMapFilterChain = function(timemap, fon, foff, pre, post) {
    var fc = this,
        dummy = function(item) {};
    /** 
     * Reference to parent TimeMap
     * @name TimeMapFilterChain#timemap
     * @type TimeMap
     */
    fc.timemap = timemap;
    
    /** 
     * Chain of filter functions, each taking an item and returning a boolean
     * @name TimeMapFilterChain#chain
     * @type Function[]
     */
    fc.chain = [];
    
    /** 
     * Function to run on an item if filter is true
     * @name TimeMapFilterChain#on
     * @function
     */
    fc.on = fon || dummy;
    
    /** 
     * Function to run on an item if filter is false
     * @name TimeMapFilterChain#off
     * @function
     */
    fc.off = foff || dummy;
    
    /** 
     * Function to run before the filter runs
     * @name TimeMapFilterChain#pre
     * @function
     */
    fc.pre = pre || dummy;
    
    /** 
     * Function to run after the filter runs
     * @name TimeMapFilterChain#post
     * @function
     */
    fc.post = post || dummy;
}

/**
 * Add a filter to the filter chain.
 *
 * @param {Function} f      Function to add
 */
TimeMapFilterChain.prototype.add = function(f) {
    this.chain.push(f);
}

/**
 * Remove a filter from the filter chain
 *
 * @param {Function} [f]    Function to remove; if not supplied, the last filter 
 *                          added is removed
 */
TimeMapFilterChain.prototype.remove = function(f) {
    var chain = this.chain,
        i;
    if (!f) {
        // just remove the last filter added
        chain.pop();
    }
    else {
        // look for the specific filter to remove
        for(i=0; i < chain.length; i++){
		    if(chain[i] == f){
			    chain.splice(i, 1);
		    }
	    }
    }
}

/**
 * Run filters on all items
 */
TimeMapFilterChain.prototype.run = function() {
    var fc = this,
        chain = fc.chain;
    // early exit
    if (!chain.length) {
        return;
    }
    // pre-filter function
    fc.pre();
    // run items through filter
    fc.timemap.eachItem(function(item) {
        var done = false;
        F_LOOP: while (!done) { 
            for (var i = chain.length - 1; i >= 0; i--) {
                if (!chain[i](item)) {
                    // false condition
                    fc.off(item);
                    break F_LOOP;
                }
            }
            // true condition
            fc.on(item);
            done = true;
        }
    });
    // post-filter function
    fc.post();
}

// TimeMap helper functions for dealing with filters

/**
 * Update items, hiding or showing according to filters
 *
 * @param {String} fid      Filter chain to update on
 */
TimeMap.prototype.filter = function(fid) {
    var fc = this.chains[fid];
    if (fc) {
        fc.run();
    }
    
};

/**
 * Add a new filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} fon    Function to run on an item if filter is true
 * @param {Function} foff   Function to run on an item if filter is false
 * @param {Function} [pre]  Function to run before the filter runs
 * @param {Function} [post] Function to run after the filter runs
 */
TimeMap.prototype.addFilterChain = function(fid, fon, foff, pre, post) {
    this.chains[fid] = new TimeMapFilterChain(this, fon, foff, pre, post);
};

/**
 * Remove a filter chain
 *
 * @param {String} fid      Id of the filter chain
 */
TimeMap.prototype.removeFilterChain = function(fid) {
    this.chains[fid] = null;
};

/**
 * Add a function to a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} f      Function to add
 */
TimeMap.prototype.addFilter = function(fid, f) {
    var filterChain = this.chains[fid];
    if (filterChain) {
        filterChain.add(f);
    }
};

/**
 * Remove a function from a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} [f]    The function to remove
 */
TimeMap.prototype.removeFilter = function(fid, f) {
    var filterChain = this.chains[fid];
    if (filterChain) {
        filterChain.remove(f);
    }
};

/**
 * @namespace
 * Namespace for different filter functions. Adding new filters to this
 * object allows them to be specified by string name.
 * @example
    TimeMap.init({
        options: {
            mapFilter: "hideFuture"
        },
        // etc...
    });
 */
TimeMap.filters = {

    /**
     * Static filter function: Hide items not in the visible area of the timeline.
     *
     * @param {TimeMapItem} item    Item to test for filter
     * @return {Boolean}            Whether to show the item
     */
    hidePastFuture: function(item) {
        var topband = item.timeline.getBand(0),
            maxVisibleDate = topband.getMaxVisibleDate().getTime(),
            minVisibleDate = topband.getMinVisibleDate().getTime(),
            itemStart = item.getStartTime(),
            itemEnd = item.getEndTime();
        if (itemStart !== undefined) {
            // hide items in the future
            return itemStart < maxVisibleDate &&
                // hide items in the past
                (itemEnd > minVisibleDate || itemStart > minVisibleDate);
        }
        return true;
    },

    /**
     * Static filter function: Hide items later than the visible area of the timeline.
     *
     * @param {TimeMapItem} item    Item to test for filter
     * @return {Boolean}            Whether to show the item
     */
    hideFuture: function(item) {
        var maxVisibleDate = item.timeline.getBand(0).getMaxVisibleDate().getTime(),
            itemStart = item.getStartTime();
        if (itemStart !== undefined) {
            // hide items in the future
            return itemStart < maxVisibleDate;
        }
        return true;
    },

    /**
     * Static filter function: Hide items not present at the exact
     * center date of the timeline (will only work for duration events).
     *
     * @param {TimeMapItem} item    Item to test for filter
     * @return {Boolean}            Whether to show the item
     */
    showMomentOnly: function(item) {
        var topband = item.timeline.getBand(0),
            momentDate = topband.getCenterVisibleDate().getTime(),
            itemStart = item.getStartTime(),
            itemEnd = item.getEndTime();
        if (itemStart !== undefined) {
            // hide items in the future
            return itemStart < momentDate &&
                // hide items in the past
                (itemEnd > momentDate || itemStart > momentDate);
        }
        return true;
    },

    /**
     * Convenience function: Do nothing. Can be used as a setting for mapFilter
     * in TimeMap.init() options, if you don't want map items to be hidden or
     * shown based on the timeline position.
     *
     * @param {TimeMapItem} item    Item to test for filter
     * @return {Boolean}            Whether to show the item
     */
    none: function(item) {
        return true;
    }

}


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
 * @param {Object} [options]        Object holding optional arguments
 * @param {String} [options.id]                 Key for this dataset in the datasets map
 * @param {String} [options.title]              Title of the dataset (for the legend)
 * @param {String|TimeMapTheme} [options.theme]  Theme settings.
 * @param {String|Function} [options.dateParser] Function to replace default date parser.
 * @param {String} [options.infoTemplate]        HTML template for info window content
 * @param {String} [options.templatePattern]     Regex pattern defining variable syntax in the infoTemplate
 * @param {Function} [options.openInfoWindow]    Function redefining how info window opens
 * @param {Function} [options.closeInfoWindow]   Function redefining how info window closes
 * @param {mixed} [options[...]]                Any of the options for {@link TimeMapTheme} may be set here,
 *                                              to cascade to the dataset's objects, though they can be 
 *                                              overridden at the TimeMapItem level
 */
TimeMapDataset = function(timemap, options) {
    var ds = this,
        defaults = {
            title:          'Untitled',
            dateParser:     TimeMapDataset.hybridParser
        };

    /** 
     * Reference to parent TimeMap
     * @name TimeMapDataset#timemap
     * @type TimeMap
     */
    ds.timemap = timemap;
    
    /** 
     * EventSource for timeline events
     * @name TimeMapDataset#eventSource
     * @type Timeline.EventSource
     */
    ds.eventSource = new Timeline.DefaultEventSource();
    
    /** 
     * Array of child TimeMapItems
     * @name TimeMapDataset#items
     * @type Array
     */
    ds.items = [];
    
    /** 
     * Whether the dataset is visible
     * @name TimeMapDataset#visible
     * @type Boolean
     */
    ds.visible = true;
        
    /** 
     * Container for optional settings passed in the "options" parameter
     * @name TimeMapDataset#opts
     * @type Object
     */
    ds.opts = options = util.merge(options, defaults, timemap.opts);
    
    // allow date parser to be specified by key
    options.dateParser = util.lookup(options.dateParser, TimeMap.dateParsers);
    // allow theme options to be specified in options
    options.theme = TimeMapTheme.create(options.theme, options);
    
    /**
     * Return an array of this dataset's items
     * @name TimeMapDataset#getItems
     * @function
     *
     * @param {Number} [index]     Index of single item to return
     * @return {TimeMapItem[]}  Single item, or array of all items if no index was supplied
     */
    ds.getItems = function(index) {
        if (index !== undefined) {
            if (index < ds.items.length) {
                return ds.items[index];
            }
            else {
                return null;
            }
        }
        return ds.items;
    };
    
    /**
     * Return the title of the dataset
     * @name TimeMapDataset#getTitle
     * @function
     * 
     * @return {String}     Dataset title
     */
    ds.getTitle = function() { return ds.opts.title; };
};

/**
 * Better Timeline Gregorian parser... shouldn't be necessary :(.
 * Gregorian dates are years with "BC" or "AD"
 *
 * @param {String} s    String to parse into a Date object
 * @return {Date}       Parsed date or null
 */
TimeMapDataset.gregorianParser = function(s) {
    if (!s || typeof(s) != "string") {
        return null;
    }
    // look for BC
    var bc = Boolean(s.match(/b\.?c\.?/i)),
        // parse - parseInt will stop at non-number characters
        year = parseInt(s, 10),
        d;
    // look for success
    if (!isNaN(year)) {
        // deal with BC
        if (bc) {
            year = 1 - year;
        }
        // make Date and return
        d = new Date(0);
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
    // in case we don't know if this is a string or a date
    if (s instanceof Date) {
        return s;
    }
    // try native date parse and timestamp
    var d = new Date(typeof(s) == "number" ? s : Date.parse(s));
    if (isNaN(d)) {
        if (typeof(s) == "string") {
            // look for Gregorian dates
            if (s.match(/^-?\d{1,6} ?(a\.?d\.?|b\.?c\.?e?\.?|c\.?e\.?)?$/i)) {
                d = TimeMapDataset.gregorianParser(s);
            } 
            // try ISO 8601 parse
            else {
                try {
                    d = DateTime.parseIso8601DateTime(s);
                } catch(e) {
                    d = null;
                }
            }
            // look for timestamps
            if (!d && s.match(/^\d{7,}$/)) {
                d = new Date(parseInt(s));
            }
        } else {
            return null;
        }
    }
    // d should be a date or null
    return d;
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
 * @param {Function} [transform]    If data is not in the above format, transformation function to make it so
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
 * @param {Object} data         Data to be loaded
 * @param {String} [data.title]         Title of the item (visible on timeline)
 * @param {String|Date} [data.start]    Start time of the event on the timeline
 * @param {String|Date} [data.end]      End time of the event on the timeline (duration events only)
 * @param {Object} [data.point]         Data for a single-point placemark: 
 * @param {Float} [data.point.lat]          Latitude of map marker
 * @param {Float} [data.point.lon]          Longitude of map marker
 * @param {Object[]} [data.polyline]    Data for a polyline placemark, as an array in "point" format
 * @param {Object[]} [data.polygon]     Data for a polygon placemark, as an array "point" format
 * @param {Object} [data.overlay]       Data for a ground overlay:
 * @param {String} [data.overlay.image]     URL of image to overlay
 * @param {Float} [data.overlay.north]      Northern latitude of the overlay
 * @param {Float} [data.overlay.south]      Southern latitude of the overlay
 * @param {Float} [data.overlay.east]       Eastern longitude of the overlay
 * @param {Float} [data.overlay.west]       Western longitude of the overlay
 * @param {Object[]} [data.placemarks]  Array of placemarks, e.g. [{point:{...}}, {polyline:[...]}]
 * @param {Object} [options]            Optional arguments - see the {@link TimeMapItem} constructor for details
 * @param {Function} [transform]        If data is not in the above format, transformation function to make it so
 * @return {TimeMapItem}                The created item (for convenience, as it's already been added)
 * @see TimeMapItem
 */
TimeMapDataset.prototype.loadItem = function(data, transform) {
    // apply transformation, if any
    if (transform !== undefined) {
        data = transform(data);
    }
    // transform functions can return a null value to skip a datum in the set
    if (!data) {
        return;
    }
    
    var ds = this,
        tm = ds.timemap,
        // set defaults for options
        options = util.merge(data.options, ds.opts),
        // allow theme options to be specified in options
        theme = options.theme = TimeMapTheme.create(options.theme, options),
        parser = ds.opts.dateParser, 
        eventClass = Timeline.DefaultEventSource.Event,
        // settings for timeline event
        start = data.start, 
        end = data.end, 
        eventIcon = theme.eventIcon,
        textColor = theme.eventTextColor,
        title = data.title,
        // allow event-less placemarks - these will be always present on map
        event = null,
        instant,
        // settings for the placemark
        markerIcon = theme.icon,
        bounds = tm.mapBounds,
        // empty containers
        placemark = [], 
        pdataArr = [], 
        pdata = null, 
        type = "", 
        point = null, 
        i;
    
    // create timeline event
    start = start ? parser(start) : null;
    end = end ? parser(end) : null;
    instant = !end;
    if (start !== null) { 
        if (util.TimelineVersion() == "1.2") {
            // attributes by parameter
            event = new eventClass(start, end, null, null,
                instant, title, null, null, null, eventIcon, theme.eventColor, 
                theme.eventTextColor);
        } else {
            if (!textColor) {
                // tweak to show old-style events
                textColor = (theme.classicTape && !instant) ? '#FFFFFF' : '#000000';
            }
            // attributes in object
            event = new eventClass({
                start: start,
                end: end,
                instant: instant,
                text: title,
                icon: eventIcon,
                color: theme.eventColor,
                textColor: textColor
            });
        }
    }
    
    // internal function: create map placemark
    // takes a data object (could be full data, could be just placemark)
    // returns an object with {placemark, type, point}
    var createPlacemark = function(pdata) {
        var placemark = null, 
            type = "", 
            point = null;
        // point placemark
        if (pdata.point) {
            var lat = pdata.point.lat, 
                lon = pdata.point.lon;
            if (lat === undefined || lon === undefined) {
                // give up
                return null;
            }
            point = new GLatLng(
                parseFloat(pdata.point.lat), 
                parseFloat(pdata.point.lon)
            );
            // add point to visible map bounds
            if (tm.opts.centerOnItems) {
                bounds.extend(point);
            }
            // create marker
            placemark = new GMarker(point, {
                icon: markerIcon,
                title: pdata.title
            });
            type = "marker";
            point = placemark.getLatLng();
        }
        // polyline and polygon placemarks
        else if (pdata.polyline || pdata.polygon) {
            var points = [], line;
            if (pdata.polyline) {
                line = pdata.polyline;
            } else {
                line = pdata.polygon;
            }
            if (line && line.length) {
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
        } 
        // ground overlay placemark
        else if ("overlay" in pdata) {
            var sw = new GLatLng(
                    parseFloat(pdata.overlay.south), 
                    parseFloat(pdata.overlay.west)
                ),
                ne = new GLatLng(
                    parseFloat(pdata.overlay.north), 
                    parseFloat(pdata.overlay.east)
                ),
                // create overlay
                overlayBounds = new GLatLngBounds(sw, ne);
            // add to visible bounds
            if (tm.opts.centerOnItems) {
                bounds.extend(sw);
                bounds.extend(ne);
            }
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
    
    // array of placemark objects
    if ("placemarks" in data) {
        pdataArr = data.placemarks;
    } else {
        // we have one or more single placemarks
        var types = ["point", "polyline", "polygon", "overlay"];
        for (i=0; i<types.length; i++) {
            if (types[i] in data) {
                // put in title (only used for markers)
                pdata = {title: title};
                pdata[types[i]] = data[types[i]];
                pdataArr.push(pdata);
            }
        }
    }
    if (pdataArr) {
        for (i=0; i<pdataArr.length; i++) {
            // create the placemark
            var p = createPlacemark(pdataArr[i]);
            // check that the placemark was valid
            if (p && p.placemark) {
                // take the first point and type as a default
                point = point || p.point;
                type = type || p.type;
                placemark.push(p.placemark);
            }
        }
    }
    // override type for arrays
    if (placemark.length > 1) {
        type = "array";
    }
    
    options.title = title;
    options.type = type;
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
    var item = new TimeMapItem(placemark, event, ds, options);
    // add event if it exists
    if (event !== null) {
        event.item = item;
        // allow for custom event loading
        if (!ds.opts.noEventLoad) {
            // add event to timeline
            ds.eventSource.add(event);
        }
    }
    // add placemark(s) if any exist
    if (placemark.length > 0) {
        for (i=0; i<placemark.length; i++) {
            placemark[i].item = item;
            // add listener to make placemark open when event is clicked
            GEvent.addListener(placemark[i], "click", function() {
                item.openInfoWindow();
            });
            // allow for custom placemark loading
            if (!ds.opts.noPlacemarkLoad) {
                // add placemark to map
                tm.map.addOverlay(placemark[i]);
            }
            // hide placemarks until the next refresh
            placemark[i].hide();
        }
    }
    // add the item to the dataset
    ds.items.push(item);
    // return the item object
    return item;
};

/*----------------------------------------------------------------------------
 * TimeMapTheme Class
 *---------------------------------------------------------------------------*/

/**
 * @class 
 * Predefined visual themes for datasets, defining colors and images for
 * map markers and timeline events. Note that theme is only used at creation
 * time - updating the theme of an existing object won't do anything.
 *
 * @constructor
 * @param {Object} [options]        A container for optional arguments
 * @param {GIcon} [options.icon=G_DEFAULT_ICON]         Icon for marker placemarks.
 * @param {String} [options.iconImage=red-dot.png]      Icon image for marker placemarks 
 *                                                      (assumes G_MARKER_ICON for the rest of the icon settings)
 * @param {String} [options.color=#FE766A]              Default color in hex for events, polylines, polygons.
 * @param {String} [options.lineColor=color]            Color for polylines.
 * @param {String} [options.polygonLineColor=lineColor] Color for polygon outlines.
 * @param {Number} [options.lineOpacity=1]              Opacity for polylines.
 * @param {Number} [options.polgonLineOpacity=lineOpacity]  Opacity for polygon outlines.
 * @param {Number} [options.lineWeight=2]               Line weight in pixels for polylines.
 * @param {Number} [options.polygonLineWeight=lineWeight]   Line weight for polygon outlines.
 * @param {String} [options.fillColor=color]            Color for polygon fill.
 * @param {String} [options.fillOpacity=0.25]           Opacity for polygon fill.
 * @param {String} [options.eventColor=color]           Background color for duration events.
 * @param {String} [options.eventTextColor=null]        Text color for events (null=Timeline default).
 * @param {String} [options.eventIconPath=timemap/images/]  Path to instant event icon directory.
 * @param {String} [options.eventIconImage=red-circle.gif]  Filename of instant event icon image.
 * @param {URL} [options.eventIcon=eventIconPath+eventIconImage] URL for instant event icons.
 * @param {Boolean} [options.classicTape=false]         Whether to use the "classic" style timeline event tape
 *                                                      (needs additional css to work - see examples/artists.html).
 */
TimeMapTheme = function(options) {

    // work out various defaults - the default theme is Google's reddish color
    var defaults = {
        /** Default color in hex
         * @name TimeMapTheme#color 
         * @type String */
        color:          "#FE766A",
        /** Opacity for polylines 
         * @name TimeMapTheme#lineOpacity 
         * @type Number */
        lineOpacity:    1,
        /** Line weight in pixels for polylines
         * @name TimeMapTheme#lineWeight 
         * @type Number */
        lineWeight:     2,
        /** Opacity for polygon fill 
         * @name TimeMapTheme#fillOpacity 
         * @type Number */
        fillOpacity:    0.25,
        /** Text color for duration events 
         * @name TimeMapTheme#eventTextColor 
         * @type String */
        eventTextColor: null,
        /** Path to instant event icon directory 
         * @name TimeMapTheme#eventIconPath 
         * @type String */
        eventIconPath:  "timemap/images/",
        /** Filename of instant event icon image
         * @name TimeMapTheme#eventIconImage 
         * @type String */
        eventIconImage: "red-circle.png",
        /** Whether to use the "classic" style timeline event tape
         * @name TimeMapTheme#classicTape 
         * @type Boolean */
        classicTape:    false,
        /** Icon image for marker placemarks 
         * @name TimeMapTheme#iconImage 
         * @type String */
        iconImage:      GIP + "red-dot.png"
    };
    
    // merge defaults with options
    var settings = util.merge(options, defaults);
    
    // kill mergeOnly if necessary
    delete settings.mergeOnly;
    
    // make default map icon if not supplied
    if (!settings.icon) {
        // make new red icon
        var markerIcon = new GIcon(G_DEFAULT_ICON);
        markerIcon.image = settings.iconImage;
        markerIcon.iconSize = new GSize(32, 32);
        markerIcon.shadow = GIP + "msmarker.shadow.png";
        markerIcon.shadowSize = new GSize(59, 32);
        markerIcon.iconAnchor = new GPoint(16, 33);
        markerIcon.infoWindowAnchor = new GPoint(18, 3);
        /** Marker icon for placemarks 
         * @name TimeMapTheme#icon 
         * @type GIcon */
        settings.icon = markerIcon;
    } 
    
    // cascade some settings as defaults
    defaults = {
        /** Line color for polylines
         * @name TimeMapTheme#lineColor 
         * @type String */
        lineColor:          settings.color,
        /** Line color for polygons
         * @name TimeMapTheme#polygonLineColor 
         * @type String */
        polygonLineColor:   settings.color,
        /** Opacity for polygon outlines 
         * @name TimeMapTheme#polgonLineOpacity 
         * @type Number */
        polgonLineOpacity:  settings.lineOpacity,
        /** Line weight for polygon outlines 
         * @name TimeMapTheme#polygonLineWeight 
         * @type Number */
        polygonLineWeight:  settings.lineWeight,
        /** Fill color for polygons
         * @name TimeMapTheme#fillColor 
         * @type String */
        fillColor:          settings.color,
        /** Background color for duration events
         * @name TimeMapTheme#eventColor 
         * @type String */
        eventColor:         settings.color,
        /** Full URL for instant event icons
         * @name TimeMapTheme#eventIcon 
         * @type String */
        eventIcon:          settings.eventIconPath + settings.eventIconImage
    };
    settings = util.merge(settings, defaults);
    
    // return configured options as theme
    return settings;
};

/**
 * Create a theme, based on an optional new or pre-set theme
 *
 * @param {TimeMapTheme} [theme]    Existing theme to clone
 * @param {Object} [options]        Optional settings to overwrite - see {@link TimeMapTheme}
 * @return {TimeMapTheme}           Configured theme
 */
TimeMapTheme.create = function(theme, options) {
    // test for string matches and missing themes
    if (theme) {
        theme = TimeMap.util.lookup(theme, TimeMap.themes);
    } else {
        return new TimeMapTheme(options);
    }
    
    // see if we need to clone - guessing fewer keys in options
    var clone = false, key;
    for (key in options) {
        if (theme.hasOwnProperty(key)) {
            clone = {};
            break;
        }
    }
    // clone if necessary
    if (clone) {
        for (key in theme) {
            if (theme.hasOwnProperty(key)) {
                clone[key] = options[key] || theme[key];
            }
        }
        // fix event icon path, allowing full image path in options
        clone.eventIcon = options.eventIcon || 
            clone.eventIconPath + clone.eventIconImage;
        return clone;
    }
    else {
        return theme;
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
 * @param {Object} [options]        A container for optional arguments
 * @param {String} [options.title=Untitled]         Title of the item
 * @param {String} [options.description]            Plain-text description of the item
 * @param {String} [options.type=none]              Type of map placemark used (marker. polyline, polygon)
 * @param {GLatLng} [options.infoPoint]             Point indicating the center of this item
 * @param {String} [options.infoHtml]               Full HTML for the info window
 * @param {String} [options.infoUrl]                URL from which to retrieve full HTML for the info window
 * @param {String} [options.infoTemplate]           HTML for the info window content, with variable expressions
 *                                                  (as "{{varname}}" by default) to be replaced by option data
 * @param {String} [options.templatePattern=/{{([^}]+)}}/g]
 *                                                  Regex pattern defining variable syntax in the infoTemplate
 * @param {Function} [options.openInfoWindow={@link TimeMapItem.openInfoWindowBasic}]   
 *                                                  Function redefining how info window opens
 * @param {Function} [options.closeInfoWindow={@link TimeMapItem.closeInfoWindowBasic}]  
 *                                                  Function redefining how info window closes
 * @param {String|TimeMapTheme} [options.theme]     Theme applying to this item, overriding dataset theme
 * @param {mixed} [options[...]]                    Any of the options for {@link TimeMapTheme} may be set here
 */
TimeMapItem = function(placemark, event, dataset, options) {
    // improve compression
    var item = this,
        // set defaults for options
        defaults = {
            type: 'none',
            title: 'Untitled',
            description: '',
            infoPoint: null,
            infoHtml: '',
            infoUrl: '',
            infoTemplate: '<div class="infotitle">{{title}}</div>' + 
                          '<div class="infodescription">{{description}}</div>',
            templatePattern: /{{([^}]+)}}/g,
            closeInfoWindow: TimeMapItem.closeInfoWindowBasic
        };

    /**
     * This item's timeline event
     * @name TimeMapItem#event
     * @type Timeline.Event
     */
    item.event = event;
    
    /**
     * This item's parent dataset
     * @name TimeMapItem#dataset
     * @type TimeMapDataset
     */
    item.dataset = dataset;
    
    /**
     * The timemap's map object
     * @name TimeMapItem#map
     * @type GMap2
     */
    item.map = dataset.timemap.map;
    
    /**
     * The timemap's timeline object
     * @name TimeMapItem#timeline
     * @type Timeline
     */
    item.timeline = dataset.timemap.timeline;
    
    // initialize placemark(s) with some type juggling
    if (placemark && util.isArray(placemark) && placemark.length === 0) {
        placemark = null;
    }
    if (placemark && placemark.length == 1) {
        placemark = placemark[0];
    }
    /**
     * This item's placemark(s)
     * @name TimeMapItem#placemark
     * @type GMarker|GPolyline|GPolygon|GOverlay|Array
     */
    item.placemark = placemark;
    
    /**
     * Container for optional settings passed in through the "options" parameter
     * @name TimeMapItem#opts
     * @type Object
     */
    item.opts = options = util.merge(options, defaults, dataset.opts);
    
    // select default open function
    if (!options.openInfoWindow) {
        if (options.infoUrl !== "") {
            // load via AJAX if URL is provided
            options.openInfoWindow = TimeMapItem.openInfoWindowAjax;
        } else {
            // otherwise default to basic window
            options.openInfoWindow = TimeMapItem.openInfoWindowBasic;
        }
    }
    
    // getter functions
    
    /**
     * Return the placemark type for this item
     * @name TimeMapItem#getType
     * @function
     * 
     * @return {String}     Placemark type
     */
    item.getType = function() { return item.opts.type; };
    
    /**
     * Return the title for this item
     * @name TimeMapItem#getTitle
     * @function
     * 
     * @return {String}     Item title
     */
    item.getTitle = function() { return item.opts.title; };
    
    /**
     * Return the item's "info point" (the anchor for the map info window)
     * @name TimeMapItem#getInfoPoint
     * @function
     * 
     * @return {GLatLng}    Info point
     */
    item.getInfoPoint = function() { 
        // default to map center if placemark not set
        return item.opts.infoPoint || item.map.getCenter(); 
    };
    
    /**
     * Return the start date of the item's event, if any
     * @name TimeMapItem#getStart
     * @function
     * 
     * @return {Date}   Item start date or undefined
     */
    item.getStart = function() {
        if (item.event) {
            return item.event.getStart();
        }
    };
    
    /**
     * Return the end date of the item's event, if any
     * @name TimeMapItem#getEnd
     * @function
     * 
     * @return {Date}   Item end dateor undefined
     */
    item.getEnd = function() {
        if (item.event) {
            return item.event.getEnd();
        }
    };
    
    /**
     * Return the timestamp of the start date of the item's event, if any
     * @name TimeMapItem#getStartTime
     * @function
     * 
     * @return {Number}    Item start date timestamp or undefined
     */
    item.getStartTime = function() {
        var start = item.getStart();
        if (start) {
            return start.getTime();
        }
    };
    
    /**
     * Return the timestamp of the end date of the item's event, if any
     * @name TimeMapItem#getEndTime
     * @function
     * 
     * @return {Number}    Item end date timestamp or undefined
     */
    item.getEndTime = function() {
        var end = item.getEnd();
        if (end) {
            return end.getTime();
        }
    };
    
    /**
     * Whether the item is currently selected
     * @name TimeMapItem#selected
     * @type Boolean
     */
    item.selected = false;
    
    /**
     * Whether the item is visible
     * @name TimeMapItem#visible
     * @type Boolean
     */
    item.visible = true;
    
    /**
     * Whether the item's placemark is visible
     * @name TimeMapItem#placemarkVisible
     * @type Boolean
     */
    item.placemarkVisible = false;
    
    /**
     * Whether the item's event is visible
     * @name TimeMapItem#eventVisible
     * @type Boolean
     */
    item.eventVisible = true;
    
    /**
     * Open the info window for this item.
     * By default this is the map infoWindow, but you can set custom functions
     * for whatever behavior you want when the event or placemark is clicked
     * @name TimeMapItem#openInfoWindow
     * @function
     */
    item.openInfoWindow = function() {
        options.openInfoWindow.call(item);
        item.selected = true;
    };
    
    /**
     * Close the info window for this item.
     * By default this is the map infoWindow, but you can set custom functions
     * for whatever behavior you want.
     * @name TimeMapItem#closeInfoWindow
     * @function
     */
    item.closeInfoWindow = function() {
        options.closeInfoWindow.call(item);
        item.selected = false;
    };
};

/** 
 * Show the map placemark(s)
 */
TimeMapItem.prototype.showPlacemark = function() {
    var item = this, i;
    if (item.placemark) {
        if (item.getType() == "array") {
            for (i=0; i<item.placemark.length; i++) {
                item.placemark[i].show();
            }
        } else {
            item.placemark.show();
        }
        item.placemarkVisible = true;
    }
};

/** 
 * Hide the map placemark(s)
 */
TimeMapItem.prototype.hidePlacemark = function() {
    var item = this, i;
    if (item.placemark) {
        if (item.getType() == "array") {
            for (i=0; i<item.placemark.length; i++) {
                item.placemark[i].hide();
            }
        } else {
            item.placemark.hide();
        }
        item.placemarkVisible = false;
    }
    item.closeInfoWindow();
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
 * NB: Will likely require calling timeline.layout(),
 * AND calling eventSource._events._index()  (ugh)
 */
TimeMapItem.prototype.hideEvent = function() {
    if (this.event) {
        if (this.eventVisible){
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
    var item = this,
        opts = item.opts,
        html = opts.infoHtml,
        match;
    // create content for info window if none is provided
    if (!html) {
        // fill in template
        html = opts.infoTemplate;
        match = opts.templatePattern.exec(html);
        while (match) {
            html = html.replace(match[0], opts[match[1]]);
            match = opts.templatePattern.exec(html);
        }
    }
    // scroll timeline if necessary
    if (item.placemark && !item.placemarkVisible && item.event) {
        item.dataset.timemap.scrollToDate(item.event.getStart());
    }
    // open window
    if (item.getType() == "marker") {
        item.placemark.openInfoWindowHtml(html);
    } else {
        item.map.openInfoWindowHtml(item.getInfoPoint(), html);
    }
    // deselect when window is closed
    item.closeListener = GEvent.addListener(item.map, "infowindowclose", function() { 
        // deselect
        item.selected = false;
        // kill self
        GEvent.removeListener(item.closeListener);
    });
};

/**
 * Open info window function using ajax-loaded text in map window
 */
TimeMapItem.openInfoWindowAjax = function() {
    var item = this;
    if (!item.opts.infoHtml) { // load content via AJAX
        if (item.opts.infoUrl) {
            GDownloadUrl(item.opts.infoUrl, function(result) {
                    item.opts.infoHtml = result;
                    item.openInfoWindow();
            });
            return;
        }
    }
    // fall back on basic function if content is loaded or URL is missing
    item.openInfoWindow = function() {
        TimeMapItem.openInfoWindowBasic.call(item);
        item.selected = true;
    };
    item.openInfoWindow();
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
};

/*----------------------------------------------------------------------------
 * Utility functions
 *---------------------------------------------------------------------------*/

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
 * @param {String} [ns]     XML namespace to look in
 * @return {String}         Tag value as string
 */
TimeMap.util.getTagValue = function(n, tag, ns) {
    var str = "",
        nList = TimeMap.util.getNodeList(n, tag, ns);
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
 * @example
 TimeMap.util.nsMap['georss'] = 'http://www.georss.org/georss';
 // find georss:point
 TimeMap.util.getNodeList(node, 'point', 'georss')
 */
TimeMap.util.nsMap = {};

/**
 * Cross-browser implementation of getElementsByTagNameNS.
 * Note: Expects any applicable namespaces to be mapped in
 * {@link TimeMap.util.nsMap}.
 *
 * @param {XML Node} n      Node in which to look for tag
 * @param {String} tag      Name of tag to look for
 * @param {String} [ns]     XML namespace to look in
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
 * @param {Boolean} [reversed]  Whether the points are KML-style lon/lat, rather than lat/lon
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
    if (!latlon) {
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
    if (latlon.length > 2) {
        latlon = latlon.slice(0, 2);
    }
    // deal with backwards (i.e. KML-style) coordinates
    if (reversed) {
        latlon.reverse();
    }
    return {
        "lat": trim(latlon[0]),
        "lon": trim(latlon[1])
    };
};

/**
 * Make TimeMap.init()-style polyline/polygons from a whitespace-delimited
 * string of coordinates (such as those in GeoRSS and KML).
 *
 * @param {Object} coords       String to convert
 * @param {Boolean} [reversed]  Whether the points are KML-style lon/lat, rather than lat/lon
 * @return {Object}             Formated coordinate array
 */
TimeMap.util.makePoly = function(coords, reversed) {
    var poly = [], 
        latlon,
        coordArr = TimeMap.util.trim(coords).split(/[\r\n\f ]+/);
    if (coordArr.length === 0) return [];
    // loop through coordinates
    for (var x=0; x<coordArr.length; x++) {
        latlon = (coordArr[x].indexOf(',') > 0) ?
            // comma-separated coordinates (KML-style lon/lat)
            coordArr[x].split(",") :
            // space-separated coordinates - increment to step by 2s
            [coordArr[x], coordArr[++x]];
        // deal with extra coordinates (i.e. KML altitude)
        if (latlon.length > 2) {
            latlon = latlon.slice(0, 2);
        }
        // deal with backwards (i.e. KML-style) coordinates
        if (reversed) {
            latlon.reverse();
        }
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
 * @param {Number} [precision] Precision indicator:<pre>
 *      3 (default): Show full date and time
 *      2: Show full date and time, omitting seconds
 *      1: Show date only
 *</pre>
 * @return {String}         Formatted string
 */
TimeMap.util.formatDate = function(d, precision) {
    // default to high precision
    precision = precision || 3;
    var str = "";
    if (d) {
        var yyyy = d.getUTCFullYear(),
            mo = d.getUTCMonth(),
            dd = d.getUTCDate();
        // deal with early dates
        if (yyyy < 1000) {
            return (yyyy < 1 ? (yyyy * -1 + "BC") : yyyy + "");
        }
        // check for date.js support
        if (d.toISOString && precision == 3) {
            return d.toISOString();
        }
        // otherwise, build ISO 8601 string
        var pad = function(num) {
            return ((num < 10) ? "0" : "") + num;
        };
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
    return 'getIcon' in pm ? 'marker' :
        'getVertex' in pm ? 
            ('setFillStyle' in pm ? 'polygon' : 'polyline') :
        false;
};

/**
 * Merge two or more objects, giving precendence to those
 * first in the list (i.e. don't overwrite existing keys).
 * Original objects will not be modified.
 *
 * @param {Object} obj1     Base object
 * @param {Object} [objN]   Objects to merge into base
 * @return {Object}         Merged object
 */
TimeMap.util.merge = function() {
    var opts = {}, args = arguments, obj, key, x, y;
    // must... make... subroutine...
    var mergeKey = function(o1, o2, key) {
        // note: existing keys w/undefined values will be overwritten
        if (o1.hasOwnProperty(key) && o2[key] === undefined) {
            o2[key] = o1[key];
        }
    };
    for (x=0; x<args.length; x++) {
        obj = args[x];
        if (obj) {
            // allow non-base objects to constrain what will be merged
            if (x > 0 && 'mergeOnly' in obj) {
                for (y=0; y<obj.mergeOnly.length; y++) {
                    key = obj.mergeOnly[y];
                    mergeKey(obj, opts, key);
                }
            }
            // otherwise, just merge everything
            else {
                for (key in obj) {
                    mergeKey(obj, opts, key);
                }
            }
        }
    }
    return opts;
};

/**
 * Attempt look up a key in an object, returning either the value,
 * undefined if the key is a string but not found, or the key if not a string 
 *
 * @param {String|Object} key   Key to look up
 * @param {Object} map          Object in which to look
 * @return {Object}             Value, undefined, or key
 */
TimeMap.util.lookup = function(key, map) {
    if (typeof(key) == 'string') {
        return map[key];
    }
    else {
        return key;
    }
};


/*----------------------------------------------------------------------------
 * Lookup maps
 * (need to be at end because some call util functions on initialization)
 *---------------------------------------------------------------------------*/

/**
 * @namespace
 * Lookup map of common timeline intervals.  
 * Add custom intervals here if you want to refer to them by key rather 
 * than as a function name.
 * @example
    TimeMap.init({
        bandIntervals: "hr",
        // etc...
    });
 *
 */
TimeMap.intervals = {
    /** second / minute */
    sec: [DateTime.SECOND, DateTime.MINUTE],
    /** minute / hour */
    min: [DateTime.MINUTE, DateTime.HOUR],
    /** hour / day */
    hr: [DateTime.HOUR, DateTime.DAY],
    /** day / week */
    day: [DateTime.DAY, DateTime.WEEK],
    /** week / month */
    wk: [DateTime.WEEK, DateTime.MONTH],
    /** month / year */
    mon: [DateTime.MONTH, DateTime.YEAR],
    /** year / decade */
    yr: [DateTime.YEAR, DateTime.DECADE],
    /** decade / century */
    dec: [DateTime.DECADE, DateTime.CENTURY]
};

/**
 * @namespace
 * Lookup map of Google map types. You could add 
 * G_MOON_VISIBLE_MAP, G_SKY_VISIBLE_MAP, or G_MARS_VISIBLE_MAP 
 * if you really needed them.
 * @example
    TimeMap.init({
        options: {
            mapType: "satellite"
        },
        // etc...
    });
 */
TimeMap.mapTypes = {
    /** Normal map */
    normal: G_NORMAL_MAP, 
    /** Satellite map */
    satellite: G_SATELLITE_MAP, 
    /** Hybrid map */
    hybrid: G_HYBRID_MAP,
    /** Physical (terrain) map */
    physical: G_PHYSICAL_MAP
};

/**
 * @namespace
 * Lookup map of supported date parser functions. 
 * Add custom date parsers here if you want to refer to them by key rather 
 * than as a function name.
 * @example
    TimeMap.init({
        datasets: [
            {
                options: {
                    dateParser: "gregorian"
                },
                // etc...
            }
        ],
        // etc...
    });
 */
TimeMap.dateParsers = {
    /** Hybrid parser: see {@link TimeMapDataset.hybridParser} */
    hybrid: TimeMapDataset.hybridParser,
    /** ISO8601 parser: parse ISO8601 datetime strings */
    iso8601: DateTime.parseIso8601DateTime,
    /** Gregorian parser: see {@link TimeMapDataset.gregorianParser} */
    gregorian: TimeMapDataset.gregorianParser
};
 
/**
 * @namespace
 * Pre-set event/placemark themes in a variety of colors. 
 * Add custom themes here if you want to refer to them by key rather 
 * than as a function name.
 * @example
    TimeMap.init({
        options: {
            theme: "orange"
        },
        datasets: [
            {
                options: {
                    theme: "yellow"
                },
                // etc...
            }
        ],
        // etc...
    });
 */
TimeMap.themes = {

    /** 
     * Red theme: <span style="background:#FE766A">#FE766A</span>
     * This is the default.
     *
     * @type TimeMapTheme
     */
    red: new TimeMapTheme(),
    
    /** 
     * Blue theme: <span style="background:#5A7ACF">#5A7ACF</span>
     *
     * @type TimeMapTheme
     */
    blue: new TimeMapTheme({
        iconImage: GIP + "blue-dot.png",
        color: "#5A7ACF",
        eventIconImage: "blue-circle.png"
    }),

    /** 
     * Green theme: <span style="background:#19CF54">#19CF54</span>
     *
     * @type TimeMapTheme
     */
    green: new TimeMapTheme({
        iconImage: GIP + "green-dot.png",
        color: "#19CF54",
        eventIconImage: "green-circle.png"
    }),

    /** 
     * Light blue theme: <span style="background:#5ACFCF">#5ACFCF</span>
     *
     * @type TimeMapTheme
     */
    ltblue: new TimeMapTheme({
        iconImage: GIP + "ltblue-dot.png",
        color: "#5ACFCF",
        eventIconImage: "ltblue-circle.png"
    }),

    /** 
     * Purple theme: <span style="background:#8E67FD">#8E67FD</span>
     *
     * @type TimeMapTheme
     */
    purple: new TimeMapTheme({
        iconImage: GIP + "purple-dot.png",
        color: "#8E67FD",
        eventIconImage: "purple-circle.png"
    }),

    /** 
     * Orange theme: <span style="background:#FF9900">#FF9900</span>
     *
     * @type TimeMapTheme
     */
    orange: new TimeMapTheme({
        iconImage: GIP + "orange-dot.png",
        color: "#FF9900",
        eventIconImage: "orange-circle.png"
    }),

    /** 
     * Yellow theme: <span style="background:#FF9900">#ECE64A</span>
     *
     * @type TimeMapTheme
     */
    yellow: new TimeMapTheme({
        iconImage: GIP + "yellow-dot.png",
        color: "#ECE64A",
        eventIconImage: "yellow-circle.png"
    }),

    /** 
     * Pink theme: <span style="background:#E14E9D">#E14E9D</span>
     *
     * @type TimeMapTheme
     */
    pink: new TimeMapTheme({
        iconImage: GIP + "pink-dot.png",
        color: "#E14E9D",
        eventIconImage: "pink-circle.png"
    })
};

// save to window
window.TimeMap = TimeMap;
window.TimeMapFilterChain = TimeMapFilterChain;
window.TimeMapDataset = TimeMapDataset;
window.TimeMapTheme = TimeMapTheme;
window.TimeMapItem = TimeMapItem;

})();
