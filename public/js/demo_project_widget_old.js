function handleCallBack(data,options){
	var projname=data['name'];
	var descriptions=data['descriptions'];
	var count=data['item_view_count'];
	var rank=data['rank'];
	var contexts=data['contexts'];
	var media=data['media'];
	var categories=data['categories'];
	var project_link=data['href'];
	var ocimages=data['images'];
        
        var mainPeople = data['main_people'];
 
 //setting widget container div style
	var widget_div=document.getElementById("oc_widget");
	widget_div.style.width="480px";
	widget_div.style.border="1px solid #d7d7d7";
	widget_div.style.fontFamily="Arial";
	widget_div.style.fontSize="12px";

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
 	
 	widget_div.innerHTML="";

	var header='<div id="ocheading" style="height:25px;padding:0px 5px 0px 6px;background:#e0e1cd;color:#8b1a1a;font-size:18px;text-align:right;">';
		header+='<span style="float:left;"><a style="padding: 1px 0px 3px; font-size: 18px; text-align: right; color: rgb(139, 26, 26); text-decoration: none;" href="'+contexts[0]['href']+'" target="_blank">'+projname+'</a></span>';
 		header+='<span id="octimescale" style="font-size:14px;color:#8b1a1a;">'+ timeBegin + ' '+ eraBegin + ' to ' + timeEnd +' '+eraEnd+'</span>';
 		header+='</div>';
 	
 	widget_div.innerHTML+=header;
 	
 	var description='<div class="content" style="background:#ffffff; margin 3px; padding:5px;"><p>'+descriptions['short']+'... <a href="'+project_link+'" target="_blank">Read More</a></p></div>';
 

   widget_div.innerHTML+=description;
 	
 	
 	var photos='<div id="home-photo" class="images" style="float:right;">';
 
 	 if (ocimages['length'] > 0) {
 		for (var i = 0; i < ocimages.length && i < 4; i++)
 		{
 			photos+='<img src="'+ocimages[i].examples[0]['href_thumb']+'" title="'+ocimages[i].examples[0]['category']+'" style="margin:3px;" />'
 		
 		if (i>=1 && i%2 != 0) {photos+='<br />'}
 	    }
 	}
 		photos+='</div>';

 	
   widget_div.innerHTML+=photos;
   
 	
 	var summary='<span style="font-weight:bold; font-size:12px; padding-left:3px;">Summary: </span>';
 	summary+='<ul style="margin-top:4px;"><li>Records: '+contexts[0]['item_count']+'</li></ul>';
 
   widget_div.innerHTML+=summary;
 	
 	var recordTypes='<div class="recordtypes" style="padding-left:33px;">';
 	        var maxCount = 9;
        if(categories.length < maxCount){
            maxCount = categories.length;
        }

 	for (var i=0; i<maxCount; i++) 
 	{
 		recordTypes+='<a href="'+categories[i]['href']+'" target="_blank">'+'<img src="'+categories[i]['icon_href']+'" title="'+ categories[i]['name']+': '+categories[i]['item_count']+' items" style="border:0px; padding-left:8px;"></a>';
 }
 
 	recordTypes+='</div>';
 
   widget_div.innerHTML+=recordTypes;
 	
 
 	var mediaContributed='<ul>';

 
 	var j=media['length'];
 	for (var i=0; i<media['length']; i++)
 	{
 		mediaContributed+='<li><a href="'+media[i]['href']+'">'+media[i]['name']+': '+media[i]['item_count']+'</a></li>';
 	}
 	mediaContributed+='</ul></div>';
 
   widget_div.innerHTML+=mediaContributed;
 
     var contributors='<span style="font-weight:bold; font-size:12px; padding-left:3px;">Project Leads:</span><ul>';
 
 	var k=data['main_people']['length'];        //var k=data['main_people'].length;

 	for (var i = 0; i<k; i++){
 	    contributors += '<li><a href="'+data['main_people'][i]['href']+'" target="_blank" title="view their OpenContext profile">Person ' +data['main_people'][i]['name']+'</a></li>';      
 	}
 	contributors +='</ul></div>';
        alert(contributors);        widget_div.innerHTML+= contributors;
 
 	widget_div.innerHTML+='<span style="font-size:10px;font-family:courier; padding-left:3px;">Project Views:'+count+'</span>';
 	widget_div.innerHTML+='<div class="footer"><span style="margin:8px;"><a href="http://opencontext.org"><img style="border:0px;" src="http://opencontext.org/images/widget/logo.jpg"/></a></div></div>';
 	}