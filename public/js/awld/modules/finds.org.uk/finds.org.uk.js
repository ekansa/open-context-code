// Module: OpenContext HTML

define(['jquery'], function($) {
    return {
        name: 'Portable Antiquities Scheme Object',
        dataType: 'html',
        type: 'object',
        corsEnabled: true,
        parseData: function(html) {
            var getText = awld.accessor(html);
            var imageURI = 'http://finds.org.uk/' + getText('a[rel="lightbox"] img', 'src')
            return {
                name: "PAS " + getText('a[rel="lightbox"]', 'title'),
                description: '<br/><img style="max-width:150" src="'+imageURI+'"/>'
            };
        },
    };
});
