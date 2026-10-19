<?php

// Lab 2B: PHP Functions and Basic Objects

echo '<h1>Lab 2B: Functions and Objects</h1>';

// 1. Function with Parameters and Return Value
echo '<h2>1. Function with Parameters and Return Value</h2>';

function formatName($first, $middle, $last)
{
    $first = ucfirst(strtolower($first));
    $middle = ucfirst(strtolower($middle));
    $last = ucfirst(strtolower($last));
    return $first . " " .  $middle . " " . $last;
}

$displayName = formatName("mARIA", "lee", "CHEN");
echo $displayName;
echo '<br>';

// 2. Function That Returns an Array
echo '<h2>2. Function That Returns an Array</h2>';

function cleanNameParts($first, $middle, $last)
{
    return [
        ucfirst(strtolower($first)),
        ucfirst(strtolower($middle)),
        ucfirst(strtolower($last))
    ];
}
$nameParts = cleanNameParts("mARIA", "lee", "CHEN");
echo $nameParts[0]; // Display the first name
echo '<br>';


// 3. Variable Scope
echo '<h2>3. Variable Scope</h2>';

$first = 'Maria';
$last = 'Chen';

function buildFullName()
{
    global $first, $last;
    return "$first $last";
}

// TODO: Call buildFullName() and display the result.

function buildFullName2($first, $last)
{
    return "$first $last";
}

echo buildFullName2($first, $last);
echo '<br>';

// 4. Static Variable
echo '<h2>4. Static Variable</h2>';

function nextTicketNumber()
{
    static $number = 0;
    $number++;
    return $number;
}

echo nextTicketNumber(); //1
echo '<br>';
echo nextTicketNumber(); //2
echo '<br>';

// 5. User Class
echo '<h2>5. User Class</h2>';

class User
{
    public $name;
    public $email;

    public function display()
    {
        return "User: "  . $this->name;
    }
}

$user = new User();
$user->name = "Maria Chen";
$user->email = "maria@example.com";
echo $user->display();

// 6. Constructor and Password Hashing
echo '<h2>6. Constructor and Password Hashing</h2>';

class User2
{
    public $name;
    private $passwordHash;

    public function __construct($name, $password)
    {
        $this->name = $name;
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }
}

// TODO: Create one User2 object and display it using print_r().
$user = new User2("Maria Chen", "temporary-password");
print_r($user);
