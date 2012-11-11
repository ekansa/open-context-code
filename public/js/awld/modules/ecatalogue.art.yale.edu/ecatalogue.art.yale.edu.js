// Module: Yale Art Museum  HTML

define(['jquery'], function($) {
    return {
        name: 'Yale Art Museum Object',
        type: 'object',
        dataType: 'html',
        parseData: function(html) {
            var getText = awld.accessor(html);

            var name = getText('.d-title');
            name = typeof name === 'undefined' ? "Object" :  name;
            
            var description = getText('.d-smallm');
            description = typeof name === 'undefined' ? '' : description;

            var imageURI = getText('#dtl-refimg','src');

            return {
                name: name,
                description: description,
                imageURI: imageURI,
            };
        },
    };
});
