<?php
class DBconnection {
    private $con;

    public function connection() {
        include_once dirname(__FILE__) . '/Contant.php';
        $this->con = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); // Corrected parameters

        if (mysqli_connect_errno()) {
            echo "Failed to connect with database: " . mysqli_connect_error();
        }
        return $this->con;
    }
}
?>