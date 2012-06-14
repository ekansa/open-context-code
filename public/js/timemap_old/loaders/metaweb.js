/* 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */
 
/**
 * @fileOverview
 * Metaweb Loader
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

/*----------------------------------------------------------------------------
 *  Loader 
 *
 */

/**
 * @class
 * Metaweb loader factory - inherits from jsonp loader
 *
 * <p>This is a loader for data from the Metaweb service at freebase.com. See
 * the API documentation at <a href="http://www.freebase.com/view/en/documentation">http://www.freebase.com/view/en/documentation</a> for
 * a description of how to write MQL queries. This code is based on code from
 * the API site.</p>
 *
 * <p>Depends on:</p>
 * <ul>
 *  <li>lib/json2.pack.js</li>
 *  <li>loaders/jsonp.js</li>
 * </ul>
 *
 * @example Usage in TimeMap.init():
 
    datasets: [
        {
            title: "Freebase Dataset",
            type: "metaweb",
            options: {
                query: [
                    {
                      // query here - see Metaweb API
                    }
                ],
                transformFunction: function(data) {
                    // map returned data to the expected format - see
                    // http://code.google.com/p/timemap/wiki/JsonFormat
                    return data;
                }
            }
        }
    ]
 *
 * @param {Object} options          All options for the loader:<pre>
 *   {Object} query                     MQL query to load
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 * @return {TimeMap.loaders.remote} Remote loader configured for MetaWeb
 */
TimeMap.loaders.metaweb = function(options) {
    var loader = new TimeMap.loaders.jsonp(options);
    
    // Host and service - default to freebase.com
    loader.HOST = options.host || "http://www.freebase.com";
    loader.QUERY_SERVICE = options.service || "/api/service/mqlread";
    
    // Metaweb preload functon
    loader.preload = function(data) {
        // Open outer envelope
        var innerEnvelope = data.qname;
        // Make sure the query was successful
        if (innerEnvelope.code.indexOf("/api/status/ok") != 0) {
            // uncomment for debugging
            /*
            // If error, get error message and throw
            var error = innerEnvelope.messages[0];
            throw error.code + ": " + error.message;
            */
            return [];
        }
        // Get result from inner envelope
        var result = innerEnvelope.result;
        return result;
    };
    
    // format the query URL for Metaweb
    var q = options.query || {};
    var querytext = encodeURIComponent(JSON.stringify({qname: {query: q}}));

    // Build the URL using encoded query text and the callback name
    loader.url = loader.HOST + loader.QUERY_SERVICE + "?queries=" + querytext + "&callback=";

    return loader;
}
