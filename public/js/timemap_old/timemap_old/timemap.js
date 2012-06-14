/*! 
 * TimeMap Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**---------------------------------------------------------------------------
 * TimeMap
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 * The TimeMap object is intended to sync a SIMILE Timeline with a Google Map.
 * Dependencies: Google Maps API v2, SIMILE Timeline v1.2 or v2.2.0
 * Thanks to Jörn Clausen (http://www.oe-files.de) for initial concept and code.
 *---------------------------------------------------------------------------*/

 
/*----------------------------------------------------------------------------
 * TimeMap Class - holds references to timeline, map, and datasets
 *---------------------------------------------------------------------------*/
 
/**
 * Creates a new TimeMap with map placemarks synched to timeline events
 * This will create the visible map, but not the timeline, which must be initialized separately.
 *
 * @constructor
 * @param {element} tElement     The timeline element.
 * @param {element} mElement     The map element.
 * @param {Object} options       A container for optional arguments:
 *   {Boolean} syncBands            Whether to synchronize all bands in timeline
 *   {GLatLng} mapCenter            Point for map center
 *   {Number} mapZoom               Intial map zoom level
 *   {GMapType} mapType             The maptype for the map
 *   {Array} mapTypes               The set of maptypes available for the map
 *   {Boolean} showMapTypeCtrl      Whether to display the map type control
 *   {Boolean} showMapCtrl          Whether to show map navigation control
 *   {Boolean} hidePastFuture       Whether to hide map placemarks for events not visible on timeline
 *   {Boolean} showMomentOnly       Whether to hide all but the current moment (bad for instant events)
 *   {Boolean} centerMapOnItems     Whether to center and zoom the map based on loaded item positions
 *   {Function} openInfoWindow      Function redefining how info window opens
 *   {Function} closeInfoWindow     Function redefining how info window closes
 */
function TimeMap(tElement, mElement, options) {
    // save elements
    this.mElement = mElement;
    this.tElement = tElement;
    // initialize array of datasets
    this.datasets = {};
    // initialize filters
    this.filters = {};
    // initialize map bounds
    this.mapBounds = new GLatLngBounds();
    
    // set defaults for options
    // other options can be set directly on the map or timeline
    this.opts = options || {};   // make sure the options object isn't null
    // allow map types to be specified by key
    if (typeof(options['mapType']) == 'string') options['mapType'] = TimeMap.mapTypes[options['mapType']];
    this.opts.mapCenter =        options['mapCenter'] || new GLatLng(0,0); 
    this.opts.mapZoom =          options['mapZoom'] || 0;
    this.opts.mapType =          options['mapType'] || G_PHYSICAL_MAP;
    this.opts.mapTypes =         options['mapTypes'] || [G_NORMAL_MAP, G_SATELLITE_MAP, G_PHYSICAL_MAP];
    this.opts.syncBands =        ('syncBands' in options) ? options['syncBands'] : true;
    this.opts.showMapTypeCtrl =  ('showMapTypeCtrl' in options) ? options['showMapTypeCtrl'] : true;
    this.opts.showMapCtrl =      ('showMapCtrl' in options) ? options['showMapCtrl'] : true;
    this.opts.hidePastFuture =   ('hidePastFuture' in options) ? options['hidePastFuture'] : true;
    this.opts.showMomentOnly =   ('showMomentOnly' in options) ? options['showMomentOnly'] : false;
    this.opts.centerMapOnItems = ('centerMapOnItems' in options) ? options['centerMapOnItems'] : true;
    
    
    // initialize map
    if (GBrowserIsCompatible()) {
        this.map = new GMap2(this.mElement);
        if (this.opts.showMapCtrl)
            this.map.addControl(new GLargeMapControl());
        if (this.opts.showMapTypeCtrl)
            this.map.addControl(new GMapTypeControl());
        // drop all existing types
        for (var i=G_DEFAULT_MAP_TYPES.length-1; i>0; i--) {
            this.map.removeMapType(G_DEFAULT_MAP_TYPES[i]);
        }
        // you can't remove the last maptype, so add a new one first
        this.map.addMapType(this.opts.mapTypes[0]);
        this.map.removeMapType(G_DEFAULT_MAP_TYPES[0]);
        // add the rest of the new types
        for (var i=1; i<this.opts.mapTypes.length; i++)
            this.map.addMapType(this.opts.mapTypes[i]);
        this.map.enableDoubleClickZoom();
        this.map.enableScrollWheelZoom();
        this.map.enableContinuousZoom();
        // initialize map center and zoom
        this.map.setCenter(this.opts.mapCenter, this.opts.mapZoom);
        // must be called after setCenter, for reasons unclear
        this.map.setMapType(this.opts.mapType);
    }
}

/**
 * Current library version.
 */
TimeMap.version = "1.4";

/**
 * Intializes a TimeMap.
 *
 * This is an attempt to create a general initialization script that will
 * work in most cases. If you need a more complex initialization, write your
 * own script instead of using this one.
 *
 * The idea here is to throw all of the standard intialization settings into
 * a large object and then pass it to the TimeMap.init() function. The full
 * data format is outlined below, but if you leave elements off the script 
 * will use default settings instead.
 *
 * Call TimeMap.init() inside of an onLoad() function (or a jQuery 
 * $.(document).ready() function, or whatever you prefer). See the examples 
 * for usage.
 *
 * @param {Object} config   Full set of configuration options.
 *                          See examples/timemapinit_usage.js for format.
 */
TimeMap.init = function(config) {
    
    // check required elements
    if (!('mapId' in config) || !config['mapId']) {
        alert("TimeMap init: No map id was specified!");
        return;
    }
    if (!('timelineId' in config) || !config['timelineId']) {
        alert("TimeMap init: No timeline id was specified!");
        return;
    }
    
    // set defaults
    config = config || {}; // make sure the config object isn't null
    config['options'] = config['options'] || {};
    config['datasets'] = config['datasets'] || [];
    config['bandInfo'] = config['bandInfo'] || false;
    config['scrollTo'] = config['scrollTo'] || "earliest";
    if (!config['bandInfo']) {
        var intervals = config['bandIntervals'] || 
            config['options']['bandIntervals'] ||
            [Timeline.DateTime.WEEK, Timeline.DateTime.MONTH];
        // allow intervals to be specified by key
        if (typeof(intervals) == 'string') intervals = TimeMap.intervals[intervals];
        // save for later reference
        config['options']['bandIntervals'] = intervals;
        // make band info
        config['bandInfo'] = [
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
  		document.getElementById(config['timelineId']), 
		document.getElementById(config['mapId']),
		config['options']
    );
    
    // create the dataset objects
    var datasets = [];
    for (var x=0; x < config['datasets'].length; x++) {
        var ds = config['datasets'][x];
        var dsOptions = ds['options'] || {};
        dsOptions['title'] = ds['title'] || '';
        dsOptions['theme'] = ds['theme'] || undefined;
        if (ds['dateParser']) dsOptions['dateParser'] = ds['dateParser'];
        var dsId = ds['id'] || "ds" + x;
        datasets[x] = tm.createDataset(dsId, dsOptions);
        if (x > 0) {
            // set all to the same eventSource
            datasets[x].eventSource = datasets[0].eventSource;
        }
    }
    
    // set up timeline bands
    var bands = [];
    // ensure there's at least an empty eventSource
    var eventSource = (datasets[0] && datasets[0]['eventSource']) || new Timeline.DefaultEventSource();
    for (var x=0; x < config['bandInfo'].length; x++) {
        var bandInfo = config['bandInfo'][x];
        if (!(('eventSource' in bandInfo) && bandInfo['eventSource']==null))
            bandInfo['eventSource'] = eventSource;
        else bandInfo['eventSource'] = null;
        bands[x] = Timeline.createBandInfo(bandInfo);
        if (x > 0 && TimeMap.TimelineVersion() == "1.2") {
            // set all to the same layout
            bands[x].eventPainter.setLayout(bands[0].eventPainter.getLayout()); 
        }
    }
    // initialize timeline
    tm.initTimeline(bands);
    
    // set up load manager
    var loadMgr = {};
    loadMgr.count = 0;
    loadMgr.loadTarget = config['datasets'].length;
    loadMgr.ifLoadedFunction = function() {
        // custom function including timeline scrolling and layout
        if (config['dataLoadedFunction'])
            config['dataLoadedFunction'](tm);
        else {
            var d = new Date();
            // make sure there are events to scroll to
            if (eventSource.getCount() > 0) {
                if (config['scrollTo']=="earliest")
                    d = eventSource.getEarliestDate();
                else if (config['scrollTo']!="now" && config['scrollTo']!=null)
                    d = eventSource.getLatestDate();
                tm.timeline.getBand(0).setCenterVisibleDate(d);
            }
            tm.timeline.layout();
            // custom function to be called when data is loaded
            if (config['dataDisplayedFunction'])
                config['dataDisplayedFunction'](tm);
        }
    };
    loadMgr.ifLoaded = function() {
        this.count++;
        if (this.count == this.loadTarget)
            this.ifLoadedFunction();
    };
    
    // load data!
    for (var x=0; x < config['datasets'].length; x++) {
        (function(x) { // magic trick to deal with closure issues
            var data = config['datasets'][x]['data'];
            var ds = datasets[x];
            // use dummy function as default
            var dummy = function(data) { return data; }
            var preload = config['datasets'][x]['preloadFunction'] || dummy;
            var transform = config['datasets'][x]['transformFunction'] || dummy;
            switch(data['type']) {
                case 'basic':
                    // data already loaded
                    var items = preload(data['value']);
                    ds.loadItems(items, transform);
                    loadMgr.ifLoaded();
                    break;
                case 'json':
                    // data to be loaded from remote json
                    JSONLoader.read(data['url'], function(result) {
                        var items = preload(result);
                        ds.loadItems(items, transform);
                        loadMgr.ifLoaded();
                    });
                    break;
                case 'kml':
                case 'georss':
                    // data to be loaded from kml or rss file
                    var parserFunc = data['type']=='kml' ? 
                        TimeMapDataset.parseKML : TimeMapDataset.parseGeoRSS;
                    GDownloadUrl(data['url'], function(result) {
                        var items = parserFunc(result);
                        items = preload(items);
                        ds.loadItems(items, transform);
                	    loadMgr.ifLoaded();
                    });
                    break;
                case 'metaweb':
                    // data to be loaded from freebase query
                    Metaweb.read(data['query'], function(result) {
                        var items = preload(result);
                        ds.loadItems(result, transform);
                	    loadMgr.ifLoaded();
                    });
                    break;
            }
        })(x);
    }
    // return timemap object for later manipulation
    return tm;
}

// for backwards compatibility
var timemapInit = TimeMap.init;

/**
 * Map of common timeline intervals. Add custom intervals here if you
 * want to refer to them by key rather than as literals.
 */
TimeMap.intervals = {
    'sec': [Timeline.DateTime.SECOND, Timeline.DateTime.MINUTE],
    'min': [Timeline.DateTime.MINUTE, Timeline.DateTime.HOUR],
    'hr': [Timeline.DateTime.HOUR, Timeline.DateTime.DAY],
    'day': [Timeline.DateTime.DAY, Timeline.DateTime.WEEK],
    'wk': [Timeline.DateTime.WEEK, Timeline.DateTime.MONTH],
    'mon': [Timeline.DateTime.MONTH, Timeline.DateTime.YEAR],
    'yr': [Timeline.DateTime.YEAR, Timeline.DateTime.DECADE],
    'dec': [Timeline.DateTime.DECADE, Timeline.DateTime.CENTURY]
}

/**
 * Map of Google map types. Using keys rather than literals allows
 * for serialization of the map type.
 */
TimeMap.mapTypes = {
    'normal':G_NORMAL_MAP, 
    'satellite':G_SATELLITE_MAP, 
    'hybrid':G_HYBRID_MAP, 
    'physical':G_PHYSICAL_MAP, 
    'moon':G_MOON_VISIBLE_MAP, 
    'sky':G_SKY_VISIBLE_MAP
}

/**
 * Create an empty dataset object and add it to the timemap
 *
 * @param {String} id           The id of the dataset
 * @param {Object} options      A container for optional arguments for dataset constructor
 * @return {TimeMapDataset}     The new dataset object    
 */
TimeMap.prototype.createDataset = function(id, options) {
    options = options || {}; // make sure the options object isn't null
    if(!("title" in options)) options["title"] = id;
    var dataset = new TimeMapDataset(this, options);
    this.datasets[id] = dataset;
    // add event listener
    if  (this.opts.centerMapOnItems) {
        var tm = this;
        GEvent.addListener(dataset, 'itemsloaded', function() {
            // determine the zoom level from the bounds
            tm.map.setZoom(tm.map.getBoundsZoomLevel(tm.mapBounds));
            // determine the center from the bounds
            tm.map.setCenter(tm.mapBounds.getCenter());
        });
    }
    return dataset;
}

/**
 * Run a function on each dataset in the timemap. This is the preferred
 * iteration method, as it allows for future iterator options.
 *
 * @param {Function} f    The function to run
 */
TimeMap.prototype.each = function(f) {
    for (id in this.datasets) {
        f(this.datasets[id]);
    }
}

/**
 * Initialize the timeline - this must happen separately to allow full control of 
 * timeline properties.
 *
 * @param {BandInfo Array} bands    Array of band information objects for timeline
 */
TimeMap.prototype.initTimeline = function(bands) {
    
    // synchronize & highlight timeline bands
    for (var x=1; x < bands.length; x++) {
        if (this.opts.syncBands)
            bands[x].syncWith = (x-1);
        bands[x].highlight = true;
    }
    
    // initialize timeline
    this.timeline = Timeline.create(this.tElement, bands);
    
    // set event listeners
    var tm = this;
    // update map on timeline scroll
    this.timeline.getBand(0).addOnScrollListener(function() {
        tm.filter("map");
    });
    // update timeline on map move (no default functionality yet)
    GEvent.addListener(tm.map, "moveend", function() {
        tm.filter("timeline");
    });
    // hijack timeline popup window to open info window
    var painter = this.timeline.getBand(0).getEventPainter().constructor;
    painter.prototype._showBubble = function(x, y, evt) {
        evt.item.openInfoWindow();
    }
    
    // filter chain for map placemarks
    this.addFilterChain("map", 
        function(item) {
            item.showPlacemark();
        },
        function(item) {
            item.hidePlacemark();
        }
    );
    
    // filter: hide when dataset is hidden
    this.addFilter("map", function(item) {
        return item.dataset.visible;
    });
    
    // filter: hide off-timeline items
    if (this.opts.hidePastFuture) {
        this.addFilter("map", TimeMap.hidePastFuture);
    }
    // filter: hide all but the present moment - overridden by hidePastFuture
    else if (this.opts.showMomentOnly) {
        this.addFilter("map", TimeMap.showMomentOnly);
    }
    
    // add callback for window resize
    resizeTimerID = null;
    var oTimeline = this.timeline;
    window.onresize = function() {
        if (resizeTimerID == null) {
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
    if (!filters || !filters.chain || filters.chain.length == 0) return;
    // run items through filter
    this.each(function(ds) {
        ds.each(function(item) {
            F_LOOP: {
                for (var i = filters.chain.length - 1; i >= 0; i--){
                    if (!filters.chain[i](item)) {
                        // false condition
                        filters.off(item);
                        break F_LOOP;
                    }
                }
                // true condition
                filters.on(item);
            }
        });
    });
}

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
}

/**
 * Remove a filter chain
 *
 * @param {String} fid      Id of the filter chain
 */
TimeMap.prototype.removeFilterChain = function(fid, on, off) {
    this.filters[fid] = null;
}

/**
 * Add a function to a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * @param {Function} f      Function to add
 */
TimeMap.prototype.addFilter = function(fid, f) {
    if (this.filters[fid] && this.filters[fid].chain) 
        this.filters[fid].chain.push(f);
}

/**
 * Remove a function from a filter chain
 *
 * @param {String} fid      Id of the filter chain
 * XXX: Support index here
 */
TimeMap.prototype.removeFilter = function(fid) {
    if (this.filters[fid] && this.filters[fid].chain) 
        this.filters[fid].chain.pop();
}

/**
 * Static filter function: Hide items not shown on the timeline
 *
 * @param {TimeMapItem} item    Item to test for filter
 * @return {Boolean}            Whether to show the item
 */
TimeMap.hidePastFuture = function(item) {
    var topband = item.dataset.timemap.timeline.getBand(0);
    var maxVisibleDate = topband.getMaxVisibleDate().getTime();
    var minVisibleDate = topband.getMinVisibleDate().getTime();
    if (item.event != null) {
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
}

/**
 * Static filter function: Hide items not shown on the timeline
 *
 * @param {TimeMapItem} item    Item to test for filter
 * @return {Boolean}            Whether to show the item
 */
TimeMap.showMomentOnly = function(item) {
    var topband = item.dataset.timemap.timeline.getBand(0);
    var momentDate = topband.getCenterVisibleDate().getTime();
    if (item.event != null) {
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
}

/*----------------------------------------------------------------------------
 * TimeMapDataset Class - holds references to items and visual themes
 *---------------------------------------------------------------------------*/

/**
 * Create a new TimeMap dataset to hold a set of items
 *
 * @constructor
 * @param {TimeMap} timemap         Reference to the timemap object
 * @param {Object} options          Object holding optional arguments:
 *   {String} title                     Title of the dataset (for the legend)
 *   {String or theme object} theme     Theme settings.
 *   {String or Function} dateParser    Function to replace default date parser.
 *   {Function} openInfoWindow          Function redefining how info window opens
 *   {Function} closeInfoWindow         Function redefining how info window closes
 */
function TimeMapDataset(timemap, options) {
    // hold reference to timemap
    this.timemap = timemap;
    // initialize timeline event source
    this.eventSource = new Timeline.DefaultEventSource();
    // initialize array of items
    this.items = [];
    // for show/hide functions
    this.visible = true;
    
    // set defaults for options
    this.opts = options || {}; // make sure the options object isn't null
    this.opts.title = options["title"] || "";
    
    // get theme by key or object
    if (typeof(options["theme"]) == "string") 
        options["theme"] = TimeMapDataset.themes[options["theme"]];
    this.opts.theme = options["theme"] || this.timemap.opts["theme"] || new TimeMapDatasetTheme({});
    // allow icon path override in options or timemap options
    this.opts.theme.eventIconPath = options["eventIconPath"] || 
        this.timemap.opts.eventIconPath || this.opts.theme.eventIconPath;
    this.opts.theme.eventIcon = options["eventIconPath"] + this.opts.theme.eventIconImage;
    
    // allow for other data parsers (e.g. Gregorgian) by key or function
    if (typeof(options["dateParser"]) == "string") 
        options["dateParser"] = TimeMapDataset.dateParsers[options["dateParser"]];
    this.opts.dateParser = options["dateParser"] || TimeMapDataset.hybridParser;
    
    // get functions
    this.getItems = function() { return this.items; }
    this.getTitle = function() { return this.opts.title; }
}

/**
 * Parse dates with the ISO 8601 parser, then fall back on the Gregorian
 * parser if the first parse fails
 *
 * @param {String} s    String to parse into a Date object
 * @return {Date}       Parsed date or null
 */
TimeMapDataset.hybridParser = function(s) {
    var d = Timeline.DateTime.parseIso8601DateTime(s);
    if (!d) d = Timeline.DateTime.parseGregorianDateTime(s);
    return d;
}

/**
 * Map of supported date parsers. Add custom date parsers here if you
 * want to refer to them by key rather than as a function name.
 */
TimeMapDataset.dateParsers = {
    'hybrid': TimeMapDataset.hybridParser,
    'iso8601': Timeline.DateTime.parseIso8601DateTime,
    'gregorian': Timeline.DateTime.parseGregorianDateTime
}

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
}

/**
 * Add items to map and timeline. 
 * Each item has both a timeline event and a map placemark.
 *
 * @param {Object} data             Data to be loaded. See loadItem() below for the format.
 * @param {Function} transform      If data is not in the above format, transformation function to make it so
 */
TimeMapDataset.prototype.loadItems = function(data, transform) {
    for (var x=0; x < data.length; x++) {
        this.loadItem(data[x], transform);
    }
    GEvent.trigger(this, 'itemsloaded');
};

/*
 * Add one item to map and timeline. 
 * Each item has both a timeline event and a map placemark.
 *
 * @param {Object} data         Data to be loaded, in the following format:
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
 * @param {Function} transform  If data is not in the above format, transformation function to make it so
 */
TimeMapDataset.prototype.loadItem = function(data, transform) {
    // apply transformation, if any
    if (transform != undefined)
        data = transform(data);
    // transform functions can return a null value to skip a datum in the set
    if (data == null) return;
    
    // use item theme if provided, defaulting to dataset theme
    var options = data.options || {};
    if (typeof(options["theme"]) == "string") 
        options["theme"] = TimeMapDataset.themes[options["theme"]];
    var theme = options["theme"] || this.opts.theme;
    theme.eventIconPath = options["eventIconPath"] || this.opts.theme.eventIconPath;
    theme.eventIcon = theme.eventIconPath + theme.eventIconImage;
    
    var tm = this.timemap;
    
    // create timeline event
    var start = (data.start == undefined||data.start == "") ? null :
        this.opts.dateParser(data.start);
    var end = (data.end == undefined||data.end == "") ? null : 
        this.opts.dateParser(data.end);
    var instant = (data.end == undefined);
    var eventIcon = theme.eventIcon;
    var title = data.title;
    // allow event-less placemarks - these will be always present
    if (start != null) {
        if (TimeMap.TimelineVersion() == "1.2") {
            // attributes by parameter
            var event = new Timeline.DefaultEventSource.Event(start, end, null, null,
                instant, title, null, null, null, eventIcon, theme.eventColor, null);
        } else {
            // attributes in object
            var event = new Timeline.DefaultEventSource.Event({
                "start": start,
                "end": end,
                "instant": instant,
                "text": title,
                "icon": eventIcon,
                "color": theme.eventColor
            });
        }
    } else var event = null;
    
    // internal function: create map placemark
    // takes a data object (could be full data, could be just placemark)
    // returns an object with {placemark, type, point}
    var createPlacemark = function(pdata) {
        var placemark = null, type = "", point = null;
        // point placemark
        if ("point" in pdata) {
            point = new GLatLng(
                parseFloat(pdata.point["lat"]), 
                parseFloat(pdata.point["lon"])
            );
            // add point to visible map bounds
            if (tm.opts.centerMapOnItems) {
                tm.mapBounds.extend(point);
            }
            markerIcon = ("icon" in pdata) ? pdata["icon"] : theme.icon;
            placemark = new GMarker(point, { icon: markerIcon });
            type = "marker";
            point = placemark.getLatLng();
        }
        // polyline and polygon placemarks
        else if ("polyline" in pdata || "polygon" in pdata) {
            var points = [];
            if ("polyline" in pdata)
                var line = pdata.polyline;
            else var line = pdata.polygon;
            for (var x=0; x<line.length; x++) {
                point = new GLatLng(
                    parseFloat(line[x]["lat"]), 
                    parseFloat(line[x]["lon"])
                );
                points.push(point);
                // add point to visible map bounds
                if (tm.opts.centerMapOnItems) {
                    tm.mapBounds.extend(point);
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
                parseFloat(pdata.overlay["south"]), 
                parseFloat(pdata.overlay["west"])
            );
            var ne = new GLatLng(
                parseFloat(pdata.overlay["north"]), 
                parseFloat(pdata.overlay["east"])
            );
            // add to visible bounds
            if (tm.opts.centerMapOnItems) {
                tm.mapBounds.extend(sw);
                tm.mapBounds.extend(ne);
            }
            // create overlay
            var overlayBounds = new GLatLngBounds(sw, ne);
            placemark = new GGroundOverlay(pdata.overlay["image"], overlayBounds);
            type = "overlay";
            point = overlayBounds.getCenter();
        }
        return {
            "placemark": placemark,
            "type": type,
            "point": point
        };
    }
    
    // create placemark or placemarks
    var placemark = [], pdataArr = [], pdata=null, type = "", point = null;
    // array of placemark objects
    if ("placemarks" in data) pdataArr = data["placemarks"];
    else {
        // we have one or more single placemarks
        var types = ["point", "polyline", "polygon", "overlay"];
        for (var i=0; i<types.length; i++) {
            if (types[i] in data) {
                pdata = {};
                pdata[types[i]] = data[types[i]];
                pdataArr.push(pdata);
            }
        }
    }
    if (pdataArr) {
        for (var i=0; i<pdataArr.length; i++) {
            // create the placemark
            var p = createPlacemark(pdataArr[i]);
            // take the first point and type as a default
            if (!point) point = p.point;
            if (!type) type = p.type;
            placemark.push(p.placemark);
        }
    }
    // override type for arrays
    if (placemark.length > 1) type = "array";
    
    options["title"] = title;
    options["type"] = type || "none";
    options["theme"] = theme;
    // check for custom infoPoint and convert to GLatLng
    if (options["infoPoint"]) {
        options["infoPoint"] = new GLatLng(
            parseFloat(options.infoPoint['lat']), 
            parseFloat(options.infoPoint['lon'])
        );
    } else options["infoPoint"] = point;
    
    // create item and cross-references
    var item = new TimeMapItem(placemark, event, this, options);
    // add event if it exists
    if (event != null) {
        event.item = item;
        this.eventSource.add(event);
    }
    // add placemark(s) if any exist
    if (placemark.length > 0) {
        for (var i=0; i<placemark.length; i++) {
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
 * Predefined visual themes for datasets, based on Google markers
 *---------------------------------------------------------------------------*/
 
/**
 * Create a new theme for a TimeMap dataset, defining colors and images
 *
 * @constructor
 * @param {Object} options          A container for optional arguments:
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
 */
function TimeMapDatasetTheme(options) {
    // work out various defaults - the default theme is Google's reddish color
    options = options || {};
    
    if (!options['icon']) {
        // make new red icon
        var markerIcon = new GIcon(G_DEFAULT_ICON);
        this.iconImage = options['iconImage'] 
            || "http://www.google.com/intl/en_us/mapfiles/ms/icons/red-dot.png";
        markerIcon.image = this.iconImage;
        markerIcon.iconSize = new GSize(32, 32);
        markerIcon.shadow = "http://www.google.com/intl/en_us/mapfiles/ms/icons/msmarker.shadow.png"
        markerIcon.shadowSize = new GSize(59, 32);
    }
    
    this.icon =              options['icon'] || markerIcon;
    this.color =             options['color'] || "#FE766A";
    this.lineColor =         options['lineColor'] || this.color;
    this.polygonLineColor =  options['polygonLineColor'] || this.lineColor;
    this.lineOpacity =       options['lineOpacity'] || 1;
    this.polgonLineOpacity = options['polgonLineOpacity'] || this.lineOpacity;
    this.lineWeight =        options['lineWeight'] || 2;
    this.polygonLineWeight = options['polygonLineWeight'] || this.lineWeight;
    this.fillColor =         options['fillColor'] || this.color;
    this.fillOpacity =       options['fillOpacity'] || 0.25;
    this.eventColor =        options['eventColor'] || this.color;
    this.eventIconPath =     options['eventIconPath'] || "timemap/images/";
    this.eventIconImage =    options['eventIconImage'] || "red-circle.png";
    this.eventIcon =         options['eventIcon'] || this.eventIconPath + this.eventIconImage;
}

TimeMapDataset.redTheme = function(options) {
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.blueTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/blue-dot.png";
    options['color'] = "#5A7ACF";
    options['eventIconImage'] = "blue-circle.png";
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.greenTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/green-dot.png";
    options['color'] =          "#19CF54";
    options['eventIconImage'] = "green-circle.png";
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.ltblueTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/ltblue-dot.png";
    options['color'] =          "#5ACFCF";
    options['eventIconImage'] = "ltblue-circle.png";
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.purpleTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/purple-dot.png";
    options['color'] =          "#8E67FD";
    options['eventIconImage'] = "purple-circle.png";
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.orangeTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/orange-dot.png";
    options['color'] =          "#FF9900";
    options['eventIconImage'] = "orange-circle.png";
    return new TimeMapDatasetTheme(options);
}

TimeMapDataset.yellowTheme = function(options) {
    options = options || {};
    options['iconImage'] = "http://www.google.com/intl/en_us/mapfiles/ms/icons/yellow-dot.png";
    options['color'] =          "#ECE64A";
    options['eventIconImage'] = "yellow-circle.png";
    return new TimeMapDatasetTheme(options);
}

/**
 * Map of themes. Add custom themes to this map if you want
 * to load them by key rather than as an object.
 */
TimeMapDataset.themes = {
    'red': TimeMapDataset.redTheme(),
    'blue': TimeMapDataset.blueTheme(),
    'green': TimeMapDataset.greenTheme(),
    'ltblue': TimeMapDataset.ltblueTheme(),
    'orange': TimeMapDataset.orangeTheme(),
    'yellow': TimeMapDataset.yellowTheme(),
    'purple': TimeMapDataset.purpleTheme()
};


/*----------------------------------------------------------------------------
 * TimeMapItem Class - holds references to map placemark and timeline event
 *---------------------------------------------------------------------------*/

/**
 * Create a new TimeMap item with a map placemark and a timeline event
 *
 * @constructor
 * @param {placemark} placemark     Placemark or array of placemarks (GMarker, GPolyline, etc)
 * @param {Event} event             The timeline event
 * @param {TimeMapDataset} dataset  Reference to the parent dataset object
 * @param {Object} options          A container for optional arguments:
 *   {String} title                     Title of the item
 *   {String} description               Plain-text description of the item
 *   {String} type                      Type of map placemark used (marker. polyline, polygon)
 *   {GLatLng} infoPoint                Point indicating the center of this item
 *   {String} infoHtml                  Full HTML for the info window
 *   {String} infoUrl                   URL from which to retrieve full HTML for the info window
 *   {Function} openInfoWindow          Function redefining how info window opens
 *   {Function} closeInfoWindow         Function redefining how info window closes
 */
function TimeMapItem(placemark, event, dataset, options) {
    // initialize vars
    this.event =     event;
    this.dataset =   dataset;
    this.map =       dataset.timemap.map;
    
    // initialize placemark(s) with some type juggling
    if (placemark && TimeMap.isArray(placemark) && placemark.length == 0) placemark = null;
    if (placemark && placemark.length == 1) placemark = placemark[0];
    this.placemark = placemark;
    
    // set defaults for options
    this.opts = options || {};
    this.opts.type =        options['type'] || '';
    this.opts.title =       options['title'] || '';
    this.opts.description = options['description'] || '';
    this.opts.infoPoint =   options['infoPoint'] || null;
    this.opts.infoHtml =    options['infoHtml'] || '';
    this.opts.infoUrl =     options['infoUrl'] || '';
    
    // get functions
    this.getType = function() { return this.opts.type; };
    this.getTitle = function() { return this.opts.title; };
    this.getInfoPoint = function() { 
        // default to map center if placemark not set
        return this.opts.infoPoint || this.map.getCenter(); 
    };
    
    // items initialize hidden
    this.visible = false;
    
    // show/hide functions - no action if placemark is null
    this.showPlacemark = function() {
        if (this.placemark) {
            if (this.getType() == "array") {
                for (var i=0; i<this.placemark.length; i++)
                    this.placemark[i].show();
            } else this.placemark.show();
            this.visible = true;
        }
    }
    this.hidePlacemark = function() {
        if (this.placemark) {
            if (this.getType() == "array") {
                for (var i=0; i<this.placemark.length; i++)
                    this.placemark[i].hide();
            } else this.placemark.hide();
            this.visible = false;
        }
        this.closeInfoWindow();
    }
    
    // allow for custom open/close functions, set at item, dataset, or timemap level
    this.openInfoWindow =   options['openInfoWindow'] ||
                            dataset.opts['openInfoWindow'] ||
                            dataset.timemap.opts['openInfoWindow'] ||
                            false;
    if (!this.openInfoWindow) {
        // load via AJAX if URL is provided
        if (this.opts.infoUrl != "")
            this.openInfoWindow = TimeMapItem.openInfoWindowAjax;
        // otherwise default to basic window
        else this.openInfoWindow = TimeMapItem.openInfoWindowBasic;    
    }
    this.closeInfoWindow = options['closeInfoWindow'] || TimeMapItem.closeInfoWindowBasic;
}

/**
 * Standard open info window function, using static text in map window
 */
TimeMapItem.openInfoWindowBasic = function() {
    var html = this.opts.infoHtml;
    // create content for info window if none is provided
    if (html == "") {
        html = '<div class="infotitle">' + this.opts.title + '</div>';
        if (this.opts.description != "") 
            html += '<div class="infodescription">' + this.opts.description + '</div>';
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
}

/**
 * Open info window function using ajax-loaded text in map window
 */
TimeMapItem.openInfoWindowAjax = function() {
    if (this.opts.infoHtml != "") { // already loaded - change to static
        this.openInfoWindow = TimeMapItem.openInfoWindowBasic;
        this.openInfoWindow();
    } else { // load content via AJAX
        if (this.opts.infoUrl != "") {
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
}

/**
 * Standard close window function, using the map window
 */
TimeMapItem.closeInfoWindowBasic = function() {
    if (this.getType() == "marker") {
        this.placemark.closeInfoWindow();
    } else {
        var infoWindow = this.map.getInfoWindow();
        // close info window if its point is the same as this item's point
        if (infoWindow.getPoint() == this.getInfoPoint() 
            && !infoWindow.isHidden())
                this.map.closeInfoWindow();
    }
}

/*----------------------------------------------------------------------------
 * Utility functions, attached to TimeMap to avoid namespace issues
 *---------------------------------------------------------------------------*/

/**
 * Convenience trim function
 * 
 * @param {String} str      String to trim
 * @return {String}         Trimmed string
 */
TimeMap.trim = function(str) {
    str = str && String(str) || '';
    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}

/**
 * Convenience array tester
 *
 * @param {Object} o        Object to test
 * @return {Boolean}        Whether the object is an array
 */
TimeMap.isArray = function(o) {   
    return o && !(o.propertyIsEnumerable('length')) && 
        typeof o === 'object' && typeof o.length === 'number';
}

/**
 * Get XML tag value as a string
 *
 * @param {XML Node} n      Node in which to look for tag
 * @param {String} tag      Name of tag to look for
 * @param {String} ns       Optional namespace
 * @return {String}         Tag value as string
 */
TimeMap.getTagValue = function(n, tag, ns) {
    var str = "";
    var nList = TimeMap.getNodeList(n, tag, ns);
    if (nList.length > 0) {
        var n = nList[0].firstChild;
        // fix for extra-long nodes
        // see http://code.google.com/p/timemap/issues/detail?id=36
        while(n != null) {
            str += n.nodeValue;
            n = n.nextSibling;
        }
    }
    return str;
};

/**
 * Empty container for mapping XML namespaces to URLs
 */
TimeMap.nsMap = {};

/**
 * Cross-browser implementation of getElementsByTagNameNS
 * Note: Expects any applicable namespaces to be mapped in
 * TimeMap.nsMap. XXX: There may be better ways to do this.
 *
 * @param {XML Node} n      Node in which to look for tag
 * @param {String} tag      Name of tag to look for
 * @param {String} ns       Optional namespace
 * @return {XML Node List}  List of nodes with the specified tag name
 */
TimeMap.getNodeList = function(n, tag, ns) {
    if (ns == undefined)
        // no namespace
        return n.getElementsByTagName(tag);
    if (n.getElementsByTagNameNS && TimeMap.nsMap[ns])
        // function and namespace both exist
        return n.getElementsByTagNameNS(TimeMap.nsMap[ns], tag);
    // no function, try the colon tag name
    return n.getElementsByTagName(ns + ':' + tag);
};

/**
 * Make TimeMap.init()-style points from a GLatLng, array, or string
 *
 * @param (Object) coords      GLatLng, array, or string to convert
 * @return (Object)
 */
TimeMap.makePoint = function(coords) {
    var latlon;
    // GLatLng
    if (coords.lat && coords.lng) {
        latlon = [coords.lat(), coords.lng()]
    }
    // array of coordinates
    if (TimeMap.isArray(coords)) latlon = coords;
    // string
    else {
        if (coords.indexOf(',') > -1) {
            // split on commas
            latlon = coords.split(",");
        } else {
            // split on whitespace
            latlon = coords.split(/[\r\n\f ]+/);
        }
    }
    return {
        "lat": TimeMap.trim(latlon[0]),
        "lon": TimeMap.trim(latlon[1])
    };
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
TimeMap.formatDate = function(d, precision) {
    // default to high precision
    precision = precision || 3;
    var str = "";
    if (d) {
        // check for date.js support
        if (d.toISOString) return d.toISOString();
        // otherwise, build ISO 8601 string
        var yyyy = d.getUTCFullYear(),
            mm = d.getUTCMonth(),
            dd = d.getUTCDate();
        str += yyyy + '-' + ((mm < 9) ? "0" : "") + (mm + 1 ) + '-' 
            + ((dd < 10) ? "0" : "") + dd;
        // show time if top interval less than a week
        if (precision > 1) {
            var hh = d.getUTCHours(),
                mm = d.getUTCMinutes(),
                ss = d.getUTCSeconds();
            str += 'T' + ((hh < 10) ? "0" : "") + hh + ':' 
                + ((mm < 10) ? "0" : "") + mm;
            // show seconds if the interval is less than a day
            if (precision > 2) {
                str += ((ss < 10) ? "0" : "") + ss;
            }
            str += 'Z';
        }
    }
    return str;
}

/**
 * Determine the SIMILE Timeline version
 * XXX: quite rough at the moment
 *
 * @return {String}     At the moment, only "1.2" or "2.2.0"
 */
TimeMap.TimelineVersion = function() {
    if (Timeline.DurationEventPainter) return "1.2";
    else return "2.2.0";
}