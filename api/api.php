<?php

// -------------------------------------------------------------------------

// EXALOT <http://exalot.com> digital language for all agents
// Copyright (C) 2014-2015 Ing. Ernst Johann Peterec (http://ernesto-sun.com)

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// @file    api.php
// @author  Ernesto (eto) <contact@ernesto-sun.com>
// @create  20150112-eto  
// @update  20150618-eto  

// @brief   The one and only entry-point to PHP-version of EXALOT server 

// PHP 5 (untested)
// MySQL 5.6.4  (the millisecond-function for @now does not work before)	

// -------------------------------------------------------------------------


ob_start();

$GLOBALS=array(
    'context'=>array(
      'is-exalot'=>0, 
      'id-st-o'=>0,
      'n-u'=>'',
      'n-u-agent'=>'',
      'id-ses'=>0,
      'id-con'=>0,
      'id-st'=>0,
      'i-xin'=>0),
      'st'=>array(
		'is-public'=>0,
		'is-now'=>0,
		'method'=>'',
		'accept'=>'',
		'charset'=>'',
		'n-lang1'=>'',
		'n-lang2'=>'',
		'n-lang3'=>'',
		'year'=>0,
		'datetime'=>'',
		'i'=>0,
		'c'=>0,
		't'=>''),
    'temp'=>array('i-msg'=>0,
	          'n-list-exp'=>"''",
		  'n-list-def'=>"''",
		  'n-list-new'=>"''",
	          'i-list-exp'=>0,
		  'i-list-def'=>0,
		  'i-list-new'=>0,
		  'i-context'=>0,
		  'i-path'=>0,
		  'i-sys'=>0, // count of expressions like op, default, limited
		  'c-def'=>0,
		  'c-alias'=>0,
		  'c-new'=>0,
		  'c-exp-top'=>0,
		  'c-exp'=>0),
    'login'=>0);

// ---------------------------------------------------
$GLOBALS['debug']=file_exists('./debug.php');  
error_reporting($GLOBALS['debug']?E_ALL:0);
// ---------------------------------------------------


$is_api_call=1;
$GLOBALS['is_api_call']=1;

include 'php/util.php';

// --------------------------------------------------- DB-CONNECTION
include './config.php';

$conn = new mysqli($GLOBALS['conf']['mysql-host'], 
		   $GLOBALS['conf']['mysql-user'],
		   $GLOBALS['conf']['mysql-pwd'], 
		   $GLOBALS['conf']['mysql-db'],
		   $GLOBALS['conf']['mysql-port']);
if ($conn->connect_error) 
{

    msg('error-internal',
	'Sorry, a database error occurred.',
	'db-connect failed: '.$conn->connect_error);
}		

unset($GLOBALS['conf']['mysql-pwd']);   // DB-Password isn't needed any more 
$GLOBALS['pre']=$GLOBALS['conf']['mysql-prefix']; //faster access


$conn->query('SET NAMES \'utf8\'');

include 'php/util_db.php';
dbs::init($conn);


// ---------------------------------------------------


// ---------------------------------------------------
if($GLOBALS['debug'])
{
	if(isset($_GET['daemon']))
	{
		include 'php/daemon.php';

		if($_GET['daemon']=='daemon_create_me')daemon_create_me();
		if($_GET['daemon']=='daemon_create_lang')daemon_create_lang();
		die('Daemon done!');
	}
}
// ---------------------------------------------------

	
// ---------------------------------------------------
include('./php/_auto/global.php');
// ---------------------------------------------------


$header=apache_request_headers();

// HTTP-method

$GLOBALS['st']['method']=trim(strtolower($_SERVER['REQUEST_METHOD']));
switch($GLOBALS['st']['method'])
{
	case 'get':
	case 'post':
		break;
	default:
	      msg('error-http-method','only GET and POST are supported for now');
}


include './php/api_01_header.php';

include './php/api_02_param.php';

include './php/api_03_login.php';


if(!$GLOBALS['login'])$con=0; // overwrite conversation if not loggedin 

$max_c=$GLOBALS['context']['u-level']['max-sub-c'];
if($GLOBALS['st']['c']>$max_c)$GLOBALS['st']['c']=$max_c;
if($GLOBALS['st']['c']<1)$GLOBALS['st']['c']=$GLOBALS['context']['u-level']['default-sub-c'];

if($GLOBALS['st']['i']<1)$GLOBALS['st']['i']=0;  // index smaller 0 does not make sense


if(strlen($GLOBALS['st']['t']))
{
  // validate theme
}
else
{
  $GLOBALS['st']['t']=$GLOBALS['conf']['default-theme'][$GLOBALS['st']['accept']]; 
}

include './php/api_04_syntax.php';


$x=validDB($x); // !! this is important here to make cache search valid SQL, must be after parsing
$x_len=strlen($x); // !! and renewal of lenth 

include './php/api_05_cache.php';

include './php/api_06_session.php';

if(!$GLOBALS['login'])
{
   $con=$_SESSION['id-con-last'];
   $GLOBALS['context']['n-u']='';
}

include './php/api_07_semantic.php';

//include './php/api_08_con.php';

//include './php/api_09_st.php';


// TODO: Disable that...
if($GLOBALS['debug']&&false) 
{
  dbs::exec('DELETE FROM exa_e WHERE id>1');
  dbs::exec('DELETE FROM exa_sub WHERE id>1');
  dbs::exec('TRUNCATE exa_sup');
  dbs::exec('TRUNCATE exa_subl');
  dbs::exec('TRUNCATE exa_tx');
  dbs::exec('TRUNCATE exa_x');
}
  
//include './php/api_09_x.php';
                        
//include './php/api_10_manifest.php';
      
      
      
debug_dump_all();
     
      
						
// -----------------------------------------------------------
// Writing result to user
// -----------------------------------------------------------


if(isset($_GET['jsoncallback']))
{
  echo $_GET['jsoncallback'], '(',$resultStr,')';
}
else
{
  echo $resultStr;
}


http_response_code($_RES[0]['state']);

header('Content-Length:'. ob_get_length());  // security not to have cut the output
ob_end_flush();


exit;


?>
