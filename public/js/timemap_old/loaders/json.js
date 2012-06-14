/* 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * JSON Loaders (JSONP, JSON String)
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

/**
 * @class
 * JSONP loader class - expects a service that takes a callback function name as
 * the last URL parameter.
 *
 * <p>The jsonp loader assumes that the JSON can be loaded from a url to which a 
 * callback function name can be appended, e.g. "http://www.test.com/getsomejson.php?callback="
 * The loader then appends a nonce function name which the JSON should include.
 * This works for services like Google Spreadsheets, etc., and accepts remote URLs.</p>
 *
 * @example Usage in TimeMap.init():
 
    datasets: [
        {
            title: "JSONP Dataset",
            type: "jsonp",
            options: {
                url: "http://www.test.com/getsomejson.php?callback="
            }
        }
    ]
 *
 * @constructor
 * @param {Object} options          All options for the loader:<pre>
 *   {Array} url                        URL of JSON service to load, callback name left off
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 */
TimeMap.loaders.jsonp = function(options) {
    // get standard functions
    TimeMap.loaders.mixin(this, options);
    // get URL to load
    this.url = options.url;
}

/**
 * JSONP load function.
 *
 * @param {TimeMapDataset} dataset  Dataset to load data into
 * @param {Function} callback       Function to call once data is loaded
 */
TimeMap.loaders.jsonp.prototype.load = function(dataset, callback) {
    var loader = this;
    // get items
    TimeMap.loaders.jsonp.read(this.url, function(result) {
        // load
        items = loader.preload(result);
        dataset.loadItems(items, loader.transform);
        // callback
        callback();
    });
}

/**
 * Static - for naming anonymous callback functions
 * @type int
 */
TimeMap.loaders.jsonp.counter = 0;

/**
 * Static - reads JSON from a URL, assuming that the service is set up to apply
 * a callback function specified in the URL parameters.
 *
 * @param {String}      jsonUrl     URL to load, missing the callback function name
 * @param {function}    f           Callback function to apply to returned data
 */
TimeMap.loaders.jsonp.read = function(url, f) {
    // Define a unique function name
    var callbackName = "_" + TimeMap.loaders.jsonp.counter++;

    TimeMap.loaders.jsonp[callbackName] = function(result) {
        // Pass result to user function
        f(result);
        // Delete the callback function
        delete TimeMap.loaders.jsonp[callbackName];
    };

    // Create a script tag, set its src attribute and add it to the document
    // This triggers the HTTP request and submits the query
    var script = document.createElement("script");
    script.src = url + "TimeMap.loaders.jsonp." + callbackName;
    document.body.appendChild(script);
};

/**
 * @class
 * JSON string loader factory - expects a plain JSON array.
 * Inherits from remote loader.
 *
 * <p>The json_string loader assumes an array of items in plain JSON, with no
 * callback function - this will require a local URL.</p>
 *
 * <p>Depends on:</p>
 * <ul>
 *  <li>lib/json2.pack.js</li>
 * </ul>
 *
 * @example Usage in TimeMap.init():
 
    datasets: [
        {
            title: "JSON String Dataset",
            type: "json_string",
            options: {
                url: "mydata.json"    // Must be a local URL
            }
        }
    ]
 *
 * @param {Object} options          All options for the loader:<pre>
 *   {Array} url                        URL of JSON service to load, callback name left off
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 * @return {TimeMap.loaders.remote} Remote loader configured for JSON strings
 */
TimeMap.loaders.json_string = function(options) {
    var loader = new TimeMap.loaders.remote(options);
    loader.parse = JSON.parse;
    return loader;
}

// Probably the default json loader should be json_string, not
// jsonp. I may change this in the future, so I'd encourage you to use
// the specific one you want.
TimeMap.loaders.json = TimeMap.loaders.jsonp;
