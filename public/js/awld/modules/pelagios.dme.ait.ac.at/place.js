// Module: Pleiades places

define(function() {
    return {
        name: 'Pelagios Places',
        type: 'place',
        toDataUri: function(uri) {
            var pleiadesID = uri.match(/[0-9]+$/);
            return 'http://pleiades.stoa.org/places/'+ pleiadesID + '/json';
        },
        corsEnabled: true,
        // add name to data
        parseData: function(data) {
            data.name = data.title;
            data.latlon = data.reprPoint && data.reprPoint.reverse();
            data.description = data.description;
            return data;
        }
    };
});
