<?php

// Error Checking
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!$_POST['process']) {
//Ask for the character file
echo "<form action='index.php' enctype='multipart/form-data' method='POST'>
<input type='hidden' name='MAX_FILE_SIZE' value='300000' />
<label for='char'>Select your DnD4e file:</label>
<input type='file' name='char' size='10' />
<input type='hidden' name='process' value='1' />
<br/><input type='submit' value='Import' />
</form>";
}

if ($_POST['process'] == 1) {

//Move user file
$dnd4efile = '/var/www/DnD4e-Converter/temp/'. basename($_FILES['char']['name']);
move_uploaded_file($_FILES['char']['tmp_name'], $dnd4efile);

//Set the character.
$filename = $_FILES['char']['name'];
$fileinfo = pathinfo($filename);
$filename = basename($filename,'.'.$fileinfo['extension']);

$path = 'temp/'.$filename.'.dnd4e';

//Load the character as an XML element
$xml = simplexml_load_file($path);

//Set character details
$details = array();
$details['name'] 	= (string)$xml->CharacterSheet->Details[0]->name;
$details['player'] 	= (string)$xml->CharacterSheet->Details[0]->Player;
$details['level'] 	= (int)$xml->CharacterSheet->Details[0]->Level;
$details['height'] 	= (string)$xml->CharacterSheet->Details[0]->Height;
$details['weight'] 	= (string)$xml->CharacterSheet->Details[0]->Weight;
$details['gender'] 	= (string)$xml->CharacterSheet->Details[0]->Gender;
$details['age'] 	= (string)$xml->CharacterSheet->Details[0]->Age;
$details['alignment'] 	= (string)$xml->CharacterSheet->Details[0]->Alignment;
$details['money'] 	= (string)$xml->CharacterSheet->Details[0]->CarriedMoney;

//Assign the stats to an array
$stats = array();
foreach ($xml->CharacterSheet->StatBlock[0]->Stat as $stat) {
	$stats[(string)$stat->alias['name']] = (string)$stat['value'];
}

//Scrub rule elements for important stuff
$feats = array();
$classfeatures = array();
$racefeatures = array();
$languages = "";

foreach ($xml->CharacterSheet->RulesElementTally[0]->RulesElement as $rule) {
	if ($rule['type'] == 'Power') $powers[] = (string)$rule['url'];
	if ($rule['type'] == 'Feat') $feats[(string)$rule['name']] = (string)$rule->specific[1];
	if ($rule['type'] == 'Racial Trait') $racefeatures[(string)$rule['name']] = (string)$rule->specific[0];
	if ($rule['type'] == 'Class Feature') $classfeatures[(string)$rule['name']] = (string)$rule->specific[0];
	if ($rule['type'] == 'Language') $languages .= (string)$rule['name']." ";
	if ($rule['type'] == 'Class') $details['class'] = (string)$rule['name'];
	if ($rule['type'] == 'Race') {
		$details['race'] = (string)$rule['name'];
		foreach ($rule->specific as $specific) {
		if ($specific['name'] == 'Vision') $details['vision'] = (string)$specific;
		}
	}
}

//Format each section of a character sheet.
$charsheet = "";

$charsheet .= "<h1>".$details['name']."</h1>";
$charsheet .= "<h2>Level ".$details['level']." ".$details['race']." ".$details['class']."</h2>";
$charsheet .= "Height: ".$details['height']." Weight: ".$details['weight']." Age: ".$details['age']."<br/>";
$charsheet .= "<strong>Initiative: ".$stats['Initiative']." Speed: ".$stats['Speed']."</strong><br/>
Passive Perception: ".$stats['Passive Perception']." Passive Insight: ".$stats['Passive Insight']."<br/>
Vision: ".$details['vision']."<br/>
Languages: ".$languages."<br/>";
$charsheet .= "<h2>Defenses</h2><table cellpadding='4'><tr><th>AC</th><th>FORT</th><th>REF</th><th>WILL</th></tr><tr><td>".$stats['AC']."</td><td>".$stats['Fortitude Defense']."</td><td>".$stats['Reflex Defense']."</td><td>".$stats['Will Defense']."</td></tr></table>";
$charsheet .= "<h2>Abilities</h2><table cellpadding='4'><tr><th>Score</th><th>Ability</th><th>Modifier</th></tr>
<tr><td>".$stats['Strength']."</td><td><b>STR</b></td><td>".$stats['Strength modifier']."</td></tr>
<tr><td>".$stats['Constitution']."</td><td><b>CON</b></td><td>".$stats['Constitution modifier']."</td></tr>
<tr><td>".$stats['Dexterity']."</td><td><b>DEX</b></td><td>".$stats['Dexterity modifier']."</td></tr>
<tr><td>".$stats['Intelligence']."</td><td><b>INT</b></td><td>".$stats['Intelligence modifier']."</td></tr>
<tr><td>".$stats['Wisdom']."</td><td><b>WIS</b></td><td>".$stats['Wisdom modifier']."</td></tr>
<tr><td>".$stats['Charisma']."</td><td><b>CHA</b></td><td>".$stats['Charisma modifier']."</td></tr>
</table>";
$surge = floor($stats['Hit Points'] / 4);
$bloodied = floor($stats['Hit Points'] / 2);
$charsheet .= "<h2>Hit Points</h2>Max HP: ".$stats['Hit Points']." Bloodied: $bloodied Surge Value: $surge Surges/Day: ".$stats['Healing Surges']."<br/>";
$charsheet .= "<h2>Skills</h2><table cellpadding='4'>
<tr><th>Skill</th><th>Ability</th><th>Bonus</th></tr>
<tr><td>Acrobatics</td><td>Dexterity</td><td>".$stats['Acrobatics']."</td></tr>
<tr><td>Arcana</td><td>Intelligence</td><td>".$stats['Arcana']."</td></tr>
<tr><td>Athletics</td><td>Strength</td><td>".$stats['Athletics']."</td></tr>
<tr><td>Bluff</td><td>Charisma</td><td>".$stats['Bluff']."</td></tr>
<tr><td>Diplomacy</td><td>Charisma</td><td>".$stats['Diplomacy']."</td></tr>
<tr><td>Dungeoneering</td><td>Wisdom</td><td>".$stats['Dungeoneering']."</td></tr>
<tr><td>Endurance</td><td>Constitution</td><td>".$stats['Endurance']."</td></tr>
<tr><td>Heal</td><td>Wisdom</td><td>".$stats['Heal']."</td></tr>
<tr><td>History</td><td>Intelligence</td><td>".$stats['History']."</td></tr>
<tr><td>Insight</td><td>Wisdom</td><td>".$stats['Insight']."</td></tr>
<tr><td>Intimidate</td><td>Charisma</td><td>".$stats['Intimidate']."</td></tr>
<tr><td>Nature</td><td>Wisdom</td><td>".$stats['Nature']."</td></tr>
<tr><td>Perception</td><td>Wisdom</td><td>".$stats['Perception']."</td></tr>
<tr><td>Religion</td><td>Wisdom</td><td>".$stats['Religion']."</td></tr>
<tr><td>Stealth</td><td>Dexterity</td><td>".$stats['Stealth']."</td></tr>
<tr><td>Streetwise</td><td>Charisma</td><td>".$stats['Streetwise']."</td></tr>
<tr><td>Theivery</td><td>Dexterity</td><td>".$stats['Thievery']."</td></tr>
</table>";


$features = "";

$features .= "<h1>Feats and Features</h1>";
$features .= "<h2>Racial Features</h2>";
foreach($racefeatures as $name => $info) {
if($info == " 1 " or $info == " @ ") $info = "";
$features .= "<strong>$name</strong><br/>$info<br/><br/>";
}
$features .= "<h2>Class Features</h2>";
foreach($classfeatures as $name => $info) {
$features .= "<strong>$name</strong><br/>$info<br/><br/>";
}
$features .= "<h2>Feats</h2>";
foreach($feats as $name => $info) {
if(preg_match('/ID_/', $info)) $info = "";
$features .= "<strong>$name</strong><br/>$info<br/><br/>";
}

$powers = "";

$powers .= "<h1>Powers</h1>";

$powers.= "<b>Bonus to hit:</b> ".$xml->CharacterSheet->PowerStats->Power[0]->Weapon[0]->AttackBonus."<br/>";
$powers.= "<b>Weapon Damage:</b> ".$xml->CharacterSheet->PowerStats->Power[0]->Weapon[0]->Damage."<br/>";

foreach($xml->CharacterSheet->PowerStats[0]->Power as $power) {
foreach($power->specific as $detail) {
if($detail['name'] == 'Flavor') $flavor = $detail;
if($detail['name'] == 'Display') $display = $detail;
if($detail['name'] == 'Power Usage') $usage = $detail;
if($detail['name'] == 'Action Type') $action = $detail;
if($detail['name'] == 'Attack Type') $attacktype = $detail;
if($detail['name'] == 'Keywords') $keywords = $detail;
if($detail['name'] == 'Requirement') $requirement = $detail;
if($detail['name'] == 'Target') $target = $detail;
if($detail['name'] == 'Attack') $attack = $detail;
if($detail['name'] == 'Hit') $hit = $detail;
if($detail['name'] == 'Effect') $effect = $detail;
if($detail['name'] == 'Miss') $miss = $detail;
if($detail['name'] == 'Special') $special = $detail;
if($detail['name'] == 'Augment') $class[] = "<b>".$detail['name'].":</b> ".$detail."<br/>";
if(preg_match('/^\s/', (string)$detail['name'])) $class[] =  "<b>".$detail['name'].":</b> ".$detail."<br/>";
}
$powers .= "<h2>".$power['name'];
$powers .= " | $display</h2>";
$powers .= "<i>$flavor</i><br/><b>$usage | $keywords</b><br/>";
$powers .= "<b>$action | $attacktype</b><br/>";
if($requirement) $powers .= "<b>Requirement:</b> $requirement<br/>";
if($target) $powers .= "<b>Target:</b> $target<br/>";
if($attack) $powers .= "<b>Attack:</b> $attack<br/>";
if($hit) $powers .= "<b>Hit:</b> $hit<br/>";
if($miss) $powers .= "<b>Miss:</b> $miss<br/>";
if($effect) $powers .= "<b>Effect:</b> $effect<br/>";
if($special) $powers .= "<b>Special:</b> $special<br/>";
if($class) foreach($class as $classpow) {$powers .= $classpow;}
unset($flavor);
unset($display);
unset($usage);
unset($action);
unset($attacktype);
unset($keywords);
unset($requirement);
unset($target);
unset($attack);
unset($hit);
unset($effect);
unset($miss);
unset($special);
unset($class);
}

$equipment = "";

$equipment .= "<h1>Equipment</h1>";
$equipment .= "<b>Money:</b> ".$details['money']."<br/><br/>";
foreach ($xml->CharacterSheet->LootTally->loot as $loot) {
if ($loot['count'] == '1') $loot['count'] = "";
if ($loot['count'] == '0') $loot = "";
$equipment .= "<b>".$loot['count']." ".$loot->RulesElement[0]['name'];
if ($loot->RulesElement[1]) {
$equipment .= " (".$loot->RulesElement[1]['name'].")</b><br/>";
foreach ($loot->RulesElement[1]->specific as $specific) {
if ($specific['name'] == 'Flavor') $equipment .= "<i>".$loot->RulesElement[1]->specific[0]."</i><br/>";
if ($specific['name'] == 'Property') $equipment .= "<b>Property:</b> ".$specific."<br/>";
if ($specific['name'] == 'Power') $equipment .= "<b>".$specific."</b><br/>";
}}
else $equipment .= "</b><br/>";
if ($loot->RulesElement[0]['type'] == 'Ritual') {
foreach ($loot->RulesElement[0]->specific as $specific) {
if ($specific['name'] == 'Flavor') $equipment .= "<i>".$specific."</i><br/>";
if ($specific['name'] == 'Category') $equipment .= "<b>".$specific."</b><br/>";
if ($specific['name'] == 'Key Skill') $equipment .= "<b>Key Skill:</b> ".$specific."<br/>";
if ($specific['name'] == 'Component Cost') $equipment .= "<b>Component Cost:</b> ".$specific."<br/>";
if ($specific['name'] == 'Duration') $equipment .= "<b>Duration:</b> ".$specific."<br/>";
if ($specific['name'] == 'Prerequisite') $equipment .= "<b>Prerequisite:</b> ".$specific."<br/>";
}
}
if ($loot) $equipment .= "<br/>";
unset($loot);
}

//Load generic HTML header and footer.
include_once('resources/ends.php');

//Create master character record
$charv = $charsheet.$features.$powers.$equipment;

//Show character record
echo $charv;

//Format array with each section as its own HTML file for epub conversion.
$char = array();
$char['charsheet'] 	= $header.$charsheet.$footer;
$char['features'] 	= $header.$features.$footer;
$char['powers'] 	= $header.$powers.$footer;
$char['equipment'] 	= $header.$equipment.$footer;

/*
$chararray = serialize($char);

//Button for approval
echo "<br/><form action='index.php' method='POST'>
<input type='hidden' name='filename' value='$filename' />
<input type='hidden' name='char' value='$chararray' />
<input type='hidden' name='process' value='2' />
<input type='submit' name='submit' value='Approve' />
</form><br/>";

}

//After the character is approved prep the output.
if ($_POST['process'] == 2) {

//Extract information from POST
$filename = $_POST['filename'];
$char = unserialize($_POST['char']);
*/

shell_exec('cp -R ./resources/epubtemplate ./output/temp');

foreach ($char as $name => $value) {
	$charfile = "output/temp/OEBPS/Text/".$name.".xhtml";
	$file = fopen($charfile, 'w') or die("can't open file");
	fwrite($file, $value);
	fclose($file);
}

//Create epub from the temp directory
shell_exec('zip -0X ./output/temp.epub ./output/temp/mimetype');
shell_exec('zip -rg ./output/temp.epub ./output/temp/META-INF -x \*.DS_Store');
shell_exec('zip -rg ./output/temp.epub ./output/temp/OEBPS -x \*.DS_Store');

//Convert the epub into a mobi
shell_exec('./resources/kindlegen/kindlegen ./output/temp.epub -o '.$filename.'.mobi');

//Remove the temp files
shell_exec('rm -Rf ./output/temp');
shell_exec('rm -f ./output/temp.epub');

// Send to Kindle, not yet implemented
// echo "<br/><a href='convert.php?publish=true'>Send to Kindle</a><br/><br/>";

}

?>
