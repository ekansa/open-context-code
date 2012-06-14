//handle the Ajax response …
function handleResponse(transport)
{
    $('hello').innerHTML = transport.responseText;
}

//creates a prototype Ajax object, sends a request, and registers the callback function ‘handleResponse’
function callAjax(s)
{
    //alert('get-data');
    //remember to put a word separator between elements of the camelcase action name, per the ZF manual:
    var myAjax = new Ajax.Request('ajaxsample/get-data',
        {method: 'get', parameters: {state: s},
        onComplete: handleResponse}
    );
}

function testJsonRequest()
{
    //alert("testJsonRequest");
    var requestObject = new Object();
    requestObject.userName = 'vanwars';
    requestObject.firstName = 'Sarah';
    requestObject.lastName = 'Van Wart';
    
    var jsonRequest = requestObject.toJSONString();//JSON.stringify(requestObject); //requestObject.toJSONString();
    //var jsonRequest = JSON.stringify(requestObject); //requestObject.toJSONString();
    //var jsonRequest = requestObject;
    
    alert(jsonRequest);

    var url = "ajaxsample/process-user";
    var myAjax = new Ajax.Request(
                url,
                {
                    method: 'get', 
                    parameters: {jObj: jsonRequest }, 
                    onSuccess: testJsonResponse
                }
    );
    /*var myAjax = new Ajax.Request('ajaxsample/process-user',
        {method: 'get', parameters: {state: jsonRequest},
        onComplete: testJsonResponse}
    );*/
}

function testJsonResponse(response){
    //alert("ajax response!");
    var renderDiv = document.getElementById('userResults');
    alert(response.responseText);
    var obj = dojo.fromJson(response.responseText);
    //obj = dojo.fromJson(obj);
    alert(obj);
    alert(obj.userName);
}


