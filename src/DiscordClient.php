<?php

namespace RPurinton\Moomoo;

require_once(__DIR__ . "/ConfigLoader.php");

use React\EventLoop\Loop;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Command;

class DiscordClient extends ConfigLoader
{

	private $discord = null;
	private $lang = null;

	function __construct()
	{
		parent::__construct();
		$this->lang = $this->config[$this->config["language"]["language"]];
		$this->config["discord"]["loop"] = Loop::get();
		$this->config["discord"]["intents"] = Intents::getDefaultIntents() | Intents::GUILD_MEMBERS;
		$this->config["discord"]['loadAllMembers'] = true;
		$this->discord = new Discord($this->config["discord"]);
		$this->discord->on("ready", $this->ready(...));
		$this->discord->run();
	}

	function __destruct()
	{
		$this->discord->close();
	}

	private function ready()
	{
		$activity = new \Discord\Parts\User\Activity($this->discord);
		$activity->name = $this->lang["playing"];
		$activity->type = 0;
		$this->discord->updatePresence($activity, false, "online", false);
		$command = new Command($this->discord, [
			'name' => $this->lang["/command"],
			'description' => $this->lang["/description"],
			'options' => [
				[
					'name' => $this->lang["/option_name"],
					'description' => $this->lang["/option_description"],
					'type' => 3,
					'required' => true
				],
			]
		]);
		$this->discord->application->commands->save($command);
		$this->discord->listenCommand('registro', $this->register(...));
	}

	private function register(Interaction $interaction)
	{
		$channel = $interaction->channel;
		$search_term = $interaction->data->options['nome exato do personagem']->value;
		$interaction->respondWithMessage(MessageBuilder::new()->setContent("Searching for \"$search_term\"..."));
		$data = json_decode(file_get_contents("https://api.mir4info.com/v6/search-exact/$search_term"), true);
		if (isset($result["error"])) return $channel->sendMessage("nome não encontrado, verifique a ortografia e tente novamente");
		$character_name = $data["name"];
		$color = "#777777";
		$embed = new \Discord\Parts\Embed\Embed($this->discord);
		$embed->setColor($color);
		$embed->setTitle($character_name);
		$description = $data["class"]["name"] . " " . number_format($data["power"], 0, ".", ",") . " PS\n";
		$embed->addField([
			"name" => "**__Clã__**",
			"value" => "[" . $data["clan"]["name"] . "](https://app.mir4info.com/clan/" . $data["clan"]["id"] . ")",
			"inline" => true
		]);
		$embed->addField([
			"name" => "**__Servidor__**",
			"value" => "[" . $data["server"]["name"] . "](https://app.mir4info.com/server/" . $data["server"]["id"] . ")",
			"inline" => true
		]);
		$embed->addField([
			"name" => "**__Região__**",
			"value" => "[" . $data["region"]["name"] . "](https://app.mir4info.com/region/" . $data["region"]["id"] . ")",
			"inline" => true
		]);
		$embed->setDescription($description);
		$embed->setThumbnail("https://app.mir4info.com/assets/images/mir4/" . strtolower($data["class"]["name"]) . ".webp");
		$embed->setURL("https://app.mir4info.com/character/" . $data["id"]);
		$embed->setTimestamp();
		$embed->setFooter("[Mir4info.com](https://mir4info.com)", "https://app.mir4info.com/assets/images/icon/icon-white-transparent-717x671-no-pad.png");
		$channel->sendEmbed($embed);
		foreach ($interaction->guild->members as $member) {
			if ($member->nick === null) continue;
			if ($member->id === $interaction->member->id) continue;
			$nick = substr($member->nick, 7);
			if ($nick === $character_name) return $channel->sendMessage("este nome já está sendo usado por outro membro do clã");
		}
		$power_k = round($data["power"] / 1000, 0);
		$class_identifier = substr($data["class"]["name"], 0, 1);
		$new_nick = "[" . $power_k . $class_identifier . "] " . $character_name;
		$interaction->member->setNickname($character_name);
		$role_match = [$data["clan"]["name"], $data["server"]["name"], $data["region"]["name"]];
		foreach ($interaction->guild->roles as $role) if (in_array($role->name, $role_match)) $interaction->member->addRole($role);
	}
}
