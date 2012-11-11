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

            var description = $('p', $content).first().html();
            description = description.replace(/href="\//g,'href="http://en.wikipedia.org/')

            var imageURI = $('.image img',$content);
            imageURI = typeof imageURI.first()[0] === 'object' ? imageURI = 'http:'+imageURI.first()[0].getAttribute('src') : ''; 

            return {
                name: data.title,
                description: description,
                imageURI: imageURI,
            };
        }
    };
});
