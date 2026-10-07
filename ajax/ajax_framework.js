function ajaxLoad(method, URL, data, displayId,indocator) {
	var ajax = null;
	if(window.ActiveXObject) {
		ajax = new 	ActiveXObject("Microsoft.XMLHTTP");
	}
	else if(window.XMLHttpRequest) {
		ajax = new 	XMLHttpRequest();
	}
	else {
		alert("Your browser doesn't support Ajax Please Use Internet Explore Version 6-7 Only");
		return;
	}
	
	method = method.toLowerCase();
	
	URL += "?dummy=" + (new Date()).getTime();
	if(method.toLowerCase()=="get") {
		URL += "&" + data;
		data = null;
  	}
	
	ajax.open(method,URL);
	
	if(method.toLowerCase()=="post") {
		ajax.setRequestHeader(
			"Content-Type","application/x-www-form-urlencoded");
	}
	ajax.onreadystatechange = function() {
		if(ajax.readyState==4 && ajax.status==200) {
			var ctype = ajax.getResponseHeader("Content-Type");
			ctype = ctype.toLowerCase();

			ajaxCallback(ctype, displayId, ajax.responseText,indocator);

			delete ajax;
			
			ajax = null;
		}
	}
	ajax.send(data);
}

function ajaxCallback(contentType,displayId,responseText,indocator) {
	if(contentType.match("text/javascript")) {
		eval(responseText);
	}
	else {
		var el = document.getElementById(displayId);
		if((el.type=='hidden')||(el.type=='text')||(el.type=='textarea')){
			el.value = responseText;
		}else if(el.type=='select-one') {
			function trim(s){ 
				var l=0; var r=s.length -1; 
				while(l < s.length && s[l] == ' ') { l++; } 
				while(r > l && s[r] == ' ') { r-=1; } 
				return s.substring(l, r+1); 
			}
			var results = responseText;
			while(el.childNodes.length>0){el.removeChild(el.childNodes[0]);}
			arr_list=results.split("###");
			if(arr_list.length-1!=0){
				for (var i=0; i < eval(arr_list.length-1); i++) {
					fn_arr=arr_list[i].split("@@@");
					if(fn_arr[1]!=""){
						el.options[i] = new Option(trim(fn_arr[0]),trim(fn_arr[1])); 
					}
				}
			}else{
				while(el.childNodes.length>0){el.removeChild(el.childNodes[0]);}
				el[0] = new Option(obj_default,'');
			}
		}else{
			el.innerHTML = responseText;
		}
	}
	if(indocator!=""){
		document.getElementById(indocator).style.display = 'none';
	}
	document.getElementById(displayId).style.display = 'block';
}

function getFormData(form_name_or_id) {
	//alert("form not found!");
	var frm = document.forms[form_name_or_id];
	if(frm==null) {
		alert("form not found!");
		return;
	}
	var data = "";
	var num_el = frm.elements.length;
	for (i=0; i<num_el; i++) {
		var el = frm.elements[i];
		if(el.name=="" && el.id=="") {
			continue;
		}
		var param_name = "";
		if(el.name!="") {
			param_name = el.name;
		}
		else if(el.id!="") {
			param_name = el.id;
		}
		var t = frm.elements[i].type;
		var value = "";
		if(t=="text"||t=="password"||t=="hidden"||t=="textarea") {
			value = encodeURI(el.value);
		}
		else if(t=="radio"||t=="checkbox") {
			if(el.checked) {
				value = encodeURI(el.value);	
			}
			else {
				continue;
			}
		}
		else if(t=="select-one") {
			value = encodeURI(el.options[el.selectedIndex].value);
		}
		else if(t=="select-multiple") {
			for(j=0; j<el.length; j++) {
				if(el.options[j].selected) {
					if(data!="") {
						data += "&";
					}
					data += param_name + "=";
					data += encodeURI(select.options[j].value);	
				}
			}
			continue;
		}
		if(data!="") {
			data += "&";	
		}
		data += param_name + "=" + value;
	}
	return data;
}
function addCommas(nStr) {
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}
function ThaiBaht(objOwner,textShow){
	var EnumNumber = objOwner.value;
	EnumNumber = EnumNumber.replace(',','');
	EnumNumber = EnumNumber.replace(',','');
	if(isNaN(EnumNumber)) {
		var tmpVal = EnumNumber.length;
		EnumNumber = EnumNumber.substring(0,(EnumNumber.length-1));
		objOwner.value = EnumNumber;
		alert("????????????????????????");
		return;
	}
	var URL = "fn_numtothai.php?number=" + EnumNumber + "&emp=" ;
	ajaxLoad('get', URL, '', textShow,'');
	objOwner.value = addCommas(EnumNumber);
}
