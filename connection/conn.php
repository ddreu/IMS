<?php

function con(){
    $servername = "localhost";
    $username = "root";
    $password = "";
    $myDB = "intramurals";
    
    $conn = mysqli_connect($servername, $username, $password, $myDB);
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    }else{
        return $conn; 
    }
    

}



?>



