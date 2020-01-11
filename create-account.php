<?php

include('classes/DB.php');
include('classes/Mail.php');

$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];

if (empty($username) && empty($password) && empty($email)) {
      echo "All fields are required";
      exit();
} else {
      if (empty($username)) {
            echo "Please enter a username";
            exit();
      } else if (empty($password)) {
            echo "Please enter a password";
            exit();
      } else if (empty($email)) {
            echo "Please enter a email";
            exit();
      } else {
            echo "";
      }
}
if (!DB::query('SELECT username FROM users WHERE username=:username', array(':username' => $username))) {

      if (strlen($username) >= 5 && strlen($username) <= 32) {

            if (preg_match("/[a-zA-Z0-9_]+/", $username)) {

                  if (strlen($password) >= 6 && strlen($password) <= 60) {

                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                              if (!DB::query('SELECT email FROM users WHERE email=:email', array(':email' => $email))) {

                                    DB::query('INSERT INTO users VALUES(\'\',:username,:password,:email,\'0\', "https://i.imgur.com/owS76PV.png")', array(':username' => $username, ':password' => password_hash($password, PASSWORD_BCRYPT), ':email' => $email));
                                    DB::query('INSERT INTO profiles VALUES(\'\', \'\', \'\', \'\', NOW(), \'\', :username)', array(':username' => $username));
                                    Mail::sendMail('Welcome to Friesta', 'Your Account has been created sucessfully.', $email);
                                    echo "Sucess";
                              } else {
                                    echo 'User Already Registered.<br>';
                              }
                        } else {
                              echo "Invalid email.<br>";
                        }
                  } else {
                        echo "Password must be greater than 6 and less than 60.<br>";
                  }
            } else {
                  echo "Invalid username.";
            }
      } else {
            echo "Username must be greater than 5 and less than 32.<br>";
      }
} else {
      echo "Username taken.<br>";
}
