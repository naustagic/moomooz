const Discord = require("discord.js");
const https = require("https");
// Require the necessary discord.js classes
const { Client } = require('discord.js');

const { Client, GatewayIntentBits } = require('discord.js');

const client = new Discord.Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
  ]
})

const token = "MTA3Njk4MzI5NDUzNDYyMzMwMg.G-OYoW.kKpcRoJNUjlIo6c7GhMMGxIDfTBiwlDSGTip14";

// Configurações
const config = {
  guilds: {
    // O nome do guild
    guild1: {
      // O nome do clã
      clanName: "UNFORGIVEN A",
      // A ID da role do clã
      roleId: "ROLE_ID_DO_CLAN_AQUI"
    },
    guild2: {
      clanName: "OUTRO_CLAN",
      roleId: "ROLE_ID_DO_CLAN_AQUI"
    },
    guild3: {
      clanName: "MAIS_UM_CLAN",
      roleId: "ROLE_ID_DO_CLAN_AQUI"
    },
    guild4: {
      clanName: "E_ASSIM_POR_DIANTE",
      roleId: "ROLE_ID_DO_CLAN_AQUI"
    }
  },
  // A ID do servidor
  serverId: "ID_DO_SERVIDOR_AQUI"
};

client.on("ready", () => {
  console.log(`Logged in as ${client.user.tag}`);
});

client.on("message", async message => {
  if (!message.guild || message.author.bot) return;

  if (message.content.startsWith("!cadastrar ")) {
    const playerName = message.content.split("!cadastrar ")[1];

    const searchUrl = `https://api.mir4info.com/v3/search/${playerName}`;
    https.get(searchUrl, res => {
      if (res.statusCode !== 200) {
        message.reply("Não foi possível encontrar esse jogador.");
        return;
      }

      let rawData = "";
      res.on("data", chunk => {
        rawData += chunk;
      });

      res.on("end", () => {
        try {
          const json = JSON.parse(rawData);

          // Change user's nickname to player name
          const player = json.characters[0];
          const member = message.guild.members.cache.get(message.author.id);
          member.setNickname(player.name);

          // Check guild/clan
          const guild = config.guilds["guild1"];
          if (player.clan.name === guild.clanName && player.server.id === config.serverId) {
            // Give user role for clan
            const role = message.guild.roles.cache.get(guild.roleId);
            member.roles.add(role);

            // Send player info in embed
            const embed = new Discord.MessageEmbed()
              .setColor(getColorByPower(player.data.power))
              .setTitle(player.name)
              .setDescription(
                `${player.data.class.name} ${player.data.power.toLocaleString()} PS\n` +
                `Clan: ${player.clan.name}\n` +
                `Clan Rank: ${player.clan.rank}\n` +
                `Server: ${player.server.name}\n` +
                `Server Rank: ${player.server.rank}\n` +
                `Region: ${player.region.name}\n` +
                `Region Rank: ${player.region.rank}\n` +
                `Global Rank: ${player.data.global_rank}\n`
              );
            message.channel.send(embed);
          } else {
            // User doesn't belong to guild/clan or server
            const roleId = "ID_DA_ROLE_DE_OUTRO_SERVIDOR_AQUI";
            const role = message.guild.roles.cache.get(roleId);
            member.roles.add(role);

            message.reply("Você não pertence a esse servidor ou clã.");
          }
        } catch (error) {
          console.error(error);
          message.reply("Não foi possível encontrar esse jogador.");
        }

        // Helper function to get color based on player power
          function getColorByPower(power) {
            if (power > 0) return "#28674F";
            if (power > 135000) return "#20416b";
            if (power > 170000) return "#751d20";
            return "#FFFFFF";
          }

      });
    });
  }
});

