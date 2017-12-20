<?php

//Check users session and force login.
$login_error = check_session();

info();

if(isset($_GET['page']) && $_GET['page']=='login'){
	login($login_error);
}

function info(){

	include('config.php');

	//echo '$_SESSION Array';
	//echo "<pre>";
	//print_r($_SESSION);
	//echo "</pre>";

	if(!loggedin()){
		echo "User: NONE <br/>\n";
		echo "<a href=\"?page=login\">Login</a>";
	}else{

		echo "User: " . $_SESSION[$site]['user'] . "<br/>\n";
		echo "<a href=\"?page=logout\">Logout</a>";
	}

	echo "<br/>";
	echo "<br/>";
} 

function loggedin(){

	include('config.php');

	if(isset($_SESSION[$site]['user']) && !$_SESSION[$site]['user']==''){
		return true;
	}else{
		return false;
	}
}


function check_session(){

	//Force SSL
	if($_SERVER["HTTPS"] != "on") {
		$newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		header("Location: $newurl");
		exit();
	}

	$login_error=false;
	include('config.php');

	if(isset($_GET['page']) && $_GET['page']=='logout'){
		logout();
	}

	session_start();

	if ((!loggedin()) && ($_GET['page'] != 'login')) {

		//Redirect to Login Page
		//		$newurl = "https://" . $_SERVER["SERVER_NAME"] . parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) . '?page=login';
		//		$_SESSION['request_uri']=$_SERVER['REQUEST_URI'];
		//		header("Location: $newurl");
		//		exit();

	}else{
		if(isset($_POST['username']) && isset($_POST['pass'])){

			//escape username and password 
			$username = escapeshellcmd(trim($_POST['username']));
			$pass = escapeshellcmd($_POST['pass']);
			// Password is not cleaned
			$pass = $_POST['pass'];

			//Validate username format
			if(preg_match('/^[a-zA-F0-9]+$/',$username)!=1){$login_error=true;}

			if($ldap_auth = ldap_login($username, $pass)){
				setup_session_and_redirect($ldap_auth);
			}else{
				//echo "Failed";
				$login_error=true;
			}
		}
	}
	return $login_error; 
}

function setup_session_and_redirect($auth){
	include('config.php');
	//session_start();
	$_SESSION[$site]['user'] = $auth['user'];
	$_SESSION[$site]['userid'] = $auth['userid'];
	//$_SESSION[$site]['role'] = $auth['role'];

	if(isset($_SESSION['request_uri'])){
		$newurl = $_SESSION['request_uri'];
	}else{
		$newurl = "https://" . $_SERVER["SERVER_NAME"] . parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
	}
	header("Location: $newurl");
	exit();
}



function ldap_login( $user, $password){

	//if(isset($_POST['user']) && $_POST['user'] != '' && isset($_POST['pass']) && $_POST['pass'] != '' )
	if(isset($user) && $user != '' && isset($password) && $password != '' ){

		//load from config!!!!!
		include('config.php');

		$ldappass=$password;

		$auth=array();
		$pass=false;

		$ldaprdn  = $user.'@iastate.edu';     // ldap rdn or dn

		// connect to ldap server
		$ds = ldap_connect($ldaphost) 
			or die("Could not connect to LDAP server.");
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

		if ($ds) {

			// binding to ldap server
			$ldapbind = @ldap_bind($ds, $ldaprdn, $ldappass);

			// verify binding
			if ($r=$ldapbind) {
				//echo "LDAP bind successful...";

				$sr=ldap_search($ds, $dn, "cn=$user");
				$info = ldap_get_entries($ds, $sr);
				if ($info[0]['samaccountname'][0] == $user){

					$pass=true;
					$auth['user']= $info[0]['samaccountname'][0];
					$auth['userid']= $info[0]['uidnumber'][0];;
					$auth['pass']= true;

					//echo "<pre>";
					//print_r($info[0]);
					//echo "</pre>";

					return $auth;

				}
				//} else {
				//echo "LDAP bind failed...";
			}
		}

		if ($pass){
			//echo 'PASS';
			return true;
		} else {
			//echo 'FAIL';
			return false;
		}
	}
	return false;

}

function logout(){
	// Initialize the session.
	// If you are using session_name("something"), don't forget it now!
	session_start();

	// Unset all of the session variables.
	$_SESSION = array();

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time()-42000, '/');
	}

	// Finally, destroy the session.
	session_destroy();

}

function login($login_error){
?>
    <form action="?page=login" method="post">
	Enter your Net-ID and password<br />
	<?php if(isset($login_error) && $login_error==true){echo "<br><b style=\"color: #FF0000;\">Invalid username or password</b><br/>\n";} ?>
      <p>Net-ID<br />
      <input name="username" />

	<p>Password<br />
	<input name="pass" type="password" /></p>
      <p>
      <input type="submit" value="Login" /></p>
    </form>

<?php
}

?>