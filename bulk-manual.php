<?php

$author_id = 8990;
$root = $_SERVER['DOCUMENT_ROOT'];//.'/wordpress';

if ( !is_file( $root . '/wp-config.php' ) ) {
    die( 'wrong root' );
}

include( $root . '/wp-config.php' );

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_errno) {
	 die( 'Connect failed: \n' . print_r( $mysqli->connect_error, true ) );
}
$mysqli->set_charset( 'utf8' );

$select = 'SELECT `ID` FROM `'.$table_prefix.'posts` WHERE `post_status` = "publish" AND ( `post_type` = "post" OR `post_type` = "page" )';

$select = queryToArray( $select );

/*
echo '<pre>';
print_r( $select );
echo '</pre>';
//*/

foreach( $select as $v ) {
    $insert[] = 'INSERT INTO `'.$table_prefix.'postmeta`
        ( `post_id`, `meta_key`, `meta_value` )
        VALUES
        ( "'.$v['ID'].'", "fcpab_ymyl-show", "a:1:{i:0;s:1:\"1\";}" );';

    $insert[] = 'INSERT INTO `'.$table_prefix.'postmeta`
        ( `post_id`, `meta_key`, `meta_value` )
        VALUES
        ( "'.$v['ID'].'", "fcpab_show-authors", "a:1:{i:0;s:4:\"'.$author_id.'\";}" );';
}

/*
echo '<pre>';
print_r( $insert );
echo '</pre>';
//*/

//*
echo '<pre>';
echo implode( "\n", $insert );
echo '</pre>';
//*/

foreach ( $insert as $v ) {
    $result = $mysqli->query( $v );
}

print_r( $mysqli->error );

function queryToArray( $sql, $id = '' ) {
    global $mysqli;

    $result = $mysqli->query( $sql );

    if ( $result->num_rows > 0 ) {
        
        while( $row = $result->fetch_assoc() ) {
            if ( $id )
                $return[$row[$id]] = $row;
            else
                $return[] = $row;
        }

        return $return;
    }
    
    return false;
}
