/*
 * Timemap.js Copyright 2010 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */
 
/**
 * @fileOverview
 * KML Loader
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

/*globals GXml, TimeMap */

/**
 * @class
 * KML loader: load KML files.
 *
 * <p>This is a loader class for KML files. Currently supports all geometry
 * types (point, polyline, polygon, and overlay) and multiple geometries. Supports loading
 * <a href="http://code.google.com/apis/kml/documentation/extendeddata.html">ExtendedData</a>
 * through the extendedData parameter.
 * </p>
 *
 * @augments TimeMap.loaders.xml
 * @requires loaders/xml.js
 * @requires param.js
 * @borrows TimeMap.loaders.kml.parse as #parse
 *
 * @example
TimeMap.init({
    datasets: [
        {
            title: "KML Dataset",
            type: "kml",
            options: {
                url: "mydata.kml"   // Must be local
            }
        }
    ],
    // etc...
});
 * @see <a href="../../examples/kenya.html">KML Example</a>
 * @see <a href="../../examples/kml_extendeddata.html">KML ExtendedData Example</a>
 *
 * @param {Object} options          All options for the loader
 * @param {String} options.url              URL of KML file to load (NB: must be local address)
 * @param {String[]} [options.extendedData] Array of names for ExtendedData data elements
 * @param {mixed} [options[...]]            Other options (see {@link TimeMap.loaders.xml})
 * @return {TimeMap.loaders.xml}    Loader configured for KML
 */
TimeMap.loaders.kml = function(options) {
    var loader = new TimeMap.loaders.xml(options),
        tagMap = options.tagMap || {},
        extendedData = options.extendedData || [],
        tagName, x;
    
    // Add ExtendedData parameters to extra params
    for (x=0; x < extendedData.length; x++) {
        tagName = extendedData[x];
        loader.extraParams.push(
            new TimeMap.params.ExtendedDataParam(tagMap[tagName] || tagName, tagName)
        );
    }
    
    // set custom parser
    loader.parse = TimeMap.loaders.kml.parse;
    return loader;
};

/**
 * Static function to parse KML with time data.
 *
 * @param {XML string} kml      KML to be parsed
 * @return {TimeMapItem Array}  Array of TimeMapItems
 */
TimeMap.loaders.kml.parse = function(kml) {
    var items = [], data, kmlnode, placemarks, pm, i, j;
    kmlnode = GXml.parse(kml);
    
    // get TimeMap utilty functions
    // assigning to variables should compress better
    var util = TimeMap.util,
        getTagValue = util.getTagValue,
        getNodeList = util.getNodeList,
        makePoint = util.makePoint,
        makePoly = util.makePoly,
        formatDate = util.formatDate;
    
    // recursive time data search
    var findNodeTime = function(n, data) {
        var check = false;
        // look for instant timestamp
        var nList = getNodeList(n, "TimeStamp");
        if (nList.length > 0) {
            data.start = getTagValue(nList[0], "when");
            check = true;
        }
        // otherwise look for span
        else {
            nList = getNodeList(n, "TimeSpan");
            if (nList.length > 0) {
                data.start = getTagValue(nList[0], "begin");
                data.end = getTagValue(nList[0], "end") ||
                    // unbounded spans end at the present time
                    formatDate(new Date());
                check = true;
            }
        }
        // try looking recursively at parent nodes
        if (!check) {
            var pn = n.parentNode;
            if (pn.nodeName == "Folder" || pn.nodeName=="Document") {
                findNodeTime(pn, data);
            }
            pn = null;
        }
    };
    
    // look for placemarks
    placemarks = getNodeList(kmlnode, "Placemark");
    for (i=0; i<placemarks.length; i++) {
        pm = placemarks[i];
        data = { options: {} };
        // get title & description
        data.title = getTagValue(pm, "name");
        data.options.description = getTagValue(pm, "description");
        // get time information
        findNodeTime(pm, data);
        // find placemark(s)
        var nList, coords, pmobj;
        data.placemarks = [];
        // look for marker
        nList = getNodeList(pm, "Point");
        for (j=0; j<nList.length; j++) {
            pmobj = { point: {} };
            // get lat/lon
            coords = getTagValue(nList[j], "coordinates");
            pmobj.point = makePoint(coords, 1);
            data.placemarks.push(pmobj);
        }
        // look for polylines
        nList = getNodeList(pm, "LineString");
        for (j=0; j<nList.length; j++) {
            pmobj = { polyline: [] };
            // get lat/lon
            coords = getTagValue(nList[j], "coordinates");
            pmobj.polyline = makePoly(coords, 1);
            data.placemarks.push(pmobj);
        }
        // look for polygons
        nList = getNodeList(pm, "Polygon");
        for (j=0; j<nList.length; j++) {
            pmobj = { polygon: [] };
            // get lat/lon
            coords = getTagValue(nList[j], "coordinates");
            pmobj.polygon = makePoly(coords, 1);
            // XXX: worth closing unclosed polygons?
            data.placemarks.push(pmobj);
        }
        // look for any extra tags and/or ExtendedData specified
        this.parseExtra(data, pm);
        
        items.push(data);
    }
    
    // look for ground overlays
    placemarks = getNodeList(kmlnode, "GroundOverlay");
    for (i=0; i<placemarks.length; i++) {
        pm = placemarks[i];
        data = { options: {}, overlay: {} };
        // get title & description
        data.title = getTagValue(pm, "name");
        data.options.description = getTagValue(pm, "description");
        // get time information
        findNodeTime(pm, data);
        // get image
        nList = getNodeList(pm, "Icon");
        data.overlay.image = getTagValue(nList[0], "href");
        // get coordinates
        nList = getNodeList(pm, "LatLonBox");
        data.overlay.north = getTagValue(nList[0], "north");
        data.overlay.south = getTagValue(nList[0], "south");
        data.overlay.east = getTagValue(nList[0], "east");
        data.overlay.west = getTagValue(nList[0], "west");
        // look for any extra tags and/or ExtendedData specified
        this.parseExtra(data, pm);
        items.push(data);
    }
    
    // clean up
    kmlnode = placemarks = pm = nList = null;
    
    return items;
};

/**
 * @class
 * Class for parameters loaded from KML ExtendedData elements
 *
 * @augments TimeMap.params.OptionParam
 *
 * @constructor
 * @param {String} paramName        String name of the parameter
 * @param {String} [tagName]        Tag name, if different
 */
TimeMap.params.ExtendedDataParam = function(paramName, tagName) {
    return new TimeMap.params.OptionParam(paramName, {
    
        /**
         * Set a config object based on an ExtendedData element
         * @name TimeMap.params.ExtendedDataParam#setConfigKML
         * @function
         * 
         * @param {Object} config       Config object to modify
         * @param {XML NodeList} node   Parent node to look for tags in
         */
        setConfigXML: function(config, node) {
            var util = TimeMap.util,
                nList = util.getNodeList(node, "Data"),
                i;
            for (i=0; i<nList.length; i++) {
                if (nList[i].getAttribute("name") == tagName) {
                    this.setConfig(config, util.getTagValue(nList[i], "value"))
                }
            }
            node = nList = null;
        },
        
        sourceName: tagName
    
    });
};
