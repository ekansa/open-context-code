/*
 * Shown below is the full data format accepted by TimeMap.init(). See the other 
 * example files for specific use cases.
 * NOTE: I haven't updated this in a while, and plan to move it to the wiki instead.
 */


TimeMap.init({
    mapId: "map",               // Id of map div element (required)
    timelineId: "timeline",     // Id of timeline div element (required)
    options: {                  // Various initialization options. Defaults shown:
        // Whether to synchronize all bands in timeline
        syncBands: true,                   
        // Point for map center
        mapCenter: new GLatLng(0,0), 
        // Intial map zoom level
        mapZoom: 0,
        // The maptype for the map
        mapType: G_PHYSICAL_MAP,
        // Whether to display the map type control
        showMapTypeCtrl: true,
        // Whether to show map navigation control
        showMapCtrl: true,  
        // Whether to hide map placemarks for events not visible on timeline
        hidePastFuture: true,
        // Whether to center and zoom the map based on loaded item positions
        centerMapOnItems: true,
        // Function redefining how info windows open for this timemap
        openInfoWindow: function() { 
            // your function here - "this" refers to the item
        },
        // Function redefining how info windows close for this timemap
        closeInfoWindow: function() { 
            // your function here - "this" refers to the item
        }
    },
    datasets: [                 // Array of datasets to show
        // Sample types of dataset shown below:
        {
            title: "Basic Dataset", // Title of the dataset
            // The dataset theme determines display options for the timeline 
            // events and map placemarks, including color and icon.
            // Use a preset or use TimeMapDatasetTheme() to make your own
            theme: TimeMapDataset.greenTheme(),
            options: {
                // Function redefining how info windows open for this dataset
                // This can also be set on a per-item basis
                openInfoWindow: function() { 
                    // your function here - "this" refers to the item
                },
                // Function redefining how info windows close for this dataset
                closeInfoWindow: function() { 
                    // your function here - "this" refers to the item
                }
            },
            data: {                 // Define the data for this dataset
                type: "basic",      // Data defined right in the javascript
                value: [            
                    // ... your data here
                ]
            }
            // preloadFunction and transformFunction accepted here, but
            // probably not necessary, as the data format will be correct
        },
        {
            title: "Remote JSON Dataset", 
            theme: TimeMapDataset.redTheme(),
            // It's unlikely that you'll ever need this, but you can change the
            // date parsing function if you want to use years earlier than 1000AD
            dateParser: Timeline.DateTime.parseGregorianDateTime,
            data: {
                type: "json",    // Data to be loaded in JSON from a remote URL
                // Leave the name of the callback function off the url.
                // See jsonloader.js for more details.
                url: "http://www.somesite.com/myjson.php?callback="
            },
            // The preloadFunction should leave you with an array of
            // elements, stripping off outer envelopes, etc.
            preloadFunction: function(result) {
                return result.feed.entry;
            },
            // The transformFunction should transform a single element from
            // your JSON format to the format required by loadItem().
            transformFunction: function(item) {
                var newItem = {};
                newItem['title'] = item['titleField'];
                // ... and so on
                return newItem;
            }
        },
        {
            title: "KML Dataset", 
            theme: TimeMapDataset.purpleTheme(),
            data: {
                type: "kml",        // Data to be loaded in KML - must be a local URL
                url: "mydata.kml",  // KML file to load
            }
            // preloadFunction and transformFunction accepted here, but
            // probably not necessary, as the data format will be correct
        },
        {
            title: "Metaweb Dataset", 
            theme: TimeMapDataset.blueTheme(),
            data: {
                type: "metaweb",    // Data to be loaded from freebase.com
                // JSON query in mql (Metaweb Query Language). The query below
                // asks for the lifespan and birthplace of all dead authors.
                // See http://www.freebase.com/view/freebase/api for more details.
                query: [                
                    {
                      "/people/deceased_person/date_of_death" : null,
                      "/people/person/date_of_birth" : null,
                      "/people/person/place_of_birth" : {
                        "geolocation" : {
                          "latitude" : null,
                          "longitude" : null
                        },
                        "name" : null
                      },
                      "name" : null,
                      "type" : "/book/author"
                    }
                ]
            },
            // The transformFunction should transform a single element from
            // the Metaweb format to the format required by loadItem().
            transformFunction: function(data) {
            	var lat = parseFloat(data["/people/person/place_of_birth"]["geolocation"]["latitude"]);
            	var lon = parseFloat(data["/people/person/place_of_birth"]["geolocation"]["longitude"]);
            	var start = data["/people/person/date_of_birth"];
            	var end = data["/people/deceased_person/date_of_death"];
            	var title = data["name"];
            	var description = data["/people/person/place_of_birth"]["name"];
            	return {
            		"title" : title,
            		"start" : start,
            		"end" : end,
                    "point" : {
                		"lat" : lat,
                        "lon" : lon
                    },
                    "options" : { "description" : description }
            	}
            }
        },
    ],
    bandInfo: [
        // this is the javascript definition for the timeline bands.
        // See http://simile.mit.edu/timeline/docs/create-timelines.html for details.
        // If this is empty, a default setup will be used, with the intervals below.
        // Put in the object parameters for Timeline.createBandInfo(), not the function,
        // and don't specify an eventSource, unless you want it to be null.
        {
           width:          "80%", 
           intervalUnit:   Timeline.DateTime.WEEK, 
           intervalPixels: 50
        },
        {
           width:          "20%", 
           intervalUnit:   Timeline.DateTime.MONTH, 
           intervalPixels: 100,
           showEventText:  false,
           trackHeight:    0.4,
           trackGap:       0.2
        }
    ],
    bandIntervals: [    
        // This will ONLY be used if bandInfo is empty
        // Only the first two items will be used, as the default has only two bands
        Timeline.DateTime.WEEK, 
        Timeline.DateTime.MONTH 
    ],
    // Date the timeline should scroll to when all data is loaded
    // Options: "earliest" (default), "latest", "now", or null (effectively same as "now")
    scrollTo: "latest",
    // Custom function to be called when all data is loaded
    // Receives the TimeMap object as an argument
    dataLoadedFunction: function(tm) {
        alert("All datasets loaded!");
    }
});
