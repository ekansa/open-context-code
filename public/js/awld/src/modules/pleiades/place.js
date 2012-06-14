// Module: Pleiades places

define(function() {
    return {
        name: 'Pleiades Places',
        type: 'place',
        toDataUri: function(uri) {
            return uri + '/json';
        },
        corsEnabled: true,
        // add name to data
        parseData: function(data) {
            data.name = data.title;
            data.latlon = data.reprPoint.reverse();
            return data;
        }
    };
});