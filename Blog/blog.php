<?
$SKIP = requestInt("SKIP");
if (!isset($BLOGID)) 
{
	$BLOGID=request("BLOGID");
	if ($BLOGID=='')
		$BLOGID='default';
}
$SEARCHSTARTDATE = request("sStartDate");
$SEARCHENDDATE = request("sEndDate");
$SEARCHTAG = requestList("sTag", true);
$SEARCHTAGARRAY = request("sTagArray");
if (is_array($SEARCHTAGARRAY))
    foreach ($SEARCHTAGARRAY as $key => $value) {
	    $SEARCHTAG[] = $value;
}
$search = false;
$searchTag = array();
if ($SEARCHSTARTDATE!='')
{
    $search = true;
    $searchTag['startDate'] = $SEARCHSTARTDATE;
}
if ($SEARCHENDDATE!='')
{
    $search = true;
    $searchTag['endDate'] = $SEARCHENDDATE;
}
if ($SEARCHTAG!='')
{
    $search = true;
    $searchTag['tag'] = $SEARCHTAG;
}
if ($search === true)
{
    $search = $searchTag;
}

require_once("engine.php");
if ($articleID=request("Article"))
{
	print ('<br /><br />');
	print ('<div style="100%" class="blogArticle" id="blog'.$BLOGID.$articleID.'">');
	print showArticle($BLOGID, $articleID, false, true);
	
/*	<iframe name="blog'.$BLOGID.$articleID.'" id="ifblog'.$BLOGID.$articleID.
			'" frameborder="0" width="100%" height="100%" '.
			'scrolling="no" marginheight="0" marginwidth="0" src="'.
			$URL.'/Blog/blogAux.php?DO=READARTICLE&amp;BLOGID='.urlencode($BLOGID).'&amp;ARTICLEID='.urlencode($articleID).'&amp;HEADER=YES">Need support for iframe tag</iframe></div>');
*/
	print '</div>';
	// if ($property['type']) // WORK AREA
	if (!isset($writeButton))
		$writeButton = false;
	print showList($BLOGID, $SKIP, "divBlog".((string)$BLOGID), $search, false, $writeButton, true);
	print "<br /><br />";
	$diggingTitle = returnTitle($BLOGID, $articleID);
?>
	<script type="text/javascript">
	document.title = '<?=$diggingTitle?> - '+document.title;
	</script>
<?
}
else
{
	if (!isset($writeButton))
		$writeButton = false;
	print (showList($BLOGID, $SKIP, "divBlog".((string)$BLOGID), $search, false, $writeButton));
}

?>
