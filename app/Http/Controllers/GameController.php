<?php

namespace App\Http\Controllers;

use App\Services\PrologService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recibe el tipo de consulta y sus parámetros desde el formulario y delega la ejecución a PrologService.
 * No contiene lógica de Prolog
 */

class GameController extends Controller
{
    public function __construct(
        private readonly PrologService $prolog   // Laravel resuelve la inyección automáticamente
    ) {}

    /** Muestra la consola vacía (GET /). */
    public function index(): View
    {
        return view('game.index', $this->viewData());
    }

    /** Procesa la consulta del formulario (POST /query). */
    public function query(Request $request): View
    {
        $type    = $request->input('query_type', '');
        $results = [];
        $error   = null;

        try {
            $results = match($type) {
                // ── Listados (sin parámetros) ─────────────────────────
                'list_characters'        => $this->prolog->listCharacters(),
                'list_missions'          => $this->prolog->listMissions(),
                'list_enemies'           => $this->prolog->listEnemies(),
                'same_level'             => $this->prolog->sameLevel(),

                // ── Misiones ──────────────────────────────────────────
                'can_accept'             => $this->prolog->canAcceptMission(
                                               $request->input('character', ''),
                                               $request->input('mission', 'm1')
                                           ),
                'has_requirement'        => $this->prolog->hasRequirement(
                                               $request->input('character', ''),
                                               $request->input('item', '')
                                           ),
                'meets_all_requirements' => $this->prolog->meetsAllRequirements(
                                               $request->input('character', ''),
                                               $request->input('mission', 'm1')
                                           ),
                'available_missions'     => $this->prolog->availableMissions(
                                               $request->input('character', '')
                                           ),
                'generate_report'        => $this->prolog->generateReport(
                                               $request->input('characters', []),
                                               $request->input('mission', 'm1')
                                           ),

                // ── Combate ───────────────────────────────────────────
                'individual_attack'      => $this->prolog->individualAttack(
                                               $request->input('character', ''),
                                               $request->input('enemy', '')
                                           ),
                'group_attack'           => $this->prolog->groupAttack(
                                               $request->input('characters', []),
                                               $request->input('enemy', '')
                                           ),

                // ── Estadísticas (reglas nuevas) ──────────────────────
                'xp_accumulated'         => $this->prolog->xpAccumulated(
                                               (int) $request->input('missions_count', 0)
                                           ),
                'total_damage'           => $this->prolog->totalDamage(
                                               $request->input('character', '')
                                           ),
                'is_expert'              => $this->prolog->isExpert(
                                               $request->input('character', '')
                                           ),
                'merge_team'             => $this->prolog->mergeTeam(
                                               $request->input('character1', ''),
                                               $request->input('character2', '')
                                           ),

                default => ["Tipo de consulta no reconocido: '{$type}'"],
            };
        } catch (\Throwable $e) {
            // Solo expone el mensaje, no el stack trace, para no filtrar rutas del servidor
            $error = "Error al ejecutar consulta: " . $e->getMessage();
        }

        return view('game.index', array_merge(
            $this->viewData(),
            compact('results', 'error', 'type')
        ));
    }

    /**
     * Datos compartidos por index() y query().
     * Si se modifica game.pl, se actualiza también aquí.
     */
    private function viewData(): array
    {
        return [
            'characters' => ['Elara', 'Kael', 'Rin', 'Alex', 'Steve', 'Raúl'],
            'missions'   => ['m1', 'm2', 'm3'],
            'enemies'    => ['Esqueleto', 'Zombie', 'Dragon'],
            'items'      => ['espada', 'escudo', 'pocion', 'arco', 'flechas',
                             'varita', 'grimorio', 'amuleto', 'daga', 'hacha', 'latigo'],
        ];
    }
}