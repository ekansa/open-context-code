// Module: Perseus: Smith's "Dictionary of Greek and Roman biography and mythology"

define(['jquery'], function($) {
    return {
        name: 'Library of Congress Online Catalog',
        type: 'citation',
        dataType: 'xml',
        toDataUri: function(uri) { 
            return uri + '/dc';
        },
        // get values from the returned XML
        parseData: function(xml) {
            var getText = awld.accessor(xml),
                title = getText('title'),
                author = getText('creator');
            return {
                name: '"' + title + '" by ' + author,
                description: getText('description')
            };
        }
    };
});