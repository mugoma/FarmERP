<?php
    function DisplaySuccessMessage(){
        echo'                
        <div class="alert alert-success" role="alert">
            <strong>Form Submitted Successfully.</strong>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>$(document).ready(function(){
            $(".alert").alert();
        });</script>';
  
    }
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
      };
    function test_int($data) {
        if(!is_int($data+0)){
            return false;
        }
        return true;
        };
    
    function test_date($data) {
        $date_array=explode('-', test_input($data));
        foreach ($date_array as $int) {
            if (!test_int($int)) {
                return false;
            }
        }
        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            return false;
        }
        return true;
        };
    
    function test_time($data) {
        $time_array=explode(':', test_input($data));
        foreach ($time_array as $int) {
            if (!test_int($int)) {
                return false;
            }elseif($int+0 <0){
                return false;
            }
        }
        if(($time_array[0]+0)>24 || $time_array[1]+0>60){
            return false;    }
        return true;
        };


function redirecttologin($current_page){
    //session_start();
    if(empty($_SESSION["loggedin"])){
        $_SESSION["login_redirect"]=true;
        $next=str_replace('php', 'html', $current_page);
        header("location: /auth/login.html?next=$next" );
    };

}
function getUserIpAddr(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
function loguserActivity(){
    global $link;
    $remote_ip=getUserIpAddr();
    $remote_address=$_SERVER['REQUEST_URI'];
    session_start();
    if(isset($_SESSION["loggedin"])){
        $remote_user=$_SESSION['id'];
        pg_query($link, "INSERT INTO auth_users_log(user_id, remote_address, remote_ip) VALUEs($remote_user, '$remote_address', '$remote_ip');");	
    }
}
loguserActivity();
function checkpermissions($id=array(), $name=null){
    global $link;
    if(isset($_SESSION["loggedin"])){

        $user_groups_query=pg_query($link, "SELECT * FROM auth_user_groups WHERE user_id=$_SESSION[id];");
        $user_groups=pg_fetch_all($user_groups_query);
        if (count(array_intersect($id,array_column($user_groups, "group_id"))) ==0 
        && !in_array('1', array_column($user_groups, "group_id"))) {
            header("location: /error.html?err=2");   
        }

    }
}
$session_username="";
if(isset($_SESSION["loggedin"])){
    $session_username=$_SESSION['username'];
}

function array_map_keys($callback, $array /* [, $args ..] */) {
    $args = func_get_args();
    if (! is_callable($callback)) trigger_error("first argument (callback) is not a valid function", E_USER_ERROR);
    if (! is_array($array)) trigger_error("second argument must be an array", E_USER_ERROR);
    $args[1] = array_keys($array);
    // If any additional arguments are not arrays, assume that value is wanted for every $array item.
    // array_map() will pad shorter arrays with Null values
    for ($i=2; $i < count($args); $i++) {
      if (! is_array($args[$i])) {
        $args[$i] = array_fill(0, count($array), $args[$i]);
      }
    }
    return array_combine(call_user_func_array('array_map', $args), $array);
  }
  function to_pg_array($set) {
    settype($set, 'array'); // can be called with a scalar or array
    $result = array();
    foreach ($set as $t) {
        if (is_array($t)) {
            $result[] = to_pg_array($t);
        } else {
            $t = str_replace('"', '\\"', $t); // escape double quote
            if (! is_numeric($t)) // quote only non-numeric values
                $t = '"' . $t . '"';
            $result[] = $t;
        }
    }
    return '{' . implode(",", $result) . '}'; // format
}
?>