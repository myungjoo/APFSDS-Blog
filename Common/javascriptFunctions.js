var imageAutoResizeIMGObjects = new Array();
function noop()
{
}
function autoResize(maxWidth) 
{
    while(imageAutoResizeIMGObjects.length>0)
    {
	var image = imageAutoResizeIMGObjects.pop();
	if (image.width>maxWidth) {
	    image.width = maxWidth;
	    image.style.display='block';
	}
    }
}


var ToggleSerial = new Array();
ToggleSerial['divCommentShow'] = -1;
ToggleSerial['divMemoShow'] = -1;
function panelToggle(divID, content, topAlignDivID, serial)
{
	var div = document.getElementById(divID);
	var stab = '';
	if (topAlignDivID != "")
		stab = document.getElementById(topAlignDivID);
	if (serial!=-1 && (ToggleSerial[divID] == -1 || ToggleSerial[divID] != serial)) {
		div.style.display="block";
		ToggleSerial[divID] = serial;
		div.innerHTML = content+"<div id=\""+divID+"EndMark\"></div>";
		var endDiv = document.getElementById(divID+"EndMark");
		div.style.width="850px";
		div.style.height = String(endDiv.offsetTop)+"px";
		if (topAlignDivID != "") {
			var offset = 0;
			while (stab.offsetParent)
			{
				offset += stab.offsetTop;
				stab = stab.offsetParent;
			}
			div.style.top = String(offset)+"px";
		}
	} else {
		ToggleSerial[divID] = -1;
		div.innerHTML = "";
		div.style.width="0px";
		div.style.height="0px";
		div.style.display="none";
	}
}
function writePanelSetHeight(writeDiv, divFollowing)
{
	var writeDivObj = document.getElementById(writeDiv);
	var followingDivObj = document.getElementById(divFollowing);
	var iHeight = writeDivObj.scrollHeight;
	writeDivObj.style.height = (iHeight+100)+'px';
	followingDivObj.style.top = (iHeight+100)+'px';
}
function imoticonListPanel(divID)
{
	var div = document.getElementById(divID);
	var frame = null;
	if (div.innerHTML != "" && div.style.display=="block") {
		div.style.display="none";
	} else {
		if (div.innerHTML=="")
			div.innerHTML = '<iframe id="imoti'+window.name+'" frameborder="0" width="98%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Imoticons/imoticon.php?DO=USAGE&PARENTID='+divID+'">Iframe needed.</iframe>';
		div.style.width = "640px";
		div.style.display = "block";
		frame = document.getElementById('imoti'+window.name);
		div.style.height = (frame.scrollHeight)+"px";
	}
	setHeight2();
}
function addImoticon(objIDofParent, key)
{
	var obj= parent.document.getElementById(objIDofParent);
	var origin = obj.selectionStart;
	obj.focus();
	obj.value = obj.value.substr(0, obj.selectionStart) + '@' + key + ' ' + obj.value.substr(obj.selectionStart);
	obj.selectionStart = origin + key.length + 2 ;
	obj.selectionEnd = obj.selectionStart;
}
var lastCommand = '';
function writePanel(writeDiv, divFollowing, command, blogid, articlenum, inParent, argument)
{
	var writeDivObj;
	if (inParent)
		writeDivObj = parent.document.getElementById(writeDiv);
	else
		writeDivObj = document.getElementById(writeDiv);
	
	if (lastCommand!='' || command=='clear') {
		if (command=='clear' || lastCommand=='nameTag' || confirm('Sure to close current write panel?'))
		{
			lastCommand = '';
			writeDivObj.innerHTML = '';
			writeDivObj.style.height = '0px';
			writeDivObj.style.width = '850px';
			var followingDivObj;
			if (inParent)
				followingDivObj = parent.document.getElementById(divFollowing);
			else
				followingDivObj = document.getElementById(divFollowing);
			followingDivObj.style.top = '0px';
			if (command=='nameTag')
				return;
		}
		else
			return;
	}
	if (command=='write') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Blog/blogAux.php?DO=SHOWWRITE&BLOGID='+encodeURI(blogid)+'">Iframe needed</iframe>';
		lastCommand = 'write';
	} else if (command=='reply') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Blog/blogAux.php?DO=SHOWREPLY&BLOGID='+encodeURI(blogid)+'&THREAD='+encodeURI(articlenum)+'">Iframe needed</iframe>';
		lastCommand = 'write';
	} else if (command=='update') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Blog/blogAux.php?DO=SHOWUPDATE&BLOGID='+encodeURI(blogid)+'&ARTICLEID='+encodeURI(articlenum)+'&PASSWORD='+encodeURI(argument)+'">Iframe needed</iframe>';
		lastCommand = 'write';
	} else if (command=='tagManage') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Blog/blogAux.php?DO=SHOWMANAGETAG&BLOGID='+encodeURI(blogid)+'">Iframe needed</iframe>';
		lastCommand = '';
	} else if (command=='nameTag') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/profile/nameTag.php">Iframe needed</iframe>';
		lastCommand = 'nameTag';
	} else if (command=='imoticonManage') {
		writeDivObj.style.display = 'block';
		writeDivObj.innerHTML = '<iframe name="'+writeDiv+'" id="if'+writeDiv+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0" src="/Imoticons/imoticon.php">Iframe needed</iframe>';
		lastCommand = '';
	}

	writeDivObj.style.width = '850px';
}
function setHeightDiv(div, offset)
{
}
function min(x, y) {
	if (x<y)
		return x;
	else
		return y;
}
function max(x, y) {
	if (x<y)
		return y;
	else
		return x;
}
function setHeight()
{
	// When this window is independent
    var iHeight = document.body.scrollHeight;
    var divBody = document.getElementById('divBody');
    divBody.style.height = max(iHeight - 50, 25) + "px";
    var divLeft = document.getElementById('divLeft');
    divLeft.style.height = max(iHeight - 50, 25) + "px";
}
function setHeight2()
{
// When this window is dependent (inside of another window as an iframe)
    i = parent.document.getElementById(window.name);
	i2 = parent.document.getElementById('if'+window.name);
    iheight = document.body.scrollHeight;
    i.style.height = (iheight+20 ) + "px";
    i2.style.height = (iheight+20 ) + "px";
}
function setHeight3()
{
	cDiv = parent.document.getElementById('divContent');
	iheight = document.body.scrollHeight;
	cDiv.style.top = iheight+30+'px';
}
function openDivIframeToggle(url, divID)
{
	var div = document.getElementById(divID);
	if (div.innerHTML=='')
	{
		div.innerHTML = '<iframe name="'+divID+'" id="if'+divID+'" frameborder="0" width="100%" height="100%" scrolling="no" marginheight="0" marginwidth="0">APFSDS Blog requires iframe tags enabled</iframe>';
		var ifid = document.getElementById('if'+divID);
		ifid.src = url;
	}
	else
	{
		div.style.height='0px';
		div.innerHTML = '';
	}
}
function openDivToggleContent(divID, content, resizeSetHeight2)
{
	var div = document.getElementById(divID);
	if (div.innerHTML=='')
	{
		div.innerHTML=content;
		div.style.display='block';
		if (resizeSetHeight2!=false)
			setHeight2();
	}
	else
	{
		div.style.display='none';
		div.innerHTML='';
		if (resizeSetHeight2!=false)
			setHeight2();
	}
}
function countLines(str, perRow)
{
	var lines = 1;
	var last = 0;
	if (str.charAt(0)=='\n')
		lines ++;
	while (last != -1)
	{
		var oldLast = last;
		last = str.indexOf('\n', last+1);
		if (last==-1)
			oneLine = str.substr(oldLast, str.length - oldLast);
		else
			oneLine = str.substr(oldLast, last-oldLast);
		if (oneLine.length>perRow)
			lines += oneLine.length/perRow;
		lines ++;
	}
	return lines;
}
function deleteFromList(list, element)
{
	if (list.length==0)
		return list;
	var arrayList = list.split(',');
	var newList = '';
	var i=0;
	var count = 0;
	for (i=0; i<arrayList.length; i++)
	{
		if (arrayList[i]==element)
			continue;
		if (count==0)
			newList = arrayList[i];
		else
			newList = newList+','+arrayList[i];
		count++;
	}
	return newList;
}
function nullFunction(argument)
{
}
function parseInt2(string)
{
	var length=string.length;
	if (length==0)
		return 0;
	var i=0;
	var stringResult = '';
	for (i=0; i<length; i++)
	{
		if (string.substr(i, 1)>='0' && string.substr(i, 1)<='9')
			stringResult=stringResult+string.substr(i,1);
	}
	if (stringResult.length > 0)
		return parseInt(stringResult);
	return 0;
}
function inTheRange(target, min, max)
{
	if (target>max)
		return max;
	if (target<min)
		return min;
	return target;
}
function flip(value)
{
	if (value=='disabled')
		return 'enabled';
	return 'disabled';
}
function commaArrayFlip(target, index)
{
	if (target=='') return '';
	var targetArray = target.split(',');
	var newArray = '';
	var i=0;
	var count=0;
	for (i=0; i<targetArray.length; i++)
		if (i==0)
			if (i==index)
				newArray=flip(targetArray[i]);
			else
				newArray=targetArray[i];
		else
			if (i==index)
				newArray=newArray+','+flip(targetArray[i]);
			else
				newArray=newArray+','+targetArray[i];
	return newArray;
}
function distinct(list, value)
{
	var listArray = list.split(',');
	var i=0;
	for (i=0; i<listArray.length; i++)
		if (listArray[i]==value)
			return false;
	return true;
}
function listlizeAccessControlList(str)
{
	var list = '';
	var tokenCount=0;
	str.replace("\r", "");

	var strA1 = str.split(',');
	var i=0;
	for (i=0; i<strA1.length; i++)
	{
		var strA2 = strA1[i].split(':');
		var i2;
		for (i2=0; i2<strA2.length; i2++)
		{
			var strA3 = strA2[i2].split(' ');
			var i3;
			for (i3=0; i3<strA3.length; i3++)
			{
				var strA4 = strA3[i3].split('\n');
				var i4;
				for (i4=0; i4<strA4.length; i4++)
				{
					if (strA4[i4].length>0)
						if (list=='')
							list=strA4[i4];
						else
							list+=':'+strA4[i4];

				}
			}
		}
	}
	return list;
}
function showHiddenContentToggle(divId)
{
	var div = document.getElementById(divId);
	if (div.style.display=='none') {
		div.style.display='block';
		div.style.height = 'auto';
	}
	else {
		div.style.display='none';
		div.style.height='0px';
	}
}
function numEnabledNewTag(str)
{
	var strList = str.split(',');
	var i=0;
	var count=0;
	for (i=0; i<strList.length; i++)
	{
		if (strList[i]=='enabled')
			count++;
	}
	return count;
}
function addToList(str, element)
{
	var list = str.split(',');
	var i=0;
	if (str=='')
		return String(element);
	for (i=0; i<list.length; i++)
		if (list[i]==String(element))
			return str;
	return str+','+String(element);
}
function constructTagList(divID, tagID, tagTitle, formID)
{  // note that tagID/tagTitle's actual name outside of this function should be same.
	var rV = "";
	var i=0;
	var list = document.getElementById(formID).TAG.value.split(',');
	if (document.getElementById(formID).TAG.value!="")
		for (i=0; i<list.length; i++) {
			rV += "<a href=\"javascript:document.getElementById('"+formID+"').TAG.value=deleteFromList(document.getElementById('"+formID+"').TAG.value, "+list[i]+"); document.getElementById('"+divID+"').innherHTML = constructTagList('"+divID+"', tagID, tagTitle, '"+formID+"'); \">"+tagTitle[list[i]]+"</a>&nbsp; ";
		}
	document.getElementById(divID).innerHTML = rV;
}
