<?php

error_reporting(E_ALL | E_STRICT);

require_once('config.php');
session_start();
//INSTALL STUFF
if(get_kvp(B,'firstuse')===false){
	if(!record_exists(''))if(!mkdir(DATAPATH))die('Can\'t create database. Create directory "'.DATAPATH.'" and make it writeable.');
	create_record(B);
	create_index(D_POSTDATE,D_POSTDATE);
	set_kvp(B,'firstuse',1);
}
//DB STUFF
function create_record($r){
	$r=sanitize_key($r);
	if(!record_exists($r))mkdir(DATAPATH.$r);
	return $r;
}
function set_kvp($r,$k,$v){
	file_put_contents(DATAPATH.sanitize_key($r).'/'.sanitize_key($k),$v);
}
function get_tpl($tpl) {
	$p=DATAPATH.'template/'.$tpl;
	return file_exists($p)?file_get_contents($p):false;
}
function get_kvp($r,$k){
	$p=DATAPATH.sanitize_key($r).'/'.sanitize_key($k);
	return file_exists($p)?file_get_contents($p):false;
}
function delete_kvp($r,$kvp){
	unlink(DATAPATH.sanitize_key($r).'/'.sanitize_key($kvp));
}
function record_exists($p){
	$p=sanitize_key($p);
	return file_exists(DATAPATH.$p)&&is_dir(DATAPATH.$p);
}
function record_delete($r){
	$r=sanitize_key($r);
	if(record_exists($r)){
		$h=opendir(DATAPATH.$r);
		for($i=0;($e=readdir($h))!==false;$i++){
			if ($e!='.'&&$e!='..'){
				unlink(DATAPATH.$r.'/'.$e);
			}
		}
		closedir($h);
		rmdir(DATAPATH.$r);
	}
}
function get_keys($r){
	$s=scandir(DATAPATH.$r);
	return array_values(array_filter($s,function($v){return $v!='.'&&$v!='..';}));
}
function sanitize_key($k){
	return preg_replace('/[^A-Za-z0-9_]/','',$k);
}
function create_index($n,$k){
	$d=array();
	$h=opendir(DATAPATH);
	for($i=0;($e=readdir($h))!==false;$i++){
		if ($e!='.'&&$e!='..'&&$e!=B){
			$d[$i][KEY]=$e;
			$d[$i][VALUE]=get_kvp($e,$k);
			if($d[$i][VALUE]===false)array_pop($d);
		}
	}
	closedir($h);
	set_kvp(B,'index_'.$n,serialize($d));
}
function get_index($n){
	return unserialize(get_kvp(B,'index_'.$n));
}
//TEMPLATE STUFF
function tpl(){
	$f=func_get_args();
	$n=sizeof($f)-1;
	$t=get_tpl($f[0]);
	for($i=1;$i<$n;$i+=2){
		$t=str_replace('{{'.$f[$i].'}}',$f[$i+1],$t);
	}
	return $t;
}
function tpl_set($t,$w,$r){
	return str_replace('{{'.$w.'}}',$r,$t);	
}
function fail(){
	echo tpl(T_HEADER,'SITENAME',SITENAME,'PAGEHOME',T_HEADER,'SELF',$_SERVER['SCRIPT_NAME']);
	echo tpl(T_FAIL);
	echo tpl(T_FOOTER);
	die();
}
//ADMIN STUFF
function rmain(){
	header('Location: '.$_SERVER['SCRIPT_NAME']);
	die();
}
if(isset($_POST['login'])){
	if($_POST['username']===USERNAME && $_POST['password']===PASSWORD)$_SESSION['loggedin']=true;
	rmain();
}
if(isset($_GET['logout'])){
	session_destroy();
	rmain();
}
if(@$_SESSION['loggedin']===true){
	if(isset($_POST['submitpost'])){//POST ACTIONS
		$r=0;
		if(empty($_POST[D_POSTID])){
			$r=create_record(uniqid());
			set_kvp($r,D_POSTDATE,time());
		}else{
			if(!record_exists($_POST[D_POSTID]))fail();
			$r=$_POST[D_POSTID];
		}
		set_kvp($r,D_POSTTITLE,$_POST[D_POSTTITLE]);
		set_kvp($r,D_POSTCONTENT,$_POST[D_POSTCONTENT]);
		create_index(D_POSTDATE,D_POSTDATE);
	}
	if(isset($_GET['delete'])){
		record_delete($_GET['delete']);
		record_delete($_GET['delete'].D_COMMENT);
		create_index(D_POSTDATE,D_POSTDATE);
	}
	if(isset($_GET['dc'])){
		$cfl=$_GET['postid'].D_COMMENT;
		if(!record_exists($cfl))fail();
		delete_kvp($cfl,$_GET['cid'].'_'.D_NAME);
		delete_kvp($cfl,$_GET['cid'].'_'.D_COMMENT);
		delete_kvp($cfl,$_GET['cid'].'_'.D_POSTDATE);
	}
	if(isset($_GET['rbindex']))create_index(D_POSTDATE,D_POSTDATE);
}
//ADD COMMENT
if(isset($_POST['submitcmnt'])){
	if($_REQUEST['me'] !== 'IchBinHomoseksuell') fail();
	if(empty($_POST[D_COMMENT])||empty($_POST[D_NAME]))fail();
	$r=$_POST[D_POSTID].D_COMMENT;
	if(!record_exists($_POST[D_POSTID]))fail();
	if(!record_exists($r))create_record($r);
	$u=uniqid();
	set_kvp($r,$u.'_'.D_POSTDATE,time());
	set_kvp($r,$u.'_'.D_NAME,$_POST[D_NAME]);
	set_kvp($r,$u.'_'.D_COMMENT,$_POST[D_COMMENT]);
}
//BB STUFF
function parsebb($t){
	$t = preg_replace('/\[b\](.+?)\[\/b\]/is','<b>\1</b>',$t);
	$t = preg_replace('/\[center\](.+?)\[\/center\]/is','<center>\1</center>',$t);
	$t = preg_replace('/\[i\](.+?)\[\/i\]/is','<i>\1</i>',$t);
	$t = preg_replace('/\[img\](.+?)\[\/img\]/is','<img src="\1" alt="\1" />',$t);
	$t = preg_replace('/\[url\=(.+?)\](.+?)\[\/url\]/is','<a href="\1">\2</a>',$t);
	$t = preg_replace('/\[url\](.+?)\[\/url\]/is','<a href="\1">\1</a>',$t);
	$t = preg_replace('/\[code\](.+?)\[\/code\]/is','<pre>\1</pre>',$t);
	return $t;
}
//BLOGGY STUFF
$p=get_index(D_POSTDATE);
//SEARCH
if(!empty($_GET['s'])){
	$s=explode(' ',$_GET['s']);
	foreach($p as $k => $m){
		$t=strtolower(get_kvp($m[KEY],D_POSTTITLE));
		$c=strtolower(parsebb(nl2br(get_kvp($m[KEY],D_POSTCONTENT))));
		$f=true;
		for($i=0;$i<sizeof($s);$i++){
			if(strpos($c,strtolower($s[$i]))===false && strpos($t,strtolower($s[$i]))===false){
				$f=false;
				break;
			}
		}
		if(!$f)unset($p[$k]);
	}
}
$sp=sizeof($p);
$o=0;
uasort($p,function($a,$b){if($a[VALUE]==$b[VALUE])return 0;return $a[VALUE]<$b[VALUE];});
if(isset($_GET['rss'])){
	$p=@array_slice($p,0,POSTSPERPAGE);
	echo tpl(RSS_HEADER,'SITENAME',SITENAME,'SITEURL',PAGEHOME,'SELF',$_SERVER['SCRIPT_NAME']);
	foreach($p as $m){
		echo tpl(RSS_ITEM,'POSTTITLE',get_kvp($m[KEY],D_POSTTITLE),'POSTCONTENT',parsebb(nl2br(get_kvp($m[KEY],D_POSTCONTENT))),'LINK',PAGEHOME.'?a='.$m[KEY],'DATE',date('D, d M Y H:i:s T',$m[VALUE]));
	}
	echo tpl(RSS_FOOTER);
	die();
	
}
echo tpl(T_HEADER,'SITENAME',SITENAME,'PAGEHOME',PAGEHOME,'SELF',$_SERVER['SCRIPT_NAME']);
if(isset($_GET['login'])){
	echo tpl(T_ADMINLOGIN);
	die();
}
if(@$_SESSION['loggedin']===true){
	if(isset($_GET['edit'])){
		if(!record_exists($_GET['edit']))fail();
		echo tpl(T_ADMIN,'POSTTITLE',get_kvp($_GET['edit'],D_POSTTITLE),'POSTCONTENT',get_kvp($_GET['edit'],D_POSTCONTENT),'POSTID',$_GET['edit'],'SELF',$_SERVER['SCRIPT_NAME']);
	}else{
		echo tpl(T_ADMIN,'POSTTITLE','','POSTCONTENT','','POSTID','','SELF',$_SERVER['SCRIPT_NAME']);
	}
}
if(isset($_GET['a']) && record_exists($_GET['a'])){
	$o=1;
	$p=array(array(VALUE => get_kvp($_GET['a'],D_POSTDATE), KEY => $_GET['a']));
}
$p=@array_slice($p,$_GET['skip'],POSTSPERPAGE);
foreach($p as $m){
	echo tpl(T_POST,'POSTID',$m[KEY],'POSTTITLE',get_kvp($m[KEY],D_POSTTITLE),'POSTCONTENT',parsebb(nl2br(get_kvp($m[KEY],D_POSTCONTENT))),'POSTDATE',date('d.m.Y H:i:s',$m[VALUE]));
	if($o){
		$r=$m[KEY].D_COMMENT;
		if(record_exists($r)){
			$c=get_keys($r);
			$c=array_unique(array_map(function($e){$e=explode('_',$e);return $e[0];},$c));
			foreach($c as $d){
				echo tpl(T_CMNT,'NAME',htmlspecialchars(get_kvp($r,$d.'_'.D_NAME)),'COMMENT',nl2br(htmlspecialchars(get_kvp($r,$d.'_'.D_COMMENT))),'POSTID',$m[KEY],'CID',$d);
			}
		}
		echo tpl(T_CMNTFRM,'POSTID',$m[KEY]);
		break;
	}
}
echo tpl(T_NAV,'NEXT',(@$_GET['skip']>0?@$_GET['skip']-POSTSPERPAGE:0).'&s='.@urlencode($_GET['s']),'PREV',(@$_GET['skip']+POSTSPERPAGE<$sp?@$_GET['skip']+POSTSPERPAGE:@(int)$_GET['skip']).'&s='.@urlencode($_GET['s']));
echo tpl(T_FOOTER,'USED',intval(memory_get_usage()/1024));
?>