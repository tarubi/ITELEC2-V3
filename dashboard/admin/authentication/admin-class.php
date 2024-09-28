<?php
require_once __DIR__.'/../../../database/dbconnection.php';
include_once __DIR__.'/../../../config/settings-configuration.php';
require_once __DIR__.'/../../../src/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
 

class ADMIN
{
    private $conn;
    private $settings;
    private $smtp_email;
    private $smtp_password;

    public function __construct()
    {
        $this->settings = new SystemConfig();
        $this->smtp_email = $this->settings->getSmtpEmail();
        $this->smtp_password = $this->settings->getSmtpPassword();

        $database = new Database();
        $this->conn = $database->dbConnection();

    }

    public function sendOtp($otp, $email){
        if($email == NULL){
            echo "<script>alert('No Email found'); window.location.href = '../../../';</script>";
            exit;
        }else{
            $stmt = $this->runQuery("SELECT * FROM user WHERE email = :email");
            $stmt->execute(array(":email" => $email));
            $stmt->fetch(PDO::FETCH_ASSOC);

            if($stmt->rowCount() > 0){
                echo "<script>alert('Email already taken. Please try another one'); window.location.href = '../../../';</script>";
                exit;   
            }else{
                $_SESSION['OTP'] = $otp;

                $subject = "OTP VERIFICATION";
                $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>OTP Verification</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color; #f5f5f5;
                            margin: 0;
                            padding: 0;
                        }  
                        h1 {
                            color: #333333;
                            font-size: 24px;
                            margin-bottom: 20px;
                        }
                        p {
                            color: #666666;
                            font-size: 16px:
                            margin-buttom: 10px;
                        }
                        .button {
                            display: Inline-block;
                            padding: 12px 24px;
                            background-color: 0088cc;
                            color: #ffffff;
                            text-decoration: none;
                            border-radius: 4px;
                            font-size: 16px;
                            margin-top: 20px;                        
                        }
                        .logo {
                            display: block;
                            text-align: center;
                            margin-bottom: 30px;
                        }
                    </style>
                </head>
                </body>
                <div>
                     <h1>OTP Verification</h1>
                     <p>Hello, $email</p>
                     <p>Your OTP is: $otp</p>
                     <p>if you didn't request an OTP, please ignore  this email.</p>
                     <p>Thank you!</p>
                </div>
                </body>
                </html>";
                $this->sendEmail($email, $message, $subject, $this->smtp_email, $this->smtp_password);
                echo "<script>alert('We sent the OTP $email'); window.location.href = '../../../verify-otp.php';</script>";
            }
        }
    
    }

    public function verifyOTP($username, $email, $password, $tokencode, $otp, $csrf_token){
        if($otp == $_SESSION['OTP']){
            unset($_SESSION['OTP']);
            
            $subject = " VERIFICATION SUCCESS";
            $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Verification SUCCESS</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color; #f5f5f5;
                        margin: 0;
                        padding: 0;
                    } 
                   }
                    h1 {
                        color: #333333;
                        font-size: 24px;
                        margin-bottom: 20px;
                    }
                    p {
                        color: #666666;
                        font-size: 16px:
                        margin-buttom: 10px;
                    }
                    .button {
                        display: Inline-block;
                        padding: 12px 24px;
                        background-color: 0088cc;
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 16px;
                        margin-top: 20px;                        
                    }
                    .logo {
                        display: block;
                        text-align: center;
                        margin-bottom: 30px;
                    }
                </style>
            </head>
            </body>
                <div>
                    <h1>Welcome</h1>
                  <p>Hello, <strong>$email</strong></p>
                  <p>Welcome to our system.</p>
                  <P>Thank you!</p>
                </div>
            </body>
            </html>";

            $this->sendEmail($email, $message, $subject, $this->smtp_email, $this->smtp_password);
            echo "<script>alert('Verification Success.'); window.location.href = '../../../';</script>";

            unset($_SESSION['not_verify_username']);
            unset($_SESSION['not_verify_email']);
            unset($_SESSION['not_verify_password']);

            $this->addAdmin(csrf_token: $csrf_token, username: $username, email: $email, password: $password);

        }else if($otp == NULL){
            echo "<script>alert('No OTP Found.'); window.location.href = '../../../verify-otp.php';</script>";
            exit;
        }else{
            echo "<script>alert('Invalid OTP.'); window.location.href = '../../../verify-otp.php';</script>";
            exit;
        }
    }

    public function addAdmin($csrf_token, $username, $email, $password)
    {
        $stmt = $this->runQuery("SELECT * FROM user WHERE email = :email");
        $stmt->execute(array(":email" => $email));

        if($stmt->rowcount() > 0){
            echo "<script>alert('Email Already Exists.'); window.location.href = '../../../';</script>";
            exit;
        }

        if(!isset($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)){
            echo "<script>alert('Invalid CSRF token.'); window.location.href = '../../../';</script>";
            exit;
        }

        unset($_SESSION['csrf_token']);

        $hash_password = md5($password);

        $stmt = $this->runQuery('INSERT INTO user (username, email,password, status) VALUES (:username, :email,:password, :status)');
        $exec = $stmt->execute(array(
            ":username" => $username,
            ":email" => $email,
            ":password" => $hash_password,
            ":status"=> "active"
        ));

        if($exec){
            echo "<script>alert('Admin Added Succesfully.'); window.location.href = '../../../';</script>";
            exit;
        }else{
            echo "<script>alert('Error Adding Admin.'); window.location.href = '../../../';</script>";
            exit;
        }
    }

    public function adminSignin($email, $password, $csrf_token)
    {
        try{
          if  (!isset($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)){
                echo "<script>alert('Invalid CSRF token.'); window.location.href = '../../../';</script>";
                exit;
            }
            unset($_SESSION['csrf_token']);

            $stmt = $this->conn->prepare("SELECT * FROM user WHERE email = :email AND status = :status");
            $stmt->execute(array(":email" => $email, ":status" => "active"));
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if($stmt->rowCount() == 1){
                if($userRow['status'] == "active"){
                    if($userRow['password'] == md5($password)){
                        $activity = "Has Successfully signed in.";
                        $user_id = $userRow['id'];

                        $this->logs($activity, $user_id);

                        $_SESSION['adminSession'] = $user_id;
                        echo "<script>alert('Welcome.'); window.location.href = '../';</script>";
                       exit;
                    }else{
                        echo "<script>alert('Password is incorrect.'); window.location.href = '../../../';</script>";
                       exit;
                    }
                }else{
                    echo "<script>alert('Entered Email is not verify.'); window.location.href = '../../../';</script>";
                    exit;
                }
            }else{
                echo "<script>alert('No account found.'); window.location.href = '../../../';</script>";
                exit;
            }

         //   if ($stmt->rowCount () == 1 && $userRow['password'] == md5($password)){
          //      $activity = "Has Successfully signed in.";
          //      $user_id = $userRow['id'];
          //      $this->logs($activity, $user_id);
          //      $_SESSION['adminSession'] = $user_id;
          //      echo "<script>alert('Welcome.'); window.location.href = '../';</script>";
          //      exit;
          //  }else{
           //     echo "<script>alert('Invalid Credentials.'); window.location.href = '../../../';</script>";
          //      exit;

         //   }

        }catch (PDOException $ex) {
            echo $ex->getMessage();
        }

    }

    public function adminSignout()
    {
        unset($_SESSION['adminSession']);
        echo "<script>alert('Sign out Successfully.'); window.location.href = '../../../';</script>";
        exit;
    }

    public function sendEmail($email, $message, $subject, $smtp_email, $smtp_password){
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->addAddress($email);
        $mail->Username = $smtp_email;
        $mail->Password = $smtp_password;
        $mail->setFrom($smtp_email, "lei");
        $mail->Subject =$subject;
        $mail->msgHTML($message);
        $mail->send();
    }
    public function logs($activity, $user_id)
    {
       $stmt = $this->conn->prepare("INSERT INTO logs (user_id, activity) VALUES  (:user_id, :activity)");
       $stmt->execute(array(":user_id" => $user_id, ":activity" => $activity));
    }

    public function isUserLoggedIn()
    {
        if(isset($_SESSION['adminSession'])){
            return true;
        }

    }

    public function redirect()
    {
       // echo "<script>alert('Admin must loggin first.'); window.location.href = '../../../';</script>";
       //exit;
       echo __DIR__;

    }

    public function runQuery($sql)
    {
       $stmt = $this->conn->prepare($sql);
       return $stmt;
    }
}

if(isset($_POST['btn-signup'])){
    $_SESSION['not_verify_username'] = trim($_POST['username']);
    $_SESSION['not_verify_email'] = trim($_POST['email']);
    $_SESSION['not_verify_password'] = trim($_POST['password']);
 
    $email = trim($_POST['email']);
    $otp = rand(100000, 999999);


    $addAdmin = new ADMIN ();
    $addAdmin->sendOtp($otp, $email);
}
if(isset($_POST['btn-verify'])){
    $csrf_token = trim($_POST['csrf_token']);
    $username =  $_SESSION['not_verify_username'];
    $email =   $_SESSION['not_verify_email'];
    $password =  $_SESSION['not_verify_password'];

    $tokencode =md5 (uniqid(rand()));
    $otp = trim($_POST['otp']);

    $adminVerify = new ADMIN();
    $adminVerify->verifyOTP($username, $email, $password, $tokencode, $otp, $csrf_token);


}
if(isset($_POST['btn-signin'])){
    $csrf_token = trim($_POST['csrf_token']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $adminSignin = new ADMIN();
    $adminSignin->adminSignin($email, $password, $csrf_token);

}

if(isset($_GET['admin_signout'])){

    $adminSignout = new ADMIN();
    $adminSignout->adminSignout();
}
?>