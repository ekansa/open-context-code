/* 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * Google Spreadsheet Loader
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */
 
/**
 * @class
 * Google spreadsheet loader factory - inherits from jsonp loader.
 *
 * <p>This is a loader for data from Google Spreadsheets. Takes an optional map
 * to indicate which columns contain which data elements; the default column
 * names (case-insensitive) are: title, description, start, end, lat, lon</p>
 * 
 * <p>See <a href="http://code.google.com/apis/spreadsheets/docs/2.0/reference.html#gsx_reference">http://code.google.com/apis/spreadsheets/docs/2.0/reference.html#gsx_reference</a>
 * for details on how spreadsheet column ids are derived. Note that date fields 
 * must be in yyyy-mm-dd format - you may need to set the cell format as "plain text" 
 * in the spreadsheet (Google's automatic date formatting won't work).</p>
 *
 * <p>The loader takes either a full URL, minus the JSONP callback function, or 
 * just the spreadsheet key. Note that the spreadsheet must be published.</p>
 *
 * <p>Depends on:</p>
 * <ul>
 *  <li>loaders/jsonp.js</li>
 * </ul>
 *
 * @example Usage in TimeMap.init():
 
    datasets: [
        {
            title: "Google Spreadsheet by key",
            type: "gss",
            options: {
                key: "pjUcDAp-oNIOjmx3LCxT4XA" // Spreadsheet key
            }
        },
        {
            title: "Google Spreadsheet by url",
            type: "gss",
            options: {
                url: "http://spreadsheets.google.com/feeds/list/pjUcDAp-oNIOjmx3LCxT4XA/1/public/values?alt=json-in-script&callback="
            }
        }
    ]
 *
 * @param {Object} options          All options for the loader:<pre>
 *   {String} key                       Key of spreadsheet to load, or
 *   {String} url                       Full JSONP url of spreadsheet to load
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 * @return {TimeMap.loaders.remote} Remote loader configured for Google Spreadsheets
 */
TimeMap.loaders.gss = function(options) {
    var loader = new TimeMap.loaders.jsonp(options);
    
    // use key if no URL was supplied
    if (!loader.url) {
        loader.url = "http://spreadsheets.google.com/feeds/list/" + 
            options.key + "/1/public/values?alt=json-in-script&callback=";
    }
        
    // column map
    loader.map = options.map;
    
    // preload function for spreadsheet data
    loader.preload = function(data) {
        return data["feed"]["entry"];
    };
    
    // transform function for spreadsheet data
    loader.transform = function(data) {
        // map spreadsheet column ids to corresponding TimeMap elements
        var fieldMap = loader.map || TimeMap.loaders.gss.map;
        var getField = function(f) {
            if (f in fieldMap && fieldMap[f]) {
                return data['gsx$' + fieldMap[f]]['$t'];
            } else return false;
        };
        var item = {
            title: getField("title"),
            start: getField("start"),
            point: {
                lat: getField("lat"),
                lon: getField("lon")
            },
            options: {
                description: getField("description")
            }
        };
        // hook for further transformation
        if (options.transformFunction) 
            item = options.transformFunction(item);
        return item;
    };

    return loader;
}

/**
 * 1:1 map of expected spreadsheet column ids.
 */
TimeMap.loaders.gss.map = {
    'title':'title',
    'description':'description',
    'start':'start',
    'end':'end',
    'lat':'lat',
    'lon':'lon'
};
