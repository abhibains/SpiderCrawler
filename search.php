<?php
    $hyperlist = array();

    $search =$_POST['search-key'];
    //$search ="CSS";
    $lines = file('http://localhost/cp476/a4/q3/url_keyword.txt');
    $lines = preg_grep("/$search/", $lines);
    foreach($lines as $name){
      //  echo "$name";
        hypertext($name);
    }

    function hypertext($text){
        global $hyperlist;
        $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i'; 
        $string = preg_replace($url, '<a href="$0">$0</a>',$text);
        $hyperlist []= $string;
      
    }
    //print_r($hyperlist);
    
    foreach ($hyperlist as $URL){
        echo'<tr>'; 
        echo'<td>'. $URL."</td><br>";
        echo'<tr>';
    }
?>