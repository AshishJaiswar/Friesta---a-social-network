<?php

include("../classes/DB.php");
include("../classes/Mail.php");
include("../classes/Comment.php");
include("../classes/Post.php");
include("../classes/Notify.php");
include("../classes/Login.php");
include("../classes/Image.php");

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if ($_GET['url'] == "auth") {
    } else if ($_GET['url'] == "check-loggedin") {
        if (Login::isLoggedIn()) {

            $userid = Login::isLoggedIn();
        } else {
            echo 'Not logged in';
            die();
        }
    } else if ($_GET['url'] == "loginid") {
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        echo $userid;
    } else if ($_GET['url'] == "liked") {

        $token = $_COOKIE['SNID'];
        $likerId = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $x = DB::query('SELECT post_id FROM post_likes WHERE user_id=:userid', array(':userid' => $likerId));
        echo json_encode($x);
    } else if ($_GET['url'] == "musers") {
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $users = DB::query("SELECT DISTINCT s.username AS Sender, r.username AS Receiver, s.id AS SenderID, r.id AS ReceiverID, s.profileimg AS Sprofile,r.profileimg AS Rprofile FROM messages LEFT JOIN users s ON s.id = messages.sender LEFT JOIN users r ON r.id = messages.receiver WHERE (s.id = :userid OR r.id=:userid)", array(":userid" => $userid));
        $u = array();
        foreach ($users as $user) {
            if (!in_array(array('username' => $user['Receiver'], 'id' => $user['ReceiverID'], 'profileimg' => $user['Rprofile']), $u)) {
                array_push($u, array('username' => $user['Receiver'], 'id' => $user['ReceiverID'], 'profileimg' => $user['Rprofile']));
            }
            if (!in_array(array('username' => $user['Sender'], 'id' => $user['SenderID'], 'profileimg' => $user['Sprofile']), $u)) {
                array_push($u, array('username' => $user['Sender'], 'id' => $user['SenderID'], 'profileimg' => $user['Sprofile']));
            }
        }

        echo json_encode($u);
    } else if ($_GET['url'] == "messages") {
        $sender = $_GET['sender'];
        $token = $_COOKIE['SNID'];
        $receiver = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];

        $messages = DB::query('SELECT messages.id, messages.body, s.username AS Sender, r.username AS Receiver
                FROM messages
                LEFT JOIN users s ON messages.sender = s.id
                LEFT JOIN users r ON messages.receiver = r.id
                WHERE (r.id=:r AND s.id=:s) OR (r.id=:s AND s.id=:r)', array(':r' => $receiver, ':s' => $sender));
        echo json_encode($messages);
    } else if ($_GET['url'] == "users") {
        $token = $_COOKIE['SNID'];
        $user_id = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $username = DB::query('SELECT username FROM users WHERE id=:uid', array(':uid' => $user_id))[0]['username'];
        echo $username;
    } else if ($_GET['url'] == "search") {
        $tosearch = explode(" ", $_GET['query']);
        if (count($tosearch) == 1) {
            $tosearch = str_split($tosearch[0], 2);
        }
        $whereclause = "";
        $paramsarray = array(':body' => '%' . $_GET['query'] . '%');
        for ($i = 0; $i < count($tosearch); $i++) {
            if ($i % 2) {
                $whereclause .= " OR body LIKE :p$i ";
                $paramsarray[":p$i"] = $tosearch[$i];
            }
        }
        $posts = DB::query('SELECT posts.id, posts.body, users.username, posts.posted_at FROM posts, users WHERE users.id = posts.user_id AND posts.body LIKE :body ' . $whereclause . ' LIMIT 10', $paramsarray);
        //echo "<pre>";
        echo json_encode($posts);
    } else if ($_GET['url'] == "search-user") {
        $tosearch = explode(" ", $_GET['query']);
        if (count($tosearch) == 1) {
            $tosearch = str_split($tosearch[0], 2);
        }
        $whereclause = "";
        $paramsarray = array(':username' => '%' . $_GET['query'] . '%');
        for ($i = 0; $i < count($tosearch); $i++) {
            $whereclause .= " OR username LIKE :u$i ";
            $paramsarray[":u$i"] = $tosearch[$i];
        }
        $users = DB::query('SELECT users.username FROM users WHERE users.username LIKE :username ' . $whereclause . '', $paramsarray);
        echo json_encode($users);
    } else if ($_GET['url'] == "users") {
    } else if ($_GET['url'] == "comments" && isset($_GET['postid'])) {
        $output = "";
        $comments = DB::query('SELECT comments.comment, users.username FROM comments, users WHERE post_id = :postid AND comments.user_id = users.id', array(':postid' => $_GET['postid']));
        $output .= "[";
        foreach ($comments as $comment) {
            $output .= "{";
            $output .= '"Comment": "' . $comment['comment'] . '",';
            $output .= '"CommentedBy": "' . $comment['username'] . '"';
            $output .= "},";
            //echo $comment['comment']." ~ ".$comment['username']."<hr />";
        }
        $output = substr($output, 0, strlen($output) - 1);
        $output .= "]";

        echo $output;
    } else if ($_GET['url'] == "posts") {
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $followingposts = DB::query('SELECT posts.id, posts.body, posts.posted_at,posts.postimg, posts.likes, users.`username`, users.profileimg FROM users, posts, followers
            WHERE (posts.user_id = followers.user_id
OR posts.user_id = :userid)
            AND users.id = posts.user_id
            AND follower_id = :userid
            ORDER BY posts.user_id DESC;', array(':userid' => $userid), array(':userid' => $userid));
        $response = "[";
        foreach ($followingposts as $post) {
            $response .= "{";
            $response .= '"PostId": ' . $post['id'] . ',';
            $response .= '"PostBody": "' . $post['body'] . '",';
            $response .= '"PostedBy": "' . $post['username'] . '",';
            $response .= '"PostDate": "' . $post['posted_at'] . '",';
            $response .= '"PostImage": "' . $post['postimg'] . '",';
            $response .= '"ProfileImg": "' . $post['profileimg'] . '",';
            $response .= '"Likes": ' . $post['likes'] . '';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        echo $response;
    } else if ($_GET['url'] == "profileposts") {

        $userid = DB::query('SELECT id FROM users WHERE username=:username', array(':username' => $_GET['username']))[0]['id'];
        $followingposts =  DB::query('SELECT posts.id, posts.body, posts.posted_at, posts.postimg, posts.likes, users.`username`, users.profileimg FROM users, posts
        WHERE users.id = posts.user_id
        AND users.id = :userid
        ORDER BY posts.posted_at DESC;', array(':userid' => $userid));
        $response = "[";
        foreach ($followingposts as $post) {
            $response .= "{";
            $response .= '"PostId": ' . $post['id'] . ',';
            $response .= '"PostBody": "' . $post['body'] . '",';
            $response .= '"PostedBy": "' . $post['username'] . '",';
            $response .= '"PostDate": "' . $post['posted_at'] . '",';
            $response .= '"PostImage": "' . $post['postimg'] . '",';
            $response .= '"ProfileImg": "' . $post['profileimg'] . '",';
            $response .= '"Likes": ' . $post['likes'] . '';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";

        echo $response;
    }
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {


    if ($_GET['url'] == "likes") {
        $postId = $_GET['id'];
        $token = $_COOKIE['SNID'];
        $likerId = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        if (!DB::query('SELECT user_id FROM post_likes WHERE post_id=:postid AND user_id=:userid', array(':postid' => $postId, ':userid' => $likerId))) {
            DB::query('UPDATE posts SET likes=likes+1 WHERE id=:postid', array(':postid' => $postId));
            DB::query('INSERT INTO post_likes VALUES (\'\', :postid, :userid)', array(':postid' => $postId, ':userid' => $likerId));
            Notify::createNotify("", $postId);
            $liked = "true";
        } else {
            DB::query('UPDATE posts SET likes=likes-1 WHERE id=:postid', array(':postid' => $postId));
            DB::query('DELETE FROM post_likes WHERE post_id=:postid AND user_id=:userid', array(':postid' => $postId, ':userid' => $likerId));
            $liked = "false";
        }
            echo "{";
            echo '"Likes":';
            echo DB::query('SELECT likes FROM posts WHERE id=:postid', array(':postid' => $postId))[0]['likes'];
            echo ',"liked":';
            echo "$liked";
            echo "}";
    } else if ($_GET['url'] == "message") {
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $postBody = file_get_contents("php://input");
        $postBody = json_decode($postBody);
        $body = $postBody->body;
        $receiver = $postBody->receiver;
        if (strlen($body) > 100) {
            echo "{ 'Error': 'Message too long!' }";
        }
        DB::query("INSERT INTO messages VALUES ('', :body, :sender, :receiver, '0')", array(':body' => $body, ':sender' => $userid, ':receiver' => $receiver));
        echo '{ "Success": "Message Sent!" }';
    } else if ($_GET['url'] == 'postcomment') {
        $postid = $_GET['postid'];
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $comment_body = file_get_contents("php://input");
        $comment = json_decode($comment_body);
        Comment::createComment($comment->comment_data, $postid, $userid);
    } else if ($_GET['url'] == "createpost") {
        //$post_body = file_get_contents("php://input");
        //$post = json_decode($post_body);
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        //$data = $post->postdata;
        //Post::createPost($data, $userid, $userid);
        //echo "sucess";
        if ($_FILES['postimg']['size'] == 0) {
            Post::createPost($_POST['postbody'], Login::isLoggedIn(), $userid);
            header("Location: http://localhost:8081/socialnetwork/index.html");
        } else {
            $postid = Post::createImgPost($_POST['postbody'], Login::isLoggedIn(), $userid);
            Image::uploadImage('postimg', "UPDATE posts SET postimg=:postimg WHERE id=:postid", array(':postid' => $postid));
            header("Location: http://localhost:8081/socialnetwork/index.html");
        }
    } else if ($_GET['url'] == 'editprofile') {
        /*$name =  $_POST['name'];
        $lives = $_POST['lives'];
        $relationship = $_POST['relationship'];
        $bio = $_POST['bio'];
        if (empty($name)  || empty($lives) || empty($relationship) || empty($bio)) { }*/
        $token = $_COOKIE['SNID'];
        $user_id = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        $username = DB::query('SELECT username FROM users WHERE id=:uid', array(':uid' => $user_id))[0]['username'];
        $formData = file_get_contents("php://input");
        $data = json_decode($formData);
        $name = $data->name;
        $lives = $data->lives;
        $relationship = $data->relationship;
        $bio = $data->bio;
        DB::query('UPDATE profiles SET fullname=:name , lives_in=:lives, relationship=:relationship, bio=:bio WHERE username=:username', array(':name' => $name, ':lives' => $lives, ':relationship' => $relationship, 'bio' => $bio, ':username' => $username));
        echo 'success';
    } else if ($_GET['url'] == 'changeprofile') {
        //print_r($_FILES['profileimg']['tmp_name']);
        $token = $_COOKIE['SNID'];
        $userid = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => sha1($token)))[0]['user_id'];
        if (empty($_FILES)) {
            echo "<p style='color:red;'>Please select an image</p>";
        } else {
            Image::uploadImage('profileimg', "UPDATE users SET profileimg = :profileimg WHERE id=:userid", array(':userid' => $userid));
            echo "<p style='color:#44bd32;'>Profile image changed successfully</p>";
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    if ($_GET['url'] == "auth") {
        if (isset($_GET['token'])) {
            if (DB::query("SELECT token FROM login_tokens WHERE token=:token", array(':token' => sha1($_GET['token'])))) {
                DB::query('DELETE FROM login_tokens WHERE token=:token', array(':token' => sha1($_GET['token'])));
                echo '{ "Status": "Success" }';
            } else {
                echo '{ "Error": "Invalid token" }';
            }
        } else {
            echo '{ "Error": "Malformed request" }';
        }
    }
}
