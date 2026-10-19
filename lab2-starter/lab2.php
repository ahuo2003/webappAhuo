<?php

require_once 'lab2B.php';
// Lab 2: PHP Expressions and Control Structures

echo '<h1>Lab 2A: Expressions and Control Structures</h1>';

// 1. Expressions and Operators
echo '<h2>1. Expressions and Operators</h2>';

$x = -5;
$y = 3 * (abs(2 * $x) + 4);

echo "The value of y is: $y<br>";

// 2. Boolean Comparisons
echo '<h2>2. Comparisons</h2>';

// TODO: Use var_dump() to compare:
// 2 > 1
// 2 == 3
// "5" == 5
// "5" === 5

var_dump(2>1); //true
echo'<br>';
var_dump(2==3); //false
echo'<br>';
var_dump("5"==5); //true
echo'<br>';
var_dump("5"===5); //false
echo'<br>';

// 3. Unary Operators
echo '<h2>3. Unary Operators</h2>';

$c = 5;
$d=-$c; // Unary minus
$e=+$c; // Unary plus
$f=++$c; // Pre-increment

echo "d = $d<br>";
echo "e = $e<br>";
echo "f = $f<br>";

// 4. Operator Precedence
echo '<h2>4. Operator Precedence</h2>';

$a = 2 + 3 * 4;
$b = (2 + 3) * 4;
echo $a; // 14
echo'<br>';
echo $b; // 20
echo'<br>';

// 5. Assignment Associativity
echo '<h2>5. Assignment Associativity</h2>';

$level = $score = $time = 0;
echo "Level: $level<br>";
echo "Score: $score<br>";
echo "Time: $time<br>";

// 6. Equality and Assignment
echo '<h2>6. Equality and Assignment</h2>';

$a = 5;
$b = 5;
var_dump($a==$b); // true
echo '<br>';
var_dump($a===$b); // true
echo '<br>';
$c = 10;
$d = 20;
var_dump($c==$d); // false
echo '<br>';
var_dump($c===$d); // false
echo '<br>';

// 7. Logical Operators
echo '<h2>7. Logical Operators</h2>';

$a = 1;
$b = 0;
$c = 1;

var_dump($a && $b); // false
echo '<br>';
var_dump($a || $b); // true
echo '<br>';
var_dump(!$a); // false
echo '<br>';
var_dump(!$b); // true


// 8. Scholarship Decision with if...else
echo '<h2>8. Scholarship Decision with if...else</h2>';

$gpa = 3.59;

if ($gpa >= 3.7){
    echo "Student gets a Scholarship";
} else {
    echo "Student does not get a Scholarship";
}
echo '<br>';

// 9. Scholarship Decision with if...elseif...else
echo '<h2>9. Scholarship Decision with if...elseif...else</h2>';

$gpa = 3.6;

if ($gpa >= 3.7){
    echo "Student gets a Scholarship";
} elseif ($gpa >= 3.5) {
    echo "Student gets a Partial Scholarship";
} else {
    echo "Student does not get a Scholarship";
}
echo '<br>';

// 10. Access Status
echo '<h2>10. Access Status</h2>';

$status = 'pending';

switch ($status) {
    case 'approved':
        echo "Access granted";
        break;
    case 'pending':
        echo "Review in Progress";
        break;
    default:
        echo "Access Denied";
        break;
}

// 11. While Loop
echo '<h2>11. While Loop</h2>';
    
    $fuel = 10;
    while ($fuel > 1) {
        echo "There is enough fuel<br>";
        $fuel--;
    }
echo '<br>';

// 12. Multiplication Table
echo '<h2>12. Multiplication Table</h2>';

for ($count = 1; $count <= 12; $count++) {
    echo $count . " times 12 is " .
    ($count * 12) . "<br>";
}

echo '<br>';