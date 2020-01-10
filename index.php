<?php 
    define( 'DB_HOST', '127.0.0.1' );
    define( 'DB_USER', 'root' );
    define( 'DB_PASS', '' );
    define( 'DB_DB'  , 'mysql_dust' );
    
    define( 'WEB_URL', 'http://localhost:8080/cs/AutoReset' );
    $mysqli = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_DB
    );
    $table_name = "db_autoreset";

    $edit_fields = array( "hostname"=>"Name", "next_reset"=>"Next Reset" );
    $index_fields = array( "hostname"=>"Name", "last_reset"=>"Last Reset", "next_reset"=>"Next Reset" );
    $search_fields = array( "hostname"=>"Name" );

    $index = isset( $_GET['id'] )? $_GET['id']:0;
    

    function CheckValid()
    {
        return true;
    }
    function log_error( $text )
    {
        ?>
            <br>
            <div class="ui-widget" style='font-size: 1.0em;'>
                <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
                    <p>
                        <span class="ui-icon ui-icon-alert"></span>
                        <?php 
                        echo "<strong>Error:</strong> ".$text;
                        ?>
                    </p>
                </div>
            </div>
            <br>
        <?php
    }

    if( isset($_GET['del']) && $index )
    {
        $query = "DELETE FROM ".$table_name." WHERE `id`=".$index;
    }
    else if( (isset( $_GET['edit'] ) && $index) || isset( $_GET['add'] ) )
    {
        if( CheckValid() )
        {
            $query = isset( $_GET['add'] )? "INSERT INTO ":"UPDATE ";
            $query .= $table_name." SET ";
            foreach( $edit_fields as $field=>$field_value )
            {
                if( isset( $_GET[ $field ] ) && ( strlen($_GET[$field]) > 0 ) )
                {
                    if( $field == 'next_reset' )
                        $query .= $field."='".$mysqli->real_escape_string(strtotime($_GET[$field]))."',";
                    else
                        $query .= $field."='".$mysqli->real_escape_string($_GET[$field])."',";
                }
            }
            
            $query = substr($query, 0, -1);

            if( !isset( $_GET['add'] ) )
                $query .= "WHERE `id`=".$index;
        }
        else if( $index )
            $index_show = $index;
        else
            $index_show = -2;
    }
    else if( isset( $_GET['search'] ) )
    {
        $query_edit = "SELECT * FROM ".$table_name." WHERE ";
        foreach( $search_fields as $search=>$search_value )
        {
            if( isset( $_GET[$search] ) && !empty( $_GET[$search] ))
            {
                $query_edit .= $search." LIKE '%".$_GET[$search]."%' AND ";
            }
        }
        //$query_edit .= "block_type = ".$show_pattern;
        $query_edit = substr( $query_edit, 0, -5 );
    }
    if( isset($query) )
    {
        $mysqli->query($query);
        if( $mysqli->error )
        {
            if(empty($query)) {
                echo "Connect Error (".$mysqli->connect_errno.") ".$mysqli->connect_error;
            } else {
                echo "<br/>".$query."<br/>Error:".$mysqli->error."<br/>";
            }
            return 0;
        }
    }
    
?>
<head>
    <title>Auto Reset</title>
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    
    <style>
        .lefty{
            float: left;
        }
        .moveLeft
        {
            width: 100px;
            left: 4.5%;
        }
        .moveRight
        {
            width: 100px; 
            right: 4.5%; 
            float:right;
            padding: 0;
        }
        .btnSelected
        {
            border: 1px solid #999999;
            background: #c5c5c5;
        }
    </style>
    
    <script>
        $(document).ready( function () {
            $('#table_display')
            .dataTable( {
                responsive: true,
                columnDefs: [
                    { targets: [-1], className: 'dt-body-center' }
                ],
                columnDefs: [
                    { targets: [ -1 ], orderable: false }
                ],
                "order": [0, 'desc'],
                ordering: true,
                bFilter: false,
                lengthChange: false
            } );
        } );
    
        //<div style='width: 4.5%; display: inline-table;'></div>
    </script>
</head>
<body>
    <br>
    
    <a href=<?php echo WEB_URL;?>><button id='HomeButton' class=moveLeft>HOME</button></a> 
    <button id='searchBtn' class=moveLeft>Search</button>
    <script>
        $( '#searchBtn' ).click(function() { 
            $( '#searchDiv' ).dialog( 'open' );
        });
    </script>
    <br>
    <div id='searchDiv' title='Search'>
        <form method=GET>
        <?php
            //echo "<input type=hidden value=".$patterns[$show_pattern]." name=show_pattern>";
            foreach( $search_fields as $field=>$field_value )
            {
                echo "<p>";
                echo $field_value.": <input type='text' name='".$field."' style='float:right; width: 700px;'>";
                echo "</p>";
            }
        ?>  
        
        <input type=hidden value=1 name=search>
        </form>
    </div>
    <script>
        
        $( '#searchDiv' ).dialog({
            autoOpen: false,
            width: 900,
            modal: true, 
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#searchDiv').dialog('close');
                })
            },
            draggable: false,
            buttons: [{ 
                text: 'Search', 
                click: function() { 
                    $(this).find('form').submit(); 
                    $(this).dialog('close'); 
                }
            }]
        });
        $( function() {
            $( "#datepicker" ).datepicker({
                dateFormat: "dd-mm-yy"
            });
        } );
        $( function() {
            $( "#datepicker2" ).datepicker({
                dateFormat: "dd-mm-yy"
            });
        } );
    </script>
    <div id='addDiv' title='Add Server'>
        <form method=GET>
        <?php
            //echo "<input type=hidden value=".$patterns[$show_pattern]." name=show_pattern>";
            foreach( $edit_fields as $field=>$field_value )
            {
                echo "<p>";
                echo $field_value.": <input type='text'  name='".$field."' style='float:right; width: 700px;' ".(($field=='next_reset')? 'id=datepicker':'')." 'value='".((isset($index_show) && $index_show == -2 && isset($_GET[$field]))? $_GET[$field]:'')."'>";
                echo "</p>";
            }
        ?>
        <input type=hidden value=1 name=add>
        
        </form>
    </div>
    <script>
        $( '#addDiv' ).dialog({
            <?php
                if( isset($index_show) && $index_show == -2 )
                    echo "autoOpen: true,";
                else
                    echo "autoOpen: false,";
            ?>
            width: 900,
            modal: true, 
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#addDiv').dialog('close');
                })
            },
            draggable: false,
            buttons: [{ 
                text: 'Add', 
                click: function() { 
                    $(this).find('form').submit(); 
                    $(this).dialog('close'); 
                }
            }]
        });
        
    </script>
    <table id="table_display" class="display" style='width:90%;'>
        <thead>
            <tr>
                <?php
                    foreach( $index_fields as $field => $field_display )
                        echo "<th>".$field_display."</th>";
                ?>
                <th><button id='addButton'>Add</button></th>
                <script>
                    $( '#addButton' ).click(function() { 
                        $( '#addDiv' ).dialog( 'open' );
                    });
                    //<script>
                    $( "button" ).button();
                </script>
            </tr>
        </thead>
        <tbody>
            <?php
                if( isset( $query_edit ) )
                {
                    $query = $query_edit;
                }
                else
                {
                    $query = "SELECT * FROM ".$table_name.";";
                }
                $result = $mysqli->query($query);
                if( $mysqli->error )
                {
                    if(empty($query)) {
                        echo "Connect Error (".$mysqli->connect_errno.") ".$mysqli->connect_error;
                    } else {
                        echo "<br/>".$query."<br/>Error:".$mysqli->error."<br/>";
                    }
                    return 0;
                }
                while( $r = $result->fetch_assoc())
                {
                    echo "<tr id='".$r['id']."'>";
                    foreach( $index_fields as $field=>$field_ass )
                    {
                            echo "<td id='edit1_".$r['id']."'>";
                            if( $field == 'next_reset' || $field == 'last_reset' )
                                echo date( "d-m-Y", $r[$field] );
                            else
                                echo htmlspecialchars($r[$field]);
                            echo "</td>";
                    }
                    echo "<td><button id='edit_".$r['id']."' class=lefty>Edit</button><form method=GET><input type=hidden name=id value='".$r['id']."'><input type=submit name=del value=Delete></form></td>";
                    echo "</tr>";
                    echo "<div id='ban_".$r['id']."' title='Details #".$r['id']."'>";
                    echo "<form action='' method='GET'>";
                    foreach( $edit_fields as $field=>$field_value )
                    {
                        echo "<p>";
                        echo $field_value.": <input type='text' name='".$field."' ".(($field=='next_reset')? 'id=datepicker2':'')."  value='".((isset($index_show) && $index_show == $r['id'] && isset($_GET[$field]))? (($field=='next_reset')? date( "d-m-Y", $_GET[$field] ): htmlspecialchars($_GET[$field])):(($field=='next_reset')? date( "d-m-Y", $r[$field] ): htmlspecialchars($r[$field])) )."' style='float:right; width:700px;'>";
                        echo "</p>";
                    }
                    echo "<input type=hidden name=id value='".$r['id']."'>";
                    echo "<input type=hidden name=edit value=1>";
                    echo "</form></div>";
                    echo "<script>";
                    echo "$( '#ban_".$r['id']."' ).dialog({";
                    if( isset( $index_show ) && $index_show == $r['id'] )
                        echo "autoOpen: true, width: 900,";
                    else
                        echo "autoOpen: false, width: 900,";
                    echo "modal: true, draggable: false,";
                    echo "open: function(){";
                    echo "jQuery('.ui-widget-overlay').bind('click',function(){";
                    echo "jQuery('#ban_".$r['id']."').dialog('close');})},";
                    echo "buttons: [{ text: 'Ok', click: function() { $(this).find('form').submit(); $(this).dialog('close'); }}]";
                    echo "});";
                    echo "$( '#edit_".$r['id']."' ).click(function() { $( '#ban_".$r['id']."' ).dialog( 'open' );});";
                    echo "$( '#edit1_".$r['id']."' ).click(function() { $( '#ban_".$r['id']."' ).dialog( 'open' );});";
                    echo "</script>";
                }
            ?>
        </tbody>

    </table>   
</body>
