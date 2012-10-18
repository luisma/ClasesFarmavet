<?php
/**
 * PHP Class para acceso de usuario (login, register, logout, etc)
 * 
 * EJEMPLO:
 * <?php
 * include('access.class.php');
 * $user = new flexibleAccess();
 * ? >

/**
 * 
 * @param string $dbName
 * @param string $dbHost 
 * @param string $dbUser
 * @param string $dbPass
 * @param string $dbTable
 */

class flexibleAccess{
  /*Settings*/

  var $dbName = 'lnkSystem';
  var $dbHost = 'localhost';
  var $dbPort = 3306;
  var $dbUser = 'permisomedio';
  var $dbPass = '--0med2100xX-XO';
  var $dbTable  = 'usuarios';
  var $sessionVariable = 'userSessionValue';

  var $tbFields = array(
  	'userID'=> 'id', 
  	'login' => 'email',
  	'pass'  => 'pwd',
  	'email' => 'email',
  	'active'=> 'active'
  );

  var $remTime = 2592000;//Un mes

  // Nombre de la cookie que guardaremos en el sistema
  var $remCookieName = 'ckSavePass';

  // Dominio de la cookie, no es necesario
  var $remCookieDomain = '';

  // método de encriptado
  var $passMethod = 'md5';

  // ¿Mostramos errores?
  var $displayErrors = true;

  var $userID;
  var $dbConn;
  var $userData=array();
  /**
   * Class Constructure
   * 
   * @param string $dbConn
   * @param array $settings
   * @return void
   */
  function flexibleAccess($dbConn = '', $settings = '')
  {
	    if ( is_array($settings) ){
		    foreach ( $settings as $k => $v ){
				    if ( !isset( $this->{$k} ) ) die('Propiedad '.$k.' no existe. Comprueba tu configuración.');
				    $this->{$k} = $v;
			}
	    }
	    $this->remCookieDomain = $this->remCookieDomain == '' ? $_SERVER['HTTP_HOST'] : $this->remCookieDomain;
	    $this->dbConn = ($dbConn=='')? mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass):$dbConn;
	    if ( !$this->dbConn ) die(mysql_error($this->dbConn));
	    mysql_select_db($this->dbName, $this->dbConn)or die(mysql_error($this->dbConn));
	    if( !isset( $_SESSION ) ) session_start();
	    if ( !empty($_SESSION[$this->sessionVariable]) )
	    {
		    $this->loadUser( $_SESSION[$this->sessionVariable] );
	    }
	    //¿Existe una cookie?
	    if ( isset($_COOKIE[$this->remCookieName]) && !$this->is_loaded()){
	      
          //echo 'Estás!<br />';
	      $u = unserialize(base64_decode($_COOKIE[$this->remCookieName]));
	      $this->login($u['uname'], $u['password']);
	    }
  }
  
  /**
  	* Login function
  	* @param string $uname
  	* @param string $password
  	* @param bool $loadUser
  	* @return bool
  */
  function login($uname, $password, $remember = false, $loadUser = true)
  {

    	$uname    = $this->escape($uname);
    	$password = $originalPassword = $this->escape($password);
		switch(strtolower($this->passMethod)){
		  case 'sha1':
		  	$password = "SHA1('$password')"; break;
		  case 'md5' :
		  	$password = "MD5('$password')";break;
		  case 'nothing':
		  	$password = "'$password'";
		}
		$res = $this->query("SELECT * FROM `{$this->dbTable}` 
		WHERE `{$this->tbFields['login']}` = '$uname' AND `{$this->tbFields['pass']}` = $password LIMIT 1",__LINE__);
        
		if ( @mysql_num_rows($res) == 0)
			return false;
		if ( $loadUser )
		{
			$this->userData = mysql_fetch_array($res);
			$this->userID = $this->userData[$this->tbFields['userID']];
			$_SESSION[$this->sessionVariable] = $this->userID;
			if ( $remember ){
			  $cookie = base64_encode(serialize(array('uname'=>$uname,'password'=>$originalPassword)));
			  $a = setcookie($this->remCookieName, 
			  $cookie,time()+$this->remTime, '/', $this->remCookieDomain);
			}
		}
		return true;
  }
  
  /**
  	* Logout 
  	* param string $redirectTo
  	* @return bool
  */
  function logout($redirectTo = '')
  {
    setcookie($this->remCookieName, '', time()-3600);
    $_SESSION[$this->sessionVariable] = '';
    $this->userData = '';
    if ( $redirectTo != '' && !headers_sent()){
	   header('Location: '.$redirectTo );
	   exit;//To ensure security
	}
  }
  
  /**
  	* Function to determine if a property is true or false
  	* param string $prop
  	* @return bool
  */
  function is($prop){
  	return $this->get_property($prop)==1?true:false;
  }
  
    /**
  	* Obtener propiedad de un usuario. Añadimos como propiedad el campo en la tabla del usuario
  	* @param string $property
  	* @return string
  */
  function get_property($property)
  {
    if (empty($this->userID)) $this->error('El usuario no se pudo seleccionar', __LINE__);
    if (!isset($this->userData[$property])) $this->error('Propiedad desconocida <b>'.$property.'</b>', __LINE__);
    return $this->userData[$property];
  }
  
  /**
  	* user está activo?
  	* @return bool
  */
  function is_active()
  {
    return $this->userData[$this->tbFields['active']];
  }
  
  /**
   * Está el usuario abierto?
   * @ return bool
   */
  function is_loaded()
  {
    return empty($this->userID) ? false : true;
  }
  
  /**
  	* Activamos al usuario
  	* @return bool
  */
  function activate()
  {
    if (empty($this->userID)) $this->error('No user is loaded', __LINE__);
    if ( $this->is_active()) $this->error('Allready active account', __LINE__);
    $res = $this->query("UPDATE `{$this->dbTable}` SET {$this->tbFields['active']} = 1 
	WHERE `{$this->tbFields['userID']}` = '".$this->escape($this->userID)."' LIMIT 1");
    if (@mysql_affected_rows() == 1)
	{
		$this->userData[$this->tbFields['active']] = true;
		return true;
	}
	return false;
  }
  
  /**
   * Creamos una cuenta de usuario. El array debe tener el formato 'database field' => 'value'
   * @param array $data
   * return int
   */  
  function insertUser($data){
    if (!is_array($data)) $this->error('No es un array', __LINE__);
    switch(strtolower($this->passMethod)){
	  case 'sha1':
	  	$password = "SHA1('".$data[$this->tbFields['pass']]."')"; break;
	  case 'md5' :
	  	$password = "MD5('".$data[$this->tbFields['pass']]."')";break;
	  case 'nothing':
	  	$password = $data[$this->tbFields['pass']];
	}
    foreach ($data as $k => $v ) $data[$k] = "'".$this->escape($v)."'";
    $data[$this->tbFields['pass']] = $password;
    $this->query("INSERT INTO `{$this->dbTable}` (`".implode('`, `', array_keys($data))."`) VALUES (".implode(", ", $data).")");
    return (int)mysql_insert_id($this->dbConn);
  }
  
  /**
   * Creamos password random
   * param int $length
   * param string $chrs
   * return string
   */
  function randomPass($length=10, $chrs = '1234567890qwertyuiopasdfghjklzxcvbnm'){
    for($i = 0; $i < $length; $i++) {
        $pwd .= $chrs{mt_rand(0, strlen($chrs)-1)};
    }
    return $pwd;
  }
  ////////////////////////////////////////////
  // PRIVATE FUNCTIONS
  ////////////////////////////////////////////
  
  /**
  	* SQL query
  	* @access private
  	* @param string $sql
  	* @return string
  */
  function query($sql, $line = 'Uknown')
  {
    //if (defined('DEVELOPMENT_MODE') ) echo '<b>Query to execute: </b>'.$sql.'<br /><b>Line: </b>'.$line.'<br />';
	$res = mysql_db_query($this->dbName, $sql, $this->dbConn);
	if ( !res )
		$this->error(mysql_error($this->dbConn), $line);
	return $res;
  }
  
  /**
  	* Sacamos info del usuario
  	* @access private
  	* @param string $userID
  	* @return bool
  */
  function loadUser($userID)
  {
	$res = $this->query("SELECT * FROM `{$this->dbTable}` WHERE `{$this->tbFields['userID']}` = '".$this->escape($userID)."' LIMIT 1");
    if ( mysql_num_rows($res) == 0 )
    	return false;
    $this->userData = mysql_fetch_array($res);
    $this->userID = $userID;
    $_SESSION[$this->sessionVariable] = $this->userID;
    return true;
  }

  /**
  	* Añadimos addslashes -> más seguridad
  	* @access private
  	* @param string $str
  	* @return string
  */  
  function escape($str) {
    $str = get_magic_quotes_gpc()?stripslashes($str):$str;
    $str = mysql_real_escape_string($str, $this->dbConn);
    return $str;
  }
  
  /**
  	* Errores 
  	* @access private
  	* @param string $error
  	* @param int $line
  	* @param bool $die
  	* @return bool
  */  
  function error($error, $line = '', $die = false) {
    if ( $this->displayErrors )
    	echo '<b>Error: </b>'.$error.'<br /><b>Línea: </b>'.($line==''?'Unknown':$line).'<br />';
    if ($die) exit;
    return false;
  }
  
  function isRegistered($mail){
    $res = $this->query("SELECT {$this->tbFields['userID']},{$this->tbFields['email']} FROM `{$this->dbTable}` WHERE `{$this->tbFields['email']}` = '".$mail."' LIMIT 1");
    if ( mysql_num_rows($res) == 0 ) {
        return false;
    } else {
        return true;
    }
  }
}
?>