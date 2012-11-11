// Module: Arachne Item/Entity HTML

define(['jquery'], function($) {
    return {
        name: 'Arachne Item',
        dataType: 'html',
        type: 'object',
        parseData: function(html) {
            var getText = awld.accessor(html);

            var imageURI = getText('img[src*="image.php"]', 'src');
            imageURI = typeof imageURI === 'string'? imageURI : imageURI[0];
            imageURI = 'http://arachne.uni-koeln.de/arachne/'+imageURI;

            return {
                name: "Arachne " + getText('#project_title'),
                //description: getText(''),
                imageURI: imageURI,
            };
        },
    };
});
