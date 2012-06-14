/*
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * Additional TimeMap manipulation functions.
 * Functions in this file are used to manipulate a TimeMap, TimeMapDataset, or
 * TimeMapItem after the initial load process.
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

/*globals TimeMap, TimeMapDataset, TimeMapItem, Timeline */
 
/*----------------------------------------------------------------------------
 * TimeMap manipulation: stuff affecting every dataset
 *---------------------------------------------------------------------------*/
 
/**
 * Delete all datasets, clearing them from map and timeline
 */
TimeMap.prototype.clear = function() {
    this.each(function(ds) {
        ds.clear();
    });
    this.datasets = [];
};

/**
 * Delete one dataset, clearing it from map and timeline
 *
 * @param id    Id of dataset to delete
 */
TimeMap.prototype.deleteDataset = function(id) {
    this.datasets[id].clear();
    delete this.datasets[id];
};

/**
 * Hides placemarks for a given dataset
 * 
 * @param {String} id   The id of the dataset to hide
 */
TimeMap.prototype.hideDataset = function (id){
    if (id in this.datasets) {
    	this.datasets[id].hide();
    }
};

/**
 * Hides all the datasets on the map
 */
TimeMap.prototype.hideDatasets = function(){
	this.each(function(ds) {
		ds.visible = false;
	});
    this.filter("map");
    this.filter("timeline");
    this.timeline.layout();
};

/**
 * Shows placemarks for a given dataset
 * 
 * @param {String} id   The id of the dataset to hide
 */
TimeMap.prototype.showDataset = function(id) {
    if (id in this.datasets) {
	    this.datasets[id].show();
    }
};

/**
 * Shows all the datasets on the map
 */
TimeMap.prototype.showDatasets = function() {
	this.each(function(ds) {
		ds.visible = true;
	});
    this.filter("map");
    this.filter("timeline");
    this.timeline.layout();
};
 
/**
 * Change the default map type
 *
 * @param {String or Object} mapType   New map type If string, looks up in TimeMap.mapTypes.
 */
TimeMap.prototype.changeMapType = function (mapType) {
    // check for no change
    if (mapType == this.opts.mapType) {
        return;
    }
    // look for mapType
    if (typeof(mapType) == 'string') {
        mapType = TimeMap.mapTypes[mapType];
    }
    // no mapType specified
    if (!mapType) {
        return;
    }
    // change it
    this.opts.mapType = mapType;
    this.map.setMapType(mapType);
};

/*----------------------------------------------------------------------------
 * TimeMap manipulation: stuff affecting the timeline
 *---------------------------------------------------------------------------*/

/**
 * Refresh the timeline, maintaining the current date
 */
TimeMap.prototype.refreshTimeline = function () {
    var topband = this.timeline.getBand(0);
    var centerDate = topband.getCenterVisibleDate();
    if (TimeMap.util.TimelineVersion() == "1.2") {
        topband.getEventPainter().getLayout()._laidout = false;
    }
    this.timeline.layout();
    topband.setCenterVisibleDate(centerDate);
};

/**
 * Change the intervals on the timeline.
 *
 * @param {String or Array} intervals   New intervals. If string, looks up in TimeMap.intervals.
 */
TimeMap.prototype.changeTimeIntervals = function (intervals) {
    // check for no change
    if (intervals == this.opts.bandIntervals) {
        return;
    }
    // look for intervals
    if (typeof(intervals) == 'string') {
        intervals = TimeMap.intervals[intervals];
    }
    // no intervals specified
    if (!intervals) {
        return;
    }
    this.opts.bandIntervals = intervals;
    // internal function - change band interval
    var changeInterval = function(band, interval) {
        band.getEther()._interval = Timeline.DateTime.gregorianUnitLengths[interval];
        band.getEtherPainter()._unit = interval;
    };
    // grab date
    var topband = this.timeline.getBand(0);
    var centerDate = topband.getCenterVisibleDate();
    // change interval for each band
    for (var x=0; x<this.timeline.getBandCount(); x++) {
        changeInterval(this.timeline.getBand(x), intervals[x]);
    }
    // re-layout timeline
    topband.getEventPainter().getLayout()._laidout = false;
    this.timeline.layout();
    topband.setCenterVisibleDate(centerDate);
};
 
/**
 * Scrolls the timeline the number of years passed (negative numbers scroll it back)
 * XXX: This should probably handle other intervals as well...
 *
 * @param {int} years    Number of years to scroll the timeline
*/
TimeMap.prototype.scrollTimeline = function (years) {
 	var topband = this.timeline.getBand(0);
 	var centerDate = topband.getCenterVisibleDate();
 	var centerYear = centerDate.getFullYear() + parseFloat(years);
 	centerDate.setFullYear(centerYear);
 	topband.setCenterVisibleDate(centerDate);
};


/*----------------------------------------------------------------------------
 * TimeMapDataset manipulation: global settings, stuff affecting every item
 *---------------------------------------------------------------------------*/

/**
 * Delete all items, clearing them from map and timeline
 */
TimeMapDataset.prototype.clear = function() {
    this.each(function(item) {
        item.clear();
    });
    this.items = [];
    this.timemap.timeline.layout();
};

/**
 * Delete one item, clearing it from map and timeline
 * 
 * @param item      Item to delete
 */
TimeMapDataset.prototype.deleteItem = function(item) {
    for (var x=0; x < this.items.length; x++) {
        if (this.items[x] == item) {
            item.clear();
            this.items.splice(x, 1);
            break;
        }
    }
    this.timemap.timeline.layout();
};

/**
 * Show dataset
 */
TimeMapDataset.prototype.show = function() {
    if (!this.visible) {
        this.visible = true;
        this.timemap.filter("map");
        this.timemap.filter("timeline");
        this.timemap.timeline.layout();
    }
};

/**
 * Hide dataset
 */
TimeMapDataset.prototype.hide = function() {
    if (this.visible) {
        this.visible = false;
        this.timemap.filter("map");
        this.timemap.filter("timeline");
        this.timemap.timeline.layout();
    }
};

 /**
 * Change the theme for every item in a dataset
 *
 * @param (TimeMapDatasetTheme) theme       New theme settings
 */
 TimeMapDataset.prototype.changeTheme = function(newTheme) {
    this.opts.theme = newTheme;
    this.each(function(item) {
        item.changeTheme(newTheme);
    });
    this.timemap.timeline.layout();
 };
 
 
/*----------------------------------------------------------------------------
 * TimeMapItem manipulation: manipulate events and placemarks
 *---------------------------------------------------------------------------*/

/** 
 * Show event and placemark
 */
TimeMapItem.prototype.show = function() {
    this.showEvent();
    this.showPlacemark();
    this.visible = true;
};

/** 
 * Hide event and placemark
 */
TimeMapItem.prototype.hide = function() {
    this.hideEvent();
    this.hidePlacemark();
    this.visible = false;
};

/**
 * Delete placemark from map and event from timeline
 */
TimeMapItem.prototype.clear = function() {
    if (this.event) {
        // this is just ridiculous
        this.dataset.timemap.timeline.getBand(0)
            .getEventSource()._events._events.remove(this.event);
    }
    if (this.placemark) {
        this.hidePlacemark();
        var f = function(p) {
            try {
                this.map.removeOverlay(p);
            } catch(e) {}
        };
        if (this.getType() == "array") {
            for (var i=0; i<this.placemark.length; i++) {
                f(this.placemark[i]);
            }
        } else {
            f(this.placemark);
        }
    }
    this.event = this.placemark = null;
};

 /**
 * Create a new event for the item.
 * 
 * @param (Date) s      Start date for the event
 * @param (Date) e      (Optional) End date for the event
 */
TimeMapItem.prototype.createEvent = function(s, e) {
    var instant = (e === undefined);
    var title = this.getTitle();
    // create event
    var event = new Timeline.DefaultEventSource.Event(s, e, null, null, instant, title, 
        null, null, null, this.opts.theme.eventIcon, this.opts.theme.eventColor, null);
    // add references
    event.item = this;
    this.event = event;
    this.dataset.eventSource.add(event);
};
 
 /**
 * Change the theme for an item
 *
 * @param theme   New theme settings
 */
 TimeMapItem.prototype.changeTheme = function(newTheme) {
    this.opts.theme = newTheme;
    // change placemark
    if (this.placemark) {
        // internal function - takes type, placemark
        var changePlacemark = function(pm, type, theme) {
            type = type || TimeMap.util.getPlacemarkType(pm);
            switch (type) {
                case "marker":
                    pm.setImage(theme.icon.image);
                    break;
                case "polygon":
                    pm.setFillStyle({
                        'color': newTheme.fillColor,
                        'opacity': newTheme.fillOpacity
                    });
                    // no break to get stroke style too
                case "polyline":
                    pm.setStrokeStyle({
                        'color': newTheme.lineColor,
                        'weight': newTheme.lineWeight,
                        'opacity': newTheme.lineOpacity
                    });
                    break;
            }
        };
        if (this.getType() == 'array') {
            for (var i=0; i<this.placemark.length; i++) {
                changePlacemark(this.placemark[i], false, newTheme);
            }
        } else {
            changePlacemark(this.placemark, this.getType(), newTheme);
        }
    }
    // change event
    if (this.event) {
        this.event._color = newTheme.eventColor;
        this.event._icon = newTheme.eventIcon;
    }
};

/** 
 * Find the next item chronologically
 *
 * @param {Boolean} inDataset   Whether to only look in this item's dataset
 * @return {TimeMapItem}        Next item, if any
 */
TimeMapItem.prototype.getNext = function(inDataset) {
    if (!this.event) {
        return;
    }
    var eventsource = this.dataset.timemap.timeline.getBand(0).getEventSource();
    // iterator dates are non-inclusive, hence the juggle here
    var i = eventsource.getEventIterator(this.event.getStart(), 
        new Date(eventsource.getLatestDate().getTime() + 1));
    var next = null;
    while (next === null) {
        if (i.hasNext()) {
            next = i.next().item;
            if (inDataset && next.dataset != this.dataset) {
                next = null;
            }
        } else {
            break;
        }
    }
    return next;
};
