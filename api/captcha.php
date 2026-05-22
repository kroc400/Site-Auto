<?php
// api/captcha.php
session_start();
header('Content-Type: application/json');

$num1 = rand(1, 10);
$num2 = rand(1, 10);
$operators = ['+', '-'];
$operator = $operators[array_rand($operators)];

if ($operator === '+') {
    $question = "$num1 + $num2";
    $result = $num1 + $num2;
} else {
    if ($num1 >= $num2) {
        $question = "$num1 - $num2";
        $result = $num1 - $num2;
    } else {
        $question = "$num2 - $num1";
        $result = $num2 - $num1;
    }
}

$_SESSION['captcha_result'] = $result;

echo json_encode(['question' => "Сколько будет $question?"]);
?>