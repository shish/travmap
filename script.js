/*
 * script.js (c) Shish 2006
 */

var displayparts = new Array();
displayparts["advanced"] = "hide";
displayparts["output"] = "hide";
displayparts["instructions"] = "show";
displayparts["langopts"] = "show";


function byId(id) {
	return document.getElementById(id);
}

window.onload = function(e) {
	updatepart("advanced");
	updatepart("output");
	updatepart("instructions");
	updatepart("langopts");
	loadsettings();
}


function updateMap() {
	elements = document.forms[0].elements;
	url = "";
	target="self";
	for(i=0; i<elements.length; i++) {
		if(elements[i].type == "checkbox") {
			if(elements[i].name == "newpage") {
				if(elements[i].checked) target="new";
			}
			else if(elements[i].checked) {
				url += elements[i].name +"=on&";
			}
		}
		else {
			if(elements[i].name && elements[i].value != "") {
				url += elements[i].name +"="+ encodeURIComponent(elements[i].value) +"&";
			}
		}
	}
	if(target == "self") {
		byId("inst").style.display = "none";
		byId("map").style.display = "block";
		byId("map").src = "loading.png";
		byId("map").src = "map.php?"+url;
		byId("link").value = baseurl+"map.php?"+url;
	}
	else {
		window.location = "map.php?"+url;
	}
}

function help() {
	byId("inst").style.display = "block";
	byId("map").style.display = "none";
}


function toggle(name) {
	if(displayparts[name] == "show") displayparts[name] = "hide";
	else displayparts[name] = "show";

	set_cookie("travmap_"+name, displayparts[name], 7);
	updatepart(name);
}

function updatepart(name) {
	if(!byId(name)) return; /* there's no langopts in new index.php */

	if(get_cookie("travmap_"+name)) {
		displayparts[name] = get_cookie("travmap_"+name);
	}

	if(displayparts[name] == "show") {
		byId(name).style.display = "block";
	}
	else {
		byId(name).style.display = "none";
	}
}


function setsettings() {
	es = document.forms[0].elements;
	str = "";
	for(i=0; i<es.length; i++) {
		str += /*es[i].name+"::"+*/es[i].value+"//";
	}
	set_cookie("travmap_settings", str);
	alert("settings saved (maybe, still working on this...)");
}
function loadsettings() {
	str = get_cookie("travmap_settings");
	if(str == false) return;

	es = document.forms[0].elements;
	list = str.split("//");
	for(i=0; i<es.length; i++) {
		es[i].value = list[i];
	}
}


function get_cookie(name) {
	with(document.cookie) {
		var index=indexOf(name+"=");
		if(index==-1) return false;
		index=indexOf("=",index)+1;
		var endstr=indexOf(";",index);
		if(endstr==-1) endstr=length;
		return unescape(substring(index,endstr));
	}
}
function set_cookie(name,value,days) {
	if(days) {
		var date=new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires="; expires="+date.toGMTString();
	}
	else expires="";
	document.cookie=name+"="+value+expires+"; path=/";
}


