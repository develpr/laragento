#!/usr/bin/env php
<?php

/**
 * MIT license
 *
 * This will create a custom command tool command completion file for use by PhpStorm 6
 * from any command line tool that uses the Symfony 2.x Console component
 *
 * see http://www.jetbrains.com/phpstorm/webhelp/command-line-tool-support.html
 *
 * Very lightly tested on OSX Lion with PhpStorm 6. Should work on Linux or OSX or Windows.
 * Windows users may have to explicitly set the path to the PhpStorm prefs.
 * NOT tested even a little tiny bit on Windows of any flavor
 *
 * Run in any directory:
 * php makestorm.php <toolname> [OPTIONAL]-p<path to phpstorm prefs>
 *
 * Usage:
 * FIRST, add a custom command line tool in PhpStorm 6: Preferences::Command Line Tool Support;
 * Click the '+' sign below the tool list and select 'Custom Tool';
 * To invoke the tool using php you'll need to add an alias for the executable:
 *    "$PhpExecutable$" /full/path/to/artisan
 * Be sure to remember the "Tool Name" you assign.
 *
 * Test the command in the Command Line Tools Console to make sure it runs
 *   usiing "<command alias> list --xml".
 * You should see quite a bit of XML output in the console listing all of the commands.
 * If not, the tool wasn't invoked correctly or it doesn't support XML output
 * ...We parse this XML to build the command line support file and makestorm won't work without it. Sorry.
 *
 * PhpStorm will have created an 'empty' <toolname>.xml file in its prefs directory that we will populate
 *
 * Now you should be able to just run "php makestorm.php <toolname>" from the command line
 *
 * Makestorm should find the path to the PhpStorm prefs folder and load the correct file that was created there.
 * If it can't you may need to explicitly point to it when you run makestorm.
 * See http://www.jetbrains.com/phpstorm/webhelp/project-and-ide-settings.html for normal locations
 * Specify it like this: "php makestorm.php <toolname> -p<path to phpstorm prefs>"
 *
 * If you go back into PhpStorm to the Command Line Tool Support page, select your tool,
 * and reload it (click the little recycle button under the list).
 *
 * You should be able to then invoke the tool using the alias you setup when you created it.
 *
 * See http://www.jetbrains.com/phpstorm/webhelp/running-command-line-tool-commands.html
 *
 * That's it.
 *
 */

//*********************
//Let's get started...'
//*********************

//in some environments we have to explicitly specify the phpcli interpreter to use to run the command tool
//if this is the case for you, uncomment this line and set the path of the interpreter you need to use
//$php = "/usr/local/php5/bin/php";

//get the commandline options
$shortopts = "";
$shortopts .= "t:"; // -t Tool Name (Required)
$shortopts .= "p::"; // -p Tool file path (Optional) -- this is the path to the PhpStorm prefs

$longopts = array(
    "toolname:", // Required value
    "prefspath::" // Optional value
);
$options = getopt($shortopts, $longopts);

$toolName = (isset($argv[1])) ? $argv[1] : $toolName;
$toolName = (isset($options['t'])) ? $options['t'] : "";
$toolName = (isset($options['toolname'])) ? $options['toolname'] : $toolName;

if (!$toolName) {
    die("You must specify the tool name used to define the custom command support in PhpStorm:\nFor instance \"php makestorm.php artisan\"\n");
}

$stormToolPath = (isset($options['p'])) ? $options['p'] : "";
$stormToolPath = (isset($options['prefspath'])) ? $options['prefspath'] : $stormToolPath;


if (!$stormToolPath) {
    //get the location of the PhpStorm Settings
    switch (strtolower(php_uname("s"))) {
        case "darwin":
            $stormToolPath = getenv('HOME') . "/Library/Preferences/WebIde60/commandlinetools/";
            break;
        case "winnt":
        case "win32":
            $stormToolPath = "%systemdrive%\\Documents and Settings\\%username%\\.WebIde60\\config\\";
            break;
        case "windows":
            $stormToolPath = "%systemdrive%\\Users\\%username%\\.WebIde60\\config\\";
            break;
        default:
            $stormToolPath = getenv('HOME') . "/.WebIde60/config/";
    }
}

$stormToolFile = $stormToolPath . $toolName . ".xml";

$newXml = simplexml_load_file($stormToolFile, "SimpleXMLExtended");
if (!$newXml) {
    die("ERROR: Couldn't load the file: '" . $stormToolFile . "' \n");
}
//are we running it again on a file we already have commands for
if (isset($newXml->command)) {
    //delete the existing commands
    unset($newXml->command);
}

//let's get the command to run
$php = isset($php) ? $php : "php";
$attributes = $newXml->attributes();
$invoker = (string)$attributes["invoke"];
$invoker = preg_replace('/^\"\$PhpExecutable\$\"/', $php, $invoker);

//getthe command list by running the command in the shell
$commandInput = `$invoker list --xml`;

//load the input
$input = simplexml_load_string($commandInput);
if (!$input) {
    die("ERROR: The command: '" . $invoker . "' \nwasn't invoked correctly (but you probably knew that by now).");
}

//turn the resulting definition objects into an array (the objects are a pain in this case)
$defs = $input->commands;
$json_string = json_encode($defs);
$result_array = json_decode($json_string, true);

//transform the XML input for each command into the proper format for output
foreach ($result_array['command'] as $def) {
    $name    = $def["@attributes"]["name"];
    $newNode = $newXml->addChild("command");
    //set the command name
    $newNode->addChild("name", $name);
    //build the command help
    $help = "<h2>$name</h2>";
    $help .= "<p>Usage:<br> " . $def['usage'] . " </p>";
    $help .= "<p>" . $def['description'] . " </p>";
    $help .= getArguments($def["arguments"]);
    $help .= getOptions($def["options"]['option']);
    $help .= "<p>help:<br> ";
    $help .= is_array($def['help']) ? "" : $def['help'];
    $help .= " </p>";
    //add the help node
    $helpNode = $newNode->addChild("help");
    //add the cdata
    $helpNode->addCData($help);
    //add the command parameters, if we have any
    $params = makeParams($def["arguments"]);
    if ($params) {
        $newNode->addChild("params", $params);
    }
}

$writeMe = $newXml->asXML($stormToolFile);
if (!$writeMe) {
    die("ERROR writing the $toolName file to path: $stormToolPath \n");
}

exit("Wrote a new $toolName" . ".xml file to $stormToolPath \n");

/**
 * Takes an array of parameter arguments and returns the correctly formatted string for help
 *
 * @param array $args
 *
 * @return string
 */
function getArguments(array $args)
{
    if (!count($args)) {
        return "";
    }
    $str = "<table> <tr><td><strong>Arguments:</strong></td></tr> <params>";
    foreach ($args as $arg) {
        $str .= "<tr> <td>/" . $arg['@attributes']['name'] . "</td> <td>" . $arg['description'] . "</td> </tr>";
    }

    return $str .= "</table><br>";
}

/**
 * Takes an array of command options and returns the correctly formatted string
 *
 * @param array $options
 *
 * @return string
 */
function getOptions(array $options)
{
    if (!count($options)) {
        return "";
    }
    $str = "<table> <tr><td><strong>Options:</strong></td></tr> ";
    foreach ($options as $option) {
        $str .= "<tr> <td>/" . $option['@attributes']['name'] . "</td> <td>" . $option['description'] . "</td> </tr>";
    }

    return $str .= "</table><br>";
}

/**
 * Takes an array of parameter arguments and returns the correctly formatted string
 *
 * @param array $args
 *
 * @return string
 */
function makeParams(array $args)
{
    if (!count($args)) {
        return "";
    }
    $str = "";
    foreach ($args as $arg) {
        $str .= $arg['@attributes']['name'];
        $str .= ($arg['@attributes']['is_required']) ? " " : "[=null] ";
    }

    return rtrim($str);
}

/**
 * Class SimpleXMLExtended
 *
 * This is just here to add cdata
 */
class SimpleXMLExtended extends SimpleXMLElement
{

    /**
     * Adds cdata node to the current node
     *
     * @param $cdata_text String
     */
    public function addCData($cdata_text)
    {
        $node = dom_import_simplexml($this);
        $no   = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }
}

/*
 * This is what each entry for the command should look like
 *
 * <table> <tr><td><strong>Options:</strong></td></tr> <tr> <td>--xml</td> <td>To output help as XML</td> </tr> <tr> <td>--raw</td> <td>To output raw command list</td> </tr> </table> <br>
//<command>
//    <name>about</name>
//    <help>
        <![CDATA[
        <h2>about</h2>
        <p>Usage:<br> about </p>
        <p>$def->description </p>
        <table>
                <tr><td><strong>Options:</strong></td></tr>
                <tr>
                        <td>--help (-h)</td>
            <td>Display this help message.</td>
                </tr>
                <tr> <td>--quiet (-q)</td> <td>Do not output any message.</td> </tr>
                <tr> <td>--verbose (-v)</td> <td>Increase verbosity of messages.</td> </tr>
                <tr> <td>--version (-V)</td> <td>Display this application version.</td> </tr>
                <tr> <td>--ansi</td> <td>Force ANSI output.</td> </tr>
                <tr> <td>--no-ansi</td> <td>Disable ANSI output.</td> </tr>
                <tr> <td>--no-interaction (-n)</td> <td>Do not ask any interactive question.</td> </tr>
                <tr> <td>--profile</td> <td>Display timing and memory usage information</td> </tr>
                <tr> <td>--working-dir (-d)</td> <td>If specified, use the given directory as working directory.</td> </tr>
        </table> <br>
        <p>Help:<br/> <info>php composer.phar about</info></p>
        ]]>
</help>
//  </command>*/