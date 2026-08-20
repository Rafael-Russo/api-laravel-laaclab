<?php

namespace Database\Seeders;

use App\Models\Jogo;
use App\Models\Plataforma;
use Illuminate\Database\Seeder;

/**
 * Catalogo de jogos reais.
 *
 * A capa aponta para a CDN da Steam, derivada do appid da loja: nada e baixado
 * nem versionado, e o frontend so precisa de uma URL. Um appid errado degrada
 * para imagem quebrada, nunca para erro — o frontend tem fallback.
 *
 * Semear e idempotente (firstOrCreate por nome), para `db:seed` repetido nao
 * multiplicar o catalogo.
 */
class CatalogoSeeder extends Seeder
{
    private const CDN = 'https://cdn.cloudflare.steamstatic.com/steam/apps/%d/header.jpg';

    /** @var list<string> */
    private const PLATAFORMAS = [
        'PC',
        'PlayStation 5',
        'Xbox Series X|S',
        'Nintendo Switch',
    ];

    /**
     * appid, nome, genero, classificacao, desenvolvedora, lancamento, plataformas.
     *
     * @var list<array{int, string, string, string, string, string, list<int>}>
     */
    private const JOGOS = [
        [730, 'Counter-Strike 2', 'FPS', '16', 'Valve', '2023-09-27', [0]],
        [570, 'Dota 2', 'MOBA', '12', 'Valve', '2013-07-09', [0]],
        [440, 'Team Fortress 2', 'FPS', '12', 'Valve', '2007-10-10', [0]],
        [550, 'Left 4 Dead 2', 'FPS', '18', 'Valve', '2009-11-17', [0]],
        [620, 'Portal 2', 'Puzzle', 'L', 'Valve', '2011-04-19', [0]],
        [220, 'Half-Life 2', 'FPS', '16', 'Valve', '2004-11-16', [0]],
        [4000, "Garry's Mod", 'Sandbox', '12', 'Facepunch Studios', '2006-11-29', [0]],
        [271590, 'Grand Theft Auto V', 'Ação', '18', 'Rockstar North', '2015-04-14', [0, 1, 2]],
        [1174180, 'Red Dead Redemption 2', 'Aventura', '18', 'Rockstar Games', '2019-12-05', [0, 1, 2]],
        [1091500, 'Cyberpunk 2077', 'RPG', '18', 'CD PROJEKT RED', '2020-12-10', [0, 1, 2]],
        [292030, 'The Witcher 3: Wild Hunt', 'RPG', '18', 'CD PROJEKT RED', '2015-05-18', [0, 1, 2, 3]],
        [1245620, 'ELDEN RING', 'RPG', '16', 'FromSoftware', '2022-02-25', [0, 1, 2]],
        [72850, 'The Elder Scrolls V: Skyrim', 'RPG', '18', 'Bethesda', '2011-11-10', [0, 3]],
        [377160, 'Fallout 4', 'RPG', '18', 'Bethesda', '2015-11-09', [0, 1, 2]],
        [413150, 'Stardew Valley', 'Simulação', 'L', 'ConcernedApe', '2016-02-26', [0, 1, 2, 3]],
        [105600, 'Terraria', 'Aventura', '10', 'Re-Logic', '2011-05-16', [0, 1, 2, 3]],
        [252490, 'Rust', 'Sobrevivência', '18', 'Facepunch Studios', '2018-02-08', [0]],
        [892970, 'Valheim', 'Sobrevivência', '12', 'Iron Gate AB', '2021-02-02', [0, 2]],
        [108600, 'Project Zomboid', 'Sobrevivência', '18', 'The Indie Stone', '2013-11-08', [0]],
        [322330, "Don't Starve Together", 'Sobrevivência', '10', 'Klei Entertainment', '2016-04-21', [0, 1, 2, 3]],
        [367520, 'Hollow Knight', 'Plataforma', '10', 'Team Cherry', '2017-02-24', [0, 3]],
        [1145360, 'Hades', 'Roguelike', '12', 'Supergiant Games', '2020-09-17', [0, 1, 2, 3]],
        [588650, 'Dead Cells', 'Roguelike', '16', 'Motion Twin', '2018-08-07', [0, 1, 2, 3]],
        [646570, 'Slay the Spire', 'Roguelike', '10', 'Mega Crit Games', '2019-01-23', [0, 1, 3]],
        [632360, 'Risk of Rain 2', 'Roguelike', '12', 'Hopoo Games', '2020-08-11', [0, 1, 2, 3]],
        [250900, 'The Binding of Isaac: Rebirth', 'Roguelike', '16', 'Nicalis', '2014-11-04', [0, 3]],
        [1794680, 'Vampire Survivors', 'Roguelike', '12', 'poncle', '2022-10-20', [0, 1, 2, 3]],
        [262060, 'Darkest Dungeon', 'Estratégia', '16', 'Red Hook Studios', '2016-01-19', [0, 1, 3]],
        [268910, 'Cuphead', 'Plataforma', '10', 'Studio MDHR', '2017-09-29', [0, 1, 2, 3]],
        [294100, 'RimWorld', 'Simulação', '16', 'Ludeon Studios', '2018-10-17', [0]],
        [227300, 'Euro Truck Simulator 2', 'Simulação', 'L', 'SCS Software', '2012-10-18', [0]],
        [244210, 'Assetto Corsa', 'Corrida', 'L', 'Kunos Simulazioni', '2014-12-19', [0, 1, 2]],
        [275850, "No Man's Sky", 'Aventura', '12', 'Hello Games', '2016-08-12', [0, 1, 2, 3]],
        [1172470, 'Apex Legends', 'FPS', '16', 'Respawn Entertainment', '2020-11-04', [0, 1, 2, 3]],
        [1085660, 'Destiny 2', 'FPS', '16', 'Bungie', '2019-10-01', [0, 1, 2]],
        [359550, "Tom Clancy's Rainbow Six Siege", 'FPS', '18', 'Ubisoft Montreal', '2015-12-01', [0, 1, 2]],
        [230410, 'Warframe', 'Ação', '16', 'Digital Extremes', '2013-03-25', [0, 1, 2, 3]],
        [236390, 'War Thunder', 'Simulação', '12', 'Gaijin Entertainment', '2013-08-15', [0, 1, 2]],
        [578080, 'PUBG: BATTLEGROUNDS', 'FPS', '16', 'KRAFTON', '2017-12-21', [0, 1, 2]],
        [1966720, 'Lethal Company', 'Terror', '16', 'Zeekerss', '2023-10-23', [0]],
        [739630, 'Phasmophobia', 'Terror', '16', 'Kinetic Games', '2020-09-18', [0, 1, 2]],
        [594650, 'Hunt: Showdown', 'FPS', '18', 'Crytek', '2019-08-27', [0, 1, 2]],
        [1237970, 'Titanfall 2', 'FPS', '16', 'Respawn Entertainment', '2016-10-28', [0, 1, 2]],
        [2050650, 'Resident Evil 4', 'Terror', '18', 'CAPCOM', '2023-03-24', [0, 1, 2]],
        [1817070, "Marvel's Spider-Man Remastered", 'Aventura', '14', 'Insomniac Games', '2022-08-12', [0, 1]],
        [1203220, 'NARAKA: BLADEPOINT', 'Ação', '16', '24 Entertainment', '2021-08-11', [0, 1, 2]],
    ];

    public function run(): void
    {
        $plataformas = [];
        foreach (self::PLATAFORMAS as $nome) {
            $plataformas[] = Plataforma::firstOrCreate(['nome' => $nome]);
        }

        foreach (self::JOGOS as [$appid, $nome, $genero, $classificacao, $desenvolvedora, $lancamento, $indices]) {
            $jogo = Jogo::firstOrCreate(
                ['nome' => $nome],
                [
                    'descricao' => "{$nome}, de {$desenvolvedora}.",
                    'genero' => $genero,
                    'classificacao' => $classificacao,
                    'desenvolvedora' => $desenvolvedora,
                    'data_lancamento' => $lancamento,
                    'capa_url' => sprintf(self::CDN, $appid),
                ],
            );

            $ids = array_map(static fn (int $i): int => $plataformas[$i]->id, $indices);
            $jogo->plataformas()->syncWithoutDetaching($ids);
        }
    }
}
