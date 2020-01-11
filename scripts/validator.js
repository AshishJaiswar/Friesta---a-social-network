$(document).ready(function () {
      $("#signup").submit(function (event) {
            var username = $("#username").val();
            var password = $("#password").val();
            var email = $("#email").val();
            event.preventDefault();
            var data = "username=" + username + "&password=" + password + "&email=" + email;
            $.ajax({
                  method: "post",
                  url: "create-account.php",
                  data: data,
                  success: function (data) {
                        $("#error_message").html(data);
                        if (data == "Sucess") {
                              window.location.href = './pages/welcome-greeting.html';
                        }
                  }
            });
      });



});
