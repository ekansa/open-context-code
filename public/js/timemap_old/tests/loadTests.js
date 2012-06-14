function exposeTestFunctionNames() {
    return [
        'testDatasetIsDefined',
        'testItemLoaded',
        'testItemLoadedInEventSource',
        'testEarliestDate',
        'testLatestDate',
        'testItemAttributes'
    ];
}

function testDatasetIsDefined() {
    assertNotUndefined("dataset is defined", tm.datasets["test"]);
}

function testItemLoaded() {
    var ds = tm.datasets["test"];
    assertEquals("two items in item array", ds.getItems().length, 2);
}

function testItemLoadedInEventSource() {
    var ds = tm.datasets["test"];
    assertEquals("two items in eventSource", ds.eventSource.getCount(), 2);
}

function testEarliestDate() {
    var ds = tm.datasets["test"];
    assertEquals("year matches", ds.eventSource.getEarliestDate().getUTCFullYear(), 1980);
    assertEquals("month matches", ds.eventSource.getEarliestDate().getUTCMonth(), 0);
    assertEquals("day matches", ds.eventSource.getEarliestDate().getUTCDate(), 2);
}

function testLatestDate() {
    var ds = tm.datasets["test"];
    assertEquals("year matches", ds.eventSource.getLatestDate().getFullYear(), 2000);
    assertEquals("month matches", ds.eventSource.getEarliestDate().getMonth(), 0);
    assertEquals("day matches", ds.eventSource.getEarliestDate().getUTCDate(), 2);
}

function testItemAttributes() {
    var items = tm.datasets["test"].getItems();
    // point
    var item = items[0];
    assertNotNull("event not null", item.event);
    assertNotNull("placemark not null", item.placemark);
    assertEquals("title matches", item.getTitle(), "Test Event");
    assertEquals("event title matches", item.event.getText(), "Test Event");
    assertEquals("placemark type matches", item.getType(), "marker");
    var point = new GLatLng(23.456, 12.345);
    assertTrue("marker point matches", item.placemark.getLatLng().equals(point));
    assertTrue("info point matches", item.getInfoPoint().equals(point));
    // polyline
    item = items[1];
    assertNotNull("event not null", item.event);
    assertNotNull("placemark not null", item.placemark);
    assertEquals("title matches", item.getTitle(), "Test Event 2");
    assertEquals("event title matches", item.event.getText(), "Test Event 2");
    assertEquals("placemark type matches", item.getType(), "polyline");
    var points = [new GLatLng(45.256, -110.45), new GLatLng(46.46, -109.48), new GLatLng(43.84, -109.86)];
    assertEquals("vertex count matches", item.placemark.getVertexCount(), 3);
    assertTrue("info point matches middle point", item.getInfoPoint().equals(points[1]));
    for (var x=0; x<points.length; x++) {
        assertTrue("vertex " + x + " matches", item.placemark.getVertex(x).equals(points[x]));
    }
}



var tm = null;

// page setup function - basic
function basicLoadTestSetup() {
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset",
                id: "test",
                // this syntax should still work
                data: {
                    type: "basic",
                    value: [
                        {
                            "start" : "1980-01-02",
                            "end" : "2000-01-02",
                            "point" : {
                                "lat" : 23.456,
                                "lon" : 12.345
                            },
                            "title" : "Test Event",
                            "options" : {
                                "description": "Test Description"
                            }
                        },
                        {
                            "start" : "1980-01-02",
                            "polyline" : [
                                {
                                    "lat" : 45.256,
                                    "lon" : -110.45
                                },
                                {
                                    "lat" : 46.46,
                                    "lon" : -109.48
                                },
                                {
                                    "lat" : 43.84,
                                    "lon" : -109.86
                                }
                            ],
                            "title" : "Test Event 2"
                        }
                    ]
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}

// page setup function - kml
function kmlLoadTestSetup() {
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset",
                id: "test",
                type: "kml",
                options: {
                    url: "data/data.kml" 
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}

// page setup function - jsonp
function jsonLoadTestSetup() {
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset",
                id: "test",
                type: "jsonp",
                options: {
                    url: "data/data.js?cb=" 
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}

// page setup function - json string
function jsonStringLoadTestSetup() {
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset",
                id: "test",
                type: "json_string",
                options: {
                    url: "data/data_string.js" 
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}
