function exposeTestFunctionNames() {
    return [
        'testLoaded',
        'testEmptyTag',
        'testExtendedData',
        'testUnboundedSpan',
        'testLongTagValue'
    ];
}

function testLoaded() {
    var ds = tm.datasets["test"];
    assertNotUndefined("Dataset is defined", ds);
    assertEquals("Correct number of items in item array", 4, ds.getItems().length);
}

function testEmptyTag() {
    var ds = tm.datasets["test"],
        items = ds.getItems(),
        item = items[0];
    assertEquals("Description not found", "", item.opts.description);
}

function testExtendedData() {
    var ds = tm.datasets["test"],
        items = ds.getItems(),
        item = items[1];
    assertEquals("ExtendedData element loaded", "Test 1", item.opts.Test1);
    assertEquals("Mapped ExtendedData element loaded", "Test 2", item.opts.foo);
}

function testUnboundedSpan() {
    var ds = tm.datasets["test"],
        items = ds.getItems(),
        item = items[2];
    assertEquals("Start year matches", 1980, item.getStart().getUTCFullYear());
    assertEquals("Start month matches", 0, item.getStart().getUTCMonth());
    assertEquals("Start day matches", 2, item.getStart().getUTCDate());
    d = new Date();
    assertEquals("End year defaults to present", d.getUTCFullYear(), item.getEnd().getUTCFullYear());
    assertEquals("End month defaults to present", d.getUTCMonth(), item.getEnd().getUTCMonth());
    assertEquals("End day defaults to present", d.getUTCDate(), item.getEnd().getUTCDate());
}

function testLongTagValue() {
    var ds = tm.datasets["test"],
        items = ds.getItems(),
        item = items[3];
    assertEquals("Description char count correct", 5000, item.opts.description.length);
}


var tm = null;

function setUpPage() {
    TimeMap.util.nsMap['dc'] = 'http://purl.org/dc/elements/1.1/';
    tm = TimeMap.init({
        mapId: "map",               // Id of map div element (required)
        timelineId: "timeline",     // Id of timeline div element (required) 
        datasets: [
            {
                title: "Test Dataset: KML",
                id: "test",
                type: "kml",
                options: {
                    url: "data/test.kml",
                    extendedData: ['Test1', 'Test2'],
                    tagMap: {
                        'Test2':'foo'
                    }
                }
            }
        ],
        dataDisplayedFunction: function() { setUpPageStatus = "complete"; }
    });
}
