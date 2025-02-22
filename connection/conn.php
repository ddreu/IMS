<?php

function con(){
    $servername = "localhost";
    $username = "u468782133_intramurals";
    $password = "Intramurals2024";
    $myDB = "u468782133_intrasports";
    
    $conn = mysqli_connect($servername, $username, $password, $myDB);
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    }else{
        return $conn; 
    }
    

}



?>



