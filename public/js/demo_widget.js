function handleCallBack(data,options){
	var name=data['name']
	var count=data['count']
	var rank=data['rank']
	var contexts=data['contexts']
	var projects=data['projects']
	var categories=data['categories']
	var person_link=data['href']
	

	var widget_div=document.getElementById("oc_widget");
	widget_div.style.backgroundColor='#eff3ff';
	widget_div.style.width="25em"
	widget_div.style.border="1px solid #d3dfff"
	widget_div.style.fontFamily="Arial"
	widget_div.style.fontSize="12px"
	
	var footer=widget_div.innerHTML
	
	widget_div.innerHTML="";
	widget_div.innerHTML+='<div class="heading"><span style="float:left;"><b>'+name+'</b></span></div>'
	
	var project_list='<div class="content"><span style="font-weight:bold; font-size:12px;">Project: </span><ul>'
	for (var i=0;i<projects.length;i++)
	{
		project_list+='<li><a href="'+projects[i]['href']+'>'+projects[i]['name']+'</a></li>'
	}
	project_list+='</ul></div>'
  widget_div.innerHTML+=project_list
	

	var category_list='<div class="content"><span style="font-weight:bold; font-size:12px;">Categories: </span><ul>'
	for (var i=0;i<categories.length;i++)
	{
		category_list+='<li><img src='+categories[i]['icon_href']+'>&nbsp;&nbsp;<a href="'+categories[i]['href']+'>'+categories[i]['name']+'</a></li>'
	}
	category_list+='</ul></div>'
  widget_div.innerHTML+=category_list
	
	var context_list='<div class="content"><span style="font-weight:bold; font-size:12px;">contexts: </span><ul>'
	for (var i=0;i<contexts.length;i++)
	{
		context_list+='<li><a href="'+contexts[i]['href']+'>'+contexts[i]['name']+'</a></li>'
		context_list+='item count :<b>'+contexts[i]['item_count']+'</b>'
	}
	context_list+='</ul></div>'
  widget_div.innerHTML+=context_list
	widget_div.innerHTML+='<span style="font-size:10px;font-family:courier">Item views:'+count+'&nbsp;&nbsp;&nbsp;&nbsp;Ranked:'+rank+'</span>'
	widget_div.innerHTML+=footer
	}