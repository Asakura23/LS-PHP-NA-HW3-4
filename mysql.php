<?php

$_FORBIDDEN = <<< EOF
<html><head><title>Error</title></head>
<body>
</body>
	<h1>Ошибка 403 (Forbidden, доступ запрещен)</h1>
	Доступ к файлу/папке ограничен. <a href="javascript:history.go(-1)">Вернитесь</a> на предыдущую страницу.
</html>
EOF;

if( !defined( "IN_DEV" ) ) { print $_FORBIDDEN; die(); }

function mysql_escape_string( $str ) {
   return str_replace( '"', '\"', $str );
}

class TSimpleDB implements ArrayAccess {

   private $connection = null;
   private $query = '';
   private $cprop = null;
   private $result = null;

   public $DEBUG = false;
   public $AUTOCLEAR = false;

   private function error_check( $error_suffix = "" ) {
      if( mysqli_errno( $this->connection ) != 0 ) {
         $e = '#' . mysqli_errno( $this->connection ) . ": " . mysqli_error( $this->connection ) . "\n" . $error_suffix;
         print sprintf( $this->DEBUG ? $this->_DEBUG_ERROR : $this->_ERROR, $e );
         die();
      }
      return true;
   }

   function __construct( $data, $isDebug = false ) {
      $_prop = array(
         'host'		=> "127.0.0.1",
         'port'		=> 3306,
         'user'		=> "root",
         'pass'		=> "",
         'flags'     => 0,
         'dbase'     => "",
         'locale'	=> "utf8",
         'prefix'    => "",
         'suffix'    => "",
      );
      foreach( $data as $key => $value ) $_prop[ $key ] = $value;
      $this->cprop = $_prop;

      $this->DEBUG = $isDebug;

      $this->connection = @mysqli_connect( $_prop['host'] . ':' . $_prop['port'], $_prop['user'], $_prop['pass'], false, $_prop['flags'] );
      if( !$this->connection ) {
         print sprintf( $this->_CONNECT_ERROR, $_prop['user'], $_prop['host'], strlen( $_prop['pass'] ) > 0 ? 'YES' : 'NO' );
         die();
      }

      @mysqli_select_db( $this->connection, $_prop['dbase'] ); $this->error_check();
      @mysqli_set_charset( $this->connection, $_prop['locale'] ); $this->error_check( "locale: {$_prop['locale']}" );

      // Костыль
      @mysqli_query( "SET NAMES " . $_prop['locale'], $this->connection ); $this->error_check();
      @mysqli_query( "SET CHARACTER SET " . $_prop['locale'], $this->connection ); $this->error_check();
      // Костыль
   }

   function __destruct() {
      @mysqli_close( $this->connection );
      unset( $this->result );
      unset( $this->connection );
   }


   function clear() {
      $this->query = "";
   }

   function ExecSQL( $QueryText ) {
      $this->result = @mysqli_query( $this->connection, $QueryText );
      $this->error_check( "Last query:\n" . $QueryText );
   }

   // ------------------------
   // Construct part

   function query( $data, $deferred_exec = false ) {
      if( $this->AUTOCLEAR ) $this->query = '';
      if( strlen( $this->query ) > 0 ) $this->query .= "\n\n";

      if( is_array( $data ) ) {
         if( isset( $data['select'] ) ) $this->query .= $this->construct_select( $data );
         elseif( isset( $data['insert'] ) ) $this->query .= $this->construct_insert( $data );
         elseif( isset( $data['update'] ) ) $this->query .= $this->construct_update( $data );
         else return false;
      }

      if( !$deferred_exec ) {
         $this->result = @mysqli_query( $this->connection, $this->query );
         $this->error_check( "Last query:\n" . $this->query );
      }

      if( strtoupper( substr( $this->query, 0, 6 ) ) == "INSERT" ) {
         return @mysqli_insert_id( $this->connection );
         $this->error_check( "Last query:\n" . $this->query );
      }

      if( strtoupper( substr( $this->query, 0, 6 ) ) == "SELECT" ) {
         // print $this->query;
         $this->prepare_results();
      }

      return true;
   }

   private function construct_where( $data ){
      $ret = '';

      for( $n = 0; $n < count( $data ); $n++ ) {
         if( !is_array( $data[ $n ] ) ) {
            if( in_array( $data[ $n ], array( 'OR', 'AND' ) ) ) $PREFIX = $data[ $n ]; else
               if( preg_match( "#^(.+?)(:AND|:OR|)$#si", $data[ $n ], $matches ) ) {
                  if( strlen( $ret ) > 0 && strlen( $matches[ 2 ] ) == 0 ) $matches[ 2 ] = ':AND';
                  $ret .= ( strlen( $matches[ 2 ] ) > 0 ? substr( $matches[ 2 ], 1 ) . ' ' : '' ) . $matches[ 1 ] . ' ';
               }
         } else {
            $ret .= ( in_array( $data[ $n ][ 0 ], array( 'OR', 'AND' ) ) ? $data[ $n ][ 0 ] . ' ' : '' ) . '(' . $this->construct_where( $data[ $n ] ) . ') ';
         }
      }

      return $ret;
   }

   private function construct_select( $data ) {
      $ret = "SELECT\n\t";

      if( is_array( $data['select'] ) ) {
         for( $n = 0; $n < count( $data['select'] ); $n++ ) $ret .= '`' . $data['select'][ $n ] . '`, ';
         $ret = substr( $ret, 0, -2 ) . "\n";
      } else $ret .= $data['select'] . "\n";

      if( isset( $data['from'] ) ) {
         $ret .= "FROM\n\t";
         if( is_array( $data['from'] ) ) {
            foreach( $data['from'] as $key => $table ) $ret .= "`{$this->cprop['prefix']}{$table}{$this->cprop['suffix']}` {$key}, ";
            $ret = substr( $ret, 0, -2 ) . "\n";
         } else $ret .= '`' . $this->cprop['prefix'] . $data['from'] . $this->cprop['suffix'] . "`\n";
      }

      if( isset( $data['join'] ) ) {
         foreach( $data['join'] as $num => $join ) {
            $ret .= "\n";
            $ret .= "\t\t" . strtoupper( $join['type'] ) . " JOIN\n";
            $ret .= "\t\t\t`{$this->cprop['prefix']}{$join['table']}{$this->cprop['suffix']}`\n";
            if( isset( $join['using'] ) )
               $ret .= "\t\tUSING(`{$join['using']}`)";
            else {
               $ret .= "\t\tON\n\t\t\t";
               if( is_array( $join['on'] ) ) {
                  $ret .= $this->construct_where( $join['on'] );
               } else $ret .= $join['on'];
            }
            $ret .= "\n\n";
         }
      }

      if( isset( $data['where'] ) ) {
         $ret .= "WHERE\n\t";
         if( is_array( $data['where'] ) ) {
            $ret .= $this->construct_where( $data['where'] );
         } else $ret .= $data['where'];
         $ret .= "\n";
      }

      if( isset( $data['order'] ) ) {
         $ret .= "ORDER BY\n\t";
         if( is_array( $data['order'] ) ) {
            foreach( $data['order'] as $field => $direct ) $ret .= "`{$field}` {$direct}, ";
            $ret = substr( $ret, 0, -2 ) . "\n";
         } else $ret .= $data['order'] . "\n";
      }

      if( isset( $data['limit'] ) ) {
         $ret .= "LIMIT\n\t" . $data['limit'] . "\n";
      }

      return $ret . "\n";
   }

   private function construct_insert_values( $data ) {
      $ret = "( ";

      for( $n = 0; $n < count( $data ); $n++ ) {
         if( $data[$n] === null || !isset( $data[$n] ) )
            $ret .= "null, ";
         else
            $ret .= '"' . mysqli_escape_string( $this->connection, $data[$n] ) . '", ';
      }

      $ret = substr( $ret, 0, -2 ) . " )";
      return $ret;
   }

   private function construct_insert( $data ) {
      $ret = "INSERT INTO\n";

      $ret .= "\t`" . $this->cprop['prefix'] . $data['insert'] . $this->cprop['suffix'] . "` ";

      if( isset( $data['fields'] ) ) {
         $ret .= "( ";
         for( $n = 0; $n < count( $data['fields'] ); $n++ ) $ret .= "`{$data['fields'][$n]}`, ";
         $ret = substr( $ret, 0, -2 ) . " )";
      }

      $ret .= "\nVALUES\n";

      if( is_array( $data['values'][0] ) ) {

         for( $n = 0; $n < count( $data['values'] ); $n++ ) $ret .= "\t" . $this->construct_insert_values( $data['values'][$n] ) . ", \n";
         $ret = substr( $ret, 0, -3 );

      } else $ret .= "\t" . $this->construct_insert_values( $data['values'] );

      return $ret . "\n";
   }

   private function construct_update( $data ) {
      $ret = "UPDATE\n";

      $ret .= "\t`" . $this->cprop['prefix'] . $data['update'] . $this->cprop['suffix'] . "`\nSET\n\t";

      foreach( $data['fields'] as $field => $value ) {
         $value = mysqli_escape_string( $this->connection, $value );
         $ret .= "`{$field}` = " . ( strpos( $value, "`" ) ? $value : "\"{$value}\"" ) . ", ";
      }
      $ret = substr( $ret, 0, -2 ) . "\n";

      if( isset( $data['where'] ) ) {
         $ret .= "WHERE\n\t";
         if( is_array( $data['where'] ) ) {
            $ret .= $this->construct_where( $data['where'] );
         } else $ret .= $data['where'];
         $ret .= "\n";
      }

      return $ret . "\n";
   }

   // ------------------------

   private function prepare_results() {
      $this->container = array();
      $count = @mysqli_num_rows( $this->result );
      $this->error_check();
      for( $n = 0; $n < $count; $n++ ) {
         $res = @mysqli_fetch_array( $this->result, MYSQLI_ASSOC );
         $this->error_check();
         if( is_array( $res ) ) {
            //$this->container['result'][ $n ] = array();
            foreach( $res as $key => $value ) {
               $result = addslashes( $value );
               $result = str_replace( "\n", '\n', $result );
               $result = str_replace( "\r", '', $result );
               $this->container[ $n ][ $key ] = $result;
            }
         }
      }
   }



   private function parse_data( $data ) {
      $JSON = '';

      foreach( $data as $key => $value ) {
         $JSON .= '"' . $key . '":';
         if( ! is_array( $value ) ) {
            $JSON .= ( is_numeric( $value ) ? $value : '"' . $value . '"' );
         } else {
            $JSON .= '{' . $this->parse_data( $value ) . '}';
         }
         $JSON .= ',';
      }

      return substr( $JSON, 0, -1 );
   }

   public function asJSON() {
      $fields = array();

      foreach( $this->container as $key => $value ) {
         if( count( $fields ) === 0 ) {
            if( is_array( $value ) ) {
               foreach( $value as $field => $_tmp ) $fields[] = $field;
            } else {
               for( $n = 0; $n < count( $data ); $n++ ) $fields[] = $key;
            }
         }
      }

      return sprintf( '{"count":' . count( $this->container ) . ',"fields":"' . implode( ',', $fields ) . '","data":{%s}}', $this->parse_data( $this->container ) );
   }


   // Support consts
   private $_ERROR = <<< EOF
<html><head><title>Error</title><meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
</body>
	<h1>Ошибка исполнения MySQL</h1>
    При повторении обратитесь к администратору. <a href="javascript:history.go(-1)">Вернуться</a> на предыдущую страницу.%s
</html>
EOF;
   private $_DEBUG_ERROR = <<< EOF
<html><head><title>Error</title><meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
</body>
	<h1>Ошибка исполнения MySQL</h1>
    <textarea style="width: 90%%; height: 350px; padding: 7px; 5px;" readonly>%s</textarea><br /><br />
	При повторении обратитесь к администратору. <a href="javascript:history.go(-1)">Вернуться</a> на предыдущую страницу.
</html>
EOF;
   private $_CONNECT_ERROR = <<< EOF
<html><head><title>Error</title><meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
</body>
	<h1>Ошибка подключения MySQL</h1>
    Пользователю '%s'@'%s' запрещен доступ. Пароль используется: %s. Проверьте настройки доступа!<br /><br />
	При повторении обратитесь к администратору. <a href="javascript:history.go(-1)">Вернуться</a> на предыдущую страницу.
</html>
EOF;

   // ArrayAccess
   private $container = array();
   public function offsetSet($offset, $value){if(is_null($offset)){$this->container[]=$value;}else{$this->container[$offset]=$value;}}
   public function offsetExists($offset){return isset($this->container[$offset]);}
   public function offsetUnset($offset){unset($this->container[$offset]);}
   public function offsetGet($offset){return isset($this->container[$offset]) ? $this->container[$offset] : ($offset=='count' ? count($this->container) : null);}

}

?>
