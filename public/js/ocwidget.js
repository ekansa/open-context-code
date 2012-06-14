function handleCallBack(data,options){

	/////// styling of the root div/////////
	
	default_styles={'heigth':'300','width':'300','backgroundColor':'#eff3ff','fontFamily':'verdana','fontFamily':'verdana','padding':'5px','font-size':'12px'}
	widgetRoot=document.getElementById('oc_widget');
	for (style in default_styles) {widgetRoot.style[style]=default_styles[style]};
	for (opt in options) {widgetRoot.style[opt]=options[opt]};
	
	/////////// creating child elements ///////////////
	
	//HEADING//
	var name=data["name"];
	
	var heading=document.createElement("p");
	heading.innerHTML='Project: '+data.projects.name+' ('
	widgetRoot.appendChild(heading);
	
	//PROJECTS//
	var projects=data['projects']
	
	var project_div=document.createElement("div");
	var project_list=document.createElement("ul");
	for (var i=0;i<projects.length;i++){
		list_item=document.createElement("li");
		list_item.innerHTML='<a href="'+projects[i]['href']+'>'+projects[i]['name']+'</a>'
		project_list.appendChild(list_item)
	}
	project_div.appendChild(project_list)
	
	widgetRoot.appendChild(project_div);

}