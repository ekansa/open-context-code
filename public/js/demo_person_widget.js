function handleCallBack(data,options){
	var name=data['name']
	var count=data['item_view_count']
	var rank=data['rank']
	var contexts=data['contexts']
	var project=data['projects']
	var categories=data['categories']
	var media=data['media']
	
	var widget_div=document.getElementById("oc_widget");
		
	widget_div.innerHTML="";
	widget_div.innerHTML+='<div style="display:table-row;"><div style="display:table-cell; padding:3px; ; vertical-align:middle;"><a href="'+project[0]['href']+'" target="_blank" title="View my Open Context Profile in a new page"><img src="http://opencontext.org/images/layout/oc-door-logo.png" alt="Open Context Logo" style="border:0px;" /></a></div><div style="display:table-cell; padding:3px; ; vertical-align:middle;"><h4>Open Data Publication</h4><p><a href="http://opencontext.org" target=_blank title="Visit OpenContext in a new window" style="text-decoration: none; color:#000; border-bottom:1px dashed silver;">Open Context</a> Open Access Data Publication</p></div></div>';
	
	var project_list='<p>Project: ';
        
        var eraBegin ="CE";
	var eraEnd ="CE";

        var timeBegin = parseInt(contexts[0]['timespan']['begin']);
        var timeEnd = parseInt(contexts[0]['timespan']['end']);

	if (timeBegin < 0) {
            eraBegin='BCE';
            timeBegin = timeBegin * -1;
        } 

  	if (timeEnd < 0) {
            eraEnd='BCE';
            timeEnd = timeEnd * -1;
        }
	
	    //project_list+=project[0]['name']+' ('+contexts[0]['name']+', '+ timeBegin + ' '+ eraBegin +' to '+ timeEnd + ' '+ eraEnd +')';	    project_list+=project[0]['name']+' ('+ contexts[0]['name'] +')';
	    project_list+='</p>';

 widget_div.innerHTML+=project_list;

	var contributions_list='<ul>';
		contributions_list+='<li>Records Contributed: '+'<a href="'+contexts[0]['href']+'" target="_blank" style="text-decoration: none; color:#000; border-bottom:1px dashed silver;" title="View these records in Open Context">'+contexts[0]['item_count']+'</a>';
		
	for (var i=0;i<media.length;i++)
	{
		contributions_list+='<li>'+media[i]['name']+': '+'<a href="'+media[i]['href']+'" target="_blank" style="text-decoration: none; color:#000; border-bottom:1px dashed silver;" title="View these records in Open Context">'+media[i]['item_count']+'</a></li>';
	}
	
	for (var i=0;i<categories.length;i++)

	{

		contributions_list+='<li><div style="display:table-row;"><a href="'+categories[i]['href']+'" target="_blank" style="text-decoration: none; color:#000; border-bottom:1px dashed silver;" title="View these records in Open Context"><div style="display:table-cell; padding:3px;"><img src="' + categories[i]['icon_href'] + '" /></div><div style="display:table-cell;  padding:3px; vertical-align:middle;">' + categories[i]['name'] +'</div></a><div style="display:table-cell; padding:3px; ; vertical-align:middle;">' + categories[i]['item_count'] + '</div></div></li>';

	}
	contributions_list+='</ul>';
	
 widget_div.innerHTML+=contributions_list;
 
	widget_div.innerHTML+='<p>Items have been viewed: <em>' +count+'</em> times</p>';
	//widget_div.innerHTML+='<p><a href="'+project[0]['href']+'" target="_blank" title="View my Open Context Profile in a new page"><img src="http://opencontext.org/images/layout/oc-door-logo.png" alt="Open Context Logo" style="border:0px;" /></a>';
	}