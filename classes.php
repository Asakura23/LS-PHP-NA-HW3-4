<?php
if( !defined( "IN_DEV" ) ) { print $_FORBIDDEN; die(); }

class TUser {
    private $id = -1; // id пользователя
    private $name = "Guest"; // Отображаемое имя
    private $group = 3; // Группа
    private $mail = ""; // EMail
    private $rdate = 0; // Дата регистрации в UnixTime

    private $db = null;

    function __construct( &$db, $user_name ) {
        $this->db = $db;
        // Если в имени пользователя есть недопустимые символы - вылетаем и считаем гостем
        if( preg_match( '#[^A-z,a-z,0-9,_]#si', $user_name ) || strlen( $user_name ) < 4 ) return;
        $this->db->clear();
        $this->db->query( array(
            'select'    => "u.*, g.*",
            'from'      => array( 'u' => "users", 'g' => "groups" ),
            'where'     => array( '`u_name` = "' . $user_name . '"', '`u_group` = `g_id`' ),
        ) );
        // В результате может быть только одна строка
        if( $this->db['count'] != 1 ) return;

        // Заполняем данные пользователя
        $this->id = $db[0]['u_id'];
        $this->name = $db[0]['u_name'];
        $this->mail = $db[0]['u_mail'];
        $this->group = $db[0]['g_id'];
        $this->rdate = $db[0]['u_rdate'];
    }

    function GetGroup() {
        return $this->group;
    }

    function SetGroup( $GroupID ) {
        $this->db->clear();
        $this->db->query( array(
            'update'    => "users",
            'fields'    => array( 'u_group' => $GroupID ),
            'where'     => '`u_id` = ' . $this->id,
        ) );
    }

    function IsAdmin() {
        return isset( $_ADMINISTRATORS[ $this->name ] ) &&  $_ADMINISTRATORS[ $this->name ];
    }

    function GetName() {
        return $this->name;
    }

    function GetID() {
        return $this->id;
    }

    function GetEMail() {
        return $this->mail;
    }

    function SetEMail( $EMail ) {
        $this->db->clear();
        $this->db->query( array(
            'update'    => "users",
            'fields'    => array( 'u_mail' => $EMail ),
            'where'     => '`u_id` = ' . $this->id,
        ) );
        $this->mail = $EMail;
    }

    function RegisterNew( $user_name, $email, $password ) {
        // Недопустимые символы или длина имени пользователя
        if( preg_match( '#[^A-z,a-z,0-9,_]#si', $user_name ) || strlen( $user_name ) < 4 ) return false;
        // Недопустимые символы в EMail
        if( preg_match( '#[^A-z,a-z,0-9,_,\.,@]#si', $email ) ) return false;
        $this->db->clear();
        $this->db->query( array(
            'select'    => "`u_id`",
            'from'      => "users",
            'where'     => '`u_name` = "' . $user_name . '" OR `u_mail` = "' . $email . '"',
        ) );
        // Такой пользователь уже существует или EMail уже зарегистрирован
        if( $this->db['count'] != 0 ) return false;

        $this->db->clear();
        $this->db->query( array(
            'insert'    => "users",
            'fields'    => array( 'u_group', 'u_name', 'u_mail', 'u_passhash', 'u_rdate' ),
            'values'    => array( 2, $user_name, $email, sha1( $password ), time() ),
        ) );
        return true;
    }
};

class TMessages implements ArrayAccess {
    private $db = null;
    private $user = "";

    function __construct( &$db, $user ) {
        $this->db = $db;
        $this->user = $user;
    }

    function Select( $last = -1, $user_id = -1 ) {
        // Пользоваться блогом (просматривать и отправлять сообщения) могут только авторизованные пользователи
        $u = new TUser( $this->db, $this->user );
        if( $u->GetID() == -1 ) return false;

        $this->db->clear();
        $query = array(
            'select'    => "m.*, u.`u_name`, u.`u_mail`",
            'from'      => array( 'm' => "messages", 'u' => "users" ),
            'where'     => "`m_deleted` = 0 AND `u_id` = `m_owner`"
        );
        if( $last > 0 ) {
            $query['order'] = '`m_id` DESC';
            $query['limit'] = $last;
        }
        if( $user_id != -1 ) $query['where'] .= ' AND `m_owner` = ' . $user_id;
        $this->db->query( $query );

        $this->container = array();
        for( $n = 0; $n < $this->db['count']; $n++ ) {
            $message = array(
                'ID'            => $this->db[$n]['m_id'],
                'text'          => htmlspecialchars( $this->db[$n]['m_text'] ),
                'ownerID'       => $this->db[$n]['m_owner'],
                'ownerName'     => $this->db[$n]['u_name'],
                'ownerMail'     => $this->db[$n]['u_mail'],
                'images'        => $this->db[$n]['m_images'],
            );
            $this->container[] = $message;
        }
        for( $n = 0; $n < count( $this->container ); $n++ ) {
            $this->db->clear();
            $this->db->query( array(
                'select'    => "*",
                'from'      => "images",
                'where'     => '`i_id` in (' . $this->container[$n]['images'] . ')',
            ) );
            $img = array();
            for( $i = 0; $i < $this->db['count']; $i++ ) $img[] = $this->db[$i]['i_path'];
            $this->container[$n]['images'] = $img;
        }

        return true;
    }

    function NewMessage( $user_name, $message, $images = array() ) {
        // Существует ли пользователь с таким ID
        $u = new TUser( $this->db, $user_name );
        if( $u->GetID() == -1 ) return false;

        $_images = "";
        // Вливаем картинки
        for( $n = 0; $n < count( $images ); $n++ ) {
            $this->db->clear();
            $_images .= $this->db->query( array(
                'insert'    => "images",
                'fields'    => array( 'i_path' ),
                'values'    => array( $images[$n] ),
            ) ) . ',';
        }
        $_images = strlen( $_images ) > 0 ? substr( $_images, 0, -1 ) : '';

        $this->db->clear();
        $this->db->query( array(
            'insert'    => "messages",
            'fields'    => array( 'm_text', 'm_owner', 'm_date', 'm_images' ),
            'values'    => array( mysql_escape_string( $message ), $u->GetID(), time(), $_images ),
        ) );
        return true;
    }

    function DeleteMessage( $mid ) {
        // Не админы не могут удалять сообщения
        $u = new TUser( $this->db, $this->user );
        if( !$u->IsAdmin() ) return false;

        // Удаляем
        $this->db->clear();
        $this->db->query( array(
            'update'    => "messages",
            'fields'    => array( 'm_deleted' => 1 ),
            'where'     => '`m_id` = ' . $mid,
        ) );

        return true;
    }

    function SomeAboutAPI( $user_id ) {
        $this->Select( 20, $user_id );
        return json_encode( $this->container );
    }

    protected $container = array();
    public function offsetSet($offset, $value){if(is_null($offset)){$this->container[]=$value;}else{$this->container[$offset]=$value;}}
    public function offsetExists($offset){return isset($this->container[$offset]);}
    public function offsetUnset($offset){unset($this->container[$offset]);}
    public function offsetGet($offset){return isset($this->container[$offset]) ? $this->container[$offset] : ($offset=='count' ? count($this->container) : null);}
};

?>