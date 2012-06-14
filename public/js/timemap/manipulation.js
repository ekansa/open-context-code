/*
 * Timemap.js Copyright 2010 Nick Rabinowitz.
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
 
(function(){
    var window = this,
        TimeMap = window.TimeMap, 
        TimeMapDataset = window.TimeMapDataset, 
        TimeMapItem = window.TimeMapItem;
        
/*----------------------------------------------------------------------------
 * TimeMap manipulation: stuff affecting every dataset
 *---------------------------------------------------------------------------*/
 
/**
 * Delete all datasets, clearing them from map and timeline. Note
 * that this is more efficient than calling clear() on each dataset.
 */
TimeMap.prototype.clear = function() {
    var tm = this;
    tm.eachItem(function(item) {
        item.event = item.placemark = null;
    });
    tm.map.clearOverlays();
    tm.eventSource.clear();
    tm.datasets = [];
};

/**
 * Delete one dataset, clearing it from map and timeline
 *
 * @param {String} id    Id of dataset to delete
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
    var tm = this;
	tm.each(function(ds) {
		ds.visible = false;
	});
    tm.filter("map");
    tm.filter("timeline");
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
    var tm = this;
	tm.each(function(ds) {
		ds.visible = true;
	});
    tm.filter("map");
    tm.filter("timeline");
};
 
/**
 * Change the default map type
 *
 * @param {String|Object} mapType   New map type If string, looks up in TimeMap.mapTypes.
 */
TimeMap.prototype.changeMapType = function (mapType) {
    var tm = this;
    // check for no change
    if (mapType == tm.opts.mapType) {
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
    tm.opts.mapType = mapType;
    tm.map.setMapType(mapType);
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
 * @param {String|Array} intervals   New intervals. If string, looks up in TimeMap.intervals.
 */
TimeMap.prototype.changeTimeIntervals = function (intervals) {
    var tm = this;
    // check for no change
    if (intervals == tm.opts.bandIntervals) {
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
    tm.opts.bandIntervals = intervals;
    // internal function - change band interval
    function changeInterval(band, interval) {
        band.getEther()._interval = Timeline.DateTime.gregorianUnitLengths[interval];
        band.getEtherPainter()._unit = interval;
    };
    // grab date
    var topband = tm.timeline.getBand(0),
        centerDate = topband.getCenterVisibleDate(),
        x;
    // change interval for each band
    for (x=0; x<tm.timeline.getBandCount(); x++) {
        changeInterval(tm.timeline.getBand(x), intervals[x]);
    }
    // re-layout timeline
    topband.getEventPainter().getLayout()._laidout = false;
    tm.timeline.layout();
    topband.setCenterVisibleDate(centerDate);
};


/*----------------------------------------------------------------------------
 * TimeMapDataset manipulation: global settings, stuff affecting every item
 *---------------------------------------------------------------------------*/

/**
 * Delete all items, clearing them from map and timeline
 */
TimeMapDataset.prototype.clear = function() {
    var ds = this;
    ds.each(function(item) {
        item.clear();
    });
    ds.items = [];
    ds.timemap.timeline.layout();
};

/**
 * Delete one item, clearing it from map and timeline
 * 
 * @param {TimeMapItem} item      Item to delete
 */
TimeMapDataset.prototype.deleteItem = function(item) {
    var ds = this, x;
    for (x=0; x < ds.items.length; x++) {
        if (ds.items[x] == item) {
            item.clear();
            ds.items.splice(x, 1);
            break;
        }
    }
    ds.timemap.timeline.layout();
};

/**
 * Show dataset
 */
TimeMapDataset.prototype.show = function() {
    var ds = this,
        tm = ds.timemap;
    if (!ds.visible) {
        ds.visible = true;
        tm.filter("map");
        tm.filter("timeline");
        tm.timeline.layout();
    }
};

/**
 * Hide dataset
 */
TimeMapDataset.prototype.hide = function() {
    var ds = this,
        tm = ds.timemap;
    if (ds.visible) {
        ds.visible = false;
        tm.filter("map");
        tm.filter("timeline");
    }
};

 /**
 * Change the theme for every item in a dataset
 *
 * @param {TimeMapTheme} theme       New theme settings
 */
 TimeMapDataset.prototype.changeTheme = function(newTheme) {
    var ds = this;
    ds.opts.theme = newTheme;
    ds.each(function(item) {
        item.changeTheme(newTheme);
    });
    ds.timemap.timeline.layout();
 };
 
 
/*----------------------------------------------------------------------------
 * TimeMapItem manipulation: manipulate events and placemarks
 *---------------------------------------------------------------------------*/

/** 
 * Show event and placemark
 */
TimeMapItem.prototype.show = function() {
    var item = this;
    item.showEvent();
    item.showPlacemark();
    item.visible = true;
};

/** 
 * Hide event and placemark
 */
TimeMapItem.prototype.hide = function() {
    var item = this;
    item.hideEvent();
    item.hidePlacemark();
    item.visible = false;
};

/**
 * Delete placemark from map and event from timeline
 */
TimeMapItem.prototype.clear = function() {
    var item = this,
        i;
    if (item.event) {
        // this is just ridiculous
        item.dataset.timemap.timeline.getBand(0)
            .getEventSource()._events._events.remove(item.event);
    }
    if (item.placemark) {
        item.hidePlacemark();
        function removeOverlay(p) {
            try {
                item.map.removeOverlay(p);
            } catch(e) {}
        };
        if (item.getType() == "array") {
            for (i=0; i<item.placemark.length; i++) {
                removeOverlay(item.placemark[i]);
            }
        } else {
            removeOverlay(item.placemark);
        }
    }
    item.event = item.placemark = null;
};

 /**
 * Create a new event for the item.
 * 
 * @param {Date} s      Start date for the event
 * @param {Date} e      (Optional) End date for the event
 */
TimeMapItem.prototype.createEvent = function(s, e) {
    var item = this,
        theme = item.opts.theme,
        instant = (e === undefined),
        title = item.getTitle();
    // create event
    var event = new Timeline.DefaultEventSource.Event(s, e, null, null, instant, title, 
        null, null, null, theme.eventIcon, theme.eventColor, null);
    // add references
    event.item = item;
    item.event = event;
    item.dataset.eventSource.add(event);
};
 
 /**
 * Change the theme for an item
 *
 * @param {TimeMapTheme} theme   New theme settings
 */
 TimeMapItem.prototype.changeTheme = function(newTheme) {
    var item = this,
        type = item.getType(),
        event = item.event,
        placemark = item.placemark,
        i;
    item.opts.theme = newTheme;
    // change placemark
    if (placemark) {
        // internal function - takes type, placemark
        function changePlacemark(pm, type, theme) {
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
        if (type == 'array') {
            for (i=0; i<placemark.length; i++) {
                changePlacemark(placemark[i], false, newTheme);
            }
        } else {
            changePlacemark(placemark, type, newTheme);
        }
    }
    // change event
    if (event) {
        event._color = newTheme.eventColor;
        event._icon = newTheme.eventIcon;
    }
};

/** 
 * Find the next or previous item chronologically
 *
 * @param {Boolean} [backwards=false]   Whether to look backwards (i.e. find previous) 
 * @param {Boolean} [inDataset=false]   Whether to only look in this item's dataset
 * @return {TimeMapItem}                Next/previous item, if any
 */
TimeMapItem.prototype.getNextPrev = function(backwards, inDataset) {
    var item = this,
        eventSource = item.dataset.timemap.timeline.getBand(0).getEventSource(),
        // iterator dates are non-inclusive, hence the juggle here
        i = backwards ? 
            eventSource.getEventReverseIterator(
                new Date(eventSource.getEarliestDate().getTime() - 1),
                item.event.getStart()) :
            eventSource.getEventIterator(
                item.event.getStart(), 
                new Date(eventSource.getLatestDate().getTime() + 1)
            ),
        next = null;
    if (!item.event) {
        return;
    }
    while (next === null) {
        if (i.hasNext()) {
            next = i.next().item;
            if (inDataset && next.dataset != item.dataset) {
                next = null;
            }
        } else {
            break;
        }
    }
    return next;
};

/** 
 * Find the next item chronologically
 *
 * @param {Boolean} [inDataset=false]   Whether to only look in this item's dataset
 * @return {TimeMapItem}                Next item, if any
 */
TimeMapItem.prototype.getNext = function(inDataset) {
    return this.getNextPrev(false, inDataset);
}

/** 
 * Find the previous item chronologically
 *
 * @requires Timeline v.2.2.0 or greater
 *
 * @param {Boolean} [inDataset=false]   Whether to only look in this item's dataset
 * @return {TimeMapItem}                Next item, if any
 */
TimeMapItem.prototype.getPrev = function(inDataset) {
    return this.getNextPrev(true, inDataset);
}

})();