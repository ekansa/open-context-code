// Module: Perseus: Smith's "Dictionary of Greek and Roman biography and mythology"

define(['jquery'], function($) {
    return {
        name: 'Perseus: Canonical Text Service',
        type: 'text',
        dataType: 'xml',
        // data format determined through content negotiation
        corsEnabled: true,
        // get values from the returned XML
        parseData: function(xml) {
            var getText = awld.accessor(xml);
            return {
                name: 'Text from Perseus',
                description: getText('body')
            };
        }
    };
});
