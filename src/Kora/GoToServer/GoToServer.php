<?php
# Cấm copy dưới mọi hình thức     

namespace Kora\GoToServer;

use pocketmine\event\player\{PlayerJoinEvent,PlayerInteractEvent,PlayerQuitEvent,PlayerDropItemEvent};
use pocketmine\command\{Command,CommandSender,ConsoleCommandSender};
use pocketmine\item\enchantment\{Enchantment,EnchantmentInstance};
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\{Server,Player};
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;

use Kora\GoToServer\libs\jojoe77777\FormAPI\SimpleForm;

class GoToServer extends PluginBase implements Listener {

    private $config;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $this->servers = $this->config->get("Servers");
        $this->getLogger()->info("GoToServer has been enabled!");
        $selectorEnable = $this->config->get("Selector-Support");
        if ($selectorEnable == true) {
            $this->selectorSupport = true;
        }
        else {
            $this->selectorSupport = false;
            $this->getLogger()->notice("§eSelector item support turned off in config! Disabling selector...");
        }
        foreach ($this->servers as $server) {
            $value = explode(":", $server);
            if(isset($value[3])){
                switch($value[3]){
                    case'url':
                        break;
                    case'path':
                        break;
                    default:
                        $this->getLogger()->notice("Invalid image type! Rank: ".$value[0]."§r Image type: ".$value[3]." not supported. ");
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() == "servers"){
            if ($sender instanceof Player) {
                $this->serverList($sender);
            }
            else{
                $sender->sendMessage(" ".$this->config->get("UI-Title"));
                foreach ($this->servers as $server){
                    $value = explode(":", $server);
                    $value = str_replace("&", "§", $value);
                    $sender->sendMessage("§eMáy chủ: ".$value[0]."§r§e | IP: ".$value[1]." | Port: ".$value[2]);
                }
            }
        }
        return true;
    }

    public function serverList($player){
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $player, $data){
            if ($data === null){
                return;
            }
            else{
                $value = explode(":", $this->servers[$data]);
                $value = str_replace("&", "§", $value);
                $this->getServer()->getCommandMap()->dispatch($player, 'transferserver '.$value[1].' '.$value[2]);
            }
            return true;
        });
        $form->setTitle($this->config->get("UI-Title"));
        $form->setContent($this->config->get("UI-Message"));
        foreach ($this->servers as $server) {
            $value = explode(":", $server);
            $value = str_replace("&", "§", $value);
            if(isset($value[3])){
                if($value[3] == "url"){
                    $form->addButton($value[0], 1, "https://".$value[4]);
                }
                if($value[3] == "path"){
                    $form->addButton($value[0], 0, $value[4]);
                }
            }
            else{
                $form->addButton($value[0]);
            }
        }
        $form->sendToPlayer($player);
    }

    public function onJoin(PlayerJoinEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $enchantment = Enchantment::getEnchantment(0);
            $enchInstance = new EnchantmentInstance($enchantment, 1);
            $itemType = $this->config->get("Selector-Item");
            $item = Item::get($itemType);
            $item->setCustomName("§o$selectorText");
            $item->addEnchantment($enchInstance);
            $player->getInventory()->addItem($item);
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $items = $player->getInventory()->getContents();
        $selectorText = $this->config->get("Selector-Name");
        $selectorText = str_replace("&", "§", $selectorText);
        foreach ($items as $target) {
            if ($target->getCustomName() == "§o$selectorText") {
                $player->getInventory()->remove($target);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $itemType = $this->config->get("Selector-Item");
            $item = $player->getInventory()->getItemInHand();
            if ($item->getCustomName() == "§o$selectorText" && $item->getId() == $itemType){
                $this->serverList($player);
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $itemType = $this->config->get("Selector-Item");
            $item = $player->getInventory()->getItemInHand();
            if ($item->getCustomName() == "§o$selectorText" && $item->getId() == $itemType){
                $event->setCancelled();
            }
        }
    }
}
