// Module: Wikipedia page

define(function() {
    return {
        name: 'Wikipedia Pages',
        dataType: 'jsonp',
        // not entirely happy with this, but it looks hard to reference specific elements
        toDataUri: function(uri) {
            var pageId = uri.split('/').pop();
            return 'http://en.wikipedia.org/w/api.php?format=json&action=parse&page=' + pageId + '&callback=?';
        },
        parseData: function(data) {
            data = data && data.parse || {};
            var $content = $('<div>' + data.text['*'] + '</div>');
            return {
                name: data.title,
                description: $('p', $content).first().html()
            };
        }
    };
});