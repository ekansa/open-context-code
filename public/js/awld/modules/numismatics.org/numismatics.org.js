// Module: OpenContext HTML

define(['jquery'], function($) {
    return {
        name: 'American Numismatic Society Object',
        dataType: 'xml',
        type: 'object',
        toDataUri: function(uri) {
            return uri + '.xml';
        },
        parseData: function(xml) {
            var getText = awld.accessor(xml);
            var imageURI = getText('[USE = "thumbnail"] *','xlink:href');
            var description = typeof imageURI === 'undefined' ? '<i>No image available.</i>' : '<img style="max-width:100px" src="'+imageURI[0]+'"/><img style="max-width:100px" src="'+imageURI[1]+'"/>';
            return {
                name: "ANS " + getText('title'),
                description: description,
        };
    },
 }
});
