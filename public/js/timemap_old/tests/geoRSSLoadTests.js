function exposeTestFunctionNames() {
    return [
        'testDatasetsAreDefined',
        'testRSSItemLoaded',
        'testRSSEarliestDate',
        'testRSSItemAttributes',
        'testAtomItemLoaded',
        'testAtomEarliestDate',
        'testAtomItemAttributes',
        'testMixedItemsLoaded',
        'testMixedPlacemarksFound',
        'testMixedKMLTime'
    ];
}

function testDatasetsAreDefined() {
    assertNotUndefined("RSS dataset is defined", tm.datasets["rss"]);
    assertNotUndefined("Atom dataset is defined", tm.datasets["atom"]);
}

function testRSSItemLoaded() {
    var ds = tm.datasets["rss"];
    assertEquals("one item in item array", ds.getItems().length, 1);
}

function testRSSEarliestDate() {
    var ds = tm.datasets["rss"];
    assertEquals("year matches", ds.eventSource.getEarliestDate().getFullYear(), 1980);
    assertEquals("month matches", ds.eventSource.getEarliestDate().getMonth(), 0);
    // Timeline seems to adjust for the timezone after parsing :(
    assertEquals("day matches", ds.eventSource.getEarliestDate().getDate(), 1);
}

function testRSSItemAttributes() {
    var items = tm.datasets["rss"].getItems();
    var item = items[0];
    assertEquals("title matches", item.getTitle(), "Test Event");
    assertEquals("placemark type matches", item.getType(), "marker");
    var point = new GLatLng(23.456, 12.345);
    assertTrue("point matches", item.getInfoPoint().equals(point));
}

function testAtomItemLoaded() {
    var ds = tm.datasets["atom"];
    assertEquals("one item in item array", ds.getItems().length, 1);
}

function testAtomEarliestDate() {
    var ds = tm.datasets["atom"];
    assertEquals("year matches", ds.eventSource.getEarliestDate().getFullYear(), 1980);
    assertEquals("month matches", ds.eventSource.getEarliestDate().getMonth(), 0);
    // Timeline seems to adjust for the timezone after parsing :(
    assertEquals("day matches", ds.eventSource.getEarliestDate().getDate(), 1);
}

function testAtomItemAttributes() {
    var items = tm.datasets["atom"].getItems();
    var item = items[0];
    assertEquals("title matches", item.getTitle(), "Test Event");
    assertEquals("placemark type matches", item.getType(), "marker");
    var point = new GLatLng(23.456, 12.345);
    assertTrue("point matches", item.getInfoPoint().equals(point));
}

function testMixedItemsLoaded() {
    var ds = tm.datasets["mixed"];
    assertEquals("Ten items in item array", 10, ds.getItems().length);
}

function testMixedPlacemarksFound() {
    var items = tm.datasets["mixed"].getItems();
    var pmTypes = ['GeoRSS-Simple','GML (pos)','GML (coordinates)','W3C Geo'];
    var offset;
    for (x=0; x<pmTypes.length; x++) {
        var item = items[x];
        assertEquals(pmTypes[x] + ": placemark type matches", item.getType(), "marker");
        var point = new GLatLng(23.456, 12.345);
        assertTrue(pmTypes[x] + ": point matches", item.getInfoPoint().equals(point));
    }
    pmTypes = ['Polyline Simple','Polyline GML'];
    offset = 4;
    var points = [new GLatLng(45.256, -110.45), new GLatLng(46.46, -109.48), new GLatLng(43.84, -109.86)];
    for (x=0; x<pmTypes.length; x++) {
        var item = items[x + offset];
        assertEquals(pmTypes[x] + ": placemark type matches", item.getType(), "polyline");
        assertEquals(pmTypes[x] + ": vertex count matches", item.placemark.getVertexCount(), 3);
        assertTrue(pmTypes[x] + ": info point matches middle point", item.getInfoPoint().equals(points[1]));
        for (var y=0; y<points.length; y++) {
            assertTrue("vertex " + y + " matches", item.placemark.getVertex(y).equals(points[y]));
        }
    }
    pmTypes = ['Polygon Simple','Polygon GML'];
    offset = 6;
    // polygon bounds center
    var point = new GLatLng(45.150000000000006, -109.965);
    for (x=0; x<pmTypes.length; x++) {
        var item = items[x + offset];
        assertEquals(pmTypes[x] + ": placemark type matches", item.getType(), "polygon");
        // Google seems to count the last vertex of a closed polygon
        assertEquals(pmTypes[x] + ": vertex count matches", item.placemark.getVertexCount(), 4);
        assertTrue(pmTypes[x] + ": info point matches middle point", item.getInfoPoint().equals(point));
        for (var y=0; y<points.length; y++) {
            assertTrue("vertex " + y + " matches", item.placemark.getVertex(y).equals(points[y]));
        }
    }
}

function testMixedKMLTime() {
    var ds = tm.datasets["mixed"];
    var items = tm.datasets["mixed"].getItems(), item, d, prefix;
    // TimeSpan
    item = items[8];
    // start
    d = item.event.getStart();
    prefix = item.getTitle() + " start ";
    assertEquals(prefix + "year matches", 1985, d.getUTCFullYear());
    assertEquals(prefix + "month matches", 0, d.getUTCMonth());
    assertEquals(prefix + "day matches", 2, d.getUTCDate());
    // end
    d = item.event.getEnd();
    prefix = item.getTitle() + " end ";
    assertEquals(prefix + "year matches", 2000, d.getUTCFullYear());
    assertEquals(prefix + "month matches", 0, d.getUTCMonth());
    assertEquals(prefix + "day matches", 2, d.getUTCDate());
    // TimeStamp
    item = items[9];
    // start
    d = item.event.getStart();
    prefix = item.getTitle() + " start ";
    assertEquals(prefix + "year matches", 1985, d.getUTCFullYear());
    assertEquals(prefix + "month matches", 0, d.getUTCMonth());
    assertEquals(prefix + "day matches", 2, d.getUTCDate());
    // is instant
    assertTrue(item.getTitle() + " event is instant", item.event.isInstant());
}


var tm = null;

function setUpPage() {
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset: RSS",
                id: "rss",
                type: "georss",
                options: {
                    url: "data/data.rss" 
                }
            },
            {
                title: "Test Dataset: Atom",
                id: "atom",
                type: "georss",
                options: {
                    url: "data/data-atom.xml" 
                }
            },
            {
                title: "Test Dataset: RSS, mixed formats",
                id: "mixed",
                type: "georss",
                options: {
                    url: "data/data-mixed.xml" 
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}
