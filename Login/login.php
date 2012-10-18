<?

require_once 'access.class.php';
$user = new flexibleAccess();
if ( $_GET['logout'] == 1 ) 
	$user->logout('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
if ( !$user->is_loaded() )
{
	//Login:
	if ( isset($_POST['uname']) && isset($_POST['pwd'])){
	  if ( !$user->login($_POST['uname'],$_POST['pwd'],$_POST['remember'] )){//La clase hace la función de addslashes, aumenta la segudirad
	    echo 'Wrong username and/or password';
	  }else{
	    //Usuario logueado
	    header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
	  }
	}
	echo '<h1>Login</h1>
	<p><form method="post" action="'.$_SERVER['PHP_SELF'].'" />
	 username: <input type="text" name="uname" /><br /><br />
	 password: <input type="password" name="pwd" /><br /><br />
	 Remember me? <input type="checkbox" name="remember" value="1" /><br /><br />
	 <input type="submit" value="login" />
	</form>
	</p>';
}else{
  //User is loaded
  echo "<h1>Hola!</h1>";
  echo '<a href="'.$_SERVER['PHP_SELF'].'?logout=1">Salir</a>';
}
?>