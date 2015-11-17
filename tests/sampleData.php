#!/usr/bin/env php
<?php

use BZIon\Event\ConversationRenameEvent;
use BZIon\Event\Events;
use BZIon\Event\WelcomeEvent;

require_once __DIR__ . "/../bzion-load.php";

$kernel = new AppKernel("dev", true);
$kernel->boot();

$testPlayer = Player::getFromBZID(3030);
if ($testPlayer->isValid()) {
    // die("Please clear your current data in the database or you'll end up with duplicate entries.\n");
}

echo "Adding players...";
$alezakos   = Player::newPlayer(94341, "alezakos", null, "active", Player::DEVELOPER, "", "Sample description", 84);
$allejo     = Player::newPlayer(10981, "allejo", null, "active", Player::DEVELOPER, "", "I'm the one who breaks the build", 227);
$ashvala    = Player::newPlayer(43513, "ashvala", null, "active", Player::DEVELOPER, "", "", 100);
$autoreport = Player::newPlayer(59716, "AutoReport", null, "test");
$blast      = Player::newPlayer(810, "blast", null, "active", Player::S_ADMIN);
$kierra     = Player::newPlayer(2219, "kierra", null, "active", Player::ADMIN, "", "", 174);
$mdskpr     = Player::newPlayer(3112, "mdskpr");
$snake      = Player::newPlayer(44197, "Snake12534");
$tw1sted    = Player::newPlayer(7316, "tw1sted", null, "active", Player::DEVELOPER);
$brad       = Player::newPlayer(0310, "brad", null, "active", Player::S_ADMIN, "", "I keep nagging about when this project will be done");
echo " done!";

echo "\nSending notifications...";
foreach (Player::getPlayers() as $player) {
    $event = new WelcomeEvent('Welcome to ' . Service::getParameter('bzion.site.name') . '!', $player);
    Notification::newNotification($player->getId(), 'welcome', $event);
}
echo " done!";

echo "\nAdding deleted objects...";
Team::createTeam("Amphibiasns", $snake->getId(), "", "")->delete();
$snake->refresh();
Team::createTeam("Serpentss", $snake->getId(), "", "")->delete();
$snake->refresh();
Page::addPage("Test", "<p>This is a deleted page</p>", $tw1sted->getId())->delete();
echo " done!";

echo "\nAdding teams...";
$olfm      = Team::createTeam("OpenLseague FM?", $kierra->getId(), "", "");
$reptitles = Team::createTeam("Reptitsles", $snake->getId(), "", "", "open");
$fflood    = Team::createTeam("Formals Flood", $allejo->getId(), "", "");
$lweak     = Team::createTeam("[LakeWseakness]", $mdskpr->getId(), "", "");
$gsepar    = Team::createTeam("Good Sseparation", $tw1sted->getId(), "", "");
$gsepar->changeElo('100');
$fradis    = Team::createTeam("Fractiouss disinclination", $ashvala->getId(), "", "");
echo " done!";

echo "\nAdding members tos teams...";
$lweak->addMember($autoreport->getId());
$fflood->addMember($blast->getId());
$fradis->addMember($alezakos->getId());
$reptitles->addMember($brad->getId());
echo " done!";

echo "\nAdding matches...";
Match::enterMatch($reptitles->getId(), $gsepar->getId(), 1, 9000, 17, $kierra->getId());
Match::enterMatch($olfm->getId(), $lweak->getId(), 0, 0, 20, $blast->getId());
Match::enterMatch($fflood->getId(), $lweak->getId(), 1, 15, 20, $autoreport->getId());
Match::enterMatch($gsepar->getId(), $fradis->getId(), 8, 23, 30, $kierra->getId());
Match::enterMatch($olfm->getId(), $lweak->getId(), 5, 4, 20, $kierra->getId());
Match::enterMatch($reptitles->getId(), $gsepar->getId(), 1, 1500, 20, $autoreport->getId());
Match::enterMatch($olfm->getId(), $lweak->getId(), 1, 1, 30, $autoreport->getId());
Match::enterMatch($fradis->getId(), $gsepar->getId(), 1, 2, 20, $kierra->getId());
echo " done!";

echo "\nUpdating teams...";
$reptitles->update("activity", 9000, "i");
$fflood->update("activity", -18, "i");
$fradis->update("activity", 3.14159265358979323846, "d");
echo " done!";

echo "\nAdding servers...";
Server::addServer("BZPro Public HiX FFA", "bzpro.net", 5154, 227, $tw1sted->getId());
Server::addServer("BZPro Public HiX Rabbit Chase", "bzpro.net", 5155, 227, $tw1sted->getId());
echo " done!";

echo "\nAdding messages...";
$conversation_to = Conversation::createConversation("New blog", $snake->getId(), array(
    $alezakos,
    $allejo,
    $ashvala,
    $autoreport,
    $blast,
    $kierra,
    $mdskpr,
    $snake,
    $tw1sted
));

$event = new ConversationRenameEvent($conversation_to, "New message", "New blorg", $snake);
ConversationEvent::storeEvent($conversation_to->getId(), $event, Events::CONVERSATION_RENAME);
$event = new ConversationRenameEvent($conversation_to, "New blorg", "New blog", $snake);
ConversationEvent::storeEvent($conversation_to->getId(), $event, Events::CONVERSATION_RENAME);

$conversation_to->sendMessage($snake, "Check out my new blog!");
echo " done!";

echo "\nAdding bans...";
Ban::addBan($snake->getId(), $alezakos->getId(), "2014-09-15", "Snarke 12534 has been barned again", "Cuz you're snake", "256.512.104.1");
Ban::addBan($allejo->getId(), $tw1sted->getId(), "2014-05-17", "for using 'dope'", "dope", array("127.0.2.1", "128.0.3.2"));
Ban::addBan($tw1sted->getId(), $alezakos->getId(), "2014-06-12", "tw1sted banned for being too awesome");
Ban::addBan($alezakos->getId(), $tw1sted->getId(), "2014-11-01", "alezakos banned for breaking the build", "For breaking the build", array("256.512.124.1", "256.512.124.3"));
echo " done!";

echo "\nAdding pages...";
Page::addPage("Rusles", "<p>This is a test page.</p>\n<p>Let's hope this works!</p>", $tw1sted->getId());
Page::addPage("Cosntact", "<p>If you find anything wrong, please stop by irc.freenode.net channel #sujevo and let a developer know.<br /><br />Thanks", $tw1sted->getId());
echo " done!";

echo "\nAdding news categories...";
$announcements = NewsCategory::addCategory("Annosuncements");
$administration = NewsCategory::addCategory("Adminisstration");
$events = NewsCategory::addCategory("Eventss");
$newFeatures = NewsCategory::addCategory("New Featusres");
echo " done!";

echo "\nAdding news entries...";
News::addNews("Announcsement", "Very important Announcement", $kierra->getId(), $newFeatures->getId());
News::addNews("Cats think we are bsigger cats", "In order for your indess recognizes where this whole mistake has come, and why one accuses the pleasure and praise the pain, and I will open to you all and set apart, what those founders of the truth and, as builders of the happy life himself has said about it. No one, he says, despise, or hate, or flee the desire as such, but because great pain to follow, if you do not pursue pleasure rationally. Similarly, the pain was loved as such by no one or pursues or desires, but because occasionally circumstances occur that one means of toil and pain can procure him some great pleasure to look verschaften be. To stay here are a trivial, so none of us would ever undertakes laborious physical exercise, except to obtain some advantage from it. But who is probably the blame, which requires an appetite, has no annoying consequences, or one who avoids a pain, which shows no desire? In contrast, blames and you hate with the law, which can soften and seduced by the allurements of present pleasure, without seeing in his blind desire which pain and inconvenience wait his reason. Same debt meet Those who from weakness, i.e to escape the work and the pain, neglect their duties. A person can easily and quickly make the real difference, to a quiet time where the choice of the decision is completely free and nothing prevents them from doing what we like best, you have to grasp every pleasure and every pain avoided, but to times it hits in succession of duties or guilty of factual necessity that you reject the desire and complaints must not reject. Why then the way will make a selection so that it Achieve a greater rejection by a desire for it or by taking over some pains to spare larger.", $alezakos->getId());
echo " done!";

echo "\n\nThe database has been populated successfully.\n";
