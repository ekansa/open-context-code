/* 
 * Timemap.js Copyright 2008 Nick Rabinowitz.
 * Licensed under the MIT License (see LICENSE.txt)
 */

/**
 * @fileOverview
 * Flickr Loader
 *
 * @author Nick Rabinowitz (www.nickrabinowitz.com)
 */

/**
 * @class
 * Flickr loader factory - inherits from jsonp loader
 *
 * <p>This is a loader for data from Flickr. You probably want to use it with a
 * URL for the Flickr Geo Feed API: <a href="http://www.flickr.com/services/feeds/geo/">http://www.flickr.com/services/feeds/geo/</a></p>
 *
 * <p>The loader takes a full URL, minus the JSONP callback function.</p>
 *
 * <p>Depends on:</p>
 * <ul>
 *  <li>loaders/jsonp.js</li>
 * </ul>
 *
 * @example Usage in TimeMap.init():
 
    datasets: [
        {
            title: "Flickr Dataset",
            type: "flickr",
            options: {
                // This is just the latest geotagged photo stream - try adding
                // an "id" or "tag" or "photoset" parameter to get what you want
                url: "http://www.flickr.com/services/feeds/geo/?format=json&jsoncallback="
            }
        }
    ]
 *
 * @param {Object} options          All options for the loader:<pre>
 *   {String} url                       Full JSONP url of Flickr feed to load
 *   {Function} preloadFunction         Function to call on data before loading
 *   {Function} transformFunction       Function to call on individual items before loading
 * </pre>
 * @return {TimeMap.loaders.remote} Remote loader configured for Flickr
 */
TimeMap.loaders.flickr = function(options) {
    var loader = new TimeMap.loaders.jsonp(options);
    
    // preload function for Flickr feeds
    loader.preload = function(data) {
        return data["items"];
    };
    
    // transform function for Flickr feeds
    loader.transform = function(data) {
        var item = {
            title: data["title"],
            start: data["date_taken"],
            point: {
                lat: data["latitude"],
                lon: data["longitude"]
            },
            options: {
                description: data["description"]
                    .replace(/&gt;/g, ">")
                    .replace(/&lt;/g, "<")
                    .replace(/&quot;/g, '"')
            }
        };
        if (options.transformFunction) 
            item = options.transformFunction(item);
        return item;
    };

    return loader;
}
