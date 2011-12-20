<?php
//CONSTANTS
define('SITENAME','Yet another b.php blog');
//ONLY CHANGE THESE IF YOU KNOW WHAT YOU'RE DOING
define('DATAPATH','b/');
define('KEY','key');
define('VALUE','value');
define('B','__b');
define('T_HEADER','template_header');
define('T_FOOTER','template_footer');
define('T_POST','template_post');
define('T_ADMIN','template_addpost');
define('D_POSTTITLE','posttitle');
define('D_POSTCONTENT','postcontent');
define('D_POSTDATE','postdate');
define('D_POSTID','postid');
//INSTALL STUFF
if(get_kvp(B,'firstuse')===false){
	echo 'Configuring b.php... This might take a while.';
	if(!record_exists(''))if(!mkdir(DATAPATH))die('Can not create database. Create directory "'.DATAPATH.'" and make it writeable.');
	create_record(B);
	create_index(D_POSTDATE,D_POSTDATE);
	set_kvp(B,T_HEADER, <<< 'EOD'
	<!DOCTYPE html>
	<html>
	<head>
	<meta charset="utf-8" />
	<title>{{SITENAME}}</title></head>
	<body>
EOD
	);
	set_kvp(B,T_FOOTER, <<< 'EOD'
	</body>
	</html>
EOD
	);
	set_kvp(B,T_POST, <<< 'EOD'
	<hr />
	<b>{{POSTTITLE}}</b> <i>{{POSTDATE}}</i> <a href="?edit={{POSTID}}">edit</a><br />
	{{POSTCONTENT}}
	<hr />
EOD
	);
	set_kvp(B,T_ADMIN, <<< 'EOD'
	<div>
	<form action="" method="post">
	<input name="posttitle" type="text" value="{{POSTTITLE}}"><br />
	<textarea name="postcontent" rows="10" cols="70">{{POSTCONTENT}}</textarea><br />
	<input name="postid" type="hidden" value="{{POSTID}}" />
	<input name="submitpost" type="submit" value="commit" />
	</form>
EOD
	);
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
function get_kvp($r,$k){
	$p=DATAPATH.sanitize_key($r).'/'.sanitize_key($k);
	return file_exists($p)?file_get_contents($p):false;
}
function record_exists($p){
	return file_exists(DATAPATH.$p)&&is_dir(DATAPATH.$p);
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
	$t=get_kvp(B,$f[0]);
	for($i=1;$i<$n;$i+=2){
		$t=str_replace('{{'.$f[$i].'}}',$f[$i+1],$t);
	}
	return $t;
}
function tpl_set($t,$w,$r){
	return str_replace('{{'.$w.'}}',$r,$t);	
}
//DO STUFF
if(isset($_POST['submitpost'])){//POST ACTIONS
	$r=0;
	if(empty($_POST[D_POSTID])){
		$r=create_record(uniqid());
		set_kvp($r,D_POSTDATE,time());
	}else{
		$r=sanitize_key($_POST[D_POSTID]);
		if(!record_exists($r))die("dum");
	}
	set_kvp($r,D_POSTTITLE,$_POST[D_POSTTITLE]);
	set_kvp($r,D_POSTCONTENT,$_POST[D_POSTCONTENT]);
	create_index(D_POSTDATE,D_POSTDATE);
}
if(isset($_GET['rbindex']))create_index(D_POSTDATE,D_POSTDATE);
//BB STUFF
function parsebb($t){
	$t = preg_replace('/\[b\](.+?)\[\/b\]/is','<b>\1<\/b>',$t);
	$t = preg_replace('/\[center\](.+?)\[\/center\]/is','<center>\1<\/center>',$t);
	$t = preg_replace('/\[i\](.+?)\[\/i\]/is','<i>\1<\/i>',$t);
	$t = preg_replace('/\[img\](.+?)\[\/img\]/is','<img src="\1" alt="\1" />',$t);
	$t = preg_replace('/\[url\=(.+?)\](.+?)\[\/url\]/is','<a href="\1">\2</a>',$t);
	$t = preg_replace('/\[url\](.+?)\[\/url\]/is','<a href="\1">\1</a>',$t);
	$t = preg_replace('/\[code\](.+?)\[\/code\]/is','<pre>\1</pre>',$t);
	return $t;
}
//BLOGGY STUFF
echo tpl(T_HEADER,'SITENAME',SITENAME);
if(isset($_GET['edit'])){
	$id=sanitize_key($_GET['edit']);
	if(!record_exists($id))die("dum");
	$tpl=tpl(T_ADMIN,'POSTTITLE',get_kvp($id,D_POSTTITLE),'POSTCONTENT',get_kvp($id,D_POSTCONTENT),'POSTID',$id);
}else{
	$tpl=tpl(T_ADMIN,'POSTTITLE','','POSTCONTENT','','POSTID','');
}
echo tpl_set($tpl,'SITENAME',SITENAME);

$p=get_index(D_POSTDATE);
uasort($p,function($a,$b){if($a[VALUE]==$b[VALUE])return 0;return $a[VALUE]<$b[VALUE];});
foreach($p as $m){
	echo tpl(T_POST,'POSTID',$m[KEY],'POSTTITLE',get_kvp($m[KEY],D_POSTTITLE),'POSTCONTENT',parsebb(nl2br(get_kvp($m[KEY],D_POSTCONTENT))),'POSTDATE',date('d.m.Y',$m[VALUE]));
}
echo tpl(T_FOOTER);
echo memory_get_peak_usage()/1024;
?>
