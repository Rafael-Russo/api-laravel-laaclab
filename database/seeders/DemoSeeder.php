<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\CurtidaAvaliacao;
use App\Models\Jogo;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Usuario demo, a biblioteca dele e a conversa em volta do catalogo.
 *
 * O frontend Flask entra com DEMO_EMAIL/DEMO_SENHA — as mesmas credenciais
 * estao no README. Mudar aqui exige mudar la.
 *
 * As notas sao espalhadas de proposito pelos dois lados de 5.0: o Detalhe do
 * jogo separa polegar para cima de para baixo nesse corte, e um seed inteiro
 * de um lado so esconderia metade da tela.
 */
class DemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'nikola@laac.test';

    public const DEMO_SENHA = 'laaclab123';

    /** Quantos jogos do catalogo entram na biblioteca do demo. */
    private const TAMANHO_DA_BIBLIOTECA = 24;

    /** @var list<array{string, string}> */
    private const COMPANHIA = [
        ['rafaFPS', 'rafa@laac.test'],
        ['Flamezera', 'flame@laac.test'],
        ['Leozin', 'leo@laac.test'],
        ['GamerPro', 'gamerpro@laac.test'],
        ['JoaozinhoB84', 'joao@laac.test'],
    ];

    /** Nota e comentario, alternados sobre os jogos avaliados. */
    private const OPINIOES = [
        ['9.1', 'Continua sendo referencia do genero. Roda liso ate em maquina modesta.'],
        ['8.4', 'Otimo, mas o matchmaking demora demais em horario de pico.'],
        ['7.0', 'Vale o preco. Perde folego depois de umas trinta horas.'],
        ['6.2', 'Divertido com amigos, esquecivel sozinho.'],
        ['4.8', 'A ultima atualizacao quebrou mais do que consertou.'],
        ['3.5', 'Trava toda vez que entro no lobby. Injogavel para mim.'],
        ['2.0', 'Comprei na promocao e ainda me arrependi.'],
        ['8.9', 'A trilha sonora sozinha ja justifica.'],
        ['5.0', 'Exatamente na media: nao decepciona nem surpreende.'],
        ['4.1', 'Boa ideia, execucao apressada. Faltou tempo de forno.'],
    ];

    public function run(): void
    {
        $demo = Usuario::firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'nome_usuario' => 'nikola98',
                'senha_hash' => self::DEMO_SENHA,
                'idade' => 27,
                'bio' => 'Jogando, aprendendo e evoluindo todos os dias.',
                'nivel' => 12,
            ],
        );

        $companhia = [];
        foreach (self::COMPANHIA as [$nome, $email]) {
            $companhia[] = Usuario::firstOrCreate(
                ['email' => $email],
                [
                    'nome_usuario' => $nome,
                    'senha_hash' => self::DEMO_SENHA,
                    'nivel' => 4,
                ],
            );
        }

        $catalogo = Jogo::orderBy('id')->get();
        $daBiblioteca = $catalogo->take(self::TAMANHO_DA_BIBLIOTECA);

        // Um a cada quatro entra como favorito, para a tela ter os dois estados.
        $vinculos = [];
        foreach ($daBiblioteca->values() as $posicao => $jogo) {
            $vinculos[$jogo->id] = ['favorito' => $posicao % 4 === 0];
        }
        $demo->jogos()->syncWithoutDetaching($vinculos);

        $autores = array_merge([$demo], $companhia);

        foreach ($catalogo->values() as $posicao => $jogo) {
            // Nem todo jogo do catalogo recebe avaliacao: a tela de Detalhe
            // precisa saber lidar com o caso vazio.
            if ($posicao % 3 === 2) {
                continue;
            }

            [$nota, $comentario] = self::OPINIOES[$posicao % count(self::OPINIOES)];
            $autor = $autores[$posicao % count($autores)];

            $avaliacao = Avaliacao::firstOrCreate(
                ['usuario_id' => $autor->id, 'jogo_id' => $jogo->id],
                ['nota' => $nota, 'comentario' => $comentario],
            );

            // Curtidas vindas de quem nao escreveu a avaliacao.
            foreach ($autores as $indice => $curtidor) {
                if ($curtidor->id === $autor->id || ($posicao + $indice) % 3 !== 0) {
                    continue;
                }

                CurtidaAvaliacao::firstOrCreate([
                    'avaliacao_id' => $avaliacao->id,
                    'usuario_id' => $curtidor->id,
                ]);
            }
        }
    }
}
