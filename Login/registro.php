<?

if (!empty($_POST['username'])){

  require_once 'access.class.php';
  $user = new flexibleAccess();
  $data = array(
  	'email' => $_POST['username'],
  	'pwd' => $_POST['pwd'],
  	'active' => 1
  );
  
  if ($user->isRegistered($_POST['username'])){
    echo "Usuario registrado";
  } else {
        $userID = $user->insertUser($data);
        if ($userID==0)
            echo 'Usuario no creado, hubo problemas...';
        else
  	         echo 'El usuario se ha creado con el id '.$userID; 
  }
}


echo '<h1>Register</h1>
	<p><form method="post" action="'.$_SERVER['PHP_SELF'].'" />
	 Mail: <input type="text" name="username" /><br /><br />
	 Password: <input type="password" name="pwd" /><br /><br />
	 <input type="submit" value="Register user" />
	</form>
	</p>';

?>