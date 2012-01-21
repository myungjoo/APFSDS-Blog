<?
// List of functions
// Output as a string.
// Be sure not to have '\n' in the string. When necessary (for showUpdateArticleonly), use '\\n'.


function getBlogProperty($blogID)
{
    $rs = DBQ("select * from Blog where id='$blogID'");
    $r = mysql_fetch_assoc($rs);
    mysql_free_result($rs);
    return $r;
}
function getAccessBlog($id, $blogID)
{
    $returnValue['index'] = false;
    $returnValue['read'] = false;
    $returnValue['write'] = false;
    $returnValue['comment'] = false;
    $rs = DBQ("select accessControlIndex, accessControlRead, accessControlWrite, accessControlComment from Blog where id='$blogID'");
    $accessValues = mysql_fetch_row($rs);
    mysql_free_result($rs);
    if (!$accessValues) return false;

    $inTheAccess = false;
    $rs = DBQ("select * from BlogAccessControl where blogID='$blogID' and user='$id'");
    $r = mysql_fetch_row($rs);
    if ($r) $inTheAccess = true;
    mysql_free_result($rs);

    if ($accessValues[0]==0 || $inTheAccess) $returnValue['index']=true;
    if ($accessValues[1]==0 || $inTheAccess) $returnValue['read']=true;
    if ($accessValues[2]==0 || $inTheAccess) $returnValue['write']=true;
    if ($accessValues[3]==0 || $inTheAccess) $returnValue['comment']=true;
    return $returnValue;
}
function getAccessTag($id, $blogID, $tagID)
{
    $rs = DBQ("select * from BlogTag where blogID='$blogID' and tagID=$tagID");
    $r = mysql_fetch_assoc($rs);
    mysql_free_result($rs);
    $returnValue = array();
    if ($r) {
	$returnValue['permissionRead'] = $r['permissionRead'];
	$returnValue['permissionWrite'] = $r['permissionWrite'];
	$returnValue['accessControlRead'] = true;
	$returnValue['accessControlWrite'] = true;

	if ($r['accessControlWrite']===1) 
	{
	    $rsTAC = DBQ("select * from BlogTagAccessControl where blogID='$blogID' and tagID=$tagID and user='".$id."'");
	    if (mysql_fetch_row($rsTAC)) $returnValue['accessControlWrite'] = true;
	    else $returnValue['accessControlWrite'] = false;
	    mysql_free_result($rsTAC);
	}
	if ($r['accessControlRead']===1) {
	    $rsTAC = DBQ("select * from BlogTagAccessControl where blogID='$blogID' and tagID=$tagID and user='".$id."'");
	    if (mysql_fetch_row($rsTAC)) $returnValue['accessControlRead'] = true;
	    else $returnValue['accessControlRead'] = false;
	    mysql_free_result($rsTAC);
	}
    }
    else return false;
    return $returnValue;
}
function getAttached($blogID, $articleID)
{
    $result = array();
    $rs = DBQ("select filename, attachmentID from BlogArticleAttached where blogID='$blogID' and articleID=$articleID order by attachmentID ASC");
    while ($r = mysql_fetch_row($rs))
	$result[$r[1]] = $r[0];
    mysql_free_result($rs);
    return $result;
}
function getAccessBlogArticle($id, $blogID, $articleID)
{
    $returnValue['read'] = true;
    $returnValue['write'] = true;
    $returnValue['permissionRead'] = 0;
    $returnValue['permissionWrite'] = 0;
    $rs = DBQ("select articleID from BlogArticle where blogID='$blogID' and articleID=$articleID");
    $r = mysql_fetch_row($rs);
    mysql_free_result($rs);
    if (!$r) return false;

    $rs = DBQ("select tagID from BlogTagArticleAssoc where blogID='$blogID' and articleID=$articleID");
	while ($r = mysql_fetch_row($rs))
	{
	    $tagID = $r[0];
	    $rs2 = DBQ("select accessControlRead, accessControlWrite, permissionRead, permissionWrite from BlogTag where blogID='$blogID' and tagID=$tagID");
	    $r2 = mysql_fetch_row($rs2);
	    if (!$r2) return false;
	    if ($r2[2] > $returnValue['permissionRead'])
		$returnValue['permissionRead'] = $r2[2];
	    if ($r2[3] > $returnValue['permissionWrite'])
		$returnValue['permissionWrite'] = $r2[3];
	    $inTheAccess = false;
	    $rs3 = DBQ("select * from BlogTagAccessControl where blogID='$blogID' and tagID=$tagID and user='$id'");
	    $r3 = mysql_fetch_row($rs3);
	    if ($r3) $inTheAccess = true;
	    mysql_free_result($rs3);
	    if (!$inTheAccess && $r2[0]!=0)
		$returnValue['read'] = false;
	    if (!$inTheAccess && $r2[1]!=0)
		$returnValue['write'] = false;
	    mysql_free_result($rs2);
	}
    return $returnValue;
}
function showList($blogID, $skip, $divPrefix, $search=false, $widthOverride=false, $writeButton=false, $indexOnly = false)
{
    global $URL, $divPopup;
    $returnValue = '';
    if ($skip<0)
    $skip = 0;
    if ($skip=='')
    $skip = 0;
    // when $search is not false
    // $search['startDate']
    // $search['endDate']
    // $search['tag'] = array of tagID

    $property = getBlogProperty($blogID);
    if ($property['permissionIndex']>$_SESSION['APFSDS_Perm'])
    return showError("ERROR: Permission Denied. (Permission) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
    $access = getAccessBlog($_SESSION['APFSDS_ID'], $blogID);
    if ($access['index']===false)
    return showError("ERROR: Permission Denied. (This blog is CUG.)");

    if ($property['permissionWrite']<=$_SESSION['APFSDS_Perm'] && $writeButton) {
	$returnValue.=
	"<input type=\"button\" value=\"Write an article\" onclick=\"document.getElementById('writeArticle').innerHTML='".
	htmlPropertySafe("<iframe name=\"writeArticle\" id=\"ifwriteArticle\" frameborder=\"0\" width=\"850px\" marginheight=\"0px\" marginwidth=\"0px\" src=\"".$URL."Blog/blogAux.php?DO=SHOWWRITE&BLOGID=".urlencode($blogID)."&contentResize=no\">Iframe Needed</iframe>").
	"'; \" />".
	"<div id=\"writeArticle\"></div>";
    }

    $whereClause = '';

    if ($search!==false) {
	if (isset($search['startDate']) && strlen($search['startDate'])>0) {
	    $whereClause = $whereClause."and (art.createDate>='".$search['startDate'].
	    "' or art.modifyDate>='".$search['startDate']."' ) ";
	}
	if (isset($search['endDate']) && strlen($search['endDate'])>0) {
	    $whereClause = $whereClause."and (art.createDate<='".$search['endDate'].
	    "' or art.modifyDate<='".$search['endDate']."' ) ";
	}
	if (isset($search['tag']) && count($search['tag'])>0) {
	    foreach ($search['tag'] as $tag) {
		$whereClause=$whereClause."and art.articleID in (select tag.articleID from BlogTagArticleAssoc tag where blogID='$blogID' and tag.articleID=art.articleID and tag.tagID=".($tag+0).") ";
	    }
	}
    }

    // Permission Check
    $whereClause = $whereClause.
	' and ( (SELECT count(tagAS.tagID) FROM BlogTagArticleAssoc AS tagAS, BlogTag AS tag WHERE tagAS.blogID=art.blogID and tagAS.articleID=art.articleID and tagAS.tagID=tag.tagID and (tag.permissionRead > '.$_SESSION['APFSDS_Perm'].' or (tag.accessControlRead=1 and (SELECT count(tagAC.user) FROM BlogTagAccessControl AS tagAC WHERE tagAC.blogID=art.blogID and tagAC.tagID=tag.tagID and user=\''.$_SESSION['APFSDS_ID'].'\')=0  ))) = 0) ';



    if ($property['type']==2)
	$rs = DBQ("SELECT * FROM BlogArticle AS art WHERE art.blogID='$blogID' $whereClause ORDER BY art.modifyDate DESC");
    else
	$rs = DBQ("SELECT * FROM BlogArticle AS art WHERE art.blogID='$blogID' $whereClause ORDER BY art.articleID DESC");
    $num = mysql_num_rows($rs);

    if ($num==0) {
	print $returnValue;
        return showError("ERROR: No Article Found ");
    }
	if ($skip >= $num)
	    return showError("ERROR: Skipping too many (Skip:$skip / Num:$num)");
	mysql_data_seek($rs, $skip);

	if ($widthOverride===false)
	    $width = " width=\"$widthOverride\" ";
	else
	    $width = " width=\"".$property['optionTableWidth']."\" ";

	// Top Index
	$indexText = 'th';
	$skipP1 = $skip + 1;
	if (($skipP1 % 10) == 1) $indexText = 'st';
	if (($skipP1 % 10) == 2) $indexText = 'nd';
	if (($skipP1 % 10) == 3) $indexText = 'rd';
	$returnValue .= "<div class=\"blogIndex\"><table><tr><td style=\"width:50%; left:0px\">";
	$linkNumLeft = 0;
	$linkNumRight = 0;
	$linkTextLeft = array();
	$linkTextRight = array();
	$linkIndexLeft = '';
	$linkIndexRight = '';

	$searchValue = '';
	if (isset($search['startDate']) && strlen($search["startDate"])>0)
	    $searchValue.='<input type="hidden" name="sStartDate" value="'.$search['startDate'].'" />';
	if (isset($search['endDate']) && strlen($search["endDate"])>0)
	    $searchValue.='<input type="hidden" name="sEndDate" value="'.$search['endDate'].'" />';
	if (isset($search['tag']) && is_array($search["tag"]) && count($search["tag"])>0)
	    foreach($search["tag"] as $index => $value)
		$searchValue.='<input type="hidden" name="sTagArray[]" value="'.$value.'" />';
	if ($skip == 0)
		$returnValue = $returnValue.($linkIndexLeft="First Page ");
	else {
	    $skipped = $skip - $property['perPage'];
	    while ($skipped >= 0) {
		$returnValue=$returnValue.$searchValue.
		"<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix."bIL".$linkNumLeft."\">".
		"<input type=\"hidden\" name=\"SKIP\" value=\"$skipped\" />".
		"</form>";
		if ($linkNumLeft==0)
		$linkTextLeft[$linkNumLeft] = 'Previous Page';
		else
		$linkTextLeft[$linkNumLeft] = '-'.($linkNumLeft+1);
		$linkNumLeft++;

		$skipped -= $property['perPage'];
		if ($linkNumLeft>=10)
		break;
	    }
	    $returnValue=$returnValue.
	    "<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix."bIL".$linkNumLeft."\">".$searchValue.
	    "<input type=\"hidden\" name=\"SKIP\" value=\"0\" />".
	    "</form>";
	    $linkTextLeft[$linkNumLeft] = 'First Page';
	    $linkNumLeft++;

	    for ($i = $linkNumLeft-1 ; $i >= 0; $i--) {
		$linkIndexLeft = $linkIndexLeft.
		"<a href=\"about:".$linkTextLeft[$i]."\" onclick=\"document.".$divPrefix."bIL".$i.".submit(); return false;\">".$linkTextLeft[$i]."</a>&nbsp;";
	    }
		$returnValue = $returnValue.$linkIndexLeft;
	}
	$returnValue = $returnValue.
		"</td><td style=\"left:50%; text-align:right\">";
	if (($skip+$property['perPage'])>=$num) {
		$returnValue = $returnValue.($linkIndexRight="Last Page");
	}
	else {
	    $skipped = $skip + $property['perPage'];
	    while ($skipped < $num) {
		$returnValue=$returnValue.
		"<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix."bIR".$linkNumRight."\">".$searchValue.
		"<input type=\"hidden\" name=\"SKIP\" value=\"$skipped\" />".
		"</form>";
		if ($linkNumRight==0)
		    $linkTextRight[$linkNumRight] = 'Next Page';
		else
		    $linkTextRight[$linkNumRight] = '[+'.($linkNumRight+1).']';
		$linkNumRight++;

		$skipped += $property['perPage'];
		if ($linkNumRight>=10)
		    break;
	    }
	    $returnValue.="<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix."bIR".$linkNumRight."\">".$searchValue.
		"<input type=\"hidden\" name=\"SKIP\" value=\"".($num-5)."\" />".
		"</form>";
	    $linkTextRight[$linkNumRight] = 'Last Page';
	    $linkNumRight++;

	    for ($i = 0; $i<=$linkNumRight; $i++) {
		$linkIndexRight = $linkIndexRight.
		"<a href=\"about:".$linkTextRight[$i]."\" onclick=\"document.".$divPrefix."bIR".$i.".submit(); return false;\">".$linkTextRight[$i]."</a>&nbsp;";
	    }
	    $returnValue.=$linkIndexRight;
	}
	$returnValue.="</td></tr></table></div>";

	if ($indexOnly == true)
	{
		return $returnValue;
	}

	// Article List
	$shown = 0;
	$javascript = '';
	while ($r = mysql_fetch_assoc($rs)) {
	    // Title line
	    //$javascript=$javascript."blogURL".$divPrefix."[".$shown."]='".$URL."Blog/blogAux.php?".urlencode("DO=READARTICLE&BLOGID=$blogID&ARTICLEID=".$r['articleID']."'; blogDiv".$divPrefix."[".$shown."]='$divPrefix".$r['articleID'])."';";
	    $articleAccess = getAccessBlogArticle($_SESSION['APFSDS_ID'], $blogID, $r['articleID']);
	    if ($articleAccess['read']==false)
		continue;
	    if ($articleAccess['permissionRead']>$_SESSION['APFSDS_Perm'])
		continue;

	    switch($property['type']) {
		case 2: // Blog
		    $returnValue.=
			"<div class=\"blogHeader\">".
			    "<div class=\"blogHeaderFull1stLine\" style=\"display:block\">".
				"&nbsp;".$r['title']."&nbsp;".
			    "</div>".
			    "<table><tr><td style=\"width:500px\"><div class=\"blogHeaderFull2ndLine1stColumn\" style=\"display:inline;\">";
		    $rsTag = DBQ("select a.tagID, t.tagTitle from BlogTagArticleAssoc AS a, BlogTag as t where a.blogID='$blogID' and a.articleID=".$r['articleID']." and a.blogID=t.blogID and a.tagID=t.tagID");
		    $tagIndex=0;
		    while($rt = mysql_fetch_row($rsTag))
		    {
			$returnValue.="<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix.$r['articleID']."tag$tagIndex\">".
			    "<input type=\"hidden\" name=\"sTag\" value=\"".$rt[0]."\" />".
			    "<a href=\"about:".$rt[1]."\" onclick=\"document.$divPrefix".$r['articleID']."tag$tagIndex.submit(); return false;\">".html2text($rt[1])."</a></form>&nbsp; ";
			$tagIndex++;
		    }
		    mysql_free_result($rsTag);

		    $returnValue.= "<br /><br />".
				(($r['author']==null)?($r['guestName']):
				("<a href=\"about:".$r['author']."\" onclick=\"window.open('".$URL."Common/miscFunction.php?".
				"DO=describeAuthor&amp;AUTHOR=".urlencode($r['author'])."'); return false;\">".$r['author']."</a>")).
				    
			    "</div></td>".
			    "<td style=\"width:350px\"><div class=\"blogHeaderFull2ndLine2ndColumn\" style=\"display:inline;width:350px\">".
				$r['createDate'].
				(($r['modifyDate']!=$r['createDate'])?('<br />'.$r['modifyDate']).' (modified)':'').
				'<br /><a href="'.
				$URL.'?BLOG='.urlencode($blogID).'&Article='.urlencode($r['articleID']).'" target="_blank">'.$URL.'?BLOG='.urlencode($blogID).'&Article='.urlencode($r['articleID']).'</a>'.
			    "</div></td></tr></table>".
			    ((strlen($r['trackbackFrom'])>0)?('<div class="blogHeaderFull3rdLine">Trackback From <a href="'.$r['trackbackFrom'].'" target="_blank">&nbsp;'.$r['trackbackFrom'].'&nbsp;</a></div>'):('')).
			"</div>".
			"<div style=\"width:850px; display:block; height:0px;\" class=\"blogArticle\" id=\"$divPrefix".$r['articleID']."\">".
			    '<iframe name="'.$divPrefix.$r['articleID'].'" id="if'.
			    $divPrefix.$r['articleID'].'" frameborder="0" width="100%" height="100%"'.
			    'scrolling="no" marginheight="0" marginwidth="0" src="'.
			    $URL."Blog/blogAux.php?DO=READARTICLE&amp;BLOGID=".urlencode($blogID)."&amp;ARTICLEID=".urlencode($r['articleID']).
			    '">Need support for iframe tag</iframe>'.
			"</div><br /><br />";


		    break;
		case 1: // GuestBook
		case 0: // Web Board
		    $returnValue.="<div class=\"blogHeader\">".
			"<table><tr>".
			"<td class=\"blogHeaderTag\"><div>";

		    $rsTag = DBQ("select a.tagID, t.tagTitle from BlogTagArticleAssoc AS a, BlogTag as t where a.blogID='$blogID' and a.articleID=".$r['articleID']." and a.blogID=t.blogID and a.tagID=t.tagID");
		    $tagIndex=0;
		    while($rt = mysql_fetch_row($rsTag))
		    {
			$returnValue.="<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix.$r['articleID']."tag$tagIndex\">".
			    "<input type=\"hidden\" name=\"sTag\" value=\"".$rt[0]."\" />".
			    "<a href=\"about:".$rt[1]."\" onclick=\"document.$divPrefix".$r['articleID']."tag$tagIndex.submit(); return false;\">".html2text($rt[1])."</a></form>&nbsp; ";
			$tagIndex++;
		    }
		    mysql_free_result($rsTag);

		    $rsC = DBQ("select count(commentID) from BlogComment where blogID='$blogID' and articleID=".$r['articleID']);
		    $rc = mysql_fetch_row($rsC);
		    $cNum = $rc[0];
		    mysql_free_result($rsC);

		    $returnValue.="</div></td>".
			"<td class=\"blogHeaderDate\"><div>".substr($r['createDate'],0,10)."</div></td>";
		    $returnValue.=((($property['modeGuestPolicy']==1)&&($r['author']==null))?"<td class=\"blogHeaderWriterGuest\">":"<td class=\"blogHeaderWriter\">").
			"<div>".
			(($r['author']==null)?$r['guestName']:
			"<a href=\"about:".$r['author']."\" onclick=\"window.open('".$URL."Common/miscFunction.php?".
			"DO=describeAuthor&amp;AUTHOR=".urlencode($r['author'])."'); return false;\">".$r['author']."</a>").
			"</div></td>".
			"<td class=\"blogHeaderTitle\"><div><a href=\"javascript:".
			"openDivIframeToggle('".$URL."Blog/blogAux.php?"."DO=READARTICLE&amp;BLOGID=".urlencode($blogID)."&amp;ARTICLEID=".urlencode($r['articleID'])."','".$divPrefix.$r['articleID']."');\">".
			html2text($r['title'])."</a>&nbsp;</div></td><td class=\"blogHeaderTitleCount\">".(($cNum>0)?"[".$cNum."]":"")."</td>".
			"</tr></table>";

		    // Content line
		    $returnValue = $returnValue."<div style=\"width:100%; display:inline; height:0px;\" class=\"blogArticle\" id=\"$divPrefix".$r['articleID']."\">";
		    if ($property['type']==1 || ($property['type']==2 && ($property['optionOpenBlog']==2 || 
			($property['optionOpenBlog']==1 && $skip==0))))
		    { // OPEN THE ARTICLES ON THE LIST
		    $returnValue = $returnValue.'<iframe name="'.$divPrefix.$r['articleID'].'" id="if'.
		    $divPrefix.$r['articleID'].'" frameborder="0" width="100%" height="100%"'.
		    'scrolling="no" marginheight="0" marginwidth="0" src="'.
		    $URL."Blog/blogAux.php?DO=READARTICLE&amp;BLOGID=".urlencode($blogID)."&amp;ARTICLEID=".urlencode($r['articleID']).
		    '">Need support for iframe tag</iframe>';
		    }
		    $returnValue = $returnValue."</div></div>";
	    }

	    $shown ++;
	    if ($shown >= $property['perPage'])
	    break;
	}
	mysql_free_result($rs);

	// Bottom Index
	$returnValue.=
	    "<div class=\"blogIndex\"><table><tr><td style=\"width:50%; left:0px\">".$linkIndexLeft.
	    "</td><td style=\"left:50%; text-align:right\">".$linkIndexRight.
	    "</td></tr></table></div><br /><br />";

	return $returnValue;
}
function returnTitle($blogID, $articleID)
{
	global $URL, $divWritePanel, $divContent;
	$returnValue = '';

	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID");
	$article = mysql_fetch_assoc($rs);
	if (!$article)
	{
		mysql_free_result($rs);
		return showError("ERROR: Cannot retrive $blogID - $articleID");
	}
	mysql_free_result($rs);

	// Permission/Access Check
	if ($property['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$access = getAccessBlog($_SESSION['APFSDS_ID'], $blogID);
	if ($access['read']===false)
		return showError("ERROR: Permission Denied. (This blog is CUG.)");
	$accessTag = getAccessBlogArticle($_SESSION['APFSDS_ID'], $blogID, $articleID);
	if ($accessTag['read']===false)
		return showError("ERROR: Permission Denied. (This article's tag is CUG.)");
	if ($accessTag['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission of tag) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$okToComment = true;
	if ($property['permissionComment']>$_SESSION['APFSDS_Perm'])
		$okToComment=false;
	if ($access['comment']===false)
		$okToComment=false;

	return $article['title'];
}
function showArticle($blogID, $articleID, $independent=false, $showHeader=false)
{
	global $URL, $divWritePanel, $divContent;
	$returnValue = '';

	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID");
	$article = mysql_fetch_assoc($rs);
	if (!$article)
	{
		mysql_free_result($rs);
		return showError("ERROR: Cannot retrive $blogID - $articleID");
	}
	mysql_free_result($rs);
	DBQ("update BlogArticle set hit=hit+1 where blogID='$blogID' and articleID=$articleID");

	// Permission/Access Check
	if ($property['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$access = getAccessBlog($_SESSION['APFSDS_ID'], $blogID);
	if ($access['read']===false)
		return showError("ERROR: Permission Denied. (This blog is CUG.)");
	$accessTag = getAccessBlogArticle($_SESSION['APFSDS_ID'], $blogID, $articleID);
	if ($accessTag['read']===false)
		return showError("ERROR: Permission Denied. (This article's tag is CUG.)");
	if ($accessTag['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission of tag) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$okToComment = true;
	if ($property['permissionComment']>$_SESSION['APFSDS_Perm'])
		$okToComment=false;
	if ($access['comment']===false)
		$okToComment=false;

	$javascript = (($independent)?'':'setHeight2();');
	$trackbackAddress = $property['accessURL']."?Article=".urlencode($articleID);
	$trackbackAddressPlain = $property['accessURL']."?Article=$articleID";

	if ($independent || $showHeader || request("HEADER")=='YES')
	{
		$returnValue=$returnValue."<div class=\"blogShowHeader\"><table>".
			"<tr><td colspan=\"4\" class=\"blogShowHeaderTitle\"><div>".html2text($article['title'])."</div></td></tr>".
			"<tr><td class=\"blogHeaderAuthor\"><div>".
				(($article['author']=='' || $article['author']==null)?
					("<a href=\"mailto:".htmlPropertySafe($article['guestEmail'])."\">".$article['guestName']."</a>".
					 "<a href=\"".htmlPropertySafe($article['guestHomepage'])."\" target=\"_blank\">Homepage</a>"):
					("<a href=\"".$URL."Common/miscFunction.php?DO=describeAuthor&AUTHORID=".urlencode($article['author'])."\" target=\"_blank\">".$article['author']."</a>")
				).
			"</div></td>".
				"<td class=\"blogHeaderTag\" colspan=\"2\"><div>";
		$rsTag = DBQ("select a.tagID, t.tagTitle from BlogTagArticleAssoc AS a, BlogTag as t where a.blogID='$blogID' and a.articleID=".$articleID." and a.blogID=t.blogID and a.tagID=t.tagID");
		$tagIndex=0;
		$divPrefix = 'inArticleShowForm';
		while($rt = mysql_fetch_row($rsTag))
		{
			$returnValue=$returnValue.
				"<form method=\"post\" action=\"".$property['accessURL']."\" name=\"".$divPrefix.$articleID."tag$tagIndex\">".
				"<input type=\"hidden\" name=\"sTag\" value=\"".$rt[0]."\" />".
				"<a href=\"about:".$rt[1]."\" onclick=\"document.$divPrefix".$r['articleID']."tag$tagIndex.submit(); return false;\">".html2text($rt[1])."</a></form>&nbsp; ";
			$tagIndex++;
		}
		mysql_free_result($rsTag);
		$rsC = DBQ("select count(commentID) from BlogComment where blogID='$blogID' and articleID=".$articleID);
		$rc = mysql_fetch_row($rsC);
		$cNum = $rc[0];
		mysql_free_result($rsC);
		$returnValue.=
			"</div></td>".
			"<td class=\"blogHeaderDate\"><div>".$article['createDate'].
				(($article['createDate']==$article['modifyDate'])?"":"<br />".$article['modifyDate']).
			"</div></td>".
			"<td class=\"blogHeaderHit\"><div>".
				$article['hit'].
			"</div></td></tr></table></div>";
	}
	$attachedList = array();
	$attachedListCount = 0;
	if ($article['attachedFiles']>0)
	{
		$returnValue=$returnValue."<div class=\"blogShowAttachment\">";
		if ($article['attachedMethod']==0) // Old. Show the file if it's a picture
		{
		    $count = 0;
		    $rsA = DBQ("select filename, attachmentID from BlogArticleAttached where blogID='$blogID' and articleID=$articleID order by attachmentID ASC");
		    while ($attached=mysql_fetch_row($rsA))
		    {
			$attachedList[$attached[1]] = $attached[0];
			if (strcasecmp(".gif",substr($attached[0], strlen($attached[0])-4))==0 ||
			strcasecmp(".jpg",substr($attached[0], strlen($attached[0])-4))==0 ||
			strcasecmp(".jpeg",substr($attached[0], strlen($attached[0])-5))==0 ||
			strcasecmp(".png",substr($attached[0], strlen($attached[0])-4))==0 ||
			strcasecmp(".bmp",substr($attached[0], strlen($attached[0])-4))==0)
			{
			    $returnValue=$returnValue."<img src=\"".$URL."Blog/AttachedFiles/Old/".$attached[0]."\" alt=\"attached picture\" id=\"attachedByDefault$count\" /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('attachedByDefault$count'));</script><br />";
			    $count++;
			    }
			    $returnValue=$returnValue."<a href=\"".$URL."Blog/AttachedFiles/Old/".$attached[0]."\" target=\"_blank\">".$attached[0]."</a><br />";
			    }
			    mysql_free_result($rsA);
		}
		else // New. Do Not show the file. Just the list of them.
		{
			$rsA = DBQ("select filename, attachmentID from BlogArticleAttached where blogID='$blogID' and articleID=$articleID order by attachmentID ASC");
			while ($attached=mysql_fetch_row($rsA))
			{
				$attachedList[$attached[1]] = $attached[0];
				/*
				if (strcasecmp(".gif",substr($attached[0], strlen($attached[0])-4))==0 ||
						strcasecmp(".jpg",substr($attached[0], strlen($attached[0])-4))==0 ||
						strcasecmp(".jpeg",substr($attached[0], strlen($attached[0])-5))==0 ||
						strcasecmp(".png",substr($attached[0], strlen($attached[0])-4))==0 ||
						strcasecmp(".bmp",substr($attached[0], strlen($attached[0])-4))==0)
				{
					$returnValue=$returnValue."<img src=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".$attached[0]."\" alt=\"attached picture\" /><br />";
				}
				*/
				$returnValue=$returnValue."<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".($attached[0])."\" target=\"_blank\">".$attached[0]."</a><br />";
			}
			mysql_free_result($rsA);
		}
		$returnValue=$returnValue."</div>";
	}
	$rgbR = rand(240,250);
	$rgbG = rand(240,250);
	$rgbB = rand(240,250);
	$returnValue=$returnValue."<div class=\"blogShowContent\" style=\"width:850px;background:rgb($rgbR , $rgbG, $rgbB )\"><div style=\"width:840px\">".
		str_replace(array("\n"),array("<br />"), 
			htmlConvention(
				(($article['html']==1)?$article['content']:
				 str_replace(array("<", ">"), array("&lt;", "&gt;"), $article['content'])),
				$attachedList, $articleID, $blogID
		)).
		((($article['hiddenContentTitle']==null || $article['hiddenContentTitle']=='') &&
		  ($article['hiddenContent']==null || $article['hiddenContent']==''))
		  ?
		 	(""):
			("<div title=\"Hidden Message\" class=\"blogShowHiddenContentTitle\" id=\"blogShowHiddenContentTitle".$blogID."_".$articleID."\" style=\"width:850px\">".
			 "<a href=\"javascript:showHiddenContentToggle('blogShowHiddenContent".$blogID."_".$articleID."'); setHeight2(); \" title=\"Show it.\">&nbsp;".
			 (($article['hiddenContentTitle']=='')?'_':$article['hiddenContentTitle']).
			 "&nbsp;</a></div>".
			 "<div class=\"blogShowHiddenContent\" id=\"blogShowHiddenContent".$blogID."_".$articleID."\" style=\"width:850px; height:0px; display:none;\">".
			str_replace(array("\n"),array("<br />"),
				htmlConvention(
				(($article['html']==1)?$article['hiddenContent']:
				 str_replace(array("<", ">"), array("&lt;", "&gt;"), $article['hiddenContent'])),
				$attachedList, $articleID, $blogID)
			).
			 "</div>")
		).
		"</div></div>".
		"<div class=\"blogShowComment\" id=\"blogShowComment".$blogID.$articleID."\" style=\"width:850px\">".
		showComment($blogID, $articleID, 0, true, 'blogShowComment'.$blogID.$articleID, $javascript, $okToComment).
		"</div>".(($okToComment==true)?("<div class=\"blogShowCommentAdd\" id=\"blogShowCommentAdd\" style=\"width:100%;display:none;\" ></div>"):"");
	
	$returnValue.="<div class=\"blogShowButtons\">";
	// comment button
	if ($okToComment==true)
		$returnValue.=
			'<input type="button" value="Add a comment" onclick="document.getElementById(\'blogShowCommentAdd\').innerHTML=\''.str_replace("\"", "&#34;", javascriptCompatible(showAddCommentInterface($blogID, $articleID, 0))).'\'; this.disabled=true; document.getElementById(\'blogShowCommentAdd\').style.display=\'block\'; setHeight2(); " />';
	// delete button
	if ($_SESSION['APFSDS_Logged']==1 &&
			($_SESSION['APFSDS_Perm']==100 /* admin */ || 
			 $_SESSION['APFSDS_ID']==$property['admin'] || 
			 $_SESSION['APFSDS_ID']==$article['author']
			)) {
		// DELETE BUTTON
		$returnValue.=
			'<form method="post" action="'.$URL.'Blog/blogAux.php">'.
			'<input type="hidden" name="DO" value="DELETEARTICLE" />'.
			'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
			'<input type="hidden" name="ARTICLEID" value="'.$articleID.'" />'.
			'<input type="submit" value="Delete" onclick="return confirm(\'Really?\');" />'.
			'</form>';
	} else if ($article['author']=='' && strlen($article['guestPassword'])>0) {
		// DELETE BUTTON with expandable password field
		$returnValue.=
			'<form method="post" action="'.$URL.'Blog/blogAux.php">'.
			'<input type="hidden" name="DO" value="DELETEARTICLE" />'.
			'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
			'<input type="hidden" name="ARTICLEID" value="'.$articleID.'" />'.
			'<div id="passwordDA'.$blogID.'x'.$articleID.'" style="display:inline"></div>'.
			'<script type="text/javascript">'.
			'var pDiv = document.getElementById(\'passwordDA'.$blogID.'x'.$articleID.'\'); '.
			'</script>'.
			'<input type="submit" value="Delete" onclick="'.
				'if (pDiv.innerHTML==\'\') {'.
					'pDiv.innerHTML=\'Password:<input type=&#34;password&#34; name=&#34;PASSWORD&#34; size=&#34;8&#34; />\';'.
					'return false; '.
				'}'.
				'else {'.
					'return true; '.
				'}'.
			'" />'.
			'</form>';
	}
	// update button
	if ($_SESSION['APFSDS_Logged']==1 &&
			($_SESSION['APFSDS_Perm']==100 /* admin */ || 
			 $_SESSION['APFSDS_ID']==$article['author']
			)) {
		// UPDATE BUTTON
		$returnValue.=
			'<input type="button" value="Update" onclick="'.
				'writePanel(\''.$divWritePanel.'\', \''.$divContent.'\', \'update\', \''.$blogID.'\' , '.$articleID.', true , \'\'); '.
			'" />';
	} else if ($article['author']=='' && strlen($article['guestPassword'])>0) {
		// UPDATE BUTTON with expandable password field
		$returnValue.=
			'<div id="passwordUA'.$blogID.'x'.$articleID.'" style="display:inline">'.
			'</div>'.
			'<input type="button" value="Update" onclick="'.
				'var pDiv = document.getElementById(\'passwordUA'.$blogID.'x'.$articleID.'\'); '.
				'if (pDiv.innerHTML==\'\') '.
					'pDiv.innerHTML=\'Password:<input type=&#34;password&#34; size=&#34;8&#34; id=&#34;passwordUAP'.$blogID.'x'.$articleID.'&#34; />\'; '.
				'else '.
					'writePanel(\''.$divWritePanel.'\', \''.$divContent.'\', \'update\', \''.$blogID.'\' , '.$articleID.', true , document.getElementById(\'passwordUAP'.$blogID.'x'.$articleID.'\')); '.
			'" />';

	}

	// reply button
	if ($property['modeThread']==1 && (
			$_SESSION['APFSDS_Perm']==100 || (
				$_SESSION['APFSDS_Perm']>=$property['permissionWrite']  &&
				$access['write']===true )
			))
	{
		$returnValue.=
			'<input type="button" value="Reply" onclick="'.
				'writePanel(\''.$divWritePanel.'\', \''.$divContent.'\', \'reply\', \''.$blogID.'\' , '.$articleID.', true , \'\'); '.
			'" />';
	}

	$returnValue.="</div>";
    return $returnValue;
}
function showComment($blogID, $articleID, $commentID=0, $simpleStyle=true, $CDIV, $addCommentResizeJavascript='', $okToComment=false)
{   // $commentID==0 for listing all.
    // $simpleStyle==true for listing as in showArticle()
	// WORK AREA
	global $URL;
	$returnValue='<script type="text/javascript">var toDisplayCA = new Array();</script>';
	$property = getBlogProperty($blogID);
	if ($property['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$access = getAccessBlog($_SESSION['APFSDS_ID'], $blogID);
	if ($access['read']===false)
		return showError("ERROR: Permission Denied. (This blog is CUG.)");
	$access = getAccessBlogArticle($_SESSION['APFSDS_ID'], $blogID, $articleID);
	if ($access['read']===false)
		return showError("ERROR: Permission Denied. (This article's tag is CUG.)");
	if ($access['permissionRead']>$_SESSION['APFSDS_Perm'])
		return showError("ERROR: Permission Denied. (Permission of tag) ".$property['permissionIndex'].">".$_SESSION['APFSDS_Perm']);
	$attached = getAttached($blogID, $articleID);

	function showCommentRecursive($blogID, $articleID, $comment, $addCommentResizeJavascript, $indent, $okToComment, $attached)
	{
		global $URL;
		if ($indent==0)
			$rV = "<div><table style=\"width:850px\">";
		else
			$rV = '<div><table style="position:relative; left:'.$indent.'px;width:'.(850-$indent).'px">';
		$loggedAuthor = false;
		if (strlen($comment['author'])>1)
		{
			$loggedAuthor = true;
			$rsA = DBQ("select * from USERS where id='".$comment['author']."'");
			$authorInfo = mysql_fetch_assoc($rsA);
			mysql_free_result($rsA);
		}


		if ($comment['secret']==0 || $comment['secret']<=$_SESSION['APFSDS_Perm'] || ($comment['author']==$_SESSION['APFSDS_ID'] && $_SESSION['APFSDS_ID']>=3))
		{
			$rV = $rV."<tr><td class=\"blogShowComment1stTD\" style=\"width:75px;\"><div style=\"width:75px;\"><div style=\"display:inline;float:left;width:60px;\">".
						((strlen($comment['author'])<1)?((strlen($comment['guestHomepage'])>6)?"<a href=\"".html2text(mb_substr($comment['guestHomepage'], 0, 200))."\" target=\"_blank\">".mb_substr($comment['guestName'], 0, 100)."</a>":mb_substr($comment['guestName'],0,100)):
						 ("<a href=\"$URL"."Common/miscFunction.php?DO=describeAuthor&AUTHORID=".urlencode($comment['author'])."\" target=\"_blank\">".((strlen($authorInfo['nickname'])>0)?('<b>'.$authorInfo['nickname'].'</b>'):$comment['author'])."</a>")).
				(($_SESSION['APFSDS_Logged']==1 && 
				  ($_SESSION['APFSDS_Perm']==100 || 
				   $_SESSION['APFSDS_ID']==$comment['author'] ||
				   $_SESSION['APFSDS_ID']==$property['admin'])
				 )?"</div><div class=\"blogCommentDelete\" style=\"display:inline;float:right;width:10px;\"><a href=\"".$URL."Blog/blogAux.php?DO=DELETECOMMENT&BLOGID=".urlencode($blogID)."&ARTICLEID=".
					urlencode($articleID)."&COMMENTID=".urlencode($comment['commentID'])."\" onclick=\"return confirm('Really?');\" >x</a></div>":
				"</div>").
				"</div></td>";
		} else {
			$rV = $rV."<tr><td class=\"blogShowComment1stTD\" style=\"width:75px;\"><div style=\"width:75px;\"><div style=\"display:inline;float:left;width:60px;font-size:8pt;color:#FF0000\">Hidden</div></div></td>";
		}
		
		$rV.="<td class=\"blogShowComment2ndTD\" style=\"width:".(730-$indent)."px;max-width:".(730-$indent)."px;\"><div style=\"width:".(730-$indent)."px;max-width:".(730-$indent)."px;\">";

		if ($comment['secret']==0 || $comment['secret']<=$_SESSION['APFSDS_Perm'] || ($comment['author']==$_SESSION['APFSDS_ID'] && $_SESSION['APFSDS_ID']>=3))
		{
			$rV.=htmlConvention(str_replace(array("\r", "\n"), array("","<br />"), html2text($comment['content'])), $attached, $articleID, $blogID);
		} else {
			$rV.="<div style=\"color:#FF0000;font-size:8pt;\">Not authorized to read the comment.</div>";
		}
		$rV.="</div></td>".
			"<td class=\"blogShowComment3rdTD\"><div style=\"min-width:30px; max-width:30px; width:30px\">\n";

		if ($okToComment) {
			$rV.=
				"<script type=\"text/javascript\">\ntoDisplayCA[".$comment['commentID']."]= '".javascriptCompatible(showAddCommentInterface($blogID, $articleID, $comment['commentID']))."';\n</script>\n".
				"<a href=\"javascript:openDivToggleContent('CA".$blogID.$articleID.$comment['commentID']."', toDisplayCA[".$comment['commentID']."],".((strlen($addCommentResizeJavascript)>0)?'true':'false').");\">[reply]</a></div></td></tr></table></div>".
				"<div id=\"CA".$blogID.$articleID.$comment['commentID']."\" style=\"width:100%; display:none; \"></div>";
		}
		else
			$rV.="</div></td></tr></table></div>";

		$rs = DBQ("select * from BlogComment where blogID='$blogID' and articleID=$articleID and thread=".$comment['commentID']." order by commentID ASC");
		while ($r = mysql_fetch_assoc($rs))
			$rV.=showCommentRecursive($blogID, $articleID, $r, $addCommentResizeJavascript, $indent+10, $okToComment, $attached);
		mysql_free_result($rs);
		return $rV;
	}
	$rsComment = DBQ("select * from BlogComment where blogID='$blogID' and articleID=$articleID and thread is null order by commentID ASC");
	while ($comment = mysql_fetch_assoc($rsComment))
	{
		$returnValue=$returnValue.
			showCommentRecursive($blogID, $articleID, $comment, $addCommentResizeJavascript, 0, $okToComment, $attached);
	}
	mysql_free_result($rsComment);

    return $returnValue;
}
function showAddCommentInterface($blogID, $articleID, $thread)
{
	global $URL, $divPopup;

	// if $divID==false, just reload this window.
    $returnValue = '<form method="post" action="'.$URL.'Blog/blogAux.php" id="addCommentForm'.$blogID.'_'.$articleID.'_'.$thread.'">'.
		'<input type="hidden" name="DO" value="WRITECOMMENT" />'.
		'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
		'<input type="hidden" name="ARTICLEID" value="'.$articleID.'" />'.
		'<input type="hidden" name="THREAD" value="'.$thread.'" />'.
		'<table style="width:100%"><tr><td style="font-size:9pt;min-width:150px; max-width:250px; width:200px; vertical-align:top; bottom:0px; top:0px">'.
		(($_SESSION['APFSDS_Perm']<3 || !(strlen($_SESSION['APFSDS_ID'])>0) || $_SESSION['APFSDS_Logged']!==1)?
		 '<input type="text" name="NAME" value="Your Name" size="8" '.
		 	 'onkeyPress="if (this.value!=\'\') this.form.SUBMIT.disabled=false;" '.
			 'onfocus="if (this.value==\'Your Name\') '.
				'this.value=\'\';" '.
			 'onblur="if (this.value==\'\') '.
				'{	this.value=\'Your Name\'; '.
				' 	this.form.SUBMIT.disabled=\'disabled\'; }'.
				'else this.form.SUBMIT.disabled=false; '.
				'" />'.
		 '<input type="text" name="GUESTHOMEPAGE" value="Homepage" size=8" '.
			 'onfocus="if (this.value==\'Homepage\') '.
				'this.value=\'\';" '.
			 'onblur="if (this.value==\'\') '.
				'{	this.value=\'Homepage\'; } '.
				'" />'
			:
		 $_SESSION['APFSDS_ID']).
		'</td>';
	$returnValue.='<td style="min-width:400px;font-size:8.5pt;">This comment can be read by <input type="radio" name="secret" value="0" checked="on" />public&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="secret" value="3" />members only&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="secret" value="100" />admin only</td></tr>';
	$returnValue.=
		'<tr><td colspan=2>'.
		'<div id="emoticonlist'.$blogID.'_'.$articleID.'_'.$thread.'"></div>'.
		'<tr><td colspan=2><textarea id="'.
		'commentTextarea_emoticonlist'.$blogID."_".$articleID."_".$thread.
		'" cols="82" name="CONTENT" rows="4" onKeyPress="'.
		'this.rows = countLines('.
				'this.value, 80)+2; '.
		'setHeight2();'.
		'"></textarea></td></tr>';
	if ($_SESSION['APFSDS_Logged']==0) {
		$returnValue.='<tr><td colspan=2 style="vertical-align:top;font-size:9pt"><img src="'.$URL.'Common/captcha.php?id='.urlencode($blogID.'_'.$articleID.'_'.$thread).'" alt="captcha" /> Write down the text in the image <input type="text" size="10" name="CAPTCHA" />(Login to skip this)</td></tr>';
	}
	$returnValue.='<tr><td colspan="2"><input type="submit" name="SUBMIT" value="Write" style="width:500px;" '.
		(($_SESSION['APFSDS_Logged']==1)?'':'disabled="disabled"').
		' />'.
		'&nbsp;&nbsp;&nbsp;<a href="'.$URL.'Common/APFSDSHelp.php?About=HTMLConvention" target="_new" style="font-size:9pt">How to use tags</a>'.
		'&nbsp;&nbsp;&nbsp;<a href="javascript:imoticonListPanel(\''.
		'emoticonlist'.$blogID.'_'.$articleID.'_'.$thread.
		'\');" style="font-size:9pt">Emoticon List</a>'.
		'</td></tr></table></form><script type="text/javascript">setHeight2();</script>';
    return $returnValue;
}
function showWriteInterface($blogID,$thread=0, $contentResize=true)
{
	global $URL, $minTitleLength;
	$property = getBlogProperty($blogID);
	if ($property['permissionWrite']>$_SESSION['APFSDS_Perm'])
		return showError('ERROR: Permission Denied.');
    $returnValue = '<form method="POST" enctype="multipart/form-data" action="'.$URL.
		'Blog/blogAux.php" id="WriteForm'.$blogID.'X'.$thread.'">'.
		'<input type="hidden" name="DO" value="WRITEARTICLE" />'.
		'<input type="hidden" name="BLOGID" value="'.$blogID.'" />';
	if ($property['modeTag']!==0)
	{
		$returnValue.=
			'<input type="hidden" name="TAG" id="blogTagListID" value="" />'.
			'<input type="hidden" name="NEWTAG" value="" />'.
			'<input type="hidden" name="NEWTAGDISABLED" value="" />'.
			'<input type="hidden" name="NEWTAGREADPERMISSION" value="" />'.
			'<input type="hidden" name="NEWTAGREADACCESSENABLED" value="" />'.
			'<input type="hidden" name="NEWTAGWRITEPERMISSION" value="" />'.
			'<input type="hidden" name="NEWTAGWRITEACCESSENABLED" value="" />'.
			'<input type="hidden" name="NEWTAGACCESSCONTROLLIST" value="" />'.
			'<div class="blogWriteTag" id="tagListShow'.$blogID.'X'.$thread.'" ></div><br />'.
			'<script type="text/javascript">'.
			'var tagTitle=new Array(); '.
			'var tagID=new Array();';
		$rs = DBQ("select tagID, tagTitle from BlogTag where blogID='$blogID' and permissionWrite<=".
			$_SESSION['APFSDS_Perm']." and (accessControlRead = 0 or ".
				"tagID in (select tagID from BlogTagAccessControl where blogID='$blogID' and user='".
				$_SESSION['APFSDS_ID']."')) ");
		$count = 0;
		while ($r = mysql_fetch_row($rs)) {
			$returnValue.=
				"tagID[$count]=".$r[0]."; ".
				"tagTitle[".$r[0]."]='".$r[1]."'; ";
			$count++;
		}
		mysql_free_result($rs);

		$returnValue.=
			'var countNewTag = 0; '.
			'var thisForm = document.getElementById(\'WriteForm'.$blogID.'X'.$thread.'\');';
		$rs = DBQ("select tagTitle from BlogTag where blogID='$blogID'");
		$tagTitleList = '';
		while ($r = mysql_fetch_row($rs))
			$tagTitleList.=','.$r[0];
		mysql_free_result($rs);
		if ($tagTitleList!='')
			$tagTitleList=substr($tagTitleList, 1).',';
		$rs = DBQ("select tagID, tagTitle from BlogTag where blogID='$blogID' and permissionWrite<=".($_SESSION['APFSDS_Perm']+0)." and (accessControlRead=0 or (accessControlRead>0 and tagID in (select tagID from BlogTagAccessControl where blogID='$blogID' and user='".$_SESSION['APFSDS_ID']."')))");
		$num = mysql_num_rows($rs);
		$returnValue=$returnValue.'var knownTagTitle=new Array();';
		while ($r=mysql_fetch_row($rs))
		{
			$returnValue=$returnValue.'knownTagTitle['.$r[0].']=\''.$r[1].'\';';
		}
		$returnValue=$returnValue.'var knownTags=\'';
		if ($num>0)
		{
			mysql_data_seek($rs, 0);
			$returnValue=$returnValue.
				'<select id="tag'.$blogID.'X'.$thread.'Tag">';
			while($r=mysql_fetch_row($rs))
			{
				$returnValue=$returnValue.
					'<option value="'.$r[0].'">'.$r[1].'</option>';
			}
			$returnValue=$returnValue.
				'</select>'.
				'<input type="button" value="Add Tag" onclick="'.
					'thisForm.TAG.value=addToList(thisForm.TAG.value, document.getElementById(\\\'tag'.$blogID.'X'.$thread.'Tag\\\').value); '.
					'constructTagList(\\\'tagListShow'.$blogID.'X'.$thread.'\\\', tagID, tagTitle, \\\'WriteForm'.$blogID.'X'.$thread.'\\\'); '.
					'setHeight2(); '.
					(($contentResize)?'setHeight3(); ':'').
				'" />';
		}
		else if ($property['modeTag']==2)
		{
			$returnValue=$returnValue.'Permission Denied to Write Any Article.';
		}
		mysql_free_result($rs);
		$returnValue=$returnValue.'\';';
		$returnValue=$returnValue.
			'</script>'."\n".
			(($thread==0)?'':'<input type="hidden" name="THREAD" value="'.$thread.'" />').
			'<div class="blogWriteTag" id="blogAddTagExists">'.
			'<script type="text/javascript">document.write(knownTags);</script>'.
			'</div>';
	}
	$returnValue=$returnValue.
		'<div class="blogWrite" id="blogWriteDiv">'.
		'<table style="width:100%" class="blogWrite">'.
		'<tr>'.
			'<th>Title</th>'.
			'<td><input type="text" name="TITLE" size="84" /></td>'.
		'</tr>';
	if ($_SESSION['APFSDS_Logged']!==1 && strlen($_SESSION['APFSDS_ID'])<1)
	{
		$returnValue=$returnValue.
			'<tr>'.
				'<td colspan="2">'.
					'<img src="'.$URL.'Common/captcha.php?id='.urlencode($blogID.'X'.$thread).'" alt="captcha" /> Please write down the image <input type="text" name="CAPTCHA" size="10" />(Login to skip this)'.
				'</td>'.
			'</tr>'.
			'<tr>'.
				'<th>Name</th>'.
				'<td><input type="text" name="GUESTNAME" size="40"></td>'.
			'</tr>'.
			'<tr>'.
				'<th>Email</th>'.
				'<td><input type="text" name="GUESTEMAIL" size="40"></td>'.
			'</tr>'.
			'<tr>'.
				'<th>Homepage</th>'.
				'<td><input type="text" name="GUESTHOMEPAGE" size="60"></td>'.
			'</tr>'.
			'<tr>'.
				'<th>Password</th>'.
				'<td><input type="password" name="GUESTPASSWORD" size="20"></td>'.
			'</tr>';
	}
	$returnValue=$returnValue.
		'<tr>'.
			'<th>TrackBack From URL</th>'.
			'<td><input type="text" name="TRACKBACKFROM" size="60" /></td>'.
		'</tr>'.
		'<tr>'.
			'<td colspan="2">'.
				'<div id="emoticonlist'.$blogID.'_newArticle"></div>'.
				'<textarea id="commentTextarea_emoticonlist'.$blogID.'_newArticle" cols="84" name="CONTENT" rows="8" onKeyPress="'.
				'thisForm.CONTENT.rows = max(8, countLines('.
						'thisForm.CONTENT.value, 80)); '.
				'setHeight2(); '.(($contentResize)?'setHeight3(); ':'').
				'"></textarea>'.
		'</td></tr>'.
/*		'<tr>'.
			'<th><input type="checkbox" name="HIDDENCONTENTENABLED" value="YES" style="border:0" />Hidden Content</th>'.
			'<td><input type="text" name="HIDDENCONTENTTITLE" size="80" /></td>'.
		'</tr>'.
		'<tr>'.
			'<td colspan="2">'.
				'<textarea cols="84" name="HIDDENCONTENT" rows="4" onKeyPress="'.
				'thisForm.HIDDENCONTENT.rows = max(4, countLines('.
						'thisForm.HIDDENCONTENT.value, 80)); '.
				'setHeight2(); '.(($contentResize)?'setHeight3(); ':'').
				'"></textarea></td>'.
		'</tr>'.
*/
		(($property['modeUpload']!=0)?
		 (
			'<tr>'.
				'<td colspan="2">'."\n".
				'<script type="text/javascript">'."\n".
					'function onClickCommandFunc()'."\n".
					'{ '."\n".
						'thisForm.attachFileCount.value++; '."\n".
						'document.getElementById(\'fileUploadInterface\'+String(thisForm.attachFileCount.value)).innerHTML='."\n".
							'\'Attachment #\'+thisForm.attachFileCount.value+\' <input type="file" name="attachFile[\'+String(thisForm.attachFileCount.value)+\']" size="50" />\'+'."\n".
							'\'<input type="button" id="attachFileButton\'+String(thisForm.attachFileCount.value)+\'" value="Add Another File" onclick="onClickCommandFunc(); document.getElementById(\\\'attachFileButton\'+String(thisForm.attachFileCount.value)+\'\\\').disabled=&#34disabled&#34; " />\'+'."\n".
							'\'<div class="fileUploadDiv" id="fileUploadInterface\'+String(Number(thisForm.attachFileCount.value)+1)+\'">\'+'.
							'\'</div>\'+'."\n".
						'\'\'; '."\n".
						'setHeight2(); '.(($contentResize)?'setHeight3(); ':'').
					'} '."\n".
				'</script>'."\n".
				'<div class="fileUploadDiv" id="fileUploadInterface0">'.
				'<input type="hidden" name="attachFileCount" value="0" />'.
				'Attachment #0 <input type="file" name="attachFile[0]" size="50" />'.
				'<input type="button" id="attachFileButton0" value="Add Another File" onclick="'.
					'onClickCommandFunc(); document.getElementById(\'attachFileButton0\').disabled=true;" />'.
				'<div class="fileUploadDiv" id="fileUploadInterface1"></div>'.
				'You can cancel an upload by deleting the filename.'.
				'</div>'.
				'</td>'."\n".
			'</tr>'):'').
		'<tr>'.
			'<td><input type="checkbox" name="HTML" value="YES" style="border:0" '.
				(($property['modeHTML']==0)?
				 'disabled="disabled"':
				 'value="YES" '.(($property['modeHTML']==2)?
				  'checked="checked"':
				  '')
				).
				' />HTML</td>'.
			'<td><a href="'.$URL.'Common/APFSDSHelp.php?About=HTMLConvention" target="_blank" style="font-size:9pt">Description of Convention</a>&nbsp;&nbsp;&nbsp;'.
			'<a href="javascript:imoticonListPanel(\'emoticonlist'.$blogID.'_newArticle'.
			'\');" style="font-size:9pt">Emoticon</a></td>'.
		'</tr>'.
		'<tr>'.
			'<td colspan="2"><input type="submit" style="width:500px" value="Write This Article" onclick="'.
			'if (thisForm.CONTENT.value.length>0 && thisForm.TITLE.value.length>='.($minTitleLength+0).''.
					(($property['modeTag']==2)?
					 ' && (thisForm.TAG.value.length>0 || numEnabledNewTag(thisForm.NEWTAGDISABLED.value)>0)':
					 '').
					(($_SESSION['APFSDS_Logged']==1)?
					 '':
					 ' && thisForm.GUESTNAME.value.length>0 ').
					') return true; '.
			'else '.
					'{alert(\'Some of the fields(Title, Content, Name(If you are a guest)) are not filled, yet.\'); return false;}'.
			'" />'.
		'</tr>'.
		'</table>'.
		'</div>'.
		'</form>';
	if ($property['modeUpload']!=0)
	{
		$returnValue=$returnValue.
			'<div class="blogAttachment" id="blogAttachmentList"></div>';
	}
	if (($_SESSION['APFSDS_Perm']==100 || $property['admin']==$_SESSION['APFSDS_ID']) // ADMIN or Owner
			&& $property['modeTag']!=0)
	{
		$returnValue=$returnValue.
			'<div class="blogNewTag">'.
			'<form method="post" action="about:blank" id="newTagAdd">'.
			'<script type="text/javascript">var thisTag=document.getElementById(\'newTagAdd\');</script>'.
			'<table>'.
			'<tr>'.
				'<th>Tag Title</th>'.
				'<td><input type="text" size="10" name="TagTitle" /></td>'.
			'</tr><tr>'.
				'<th>Read Permission Level</th>'.
				'<td><input type="text" size="10" value="0" name="TagReadPermission" id="newTagReadPermissionInput" onkeyPress="document.getElementById(\'newTagReadPermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagReadPermissionInput\').value), 0, 100);" onblur="document.getElementById(\'newTagReadPermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagReadPermissionInput\').value), 0, 100);" /></td>'.
			'</tr><tr>'.
				'<th>Read Restricted To</th>'.
				'<td><input type="checkbox" style="border:0" name="TagAccessControlReadEnabled" />Enable Access Control on this Tag</td>'.
			'</tr><tr>'.
				'<th>Write Permission Level</th>'.
				'<td><input type="text" size="10" value="0" name="TagWritePermission" id="newTagWritePermissionInput" onkeyPress="document.getElementById(\'newTagWritePermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagWritePermissionInput\').value), 0, 100);" onblur="document.getElementById(\'newTagWritePermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagWritePermissionInput\').value), 0, 100);" /></td>'.
			'</tr><tr>'.
				'<th>Write Restricted To</th>'.
				'<td><input type="checkbox" style="border:0" name="TagAccessControlWriteEnabled" />Enable Access Control on this Tag</td>'.
			'</tr><tr>'.
				'<th>Access Control Member List<br />example) id1,id2,id3 (comma)</th>'.
				'<td><textarea cols="42" name="TagAccessControlList" rows="2" onKeyPress="'.
				'thisTag.TagAccessControlList.rows = countLines('.
						'thisTag.TagAccessControlList.value, 40); '.
				'setHeight2(); '.(($contentResize)?'setHeight3(); ':'').
				'"></textarea></td>'.
			'</tr><tr>'.
				'<td colspan="2">'.
				'<input type="button" value="Add Tag" onclick="'.
					'if (thisForm.NEWTAG.value==\'\') {'.
						'thisForm.NEWTAG.value=thisTag.TagTitle.value;'.
						'thisForm.NEWTAGDISABLED.value=\'enabled\';'.
						'thisForm.NEWTAGREADPERMISSION.value=new String(thisTag.TagReadPermission.value);'.
						'thisForm.NEWTAGWRITEPERMISSION.value=new String(thisTag.TagWritePermission.value);'.
						'thisForm.NEWTAGREADACCESSENABLED.value=new String(thisTag.TagAccessControlReadEnabled.checked);'.
						'thisForm.NEWTAGWRITEACCESSENABLED.value=new String(thisTag.TagAccessControlWriteEnabled.checked);'.
						'thisForm.NEWTAGACCESSCONTROLLIST.value=listlizeAccessControlList(new String(thisTag.TagAccessControlList.value));'.
					'}'.
					'else {'.
						'if (!distinct(\''.$tagTitleList.'\'+'.
									'thisForm.NEWTAG.value, thisTag.TagTitle.value)) {'.
							'alert(\'We already have Tag Title: \'+thisTag.TagTitle.value); return false;'.
						'}'.
						'thisForm.NEWTAG.value+=\',\'+new String(thisTag.TagTitle.value);'.
						'thisForm.NEWTAGDISABLED.value+=\',\'+\'enabled\';'.
						'thisForm.NEWTAGREADPERMISSION.value+=\',\'+new String(thisTag.TagReadPermission.value);'.
						'thisForm.NEWTAGWRITEPERMISSION.value+=\',\'+new String(thisTag.TagWritePermission.value);'.
						'thisForm.NEWTAGREADACCESSENABLED.value+=\',\'+new String(thisTag.TagAccessControlReadEnabled.checked);'.
						'thisForm.NEWTAGWRITEACCESSENABLED.value+=\',\'+new String(thisTag.TagAccessControlWriteEnabled.checked);'.
						'thisForm.NEWTAGACCESSCONTROLLIST.value+=\',\'+listlizeAccessControlList(thisTag.TagAccessControlList.value);'.
					'}'.
					'document.getElementById(\'blogAddTagNew\').innerHTML=document.getElementById(\'blogAddTagNew\').innerHTML+'.
						'\'<table class=&#34;newTagTitle&#34; style=&#34;display:inline&#34;><tr><th>\'+'.
						'thisTag.TagTitle.value+'.
						'\'</th><td>\'+'.
							'\''.
								'<input type=&#34;checkbox&#34; border=&#34;border:0&#34; checked=&#34;checked&#34;'.
									'onchange=&#34;'.
										'thisForm.NEWTAGDISABLED.value=commaArrayFlip(thisForm.NEWTAGDISABLED.value,\'+countNewTag+\');'.
									'&#34;'.
								' />Enabled</td></tr></table>'.
							'\'+'.
						'\'<br />\';'.
					'loading(); countNewTag++;'.
				'" />'.
				'</td></tr>'.
			'</tr>'.
			'</table>'.
			'</form>'.
			'</div>'.
			'<div class="blogWriteTag" id="blogAddTagNew">'.
			'</div>';
	}
    return $returnValue;
}
function showUpdateInterface($blogID, $articleID)
{
	global $URL, $ATTACHMENTDIR, $LOCALSEPERATOR;
    $returnValue = '';
	$PASSWORD = request("PASSWORD");
	// 1. Existence / Security Check
	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID ");
	$article = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$article)
		return "ERROR: Article Not Exists $blogID:$articleID";
	if ($_SESSION['APFSDS_Perm']<100) {
		if ($article['author']==null || $article['author']=='') {
			if ($article['guestPassword']==null || $article['guestPassword']=='' ||
					$article['guestPassword']!=$PASSWORD)
				return "ERROR: Permission Denied(Invalid Password) $blogID:$articleID";
		}
		else{
			if ($article['author']!==$_SESSION['APFSDS_ID'])
				return "ERROR: Permission Denied(ID Mismatch) $blogID:$articleID (".$article['author']." vs ".$_SESSION['APFSDS_ID'].")";
		}
	}
    $returnValue.= '<form method="POST" enctype="multipart/form-data" action="'.$URL.
		'Blog/blogAux.php" id="UpdateForm">'.
		'<script type="text/javascript">'.
		'var thisForm = document.getElementById(\'UpdateForm\'); '.
		'var tagTitle = new Array();'. // index by tagID
		'var tagID = new Array();'; // indexed 0..n-1
	$rs = DBQ("select tagID, tagTitle from BlogTag where blogID='$blogID' and permissionWrite<=".
			$_SESSION['APFSDS_Perm']." and (accessControlRead = 0 or ".
				"tagID in (select tagID from BlogTagAccessControl where blogID='$blogID' and user='".
				$_SESSION['APFSDS_ID']."')) ");
	$count = 0;
	while ($r = mysql_fetch_row($rs)) {
		$returnValue.=
			"tagID[$count]=".$r[0]."; ".
			"tagTitle[".$r[0]."]='".$r[1]."'; ";
		$count++;
	}
	mysql_free_result($rs);
	$returnValue.=
		'</script>'.
		'<input type="hidden" name="DO" value="UPDATEARTICLE" />'.
		'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
		'<input type="hidden" name="ARTICLEID" value="'.$articleID.'" />'.
		'<input type="hidden" name="PASSWORD" value="'.$PASSWORD.'" />'.
		'<input type="hidden" name="TAG" id="blogTagListID" value="';
	$rs = DBQ("select tagID from BlogTagArticleAssoc where blogID='$blogID' and articleID=$articleID ");
	$new = true;
	while ($r = mysql_fetch_row($rs)) {
		if ($new) {
			$returnValue.=$r[0];
			$new = false;
		} else 
			$returnValue.=','.$r[0];
	}
	mysql_free_result($rs);
	$returnValue.='" />'.
		'<div class="blogWriteTag" id="blogTagListShow" >'.
		'</div>'.
		'<script type="text/javascript">'.
		'constructTagList(\'blogTagListShow\', tagID, tagTitle, \'UpdateForm\');'.
		'</script>'.
		'<div class="blogWriteTag">'.
		'<select id="tagAdd">';
	$rs = DBQ("select tagID, tagTitle from BlogTag where blogID='$blogID' and permissionWrite<=".
			$_SESSION['APFSDS_Perm']." and (accessControlRead = 0 or ".
				"tagID in (select tagID from BlogTagAccessControl where blogID='$blogID' and user='".
				$_SESSION['APFSDS_ID']."')) ");
	while ($r = mysql_fetch_row($rs)) {
		$returnValue.=
			'<option value="'.$r[0].'">'.$r[1].'</option>';
	}
	mysql_free_result($rs);
	$returnValue.=
		'</select><br />'.
		'<input type="button" value="Add Tag" onclick="'.
			'thisForm.TAG.value=addToList(thisForm.TAG.value, document.getElementById(\'tagAdd\').value); '.
			'constructTagList(\'blogTagListShow\', tagID, tagTitle, \'UpdateForm\'); '.
			'setHeight2(); setHeight3(); '.
		'" />'.
		'</div>'.
		'<div class="blogWrite" id="blogWriteDiv">'.
		'<table style="width:100%" class="blogWrite">'.
		'<tr>'.
			'<th>Title</th>'.
			'<td><input type="text" name="TITLE" size="84" value="'.
				htmlPropertySafe($article['title']).
			'" /></td>'.
		'</tr>'.
		'<tr>'.
			'<th>Author</th>'.
			'<td>'.$article['author'].$article['guestName'].'</td>'.
		'</tr>';
	if ($_SESSION['APFSDS_Perm']==100 || $_SESSION['APFSDS_ID']==$property['admin'])
		$returnValue.=
			'<tr>'.
			'<td colspan="2"><input type="checkbox" style="border:0" name="UPDATEMODIFIEDDATE" value="NO" checked="checked" /> Do Not Update Modify-Date Timestamp. </td>'.
			'</tr>';
	$returnValue.=
		'<tr><td colspan="2">'.
			'<div id="emoticonlist'.$blogID.'_newArticle"></div>'.
			'<textarea id="commentTextarea_emoticonlist'.$blogID.'_newArticle" cols="84" name="CONTENT" rows="8" onKeyPress="'.
				'this.rows = countLines(this.value, 80); '.
				'setHeight2(); setHeight3(); ">'.$article['content'].'</textarea>'.
			'</td>'.
		'</tr>'.
		'<tr>'.
			'<th><input type="checkbox" style="border:0" name="HIDDENCONTENTENABLED" '.
			(($article['hiddenContentTitle']!==null && strlen($article['hiddenContentTitle'])>0)?
			 'checked="checked" ':'').' />Hidden</th>'.
			'<td><input type="text" name="HIDDENCONTENTTITLE" value="'.htmlPropertySafe($article['hiddenContentTitle']).'" size="60" /></td>'.
		'</tr>';
	$returnValue.=
		'<tr>'.
			'<td colspan="2"><textarea name="HIDDENCONTENT" cols="82" rows="2" onKeyPress="'.
				'this.rows = countLines(this.value, 80); '.
				'setHeight2(); setHeight3(); ">'.$article['hiddenContent'].'</textarea>'.
			'</td>'.
		'</tr>'.
		'<script type="text/javascript">'.
		'thisForm.CONTENT.rows = countLines(thisForm.CONTENT.value, 80); '.
		'thisForm.HIDDENCONTENT.rows = countLines(thisForm.HIDDENCONTENT.value, 80); '.
		'setHeight2(); setHeight3(); '.
		'</script>';
	$rs = DBQ("select attachmentID, filename from BlogArticleAttached where blogID='$blogID' and articleID=$articleID order by attachmentID ASC");
	while ($r = mysql_fetch_row($rs)) {
		$returnValue.='<tr><td>Attachment#'.$r[0].'</td><td><a href="'.$URL.'Blog/AttachedFiles/'.$blogID.$LOCALSEPERATOR.$articleID.$LOCALSEPERATOR.$r[1].'" target="_blank">'.$r[1].'</a> &nbsp;&nbsp; Delete File <input type="checkbox" style="border:0" name="attachFileDelete['.$r[0].']" /></td></tr>';
	}
	mysql_free_result($rs);
	$rs = DBQ("select max(attachmentID) from BlogArticleAttached where blogID='$blogID' and articleID=$articleID");
	$r = mysql_fetch_row($rs);
	mysql_free_result($rs);
	if ($r)
		$attachFileStart = $r[0]+1;
	else
		$attachFileStart = 0;

	$returnValue.='<input type="hidden" name="attachFileStart" value="'.$attachFileStart.'" />'.
		'<input type="hidden" name="attachFileCount" value="1" />';

	if ($property['modeTag']!==0) {
		$returnValue.=
			'<script type="text/javascript">'.
			'function divU(num) { return document.getElementById(\'fileUpload\'+num); } '.
			'function addAttach() { '.
				'divU(thisForm.attachFileCount.value).innerHTML = \'Attachment#\'+(parseInt2(thisForm.attachFileCount.value)+'.$attachFileStart.')+\' <input type="file" name="attachFile[]" size="50" /><input type="button" value="Attach Another File" onclick="addAttach(); this.disabled=true; " /><div class="fileUploadDiv" id="fileUpload\'+(parseInt2(thisForm.attachFileCount.value)+1)+\'"></div>\'; thisForm.attachFileCount.value++; setHeight2(); setHeight3(); '.
			' } '.
			'</script>'.
			'<tr><td colspan="2">Attachment#'.$attachFileStart.' <input type="file" name="attachFile[]" size="50" /><input type="button" value="Attach Another File" onclick="addAttach(); this.disabled=true; '.
			'" />'.
			'<div class="fileUploadDiv" id="fileUpload1"></div>You can cancel an upload by deleting the filename.';
	}
	$returnValue.=
		'<tr>'.
			'<td><input type="checkbox" style="border:0" name="HTML" '.
				(($property['modeHTML']==0)?
				 'disabled="disabled"':
				 (($article['html']==1)?
				  'checked="checked"':
				  '')
				).
				' />HTML</td>'.
			'<td><a href="'.$URL.'Common/APFSDSHelp.php?About=HTMLConvention" target="_blank" style="font-size:9pt">Description of Convention</a>&nbsp;&nbsp;&nbsp;'.
			'<a href="javascript:imoticonListPanel(\'emoticonlist'.$blogID.'_newArticle'.
			'\');" style="font-size:9pt">Emoticon</a></td>'.
		'</tr>'.
		'<tr>'.
			'<td colspan="2"><input type="submit" style="width:500px" value="Write This Article" onclick="'.
			'if (thisForm.CONTENT.value.length>0 && thisForm.TITLE.value.length>'.($minTitleLength+0).''.
					(($property['modeTag']==2)?
					 ' && (thisForm.TAG.value.length>0 || numEnabledNewTag(thisForm.NEWTAGDISABLED.value)>0)':
					 '').
					(($_SESSION['APFSDS_Logged']==1)?
					 '':
					 ' && thisForm.GUESTNAME.value.length>0 && thisForm.GUESTPASSWORD.value.length>0 && thisForm.GUESTNAME.value.length>0 ').
					') return true; '.
			'else '.
					'{alert(\'Some of the fields are not filled, yet.\'); return false;}'.
			'" />'.
		'</tr>';
	$returnValue.="</table></div>".
		"</form>";
    return $returnValue;
}
function showManageTag($blogID)
{   // assuming that we are in the write div.
	global $URL;
	$property = getBlogProperty($blogID);
	$tagID = request("TAGID");
	$action = request("ACTION");
	$returnValue = '';

	if ($_SESSION['APFSDS_ID']==$property['admin'] || $_SESSION['APFSDS_Perm']==100) {
		switch ($action) {
		case "DELETE":
			$rs = DBQ("select count(articleID) from BlogTagArticleAssoc where blogID='$blogID' and tagID=$tagID");
			$r = mysql_fetch_row($rs);
			mysql_free_result($rs);
			if ($r[0]==0) {
				DBQ("delete from BlogTag where blogID='$blogID' and tagID=$tagID");
				print "Deleted Tag : $blogID.$tagID <br />";
			}
			else
				print "Failed Deletion : $blogID.$tagID $r[0] articles are using it.<br />";
			break;
		case "ADD":
			DBQ("insert into BlogTag values('$blogID', null, '".request("TagTitle")."', ".
					((request("TagAccessControlReadEnabled")=="YES")?"1":"0").", ".
					((request("TagAccessControlWriteEnabled")=="YES")?"1":"0").", ".
					request("TagReadPermission").",".request("TagWritePermission").")");
			$tagID = mysql_insert_id();
			$accessControlList = explode(":", request("TagAccessControlList"));
			foreach($accessControlList as $index=>$value) {
				DBQ("insert into BlogTagAccessControl values('$blogID', $tagID, '$value')");
			}
			break;
		}
	}


	$rs = DBQ("select * from BlogTag where blogID='$blogID' ");
	$returnValue.= '<div class="blogTagList">'.
		'<table><tr><th colspan="2">'.$blogID.' Tag List </th><td>Read Permission</td><td>Read Access Control</td><td>Write Permission</td><td>Write Access Control</td></tr>';

	while ($r=mysql_fetch_assoc($rs)) {
		$returnValue.='<tr><td>'.$r['tagID'].'</td><td><a href="'.$URL.'Blog/blogAux.php?DO=SHOWMANAGETAG&ACTION=SHOWEDIT&BLOGID='.urlencode($blogID).'&TAGID='.urlencode($r['tagID']).'">&nbsp; '.$r['tagTitle'].' &nbsp;</a></td><td>'.$r['permissionRead'].'</td><td>'.(($r['accessControlRead']==1)?'on':'off').'</td><td>'.$r['permissionWrite'].'</td><td>'.(($r['accessControlRead']==1)?'on':'off').'</td><td><a href="'.$URL.'Blog/blogAux.php?BLOGID='.urlencode($blogID).'&DO=SHOWMANAGETAG&ACTION=DELETE&TAGID='.trim($r['tagID']).'" onclick="return confirm(\'Really?\');">[delete]</a></td></tr>';
	}
	mysql_free_result($rs);
	$returnValue.= '</table>'.
		'<br />'.
		'<form method="post" action="'.$URL.'Blog/blogAux.php" id="NEWTAG">'.
		'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
		'<input type="hidden" name="DO" value="SHOWMANAGETAG" />'.
		'<input type="hidden" name="ACTION" value="ADD" />'.
		'<table>'.
			'<tr>'.
				'<th>Tag Title</th>'.
				'<td><input type="text" size="10" name="TagTitle" /></td>'.
			'</tr><tr>'.
				'<th>Read Permission Level</th>'.
				'<td><input type="text" size="10" value="0" name="TagReadPermission" id="newTagReadPermissionInput" onkeyPress="document.getElementById(\'newTagReadPermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagReadPermissionInput\').value), 0, 100);" onblur="document.getElementById(\'newTagReadPermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagReadPermissionInput\').value), 0, 100);" /></td>'.
			'</tr><tr>'.
				'<th>Read Restricted To</th>'.
				'<td><input type="checkbox" style="border:0" name="TagAccessControlReadEnabled" value="YES" />Enable Access Control on this Tag</td>'.
			'</tr><tr>'.
				'<th>Write Permission Level</th>'.
				'<td><input type="text" size="10" value="0" name="TagWritePermission" id="newTagWritePermissionInput" onkeyPress="document.getElementById(\'newTagWritePermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagWritePermissionInput\').value), 0, 100);" onblur="document.getElementById(\'newTagWritePermissionInput\').value=inTheRange(parseInt2(document.getElementById(\'newTagWritePermissionInput\').value), 0, 100);" /></td>'.
			'</tr><tr>'.
				'<th>Write Restricted To</th>'.
				'<td><input type="checkbox" style="border:0" name="TagAccessControlWriteEnabled" value="YES" />Enable Access Control on this Tag</td>'.
			'</tr><tr>'.
				'<th>Access Control Member List<br />example) id1,id2,id3 (comma)</th>'.
				'<td><textarea cols="42" name="TagAccessControlList" rows="2" onKeyPress="'.
				'this.rows = countLines(this.value, 40); '.
				'setHeight2(); '.(($contentResize)?'setHeight3(); ':'').
				'"></textarea></td>'.
			'</tr><tr>'.
				'<td colspan="2"><input type="submit" value="Add a new tag" /></td>'.
			'</tr>'.
		'</table>'.
		'</form>';
	
	if ($_SESSION['APFSDS_ID']==$property['admin'] || $_SESSION['APFSDS_Perm']==100) {
		switch ($action) {
		case "SHOWEDIT":
			$rs = DBQ("select * from BlogTag where blogID='$blogID' and tagID=$tagID");
			$r = mysql_fetch_assoc($rs);
			mysql_free_result($rs);
			if (!$r) {
				$returnValue.="BlogTag Not Exists: $blogID.$tagID";
			} else {
				$returnValue.=
					'<form method="post" action="'.$URL.'Blog/blogAux.php" id="TAGINFO">'.
					'<input type="hidden" name="BLOGID" value="'.$blogID.'" />'.
					'<input type="hidden" name="DO" value="SHOWMANAGETAG" />'.
					'<input type="hidden" name="ACTION" value="UPDATE" />'.
					'<input type="hidden" name="TAGID" value="'.$tagID.'" />'.
					'<script type="text/javascript">'.
					'var thisForm = document.getElementById(\'TAGINFO\'); '.
					'</script>'.
					"<table><tr><th colspan=\"2\">$blogID [ $tagID ] '".$r['tagTitle']."'</th></tr>".
					'<tr><th>Tag Title</th><td><input type="text" name="TAGTITLE" size="40" value="'.htmlPropertySafe($r['tagTitle']).'" /></td></tr>'.
					'<tr><th>Read Permission</th><td><input type="text" name="TAGREADPERMISSION" size="4" value="'.$r['permissionRead'].'" onKeyPress="this.value = inTheRange(parseInt2(this.value), 0, 100);" onblur="this.value = inTheRange(parseInt2(this.value), 0, 100);" /></td></tr>'.
					'<tr><th>Read Access Control</th><td><input type="checkbox" style="border:0" name="TAGREADACCESSCONTROL" '.
						(($r['accessControlRead']==1)?'checked="checked"':'').
						' /></td></tr>'.
					'<tr><th>Write Permission</th><td><input type="text" name="TAGWRITEPERMISSION" size="4" value="'.$r['permissionWrite'].'" onKeyPress="this.value = inTheRange(parseInt2(this.value), 0, 100);" onblur="this.value = inTheRange(parseInt2(this.value), 0, 100);" /></td></tr>'.
					'<tr><th>Write Access Control</th><td><input type="checkbox" style="border:0" name="TAGWRITEACCESSCONTROL" '.
						(($r['accessControlWrite']==1)?'checked="checked"':'').
						' /></td></tr>'.
					'<tr><th colspan="2">Access Control List</th></tr>'.
					'<tr><td colspan="2"><textarea name="TAGACCESSCONTROLLIST" rows="2" cols="82" onKeyPress="this.rows = countLines(this.value, 80); setHeight2(); setHeight3(); " >';
				$rs = DBQ("select user from BlogTagAccessControl where blogID='$blogID' and tagID=$tagID");
				$first = true;
				while ($t = mysql_fetch_row($rs)) {
					if ($first) {
						$first = false;
						$returnValue.=$t[0];
					} else 
						$returnValue.=','.$t[0];
				}
				$returnValue.=
					'</textarea></td></tr>'.
					'<tr><td colspan="2"><input type="submit" value="Update The Tag" style="width:350px" /><td></tr>'.
					'</table>'.
					'</form>';
			}
			break;
		case "UPDATE":
				$TAGTITLE = request("TAGTITLE");
				if (strlen($TAGTITLE)<1)
					return "ERROR: TagTitle Too Short";
				$TAGREADPERMISSION = requestInt("TAGREADPERMISSION");
				if ($TAGREADPERMISSION<0 || $TAGREADPERMISSION>100)
					return "ERROR: TagReadPermission Out of Bound";
				$TAGWRITEPERMISSION = requestInt("TAGWRITEPERMISSION");
				if ($TAGWRITEPERMISSION<0 || $TAGWRITEPERMISSION>100)
					return "ERROR: TagWritePermission Out of Bound";
				$TAGREADACCESSCONTROL = (request("TAGREADACCESSCONTROL")=='on')?1:0;
				$returnValue.=request("TAGREADACCESSCONTROL").$TAGREADACCESSCONTROL;
				$TAGWRITEACCESSCONTROL= (request("TAGWRITEACCESSCONTROL")=='on')?1:0;
				$returnValue.=request("TAGWRITEACCESSCONTROL").$TAGWRITEACCESSCONTROL;
				$TAGACCESSCONTROLLIST = listFromList(listFromList(listFromList(listFromList(
									explode(",",request("TAGACCESSCONTROLLIST")), ":"),
								" "), "\n"), "\r");
				DBQ("START TRANSACTION");
				DBQ("update BlogTag set tagTitle='$TAGTITLE', accessControlRead=$TAGREADACCESSCONTROL ,accessControlWrite=$TAGWRITEACCESSCONTROL ,permissionRead=$TAGREADPERMISSION ,permissionWrite=$TAGWRITEPERMISSION where blogID='$blogID' and tagID=$tagID");
				DBQ("delete from BlogTagAccessControl where blogID='$blogID' and tagID=$tagID ");
				foreach ($TAGACCESSCONTROLLIST as $key => $value)
				{
					$returnValue.="[".$key."=>".$value."]";
					DBQ("insert into BlogTagAccessControl values ('$blogID', $tagID, '$value')");
				}
				DBQ("COMMIT");
				$returnValue.= "Updated.<br />";
			break;
		}
	}

	$returnValue.='<script type="text/javascript">setHeight2(); setHeight3();</script></div>';
	return $returnValue;
}
function doWriteArticle($parentDIV )
{
	global $URL, $BLOGID, $minTitleLength, $maxTitleLength, $ATTACHMENTDIR, $LOCALSEPERATOR, $mkdirMOD;
	function constructTextArea($title, $content, $hiddenTitle, $hiddenContent)
	{
		return "<br /><input type=\"text\" size=\"80\" value=\"".htmlPropertySafe($title)."\" /><br />".
			"<textarea>".$content."</textarea><br />".
			"<input type=\"text\" size=\"80\" value=\"".htmlPropertySafe($hiddenTitle)."\" /><br />".
			"<textarea>".$hiddenContent."</textarea><br />";
	}
	$returnValue = 'UNDER CONSTUCTION. Not actually writing anything :) <br />';

	$TAG = requestList("TAG", true, true);
	$NEWTAG = requestList("NEWTAG", false);
	$NEWTAGDISABLED = requestList("NEWTAGDISABLED", false);
	$NEWTAGREADPERMISSION = requestList("NEWTAGREADPERMISSION", true);
	$NEWTAGREADACCESSENABLED = requestList("NEWTAGREADACCESSENABLED", false);
	$NEWTAGWRITEPERMISSION = requestList("NEWTAGWRITEPERMISSION", true);
	$NEWTAGWRITEACCESSENABLED = requestList("NEWTAGWRITEACCESSENABLED", false);
	$NEWTAGACCESSCONTROLLIST = requestList("NEWTAGACCESSCONTROLLIST", false, true);

	$THREAD = requestInt("THREAD");
	$TITLE = request("TITLE");
	$GUESTNAME = request("GUESTNAME");
	$GUESTEMAIL = request("GUESTEMAIL");
	$GUESTHOMEPAGE = request("GUESTHOMEPAGE");
	$GUESTPASSWORD = request("GUESTPASSWORD");
	$TRACKBACKFROM = request("TRACKBACKFROM");
	$CONTENT = request("CONTENT");
	$HIDDENCONTENTENABLED = request("HIDDENCONTENTENABLED");
	$HIDDENCONTENTTITLE = request("HIDDENCONTENTTITLE");
	$HIDDENCONTENT = request("HIDDENCONTENT");
	$HTML = request("HTML"); // 'on' or ''
	$property=getBlogProperty($BLOGID);
	if ($property['modeHTML']==0)
		$HTML = false;
	else
		$HTML = ($HTML=='on' || $HTML=='YES')?true:false;

	$returnValue.= "----------------------------------------<br />".implode(",",$TAG)."<br />".implode(",",$NEWTAG)."<br />".implode(",",$NEWTAGDISABLED)."<br />".implode(",",$NEWTAGREADPERMISSION)."<br />".implode(",",$NEWTAGREADACCESSENABLED)."<br />".implode(",",$NEWTAGWRITEPERMISSION)."<br />".implode(",",$NEWTAGWRITEACCESSENABLED)."<br />".implode(",",$NEWTAGACCESSCONTROLLIST)."<br />$THREAD<br />$TITLE<br />$GUESTNAME<br />$GUESTEMAIL<br />$GUESTPASSWORD<br />$TRACKBACKFROM<br />$CONTENT<br />$HIDDENCONTENTENABLED<br />$HIDDENCONTENTTITLE<br />$HIDDENCONTENT<br />$HTML<br />----------------------------------------<br />";
	// Security Check
	$access=getAccessBlog($_SESSION['APFSDS_ID'], $BLOGID);
	if ($property['permissionWrite']>$_SESSION['APFSDS_Perm'])
		return 'ERROR: Permission Denied.'.constructTextArea($TITLE, $CONTENT, $HIDDENCONTENTTITLE, $HIDDENCONTENT);
	if ($access['write']===false)
		return 'ERROR: Write Access Denied.'.constructTextArea($TITLE, $CONTENT, $HIDDENCONTENTTITLE, $HIDDENCONTENT);

	// Check Tag (existance and security)
	if (sizeof($TAG)>0)
		foreach ($TAG as $i => $value)
		{
			$rs = DBQ("select * from BlogTag where blogID='$BLOGID' and tagID=$value");
			$r = mysql_fetch_assoc($rs);
			if ($r)
			{
				if ($r['permissionWrite']>$_SESSION['APFSDS_Perm'])
					return 'ERROR: Tag #'.$value.'('.$r['tagTitle'].'): Permission Denied.'.constructTextArea($TITLE, $CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
				if ($r['accessControlWrite']===1)
				{
					$rsTAC = DBQ("select * from BlogTagAccessControl where blogID='$BLOGID' and tagID=$value and user='".$_SESSION['APFSDS_ID']."'");
					if (!mysql_fetch_row($rsTAC))
					{
						mysql_free_result($rsTAC);
						return 'ERROR: Tag #'.$value.'('.$r['tagTitle'].'): Access Control Denied.'.constructTextArea($TITLE, $CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
					}
					mysql_free_result($rsTAC);
				}
			}
			else
				return 'ERROR: Tag #'.$value.'['.request("TAG").']:'.sizeof($TAG).' does not exists.'.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			mysql_free_result($rs);
		}

	// Check New Tags-1 unset disabled ones (and reindex by array_values)
	foreach ($NEWTAGDISABLED as $i=>$value)
	{
		if ($value=='disabled')
		{
			unset($NEWTAG[$i]);
			unset($NEWTAGDISABLED[$i]);
			unset($NEWTAGREADPERMISSION[$i]);
			unset($NEWTAGREADACCESSENABLED[$i]);
			unset($NEWTAGWRITEPERMISSION[$i]);
			unset($NEWTAGWRITEACCESSENABLED[$i]);
			unset($NEWTAGACCESSCONTROLLIST[$i]);
		}
	}
	array_values($NEWTAG); array_values($NEWTAGDISABLED); array_values($NEWTAGREADPERMISSION); array_values($NEWTAGREADACCESSENABLED); array_values($NEWTAGWRITEPERMISSION); array_values($NEWTAGWRITEACCESSENABLED); array_values($NEWTAGACCESSCONTROLLIST);
	
	// Check New Tags-2 (distinctness and security)
	$num = sizeof($NEWTAG);
	for ($i=0;$i<$num;$i++) {
		for ($j=0;$j<$i;$j++)
			if ($NEWTAG[$j]==$NEWTAG[$i]){
				$NEWTAGDISABLED[$i]='disabled';
				break;
			}
		if ($NEWTAGDISABLED[$i]!='disabled') {	// check security validity
			if ($_SESSION['APFSDS_Perm']==100) // admin can do anything.
				continue;
			if ($_SESSION['APFSDS_ID']!=$property['admin'])
				return 'ERROR: You are not the owner of the blog. You cannot make a new tag. '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			if ($_SESSION['APFSDS_Perm']<$NEWTAGWRITEPERMISSION[$i])
				return 'ERROR: Tag '.$NEWTAG[$i].' has higher write permission level than you are.'.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			if ($NEWTAGWRITEPERMISSION[$i]<$NEWTAGREADPERMISSION[$i])
				return 'ERROR: Tag '.$NEWTAG[$i].' has higher write permission than its read permission.'.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			if ($NEWTAGWRITEACCESSENABLED[$i]=='true') {
				$list = explode(":", $NEWTAGACCESSCONTROLLIST[$i]);
				$numAccess = sizeof($list);
				$found=false;
				for ($k=0;$k<$numAccess;$k++)
					if ($list[$k]==$_SESSION['APFSDS_ID'] && strlen($list[$k])>0)
						$found=true;
				if ($found==false)
					return 'ERROR: Tag '.$NEWTAG[$i].'does not allow you to write in the access list.'.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			}
		}
	}
	foreach ($NEWTAGDISABLED as $i=>$value) { // delete indistinct parts
		if ($value=='disabled') {
			unset($NEWTAG[$i]);
			unset($NEWTAGDISABLED[$i]);
			unset($NEWTAGREADPERMISSION[$i]);
			unset($NEWTAGREADACCESSENABLED[$i]);
			unset($NEWTAGWRITEPERMISSION[$i]);
			unset($NEWTAGWRITEACCESSENABLED[$i]);
			unset($NEWTAGACCESSCONTROLLIST[$i]);
		}
	}
	array_values($NEWTAG); array_values($NEWTAGDISABLED); array_values($NEWTAGREADPERMISSION); array_values($NEWTAGREADACCESSENABLED); array_values($NEWTAGWRITEPERMISSION); array_values($NEWTAGWRITEACCESSENABLED); array_values($NEWTAGACCESSCONTROLLIST);

	// at least one TAG if it's mandatory.
	switch ($property['modeTag']) {
		case 0: // NO TAG
			if (sizeof($TAG)>0 || sizeof($NEWTAG)>0)
				return 'ERROR: Tag not allowed in this blog. '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			break;
		case 1: // TAG allowed but not mandatory
			break;
		case 2: // Mandatory Tag
			if (sizeof($TAG)==0 && sizeof($NEWTAG)==0)
				return 'ERROR: Tag should be set. This blog is in tag-mandatory mode. '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			break;
		default:
			return 'ERROR: DB Inconsistency: modeTag of ['.$BLOGID.'] out of range: '.$property['modeTag'].'  '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
	}
	
	// check title length
	if (strlen($TITLE)<$minTitleLength)
		return 'ERROR: Title Too Short '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
	$TITLE = substr($TITLE, 0, $maxTitleLength);
	
	// check content length
	if (strlen($CONTENT)<1)
		return 'ERROR: Content Too Short '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
	
	// check hiddenTitle,hiddenContent length if it's enabled
	if ($HIDDENCONTENTENABLED=='YES' || strlen($HIDDENCONTENT)>0 || strlen($HIDDENCONTENTTITLE)>0)
	{
		$HIDDENCONTENTENABLED='on';
	}
	
	// check guestinfo
	if ($_SESSION['APFSDS_Logged']!=1) {
		if (strlen($GUESTNAME)<1)
			return 'ERROR: Guest\' name required. '.constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
		$GUESTNAME = substr($GUESTNAME, 0, 40);
		$GUESTEMAIL = substr($GUESTEMAIL, 0, 80);
		$GUESTPASSWORD = substr($GUESTPASSWORD, 0, 12);

		$threadnum = ($THREAD==false)?0:$THREAD;

		$captcha = $_SESSION['APFSDS_Captcha'][$BLOGID.'X'.$threadnum];
		$captchaEntered = request("CAPTCHA");
		if (strlen($captcha)<3 || strcasecmp($captchaEntered, $captcha)!=0) {
			return 'ERROR: Captcha Error. Please Read and Write down the image. '.constructTextArea($TITLE, $CONTENT, $HIDDENCONTENTTITLE, $HIDDENCONTENT);
		}
	}
	
	// attachments
	if ($property['modeUpload']==0)
		$uploadNum = 0;
	else
		$uploadNum = request("attachFileCount")+1;
	$uploaded = array();
	$actuallyUploadedNum = 0;
	print_r($_FILES);
	for ($i=0;$i<$uploadNum;$i++) {
		$returnValue.="attachFile$i : ".$_FILES["attachFile"]['tmp_name'][$i]."<br />";

		$uploaded[$i] = isset($_FILES["attachFile"]['tmp_name'][$i]) && is_uploaded_file($_FILES["attachFile"]['tmp_name'][$i]);
		if (!isset($_FILES["attachFile"]["tmp_name"][$i]))
			$returnValue.="FALSE.<br />";
		else
			print_r($_FILES["attachFile"]["tmp_name"][$i]);
		if ($uploaded[$i]===true) {
			$returnValue.="TRUE.<br />";
			$fileNameAs[$i] = $_FILES["attachFile"]['name'][$i];
			$fileMime[$i] = $_FILES["attachFile"]['type'][$i];
			$fileSize[$i] = $_FILES["attachFile"]['size'][$i];
			$fileName[$i] = $_FILES["attachFile"]['tmp_name'][$i];
			$actuallyUploadedNum ++;

			if (!is_dir($ATTACHMENTDIR.$BLOGID))
				if (!mkdir($ATTACHMENTDIR.$BLOGID, $mkdirMOD))
					return "ERROR: Cannot create attachment directory: ".$ATTACHMENTDIR.$BLOGID." / ".constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
		}
	}
	$returnValue.="UPLOAD : ".$uploadNum." of ".$actuallyUploadedNum."<br />";
	
	// move into LOCK state
	DBQ("START TRANSACTION");
	//$HIDDENCONTENTENABLED = request("HIDDENCONTENTENABLED");
	$HIDDENCONTENTTITLE = request("HIDDENCONTENTTITLE");
	$HIDDENCONTENT = request("HIDDENCONTENT");
	
	// insert into the DB
	logwrite("Article Write @$BLOGID/$GUESTNAME/$GUESTEMAIL/$GUESTHOMEPAGE [$TITLE]: \n$CONTENT\n$HIDDENCONTENT");
	DBQ("insert into BlogArticle values('$BLOGID', null, ".
			(($THREAD==0)?'null, ':"$THREAD , ").
			"'$TITLE', ".
			(($_SESSION['APFSDS_Logged']==1)?("'".$_SESSION['APFSDS_ID']."', null, null, null, null, "):
			 "null, '$GUESTNAME', '$GUESTEMAIL', '$GUESTHOMEPAGE', '$GUESTPASSWORD', ").
			"null, NOW(), ".
			"'".$_SERVER['REMOTE_ADDR']."', ".
			(($HTML==true)?"1, ":"0, ").
			"0, ".
			"$actuallyUploadedNum , ".
			"1, ".
			"'".(($HTML==true)?$CONTENT:html2text($CONTENT))."', ".
			(($HIDDENCONTENTENABLED=='on')?
			 "'$HIDDENCONTENT', '$HIDDENCONTENTTITLE', ":
			 "null, null, ").
			"'$TRACKBACKFROM', 0 ".
			")");
	$articleID = mysql_insert_id();
	
	// create attachment directory if we have attachments.
	if (is_dir($ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID))
	{
		DBQ("ROLLBACK");
		return "ERROR: $ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID lready exists.".constructTextArea($TITLLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
	}
	if ($actuallyUploadedNum > 0)
		if (!mkdir($ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID, $mkdirMOD))
			return "ERROR: Cannot create attachment directory: ".$ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID." / ".constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);

	// move the attached file to $ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID
	// and register in BlogArticleAttached
	if ($actuallyUploadedNum>0)
		for($i=0;$i<$uploadNum;$i++) {
			if ($uploaded[$i]===true)
			{
				move_uploaded_file($fileName[$i], $ATTACHMENTDIR.$BLOGID.$LOCALSEPERATOR.$articleID.$LOCALSEPERATOR.$i.".".$fileNameAs[$i]);
				DBQ("insert into BlogArticleAttached values(".
						"'$BLOGID', ".
						"$articleID , ".
						"$i , ".
						"'".$i.".".$fileNameAs[$i]."', ".
						"'".$fileMime[$i]."')");
			}
		}
	
	// create new tags (in BlogTag and BlogTagAccessControl)
	// create tag assoc (in BlogTagArticleAssoc)
	$num = sizeof($NEWTAG);
	for ($i=0;$i<$num;$i++) {
		DBQ("insert into BlogTag values ('$BLOGID', null, '".$NEWTAG[$i]."', ".
				(($NEWTAGREADACCESSENABLED[$i]=='on')?'1, ':'0, ').
				(($NEWTAGWRITEACCESSENABLED[$i]=='on')?'1, ':'0, ').
				$NEWTAGREADPERMISSION[$i]." , ".
				$NEWTAGWRITEPERMISSION[$i]." )");
		$tagID = mysql_insert_id();
		$BTAC = explode(":", $NEWTAGACCESSCONTROLLIST[$i]);
		for ($j=0;$j<sizeof($BTAC);$j++){
			for ($k=0;$k<$j;$k++)
				if ($BTAC[$j]==$BTAC[$i])
					continue;
			$rs = DBQ("select id from USERS where id='".$BTAC[$j]."'");
			if (mysql_fetch_row($rs))
				DBQ("insert into BlogTagAccessControl values ( '$BLOGID', $tagID, '".$BTAC[$j]."')");
			mysql_free_result($rs);
		}
		DBQ("insert into BlogTagArticleAssoc values('$BLOGID', $tagID, $articleID)");
	}
	$num = sizeof($TAG);
	for ($i=0; $i<$num; $i++)
		DBQ("insert into BlogTagArticleAssoc values('$BLOGID', ".$TAG[$i].", $articleID)");
	
	// came out from LOCK state
	DBQ("COMMIT");
	
    $returnValue.= '<script type="text/javascript">setHeight2(); setHeight3();</script>';
    return $returnValue;
}
function doWriteComment($thread)
{
	global $URL, $BLOGID, $ARTICLEID;
	function reconstruct()
	{
		DBQ("ROLLBACK");
		return '<textarea>'.request("CONTENT")."</textarea>";
	}
    $returnValue = '';
	$property = getBlogProperty($BLOGID);
	DBQ("START TRANSACTION");

	$rs = DBQ("select * from BlogArticle where blogID='$BLOGID' and articleID=$ARTICLEID");
	$article = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$article)
		return 'ERROR: Article Not Found '.reconstruct();
	
	// security check
	if ($property['permissionComment']>$_SESSION['APFSDS_Perm'])
		return 'ERROR: Blog Comment Permission Denied '.reconstruct();
	$access = getAccessBlog($_SESSION['APFSDS_ID'], $BLOGID);
	if (!$access['comment'])
		return 'ERROR: Blog Comment Access Denied '.reconstruct();

	// thread check
	if ($thread!==0 && $thread!==false){
		$rs = DBQ("select author from BlogComment where blogID='$BLOGID' and articleID=$ARTICLEID and commentID=$thread ");
		$r = mysql_fetch_row($rs);
		if (!$r)
			return "ERROR: Blog Comment-Reply didn't find its parent($thread) from $BLOGID:$ARTICLEID ".reconstruct();
		mysql_free_result($rs);
	}

	// spam check
	if ($_SESSION['APFSDS_Logged']!=1) {
		$captchaEntered = request("CAPTCHA");
		$captcha = $_SESSION['APFSDS_Captcha'][$BLOGID.'_'.$ARTICLEID.'_'.$thread];
		if (strlen($captcha)<3 || strcasecmp($captchaEntered, $captcha)!=0) {
			logwrite("Comment Failure: Captcha Failure/$BLOGID/$ARTICLEID/".request("NAME")." |".mb_substr(request("CONTENT"), 80));
			return "ERROR: Captcha Validation Failure.";
		}
	}
	logwrite("Comment $BLOGID/$ARTICLEID/".request("NAME")." |".request("CONTENT"));

	$secret = requestInt("secret");
	if ($secret!=100 && $secret!=0 && $secret!=3)
		$secret = 0;

	// into the DB
	DBQ("insert into BlogComment values (".
			"'$BLOGID', ".
			"$ARTICLEID, ".
			"null, ".
			(($thread===false || $thread===0)?'null, ':"$thread, ").
			(($_SESSION['APFSDS_Logged']===1)?
			 	("'".$_SESSION['APFSDS_ID']."', null, null, null, null, "):
				("null, '".request("NAME")."', '', '', '".request("GUESTPASSWORD")."', ")
			).
			"'".$_SERVER['REMOTE_ADDR']."', ".
			"null, ".
			"NOW() , ".
			"'".request("CONTENT")."', ".
			"$secret )");

	DBQ("COMMIT");
    return $returnValue;
}
function doUpdateArticle($blogID, $articleID)
{
	function constructTextArea($title, $content, $hiddenTitle, $hiddenContent)
	{
		return "<br /><input type=\"text\" size=\"80\" value=\"".htmlPropertySafe($title)."\" /><br />".
			"<textarea>".$content."</textarea><br />".
			"<input type=\"text\" size=\"80\" value=\"".htmlPropertySafe($hiddenTitle)."\" /><br />".
			"<textarea>".$hiddenContent."</textarea><br />";
	}

	global $URL, $ATTACHMENTDIR, $_FILES, $LOCALSEPERATOR, $mkdirMOD;
    $returnValue = '';
	// updatable: title, content, hiddenTitle, hiddenContent, setOfTags
	// 			add file / delete file

	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID ");
	$article = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$article) {
		return "ERROR: Article not found: $blogID:$articleID";
	}

	// security check
	if ($_SESSION['APFSDS_Logged']!==1 && (
				strlen($article['author'])>0 ||
				strlen($article['guestName'])<1 ||
				strlen($article['guestPassword'])<1 ||
				request("PASSWORD")!==$article['guestPassword']))
		return "ERROR: Security Error (Possibly wrong password) ";
	if ($_SESSION['APFSDS_Perm']!=100 && (
				(strlen($article['author'])>0 && $_SESSION['APFSDS_ID']!=$article['author']) ||
				(strlen($article['guestPassword'])>0 && request("PASSWORD")!=$article['guestPassword']) ||
				(strlen($article['author'])<1 && strlen($article['guestPassword'])<1))) 
		return "ERROR: Security Error (Possibly wrong password) ";

	$CONTENT = request("CONTENT");
	$TITLE = request("TITLE");
	$HIDDENCONTENTTITLE = request("HIDDENCONTENTTITLE");
	$HIDDENCONTENT = request("HIDDENCONTENT");
	$HIDDENCONTENTENABLED = (request("HIDDENCONTENTENABLED")=='on')?true:false;
	$TAG = requestList("TAG", true, true);
	// use attachFile for attachment ($_FILES["attachFile"])
	$attachNumberStart = requestInt("attachFileStart");
	$deletedAttachmentNumber = request("attachFileDelete");

	$HTML = request("HTML");
	if ($property['modeHTML']==0)
		$HTML=false;
	else if ($HTML=='on' || $HTML=='YES')
		$HTML=true;
	else
		$HTML = false;

	DBQ("START TRANSACTION");

	DBQ("update BlogArticle set content='$CONTENT' ,title='$TITLE' where blogID='$blogID' and articleID=$articleID ");
	if ($HIDDENCONTENTENABLED)
		DBQ("update BlogArticle set hiddenContent='$HIDDENCONTENT', hiddenContentTitle='$HIDDENCONTENTTITLE' where blogID='$blogID' and articleID=$articleID ");
	else
		DBQ("update BlogArticle set hiddenContent='$HIDDENCONTENT', hiddenContentTitle=null where blogID='$blogID' and articleID=$articleID ");
	if ($property['modeTag']==0)
		$TAG = array();
	else if ($property['modeTag']==2 && sizeof($TAG)==0) {
		DBQ("ROLLBACK");
		return "ERROR: Tag is mandatory in this blog.";
	}
	$numTag = sizeof($TAG);
	for ($i=0;$i<$numTag;$i++) {
		$accessTag = getAccessTag($id, $blogID, $TAG[$i]);
		if ($accessTag===false) {
			DBQ("ROLLBACK");
			return "ERROR: Tag not exists $blogID.".$TAG[$i];
		}
		if ($accessTag['permissionWrite']>$_SESSION['APFSDS_Perm']) {
			DBQ("ROLLBACK");
			return "ERROR: Tag $blogID.".$TAG[$i]." Permission Error";
		}
		if ($accessTag['accessControlWrite']===false) {
			DBQ("ROLLBACK");
			return "ERROR: Tag $blogID.".$TAG[$i]." AccessWrite Error";
		}
	}
	DBQ("delete from BlogTagArticleAssoc where blogID='$blogID' and articleID=$articleID");
	for ($i=0;$i<$numTag;$i++) {
		DBQ("insert into BlogTagArticleAssoc values ('$blogID', ".$TAG[$i].", $articleID )");
	}

	// new attachments
	if ($property['modeUpload']==0)
		$uploadNum = 0;
	else
		$uploadNum = request("attachFileCount");
	$uploaded = array();
	$actuallyUploadedNum = 0;
	print_r($_FILES);
	for ($i=0;$i<$uploadNum;$i++) {
		if ($_FILES["attachFile"]['error'][$i] != UPLOAD_ERR_OK)
			continue;

		$uploaded[$i] = isset($_FILES["attachFile"]['tmp_name'][$i]) && is_uploaded_file($_FILES["attachFile"]['tmp_name'][$i]);
		if ($uploaded[$i]===true) {
			$fileNameAs[$i] = $_FILES["attachFile"]['name'][$i];
			$fileMime[$i] = $_FILES["attachFile"]['type'][$i];
			$fileSize[$i] = $_FILES["attachFile"]['size'][$i];
			$fileName[$i] = $_FILES["attachFile"]['tmp_name'][$i];
			$actuallyUploadedNum ++;

			if (!is_dir($ATTACHMENTDIR.$blogID))
				if (!mkdir($ATTACHMENTDIR.$blogID, $mkdirMOD)) {
					DBQ("ROLLBACK");
					return "ERROR: Cannot create attachment directory: ".$ATTACHMENTDIR.$blogID." / ".constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
				}
		}
	}
	print ("NUM=".$actuallyUploadedNum.".");
	// create attachment directory if we have attachments.
	if ($actuallyUploadedNum > 0)
		if (!is_dir($ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID))
			if (!mkdir($ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID, $mkdirMOD)) {
				DBQ("ROLLBACK");
				return "ERROR: Cannot create attachment directory: ".$ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID." / ".constructTextArea($TITLE,$CONTENT,$HIDDENCONTENTTITLE,$HIDDENCONTENT);
			}

	// move the attached file to $ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID
	// and register in BlogArticleAttached
	if ($actuallyUploadedNum>0)
		for($i=0;$i<$uploadNum;$i++) {
			if ($uploaded[$i]===true)
			{
				move_uploaded_file($fileName[$i], $ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID.$LOCALSEPERATOR.($i+$attachNumberStart).".".$fileNameAs[$i]);
				DBQ("insert into BlogArticleAttached values(".
						"'$blogID', ".
						"$articleID , ".
						($i+$attachNumberStart)." , ".
						"'".($i+$attachNumberStart).".".$fileNameAs[$i]."', ".
						"'".$fileMime[$i]."')");
			}
		}

	// attachments to be deleted
	foreach ($deletedAttachmentNumber as $key => $value)
	{
		if ($value==='on') {
			$rs = DBQ("select filename from BlogArticleAttached where blogID='$blogID' and articleID=$articleID and attachmentID=".$key);
			$r = mysql_fetch_row($rs);
			mysql_free_result($rs);
			if ($r) {
				$toBeDeleted = $r[0];
				unlink($ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID.$LOCALSEPERATOR.$toBeDeleted);
				DBQ("DELETE FROM BlogArticleAttached where blogID='$blogID' and articleID=$articleID and attachmentID=".$key);
			}
		}
	}
	if (($_SESSION['APFSDS_Perm']==100 || $_SESSION['APFSDS_ID']==$property['admin']) && request("UPDATEMODIFIEDDATE")=="NO") 
	{
		DBQ("UPDATE BlogArticle set html=".(($HTML==true)?'1, ':'0, ')." attachedFiles = (select count(attachmentID) from BlogArticleAttached where blogID='$blogID' and articleID=$articleID ) where blogID='$blogID' and articleID=$articleID ");
	}
	else {
		DBQ("UPDATE BlogArticle set html=".(($HTML==true)?'1, ':'0, ')." modifyDate=NOW(), attachedFiles = (select count(attachmentID) from BlogArticleAttached where blogID='$blogID' and articleID=$articleID ) where blogID='$blogID' and articleID=$articleID ");
	}
	DBQ("COMMIT");
    return $returnValue.'<script type="text/javascript">parent.document.getElementById(window.name).style.display=\'none\'; parent.document.getElementById(\'divContent\').style.top =\'0px\'; </script>';
}
function doDeleteArticle($blogID, $articleID)
{
	global $URL, $ATTACHMENTDIR, $LOCALSEPERATOR;
    $returnValue = '';

	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID ");
	$article = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$article) {
		return "ERROR: Article not found: $blogID:$articleID";
	}

	// security check
	if ($_SESSION['APFSDS_Logged']!==1 && (
				strlen($article['author'])>0 ||
				strlen($article['guestName'])<1 ||
				strlen($article['guestPassword'])<1 ||
				request("PASSWORD")!==$article['guestPassword']))
		return "ERROR: Security Error (Possibly wrong password) ";
	if (($_SESSION['APFSDS_Perm']!=100 && $_SESSION['APFSDS_ID']!=$property['admin']) && 
			(
				(strlen($article['author'])>0 && $_SESSION['APFSDS_ID']!=$article['author']) ||
				(strlen($article['guestPassword'])>0 && request("PASSWORD")!=$article['guestPassword']) ||
				(strlen($article['author'])<1 && strlen($article['guestPassword'])<1)
			)
	   )
		return "ERROR: Security Error (Possibly wrong password) ";
	DBQ("START TRANSACTION");

	// delete Attachments
	$rs = DBQ("select filename from BlogArticleAttached where blogID='$blogID' and articleID=$articleID");
	while ($r = mysql_fetch_row($rs)) {
		unlink($ATTACHMENTDIR.$blogID.$LOCALSEPERATOR.$articleID.$LOCALSEPERATOR.$r[0]);
	}
	mysql_free_result($rs);

	DBQ("DELETE FROM BlogArticleAttached WHERE blogID='$blogID' and articleID=$articleID");
	DBQ("DELETE FROM BlogComment WHERE blogID='$blogID' and articleID=$articleID");
	DBQ("DELETE FROM BlogTagArticleAssoc WHERE blogID='$blogID' and articleID=$articleID");

	$rs = DBQ("select articleID from BlogArticle where blogID='$blogID' and threadFrom=$articleID");
	while($r = mysql_fetch_row($rs)) {
		DBQ("update BlogArticle set threadFrom = ".
				(($article['threadFrom']==null)?'null ':($article['threadFrom'])).
				" where blogID='$blogID' and articleID=".$r[0]);
	}

	DBQ("DELETE FROM BlogArticle WHERE blogID='$blogID' and articleID=$articleID");

	DBQ("COMMIT");
    return $returnValue;
}
function doDeleteComment($blogID, $articleID)
{
	global $URL;
    $returnValue = '';

	$commentID = requestInt("COMMENTID");
	$property = getBlogProperty($blogID);
	$rs = DBQ("select * from BlogArticle where blogID='$blogID' and articleID=$articleID");
	$article = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$article)
		return "Article $blogID.$articleID does not exist.";
	$rs = DBQ("select * from BlogComment where blogID='$blogID' and articleID=$articleID and commentID=$commentID");
	$comment = mysql_fetch_assoc($rs);
	mysql_free_result($rs);
	if (!$comment)
		return "Comment $blogID.$articleID.$commentID does not exist.";

	// Security Check
	if ($_SESSION['APFSDS_Logged']!==1 && (
				strlen($comment['author'])>0 ||
				strlen($comment['guestName'])<1 ||
				strlen($comment['guestPassword'])<1 ||
				request("PASSWORD")!==$comment['guestPassword']))
		return "ERROR: Security Error (Possibly wrong password) ";
	if (($_SESSION['APFSDS_Perm']!=100 && $_SESSION['APFSDS_ID']!=$property['admin']) && 
			(
				(strlen($comment['author'])>0 && $_SESSION['APFSDS_ID']!=$comment['author']) ||
				(strlen($comment['guestPassword'])>0 && request("PASSWORD")!=$comment['guestPassword']) ||
				(strlen($comment['author'])<1 && strlen($comment['guestPassword'])<1)
			)
	   )
		return "ERROR: Security Error (Possibly wrong password) ";
	DBQ("START TRANSACTION");

	$rs = DBQ("select commentID from BlogComment where blogID='$blogID' and articleID=$articleID and thread=$commentID");
	while ($r = mysql_fetch_row($rs)) {
		DBQ("update BlogComment set thread = ".(($comment['thread']==null)?"null ":($comment['thread'])).
				" where blogID='$blogID' and articleID=$articleID and commentID=".$r[0]);
	}
	mysql_free_result($rs);

	DBQ("delete from BlogComment where blogID='$blogID' and articleID=$articleID and commentID=$commentID");


	DBQ("COMMIT");
    return $returnValue;
}

?>
