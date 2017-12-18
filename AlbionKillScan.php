<?php
require 'vendor/autoload.php';

use Albion\AlbionApi;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Intervention\Image\ImageManagerStatic as Image;

chdir(dirname($_SERVER['argv'][0]));

$albion = new AlbionApi();
$client = $albion->gameInfoClient();

$lastEventFile = 'lastEvent.txt';
$lastEvent = file_get_contents($lastEventFile);
//Change with your own Discord Webhook
$webhookURL = 'https://discordapp.com/api/webhooks/SET_YOUR_OWN_DISCORD_WEBHOOK_URL';

$params['limit'] = 50;
$params['offset'] = 0;
$data = $client->recentEvents($params)->get();
//Change with your alliance TAG
$alliance = 'RIXE';
$victory = false;
$itemURL = 'https://gameinfo.albiononline.com/api/gameinfo/items/';

$reversedData = array_reverse($data);

foreach ($reversedData as $event) {
    //You may change it if you just want to set the killboard for a guild
    if ($event->Killer->AllianceName == $alliance || $event->Victim->AllianceName == $alliance) {
        if ($event->EventId > $lastEvent) {
            if ($event->TotalVictimKillFame > 0) {
                $author = $event->Killer->Name . ' a tué ' . $event->Victim->Name . ' !';
                $assists = $event->numberOfParticipants - 1;
                if ($assists > 1) {
                    $title = 'Avec l\'assistance de : ';
                    $i = 0;
                    usort($event->Participants, 'compare');
                    foreach ($event->Participants as $participant) {
                        if ($participant->Name !== $event->Killer->Name) {
                            if ($i >= 3) {
                                $title .= ' et ' . ($event->numberOfParticipants - 4) . ' ' . ((($event->numberOfParticipants - 4) == 1) ? 'autre joueur' : 'autres joueurs');
                                break;
                            }
                            if ($i > 0) {
                                $title .= ', ';
                            }
                            $title .= $participant->Name;
                            if ($participant->DamageDone > 0) {
                                $title .= ' (' . number_format($participant->DamageDone, 0, ',', ' ') . ' dégâts)';
                            }
                            $i++;
                        }
                    }
                } else {
                    $title = 'SOLO KILL';
                }
                $itemCount = 0;
                foreach ($event->Victim->Inventory as $inventory) {
                    if ($inventory !== null) {
                        $itemCount++;
                    }
                }
                foreach ($event->Victim->Equipment as $equipment) {
                    if ($equipment !== null) {
                        $itemCount++;
                    }
                }
                $title .= ' détruisant ' . $itemCount . ' items.';
                $description = '+' . number_format($event->TotalVictimKillFame, 0, ',', ' ') . ' renommée';
                $url = 'https://albiononline.com/en/killboard/kill/' . $event->EventId;
                $killerInfo = ($event->Killer->AllianceName) ? '[' . $event->Killer->AllianceName . '] ' : '';
                $killerInfo .= ($event->Killer->GuildName) ? $event->Killer->GuildName : '<none>';
                $victimInfo = ($event->Victim->AllianceName) ? '[' . $event->Victim->AllianceName . '] ' : '';
                $victimInfo .= ($event->Victim->GuildName) ? $event->Victim->GuildName : '<none>';
                $killerIP = number_format($event->Killer->AverageItemPower, 0, ',', ' ');
                $victimIP = number_format($event->Victim->AverageItemPower, 0, ',', ' ');
                //Don't forget to change this too if you set the killboard for a guild
                if ($event->Killer->AllianceName == $alliance) {
                    $victory = true;
                }
                $footer = '#' . $event->EventId;
                $timestamp = $event->TimeStamp;
                $thumbnail = ($event->Killer->Equipment->MainHand) ? $event->Killer->Equipment->MainHand->Type . '.png?count=' . $event->Killer->Equipment->MainHand->Count . '&quality=' . $event->Killer->Equipment->MainHand->Quality : null;
                $MainHand = ($event->Victim->Equipment->MainHand) ? $event->Victim->Equipment->MainHand->Type . '.png?count=' . $event->Victim->Equipment->MainHand->Count . '&quality=' . $event->Victim->Equipment->MainHand->Quality : null;
                $OffHand = ($event->Victim->Equipment->OffHand) ? $event->Victim->Equipment->OffHand->Type . '.png?count=' . $event->Victim->Equipment->OffHand->Count . '&quality=' . $event->Victim->Equipment->OffHand->Quality : null;
                $Head = ($event->Victim->Equipment->Head) ? $event->Victim->Equipment->Head->Type . '.png?count=' . $event->Victim->Equipment->Head->Count . '&quality=' . $event->Victim->Equipment->Head->Quality : null;
                $Armor = ($event->Victim->Equipment->Armor) ? $event->Victim->Equipment->Armor->Type . '.png?count=' . $event->Victim->Equipment->Armor->Count . '&quality=' . $event->Victim->Equipment->Armor->Quality : null;
                $Shoes = ($event->Victim->Equipment->Shoes) ? $event->Victim->Equipment->Shoes->Type . '.png?count=' . $event->Victim->Equipment->Shoes->Count . '&quality=' . $event->Victim->Equipment->Shoes->Quality : null;
                $Mount = ($event->Victim->Equipment->Mount) ? $event->Victim->Equipment->Mount->Type . '.png?count=' . $event->Victim->Equipment->Mount->Count . '&quality=' . $event->Victim->Equipment->Mount->Quality : null;

                $img = Image::canvas(1302, 217);
                ($event->Victim->Equipment->MainHand) ? $img->insert($itemURL . $MainHand, 'left') : '';
                ($event->Victim->Equipment->OffHand) ? $img->insert($itemURL . $OffHand, 'left', 217, 0) : '';
                ($event->Victim->Equipment->Head) ? $img->insert($itemURL . $Head, 'left', 434, 0) : '';
                ($event->Victim->Equipment->Armor) ? $img->insert($itemURL . $Armor, 'left', 651, 0) : '';
                ($event->Victim->Equipment->Shoes) ? $img->insert($itemURL . $Shoes, 'left', 868, 0) : '';
                ($event->Victim->Equipment->Mount) ? $img->insert($itemURL . $Mount, 'left', 1085, 0) : '';
                $img->save('images/' . $event->EventId . '.png');
                $image = 'images/' . $event->EventId . '.png';

                $location = $event->Location;

                //1s sleep for each webhook call so you won't be blocked by discord limitations
                sleep(1);

                sendDiscord($webhookURL, $author, $victory, $title, $description, $url, $footer, $timestamp, $thumbnail, $killerInfo, $killerIP, $victimInfo, $victimIP, $image, $location);
                $victory = false;
            }
            $lastEvent = $event->EventId;
        }
    }
}

file_put_contents($lastEventFile, $lastEvent);

function compare($a, $b)
{
    return $a->DamageDone < $b->DamageDone;
}

function sendDiscord($webhookURL, $author, $victory, $title, $description, $url, $footer, $timestamp, $thumbnail, $killerInfo, $killerIP, $victimInfo, $victimIP, $image, $location)
{
    $webhook = new Client($webhookURL);
    $embed = new Embed();
    $embed->author($author, $url, ($victory) ? 'https://i.imgur.com/CeqX0CY.png' : 'https://albiononline.com/assets/images/killboard/kill__date.png');
    $embed->title($title);
    $embed->description($description);
    $embed->color(($victory) ? 0x008000 : 0x800000);
    $embed->footer($footer);
    $embed->timestamp($timestamp);
    $embed->field('Guilde de l\'assassin', $killerInfo, true);
    $embed->field('Guilde de la victime', $victimInfo, true);
    $embed->field('Puissance d\'objet', $killerIP, true);
    $embed->field('Puissance d\'objet', $victimIP, true);
    if ($location !== null) {
        $embed->field('Emplacement', $location, true);
    }
    $embed->thumbnail('https://gameinfo.albiononline.com/api/gameinfo/items/' . $thumbnail);
    //Change with your base URL (used for images)
    $embed->image('http://YOUR_SERVER_URL.com/albion' . $image);
    $webhook->embed($embed)->send();
}