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
};


function updateMap() {
	var elements = document.forms[0].elements;
	var url = "";
	for(var i=0; i<elements.length; i++) {
		if(elements[i].type == "checkbox") {
			if(elements[i].checked) {
				url += elements[i].name +"=on&";
			}
		}
		else {
			if(elements[i].name && elements[i].value != "") {
				url += elements[i].name +"="+ encodeURIComponent(elements[i].value) +"&";
			}
		}
	}
	if(byId("newpage_check").checked == false) {
		byId("inst").style.display = "none";
		byId("about").style.display = "none";
		byId("map").style.display = "block";
		if(byId("format_select").value == "svg") {
			byId("map").innerHTML = ""+
				"<object data='map.php?"+url+"' type='image/svg+xml' width='768' height='512'></object>";
		}
		else {
			byId("map").innerHTML = "<img src='map.php?"+url+"' alt='map image'>";
		}
		byId("link").value = baseurl+"map.php?"+url;
	}
	else {
		window.location = "map.php?"+url;
	}
}


function getHTTPObject() {
	if (window.XMLHttpRequest){
		return new XMLHttpRequest();
	}
	else if(window.ActiveXObject){
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
}

function updateServers(country_box) {
	var country = country_box[country_box.selectedIndex].text;

	var server_list = document.getElementById("server_select");
	server_list.disabled = true;

	var http = getHTTPObject();
	http.open("GET", 'ajax.php?mode=servers&country='+country, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			server_list.innerHTML = "";
			var results = http.responseText.split("\n");

			for(var i=0; i<results.length; i++) {
				var parts = results[i].split(",");
				var server = parts[0];
				var enabled = parts[1];

				if(server.length > 0) {
					server_list.appendChild(new Option(server, server));
				}
			}

			server_list.disabled = false;
		}
	};
	http.send(null);
}

function saveServer(server_box) {
	var server = server_box[server_box.selectedIndex].text;
	set_cookie("travmap_server", server, 31);
}

function loadsettings() {
	var box = document.getElementById("server_select");
	var server = get_cookie("travmap_server");
	if(server) {
		for(var i=0; i<box.options.length; i++) {
			if(box.options[i].value == server) {
				byId("server_select").selectedIndex = i;
				break;
			}
		}
	}
}



function help() {
	byId("map").style.display = "none";
	byId("about").style.display = "none";
	byId("inst").style.display = "block";
}

function about() {
	byId("map").style.display = "none";
	byId("inst").style.display = "none";
	byId("about").style.display = "block";
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


