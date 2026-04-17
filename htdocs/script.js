/*
 * script.js (c) Shish 2006
 */

// Get the base URL dynamically from the current location
var baseurl =
  window.location.origin + window.location.pathname.replace(/[^/]*$/, "");

var displayparts = {
  advanced: "hide",
  output: "hide",
  instructions: "show",
  langopts: "show",
};

function byId(id) {
  return document.getElementById(id);
}

window.addEventListener("DOMContentLoaded", function () {
  updatepart("advanced");
  updatepart("output");
  updatepart("instructions");
  updatepart("langopts");
  loadsettings();

  // Attach event handlers
  const helpLink = byId("help-link");
  if (helpLink) {
    helpLink.addEventListener("click", function (e) {
      e.preventDefault();
      help();
    });
  }

  const advancedToggle = byId("advanced-toggle");
  if (advancedToggle) {
    advancedToggle.addEventListener("click", function (e) {
      e.preventDefault();
      toggle("advanced");
    });
  }

  const outputToggle = byId("output-toggle");
  if (outputToggle) {
    outputToggle.addEventListener("click", function (e) {
      e.preventDefault();
      toggle("output");
    });
  }

  const mapForm = document.querySelector(".map-form");
  if (mapForm) {
    mapForm.addEventListener("submit", function (e) {
      e.preventDefault();
      updateMap();
    });
  }

  const countrySelect = byId("country_select");
  if (countrySelect) {
    countrySelect.addEventListener("change", function () {
      updateServers(this);
    });
  }

  const serverSelect = byId("server_select");
  if (serverSelect) {
    serverSelect.addEventListener("change", function () {
      saveServer(this);
    });
  }
});

function updateMap() {
  var elements = document.forms[0].elements;
  var url = "";
  for (var i = 0; i < elements.length; i++) {
    if (elements[i].type == "checkbox") {
      if (elements[i].checked) {
        url += elements[i].name + "=on&";
      }
    } else {
      if (elements[i].name && elements[i].value != "") {
        url +=
          elements[i].name + "=" + encodeURIComponent(elements[i].value) + "&";
      }
    }
  }
  if (byId("newpage_check").checked == false) {
    byId("inst").style.display = "none";
    byId("about").style.display = "none";
    byId("map").style.display = "block";
    if (byId("format_select").value == "svg") {
      byId("map").innerHTML =
        "" +
        "<object data='map.php?" +
        url +
        "' type='image/svg+xml' width='768' height='512'></object>";
    } else {
      byId("map").innerHTML = "<img src='map.php?" + url + "' alt='map image'>";
    }
    byId("link").value = baseurl + "map.php?" + url;
  } else {
    window.location = "map.php?" + url;
  }
}

function updateServers(country_box) {
  var country = country_box[country_box.selectedIndex].text;

  var server_list = document.getElementById("server_select");
  server_list.disabled = true;
  server_list.innerHTML = "";

  var servers = serverData[country] || [];
  for (var i = 0; i < servers.length; i++) {
    var server = servers[i];
    if (server.name) {
      server_list.appendChild(new Option(server.name, server.name));
    }
  }

  server_list.disabled = false;
}

function saveServer(server_box) {
  var server = server_box[server_box.selectedIndex].text;
  set_cookie("travmap_server", server, 31);
}

function loadsettings() {
  var box = document.getElementById("server_select");
  var server = get_cookie("travmap_server");
  if (server) {
    for (var i = 0; i < box.options.length; i++) {
      if (box.options[i].value == server) {
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
  if (displayparts[name] == "show") displayparts[name] = "hide";
  else displayparts[name] = "show";

  set_cookie("travmap_" + name, displayparts[name], 7);
  updatepart(name);
}

function updatepart(name) {
  if (!byId(name)) return; /* there's no langopts in new index.php */

  if (get_cookie("travmap_" + name)) {
    displayparts[name] = get_cookie("travmap_" + name);
  }

  if (displayparts[name] == "show") {
    byId(name).style.display = "block";
  } else {
    byId(name).style.display = "none";
  }
}

function get_cookie(name) {
  var cookies = document.cookie;
  var index = cookies.indexOf(name + "=");
  if (index === -1) return false;
  index = cookies.indexOf("=", index) + 1;
  var endstr = cookies.indexOf(";", index);
  if (endstr === -1) endstr = cookies.length;
  return decodeURIComponent(cookies.substring(index, endstr));
}
function set_cookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie =
    name + "=" + encodeURIComponent(value) + expires + "; path=/";
}
