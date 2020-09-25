<?php

namespace pedhot\SkyBlockUI;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use room17\SkyBlock\island\IslandFactory;
use room17\SkyBlock\session\SessionLocator;
use room17\SkyBlock\SkyBlock;
use room17\SkyBlock\utils\Invitation;
use room17\SkyBlock\utils\message\MessageContainer;

class Main extends PluginBase implements Listener
{

    public function onEnable()
    {
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        $cmd = $command->getName();
        $session = SkyBlock::getInstance()->getSessionManager()->getSession($sender);
        if ($cmd === "sbui"){
            if (!$session->hasIsland()){
                $this->onCreate($sender);
            }else{
                $this->onManage($sender);
            }
        }
        return true;
    }

    public function onCreate(Player $sender)
    {
        $form = new SimpleForm(function (Player $sender, $data){
            if ($data === null) {
                return true;
            }
            $session = SkyBlock::getInstance()->getSessionManager()->getSession($sender);
            switch ($data){
                case '0':
                    IslandFactory::createIslandFor($session, "Basic");
                    $sender->sendMessage(TextFormat::GREEN . "Success Create Island");
                    break;
                case '1':
                    IslandFactory::createIslandFor($session, "Lost");
                    $sender->sendMessage(TextFormat::GREEN . "Success Create Island");
                    break;
                case '2':
                    IslandFactory::createIslandFor($session, "Palm");
                    $sender->sendMessage(TextFormat::GREEN . "Success Create Island");
                    break;
            }
        });
        $form->setTitle("SkyBlockUI");
        $form->addButton("Create Basic Island");
        $form->addButton("Create Lost Island");
        $form->addButton("Create Palm Island");
        $form->sendToPlayer($sender);
        return $form;
    }

    public function onManage(Player $sender)
    {
        $form = new SimpleForm(function (Player $sender, $data){
            if ($data === null) {
                return true;
            }
            $session = SkyBlock::getInstance()->getSessionManager()->getSession($sender);
            switch ($data){
                case '0':
                    $session->getPlayer()->teleport($session->getIsland()->getLevel()->getSpawnLocation());
                    $sender->sendMessage(TextFormat::YELLOW . "Welcome to Your island");
                    break;
                case '1':
                    $this->onManageI($sender);
                    break;
                case '2':
                    $this->onManageF($sender);
                    break;
                case '3':
                    IslandFactory::disbandIsland($session->getIsland());
                    $sender->sendMessage(TextFormat::RED . "Success delete your islands");
                    break;
            }
        });
        $form->setTitle("SkyBlockUI");
        $form->setContent("Manage your islands");
        $form->addButton("HOME");
        $form->addButton("MANAGE");
        $form->addButton("HELPER");
        $form->addButton("DELETE");
        $form->sendToPlayer($sender);
        return $form;
    }

    public function onManageI(Player $sender)
    {
        $session = SkyBlock::getInstance()->getSessionManager()->getSession($sender);
        $form = new SimpleForm(function (Player $sender, $data){
            if ($data === null) {
                return true;
            }
            switch ($data){
                case '0':
                    $session->getIsland()->setLocked(!$session->getIsland()->isLocked());
                    $session->getIsland()->save();
                    $session->sendTranslatedMessage(new MessageContainer($session->getIsland()->isLocked() ? "ISLAND_LOCKED" : "ISLAND_UNLOCKED"));
                    break;
            }
        });
        $form->setTitle("SkyBlockUI");
        if ($session->getIsland()->isLocked()) {
            $form->addButton("Unlock Island");
        } else {
            $form->addButton("Lock Island");
        }
        $form->sendToPlayer($sender);
        return $form;
    }

    public function onManageF(Player $sender)
    {
        $form = new SimpleForm(function (Player $sender, $data){
            if ($data === null) {
                return true;
            }
            switch ($data){
                case '0':
                    $this->onManageAF($sender);
                    break;
                case '0':
                    $this->onManageRF($sender);
                    break;
            }
        });
        $form->setTitle("SkyBlockUI");
        $form->addButton("ADD HELPER");
        $form->addButton("REMOVE HELPER");
        $form->sendToPlayer($sender);
        return $form;
    }

    public function onManageAF(Player $sender)
    {
        $form = new CustomForm(function (Player $sender, $data){
            $result = $data[0];
            if ($result === null)
                return;
            $p = Server::getInstance()->getPlayer((string) $result);
            $session = SkyBlock::getInstance()->getSessionManager()->getSession($p);
            if ($p !== null){
                if ($p instanceof Player){
                    $invitedPlayerSession = $session;
                    $session->sendInvitation(new Invitation($session, $invitedPlayerSession));
                }
            }else{
                $session->sendTranslatedMessage(new MessageContainer("NOT_ONLINE_PLAYER", [
                    "name" => $data[0]
                ]));
            }
        });
        $form->setTitle("SkyBlockUI");
        $form->addLabel("Please write the IGN on the input box below");
        $form->addInput("Player Name:", "APGaming2308");
        $form->sendToPlayer($sender);
        return $form;
    }

    public function onManageRF(Player $sender)
    {
        $form = new CustomForm(function (Player $sender, $data){
            $result = $data[0];
            if ($result === null)
                return;
            $p = Server::getInstance()->getPlayer((string) $result);
            $session = SkyBlock::getInstance()->getSessionManager()->getSession($p);
            if ($p instanceof Player){
                $playerSession = SessionLocator::getSession($p);
                if ($playerSession->getIsland() === $session->getIsland()){
                    $sender->sendMessage(TextFormat::RED . "You cannot bannish a member");
                }elseif (in_array($p, $session->getIsland()->getPlayersOnline())){
                    $p->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());
                    $playerSession->sendTranslatedMessage(new MessageContainer("BANISHED_FROM_THE_ISLAND"));
                    $session->sendTranslatedMessage(new MessageContainer("YOU_BANISHED_A_PLAYER", [
                        "name" => $playerSession->getName()
                    ]));
                }else{
                    $session->sendTranslatedMessage(new MessageContainer("NOT_A_VISITOR", [
                        "name" => $playerSession->getName()
                    ]));
                }
            }else {
                $session->sendTranslatedMessage(new MessageContainer("NOT_ONLINE_PLAYER", [
                    "name" => (string) $result
                ]));
            }
        });
        $form->setTitle("SkyBlockUI");
        $form->addLabel("Please write the IGN on the input box below");
        $form->addInput("Player Name:", "APGaming2308");
        $form->sendToPlayer($sender);
        return $form;
    }

}
