// Module: SUDOC RDF 
// @author: adapted from code by Régis Robineau

define(['jquery'], function($) {
    return {
        name: 'Notices Sudoc',
        dataType: 'xml',
        type: 'citation',
        corsEnabled: true,
        toDataUri: function(uri) {
            return uri + '.rdf';
        },
        // get values from the returned XML
        parseData: function(xml) {
            var $xml = $(xml);
            // jQuery 1.7's namespace support is broken, but this works here
            var name = $xml.find('title')[0].textContent;
            if (typeof name === 'undefined') { name = '' }; // be defensive

            var description = $xml.find('date')[0].textContent;
            description = typeof description === 'undefined' ? '' : 'Date: ' + description;
            
            return {
                name: name,
                description: description,
            };
            
        }
    };
});
