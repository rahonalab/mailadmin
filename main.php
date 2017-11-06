<?php

/* NFOs */
/**
*   Postfix-Mail-Account Management Script, restricted to one domain only (luigi@rahona.be)
*
*   Coders:     - R.T. @ preisroboter.de (tiebel@preisroboter.de)
*                - Jonny007-MKD (1-23-4-5@web.de)
*                - Scott Moody (smoody@motechsystems.com)
*		 - Luigi (luigi@rahona.be)
*								
*   Codestyle:      Quick & Dirty ( & Tidied up) (but still works fine ;) )
*   "Copyrights":   GNU / GPL
*
*   Just change the VARs to yout environment (DB) and
*   restrict the access on this file with e.g. htaccess 
**/

define('db_server','localhost');
	define('db_name','');
	define('db_user','');
	define('db_pwd','');
	$tbl[1] = 'virtual_aliases'; #change to fit yours
	$tbl[2] = 'virtual_users';   #change to fit yours
	$lang = 'en';

			
/* Functions */
function query($sql) {
    global $cid;
    if($sql) return mysql_query($sql,$cid);
    //else die(mysql_error());
}

function fetch($res) {
    if($res) return mysql_fetch_array($res);
    else return false;
}

function connect() {
    global $cid,$db;
    $cid = mysql_connect(db_server,db_user,db_pwd);
    if($cid) $db = mysql_select_db(db_name,$cid);
    else die(mysql_error());
}

function disconnect()   {
    global $cid;
    if(!(mysql_close($cid))) die("Fehler beim Trennen der Verbindung!");
}

/* LocalLanguage */
if ($lang) {
$LLA = array();
if ($lang == 'de') {
	$LLA['de']['hide password'] = 'verstecke Passwort';
	$LLA['de']['show password'] = 'zeige Passwort';
	$LLA['de']['All'] = 'Alle';
	$LLA['de']['Alias Management'] = 'Alias-Verwaltung';
	$LLA['de']['New Alias'] = 'Alias hinzuf&uuml;gen';
	$LLA['de']['Action'] = 'Aktion';
	$LLA['de']['Source'] = 'Quelle';
	$LLA['de']['Destination'] = 'Ziel';
	$LLA['de']['Domain Management'] = 'Domain-Verwaltung';
	$LLA['de']['User Management'] = 'User-Verwaltung';
	$LLA['de']['New Domain'] = 'Domain hinzuf&uuml;gen';
	$LLA['de']['Change this record'] = 'Diesen Eintrag &auml;ndern';
	$LLA['de']['Delete this record'] = 'Diesen Eintrag l&ouml;schen';
	$LLA['de']['Reset'] = 'Zur&uuml;cksetzen';
	$LLA['de']['New User'] = 'User hinzuf&uuml;gen';
	$LLA['de']['Username'] = 'Benutzername';
	$LLA['de']['Password'] = 'Passwort';
	$LLA['de']['ERROR adding Alias'] = 'Fehler beim Hinzuf&uuml;gen eines Aliases';
	$LLA['de']['ERROR adding Domain'] = 'Fehler beim Hinzuf&uuml;gen einer Domain';
	$LLA['de']['ERROR adding User'] = 'Fehler beim Hinzuf&uuml;gen eines Users';
	$LLA['de']['Back'] = 'Zur&uuml;ck';
	$LLA['de']['was added...'] = ' wurde hinzugef&uuml;gt...';
	$LLA['de']['Delete this alias?'] = 'Diesen Alias wirklich l&ouml;schen?';
	$LLA['de']['Delete this domain?'] = 'Diese Domain wirklich l&ouml;schen?';
	$LLA['de']['Delete this user?'] = 'Diesen User wirklich l&ouml;schen?';
	$LLA['de']['was deleted...'] = 'wurde gel&ouml;scht';
	$LLA['de']['was changed...'] = 'wurde ge&auml;ndert...';
	$LLA['de']['No'] = 'Nein';
	$LLA['de']['Yes'] = 'Ja';
	$LLA['de']['Edit Alias'] = 'Alias bearbeiten';
	$LLA['de']['Edit Domain'] = 'Domain bearbeiten';
	$LLA['de']['Edit User'] = 'User bearbeiten';
	$LLA['de']['Target'] = 'Ziel';
	$LLA['de']['Cancel'] = 'Abbrechen';
	$LLA['de']['Speichern'] = 'Save';
	$LLA['de']['Reset'] = 'Zur&uuml;ksetzen';
}
/*
also available:
- Aliases
- Domains
- User
- ID
- Domain
- Name
- Ok
leave one alias out in the language conf and the original one will be used.
*/
}

function echoAl($al) {
    global $LLA, $lang;
    if (!$LLA[$lang][$al]) {echo $al; return false;}
    echo $LLA[$lang][$al];
    return true;
}

function retAl($al) {
    global $LLA, $lang;
    if (!$LLA[$lang][$al]) return $al;
    return $LLA[$lang][$al];
}

/* Initialize */
session_start();
connect();
$adm_name = $_POST['adm_name'];
$adm_pass = $_POST['adm_pass'];
$adm_sql = "SELECT * from virtual_users WHERE email ='$adm_name' AND password = '".md5($adm_pass)."' AND adm='1';";


if (!isset($_SESSION['login'])) {
if (mysql_num_rows(query($adm_sql)) < 1) {
echo $adm_sql;
echo "Sorry, invalid username and/or password";
exit;
}
else {
$_SESSION['login'] = TRUE;
//We limit to our domain
$adm_part = explode("@",$adm_name);
$_SESSION['domain'] = $adm_part[1];
$mydomain = $_SESSION['domain'];
$sel_sql = "SELECT id from virtual_domains where name ='$mydomain'";
$result=query($sel_sql);
while ($row = mysql_fetch_array($result)) {
$_SESSION['sel'] = $row['id'];
}
}
}

$do = $_REQUEST['do'];
$id = $_REQUEST['id'];
$new = $_REQUEST['new'];
$del = $_REQUEST['del'];
$chg = $_REQUEST['chg'];
$ack = $_REQUEST['ack'];
$mydomain = $_SESSION['domain'];
$domain = $_REQUEST['domain'];
$pwd = $_REQUEST['pwd'];
$dest = $_REQUEST['dest'];
$ndomain = $_REQUEST['new_domain'];
$orderby = $_REQUEST['orderby'];
$sel = $_SESSION['sel'];
$usr = $_REQUEST['usr']."@".$mydomain;
$source = $_REQUEST['src']."@".$mydomain;


$fetch_list = mysql_query("SELECT * FROM `virtual_domains` where name ='$mydomain' ORDER BY `name` ASC");

$orderbysql = $orderby ? ' ORDER BY '.$orderby.';' : ';';
$selectsql = $sel ? '  AND virtual_domains.id = '.$sel : '';
## Query Aliases ##
$sql[1] = "SELECT virtual_aliases.id, virtual_aliases.source, virtual_domains.name, virtual_aliases.destination FROM virtual_aliases, virtual_domains WHERE virtual_aliases.domain_id = virtual_domains.id".$selectsql.$orderbysql;

## Query Users ## 
$sql[2] = "SELECT virtual_users.id, virtual_domains.name, virtual_users.email FROM virtual_users, virtual_domains WHERE virtual_users.domain_id = virtual_domains.id".$selectsql.$orderbysql;

## Insert Alias Query ##
if(($domain <> "") && ($dest <> "")) $sql[4] = "INSERT INTO virtual_aliases VALUES (NULL,'$domain','$source','$dest');";

## Insert Domain Query ##
if($ndomain <> "") {
    $sql0 = "SELECT name FROM virtual_domains WHERE name = '$ndomain';";
    $qr = query($sql0);
    if(mysql_num_rows($qr) < 1) $sql[5] = "INSERT INTO virtual_domains VALUES(NULL,'$ndomain');";
}

## Insert User Query ##
if(($domain <> "") && ($usr <> "") && ($pwd <> "")) {
    $sql1 = "SELECT domain_id, email, password FROM virtual_users WHERE domain_id = $domain AND email = '$usr' AND password = '".md5($pwd)."';";
    $qr = query($sql1);	
    if(mysql_num_rows($qr) < 1) $sql[6] = "INSERT INTO virtual_users VALUES(NULL,'$domain','".md5($pwd)."','$usr','0');";
}

## Delete Query ##
if(($del <> "") && ($id <> "") && ($ack == retAl('Yes'))) {
    $sql = "DELETE FROM ".$tbl[$del]." WHERE id = $id;";
    query($sql);
}

## Update Alias Query##
if(($chg == 1) && ($id <> "") && ($domain <> "") && ($dest <> "") && ($ack == retAl('Save'))) {
    $sql = "UPDATE ".$tbl[$chg]." SET domain_id = '".$domain."', source = '".$source."', destination = '".$dest."' WHERE id = ".$id.";";
    query($sql);
}

## Update User Query ##
if(($chg == 2) && ($id <> "") && ($domain <> "") && ($usr <> "") && ($pwd <> "") && ($ack == retAl('Save'))) {
    $sql = "UPDATE ".$tbl[$chg]." SET domain_id ='".$domain."', email ='".$usr."', password = '".md5($pwd)."' WHERE id = ".$id.";";
    query($sql);
}
if($do) $qry = query($sql[$do]);
if($qry) $result = true;
else $result = false;
/* Code */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Amministrazione caselle di posta Rahona.be</title>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
<style type="text/css">
<!--
body {font-family: Verdana;}
#head {font-weight:bold; font-size:1.2em;}
#topLinks {margin:1em;}
#topLinks a {color:#000;}
#topLinks a:hover, #topLinks a:focus {color:#444;}
#listing td, #listing th {border-left:1px solid #000; border-bottom:1px solid #000;}
table {margin:auto}
td {padding:5px}
.table {border:1px solid #000; text-align:center; vertical-align:middle; horizontal-align:center; margin:auto; width:1000px;}
.align-center {border:1px solid #EEE; margin:auto; width:auto; padding:5px; overflow:visible;}
.align-left {text-align:left;}
.align-right {text-align:right;}
input[type=text], input[type=password], select {width:200px; background-color:#EFEFEF; border:1px solid #AAA;}
select {width:203px;}
input[type=text]:focus, input[type=password]:focus, select:focus {background-color:#F9F9F9; border:1px solid #555;}
.rowcolor0 {background-color:#EFEFEF;}
.rowcolor1 {background-color:#E0E0E0;}
th {background-color: #BBBBBB;}
a { color:#555; text-decoration:none;}
img {border:0; margin:2px}
#orderedby {background-color:#666666;	color:#EFEFEF;}
a:hover, a:focus {color:#111; text-decoration:underline;}
#behindPwd {color:#00F; text-decoration:underline; font-size:0.7em; cursor:pointer;}
#behindSource {font-size:0.9em;}
-->
</style>

 <script type="text/javascript">
 <!--
 function onBodyLoad() {
    window.onload = "create();";
    window.setTimeout(create, 1000);
    return true;
 }
 function create() {
    mc = {
     do: '<?php echo $do; ?>',
     sel: '<?php echo $sel; ?>',
     changeSel: function(sel) {
        document.location.href = '<?php echo '?do='.$do.'&sel=\'+sel+\'&orderby='.$orderby ?>';
     },
     togglePwd: function(beh) {
        //var beh = document.getElementById("behindPwd")
        var inp = document.getElementById("pwdInput")
        if (inp.type == 'password') {
					inp.type = 'text';
					beh.innerHTML = '<?php echoAl('hide password'); ?>';
        } else {
					inp.type = 'password';
					beh.innerHTML = '<?php echoAl('show password'); ?>';
        }
    },
    setDomain: function(val) {
			var domain = val.options[val.selectedIndex].innerHTML;
			document.getElementById('behindSource').innerHTML = "@" + domain;
			document.getElementById("fullEmail").value = '@' + domain;
    }
   };
 }
    -->
</script>
</head>
<body onload="onBodyLoad();">
    <div style="text-align: center;">
        <h2>Mail Administration</h2>
        <form id="selection" action=""><div>
            <select name="sel_domain" id="sel_domain" onchange="mc.changeSel(this.value)">
                <option value=""><?php echoAl('All') ?></option>
                <?php
                    while ($dlist = mysql_fetch_array($fetch_list)) { 
                        if ($dlist['id'] == $sel) echo '        <option value="'. $dlist['id'] .'" selected="selected">'. $dlist['name'] .'</option>';
                        else echo '     <option value="'. $dlist['id'] .'">'. $dlist['name'] .'</option>';
                    }
                ?>
            </select>
					</form>
        </div>
        <div id="topLinks">
<a href="<?php echo '?do=1&amp;sel='.$sel ?>" id="aliases_link"><strong><?php echoAl('Aliases') ?></strong></a> | <a href="<?php echo '?do=2&amp;sel='.$sel ?>" id="user_link"><strong><?php echoAl('User') ?></strong></a> | <a href="<?php echo '?do=3&amp;sel='.$sel ?>" id="logout"><strong><?php echoAl('Logout') ?></strong></a>
        </div>
    </div>
    <hr />
    <div>
<?php
switch($new) {
    case 1: //New Alias
        $sql = "SELECT id,name FROM virtual_domains WHERE name = '$mydomain';";
        $qry = query($sql);
        $out = '<div style="text-align: center;"><span id="head">'.retAl('New Alias').'</span><br /><form action="?do=4&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
        $out.= '<table class="align-center"><tr><td class="align-right">'.retAl('Domain').':</td>';
        $out.= '<td class="align-left"><select name="domain" size="1" onchange="mc.setDomain(this)">';
        while($row = fetch($qry)) {
            $out.= '<option value="'.$row[0].'"';
            $out.= ($row[0]==$sel) ? 'selected="selected">' :'>';
            $out.= $row[1].'</option>';
						if ($row[0] == $sel){ $thisDomain = $row[1]; }
        }
        $out.= '</select><input type="hidden" name="fullEmail" id="fullEmail" value="" /></td></tr>';
        $out.= '<tr><td class="align-right">'.retAl('Source').':</td><td class="align-left"><input type="text" name="src" size="15" /><span id="behindSource">@'.$thisDomain.'</span></td></tr>';
        $out.= '<tr><td class="align-right">'.retAl('Destination').':</td><td class="align-left"><input type="text" name="dest" size="20" /></td></tr>';
        $out.= '<tr><td class="align-right">&nbsp;</td><td class="align-left"><input type="submit" value="'.retAl('Submit').'" /></td></tr>';
        $out.= '</table></form></div>';     
    break;
       case 2: //New User
        $sql = "SELECT id,name FROM virtual_domains where name='$mydomain';";
        $qry = query($sql);
        $out = '<div style="text-align: center;"><span id="head">'.retAl('New User').'</span><form action="?do=6&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
        $out.= '<table class="align-center"><tr><td class="align-right">'.retAl('Domain').':</td>';
        $out.= '<td class="align-left"><select name="domain" size="1" onchange="mc.setDomain(this)">';
        while($row = fetch($qry)) {
            $out.= '<option value="'.$row[0].'"';
            $out.= ($row[0]==$sel) ? 'selected="selected">' : '>';
            $out.=$row[1].'</option>';
						if ($row[0] == $sel){ $thisDomain = $row[1]; }
        }
        $out.= '</select><input type="hidden" name="fullEmail" id="fullEmail" value="" /></td></tr>';
        $out.= '<tr><td class="align-right">'.retAl('Username').':</td><td class="align-left"><input type="text" name="usr" size="15" /><span id="behindSource">@'.$thisDomain.'</span></td></tr>';
        $out.= '<tr><td class="align-right">'.retAl('Password').':</td><td class="align-left"><input type="password" id="pwdInput" name="pwd" size="20" /> <span id="behindPwd" onclick="mc.togglePwd(this);">'.retAl('show password').'</span></td></tr>';
        $out.= '<tr><td class="align-right">&nbsp;</td><td class="align-left"><input type="submit" value="'.retAl('Submit').'" /></td></tr>';
        $out.= '</table></form></div>';     
    break;
}

switch($do) {
    case 1: 
        $thname = array(retAl('Action'),retAl('ID'),retAl('Source'),retAl('Domain'),retAl('Destination'));
        $thback = array('','id','source','domain_id','destination');
        $out.= '<div><div style="text-align: center;"><div id="head">'.retAl('Alias Management').'</div><a href="?new=1&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('New Alias').'</a></div><br /><br />';
        $out.='<table width="80%" class="table" rules="all"><tr>';
        for ($a=0;$a<count($thname);$a++) {
            $idordered = ($orderby==$thback[$a]&&!($sel&&$thname[$a]=='Domain')) ? '" id="orderedby' : '';
            $link = (($thback[$a])&&!($orderby==$thback[$a])&&!($sel&&$thname[$a]=='Domain')) ? '<a href="?do='.$do.'&amp;orderby='.$thback[$a].$idordered.'&amp;sel='.$sel.'">' : '';
            $endlink = ($link) ? '</a>' : '';
            $out.='<th class="'.$thname[$a].$idordered.'">'.$link.$thname[$a].$endlink.'</th>';
        }
        $out.='</tr>';
        while($row = fetch($qry)) {
            $i++;
            $rowcolor = ($i%2==0) ? 'rowcolor0' : 'rowcolor1';
            $out.='<tr class="'.$rowcolor.'"><td class="Action"><a href="?chg=1&amp;id='.$row[0].'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" title="'.retAl('Change this record').'"><img src="icons/edit.gif" alt="Edit"/></a><a href="?del=1&amp;id='.$row[0].'&amp;sel='.$sel.'" title="'.retAl('Delete this record').'"><img src="icons/delete.gif" alt="Delete"/></a></td><td class="ID">'.$row[0].'</td><td align="right" class="Source">'.$row[1].'</td><td align="left" class="Domain">'.$row[2].'</td><td align="left" class="Destination">'.$row[3].'</td></tr>';
        }
        $out.='</table></div>';
    break;
       case 2:
        $thname = array(retAl('Action'),retAl('ID'),retAl('User'),retAl('Domain'));
        $thback = array("","id","user","domain_id");
        $out.='<div><div style="text-align: center;"><div id="head">'.retAl('User Management').'</div><a href="?new=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('New User').'</a></div><br /><br />';
        $out.='<table width="60%" class="table" rules="all"><tr>';
        for ($a=0;$a<count($thname);$a++) {
            $idordered = ($orderby==$thback[$a]) ? '" id="orderedby' : '';
            $link = (($thback[$a])&&!($orderby==$thback[$a])) ? '<a href="?do='.$do.'&amp;sel='.$sel.'&amp;orderby='.$thback[$a].$idordered.'">' : '';
            $endlink = ($link) ? '</a>' : '';
            $out.='<th class="'.$thname[$a].$idordered.'">'.$link.$thname[$a].$endlink.'</th>';
        }
        $out.='</tr>';
        while($row = fetch($qry)) {
            $i++;
            $rowcolor = ($i%2==0) ? 'rowcolor0' : 'rowcolor1';
            $out.='<tr class="'.$rowcolor.'"><td><a href="?chg=2&amp;id='.$row[0].'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" title="'.retAl('Change this record').'"><img src="icons/edit.gif" alt="Edit"/></a><a href="?del=2&amp;id='.$row[0].'&amp;sel='.$sel.'" title="'.retAl('Delete this record').'"><img src="icons/delete.gif" alt="Delete"/></a></td><td>'.$row[0].'</td><td>'.$row[2].'</td><td align="left">'.$row[1].'</td></tr>';
        }
        $out.='</table></div>';
    break;
    case 3:
    session_unset();
    session_destroy();
    echo "You have successfully logged out.";
    exit;
    case 4:
        if($result == true) {
            $out = '<div style="text-align: center;"><b>'.retAl('Alias').' '.retAl('was added...').'</b></div>';
            $out .= '<div style="text-align: center;"><a href="?do=1&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=1&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
        } else {
        $out = '<div style="text-align: center;"><h3>'.retAl('ERROR adding Alias').'</h3></div>';
        $out .= '<div style="text-align: center;"><a href="?do=1&amp;sel='.$sel.'&amp;orderby='.$orderby.'" onclick="history.back(); return false;">'.retAl('Back').'</a></div>';
        }
    break;
    case 5:
        if($result == true) {
            $out = '<div style="text-align: center;"><b>'.retAl('Domain').' '.retAl('was added...').'</b></div>';
            $out .= '<div style="text-align: center;"><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
        } else {
            $out = '<div style="text-align: center;"><h3>'.retAl('ERROR adding Domain').'</h3></div>';
            $out .= '<div style="text-align: center;"><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'" onclick="history.back(); return false;">'.retAl('Back').'</a></div>';
        }
    break;
    case 6:
        if($result == true) {
            $out = '<div style="text-align: center;"><b>'.retAl('User').' '.retAl('was added...').'</b></div>';
            $out .= '<div style="text-align: center;"><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
        } else {
            $out = '<div style="text-align: center;"><h3>'.retAl('ERROR adding User').'</h3></div>';
            $out .= '<div style="text-align: center;"><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'" onclick="history.back(); return false;">'.retAl('Back').'</a></div>';
        }
    break;
}

switch($del) {
    case 1:
        if($ack == "") {
            $out = '<div style="text-align: center;"><h3>'.retAl('Delete this alias?').'</h3><form action = "?del='.$del.'&amp;id='.$id.'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
            $out.= '<div><input type="submit" name="ack" value="'.retAl('Yes').'" /> | <input type="submit" name="ack" value="'.retAl('No').'" /></div></form></div>';
        } else if($ack == retAl('Yes')) {
            $out = '<div style="text-align: center;"><b>'.retAl('Alias').' '.retAl('was deleted...').'</b><br /><a href="?do=1">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=1&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
        } else {
            $out = '<div style="text-align: center;"><a href="?do=1&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=1&sel=".$sel.'&orderby='.$orderby."\"},500)\n--></script>";
        }
    break;
    case 2:
        if($ack == "") {
            $out = '<div style="text-align: center;"><h3>'.retAl('Delete this user?').'</h3><form action = "?del='.$del.'&amp;id='.$id.'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
            $out.= '<div><input type="submit" name="ack" value="'.retAl('Yes').'" /> | <input type="submit" name="ack" value="'.retAl('No').'" /></div></form></div>';
        } else if($ack == retAl('Yes')) {
            $out = '<div style="text-align: center;"><b>'.retAl('User').' '.retAl('was deleted...').'</b><br /><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
echo $sql;
        } else {
            $out = '<div style="text-align: center;"><a href="?do=2">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&sel=".$sel.'&orderby='.$orderby."\"},500)\n--></script>";
        }
    break;
}

switch($chg) {
    case 1:
        $sql = "SELECT id,name FROM virtual_domains where name='$mydomain';";
        $qry = query($sql);
        $sql = "SELECT * FROM virtual_aliases WHERE id=".$id.";";
        $qry2 = query($sql);
        $details = fetch($qry2);
        if($ack == "") {
            $out = '<div style="text-align: center;"><span id="head">'.retAl('Edit Alias').':</span><form action = "?chg='.$chg.'&amp;id='.$id.'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
            $out.= '<table class="align-center">';
            $out.= '<tr><td class="align-right">'.retAl('Domain').':</td><td class="align-left"><select name="domain" size="1" onchange="mc.setDomain(this)">';
            while($row = fetch($qry)) {
                $selected = ($row[0] == $details['domain_id']) ? ' selected="selected"' : '';
                $out.= '<option value="'.$row[0].'"'.$selected.'>'.$row[1].'</option>';
								if ($row[0] == $sel){ $thisDomain = $row[1]; }
            }
            $out.= '</select><input type="hidden" name="fullEmail" id="fullEmail" value="" /></td></tr>';
            $out.= '<tr><td class="align-right">'.retAl('Source').':</td><td class="align-left"><input type="text" name="src" size="15" value="'.$details['source'].'" /><span id="behindSource">@'.$thisDomain.'</span></td></tr>';
            $out.= '<tr><td class="align-right"><input type="submit" name="ack" value="'.retAl('Save').'" /></td><td class="align-left"><input type="submit" name="ack" value="'.retAl('Cancel').'" /></td></tr></table></form></div>';
        } else if($ack == retAl('Save')) {
            $out = '<div style="text-align: center;"><b>'.retAl('Alias').' '.('was changed...').'</b><br /><a href="?do=1">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=1&sel=".$sel.'&orderby='.$orderby."\"},100000)\n--></script>";
echo $sql;
        } else {
            $out = '<div style="text-align: center;"><a href="?do=1&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').' </a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=1&sel=".$sel.'&orderby='.$orderby."\"},500)\n--></script>";
        }
    break;
   
    case 2:
        $sql = "SELECT id,name FROM virtual_domains where name='$mydomain';";
        $qry = query($sql);
        $sql = "SELECT * FROM virtual_users WHERE id=".$id.";";
        $qry2 = query($sql);
        $details = fetch($qry2);
        if($ack == "") {
            $out = '<div style="text-align: center;"><span id="head">'.retAl('Edit User').'</span><form action = "?chg='.$chg.'&amp;id='.$id.'&amp;sel='.$sel.'&amp;orderby='.$orderby.'" method="post">';
            $out.= '<table class="align-center">';
            $out.= '<tr><td class="align-right">'.retAl('Domain').':</td><td class="align-left"><select name="domain" size="1" onchange="mc.setDomain(this)">';
            while($row = fetch($qry)) {
                $selected = ($details['domain_id'] == $row[0]) ? ' selected="selected"' : '';
                $out.= '<option value="'.$row[0].'"'.$selected.'>'.$row[1].'</option>';
								if ($row[0] == $sel){ $thisDomain = $row[1]; }
            }

            $out.= '</select><input type="hidden" name="fullEmail" id="fullEmail" value="" /></td></tr>';
            $out.= '<tr><td class="align-right">'.retAl('Name').':</td><td class="align-left"><input type="text" name="usr" size="15" value="'.$details['user'].'" /><span id="behindSource">@'.$thisDomain.'</span></td></tr>';
            $out.= '<tr><td class="align-right">'.retAl('Password').':</td><td class="align-left"><input type="password" id="pwdInput" name="pwd" size="20" value="" onclick="if(this.value==\'******\')this.value=\'\'" /> <span id="behindPwd" onclick="mc.togglePwd(this);">'.retAl('show password').'</span></td></tr>';
            $out.= '<tr><td class="align-right"><input type="submit" name="ack" value="'.retAl('Save').'" /></td><td class="align-left"><input type="submit" name="ack" value="'.retAl('Cancel').'" /></td></tr></table></form></div>';
        } else if($ack == retAl('Save')) {
            $out = '<div style="text-align: center;"><b>'.retAl('User').' '.('was changed...').'</b><br /><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').'</a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&sel=".$sel.'&orderby='.$orderby."\"},1000)\n--></script>";
        } else {
            $out = '<div style="text-align: center;"><a href="?do=2&amp;sel='.$sel.'&amp;orderby='.$orderby.'">'.retAl('Back').' </a></div>';
            $out .= "<script type=\"text/javascript\"><!--\nsetTimeout(function(){document.location = \"?do=2&amp;sel=".$sel.'&amp;orderby='.$orderby."\"},500)\n--></script>";
        }
    break;
}
echo $out;
?>

</div>

</body>
</html>
