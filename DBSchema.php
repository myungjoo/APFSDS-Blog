<?
include_once("Common/common.php");

if ($_SERVER['REMOTE_ADDR']!='127.0.0.1' && $_SERVER['REMOTE_ADDR']!='128.174.245.81' && $_SERVER['REMOTE_ADDR']!='130.126.80.136')
{
	print ("Permission Error [".$_SERVER['REMOTE_ADDR']."]");
    return;
}

//return; // disabled.

DBConnect();
if (request("BLOGRESET")=="YES") {

if (request("DROP")=='YES' || $DROP=='YES')
{
	DBQ("drop table IF EXISTS CHARS");
	DBQ("drop table IF EXISTS USERS");
	DBQ("drop table IF EXISTS Blog, BlogArticle, BlogComment, BlogArticleAttached, BlogTag, BlogTagArticleAssoc, BlogTagAccessControl, BlogAccessControl");
	DBQ("drop table IF EXISTS PhotoComment, STATUS_DEF, STATUS, MEMO, IPSTAT, IPSTATGROUP, COUNTER, ORACLE, IMOTICONS");

}
DBQ("create table IF NOT EXISTS CHARS (".
    "id char(40)	not null, ". 
    "pictureURL		TEXT, ".
    "smallPictureURL	TEXT, ".
    "infoURL		TEXT, ". // TEXT : 64k
    "flag		SET('personal', 'adminOnly'), ".
    "primary key(id)".
    ")");
DBQ("insert into CHARS values('private', null, null, null, '')");
DBQ("insert into CHARS values('god', null, null, null, 'adminOnly')");

DBQ("create table if not exists USERS (".
    "id			char(40) not null, ".
    "password		char(40) not null, ".
    "permission		int not null, ". 
	// 0: waiting for commit
	// 1: deleted
	// 2: inactive
	// 3: lowest for active 
	// 4 ~ 99 : levels
	// 100: admin
    "level		int not null, ".
    "experience		int not null, ".
    "money		int not null, ".
    "chars		char(40) not null REFERENCES CHARS.id, ".
    "realname		char(40) not null, ".
	"nickname		char(40), ".
    "sex		int not null, ". // 0: Undefined, 1: Male, 2: Female
    "birth		date, ".
    "telephone		TEXT, ".
    "cellular		TEXT, ".
    "address		TEXT, ".
    "job		TEXT, ".
    "email	        TEXT, ".
    "comment		TEXT, ".
    "nameTagURL		TEXT, ".
    "lastlogdate	TIMESTAMP, ".
    "lastlogip		char(80), ".
    "undefined		char(255), ". // will be used for 'ITEM SLOT' or something like that..
    "primary key(id)".
    ")");
DBQ("insert into USERS values ('admin', 'mzx', 100, 100, 1000000, 1000000, 'god', 'MyungJoo Ham', 'MZX', 1, '1981-02-09', '1-217-332-2129', '1-217-714-2858', '1107 W Green St APT336, Urbana, IL, USA', 'Graduate Student', 'ham1@uiuc.edu', null, null, null, null, '')");

DBQ("create table if not exists Blog (".
    "id			char(40) not null, ".
    "admin		char(40) not null REFERENCES USERS.id, ".
    "type		int not null, ". // 0: Web Board, 1: Guest Book, 2: Blog
    "optionOpenBlog		int not null, ". // for Blog, 0: no open, 1:open first page, 2: open all
    "optionTableWidth		TEXT not null ,". // example : "95%" // ignored currently
    "perPage		int not null DEFAULT 5, ".
    "accessControlIndex	int not null, ". // 0: No, 1: Yes and refer to BlogAccessControl
    "accessControlRead 	int not null, ". // 0: No, 1: Yes and refer to BlogAccessControl
    "accessControlWrite	int not null, ". // 0: No, 1: Yes and refer to BlogAccessControl
    "accessControlComment   int not null, ". // 0: No, 1: Yes and refer to BlogAccessControl
    "permissionIndex	int not null, ". // required permission
    "permissionRead	int not null, ". // required permission
    "permissionWrite	int not null, ". // required permission
    "permissionComment	int not null, ". // required permission
    "modeAnonymous	int not null, ". // if 1, article writer is hidden
    "modeGuestPolicy	int not null, ". // if 1, guest is marked/distinguished
    "modeHTML		int not null, ". // 0: NO HTML, 1: HTML Permitted, 2: HTML default
    "modeCommentHTML	int not null, ". // 0: NO, 1: Permitted Partially, 2: Permitted Fully
    "modeIPShow		int not null, ". // 0: NO, 1: IP address is shown, 2: Partially shown
    "modeThread		int not null, ". // if 1, Threading Tree is enabled. no work wwith 2:Blog
    "modeUpload		int not null, ". // 0:no, xxxx : max size(kbytes), -1: no limit
    "modeTag		int not null, ". // 0:no, 1:yes, 2:mandatory
     "cssURL		TEXT, ". // Only available when it is stand alone mode.
    "info		TEXT, ".
    "titleTEXT		TEXT, ". // title text
    "titleHTML		TEXT, ". // title decorated
	"accessURL		TEXT, ". // access URL example) http://babot.net/index.php
    "primary key(id)".
    ")");
DBQ("insert into Blog values ('default', 'besthm1', 2, 2, '95%', 5, 0, 0, 0, 0, 0, 0, 3, 0, 0, 1, 2, 1, 1, 0, -1, 2, '$CSS', 'BaBoT.net Main Blog', 'BaBoT.net', 'BaBoT.net', 'http://apfsds.com/index.php')");
DBQ("insert into Blog values ('guestbook', 'besthm1', 1, 2, '95%', 20, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 1, 1, 0, 0, 0, '$CSS', 'BaBoT.net Guestbook', 'BaBoT.net', 'BaBoT.net', 'http://apfsds.com/guestbook.php')");

DBQ("create table if not exists BlogAccessControl (". // list of permitted users
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "user		char(40) not null REFERENCES USERS.id, ".
    "primary key(blogID, user)".
    ")");
DBQ("create table if not exists BlogTag (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "tagID		int not null AUTO_INCREMENT, ".
    "tagTitle		TEXT, ". // null for no Header(text on the article title) html enabled. 
    "accessControlRead	int not null, ". // 0: No, 1: Yes and refer to BlogTagAccessControl
    "accessControlWrite	int not null, ". // 0: No, 1: Yes and refer to BlogTagAccessControl
    "permissionRead	int not null, ".
    "permissionWrite	int not null, ".
    "primary key(blogID, tagID)".
    ")");
DBQ("create table if not exists BlogTagAccessControl (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "tagID		int not null REFERENCES BlogTag.tagID, ".
    "user		char(40) not null REFERENCES USERS.id, ".
    "primary key(blogID, tagID, user)".
    ")");
DBQ("create table if not exists BlogArticle (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "articleID		int not null AUTO_INCREMENT, ". // enter null here.
    "threadFrom		int, ". // null for a new article
    "title		TEXT not null, ".
    "author		char(40) REFERENCES USERS.id, ". // null for guest
    "guestName		TEXT, ". // null for non-guest.
	"guestEmail		TEXT, ". // null for non-guest.
	"guestHomepage	TEXT, ". // null for non-guest.
    "guestPassword	char(20), ". // null for non-guest
    "createDate		TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, ".
    "modifyDate		TIMESTAMP, ".
    "ip			char(80), ".
    "html		int not null, ". // if 1, it's written in HTML
    "hit		int not null DEFAULT 0, ".
    "attachedFiles	int not null DEFAULT 0, ". // number of attached files
    "attachedMethod	int not null DEFAULT 1, ". // 0: Old Version ($exec/$bbsid/$num.$filename). jpg/gif is shown on the top
						    // 1: New Version ($exec/$bbsid/$num/$filename
						    // not shown on the top
    "content		MEDIUMTEXT not null, ".
    "hiddenContent	MEDIUMTEXT, ".
    "hiddenContentTitle	TEXT, ". // null if there is no hiddenContent
	"trackbackFrom	TEXT, ".
    "deleted		int not null, ". // 0: not deleted / 1:(soft) deleted
    "primary key(blogID, articleID)".
    ")");
DBQ("create table if not exists BlogTagArticleAssoc (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "tagID		int not null REFERENCES BlogTag.tagID, ".
    "articleID		int not null REFERENCES BlogArticle.articleID, ".
    "primary key(blogID, tagID, articleID)".
    ")");
DBQ("create table if not exists BlogArticleAttached (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "articleID		int not null REFERENCES BlogArticle.articleID, ".
    "attachmentID	int not null, ".
    "filename		TEXT, ".
    "filemime		TEXT, ".
    "primary key(blogID, articleID, attachmentID)".
    ")");
DBQ("create table if not exists BlogComment (".
    "blogID		char(40) not null REFERENCES Blog.id, ".
    "articleID		int not null REFERENCES BlogArticle.articleID, ".
    "commentID		int not null AUTO_INCREMENT, ".
    "thread		int,". // null if starting thread.
    "author		char(40) REFERENCES USERS.id, ".
    "guestName		TEXT, ". // null for non-guest
	"guestEmail		TEXT, ". // null for non-guest
	"guestHomepage	TEXT, ". // null for non-guest
    "guestPassword	char(20), ".
    "ip			char(80), ".
    "createDate		TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, ".
    "modifyDate		TIMESTAMP, ".
    "content		MEDIUMTEXT, ".
	"secret		int not null DEFAULT 0, ". // 0:non-secret 3: Logged Only 4: Senior members only ... 100:secret only the owner/admin can read
    "primary key(blogID, articleID, commentID)".
    ")");

DBQ("create table if not exists PhotoComment (".
    "photoDir		TEXT not null, ".
    "photoFilename	TEXT not null, ".
	"id				int not null AUTO_INCREMENT, ".
    "createon		TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, ".
    "author			char(40) REFERENCES USERS.id, ".
    "guestInfo		TEXT, ".
    "content		MEDIUMTEXT, ".
    "ip			char(80), ". 
	"thread			int, ". // null for no threading.
	"primary key(id)".
    ")");

DBQ("create table if not exists STATUS (id int)");
DBQ("create table if not exists STATUS_DEF (id int not null AUTO_INCREMENT, description TEXT, icon TEXT, primary key(id))");

DBQ("create table if not exists MEMO (id int not null AUTO_INCREMENT ,author char(40) REFERENCES USERS.id, content TEXT, ip char(80), createon TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, primary key(id));");

DBQ("create table if not exists IPSTAT (ip varchar(80) not null, lastaccess TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, lastid char(40), lastarticle TIMESTAMP, lastcomment TIMESTAMP, lastmemo TIMESTAMP, lastspam TIMESTAMP, access int, accessday int, article int, comment int, memo int, note TEXT, primary key(ip))");
DBQ("create table if not exists IPSTATGROUP (id varchar(80) not null, ipBase int not null, ipBitMask int  not null, primary key(id))");

DBQ("create table if not exists COUNTER (id varchar(80) not null, counter int not null, lastaccess datetime, primary key(id))");

DBQ("create table if not exists ORACLE (id int not null AUTO_INCREMENT, createon TIMESTAMP not null DEFAULT CURRENT_TIMESTAMP, title TEXT, price TEXT, status int not null, primary key(id))");

}

DBQ("create table if not exists AUTOMAINTAIN_CarType (".
		"typeID int not null AUTO_INCREMENT, ".
		"maker TEXT, ".
		"year	YEAR, ".
		"model TEXT, ".
		"trim TEXT, ".
		"memo	TEXT, ".
		"transmission TEXT, ".
	"primary key(typeID))");
DBQ("create table if not exists AUTOMAINTAIN_Car (".
		"carID int not null AUTO_INCREMENT, ".
		"typeID int REFERENCES AUTOMAINTAIN_CarType.typeID, ".
		"ownerID char(40) REFERENCES USERS.id, ".
		"color	TEXT, ".
		"mileage	int, ".
		"startDate	DATE, ".
		"dealerContact	TEXT, ".
		"VIN	TEXT, ".
		"licensePlate	TEXT, ".
		"pictureURL		TEXT, ".
		"memo	TEXT, ".
	"primary key(carID))");
DBQ("create table if not exists AUTOMAINTAIN_Gas (".
		"carID int REFERENCES AUTOMAINTAIN_Car.carID, ".
		"date	DATETIME, ".
		"mileage	int, ".
		"amount		int, ".
		"octane		int, ".
		"price		DEC(10,2), ".
		"memo	TEXT, ".
	"primary key(carID, mileage))");
DBQ("create table if not exists AUTOMAINTAIN_Stock (".
		"typeID	int REFERENCES AUTOMAINTAIN_CarType.typeID, ".
		"elementID	int NOT NULL AUTO_INCREMENT, ".
		"name	TEXT NOT NULL, ".
		"mileageInterval	int NOT NULL, ". // 0 for infinite
		"dayInterval	int NOT NULL, ". // 0 for infinite
		"expectedPrice	DEC(10,2), ".
		"memo	TEXT, ".
	"primary key(typeID, elementID))");
DBQ("create table if not exists AUTOMAINTAIN_Aftermarket (".
		"carID	int REFERENCES AUTOMAINTAIN_Car.carID, ".
		"aftermarketID	int NOT NULL AUTO_INCREMENT, ".
		"name	TEXT NOT NULL, ".
		"mileageInterval	int NOT NULL, ". // 0 for infinite
		"dayInterval int NOT NULL, ". // 0 for infinite
		"expectedPrice	DEC(10, 2), ".
		"memo	TEXT, ".
	"primary key(carID, aftermarketID))");
DBQ("create table if not exists AUTOMAINTAIN_MaintenanceStock (".
		"carID int REFERENCES AUTOMAINTAIN_Car.carID, ".
		"elementID	int REFERENCES AUTOMAINTAIN_Stock.elementID, ".
		"date	DATE, ".
		"mileage	int, ".
		"price	DEC(10, 2), ".
		"memo	TEXT, ".
	"primary key(carID, elementID, mileage))");
DBQ("create table if not exists AUTOMAINTAIN_MaintenanceAftermarket (".
		"carID int REFERENCES AUTOMAINTAIN_Car.carID, ".
		"aftermarketID	int REFERENCES AUTOMAINTAIN_Aftermarket.aftermarketID, ".
		"date	DATE, ".
		"mileage	int, ".
		"price 	DEC(10, 2), ".
		"memo	TEXT, ".
	"primary key(carID, aftermarketID, mileage))");


// For Cookie Login
DBQ("create table if not exists COOKIELOGIN (".
		"userID		char(40) not null REFERENCES USERS.id, ".
		"cookieKey	char(80) not null, ".
		"passwordEntered	DATETIME, ".
	"primary key(userID, cookieKey))");

DBQ("create table if not exists IMOTICONS (".
		"imoticon	char(8) NOT NULL, ".
		"filename	TEXT, ".
		"imageURL	TEXT, ".
		"description	TEXT, ".
		"creator	char(40) REFERENCES USERS.id, ".
		"authorized int NOT NULL, ".
		"created	DATETIME, ".
	"primary key(imoticon))");
		


?>
Finished.
