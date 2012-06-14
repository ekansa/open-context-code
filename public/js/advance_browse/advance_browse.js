
function textSearch(){
    //this function does a normal key word search of the subset already selected
    //it can also be combined with multiple checked selections
               
    var doSearch = false;
    var dom_act_uri = document.getElementById('act_uri');
    var dom_qstring = document.getElementById('q_string');
               
    var acr_uri  = dom_act_uri.value;
    var q_string = dom_qstring.value;
    var amp_char = String.fromCharCode(38);
    
    var readyQueryURI = makeCheckedQuery();
    if(readyQueryURI != false){
        acr_uri = readyQueryURI; //include multiple checked search options, if made
        doSearch = true;
    }
               
    q_string = escape(q_string);
    
    if(q_string.length > 1){           
        if (acr_uri.indexOf("?")>0){
            acr_uri = acr_uri + amp_char + "q=" + q_string; 
        }
        else{
            acr_uri = acr_uri + "?q=" + q_string; 
        }
        doSearch = true;
    }
    
    if(doSearch){
        window.location = acr_uri;  
    }
    else{
        alert("Please enter a search term or select one or more items below to search");
    }
}



function searchChecked(){
    var readyQueryURI = makeCheckedQuery();
    
    if(!readyQueryURI){
        alert("Please check items to use in your search.");
    }
    else{
        window.location = readyQueryURI;
    }
    
}




function makeCheckedQuery(){
    var workingURI = baseURI;
    var contextBaseURI = contextBase;
    var paramterBaseURI = paramterBase;
    var activeTaxaBaseValue = activeTaxaValue;
    var activeRelBaseValue = activeRelationValue;
    
    var listInputs = document.getElementsByTagName("input");
    
    var lastParameter = "";
    var newParameter = true;
    var anyChecked = false;
    
    for (i=0; i<listInputs.length; i++){
        var actParameter = listInputs[i].name;
        var actValue = listInputs[i].value;
        var actChecked = listInputs[i].checked;
    
        if(actChecked){
            //item is selected to use in query
            anyChecked = true;
            
            if(actParameter != lastParameter){
                newParameter = true;
                lastParameter = actParameter;
            }
            else{
                newParameter = false;
            }
            
            //not a new parameter, active values of input fields used to make OR query
            if(!newParameter){
                actValue = "||" + actValue; // add OR pipes
            }
            
            if(actParameter == "default_context_path"){
                //special handling of default contexts
                contextBaseURI = contextBaseURI + actValue;
            }
            else if(actParameter == "dtaxa%5B%5D"){
                //special handling of the last (active) taxa parameter.
                if(newParameter){
                    var extnendedActTaxon = activeTaxaBaseValue + "::" + actValue;
                }
                else{
                    var extnendedActTaxon = activeTaxaBaseValue + actValue;
                }
                 paramterBaseURI = paramterBaseURI.replace(activeTaxaBaseValue, extnendedActTaxon);
                 activeTaxaBaseValue = extnendedActTaxon;
            }
            else if(actParameter == "drel%5B%5D"){
                //special handling of the last (active) rel parameter.
                if(newParameter){
                    var extnendedActLink = activeRelBaseValue + "::" + actValue;
                }
                else{
                    var extnendedActLink = activeRelBaseValue + actValue;
                }
                 paramterBaseURI = paramterBaseURI.replace(activeRelBaseValue, extnendedActLink);
                 activeRelBaseValue = extnendedActLink;
            }
            else{
                //usual case, where a parameter is added to query and 1 or more values are requested for that parameter
                if(newParameter){
                    if(paramterBaseURI.length < 1){
                        paramterBaseURI = "?" + actParameter + "=";
                    }
                    else{
                        paramterBaseURI = paramterBaseURI + "&" + actParameter + "=";
                    }
                    
                }
                paramterBaseURI = paramterBaseURI + actValue;
            }//end case of normal paramters
        }//end case of checked input field
        
    }//end loop 
    
    var readyQueryURI = contextBaseURI + paramterBaseURI;
    
    if(anyChecked){
        return readyQueryURI;
    }
    else{
        return false;
    }
}


function doAdvanced(facet_type){
    var mylist=document.getElementById(facet_type);
    
    //change control to remove select multiple button
    var controlButton = document.getElementById(("l_"+facet_type));
    controlButton.setAttribute("href","javascript:searchChecked()");
    controlButton.innerHTML = "Search with Checked Items";
    
    var listitems= mylist.getElementsByTagName("li");
    var listIds = new Array();
    
    for (i=0; i<listitems.length; i++){
        var actID = listitems[i].id;
        if(actID){ 
            if(actID.length >1){
                listIds[i] = actID;
                var actListItem = document.getElementById(actID);
                actListItem.setAttribute("style","list-style-type:none; margin-left:-20px;");
            }
        }
    }
    
    for (i=0; i<listIds.length; i++){
        var actID = listIds[i];
        if(actID){
            var listItem = document.getElementById(actID);
            //listItem.setAttribute("style","display:table-row; vertical-align:middle;");
            //listItem.style.display = "table-row";
            listItem.setAttribute("class", "facetListInput");    //good browsers
            listItem.setAttribute("className", "facetListInput");    //ie
            
            var inputID = actID.replace("f_", "in_");
            var actInput = document.getElementById(inputID);
            //actInput.setAttribute("type","checkbox");
            //actInput.setAttribute("style","display:table-cell; margin-left:-18px;");
            //actInput.style.display = "table-cell";
            actInput.setAttribute("class", "ActCheck");    //good browsers
            actInput.setAttribute("className", "ActCheck");    //ie
            
            
            var linkID = actID.replace("f_", "l_");
            var linkNode = document.getElementById(linkID);
            //linkNode.setAttribute("style","display:none;");
            //linkNode.style.display = "none";
            linkNode.setAttribute("class", "hideLink");    //good browsers
            linkNode.setAttribute("className", "hideLink");    //ie
            
            var facetCountID = actID.replace("f_", "fc_");
            var facetCountNode = listItem.getElementsByTagName("span")[0];
            //facetCountNode.setAttribute("style","display:none;");
            //facetCountNode.style.display="none";
            facetCountNode.setAttribute("class", "hideSpan");    //good browsers
            facetCountNode.setAttribute("className", "hideSpan");    //ie
            
            
            var facetCountText = facetCountNode.innerHTML;
            var linkNodeText = linkNode.innerHTML;
            
            var newSpanID = actID.replace("f_", "il_");
            var newSpan = document.createElement("span");
            var newSpanText = document.createTextNode(linkNodeText + " " + facetCountText);
            newSpan.appendChild(newSpanText);
            newSpan.setAttribute("id", newSpanID);
            newSpan.setAttribute("class", "inputLabel");
            newSpan.setAttribute("className", "inputLabel");    //ie
            
            //newSpan.setAttribute("style","display:table-cell; vertical-align:middle; color:#4F0101;");
            //actInput.style.display = "table-cell";
            
            listItem.appendChild(newSpan);
            
            
        }
    }
    
}

