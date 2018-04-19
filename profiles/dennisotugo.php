<?php
  if(!defined('DB_USER')){
    require "../../config.php";
  }
  try {
    $conn = new PDO("mysql:host=". DB_HOST. ";dbname=". DB_DATABASE , DB_USER, DB_PASSWORD);
  } catch (PDOException $pe) {
    die("Could not connect to the database " . DB_DATABASE . ": " . $pe->getMessage());
  }
  $date_time = new DateTime('now', new DateTimezone('Africa/Lagos'));
  global $conn;

  try {
    $sql = 'SELECT * FROM secret_word';
    $secret_word_query = $conn->query($sql);
    $secret_word_query->setFetchMode(PDO::FETCH_ASSOC);
    $secret_word_result = $secret_word_query->fetch();

    $sql = 'SELECT * FROM interns_data WHERE username = "dennisotugo"';
    $intern_data_query = $conn->query($sql);
    $intern_data_query->setFetchMode(PDO::FETCH_ASSOC);
    $intern_data_result = $intern_data_query->fetch();
  } catch (PDOException $e) {
      throw $e;
  }

  $secret_word = $secret_word_result['secret_word'];
  $name = $intern_data_result['name'];
  $img_url = $intern_data_result['image_filename'];

  if(isset($_POST['payload']) ){
    require "../answers.php";
    $question = trim($_POST['payload']);
  
    function isTraining($question) {
      if (strpos($question, 'train:') !== false) {
        return true;
      }
      return false;
    }
  
    function getAnswer() {
      global $question;
      global $conn;

      $sql = 'SELECT * FROM chatbot WHERE question LIKE "' . $question . '"';
      $answer_data_query =  $conn->query($sql);
      $answer_data_query->setFetchMode(PDO::FETCH_ASSOC);
      $answer_data_result = $answer_data_query->fetchAll();
      $answer_data_index = 0;
      if (count($answer_data_result) > 0) {
        $answer_data_index = rand(0, count($answer_data_result) - 1);
      }

      if ($answer_data_result[$answer_data_index]["answer"] == "") {
        return 'I don\'t understand that question. If you want to train me to understand, please type "<code>train: your question? # The answer.</code>"';
      }
  
      if (containsVariables($answer_data_result[$answer_data_index]['answer']) || containsFunctions($answer_data_result[$answer_data_index]['answer'])) {
        $answer = resolveAnswer($answer_data_result[$answer_data_index]['answer']);
        return $answer;
      } else {
        return $answer_data_result[$answer_data_index]['answer'];
      }
    }
    
    function resolveQuestionFromTraining($question) {
      $start = 7;
      $end = strlen($question) - strpos($question, " # ");
      $new_question = substr($question, $start, -$end);
      return $new_question;
    }
    
    function resolveAnswerFromTraining($question) {
      $start = strpos($question, " # ") + 3;
      $answer = substr($question, $start);
      return $answer;
    }

    if (isTraining($question)) {
      $answer = resolveAnswerFromTraining($question);
      $question = strtolower(resolveQuestionFromTraining($question));
      $question_data = array(':question' => $question, ':answer' => $answer);
  
      $sql = 'SELECT * FROM chatbot WHERE question = "' . $question . '"';
      $question_data_query =  $conn->query($sql);
      $question_data_query->setFetchMode(PDO::FETCH_ASSOC);
      $question_data_result = $question_data_query->fetch();
  
  
      $sql = 'INSERT INTO chatbot ( question, answer )
          VALUES ( :question, :answer );';
      $q = $conn->prepare($sql);
      $q->execute($question_data);
      echo "Training successful.";
      return;
    }

    function containsVariables($answer) {
      if (strpos($answer, "{{") !== false && strpos($answer, "}}") !== false) {
        return true;
      }
    
      return false;
    }
    
    function containsFunctions($answer) {
      if (strpos($answer, "((") !== false && strpos($answer, "))") !== false) {
        return true;
      }
      return false;
    }
    
    function resolveAnswer($answer) {
      if (strpos($answer, "((") == "" && strpos($answer, "((") !== 0) {
        return $answer;
      } else {
        $start = strpos($answer, "((") + 2;
        $end = strlen($answer) - strpos($answer, "))");
        $function_found = substr($answer, $start, - $end);
        $replacable_text = substr($answer, $start, - $end);
        $new_answer = str_replace($replacable_text, $function_found(), $answer);
        
        $new_answer = str_replace("((", "", $new_answer);
        $new_answer = str_replace("))", "", $new_answer);
        return resolveAnswer($new_answer);
      }
    }
  
    $answer = getAnswer();
    echo $answer;
    exit();
  
  } else {

?>

  <div class="bot-body">
    <div class="messages-body">
      <div>
        <div class="message bot">
          <span class="content">Hi! I'm a bot and you can ask me various questions and give me different commands.</span>
        </div>
      </div>
      <div>
        <div class="message bot">
          <span class="content">Type "<code>show: List of commands</code>" to see what the list of commands I understand.</span>
        </div>
      </div>
    </div>
    <div class="send-message-body">
      <input class="message-box" placeholder="Type here..."/>
    </div>
  </div>

<style>
	/* Popup box BEGIN */
.hover {
    background:rgba(0,0,0,.4);
    cursor:pointer;
    display:none;
    height:100%;
    position:fixed;
    text-align:center;
    top:0;
    width:100%;
    z-index:10000;
}
.hover .helper {
    display:inline-block;
    height:100%;
    vertical-align:middle;
}
.hover > div {
    background-color: #fff;
    box-shadow: 10px 10px 60px #555;
    display: inline-block;
    height: auto;
    max-width: 551px;
    min-height: 100px;
    vertical-align: middle;
    width: 60%;
    position: relative;
    border-radius: 8px;
    padding: 15px 5%;
}
.popupCloseButton {
    background-color: #fff;
    border: 3px solid #999;
    border-radius: 50px;
    cursor: pointer;
    display: inline-block;
    font-family: arial;
    font-weight: bold;
    position: absolute;
    top: -20px;
    right: -20px;
    font-size: 25px;
    line-height: 30px;
    width: 30px;
    height: 30px;
    text-align: center;
}
.popupCloseButton:hover {
    background-color: #ccc;
}
.trigger_popup {
    cursor: pointer;
    font-size: 20px;
    margin: 20px;
    display: inline-block;
    font-weight: bold;
}
footer {
	padding: 0px !important;
}
  .bot-body {
    margin: 100px auto;
  }

  .messages-body {
    background-color: white;
    color: #3A3A5E;
    padding: 10px;
    overflow: auto;
    width: 100%;
    border-top: 1px solid #f1f1f1;
    padding-bottom: 50px;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
  }

  .messages-body > div {
    display: inline-block;
    width: 100%;
  }

  .message {
    float: left;
    font-size: 16px;
    background-color: #edf3fd;
    padding: 10px;
    display: inline-block;
    border-radius: 3px;
    position: relative;
    margin: 5px;
  }

  .message:before {
    position: absolute;
    top: 0;
    content: '';
    width: 0;
    height: 0;
    border-style: solid;
  }

  .message.bot:before {
    border-color: transparent #edf3fd transparent transparent;
    border-width: 0 10px 10px 0;
    left: -9px;
  }

  .message.you:before {
    border-width: 10px 10px 0 0;
    right: -9px;
    border-color: #edf3fd transparent transparent transparent;
  }
  
  .message.you {
    float: right;
  }

  .content {
    display: block;
  }

  .send-message-body {
		position: fixed;
    width: 100%;
    left: 0;
    bottom: 0px;
    box-sizing: border-box;
    box-shadow: 1px 1px 9px 0px rgba(1, 1, 1, 1);
  }

  .message-box {    
		width: -webkit-fill-available;
    border: none;
		padding: 2px 4px;
    font-size: 18px;
  }

  .img-body {
    margin-left: 85px;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background-color: #fff;
    padding: 5px;
    top: 150px;
    left: 175px;
  }

  .img-body > img {
    height: 190px;
    width: 190px;
    border-radius: 50%;
  }

  .time {
    padding: 10px;
    text-transform: uppercase;
    font-size: 60px;
    width: 100%;
  }

  .time-body {
    display: flex;
    height: 100%;
    text-align: center;
    justify-content: center;
    align-items: center;
  }

  body {
		overflow: hidden;
    height: 100%;
  }

</style>
<script>
  window.onload = function() {
    $(document).keypress(function(e) {
      if(e.which == 13) {
        getResponse(getQuestion());
      }
    });
  }

  function isUrl(string) {
    var expression = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
    var regex = new RegExp(expression);
    var t = string;

    if (t.match(regex)) {
      return true;
    } else {
      return false;
    }
  }

  function stripHTML(message){
    var re = /<\S[^><]*>/g
    return message.replace(re, "");
  }

  function getResponse(question) {
    updateThread(question);
    showResponse(true);

    if (question.trim() === "") {
      showResponse(':)');
      return;
    } 

    if (question.toLowerCase().includes("open: ") && isUrl(question.toLowerCase().split("open: ")[1].trim())) {
      var textToSay = question.toLowerCase().split("open: ")[1];
      showResponse('Okay, opening: <code>'+ textToSay + '</code>');
      window.open("http://" + textToSay);
      return;
    }

    if (question.toLowerCase().includes("say: ")) {
      var textToSay = question.toLowerCase().split("say: ")[1];
      var msg = new SpeechSynthesisUtterance(textToSay);
      window.speechSynthesis.speak(msg);
      showResponse('Okay, saying: <code>'+ textToSay + '</code>');
      return;
    }

    $.ajax({
      url: "profiles/dennisotugo.php",
      method: "POST",
      data: { payload: question },
      success: function(res) {
        if (res.trim() === "") {
          showResponse(`
          I don\'t understand that question. If you want to train me to understand,
          please type <code>"train: your question? # The answer."</code>
          `);
        } else {
          showResponse(res);
        }
      }
    });
  }

  function showResponse(response) {
    if (response === true) {
      $('.messages-body').append(
        `<div class="trigger_popup">
          <div class="message bot temp">
            <span class="content">Thinking...</span>
          </div>
        </div>`
      );
      return;
    }

    $('.temp').parent().remove();
    $('.messages-body').append(
      `<div class="trigger_popup">
        <div class="message bot">
          <span class="content">${response}</span>
        </div>
      </div>`
    );
    $('.message-box').val("");

  }

  function getQuestion() {
    return $('.message-box').val();
  }

  function updateThread(message) {
    message = stripHTML(message);
    $('.messages-body').append(
      `<div class "trigger_popup">
        <div class="message you">
          <span class="content">${message}</span>
        </div>
      </div>`
    );
  }

  var options = { hour12: true };
  var time = "";
  function updateTime() {
    var date = new Date();
    time = date.toLocaleString('en-NG', options).split(",")[1].trim();
    document.querySelector(".time").innerHTML = time;
  }
  setInterval(updateTime, 60);

	$(window).load(function () {
    $(".trigger_popup").click(function(){
       $('.hover').show();
    });
    $('.hover').click(function(){
        $('.hover').hide();
    });
    $('.popupCloseButton').click(function(){
        $('.hover').hide();
    });
});
</script>
<?php } ?>
