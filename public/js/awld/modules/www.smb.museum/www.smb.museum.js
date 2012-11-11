// Module: SMB HTML

define(['jquery'], function($) {
    return {
        name: 'Münzkabinett Berlin',
        dataType: 'html',
        type: 'object',
        parseData: function(html) {
            var getText = awld.accessor(html);

            var name = getText('[id = "objektInfo"] h3');
            if (typeof name === 'undefined') { name = '' };

            var imageURI = getText('[id = "ansichtOben"] img', 'src');
            imageURI = typeof imageURI === 'string'? imageURI : imageURI[0];
            imageURI = 'http://www.smb.museum/ikmk/'+imageURI;

            return {
                name: "Münzkabinett Berlin: " + name,
                // description: getText('[id = "item_class"]'),
                imageURI: imageURI,
            };
        },
    };
});
