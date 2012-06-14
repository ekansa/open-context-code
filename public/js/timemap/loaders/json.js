/* 
 * Timemap.js Copyright 2010 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * JSON Loaders (JSONP, JSON String)
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */
 
// for JSLint
/*global TimeMap */

/**
 * @class
 * JSONP loader - expects a service that takes a callback function name as
 * the last URL parameter.
 *
 * <p>The jsonp loader assumes that the JSON can be loaded from a url to which a 
 * callback function name can be appended, e.g. "http://www.test.com/getsomejson.php?callback="
 * The loader then appends a nonce function name which the JSON should include.
 * This works for services like Google Spreadsheets, etc., and accepts remote URLs.</p>
 *
 * @augments TimeMap.loaders.remote
 *
 * @example
TimeMap.init({
    datasets: [
        {
            title: "JSONP Dataset",
            type: "jsonp",
            options: {
                url: "http://www.example.com/getsomejson.php?callback="
            }
        }
    ],
    // etc...
});
 *
 * @constructor
 * @param {Object} options          All options for the loader:
 * @param {String} options.url          URL of JSON service to load, callback name left off
 * @param {mixed} [options[...]]        Other options (see {@link TimeMap.loaders.remote})
 */
TimeMap.loaders.jsonp = function(options) {
    var loader = new TimeMap.loaders.remote(options);
    
    /**
     * JSONP load function. Creates a callback function and adds a script tag
     * with the appropriate URL to the document, triggering the HTTP request.
     * @name TimeMap.loaders.jsonp#load
     * @function
     *
     * @param {TimeMapDataset} dataset  Dataset to load data into
     * @param {Function} callback       Function to call once data is loaded
     */
     loader.load = function(dataset, callback) {
        // get loader callback name
        var callbackName = this.getCallbackName(dataset, callback),
            // create a script tag
            script = document.createElement("script");
        // set the src attribute and add to the document
        script.src = this.url + "TimeMap.loaders.cb." + callbackName;
        document.body.appendChild(script);
    };
    
    return loader;
};

/**
 * @class
 * JSON string loader factory - expects a plain JSON array.
 *
 * <p>The json_string loader assumes an array of items in plain JSON, with no
 * callback function - this will require a local URL.</p>
 * <p>Note that this loader requires lib/json2.pack.js.</p>
 *
 * @augments TimeMap.loaders.remote
 *
 * @requires lib/json2.pack.js
 *
 * @example
TimeMap.init({
    datasets: [
        {
            title: "JSON String Dataset",
            type: "json_string",
            options: {
                url: "mydata.json"    // Must be a local URL
            }
        }
    ],
    // etc...
});
 *
 * @param {Object} options          All options for the loader
 * @param {String} options.url          URL of JSON file to load
 * @param {mixed} [options[...]]        Other options (see {@link TimeMap.loaders.remote})
 */
TimeMap.loaders.json_string = function(options) {
    var loader = new TimeMap.loaders.remote(options);
    
    /**
     * Parse a JSON string into a JavaScript object, using the json2.js library.
     * @name TimeMap.loaders.json_string#parse
     * @function
     * @param {String} json     JSON string to parse
     * @returns {Object}        Parsed JavaScript object
     */
    loader.parse = JSON.parse;
    
    return loader;
};

// Probably the default json loader should be json_string, not
// jsonp. I may change this in the future, so I'd encourage you to use
// the specific one you want.
TimeMap.loaders.json = TimeMap.loaders.jsonp;
