<?php

namespace PocketMoney;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use PocketMoney\Error\SimpleError;
use PocketMoney\constants\PlayerType;

class PocketMoney extends PluginBase
{
	public function onLoad()
	{
	}

	public function onEnable()
	{
        PocketMoneyAPI::init();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

	public function onDisable()
	{
        PocketMoneyAPI::getAPI()->saveConfig();
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args)
	{
		if (strtolower($sender->getName()) !== "console") return $this->onCommandByUser($sender, $command, $label, $args);
		switch ($command->getName()) {
			case "money":
				$subCommand = strtolower(array_shift($args));
				switch ($subCommand) {
					case "":
					case "help":
						$sender->sendMessage("/money help( or /money )");
						$sender->sendMessage("/money view <account>");
						$sender->sendMessage("/money create <account>");
						$sender->sendMessage("/money hide <account>");
						$sender->sendMessage("/money unhide <account>");
						$sender->sendMessage("/money set <target> <amount>");
						$sender->sendMessage("/money grant <target> <amount>");
						$sender->sendMessage("/money top <amount>");
						$sender->sendMessage("/money stat");
                        break;

					case "view":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money view <account>");
							break;
						}

                        $money = PocketMoneyAPI::getAPI()->getMoney($account);
                        $type = PocketMoneyAPI::getAPI()->getType($account);
                        $hide = PocketMoneyAPI::getAPI()->getHide($account);

						if ($money instanceof SimpleError) {
							$sender->sendMessage($money->getDescription());
							break;
						}
                        if ($type instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($hide instanceof SimpleError) {
                            $sender->sendMessage($hide->getDescription());
                            break;
                        }
						$type = ($type === PlayerType::Player) ? "Player" : "Non-player";
                        $hide = ($hide === false) ? "false" : "true";
						$sender->sendMessage("\"$account\" money:$money PM type:$type hide:$hide");
						break;

					case "create":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money create <account>");
							break;
						}

                        if (($err = PocketMoneyAPI::getAPI()->createAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully created \"$account\"");
						break;

					case "hide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money hide <account>");
							break;
						}

                        if (($err = PocketMoneyAPI::getAPI()->hideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully hid \"$account\"");
						break;

                    case "unhide":
                    case "expose":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money unhide <account>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->unhideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully unhid \"$account\"");
						break;

					case "set":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money set <target> <amount>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->setMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
						$sender->sendMessage("[set] Done!");
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $player->sendMessage("Your money was changed to $amount PM by admin");
                        }
						break;

					case "grant":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money grant <target> <amount>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->grantMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("[grant] Done!");
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $player->sendMessage("You were granted $amount PM by admin");
                        }
                        break;
						
					case "top":
						$amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage("Usage: /money top <amount>");
							break;
						}
                        $sender->sendMessage("Millionaires");
                        $sender->sendMessage("-* ======= *-");
                        $rank = 1;
						foreach (PocketMoneyAPI::getAPI()->getRanking($amount) as $name => $money) {
                            $sender->sendMessage("#$rank : $name $money PM");
							$rank++;
						}
                        $sender->sendMessage("-* ======= *-");
						break;
					case "stat":
						$totalMoney = PocketMoneyAPI::getAPI()->getTotalMoney();
                        $accountNum = PocketMoneyAPI::getAPI()->getNumberOfAccount();
						$avr = floor($totalMoney / $accountNum);
                        $sender->sendMessage("Circulation:$totalMoney Average:$avr Accounts:$accountNum");
						break;

					default:
                        $sender->sendMessage("\"/money $subCommand\" dose not exist");
						break;
				}
				return true;

            default:
                return false;
		}
	}

	private function onCommandByUser(CommandSender $sender, Command $command, $label, array $args)
	{
        switch ($command->getName()) {
            case "money":
                $subCommand = strtolower(array_shift($args));
                switch ($subCommand) {
                    case "":
                        $money = PocketMoneyAPI::getAPI()->getMoney($sender->getName());
                        $sender->sendMessage("$money PM");
                        break;
                    case "help":
                        $sender->sendMessage("/money help");
                        $sender->sendMessage("/money view <account>");
                        $sender->sendMessage("/money pay <target>");
                        $sender->sendMessage("/money create <account>");
                        $sender->sendMessage("/money hide <account>");
                        $sender->sendMessage("/money unhide <account>");
                        $sender->sendMessage("/money wd <target> <amount>");
                        $sender->sendMessage("/money top <amount>");
                        $sender->sendMessage("/money stat");
                        break;

                    case "view":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money view <account>");
                            break;
                        }

                        $money = PocketMoneyAPI::getAPI()->getMoney($account);
                        $type = PocketMoneyAPI::getAPI()->getType($account);
                        $hide = PocketMoneyAPI::getAPI()->getHide($account);
                        if ($money instanceof SimpleError) {
                            $sender->sendMessage($money->getDescription());
                            break;
                        }
                        if ($type instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($hide instanceof SimpleError) {
                            $sender->sendMessage($hide->getDescription());
                            break;
                        }
                        $type = ($type === PlayerType::Player) ? "Player" : "Non-player";
                        $hide = ($hide === false) ? "false" : "true";
                        $sender->sendMessage("\"$account\" money:$money PM type:$type hide:$hide");
                        break;

                    case "pay":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage("Usage: /money pay <target> <amount>");
                            break;
                        }
                        if (($err = PocketMoneyAPI::getAPI()->payMoney($sender->getName(), $target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("you -> $target: $amount PM");
                        if (($targetPlayer = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $targetPlayer->sendMessage($sender->getName()." -> you: $amount PM");
                        }
                        break;

                    case "withdraw":
                    case "wd":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage("Usage: /money wd <target> <amount>");
                            break;
                        }
                        if (($type = PocketMoneyAPI::getAPI()->getType($target)) instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($type !== PlayerType::NonPlayer) {
                            $sender->sendMessage("You can withdraw money from only non-player account");
                            break;
                        }
                        if (($err = PocketMoneyAPI::getAPI()->payMoney($target, $sender->getName(), $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("$target -> you: $amount PM");
                        break;

                    case "create":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money create <account>");
                            break;
                        }
                        if (($err = PocketMoneyAPI::getAPI()->createAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully created \"$account\"");
                        break;

                    case "hide":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage('Usage: /money hide <account>');
                            break;
                        }
                        if (($err = PocketMoneyAPI::getAPI()->hideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully hid \"$account\"");
                        break;

                    case "unhide":
                    case "expose":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money unhide <account>");
                            break;
                        }
                        if (($err = PocketMoneyAPI::getAPI()->unhideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully unhid \"$account\"");
                        break;

                    case "top":
                        $amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage("Usage: /money top <amount>");
                            break;
                        }
                        $sender->sendMessage("Millionaires");
                        $sender->sendMessage("-* ======= *-");
                        $rank = 1;
                        foreach (PocketMoneyAPI::getAPI()->getRanking($amount) as $name => $money) {
                            $sender->sendMessage("#$rank : $name $money PM");
                            $rank++;
                        }
                        $sender->sendMessage("-* ======= *-");
                        break;
                    case "stat":
                        $totalMoney = PocketMoneyAPI::getAPI()->getTotalMoney();
                        $accountNum = PocketMoneyAPI::getAPI()->getNumberOfAccount();
                        $avr = floor($totalMoney / $accountNum);
                        $sender->sendMessage("Circulation:$totalMoney Average:$avr Accounts:$accountNum");
                        break;

                    default:
                        $sender->sendMessage("\"/money $subCommand\" dose not exist");
                        break;
                }
                return true;

            default:
                return false;
        }
	}
}